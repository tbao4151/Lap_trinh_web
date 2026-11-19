<?php
require_once __DIR__.'/includes/customer.php';
$aiReady=trim((string)(getenv('OPENAI_API_KEY')?:''))!==''&&function_exists('curl_init');
$options=cas_catalog_options();
?>
<!doctype html><html lang="vi"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="Tư vấn chọn hoa theo dịp, tone màu và ngân sách tại CÁ'S HOA.">
<title>Tư vấn chọn hoa | CÁ'S HOA</title>
<link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
<link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/responsive.css">
<link rel="stylesheet" href="assets/css/customer.css">
<style>
.cas-advisor-intro{max-width:770px;margin:25px auto 0}
.cas-advisor-panel{background:#fff;border:1px solid #e1e6de;border-radius:24px;padding:24px;box-shadow:0 10px 32px rgba(34,53,44,.06)}
.cas-advisor-field label{display:block;font-weight:600;font-size:.9rem;margin-bottom:7px}
.cas-advisor-field textarea{min-height:110px}
.cas-advisor-result{background:#f4f5ef;border:1px solid #dce4d8;border-radius:18px;padding:20px;margin-top:20px}
.cas-advisor-result[hidden]{display:none}
.cas-advisor-status{font-size:.85rem;color:#4e6649;font-weight:600}
.cas-advisor-card{display:flex;align-items:center;gap:13px;padding:12px;background:white;border:1px solid #e5e7df;border-radius:14px;text-decoration:none;color:inherit;height:100%}
.cas-advisor-card img{width:75px;height:75px;object-fit:cover;border-radius:11px}
.cas-advisor-card:hover{border-color:#4e6649;color:#254f30}
.cas-advisor-note{font-size:.88rem;color:#526157}
@media(max-width:575px){.cas-advisor-panel{padding:17px}.cas-advisor-card img{width:60px;height:60px}}
</style></head>
<body data-page="advisor" class="d-flex flex-column">
<div id="siteHeader"></div>
<main class="cas-page"><section class="cas-container" style="padding-top:44px;padding-bottom:65px">
<div class="cas-advisor-intro">
<p class="cas-eyebrow">Gợi ý dành riêng cho bạn</p>
<h1 class="cas-title">Bạn đang tìm một bó hoa?</h1>
<p class="cas-subtitle">Cho CÁ'S HOA biết dịp tặng, màu sắc và ngân sách. Hệ thống chỉ gợi ý những mẫu đang bán trên website.</p>
<div class="cas-advisor-panel mt-4">
<form id="advisorForm" novalidate>
<div class="row g-3">
<div class="col-md-6 cas-advisor-field"><label for="advisorOccasion">Dịp tặng</label><select id="advisorOccasion" name="occasion" class="form-select cas-form-select"><option value="">Không giới hạn</option>
<?php foreach($options['occasions'] as $o): ?><option value="<?= cas_e($o) ?>"><?= cas_e($o) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-6 cas-advisor-field"><label for="advisorTone">Tone màu</label><select id="advisorTone" name="tone" class="form-select cas-form-select"><option value="">Không giới hạn</option>
<?php foreach($options['tones'] as $t): ?><option value="<?= cas_e($t) ?>"><?= cas_e($t) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-6 cas-advisor-field"><label for="advisorBudget">Ngân sách tối đa (VNĐ)</label><input class="form-control cas-form-control" id="advisorBudget" name="budget" type="number" min="0" max="50000000" step="10000" placeholder="Ví dụ: 500000"></div>
<div class="col-12 cas-advisor-field"><label for="advisorMessage">Mô tả thêm mong muốn</label><textarea id="advisorMessage" name="message" class="form-control cas-form-control" maxlength="500" placeholder="Ví dụ: Tặng sinh nhật mẹ, muốn một bó thanh lịch, không quá rực rỡ."></textarea></div>
<div class="col-12"><button id="advisorSubmit" type="submit" class="btn-cas-primary w-100">Tìm mẫu hoa phù hợp</button></div>
</div></form>
<p class="cas-advisor-note mt-3 mb-0"><?= $aiReady?'Tư vấn AI đang được bật. Mô tả của bạn sẽ được gửi đến dịch vụ AI để tạo lời gợi ý.':'Gợi ý theo dữ liệu và bộ lọc đang bật. Cấu hình API trên máy chủ để sử dụng tư vấn AI.' ?> Không gửi tên, địa chỉ hoặc số điện thoại trong ô mô tả.</p>
<div id="advisorError" class="alert alert-danger mt-3" role="alert" hidden></div>
<div id="advisorResult" class="cas-advisor-result" hidden>
<div id="advisorMode" class="cas-advisor-status"></div>
<p id="advisorAnswer" class="mt-2" style="white-space:pre-line"></p>
<div id="advisorItems" class="row g-3"></div>
</div></div></div></section></main>
<div id="siteFooter"></div>
<script>window.CAS_CSRF=<?= json_encode(cas_csrf()) ?>;</script>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js" defer></script>
<script src="assets/js/products.php" defer></script>
<script src="assets/js/cart-r2.php" defer></script>
<script src="assets/js/main-r2.js" defer></script>
<script src="assets/js/flower-advisor.js" defer></script>
</body></html>
