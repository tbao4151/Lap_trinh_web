<?php require_once __DIR__.'/includes/storefront.php'; $discovery = cas_discovery_groups(); ?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="CÁ'S HOA - Hoa tươi cho những ngày đáng nhớ và cả những ngày bình thường.">
  <title>CÁ'S HOA — Hoa cho những điều khó nói</title>
  <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <link rel="stylesheet" href="assets/css/customer.css">
</head>
<body data-page="home" class="d-flex flex-column">
  <div id="siteHeader"></div>

  <main class="cas-page">
    <section class="hero-section" aria-labelledby="featured-title">
      <div class="cas-container">
        <p class="cas-eyebrow mb-3">Mẫu hoa nổi bật</p>
        <h1 id="featured-title" class="visually-hidden">Mẫu hoa nổi bật của CÁ'S HOA</h1>

        <div id="heroCarousel" class="carousel slide hero-slider" data-bs-ride="carousel" data-bs-interval="10000">
          <div class="carousel-inner" id="heroTrack"></div>
          <div class="d-flex align-items-center justify-content-center gap-3 mt-3">
            <button class="hero-control" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" aria-label="Mẫu trước"><i class="bi bi-chevron-left"></i></button>
            <span class="cas-muted small fw-bold">Mẫu hoa được chọn từ bộ sưu tập mới</span>
            <button class="hero-control" type="button" data-bs-target="#heroCarousel" data-bs-slide="next" aria-label="Mẫu tiếp theo"><i class="bi bi-chevron-right"></i></button>
          </div>
        </div>

        <div class="d-flex justify-content-center mt-4">
          <a href="san-pham.php?type=bouquet&today=1" class="btn-cas-primary hero-today-btn">Xem bó hoa hôm nay <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
    </section>

<?php if ($discovery !== []): ?>
    <section class="discovery-section" aria-labelledby="discovery-title">
      <div class="cas-container">
        <div class="row align-items-end g-3 mb-4">
          <div class="col-lg-8">
            <p class="cas-eyebrow">Khám phá</p>
            <h2 id="discovery-title" class="cas-title">Hoa cho mọi dịp.</h2>
            <p class="cas-subtitle mb-0">Chọn nhanh theo cảm xúc, dịp tặng và tông màu phù hợp với người nhận.</p>
          </div>
          <div class="col-lg-4 text-lg-end"><a href="san-pham.php" class="btn-cas-ghost">Xem tất cả <i class="bi bi-arrow-right"></i></a></div>
        </div>

<?php if ($discovery === null): ?>
        <div class="row g-3">
          <div class="col-6 col-lg-3"><a class="discovery-card d-block" href="san-pham.php?occasion=sinh-nhat"><img src="assets/images/garden.png" alt="Hoa sinh nhật"><span class="discovery-copy"><small>Theo dịp</small><strong>Sinh nhật</strong></span></a></div>
          <div class="col-6 col-lg-3"><a class="discovery-card d-block" href="san-pham.php?occasion=nguoi-thuong"><img src="assets/images/mot-bo-hoa.png" alt="Hoa tặng người thương"><span class="discovery-copy"><small>Theo cảm xúc</small><strong>Người thương</strong></span></a></div>
          <div class="col-6 col-lg-3"><a class="discovery-card d-block" href="san-pham.php?tone=xanh"><img src="assets/images/ly-xanh.png" alt="Hoa tone xanh"><span class="discovery-copy"><small>Theo tone màu</small><strong>Xanh dịu</strong></span></a></div>
          <div class="col-6 col-lg-3"><a class="discovery-card d-block" href="san-pham.php?occasion=ky-niem"><img src="assets/images/phi-yen.png" alt="Hoa kỷ niệm"><span class="discovery-copy"><small>Theo dịp</small><strong>Kỷ niệm</strong></span></a></div>
<?php else: ?>
        <div class="row g-3">
<?php foreach ($discovery as $group): ?>
          <div class="col-6 col-lg-3"><a class="discovery-card d-block" href="<?= cas_e($group['href']) ?>"><img src="<?= cas_e($group['image']) ?>" alt="<?= cas_e($group['title']) ?>"><span class="discovery-copy"><small><?= cas_e($group['subtitle']) ?></small><strong><?= cas_e($group['title']) ?></strong></span></a></div>
<?php endforeach; ?>
<?php endif; ?>
        </div>
      </div>
    </section>
<?php endif; ?>

    <section class="latest-section" aria-labelledby="latest-title">
      <div class="cas-container">
        <div class="d-flex align-items-end justify-content-between gap-3 mb-4">
          <div>
            <p class="cas-eyebrow">Đang được chọn</p>
            <h2 id="latest-title" class="cas-title">Bó hoa mới cập nhật.</h2>
          </div>
          <a href="san-pham.php" class="btn-cas-ghost d-none d-sm-inline-flex">Xem tất cả <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-3 g-lg-4" id="featuredGrid"></div>
        <div class="d-sm-none mt-4"><a href="san-pham.php" class="btn-cas-outline w-100">Xem tất cả sản phẩm</a></div>
      </div>
    </section>
  </main>

  <div class="cas-container"><?php require __DIR__.'/includes/customer-recommendations.php'; ?></div>
  <div id="siteFooter"></div>

  <script src="assets/vendor/bootstrap/bootstrap.bundle.min.js" defer></script>
  <script src="assets/js/products.php" defer></script>
  <script src="assets/js/cart-r2.php" defer></script>
  <script src="assets/js/main-r2.js" defer></script>
<script>window.CAS_CSRF = <?= json_encode(cas_csrf()) ?>;</script>
  <script src="assets/js/customer-forms.js" defer></script>
</body>
</html>
