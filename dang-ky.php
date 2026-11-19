<?php
require_once __DIR__.'/includes/customer.php';
$existing=cas_user();if($existing)cas_redirect($existing['role']==='customer'?'tai-khoan.php':'admin/index.php');
$error='';
if(cas_post()){
 try{
    cas_assert_csrf();
    $name=cas_customer_text($_POST,'full_name',255);
    $phone=cas_phone(cas_customer_text($_POST,'phone',30));
    $email=strtolower(trim(cas_customer_text($_POST,'email',150)));
    $password=(string)($_POST['password']??'');
    if(mb_strlen($name)<2||mb_strlen($name)>120)throw new RuntimeException('Họ tên phải từ 2 đến 120 ký tự.');
    if(!preg_match('/^0[0-9]{9}$/',$phone))throw new RuntimeException('Số điện thoại chưa hợp lệ.');
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>150)throw new RuntimeException('Email chưa hợp lệ.');
    if(!cas_valid_password($password))throw new RuntimeException('Mật khẩu tối thiểu 10 ký tự, gồm hoa/thường/số/ký tự đặc biệt.');
    if($password!==(string)($_POST['confirm_password']??''))throw new RuntimeException('Mật khẩu xác nhận không khớp.');
    $db=cas_db();$db->beginTransaction();
    try{
       $role=$db->query("SELECT role_id FROM roles WHERE name='customer'")->fetchColumn();
       if(!$role)throw new RuntimeException('Chưa import dữ liệu phân quyền.');
       $q=$db->prepare("SELECT COUNT(*) FROM users WHERE email=? OR phone=?");$q->execute([$email,$phone]);
       if((int)$q->fetchColumn()>0)throw new RuntimeException('Email hoặc số điện thoại đã được đăng ký.');
       $q=$db->prepare("INSERT INTO users(role_id,full_name,email,password_hash,phone) VALUES(?,?,?,?,?)");
       $q->execute([$role,$name,$email,password_hash($password,PASSWORD_DEFAULT),$phone]);
       $uid=(int)$db->lastInsertId();
       $q=$db->prepare("INSERT INTO customers(user_id,full_name,phone,email) VALUES(?,?,?,?)");
       $q->execute([$uid,$name,$phone,$email]);$db->commit();
    }catch(Throwable $e){$db->rollBack();throw $e;}
    session_regenerate_id(true);$_SESSION['cas_uid']=$uid;$_SESSION['cas_session_version']=1;
    unset($_SESSION['cas_csrf']);cas_redirect('tai-khoan.php');
 }catch(Throwable $e){$error=($e instanceof PDOException)?'Dữ liệu tài khoản đã tồn tại hoặc MySQL chưa sẵn sàng.':$e->getMessage();}
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Tạo tài khoản khách hàng CÁ'S HOA.">
  <title>Đăng ký | CÁ'S HOA</title>
  <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <link rel="stylesheet" href="assets/css/customer.css">
</head>
<body data-page="signup" class="auth-page">
  <main class="auth-main">
    <div class="auth-shell"><div class="auth-grid">
      <aside class="auth-story">
  <p class="cas-eyebrow">CÁ'S HOA · FLOWERS & FEELINGS</p>
  <h2>Những điều khó nói, gửi cùng một nhành hoa.</h2>
  <p>Tài khoản giúp bạn lưu thông tin, theo dõi đơn và giữ lại những lựa chọn hoa dành cho người thương.</p>
  <div class="d-flex align-items-center gap-3 mt-4 fw-semibold" style="color:var(--primary)"><span class="cas-brand-mark"><i class="bi bi-flower1"></i></span><span>Tiệm hoa tươi online</span></div>
</aside>
      <div><a href="index.php" class="auth-brand" aria-label="Về trang chủ CÁ'S HOA"><span class="cas-brand-mark"><i class="bi bi-flower1"></i></span><span class="cas-brand-name">CÁ'S HOA</span></a>
        <section class="auth-card">
          <p class="cas-eyebrow">Tạo tài khoản khách hàng</p>
          <h1>Lưu lại những đơn hoa.</h1>
          <p class="auth-description">Dùng email để khôi phục tài khoản và số điện thoại để shop liên hệ khi cần.</p>
          <form id="signupForm" method="post" action="dang-ky.php" class="auth-form" novalidate>
            <div class="mb-3"><label class="form-label" for="signupFullName">Họ và tên</label><input id="signupFullName" name="full_name" value="<?= cas_e($_POST['full_name']??'') ?>" class="form-control cas-form-control" autocomplete="name" minlength="2" maxlength="120" required></div>
            <div class="mb-3"><label for="signupPhone" class="form-label">Số điện thoại <span style="color:var(--danger)">*</span></label><input id="signupPhone" name="phone" value="<?= cas_e($_POST['phone']??'') ?>" class="form-control cas-form-control" inputmode="numeric" maxlength="10" pattern="0[0-9]{9}" placeholder="0123456789" autocomplete="tel" required></div>
            <div class="mb-3"><label for="signupEmail" class="form-label">Email <span style="color:var(--danger)">*</span></label><input id="signupEmail" name="email" value="<?= cas_e($_POST['email']??'') ?>" class="form-control cas-form-control" type="email" maxlength="150" placeholder="example@gmail.com" autocomplete="email" required></div>
            <div class="mb-3"><label for="signupPassword" class="form-label">Mật khẩu <span style="color:var(--danger)">*</span></label><div class="auth-password-wrap"><input id="signupPassword" name="password" class="form-control cas-form-control" type="password" autocomplete="new-password" required><button class="auth-password-toggle" type="button" data-toggle-password="signupPassword" aria-label="Hiện mật khẩu"><i class="bi bi-eye"></i></button></div><div class="password-policy">Từ 10 ký tự, tối đa 72 byte; có chữ hoa, chữ thường, số và ký tự đặc biệt.</div></div>
            <div class="mb-3"><label for="signupConfirm" class="form-label">Xác nhận mật khẩu <span style="color:var(--danger)">*</span></label><div class="auth-password-wrap"><input id="signupConfirm" name="confirm_password" class="form-control cas-form-control" type="password" autocomplete="new-password" required><button class="auth-password-toggle" type="button" data-toggle-password="signupConfirm" aria-label="Hiện mật khẩu"><i class="bi bi-eye"></i></button></div></div>
            <?= cas_csrf_form() ?>
<?php if($error!==''): ?><div class="auth-message error" role="alert"><?= cas_e($error) ?></div><?php endif; ?>
            <button class="btn-cas-primary w-100 mt-2" type="submit"><i class="bi bi-person-plus"></i> Tạo tài khoản <i class="bi bi-arrow-right"></i></button>
          </form>
          
          
          <p class="auth-foot">Đã có tài khoản? <a href="dang-nhap.php">Đăng nhập</a></p>
        </section>
      </div>
    </div></div>
  </main>
  
  <script src="assets/js/customer-forms.js" defer></script>
</body>
</html>
