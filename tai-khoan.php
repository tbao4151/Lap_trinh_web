<?php
require_once __DIR__.'/includes/auth.php';
$user=cas_require_user();$error='';
if(cas_post()){
  try {
    cas_assert_csrf();
    $name=trim((string)($_POST['full_name']??''));
    $phone=cas_phone((string)($_POST['phone']??''));
    $email=strtolower(trim((string)($_POST['email']??'')));
    $address=trim((string)($_POST['address']??''));
    if(mb_strlen($name)<2||mb_strlen($name)>120||mb_strlen($address)>255||!preg_match('/^0[0-9]{9}$/',$phone)||!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Họ tên, email hoặc điện thoại chưa hợp lệ.');
    $db=cas_db();$db->beginTransaction();
    try{
      $q=$db->prepare("UPDATE users SET full_name=?,email=?,phone=? WHERE user_id=?");
      $q->execute([$name,$email,$phone,$user['user_id']]);
      $q=$db->prepare("UPDATE customers SET full_name=?,phone=?,email=?,address=? WHERE user_id=?");
      $q->execute([$name,$phone,$email,$address,$user['user_id']]);
      $db->commit();
    }catch(Throwable $e){$db->rollBack();throw $e;}
    cas_redirect('tai-khoan.php?saved=1');
  }catch(Throwable $e){$error=$e instanceof PDOException?'Email đã tồn tại hoặc MySQL xảy ra lỗi.':$e->getMessage();}
}
$q=cas_db()->prepare("SELECT u.full_name,u.email,u.phone,c.address FROM users u LEFT JOIN customers c ON c.user_id=u.user_id WHERE u.user_id=?");
$q->execute([$user['user_id']]);$profile=$q->fetch()?:[];
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Quản lý thông tin khách hàng.">
  <title>Tài khoản | CÁ'S HOA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body data-page="account" class="d-flex flex-column">
  <div id="siteHeader"></div>

  <main class="cas-page content-page">
    <div class="cas-container">
      <div class="row g-4">
        <div class="col-lg-4">
    <aside class="content-card">
      <div class="d-flex align-items-center gap-3 mb-4">
        <div class="profile-avatar"><i class="bi bi-person"></i></div>
        <div>
          <p class="cas-muted small mb-1">Tài khoản khách hàng</p>
          <strong id="profileName"><?= cas_e($profile['full_name']??'Khách hàng') ?></strong>
        </div>
      </div>

      <nav class="account-nav" aria-label="Điều hướng tài khoản">
        <a class="active" href="tai-khoan.php"><i class="bi bi-person-lines-fill"></i> Thông tin cá nhân</a>
        <a href="lich-su-don-hang.php"><i class="bi bi-receipt"></i> Lịch sử đơn hàng</a>
        <a href="tra-cuu-don-hang.php"><i class="bi bi-search"></i> Tra cứu đơn</a>
        <form action="dang-xuat.php" method="post"><?= cas_csrf_form() ?><button class="btn-cas-outline mt-2" type="submit">Đăng xuất</button></form>
      </nav>
    </aside>
        </div>

        <div class="col-lg-8">
          <section class="content-card">
            <p class="cas-eyebrow">Hồ sơ khách hàng</p>
            <h1 class="font-display fs-2 mt-2">Thông tin cá nhân</h1>
            <p class="cas-muted mt-2">
              Lưu các thông tin thường dùng để thao tác đặt hoa nhanh hơn trong những lần tiếp theo.
            </p>

            <form id="profileForm" method="post" action="tai-khoan.php" class="row g-3 mt-2" novalidate>
              <div class="col-md-6">
                <label class="form-label small fw-semibold" for="fullName">Họ và tên</label>
                <input id="fullName" name="full_name" value="<?= cas_e($profile['full_name']??'') ?>" class="form-control cas-form-control" autocomplete="name" placeholder="Nguyễn Văn A">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold" for="phone">Số điện thoại</label>
                <input id="phone" name="phone" value="<?= cas_e($profile['phone']??'') ?>" class="form-control cas-form-control" inputmode="numeric" maxlength="10" autocomplete="tel" placeholder="0123456789">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold" for="email">Email</label>
                <input id="email" name="email" value="<?= cas_e($profile['email']??'') ?>" type="email" class="form-control cas-form-control" autocomplete="email" placeholder="example@gmail.com">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold" for="address">Địa chỉ thường dùng</label>
                <input id="address" name="address" value="<?= cas_e($profile['address']??'') ?>" class="form-control cas-form-control" autocomplete="street-address" placeholder="Số nhà, đường, phường/xã">
              </div>
              <div class="col-12">
                <?= cas_csrf_form() ?>
<?php if($error!==''): ?><div class="auth-message error"><?= cas_e($error) ?></div><?php elseif(isset($_GET['saved'])): ?><div class="auth-message success">Đã lưu thông tin cá nhân.</div><?php endif; ?>
                <button class="btn-cas-primary mt-2" type="submit"><i class="bi bi-check2"></i> Lưu thông tin</button>
              </div>
            </form>
          </section>

          <section class="content-card mt-3">
            <p class="cas-eyebrow">Tiện ích tài khoản</p>
            <div class="row g-3 mt-1">
              <div class="col-md-4"><div class="value-card"><i class="bi bi-clock-history"></i><h2 class="font-display fs-5 mt-3">Xem đơn đã đặt</h2><p class="cas-muted small mb-0">Theo dõi lại mã đơn, người nhận và trạng thái đơn hàng.</p></div></div>
              <div class="col-md-4"><div class="value-card"><i class="bi bi-geo-alt"></i><h2 class="font-display fs-5 mt-3">Lưu địa chỉ</h2><p class="cas-muted small mb-0">Giữ sẵn thông tin thường dùng cho những lần đặt hoa tiếp theo.</p></div></div>
              <div class="col-md-4"><div class="value-card"><i class="bi bi-chat-heart"></i><h2 class="font-display fs-5 mt-3">Hỗ trợ nhanh</h2><p class="cas-muted small mb-0">Liên hệ shop khi cần thay đổi thông tin hoặc thời gian nhận hoa.</p></div></div>
            </div>
          </section>
        </div>
      </div>
    </div>
  </main>

  <div id="siteFooter"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <script src="assets/js/products.php" defer></script>
  <script src="assets/js/cart-r2.php" defer></script>
  
  
  <script src="assets/js/main-r2.js" defer></script>
<script>window.CAS_CSRF = <?= json_encode(cas_csrf()) ?>;</script>
</body>
</html>
