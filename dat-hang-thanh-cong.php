<?php
require_once __DIR__ . '/includes/bootstrap.php';
$order = null;
$code = is_string($_GET['code'] ?? null) ? $_GET['code'] : '';
if (!empty($_SESSION['cas_last_order_id']) && hash_equals((string)($_SESSION['cas_last_order_code'] ?? ''), $code)) {
    $stmt = cas_db()->prepare("SELECT order_code,order_status,recipient_name,recipient_phone,receive_date,receive_time,total_amount FROM orders WHERE order_id=? LIMIT 1");
    $stmt->execute([(int)$_SESSION['cas_last_order_id']]);
    $order = $stmt->fetch() ?: null;
}
cas_page_start("Đặt hàng thành công | CÁ'S HOA", 'success', "Xác nhận đơn hàng tại CÁ'S HOA.");
?>
  <main class="cas-page success-page">
    <div class="cas-container">
      <section class="success-card">
        <div class="success-icon"><i class="bi bi-check2"></i></div>
        <p class="cas-eyebrow mt-3"><?= $order?'Đã tiếp nhận thông tin':'Thông tin đơn hàng' ?></p>
        <h1 class="cas-title" style="font-size:clamp(2.1rem,5vw,3.25rem)"><?= $order?'Đơn hoa đã được tạo.':'Chưa có xác nhận đơn.' ?></h1>
        <p class="cas-subtitle"><?= $order?'Shop đã tiếp nhận thông tin. Hãy lưu mã đơn để tra cứu.':'Hãy tra cứu bằng mã đơn và số điện thoại người nhận.' ?></p>
        <div class="success-code"><small>Mã đơn của bạn</small><strong id="successOrderCode"><?= cas_e($order["order_code"] ?? "Không có mã hợp lệ") ?></strong></div>
        <div id="successOrderDetails" class="success-details">
<?php if($order): ?>
  <div class="success-detail-row"><span>Trạng thái</span><strong><?= cas_e(cas_order_status_label($order['order_status'])) ?></strong></div>
  <div class="success-detail-row"><span>Người nhận</span><strong><?= cas_e($order['recipient_name']) ?></strong></div>
  <div class="success-detail-row"><span>Ngày nhận</span><strong><?= cas_e($order['receive_date']) ?> · <?= cas_e($order['receive_time']) ?></strong></div>
  <div class="success-detail-row"><span>Tổng tạm tính</span><strong><?= number_format((float)$order['total_amount'],0,',','.') ?> ₫</strong></div>
<?php else: ?>
  <p class="auth-message info">Chỉ có thể xem xác nhận của đơn vừa tạo trong phiên hiện tại. Bạn có thể tra cứu đơn bằng mã và số điện thoại.</p>
<?php endif; ?>
</div>
        <div class="d-flex flex-wrap gap-2 mt-4">
          <a id="successLookupLink" href="tra-cuu-don-hang.php<?= $order ? "?code=".urlencode($order["order_code"]) : "" ?>" class="btn-cas-primary"><i class="bi bi-search"></i> Tra cứu đơn</a>
          <a href="san-pham.php" class="btn-cas-outline">Tiếp tục chọn hoa</a>
          <a href="index.php" class="btn-cas-ghost">Về trang chủ</a>
        </div>
      </section>
    </div>
  </main>
<?php cas_page_end(); ?>
