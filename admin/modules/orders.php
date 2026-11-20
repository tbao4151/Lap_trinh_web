<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && str_replace('\\', '/', strtolower((string)realpath($_SERVER['SCRIPT_FILENAME'] ?? ''))) === str_replace('\\', '/', strtolower((string)realpath(__FILE__)))) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Core Admin views: dashboard, orders (including order_update state machine & inventory sync), customers, reports.
 */
// Định nghĩa doanh thu thuần dùng chung với trang Lợi nhuận gộp (chỉ khai báo hàm, không render).
require_once __DIR__ . '/analytics.php';

function cas_admin_number(string $field): int
{
    $v = filter_var($_POST[$field] ?? null, FILTER_VALIDATE_INT);
    if ($v === false || $v < 1) {
        throw new RuntimeException('ID không hợp lệ.');
    }
    return $v;
}

function cas_admin_order_statuses(): array
{
    return cas_order_statuses();
}

function cas_admin_payment_statuses(): array
{
    return [
        'unpaid' => 'Chưa thu',
        'partial' => 'Đã cọc',
        'paid' => 'Đã thu',
        'partially_refunded' => 'Hoàn một phần',
        'refunded' => 'Đã hoàn',
    ];
}

function cas_order_transitions(): array
{
    return [
        'pending_confirmation' => ['confirmed', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready' => ['delivering', 'completed', 'cancelled'],
        'delivering' => ['completed', 'ready'],
        'completed' => [],
        'cancelled' => [],
    ];
}

function cas_order_choices(string $current, string $method): array
{
    $statuses = cas_admin_order_statuses();
    $choices = [$current => $statuses[$current] ?? $current];
    foreach (cas_order_transitions()[$current] ?? [] as $next) {
        // Orders with delivery method "delivery" use the delivery page to advance.
        if ($current === 'ready' && $method === 'delivery' && in_array($next, ['delivering', 'completed'], true)) {
            continue;
        }
        $choices[$next] = $statuses[$next] ?? $next;
    }
    return $choices;
}

function cas_admin_rows(PDO $db, string $sql, array $args = []): array
{
    $q = $db->prepare($sql);
    $q->execute($args);
    return $q->fetchAll();
}

function cas_admin_select(string $field, array $options, string $selected): string
{
    $s = '<select class="admin-select" name="' . cas_e($field) . '">';
    foreach ($options as $key => $label) {
        $s .= '<option value="' . cas_e((string)$key) . '"' . ((string)$key === $selected ? ' selected' : '') . '>' . cas_e((string)$label) . '</option>';
    }
    return $s . '</select>';
}

function cas_admin_handle_order_post(PDO $db, array $user, string $cas_page, string &$error): void
{
    if (!cas_post()) {
        return;
    }
    try {
        cas_assert_csrf();
        $act = (string)($_POST['action'] ?? '');
        if ($act === 'order_update' && $cas_page === 'orders') {
            $id = cas_admin_number('id');
            $status = (string)($_POST['status'] ?? '');
            $payment = (string)($_POST['payment_status'] ?? '');
            $reason = trim((string)($_POST['reason'] ?? ''));
            $allowedStatuses = cas_order_transitions();
            if (!array_key_exists($status, $allowedStatuses) || !in_array($payment, ['unpaid', 'partial', 'paid', 'partially_refunded', 'refunded'], true)) {
                throw new DomainException('Trạng thái không hợp lệ.');
            }
            if (mb_strlen($reason) > 255) {
                throw new DomainException('Lý do tối đa 255 ký tự.');
            }
            $db->beginTransaction();
            try {
                $q = $db->prepare('SELECT order_status,payment_status,delivery_method FROM orders WHERE order_id=? FOR UPDATE');
                $q->execute([$id]);
                $previous = $q->fetch();
                if (!$previous) {
                    throw new DomainException('Không tìm thấy đơn hàng.');
                }
                $from = $previous['order_status'];
                $oldPayment = $previous['payment_status'];
                if ($status !== $from && !in_array($status, $allowedStatuses[$from] ?? [], true)) {
                    throw new DomainException('Không được chuyển trạng thái đơn hàng từ ' . $from . ' sang ' . $status . '.');
                }
                if ($status === 'delivering') {
                    if ($previous['delivery_method'] !== 'delivery') {
                        throw new DomainException('Đơn nhận tại cửa hàng không có trạng thái đang giao.');
                    }
                    if ($status !== $from) {
                        throw new DomainException('Vui lòng chuyển trạng thái Đang giao từ màn hình Giao hàng để đồng bộ lịch và đơn.');
                    }
                }
                if ($status === 'completed' && $previous['delivery_method'] === 'delivery') {
                    $q = $db->prepare('SELECT delivery_status FROM deliveries WHERE order_id=? LIMIT 1');
                    $q->execute([$id]);
                    if ($q->fetchColumn() !== 'delivered') {
                        throw new DomainException('Đơn giao hàng cần được xác nhận đã giao trước khi hoàn tất.');
                    }
                }
                if ($status !== $from && in_array($status, ['cancelled', 'ready'], true) && $from === 'delivering' && mb_strlen($reason) < 3) {
                    throw new DomainException('Quay lại trạng thái trước cần ghi lý do tối thiểu 3 ký tự.');
                }
                if ($status === 'cancelled' && in_array($from, ['preparing', 'ready'], true) && mb_strlen($reason) < 3) {
                    throw new DomainException('Hủy đơn đang chuẩn bị cần ghi lý do tối thiểu 3 ký tự.');
                }
                if ($payment !== $oldPayment) {
                    if (!in_array($user['role'], ['admin', 'owner'], true)) {
                        throw new DomainException('Chỉ quản trị viên được cập nhật thanh toán.');
                    }
                    $paymentFlow = ['unpaid' => ['partial', 'paid'], 'partial' => ['paid'], 'paid' => [], 'partially_refunded' => [], 'refunded' => []];
                    if (!in_array($payment, $paymentFlow[$oldPayment] ?? [], true)) {
                        throw new DomainException('Trạng thái thanh toán không hợp lệ; hoàn tiền phải xử lý qua chức năng Hoàn tiền.');
                    }
                }
                if ($status !== $from && $status === 'preparing') {
                    cas_order_consume_inventory($db, $id, (int)$user['user_id']);
                }
                if ($status !== $from && $status === 'cancelled') {
                    cas_order_restore_inventory($db, $id, (int)$user['user_id']);
                }
                if ($from === 'delivering' && $status === 'ready') {
                    // Quay lại "Sẵn sàng" = chuyến giao không thành công: đưa lịch giao về "Chưa giao" trong cùng transaction,
                    // nếu không đơn bị kẹt (Giao hàng chỉ cho xuất giao từ "Chưa giao" và xác nhận đã giao khi đơn "Đang giao").
                    $q = $db->prepare("SELECT delivery_id FROM deliveries WHERE order_id=? AND delivery_status='out_for_delivery' FOR UPDATE");
                    $q->execute([$id]);
                    foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $deliveryId) {
                        $db->prepare("UPDATE deliveries SET delivery_status='not_delivered', note=? WHERE delivery_id=?")->execute([$reason, $deliveryId]);
                        $db->prepare("INSERT INTO delivery_events(delivery_id,from_status,to_status,actor_id,note) VALUES(?,'out_for_delivery','not_delivered',?,?)")
                           ->execute([$deliveryId, $user['user_id'], 'Trang Đơn hàng: ' . $reason]);
                    }
                }
                $q = $db->prepare('UPDATE orders SET order_status=?,payment_status=? WHERE order_id=?');
                $q->execute([$status, $payment, $id]);
                $db->commit();
                if ($status !== $from || $payment !== $oldPayment) {
                    try {
                        $audit = $db->prepare('INSERT INTO admin_audit_log(user_id,action,entity_type,entity_id,outcome,detail,ip) VALUES(?,?,?,?,?,?,?)');
                        $audit->execute([
                            $user['user_id'],
                            'order_update',
                            'order',
                            (string)$id,
                            'ok',
                            json_encode(['from' => $from, 'to' => $status, 'payment_from' => $oldPayment, 'payment_to' => $payment, 'reason' => $reason], JSON_UNESCAPED_UNICODE),
                            substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                        ]);
                    } catch (Throwable $logError) {
                        error_log('CAS order audit: ' . $logError->getMessage());
                    }
                }
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $e;
            }
        } else {
            throw new RuntimeException('Hành động không được hỗ trợ.');
        }
        cas_redirect(basename($_SERVER['SCRIPT_NAME']) . '?saved=1');
    } catch (Throwable $e) {
        if ($e instanceof DomainException) {
            http_response_code(422);
        }
        $error = $e instanceof PDOException ? 'Dữ liệu trùng, đang được sử dụng hoặc lỗi kết nối MySQL.' : $e->getMessage();
    }
}

function cas_admin_core_meta(string $cas_page): array
{
    $titles = [
        'dashboard' => 'Tổng quan vận hành',
        'orders'    => 'Quản lý đơn hàng',
        'customers' => 'Khách hàng',
        'reports'   => 'Báo cáo doanh thu',
    ];
    $icons = [
        'dashboard' => 'bi-grid',
        'orders'    => 'bi-clipboard2-check',
        'customers' => 'bi-people',
        'reports'   => 'bi-bar-chart',
    ];
    return [$titles[$cas_page] ?? "CÁ'S HOA Admin", $icons[$cas_page] ?? 'bi-flower2'];
}

/** Vai trò được xem từng trang lõi; trang không có trong danh sách mở cho mọi vai trò nội bộ. */
function cas_admin_core_page_roles(string $cas_page): array
{
    $roles = [
        // Doanh thu là số liệu tài chính: chỉ admin/owner, giống Biểu đồ lợi nhuận (đề xuất C7 trong docs/admin-ops-contracts.md).
        'reports' => ['admin', 'owner'],
    ];
    return $roles[$cas_page] ?? ['staff', 'admin', 'owner'];
}

/** Chặn trang lõi khi vai trò không đủ quyền: ghi audit "denied", trả 403 và dừng. */
function cas_admin_guard_core_page(PDO $db, array $user, string $cas_page): void
{
    if (in_array($user['role'] ?? '', cas_admin_core_page_roles($cas_page), true)) {
        return;
    }
    try {
        $db->prepare('INSERT INTO admin_audit_log(user_id,action,entity_type,entity_id,outcome,detail,ip) VALUES(?,?,?,?,?,?,?)')
           ->execute([$user['user_id'] ?? null, 'page_view', 'permission', basename($_SERVER['SCRIPT_NAME'] ?? ''), 'denied',
               json_encode(['role' => $user['role'] ?? ''], JSON_UNESCAPED_UNICODE), substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45)]);
    } catch (Throwable $e) {
        error_log('CAS audit: ' . $e->getMessage());
    }
    http_response_code(403);
    cas_admin_layout_start($user, 'Không có quyền truy cập', 'Bạn không có quyền truy cập khu vực này.', 'bi-shield-lock', $cas_page);
    echo '<section class="admin-card admin-section"><p class="mb-0">Tài khoản của bạn không được phép xem trang này. Liên hệ quản trị viên nếu bạn cần được cấp quyền.</p></section>';
    cas_admin_layout_end();
    exit;
}

function cas_admin_render_core_page(PDO $db, array $user, string $cas_page): void
{
    $statuses = cas_admin_order_statuses();
    $payments = cas_admin_payment_statuses();
    $statusTones = ['pending_confirmation' => 'warning', 'confirmed' => 'success', 'preparing' => 'warning', 'ready' => 'warning', 'delivering' => 'warning', 'completed' => 'success', 'cancelled' => 'danger'];

    switch ($cas_page) {
        case 'dashboard':
            $kpis = [
                'Đơn mới 24 giờ' => (int)$db->query('SELECT COUNT(*) FROM orders WHERE created_at >= NOW()-INTERVAL 1 DAY')->fetchColumn(),
                'Chờ xác nhận' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status='pending_confirmation'")->fetchColumn(),
                'Đang chuẩn bị' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('confirmed','preparing','ready')")->fetchColumn(),
                'Giao hôm nay' => (int)$db->query('SELECT COUNT(*) FROM deliveries WHERE DATE(scheduled_at)=CURDATE()')->fetchColumn(),
                // Đơn đã thu tiền: tính cả đơn sau đó hoàn một phần/toàn bộ (cùng tập trạng thái với analytics.php),
                // nếu chỉ đếm 'paid' thì một đơn vừa ghi nhận hoàn tiền sẽ biến mất khỏi ô này.
                'Đơn đã thanh toán' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE payment_status IN ('paid','partially_refunded','refunded')")->fetchColumn(),
                'Nguyên liệu sắp hết' => (int)$db->query("SELECT COUNT(*) FROM inventory_items WHERE quantity<=min_quantity AND status='active'")->fetchColumn(),
                // Trước đây: SUM(total_amount) chỉ với payment_status='paid' => bỏ sót phần còn lại của đơn hoàn một phần,
                // lệch với Báo cáo doanh thu và Lợi nhuận gộp. Nay dùng chung định nghĩa doanh thu thuần (toàn thời gian).
                CAS_REVENUE_NET_LABEL => (int)round(cas_revenue_net_total($db)),
            ];
            if (!in_array($user['role'] ?? '', cas_admin_core_page_roles('reports'), true)) {
                // Nhân viên không xem số liệu doanh thu (cùng quyền với trang Báo cáo doanh thu).
                unset($kpis[CAS_REVENUE_NET_LABEL]);
            }
            $latest = cas_admin_rows($db, "SELECT o.order_code,o.total_amount,o.order_status,o.created_at,c.full_name FROM orders o JOIN customers c ON c.customer_id=o.customer_id ORDER BY o.order_id DESC LIMIT 10");
            ?>
<div class="cas-admin-metrics admin-section">
<?php foreach ($kpis as $label => $count): ?><article class="cas-admin-metric"><span><?= cas_e($label) ?></span><strong><?= number_format($count, 0, ',', '.') ?><?= $label === CAS_REVENUE_NET_LABEL ? ' ₫' : '' ?></strong></article><?php endforeach; ?>
</div>
<section class="admin-card admin-section">
<div class="cas-admin-stack justify-content-between mb-2"><h2 class="admin-card-title mb-0">Đơn gần đây</h2><a class="admin-btn-light" href="don-hang.php">Quản lý đơn hàng</a></div>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Giá trị</th><th>Trạng thái</th></tr></thead><tbody>
<?php foreach ($latest as $o): $tone = $statusTones[$o['order_status']] ?? ''; ?><tr><td><strong><?= cas_e($o['order_code']) ?></strong><div class="cas-muted"><?= cas_e($o['created_at']) ?></div></td><td><?= cas_e($o['full_name']) ?></td><td class="admin-money"><?= cas_money($o['total_amount']) ?></td><td><span class="admin-status<?= $tone ? ' ' . $tone : '' ?>"><?= cas_e($statuses[$o['order_status']] ?? $o['order_status']) ?></span></td></tr><?php endforeach; ?>
<?php if (!$latest): ?><tr><td colspan="4"><div class="admin-empty">Chưa có đơn hàng nào.</div></td></tr><?php endif; ?>
</tbody></table></div></section>
<?php
            break;

        case 'orders':
            $filter = (string)($_GET['status'] ?? 'all');
            $args = [];
            $sql = "SELECT o.*,c.full_name AS customer_name,c.phone AS customer_phone FROM orders o JOIN customers c ON c.customer_id=o.customer_id";
            if (isset($statuses[$filter])) {
                $sql .= " WHERE o.order_status=?";
                $args[] = $filter;
            }
            $sql .= " ORDER BY o.created_at DESC,o.order_id DESC LIMIT 200";
            $rows = cas_admin_rows($db, $sql, $args);
            ?>
<section class="admin-card admin-section">
<form method="get" class="cas-admin-stack mb-3"><label class="admin-label mb-0" for="orderStatusSelect">Trạng thái:</label><select id="orderStatusSelect" class="admin-select" name="status"><?php foreach (['all' => 'Tất cả'] + $statuses as $k => $lbl): ?><option value="<?= cas_e((string)$k) ?>"<?= (string)$k === $filter ? ' selected' : '' ?>><?= cas_e((string)$lbl) ?></option><?php endforeach; ?></select><button class="admin-btn-light" type="submit">Lọc đơn</button><span class="admin-badge"><?= count($rows) ?> đơn</span></form>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Mã đơn / khách</th><th>Người nhận</th><th>Giá trị</th><th>Cập nhật</th></tr></thead><tbody>
<?php foreach ($rows as $o): $tone = $statusTones[$o['order_status']] ?? ''; ?><tr>
<td><strong><?= cas_e($o['order_code']) ?></strong> <span class="admin-status<?= $tone ? ' ' . $tone : '' ?>"><?= cas_e($statuses[$o['order_status']] ?? $o['order_status']) ?></span><div class="mt-1"><?= cas_e($o['customer_name']) ?> — <?= cas_e($o['customer_phone']) ?></div><div class="cas-muted"><?= cas_e($o['created_at']) ?></div></td>
<td><?= cas_e($o['recipient_name']) ?><div class="cas-muted"><?= cas_e($o['receive_date']) ?> · <?= cas_e($o['receive_time']) ?></div></td>
<td class="admin-money"><?= cas_money($o['total_amount']) ?></td>
<td><form method="post" class="cas-admin-table-form"><?= cas_csrf_form() ?><input type="hidden" name="action" value="order_update"><input type="hidden" name="id" value="<?= (int)$o['order_id'] ?>">
<?= str_replace('<select class="admin-select"', '<select class="admin-select" aria-label="Trạng thái đơn ' . cas_e($o['order_code']) . '"', cas_admin_select('status', cas_order_choices($o['order_status'], $o['delivery_method']), $o['order_status'])) ?>
<?php if (in_array($user['role'], ['admin', 'owner'], true)): ?>
<?= str_replace('<select class="admin-select"', '<select class="admin-select" aria-label="Thanh toán đơn ' . cas_e($o['order_code']) . '"', cas_admin_select('payment_status', $payments, $o['payment_status'])) ?>
<?php else: ?><input type="hidden" name="payment_status" value="<?= cas_e($o['payment_status']) ?>"><span class="admin-badge"><?= cas_e($payments[$o['payment_status']] ?? $o['payment_status']) ?></span><?php endif; ?>
<input class="admin-input" name="reason" maxlength="255" aria-label="Lý do thay đổi trạng thái" placeholder="Lý do nếu hủy/hoàn tác">
<button class="admin-btn-light" type="submit">Lưu</button></form></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="4"><div class="admin-empty">Không có đơn hàng phù hợp bộ lọc.</div></td></tr><?php endif; ?>
</tbody></table></div></section>
<?php
            break;

        case 'customers':
            $search = trim((string)($_GET['q'] ?? ''));
            $args = [];
            $sql = "SELECT c.customer_id,c.full_name,c.phone,c.email,c.address,COUNT(o.order_id) AS orders_count,COALESCE(SUM(o.total_amount),0) AS amount FROM customers c LEFT JOIN orders o ON o.customer_id=c.customer_id";
            if ($search !== '') {
                $sql .= " WHERE c.full_name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?";
                $args = array_fill(0, 3, '%' . $search . '%');
            }
            $sql .= " GROUP BY c.customer_id ORDER BY c.customer_id DESC LIMIT 200";
            $rows = cas_admin_rows($db, $sql, $args);
            ?>
<section class="admin-card admin-section"><form method="get" class="cas-admin-stack mb-3"><input name="q" class="admin-input" aria-label="Tìm khách hàng theo tên, SĐT hoặc email" value="<?= cas_e($search) ?>" placeholder="Tên, SĐT, email"><button class="admin-btn-light" type="submit">Tìm</button><span class="admin-badge"><?= count($rows) ?> khách</span></form>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Khách hàng</th><th>SĐT / email</th><th>Địa chỉ</th><th>Số đơn</th><th>Tổng đơn</th></tr></thead><tbody>
<?php foreach ($rows as $c): ?><tr><td><strong><?= cas_e($c['full_name']) ?></strong></td><td><?= cas_e($c['phone']) ?><div class="cas-muted"><?= cas_e($c['email']) ?></div></td><td><?= cas_e($c['address'] ?: '—') ?></td><td><?= (int)$c['orders_count'] ?></td><td><strong><?= cas_money($c['amount']) ?></strong></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5"><div class="admin-empty">Không tìm thấy khách hàng phù hợp.</div></td></tr><?php endif; ?>
</tbody></table></div></section>
<?php
            break;

        case 'reports':
            $start = (string)($_GET['start'] ?? date('Y-m-01'));
            $end = (string)($_GET['end'] ?? date('Y-m-d'));
            if (!preg_match('/^\d{4}-\d\d-\d\d$/', $start) || !preg_match('/^\d{4}-\d\d-\d\d$/', $end) || $end < $start) {
                $start = date('Y-m-01');
                $end = date('Y-m-d');
            }
            $q = $db->prepare("SELECT DATE(o.created_at) day,COUNT(*) order_count, SUM(o.total_amount) gross_sales,
    SUM(CASE WHEN " . cas_revenue_eligible_sql() . " THEN " . cas_revenue_net_sql() . " ELSE 0 END) confirmed_revenue
    FROM orders o " . cas_revenue_refund_join() . "
    WHERE DATE(o.created_at) BETWEEN ? AND ? GROUP BY DATE(o.created_at) ORDER BY day DESC");
            $q->execute([$start, $end]);
            $rows = $q->fetchAll();
            $totals = ['orders' => 0, 'gross' => 0, 'revenue' => 0];
            foreach ($rows as $r) {
                $totals['orders'] += (int)$r['order_count'];
                $totals['gross'] += (float)$r['gross_sales'];
                $totals['revenue'] += (float)$r['confirmed_revenue'];
            }
            ?>
<section class="admin-card admin-section">
<div class="cas-admin-stack justify-content-between mb-3">
  <form method="get" class="cas-admin-stack"><label class="admin-label mb-0" for="reportStart">Từ</label><input id="reportStart" name="start" type="date" class="admin-input" value="<?= cas_e($start) ?>"><label class="admin-label mb-0" for="reportEnd">đến</label><input id="reportEnd" name="end" type="date" class="admin-input" value="<?= cas_e($end) ?>"><button class="admin-btn-light" type="submit">Xem báo cáo</button></form>
  <?php if (in_array($user['role'], ['admin', 'owner'], true)): ?><a class="admin-btn-light" href="loi-nhuan.php">Biểu đồ lợi nhuận gộp <i class="bi bi-arrow-right" aria-hidden="true"></i></a><?php endif; ?>
</div>
<div class="cas-admin-metrics mb-4"><article class="cas-admin-metric"><span>Tổng đơn</span><strong><?= $totals['orders'] ?></strong></article><article class="cas-admin-metric"><span>Giá trị đơn</span><strong><?= cas_money($totals['gross']) ?></strong></article><article class="cas-admin-metric"><span><?= cas_e(CAS_REVENUE_NET_LABEL) ?></span><strong><?= cas_money($totals['revenue']) ?></strong></article></div>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Ngày</th><th>Số đơn</th><th>Giá trị tạo đơn</th><th><?= cas_e(CAS_REVENUE_NET_LABEL) ?></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= cas_e($r['day']) ?></td><td><?= (int)$r['order_count'] ?></td><td><?= cas_money($r['gross_sales']) ?></td><td><strong><?= cas_money($r['confirmed_revenue']) ?></strong></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="4"><div class="admin-empty">Chưa có đơn hàng trong khoảng thời gian đã chọn.</div></td></tr><?php endif; ?>
</tbody></table></div></section>
<?php
            break;

        default:
            http_response_code(404);
            echo '<section class="admin-card admin-section"><p class="mb-0">Không tìm thấy màn hình quản trị được yêu cầu.</p></section>';
            break;
    }
}
