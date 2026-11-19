<?php
require_once __DIR__ . '/includes/bootstrap.php';
$articles = cas_db()->query("SELECT slug,title,summary,thumbnail,published_at FROM news WHERE status='published' ORDER BY published_at DESC")->fetchAll();
cas_page_start("Tin tức | CÁ'S HOA", 'news', "Tin tức, mẹo chăm hoa và gợi ý quà tặng.");
?>

  <main class="cas-page content-page">
    <div class="cas-container">
      <p class="cas-eyebrow">Góc nhỏ của tiệm</p>
      <h1 class="cas-title">Tin tức & câu chuyện hoa</h1>
      <p class="cas-subtitle">Gợi ý chọn hoa, viết thiệp, chăm hoa và chuẩn bị món quà phù hợp với từng dịp.</p>

      <div class="row g-4 mt-2">
  <?php if(!$articles): ?>
  <div class="col-12"><div class="customer-empty"><i class="bi bi-journal-text"></i><h2 class="font-display fs-4">Chưa có bài viết mới</h2><p class="cas-muted mb-3">Shop sẽ sớm cập nhật những câu chuyện và gợi ý chăm hoa mới.</p><a class="btn-cas-outline" href="san-pham.php">Xem các mẫu hoa</a></div></div>
  <?php endif; ?>
  <?php foreach($articles as $article): $href='chi-tiet-tin-tuc.php?slug='.urlencode($article['slug']); ?>
  <div class="col-md-6 col-lg-4">
    <article class="news-card">
      <a href="<?= cas_e($href) ?>"><img src="<?= cas_e($article['thumbnail']) ?>" alt="<?= cas_e($article['title']) ?>" loading="lazy"></a>
      <div class="news-card-body">
        <div class="article-meta"><?= cas_e(date('d.m.Y',strtotime($article['published_at']))) ?> · Tin tức</div>
        <h2 class="mt-2"><a href="<?= cas_e($href) ?>"><?= cas_e($article['title']) ?></a></h2>
        <p><?= cas_e($article['summary']) ?></p>
        <a class="news-card-link" href="<?= cas_e($href) ?>">Đọc bài viết <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
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
<?php cas_page_end(); ?>
