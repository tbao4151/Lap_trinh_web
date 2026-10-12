<?php
require_once __DIR__.'/includes/bootstrap.php';
$slug=(string)($_GET['slug']??'');$article=null;
if(preg_match('/^[a-z0-9-]{1,220}$/',$slug)){
 $stmt=cas_db()->prepare("SELECT title,thumbnail,content,published_at FROM news WHERE slug=? AND status='published' LIMIT 1");
 $stmt->execute([$slug]);$article=$stmt->fetch() ?: null;
}
if(!$article) http_response_code(404);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Bài viết về hoa và quà tặng.">
  <title>Chi tiết tin tức | CÁ'S HOA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body data-page="article" class="d-flex flex-column">
  <div id="siteHeader"></div>

  <main class="cas-page content-page">
    <div class="cas-container">
      <article id="articleHost" class="mx-auto" style="max-width: 900px">
<?php if($article): ?>
  <div class="article-meta"><?= cas_e(date('d.m.Y',strtotime($article['published_at']))) ?> · Tin tức</div>
  <h1 class="cas-title mt-3"><?= cas_e($article['title']) ?></h1>
  <img class="img-fluid rounded mt-4" src="<?= cas_e($article['thumbnail']) ?>" alt="<?= cas_e($article['title']) ?>">
  <div class="content-card mt-4"><p style="white-space:pre-line"><?= cas_e($article['content']) ?></p></div>
<?php else: ?>
  <h1 class="cas-title">Không tìm thấy bài viết.</h1>
  <a href="tin-tuc.php">Trở lại tin tức</a>
<?php endif; ?>
</article>
    </div>
  </main>

  <div id="siteFooter"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <script src="assets/js/products.php" defer></script>
  <script src="assets/js/cart.js" defer></script>
  <script src="assets/js/batch2.js" defer></script>
  <script src="assets/js/batch3.js" defer></script>
  
  <script src="assets/js/main-r2.js" defer></script>
</body>
</html>
