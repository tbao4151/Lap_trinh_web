<?php
require_once __DIR__.'/includes/auth.php';
$user=cas_require_user();
$q=cas_db()->prepare("SELECT o.order_code,o.created_at,o.recipient_name,o.recipient_phone,o.receive_date,o.total_amount,o.order_status FROM orders o JOIN customers c ON c.customer_id=o.customer_id WHERE c.user_id=? ORDER BY o.created_at DESC,o.order_id DESC LIMIT 100");
$q->execute([$user['user_id']]);$orders=$q->fetchAll();
function cas_order_status(string $status): string{
 return ['pending_confirmation'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','preparing'=>'Đang chuẩn bị','ready'=>'Sẵn sàng','delivering'=>'Đang giao','completed'=>'Hoàn tất','cancelled'=>'Đã huỷ'][$status]??$status;
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Xem các đơn hàng đã đặt.">
  <title>Lịch sử đơn hàng | CÁ'S HOA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body data-page="orders" class="d-flex flex-column">
  <div id="siteHeader"></div>

  <main class="cas-page content-page">
    <div class="cas-container">
      <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
          <p class="cas-eyebrow">Tài khoản khách hàng</p>
          <h1 class="cas-title">Lịch sử đơn hàng</h1>
          <p class="cas-subtitle">Danh sách các đơn đã tạo, kèm trạng thái và thông tin người nhận.</p>
        </div>
        <span class="stat-chip"><span id="orderCount"><?= count($orders) ?></span> đơn hàng</span>
      </div>

      <div class="row g-4">
        <div class="col-lg-9"><div id="orderHistory">
<?php if(!$orders): ?><p class="cas-muted">Chưa có đơn hàng được liên kết với tài khoản này.</p><?php endif; ?>
<?php foreach($orders as $o): ?>
<article class="order-history-card mb-3">
  <div class="d-flex justify-content-between flex-wrap gap-2"><strong><?= cas_e($o['order_code']) ?></strong><span class="stat-chip"><?= cas_e(cas_order_status($o['order_status'])) ?></span></div>
  <div class="cas-muted small mt-2"><?= cas_e($o['created_at']) ?> · <?= cas_e($o['recipient_name']) ?></div>
  <div class="fw-bold mt-2"><?= number_format((float)$o['total_amount'],0,',','.') ?> ₫</div>
  <a class="btn-cas-outline mt-3" href="tra-cuu-don-hang.php?code=<?= urlencode($o['order_code']) ?>">Tra cứu đơn</a>
</article>
<?php endforeach; ?>
</div></div>
        <div class="col-lg-3">
          <aside class="content-card">
            <h2 class="font-display fs-5">Cần hỗ trợ đơn hàng?</h2>
            <p class="cas-muted small lh-lg">Chuẩn bị mã đơn và số điện thoại người nhận để shop kiểm tra nhanh hơn.</p>
            <a class="btn-cas-outline w-100" href="tra-cuu-don-hang.php">Tra cứu đơn</a>
            <a class="btn-cas-primary w-100 mt-2" href="lien-he.php">Liên hệ shop</a>
          </aside>
        </div>
      </div>
    </div>
  </main>

  <div id="siteFooter"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <script src="assets/js/products.php" defer></script>
  <script src="assets/js/cart-r2.php" defer></script>
  
  
  <script src="assets/js/main-r2.js" defer></script>
<script>window.CAS_CSRF = <?= json_encode(cas_csrf()) ?>;</script>
</body>
</html>
