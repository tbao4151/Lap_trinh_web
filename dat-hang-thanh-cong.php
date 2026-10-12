<?php
require_once __DIR__.'/includes/bootstrap.php';
$order=null;$code=(string)($_GET['code']??'');
if(!empty($_SESSION['cas_last_order_id']) && hash_equals((string)($_SESSION['cas_last_order_code']??''),$code)){
  $stmt=cas_db()->prepare("SELECT order_code,order_status,recipient_name,recipient_phone,receive_date,receive_time,total_amount FROM orders WHERE order_id=? LIMIT 1");
  $stmt->execute([(int)$_SESSION['cas_last_order_id']]);
  $order=$stmt->fetch() ?: null;
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Xác nhận đơn hàng mô phỏng tại CÁ'S HOA.">
  <title>Đặt hàng thành công | CÁ'S HOA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body data-page="success" class="d-flex flex-column">
  <div id="siteHeader"></div>
  <main class="cas-page success-page">
    <div class="cas-container">
      <section class="success-card">
        <div class="success-icon"><i class="bi bi-check2"></i></div>
        <p class="cas-eyebrow mt-3">Đã tiếp nhận thông tin</p>
        <h1 class="cas-title" style="font-size:clamp(2.1rem,5vw,3.25rem)">Đơn hoa đã được tạo.</h1>
        <p class="cas-subtitle">Shop đã tiếp nhận thông tin đơn hàng. Bạn có thể lưu lại mã đơn để tra cứu trạng thái khi cần.</p>
        <div class="success-code"><small>Mã đơn của bạn</small><strong id="successOrderCode"><?= cas_e($order["order_code"] ?? "Không có mã hợp lệ") ?></strong></div>
        <div id="successOrderDetails" class="success-details">
<?php if($order): ?>
  <div class="success-detail-row"><span>Trạng thái</span><strong>Chờ xác nhận</strong></div>
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
  <div id="siteFooter"></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <script src="assets/js/products.php" defer></script>
  <script src="assets/js/cart.js" defer></script>
  
  <script src="assets/js/main-r2.js" defer></script>
</body>
</html>