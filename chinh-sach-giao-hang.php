<?php
require_once __DIR__ . '/includes/bootstrap.php';
cas_page_start("Chính sách giao hàng | CÁ'S HOA", 'policy', "Các thông tin dưới đây giúp khách hàng hiểu cách shop tiếp nhận, chuẩn bị và giao hoa.");
?>

  <main class="cas-page content-page">
    <div class="cas-container" style="max-width: 920px">
      <p class="cas-eyebrow">Thông tin mua hàng</p>
      <h1 class="cas-title">Chính sách giao hàng</h1>
      <p class="cas-subtitle">Các thông tin dưới đây giúp khách hàng hiểu cách shop tiếp nhận, chuẩn bị và giao hoa.</p>

      <div class="d-grid gap-3 mt-4">
        <section class="content-card">
          <div class="d-flex gap-3 align-items-start">
            <span class="stat-chip">01</span>
            <div>
              <h2 class="font-display fs-4">Khu vực và hình thức nhận hoa</h2>
              <p class="cas-muted lh-lg mb-0">Khách hàng có thể chọn giao tận nơi hoặc nhận trực tiếp tại cửa hàng. Phạm vi giao thực tế, thời gian di chuyển và phí giao sẽ được xác nhận trước khi hoàn tất đơn.</p>
            </div>
          </div>
        </section>
        <section class="content-card">
          <div class="d-flex gap-3 align-items-start">
            <span class="stat-chip">02</span>
            <div>
              <h2 class="font-display fs-4">Thời gian nhận hoa</h2>
              <p class="cas-muted lh-lg mb-0">Khách chọn ngày và khung giờ mong muốn khi đặt hàng. Với các ngày cao điểm, thời gian có thể được điều chỉnh sau khi shop liên hệ xác nhận.</p>
            </div>
          </div>
        </section>
        <section class="content-card">
          <div class="d-flex gap-3 align-items-start">
            <span class="stat-chip">03</span>
            <div>
              <h2 class="font-display fs-4">Thông tin người nhận</h2>
              <p class="cas-muted lh-lg mb-0">Tên, số điện thoại và địa chỉ cần được nhập chính xác. Trường hợp không liên hệ được người nhận, shop sẽ liên hệ người đặt để thống nhất cách xử lý.</p>
            </div>
          </div>
        </section>
        <section class="content-card">
          <div class="d-flex gap-3 align-items-start">
            <span class="stat-chip">04</span>
            <div>
              <h2 class="font-display fs-4">Phí giao hàng</h2>
              <p class="cas-muted lh-lg mb-0">Phí giao phụ thuộc khu vực, thời điểm và hình thức giao. Phí cuối cùng được thông báo trước khi đơn được xử lý.</p>
            </div>
          </div>
        </section>
        <section class="content-card">
          <div class="d-flex gap-3 align-items-start">
            <span class="stat-chip">05</span>
            <div>
              <h2 class="font-display fs-4">Thay đổi lịch giao</h2>
              <p class="cas-muted lh-lg mb-0">Khách có thể yêu cầu đổi thời gian hoặc địa chỉ nếu shop chưa bắt đầu giao. Một số thay đổi có thể phát sinh thêm phí vận chuyển.</p>
            </div>
          </div>
        </section>
        <section class="content-card">
          <div class="d-flex gap-3 align-items-start">
            <span class="stat-chip">06</span>
            <div>
              <h2 class="font-display fs-4">Giao hàng trong ngày</h2>
              <p class="cas-muted lh-lg mb-0">Các mẫu có trạng thái “Mẫu hôm nay” được ưu tiên cho đơn cần sớm. Khả năng nhận trong ngày vẫn phụ thuộc thời điểm đặt và khoảng cách giao.</p>
            </div>
          </div>
        </section>
      </div>

      <section class="content-card mt-4">
        <div class="row align-items-center g-3">
          <div class="col-md-8">
            <h2 class="font-display fs-4 mb-2">Cần shop giải thích thêm?</h2>
            <p class="cas-muted mb-0">Gửi mã đơn và nội dung cần hỗ trợ để shop kiểm tra nhanh hơn.</p>
          </div>
          <div class="col-md-4 text-md-end"><a class="btn-cas-primary" href="lien-he.php">Liên hệ hỗ trợ</a></div>
        </div>
      </section>
    </div>
  </main>
<?php cas_page_end(); ?>
