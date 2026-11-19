<?php
require_once __DIR__.'/includes/customer.php';
if(cas_user() && cas_user()['role']==='customer')cas_redirect('tai-khoan.php');
$error='';
$next=is_string($_GET['next']??null)?$_GET['next']:'tai-khoan.php';
if(!in_array($next,['tai-khoan.php','lich-su-don-hang.php','thanh-toan.php'],true))$next='tai-khoan.php';
if(cas_post()){
 try{
   cas_assert_csrf();
   $attempts=$_SESSION['cas_login_attempts']??[];
   $attempts=array_values(array_filter($attempts,static fn($t)=>$t>time()-600));
   if(count($attempts)>=10)throw new RuntimeException('Đăng nhập quá nhiều lần. Hãy thử lại sau 10 phút.');
   $attempts[]=time();$_SESSION['cas_login_attempts']=$attempts;
   if(!cas_login(cas_customer_text($_POST,'identifier',255),(string)($_POST['password']??'')))throw new RuntimeException('Email, số điện thoại hoặc mật khẩu chưa đúng.');
   unset($_SESSION['cas_login_attempts']);cas_redirect($next);
 }catch(Throwable $e){$error=cas_customer_error($e);}
}
cas_auth_start("Đăng nhập | CÁ'S HOA", 'login', "Đăng nhập tài khoản khách hàng CÁ'S HOA.");
?>
          <p class="cas-eyebrow">Tài khoản khách hàng</p>
          <h1>Chào mừng bạn trở lại.</h1>
          <p class="auth-description">Đăng nhập bằng Gmail hoặc số điện thoại cùng mật khẩu của bạn.</p>
          <form id="loginForm" method="post" action="dang-nhap.php?next=<?= urlencode($next) ?>" class="auth-form" novalidate>
            <div class="mb-3"><label for="loginIdentifier" class="form-label">Email hoặc số điện thoại</label><input id="loginIdentifier" name="identifier" class="form-control cas-form-control" type="text" inputmode="email" placeholder="example@gmail.com hoặc 0123456789" autocomplete="username" required></div>
            <div class="mb-2"><label for="loginPassword" class="form-label">Mật khẩu</label><div class="auth-password-wrap"><input id="loginPassword" name="password" class="form-control cas-form-control" type="password" placeholder="Mật khẩu của bạn" autocomplete="current-password" required><button class="auth-password-toggle" type="button" data-toggle-password="loginPassword" aria-label="Hiện mật khẩu" aria-pressed="false"><i class="bi bi-eye"></i></button></div></div>
            <div class="text-end mb-3"><a href="quen-mat-khau.php" class="small fw-bold" style="color:var(--primary)">Quên mật khẩu?</a></div>
            <?= cas_csrf_form() ?>
<?php if($error!==''): ?><div class="auth-message error" role="alert"><?= cas_e($error) ?></div><?php endif; ?>
            <button class="btn-cas-primary w-100 mt-3" type="submit"><i class="bi bi-lock"></i> Đăng nhập <i class="bi bi-arrow-right"></i></button>
          </form>
          
          
          <p class="auth-foot">Chưa có tài khoản? <a href="dang-ky.php">Đăng ký ngay</a></p>
          <p class="auth-foot mt-2"><a href="index.php" style="color:var(--muted-foreground)">Tiếp tục mua hoa như khách</a></p>
<?php cas_auth_end(); ?>
