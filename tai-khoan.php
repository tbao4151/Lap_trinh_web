<?php
require_once __DIR__.'/includes/customer.php';
$user=cas_require_user();$error='';
if(cas_post()){
  try {
    cas_assert_csrf();
    $name=cas_customer_text($_POST,'full_name',255);
    $phone=cas_phone(cas_customer_text($_POST,'phone',30));
    $email=strtolower(trim(cas_customer_text($_POST,'email',150)));
    $address=cas_customer_text($_POST,'address',255);
    if(mb_strlen($name)<2||mb_strlen($name)>120||mb_strlen($address)>255||!preg_match('/^0[0-9]{9}$/',$phone)||!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Họ tên, email hoặc điện thoại chưa hợp lệ.');
    $db=cas_db();$db->beginTransaction();
    try{
      $check=$db->prepare('SELECT user_id FROM users WHERE (email=? OR phone=?) AND user_id<>? LIMIT 1');
      $check->execute([$email,$phone,$user['user_id']]);
      if($check->fetch())throw new RuntimeException('Email hoặc số điện thoại đã được tài khoản khác sử dụng.');
      $q=$db->prepare("UPDATE users SET full_name=?,email=?,phone=? WHERE user_id=?");
      $q->execute([$name,$email,$phone,$user['user_id']]);
      $q=$db->prepare("UPDATE customers SET full_name=?,phone=?,email=?,address=? WHERE user_id=?");
      $q->execute([$name,$phone,$email,$address,$user['user_id']]);
      $db->commit();
    }catch(Throwable $e){$db->rollBack();throw $e;}
    cas_redirect('tai-khoan.php?saved=1');
  }catch(Throwable $e){$error=cas_customer_error($e);}
}
$q=cas_db()->prepare("SELECT u.full_name,u.email,u.phone,c.address FROM users u LEFT JOIN customers c ON c.user_id=u.user_id WHERE u.user_id=?");
$q->execute([$user['user_id']]);$profile=$q->fetch()?:[];
cas_page_start("Tài khoản | CÁ'S HOA", 'account', "Quản lý thông tin khách hàng.");
?>

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

<?php cas_account_nav('account', true); ?>
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
<?php cas_page_end(); ?>
