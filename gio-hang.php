<?php
require_once __DIR__ . '/includes/bootstrap.php';
cas_page_start("Giỏ hàng | CÁ'S HOA", 'cart', "Giỏ hàng CÁ'S HOA.");
?>

  <main class="cas-page cart-page">
    <div class="cas-container">
      <p class="cas-eyebrow">Đơn hoa của bạn</p>
      <h1 class="cas-title">Giỏ hàng.</h1>
      <p class="cas-subtitle">Bạn có thể thay đổi số lượng hoặc xoá sản phẩm trước khi chuyển sang bước thanh toán.</p>

      <div class="row g-4 mt-2">
        <div class="col-lg-8">
          <div id="cartLines"></div>
          <div id="emptyCart" class="empty-cart d-none"><i class="bi bi-bag"></i><h2 class="font-display fs-3">Giỏ hàng đang trống.</h2><p class="cas-muted">Chọn một mẫu hoa bạn thích để bắt đầu.</p><a class="btn-cas-primary mt-2" href="san-pham.php">Xem sản phẩm</a></div>
        </div>

        <div class="col-lg-4">
          <aside id="cartSummary" class="summary-card d-none">
            <p class="cas-eyebrow">Tóm tắt</p>
            <h2 class="font-display fs-4 mt-2 mb-3">Đơn hàng</h2>
            <div class="summary-row"><span>Tạm tính</span><strong data-cart-subtotal>0 ₫</strong></div>
            <div class="summary-row"><span>Phí giao hàng</span><span>Tính ở bước sau</span></div>
            <div class="summary-row total"><span>Tổng tạm tính</span><span data-cart-total>0 ₫</span></div>
            <a href="thanh-toan.php" class="btn-cas-primary w-100 mt-3">Tiếp tục thanh toán <i class="bi bi-arrow-right"></i></a>
            <a href="san-pham.php" class="btn-cas-outline w-100 mt-2">Tiếp tục chọn hoa</a>
            <p class="demo-note mt-3 mb-0">Giỏ hàng được kiểm tra lại từ MySQL lúc đặt hàng.</p>
          </aside>
        </div>
      </div>
    </div>
  </main>
<?php cas_page_end(); ?>
