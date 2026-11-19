<?php
require_once __DIR__.'/includes/bootstrap.php';
$slug=is_string($_GET['slug']??null)?$_GET['slug']:'';
$found=cas_db()->prepare("SELECT product_id FROM products WHERE slug=? AND status='active'");
$found->execute([$slug]);
if(!$found->fetchColumn()) http_response_code(404);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Chi tiết mẫu hoa tại CÁ'S HOA.">
  <title>Chi tiết sản phẩm | CÁ'S HOA</title>
  <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <link rel="stylesheet" href="assets/css/customer.css">
</head>
<body data-page="detail" class="d-flex flex-column">
  <div id="siteHeader"></div>

  <main class="cas-page detail-page">
    <div class="cas-container">
      <div id="productDetailHost"></div>

      <section class="related-section" aria-labelledby="related-title">
        <div class="d-flex justify-content-between align-items-end gap-3 mb-4">
          <div><p class="cas-eyebrow">Có thể bạn sẽ thích</p><h2 id="related-title" class="font-display mt-2 mb-0">Những mẫu khác.</h2></div>
          <a href="san-pham.php" class="btn-cas-ghost d-none d-sm-inline-flex">Xem tất cả <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-3 g-lg-4" id="relatedGrid"></div>
      </section>
    </div>
  </main>

  <div id="siteFooter"></div>

  <script src="assets/vendor/bootstrap/bootstrap.bundle.min.js" defer></script>
  <script src="assets/js/products.php" defer></script>
  <script src="assets/js/cart-r2.php" defer></script>
  <script src="assets/js/main-r2.js" defer></script>
<script>window.CAS_CSRF = <?= json_encode(cas_csrf()) ?>;</script>
  <script src="assets/js/customer-forms.js" defer></script>
</body>
</html>
