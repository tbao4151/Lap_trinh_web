<?php
require_once __DIR__ . '/includes/customer.php';
$aiReady = trim((string)(getenv('OPENAI_API_KEY') ?: '')) !== '' && function_exists('curl_init');
$options = cas_catalog_options();
cas_page_start("Tư vấn chọn hoa | CÁ'S HOA", 'advisor', "Tư vấn chọn hoa theo dịp, tone màu và ngân sách tại CÁ'S HOA.");
?>
<main class="cas-page"><section class="cas-container" style="padding-top:44px;padding-bottom:65px">
<div class="cas-advisor-intro">
<p class="cas-eyebrow">Gợi ý dành riêng cho bạn</p>
<h1 class="cas-title">Bạn đang tìm một bó hoa?</h1>
<p class="cas-subtitle">Cho CÁ'S HOA biết dịp tặng, màu sắc và ngân sách. Hệ thống chỉ gợi ý những mẫu đang bán trên website.</p>
<div class="cas-advisor-panel mt-4">
<form id="advisorForm" novalidate>
<div class="row g-3">
<div class="col-md-4 cas-advisor-field"><label for="advisorOccasion">Dịp tặng</label><select id="advisorOccasion" name="occasion" class="form-select cas-form-select"><option value="">Không giới hạn</option>
<?php foreach($options['occasions'] as $o): ?><option value="<?= cas_e($o) ?>"><?= cas_e($o) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-4 cas-advisor-field"><label for="advisorTone">Tone màu</label><select id="advisorTone" name="tone" class="form-select cas-form-select"><option value="">Không giới hạn</option>
<?php foreach($options['tones'] as $t): ?><option value="<?= cas_e($t) ?>"><?= cas_e($t) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-4 cas-advisor-field"><label for="advisorBudget">Ngân sách tối đa (VNĐ)</label><input class="form-control cas-form-control" id="advisorBudget" name="budget" type="number" min="0" max="50000000" step="10000" placeholder="Ví dụ: 500000"></div>
<div class="col-12 cas-advisor-field"><label for="advisorMessage">Mô tả thêm mong muốn</label><textarea id="advisorMessage" name="message" class="form-control cas-form-control" maxlength="500" placeholder="Ví dụ: Tặng sinh nhật mẹ, muốn một bó thanh lịch, không quá rực rỡ."></textarea></div>
<div class="col-12"><button id="advisorSubmit" type="submit" class="btn-cas-primary w-100">Tìm mẫu hoa phù hợp</button></div>
</div></form>
<p class="cas-advisor-note mt-3 mb-0"><?= $aiReady?'Tư vấn AI đang được bật. Mô tả của bạn sẽ được gửi đến dịch vụ AI để tạo lời gợi ý.':'Gợi ý theo dữ liệu và bộ lọc đang bật. Cấu hình API trên máy chủ để sử dụng tư vấn AI.' ?> Không gửi tên, địa chỉ hoặc số điện thoại trong ô mô tả.</p>
<div id="advisorError" class="auth-message error mt-3" role="alert" hidden></div>
<div id="advisorResult" class="cas-advisor-result" hidden>
<div id="advisorMode" class="cas-advisor-status"></div>
<p id="advisorAnswer" class="mt-2" style="white-space:pre-line"></p>
<div id="advisorItems" class="row g-3"></div>
</div></div></div></section></main>
<?php cas_page_end(['assets/js/flower-advisor.js'], false); ?>
