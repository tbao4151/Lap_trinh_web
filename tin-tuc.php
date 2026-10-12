<?php
require_once __DIR__.'/includes/bootstrap.php';
$articles=cas_db()->query("SELECT slug,title,summary,thumbnail,published_at FROM news WHERE status='published' ORDER BY published_at DESC")->fetchAll();
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Tin tức, mẹo chăm hoa và gợi ý quà tặng.">
  <title>Tin tức | CÁ'S HOA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body data-page="news" class="d-flex flex-column">
  <div id="siteHeader"></div>

  <main class="cas-page content-page">
    <div class="cas-container">
      <p class="cas-eyebrow">Góc nhỏ của tiệm</p>
      <h1 class="cas-title">Tin tức & câu chuyện hoa</h1>
      <p class="cas-subtitle">Gợi ý chọn hoa, viết thiệp, chăm hoa và chuẩn bị món quà phù hợp với từng dịp.</p>

      <div class="row g-4 mt-2">
  <?php foreach($articles as $article): ?>
  <div class="col-md-6 col-lg-4">
    <article class="news-card">
      <img src="<?= cas_e($article['thumbnail']) ?>" alt="<?= cas_e($article['title']) ?>">
      <div class="news-card-body">
        <div class="article-meta"><?= cas_e(date('d.m.Y',strtotime($article['published_at']))) ?> · Tin tức</div>
        <h2 class="mt-2"><?= cas_e($article['title']) ?></h2>
        <p><?= cas_e($article['summary']) ?></p>
        <a class="fw-bold small" style="color:var(--primary)"
          href="chi-tiet-tin-tuc.php?slug=<?= urlencode($article['slug']) ?>">Đọc bài viết</a>
      </div>
    </article>
  </div>
  <?php endforeach; ?>
</div>
<section class="content-card mt-5">
        <div class="row align-items-center g-3">
          <div class="col-lg-8">
            <p class="cas-eyebrow">Cần một gợi ý riêng?</p>
            <h2 class="font-display fs-3 mt-2">Chưa biết nên chọn mẫu nào?</h2>
            <p class="cas-muted mb-0">Gửi cho shop dịp tặng, màu người nhận thích và khoảng ngân sách dự kiến.</p>
          </div>
          <div class="col-lg-4 text-lg-end"><a class="btn-cas-primary" href="lien-he.php">Nhờ shop tư vấn</a></div>
        </div>
      </section>
    </div>
  </main>

  <div id="siteFooter"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <script src="assets/js/products.php" defer></script>
  <script src="assets/js/cart-r2.php" defer></script>
  <script src="assets/js/batch2.js" defer></script>
  <script src="assets/js/batch3.js" defer></script>
  <script src="assets/js/main-r2.js" defer></script>
<script>window.CAS_CSRF = <?= json_encode(cas_csrf()) ?>;</script>
</body>
</html>
