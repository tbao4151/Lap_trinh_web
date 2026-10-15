<?php
declare(strict_types=1);
/** ADM-04 — Giao hàng: lịch theo ngày / khung giờ, luật chuyển trạng thái và lịch sử thay đổi. */

const CAS_DELIVERY_LABELS = ['not_delivered' => 'Chưa giao', 'out_for_delivery' => 'Đang giao', 'pickup_ready' => 'Chờ khách lấy', 'delivered' => 'Đã giao'];
/** Luật chuyển trạng thái: không nhảy tùy ý, "Đã giao" là trạng thái cuối. */
const CAS_DELIVERY_FLOW = [
    'not_delivered' => ['out_for_delivery', 'pickup_ready'],
    'out_for_delivery' => ['delivered', 'not_delivered'],   // not_delivered = giao không thành công, lên lịch lại
    'pickup_ready' => ['delivered', 'not_delivered'],
    'delivered' => [],
];
const CAS_DELIVERY_SLOTS = ['08:00 - 10:00', '10:00 - 12:00', '13:00 - 15:00', '15:00 - 17:00', '17:00 - 19:00'];

function cas_delivery_update(PDO $db, array $user): string
{
    cas_require_permission('delivery.update', 'delivery_update');
    $id = cas_in_id('id');
    $to = cas_in_choice('status', array_keys(CAS_DELIVERY_LABELS), 'Trạng thái giao');
    $note = trim((string)($_POST['note'] ?? ''));
    if (mb_strlen($note) > 255) {
        throw new DomainException('Ghi chú giao hàng tối đa 255 ký tự.');
    }
    $db->beginTransaction();
    $k=$db->prepare('SELECT order_id FROM deliveries WHERE delivery_id=?');
    $k->execute([$id]);$orderId=(int)$k->fetchColumn();
    if(!$orderId){$db->rollBack();throw new DomainException('Không tìm thấy lịch giao.');}
    $ol=$db->prepare('SELECT order_status,delivery_method FROM orders WHERE order_id=? FOR UPDATE');
    $ol->execute([$orderId]);$order=$ol->fetch();
    $q = $db->prepare('SELECT d.delivery_id, d.delivery_status, o.order_code, o.order_status FROM deliveries d JOIN orders o ON o.order_id=d.order_id WHERE d.delivery_id=? FOR UPDATE');
    $q->execute([$id]);
    $d = $q->fetch();
    if (!$d) {
        $db->rollBack();
        throw new DomainException('Không tìm thấy lịch giao.');
    }
    $from = $d['delivery_status'];
    if ($d['order_status'] === 'cancelled') {
        $db->rollBack();
        throw new DomainException('Đơn ' . $d['order_code'] . ' đã huỷ, không cập nhật giao hàng.');
    }
    if (!in_array($to, CAS_DELIVERY_FLOW[$from] ?? [], true)) {
        $db->rollBack();
        cas_audit('delivery_update', 'delivery', $id, ['from' => $from, 'to' => $to], 'rejected');
        throw new DomainException('Không thể chuyển từ "' . (CAS_DELIVERY_LABELS[$from] ?? $from) . '" sang "' . CAS_DELIVERY_LABELS[$to] . '". '
            . (CAS_DELIVERY_FLOW[$from] ?? [] ? 'Bước hợp lệ: ' . implode(', ', array_map(static fn($s) => CAS_DELIVERY_LABELS[$s], CAS_DELIVERY_FLOW[$from])) . '.' : 'Đơn đã giao xong.'));
    }
    if ($to === 'not_delivered' && mb_strlen($note) < 3) {
        $db->rollBack();
        throw new DomainException('Giao không thành công cần ghi lý do (tối thiểu 3 ký tự).');
    }
    if($to==='pickup_ready' && $order['delivery_method']!=='pickup'){
        $db->rollBack();throw new DomainException('Trạng thái Chờ lấy chỉ áp dụng cho đơn nhận tại cửa hàng.');
    }
    $nextOrder=null;
    if($to==='out_for_delivery'){
        if($order['delivery_method']!=='delivery'||$order['order_status']!=='ready'){
            $db->rollBack();throw new DomainException('Đơn cần ở trạng thái Sẵn sàng trước khi xuất giao.');
        }
        $nextOrder='delivering';
    } elseif($to==='not_delivered' && $from==='out_for_delivery'){
        if($order['order_status']!=='delivering'){
            $db->rollBack();throw new DomainException('Đơn giao không có trạng thái đang giao.');
        }
        $nextOrder='ready';
    } elseif($to==='delivered'){
        if($order['order_status']!=='delivering'){
            $db->rollBack();throw new DomainException('Đơn cần ở trạng thái đang giao trước khi xác nhận đã giao.');
        }
        $nextOrder='completed';
    }
    $db->prepare('UPDATE deliveries SET delivery_status=?, note=COALESCE(NULLIF(?,\'\'),note) WHERE delivery_id=?')->execute([$to, $note, $id]);
    if($nextOrder!==null)$db->prepare('UPDATE orders SET order_status=? WHERE order_id=?')->execute([$nextOrder,$orderId]);
    $db->prepare('INSERT INTO delivery_events(delivery_id,from_status,to_status,actor_id,note) VALUES(?,?,?,?,?)')->execute([$id, $from, $to, $user['user_id'], $note ?: null]);
    $db->commit();
    cas_audit('delivery_update', 'delivery', $id, ['order' => $d['order_code'], 'from' => $from, 'to' => $to, 'note' => $note]);
    return 'Đơn ' . $d['order_code'] . ': ' . CAS_DELIVERY_LABELS[$from] . ' → ' . CAS_DELIVERY_LABELS[$to] . '.';
}

function cas_delivery_page(): void
{
    global $db, $user;
    cas_ops_post(['delivery_update' => static fn() => cas_delivery_update($db, $user)]);
    $date = (string)($_GET['date'] ?? '');
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
    $slot = in_array($_GET['slot'] ?? '', CAS_DELIVERY_SLOTS, true) ? (string)$_GET['slot'] : '';
    $status = isset(CAS_DELIVERY_LABELS[$_GET['status'] ?? '']) ? (string)$_GET['status'] : '';
    $search = trim((string)($_GET['q'] ?? ''));
    $week = (string)($_GET['week'] ?? ($date ?: date('Y-m-d')));
    $week = preg_match('/^\d{4}-\d{2}-\d{2}$/', $week) ? $week : date('Y-m-d');

    $where = [];
    $args = [];
    if ($date !== '') {
        $where[] = 'DATE(d.scheduled_at)=?';
        $args[] = $date;
    }
    if ($slot !== '') {
        $where[] = "DATE_FORMAT(d.scheduled_at,'%H:%i')=?";
        $args[] = substr($slot, 0, 5);
    }
    if ($status !== '') {
        $where[] = 'd.delivery_status=?';
        $args[] = $status;
    }
    if ($search !== '') {
        $where[] = '(o.order_code LIKE ? OR o.recipient_name LIKE ? OR o.recipient_phone LIKE ?)';
        array_push($args, "%$search%", "%$search%", "%$search%");
    }
    $rows = cas_rows("SELECT d.*, o.order_code, o.order_status, o.recipient_name, o.recipient_phone, o.delivery_address, o.receive_time,
            (SELECT CONCAT(u.full_name,' · ',e.created_at) FROM delivery_events e LEFT JOIN users u ON u.user_id=e.actor_id
              WHERE e.delivery_id=d.delivery_id ORDER BY e.event_id DESC LIMIT 1) AS last_change
            FROM deliveries d JOIN orders o ON o.order_id=d.order_id"
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY d.scheduled_at, d.delivery_id LIMIT 300', $args);
    // Lịch 7 ngày: đếm theo ngày và trạng thái bằng SQL
    $cal = [];
    foreach (cas_rows("SELECT DATE(scheduled_at) day, COUNT(*) total, SUM(delivery_status='delivered') done
                       FROM deliveries WHERE scheduled_at >= ? AND scheduled_at < ? + INTERVAL 7 DAY GROUP BY DATE(scheduled_at)", [$week, $week]) as $c) {
        $cal[$c['day']] = $c;
    }
    $slots = cas_rows("SELECT DATE_FORMAT(scheduled_at,'%H:%i') start, COUNT(*) total FROM deliveries
                       WHERE " . ($date !== '' ? 'DATE(scheduled_at)=?' : 'scheduled_at >= CURDATE()') . ' GROUP BY start ORDER BY start', $date !== '' ? [$date] : []);
    $overdue = (int)cas_value("SELECT COUNT(*) FROM deliveries d JOIN orders o ON o.order_id=d.order_id WHERE d.scheduled_at < NOW() AND d.delivery_status<>'delivered' AND o.order_status<>'cancelled'");
    $canUpdate = cas_can($user, 'delivery.update');

    cas_ops_start('Giao hàng', 'Lịch giao theo ngày và khung giờ, cập nhật trạng thái theo đúng luồng nghiệp vụ.', 'bi-truck');
    ?>
<section class="admin-card admin-section">
  <div class="cas-admin-stack justify-content-between mb-2">
    <h2 class="admin-card-title mb-0">Lịch 7 ngày từ <?= cas_e(date('d/m/Y', strtotime($week))) ?></h2>
    <div class="cas-admin-stack"><a class="admin-btn-light" href="?week=<?= date('Y-m-d', strtotime($week . ' -7 days')) ?>">← Tuần trước</a><a class="admin-btn-light" href="?week=<?= date('Y-m-d', strtotime($week . ' +7 days')) ?>">Tuần sau →</a>
      <?php if ($overdue): ?><?= cas_status_badge($overdue . ' lịch quá giờ chưa giao', 'danger') ?><?php endif; ?></div>
  </div>
  <div class="cas-calendar">
  <?php for ($i = 0; $i < 7; $i++): $day = date('Y-m-d', strtotime($week . " +$i days")); $c = $cal[$day] ?? null; ?>
    <a class="day text-decoration-none text-reset <?= $date === $day ? 'active' : '' ?>" href="?date=<?= $day ?>&week=<?= cas_e($week) ?>">
      <strong><?= cas_e(['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][(int)date('w', strtotime($day))]) ?> <?= date('d/m', strtotime($day)) ?></strong>
      <div class="cas-muted"><?= $c ? (int)$c['total'] . ' lịch · ' . (int)$c['done'] . ' đã giao' : 'Không có lịch' ?></div></a>
  <?php endfor; ?>
  </div>
  <?php if ($slots): ?><div class="cas-admin-stack mt-3"><span class="cas-muted"><?= $date ? 'Khung giờ ngày ' . cas_e($date) . ':' : 'Khung giờ sắp tới:' ?></span><?php foreach ($slots as $s): ?><span class="admin-badge"><?= cas_e($s['start']) ?> · <?= (int)$s['total'] ?> lịch</span><?php endforeach; ?></div><?php endif; ?>
</section>
<section class="admin-card admin-section">
  <form method="get" class="cas-admin-stack mb-3">
    <input class="admin-input" type="date" name="date" value="<?= cas_e($date) ?>">
    <?= cas_select('slot', ['' => 'Mọi khung giờ'] + array_combine(CAS_DELIVERY_SLOTS, CAS_DELIVERY_SLOTS), $slot) ?>
    <?= cas_select('status', ['' => 'Mọi trạng thái'] + CAS_DELIVERY_LABELS, $status) ?>
    <input class="admin-input" name="q" value="<?= cas_e($search) ?>" placeholder="Mã đơn, người nhận, SĐT">
    <button class="admin-btn-light" type="submit">Lọc lịch giao</button><a class="admin-btn-light" href="giao-hang.php">Bỏ lọc</a>
    <span class="admin-badge"><?= count($rows) ?> lịch</span>
  </form>
  <div class="admin-table-wrap"><table class="admin-table">
    <thead><tr><th>Đơn</th><th>Người nhận</th><th>Địa chỉ</th><th>Lịch giao</th><th>Trạng thái</th><th>Cập nhật</th></tr></thead><tbody>
    <?php foreach ($rows as $d): $next = CAS_DELIVERY_FLOW[$d['delivery_status']] ?? []; ?>
    <tr><td><strong><?= cas_e($d['order_code']) ?></strong><?php if ($d['order_status'] === 'cancelled'): ?><div><?= cas_status_badge('Đơn đã huỷ', 'danger') ?></div><?php endif; ?></td>
      <td><?= cas_e($d['recipient_name']) ?><div class="cas-muted"><?= cas_e($d['recipient_phone']) ?></div></td>
      <td><?= cas_e($d['delivery_address']) ?></td>
      <td><?= cas_e(date('d/m/Y', strtotime((string)$d['scheduled_at']))) ?><div class="cas-muted"><?= cas_e($d['receive_time']) ?></div></td>
      <td><?= cas_status_badge(CAS_DELIVERY_LABELS[$d['delivery_status']] ?? $d['delivery_status'], $d['delivery_status'] === 'delivered' ? 'success' : ($d['delivery_status'] === 'out_for_delivery' ? 'warning' : '')) ?>
        <?php if ($d['last_change']): ?><div class="cas-muted"><?= cas_e($d['last_change']) ?></div><?php endif; ?><?php if ($d['note']): ?><div class="cas-muted">Ghi chú: <?= cas_e($d['note']) ?></div><?php endif; ?></td>
      <td><?php if ($canUpdate && $next && $d['order_status'] !== 'cancelled'): ?>
        <form method="post" class="cas-admin-table-form"><?= cas_csrf_form() ?><input type="hidden" name="action" value="delivery_update"><input type="hidden" name="id" value="<?= (int)$d['delivery_id'] ?>">
          <?= cas_select('status', array_intersect_key(CAS_DELIVERY_LABELS, array_flip($next)), $next[0]) ?><input class="admin-input" name="note" maxlength="255" placeholder="Ghi chú"><button class="admin-btn-light" type="submit">Lưu</button></form>
      <?php else: ?><span class="cas-muted"><?= $next ? 'Chỉ xem' : 'Hoàn tất' ?></span><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6"><div class="admin-empty">Không có lịch giao phù hợp.</div></td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php
    cas_ops_end();
}
