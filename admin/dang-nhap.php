<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
$u=cas_user();
if($u && in_array($u['role'],['staff','admin','owner'],true))cas_redirect('index.php');
$error='';
if(cas_post()){
  try {
    cas_assert_csrf();
    $attempts=$_SESSION['cas_admin_login_attempts']??[];
    $attempts=array_values(array_filter($attempts,static fn($v)=>$v>time()-600));
    if(count($attempts)>=8)throw new RuntimeException('Quá nhiều lần đăng nhập. Thử lại sau 10 phút.');
    $attempts[]=time();$_SESSION['cas_admin_login_attempts']=$attempts;
    if(!cas_login((string)($_POST['email']??''),(string)($_POST['password']??''),['staff','admin','owner']))
      throw new RuntimeException('Thông tin đăng nhập quản trị không hợp lệ.');
    unset($_SESSION['cas_admin_login_attempts']);cas_redirect('index.php');
  } catch(Throwable $e){$error=$e->getMessage();}
}
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng nhập quản trị | CÁ'S HOA Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css"></head>
<body data-admin-page="login"><main class="admin-login"><section class="admin-login-card">
<a class="admin-brand" href="../index.php"><span><strong>CÁ'S HOA</strong><small>flowers & feelings</small></span></a>
<h1>Trung tâm điều hành</h1>
<p>Đăng nhập bằng tài khoản nhân viên hoặc quản trị viên được cấp trên hệ thống MySQL.</p>
<form method="post" action="dang-nhap.php" class="mt-4">
<?= cas_csrf_form() ?>
<div class="mb-3"><label class="admin-label" for="adminEmail">Email quản trị</label>
<input id="adminEmail" name="email" class="admin-input" type="email" autocomplete="username" required></div>
<div class="mb-3"><label class="admin-label" for="adminPassword">Mật khẩu</label>
<input id="adminPassword" name="password" class="admin-input" type="password" autocomplete="current-password" required></div>
<?php if($error!==''): ?><div class="alert alert-danger" role="alert"><?= cas_e($error) ?></div><?php endif; ?>
<button class="admin-btn w-100" type="submit">Đăng nhập</button>
</form>
<a class="admin-btn-light w-100 mt-2" href="../index.php">Về cửa hàng</a>
</section></main></body></html>
