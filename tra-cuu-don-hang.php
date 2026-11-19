<?php
require_once __DIR__ . '/includes/bootstrap.php';
cas_page_start("Tra cứu đơn hàng | CÁ'S HOA", 'lookup', "Tra cứu trạng thái đơn hàng CÁ'S HOA bằng mã đơn và số điện thoại người nhận.");
?>
  <main class="cas-page lookup-page">
    <div class="cas-container">
      <section class="lookup-card mx-auto" style="max-width:860px">
        <div class="lookup-icon"><i class="bi bi-clipboard2-check"></i></div>
        <p class="cas-eyebrow mt-3">Theo dõi đơn hoa</p>
        <h1 class="cas-title" style="font-size:clamp(2rem,4vw,3rem)">Tra cứu đơn hàng.</h1>
        <p class="cas-subtitle">Nhập mã đơn và số điện thoại người nhận. Hai thông tin được dùng cùng nhau để tránh hiển thị nhầm dữ liệu đơn.</p>
        <form id="lookupForm" class="row g-3 align-items-end mt-2" novalidate>
          <div class="col-md-5"><label for="lookupCode" class="form-label small fw-semibold">Mã đơn <span class="form-required">*</span></label><input id="lookupCode" class="form-control cas-form-control text-uppercase" placeholder="CSH-2026-AB12CD34" autocomplete="off" required><div class="invalid-feedback">Vui lòng nhập mã đơn hàng.</div></div>
          <div class="col-md-4"><label for="lookupPhone" class="form-label small fw-semibold">Số điện thoại người nhận <span class="form-required">*</span></label><input id="lookupPhone" class="form-control cas-form-control" inputmode="numeric" maxlength="10" placeholder="0123456789" autocomplete="tel" required><div class="invalid-feedback">Vui lòng nhập số điện thoại người nhận.</div></div>
          <div class="col-md-3"><button class="btn-cas-primary w-100" type="submit"><i class="bi bi-search"></i> Tra cứu</button></div>
        </form>
        <div class="demo-note mt-4">Mã đơn được hiển thị sau khi đặt hoa. Hãy nhập đúng mã đơn và số điện thoại người nhận để xem trạng thái.</div>
        <div id="lookupMessage" class="auth-message d-none" role="status"></div>
      </section>
      <section id="lookupResults" class="mx-auto d-none" style="max-width:860px" aria-live="polite">
        <div class="mt-4"><p class="cas-eyebrow">Kết quả tra cứu</p><h2 class="font-display fs-3 mt-2">Thông tin đơn hàng</h2></div>
        <div id="lookupResultCard"></div>
      </section>
    </div>
  </main>
<?php cas_page_end(['assets/js/r2-lookup.js']); ?>