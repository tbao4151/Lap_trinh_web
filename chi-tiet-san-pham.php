<?php
require_once __DIR__ . '/includes/bootstrap.php';
$slug = is_string($_GET['slug'] ?? null) ? $_GET['slug'] : '';
$found = cas_db()->prepare("SELECT product_id FROM products WHERE slug=? AND status='active'");
$found->execute([$slug]);
if (!$found->fetchColumn()) http_response_code(404);
cas_page_start("Chi tiết sản phẩm | CÁ'S HOA", 'detail', "Chi tiết mẫu hoa tại CÁ'S HOA.");
?>

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
<?php cas_page_end(); ?>
