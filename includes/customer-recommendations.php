<?php
require_once __DIR__.'/customer.php';
$recommendations=cas_catalog_options();
?>
<section class="customer-recommendations" aria-labelledby="recommendationTitle">
  <p class="cas-eyebrow">Một gợi ý dành riêng cho bạn</p>
  <h2 id="recommendationTitle">Chọn hoa theo dịp và ngân sách</h2>
  <p class="cas-muted">Chọn điều bạn muốn gửi gắm. Tiệm sẽ tìm những mẫu hoa phù hợp.</p>
  <form action="san-pham.php" method="get" data-budget-form>
    <label>Dịp tặng<select name="occasion" class="form-select cas-form-select"><option value="">Tất cả dịp</option><?php foreach($recommendations['occasions'] as $value): ?><option><?= cas_e($value) ?></option><?php endforeach; ?></select></label>
    <label>Tông màu<select name="tone" class="form-select cas-form-select"><option value="all">Tất cả màu</option><?php foreach($recommendations['tones'] as $value): ?><option><?= cas_e($value) ?></option><?php endforeach; ?></select></label>
    <label>Kiểu hoa<select name="type" class="form-select cas-form-select"><option value="all">Bó hoặc giỏ</option><option value="bouquet">Bó hoa</option><option value="basket">Giỏ hoa</option></select></label>
    <label>Ngân sách từ (đồng)<input class="form-control cas-form-control" type="number" name="min" min="0" step="1000" placeholder="Ví dụ: 200000" aria-describedby="recommendationError"></label>
    <label>Ngân sách đến (đồng)<input class="form-control cas-form-control" type="number" name="max" min="0" step="1000" placeholder="Ví dụ: 400000" aria-describedby="recommendationError"></label>
    <button class="btn-cas-primary" type="submit">Tìm hoa phù hợp <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
  </form>
  <p id="recommendationError" class="auth-message error" role="alert" hidden></p>
</section>
