<?php
declare(strict_types=1);
/**
 * ADM-04 — Hoàn tiền: yêu cầu → duyệt / từ chối → ghi nhận đã hoàn thủ công, có lịch sử từng bước.
 * Không chuyển tiền thật. Khi admin ghi nhận chứng từ hoàn tiền, trạng thái thanh toán cập nhật trong cùng transaction.
 */

const CAS_REFUND_LABELS = ['requested' => 'Chờ duyệt', 'approved' => 'Đã duyệt – chờ hoàn', 'recorded' => 'Đã ghi nhận hoàn', 'rejected' => 'Từ chối'];
const CAS_REFUND_FLOW = ['requested' => ['approved', 'rejected'], 'approved' => ['recorded', 'rejected'], 'recorded' => [], 'rejected' => []];
const CAS_REFUND_OPEN = ['requested', 'approved', 'recorded'];

/** Số tiền tối đa còn có thể hoàn của một đơn (chỉ đơn đã thanh toán đủ). */
function cas_refund_remaining(array $order): int
{
    if (!in_array($order['payment_status'], ['paid','partially_refunded'], true)) {
        return 0;
    }
    $used = (float)cas_value('SELECT COALESCE(SUM(amount),0) FROM refunds WHERE order_id=? AND status IN (\'requested\',\'approved\',\'recorded\')', [$order['order_id']]);
    return max(0, (int)round((float)$order['total_amount'] - $used));
}

function cas_refund_create(PDO $db, array $user): string
{
    cas_require_permission('refund.request', 'refund_create');
    $code = strtoupper(cas_in_str('order_code', 6, 40, 'Mã đơn'));
    $amount = cas_in_int('amount', 1000, 100000000, 'Số tiền hoàn');
    $reason = cas_in_str('reason', 5, 255, 'Lý do hoàn tiền');
    $token = cas_in_token();
    $db->beginTransaction();
    $q = $db->prepare('SELECT order_id, order_code, total_amount, payment_status, order_status FROM orders WHERE order_code=? FOR UPDATE');
    $q->execute([$code]);
    $order = $q->fetch();
    if (!$order) {
        $db->rollBack();
        throw new DomainException('Không tìm thấy đơn ' . $code . '.');
    }
    if (!in_array($order['payment_status'], ['paid','partially_refunded'], true)) {
        $db->rollBack();
        throw new DomainException('Đơn ' . $code . ' chưa ghi nhận thanh toán đủ (' . $order['payment_status'] . '), chưa thể tạo yêu cầu hoàn tiền.');
    }
    $remaining = cas_refund_remaining($order);
    if ($amount > $remaining) {
        $db->rollBack();
        throw new DomainException('Số tiền hoàn vượt mức cho phép. Đơn ' . $code . ' chỉ còn có thể hoàn ' . cas_money($remaining) . '.');
    }
    try {
        $db->prepare('INSERT INTO refunds(order_id,amount,reason,status,requested_by,request_token) VALUES(?,?,?,\'requested\',?,?)')
           ->execute([$order['order_id'], $amount, $reason, $user['user_id'], $token]);
    } catch (PDOException $e) {
        $db->rollBack();
        if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'uq_refund_request')) {
            throw new DomainException('Yêu cầu này đã được gửi trước đó, không tạo trùng.');
        }
        throw $e;
    }
    $id = (int)$db->lastInsertId();
    $db->prepare('INSERT INTO refund_events(refund_id,from_status,to_status,actor_id,note) VALUES(?,NULL,\'requested\',?,?)')->execute([$id, $user['user_id'], $reason]);
    $db->commit();
    cas_audit('refund_create', 'refund', $id, ['order' => $code, 'amount' => $amount]);
    return 'Đã tạo yêu cầu hoàn RF-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT) . ' cho đơn ' . $code . ' (' . cas_money($amount) . ').';
}

function cas_refund_transition(PDO $db, array $user): string
{
    cas_require_permission('refund.review', 'refund_review');
    $id = cas_in_id('id');
    $to = cas_in_choice('status', ['approved', 'rejected', 'recorded'], 'Trạng thái hoàn tiền');
    $note = trim((string)($_POST['note'] ?? ''));
    if (mb_strlen($note) > 255) {
        throw new DomainException('Ghi chú tối đa 255 ký tự.');
    }
    $db->beginTransaction();
    // Serialize all refunds of an order by locking orders before any refund row.
    $key=$db->prepare('SELECT order_id FROM refunds WHERE refund_id=?');
    $key->execute([$id]);$orderId=(int)$key->fetchColumn();
    if(!$orderId){$db->rollBack();throw new DomainException('Không tìm thấy yêu cầu hoàn tiền.');}
    $orderLock=$db->prepare('SELECT order_id FROM orders WHERE order_id=? FOR UPDATE');
    $orderLock->execute([$orderId]);
    $q = $db->prepare('SELECT r.*, o.order_code, o.total_amount, o.payment_status FROM refunds r JOIN orders o ON o.order_id=r.order_id WHERE r.refund_id=? FOR UPDATE');
    $q->execute([$id]);
    $r = $q->fetch();
    if (!$r) {
        $db->rollBack();
        throw new DomainException('Không tìm thấy yêu cầu hoàn tiền.');
    }
    if (!in_array($to, CAS_REFUND_FLOW[$r['status']] ?? [], true)) {
        $db->rollBack();
        throw new DomainException('Không thể chuyển yêu cầu từ "' . (CAS_REFUND_LABELS[$r['status']] ?? $r['status']) . '" sang "' . CAS_REFUND_LABELS[$to] . '".');
    }
    if ($to === 'rejected' && mb_strlen($note) < 5) {
        $db->rollBack();
        throw new DomainException('Từ chối hoàn tiền cần ghi lý do (tối thiểu 5 ký tự).');
    }
    $reference = null;
    if ($to === 'recorded') {
        $reference = cas_in_str('reference', 3, 120, 'Mã / ghi chú giao dịch hoàn');
    }
    $db->prepare('UPDATE refunds SET status=?, reviewed_by=?, reviewed_at=NOW(), reference=COALESCE(?,reference) WHERE refund_id=?')
       ->execute([$to, $user['user_id'], $reference, $id]);
    $db->prepare('INSERT INTO refund_events(refund_id,from_status,to_status,actor_id,note) VALUES(?,?,?,?,?)')
       ->execute([$id, $r['status'], $to, $user['user_id'], trim($note . ($reference ? ' | Mã GD: ' . $reference : '')) ?: null]);
    if($to==='recorded'){
        $q=$db->prepare("SELECT COALESCE(SUM(amount),0) FROM refunds WHERE order_id=? AND status='recorded'");
        $q->execute([$orderId]);$refunded=(int)round((float)$q->fetchColumn());
        $total=(int)round((float)$r['total_amount']);
        if($refunded>$total)throw new DomainException('Tổng hoàn tiền vượt số tiền đơn.');
        $payment=$refunded===$total?'refunded':'partially_refunded';
        $db->prepare('UPDATE orders SET payment_status=? WHERE order_id=?')->execute([$payment,$orderId]);
    }
    $db->commit();
    cas_audit('refund_' . $to, 'refund', $id, ['order' => $r['order_code'], 'amount' => (float)$r['amount'], 'from' => $r['status'], 'reference' => $reference]);
    return 'RF-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT) . ': ' . CAS_REFUND_LABELS[$r['status']] . ' → ' . CAS_REFUND_LABELS[$to] . '.';
}

function cas_refunds_page(): void
{
    global $db, $user;
    cas_ops_post([
        'refund_create' => static fn() => cas_refund_create($db, $user),
        'refund_transition' => static fn() => cas_refund_transition($db, $user),
    ]);
    $status = isset(CAS_REFUND_LABELS[$_GET['status'] ?? '']) ? (string)$_GET['status'] : '';
    $kpi = cas_rows("SELECT SUM(status='requested') pending, SUM(status='approved') approved,
                     COALESCE(SUM(CASE WHEN status='recorded' THEN amount END),0) recorded, SUM(status='rejected') rejected FROM refunds")[0];
    $rows = cas_rows("SELECT r.*, o.order_code, o.total_amount, o.payment_status, c.full_name customer, rq.full_name requester, rv.full_name reviewer
                      FROM refunds r JOIN orders o ON o.order_id=r.order_id JOIN customers c ON c.customer_id=o.customer_id
                      LEFT JOIN users rq ON rq.user_id=r.requested_by LEFT JOIN users rv ON rv.user_id=r.reviewed_by"
                      . ($status ? ' WHERE r.status=?' : '') . ' ORDER BY r.refund_id DESC LIMIT 200', $status ? [$status] : []);
    $events = [];
    foreach (cas_rows('SELECT e.*, u.full_name actor FROM refund_events e LEFT JOIN users u ON u.user_id=e.actor_id ORDER BY e.event_id') as $e) {
        $events[(int)$e['refund_id']][] = $e;
    }
    $paidOrders = cas_rows("SELECT o.order_code, o.total_amount FROM orders o WHERE o.payment_status IN ('paid','partially_refunded') ORDER BY o.order_id DESC LIMIT 50");
    $canReview = cas_can($user, 'refund.review');

    cas_ops_start('Hoàn tiền', 'Yêu cầu hoàn tiền theo lý do và số tiền, có người duyệt và lịch sử. Hệ thống không tự chuyển tiền.', 'bi-arrow-counterclockwise');
    ?>
<section class="admin-grid-4 admin-section">
  <article class="admin-stat"><div class="admin-stat-icon"><i class="bi bi-hourglass-split"></i></div><div class="admin-stat-label">Chờ duyệt</div><div class="admin-stat-value"><?= (int)$kpi['pending'] ?></div><div class="admin-stat-note">Cần quản trị viên xem xét</div></article>
  <article class="admin-stat"><div class="admin-stat-icon"><i class="bi bi-check2-circle"></i></div><div class="admin-stat-label">Đã duyệt – chờ hoàn</div><div class="admin-stat-value"><?= (int)$kpi['approved'] ?></div><div class="admin-stat-note">Chờ ghi nhận chuyển khoản</div></article>
  <article class="admin-stat"><div class="admin-stat-icon"><i class="bi bi-cash-coin"></i></div><div class="admin-stat-label">Đã ghi nhận hoàn</div><div class="admin-stat-value"><?= cas_money($kpi['recorded']) ?></div><div class="admin-stat-note">Tổng tiền đã hoàn thủ công</div></article>
  <article class="admin-stat"><div class="admin-stat-icon"><i class="bi bi-x-circle"></i></div><div class="admin-stat-label">Từ chối</div><div class="admin-stat-value"><?= (int)$kpi['rejected'] ?></div><div class="admin-stat-note">Có lý do trong lịch sử</div></article>
</section>
<section class="admin-grid-2 admin-section">
<article class="admin-card">
  <h2 class="admin-card-title">Tạo yêu cầu hoàn tiền</h2>
  <p class="admin-card-subtitle">Đơn đã thu đủ hoặc hoàn một phần; tổng yêu cầu không vượt giá trị đơn.</p>
  <form method="post" class="admin-form-grid mt-3">
    <?= cas_csrf_form() ?><input type="hidden" name="action" value="refund_create"><input type="hidden" name="request_token" value="<?= cas_form_token() ?>">
    <div><label class="admin-label">Mã đơn</label><input class="admin-input" name="order_code" list="paidOrders" required maxlength="40" placeholder="CSH-2026-...">
      <datalist id="paidOrders"><?php foreach ($paidOrders as $o): ?><option value="<?= cas_e($o['order_code']) ?>"><?= cas_money($o['total_amount']) ?></option><?php endforeach; ?></datalist></div>
    <div><label class="admin-label">Số tiền hoàn (VND)</label><input class="admin-input" name="amount" type="number" min="1000" step="1000" required></div>
    <div class="full"><label class="admin-label">Lý do</label><input class="admin-input" name="reason" minlength="5" maxlength="255" required placeholder="Khách huỷ trước khi chuẩn bị hoa, hoa không đúng mẫu..."></div>
    <div class="full"><button class="admin-btn" type="submit">Gửi yêu cầu</button></div>
  </form>
</article>
<article class="admin-card">
  <h2 class="admin-card-title">Quy trình</h2>
  <div class="admin-list mt-3">
    <div class="admin-list-item"><div><strong>1. Chờ duyệt</strong><small class="d-block mt-1">Nhân viên hoặc quản trị viên tạo yêu cầu kèm lý do.</small></div></div>
    <div class="admin-list-item"><div><strong>2. Duyệt / từ chối</strong><small class="d-block mt-1">Chỉ quản trị viên, chủ sở hữu; từ chối phải ghi lý do.</small></div></div>
    <div class="admin-list-item"><div><strong>3. Ghi nhận đã hoàn</strong><small class="d-block mt-1">Nhập mã giao dịch chuyển khoản thủ công. Không có cổng thanh toán, hệ thống không tự đánh dấu chuyển tiền.</small></div></div>
  </div>
</article>
</section>
<section class="admin-card admin-section">
  <form method="get" class="cas-admin-stack mb-3"><?= cas_select('status', ['' => 'Mọi trạng thái'] + CAS_REFUND_LABELS, $status) ?><button class="admin-btn-light" type="submit">Lọc</button><span class="admin-badge"><?= count($rows) ?> yêu cầu</span></form>
  <div class="admin-table-wrap"><table class="admin-table">
    <thead><tr><th>Mã</th><th>Đơn / khách</th><th>Số tiền</th><th>Lý do</th><th>Trạng thái</th><th>Người tạo / duyệt</th><th>Thao tác</th></tr></thead><tbody>
    <?php foreach ($rows as $r): $next = CAS_REFUND_FLOW[$r['status']] ?? []; ?>
    <tr><td><strong>RF-<?= str_pad((string)$r['refund_id'], 4, '0', STR_PAD_LEFT) ?></strong><div class="cas-muted"><?= cas_e($r['created_at']) ?></div></td>
      <td><?= cas_e($r['order_code']) ?><div class="cas-muted"><?= cas_e($r['customer']) ?> · đơn <?= cas_money($r['total_amount']) ?></div></td>
      <td><strong><?= cas_money($r['amount']) ?></strong></td><td><?= cas_e($r['reason']) ?><?php if ($r['reference']): ?><div class="cas-muted">Mã GD: <?= cas_e($r['reference']) ?></div><?php endif; ?></td>
      <td><?= cas_status_badge(CAS_REFUND_LABELS[$r['status']] ?? $r['status'], ['requested' => 'warning', 'approved' => '', 'recorded' => 'success', 'rejected' => 'danger'][$r['status']] ?? '') ?></td>
      <td><?= cas_e($r['requester'] ?? '—') ?><div class="cas-muted"><?= $r['reviewer'] ? 'Duyệt: ' . cas_e($r['reviewer']) . ' · ' . cas_e($r['reviewed_at']) : 'Chưa duyệt' ?></div></td>
      <td><?php if ($canReview && $next): ?>
        <form method="post" class="cas-admin-table-form" data-confirm="Xác nhận cập nhật yêu cầu hoàn tiền này?"><?= cas_csrf_form() ?><input type="hidden" name="action" value="refund_transition"><input type="hidden" name="id" value="<?= (int)$r['refund_id'] ?>">
          <?= cas_select('status', array_intersect_key(CAS_REFUND_LABELS, array_flip($next)), $next[0]) ?>
          <input class="admin-input" name="<?= $r['status'] === 'approved' ? 'reference' : 'note' ?>" maxlength="120" placeholder="<?= $r['status'] === 'approved' ? 'Mã giao dịch hoàn' : 'Ghi chú / lý do' ?>">
          <?php if ($r['status'] === 'approved'): ?><input type="hidden" name="note" value=""><?php endif; ?><button class="admin-btn-light" type="submit">Lưu</button></form>
      <?php else: ?><span class="cas-muted"><?= $next ? 'Chờ quản trị viên duyệt' : 'Đã đóng' ?></span><?php endif; ?>
        <?php if (!empty($events[(int)$r['refund_id']])): ?><details class="mt-1"><summary class="cas-muted">Lịch sử (<?= count($events[(int)$r['refund_id']]) ?>)</summary>
          <?php foreach ($events[(int)$r['refund_id']] as $e): ?><div class="cas-muted"><?= cas_e($e['created_at']) ?> · <?= cas_e($e['actor'] ?? '—') ?>: <?= cas_e($e['from_status'] ? (CAS_REFUND_LABELS[$e['from_status']] ?? $e['from_status']) . ' → ' : '') ?><?= cas_e(CAS_REFUND_LABELS[$e['to_status']] ?? $e['to_status']) ?><?= $e['note'] ? ' — ' . cas_e($e['note']) : '' ?></div><?php endforeach; ?></details><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7"><div class="admin-empty">Chưa có yêu cầu hoàn tiền.</div></td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php
    cas_ops_end();
}
