<?php
declare(strict_types=1);
require_once __DIR__.'/includes/customer.php';
$error='';$success='';
$uid=filter_var($_POST['uid']??$_GET['uid']??null,FILTER_VALIDATE_INT);
$token=is_string($_POST['token']??$_GET['token']??null)?($_POST['token']??$_GET['token']):'';
$resetMode=$uid!==false&&$uid>0&&preg_match('/^[a-f0-9]{64}$/',$token);
if(cas_post()){
  try{
    cas_assert_csrf();
    $attempts=$_SESSION['cas_reset_attempts']??[];
    $attempts=array_values(array_filter($attempts,static fn($v)=>$v>time()-3600));
    if(count($attempts)>=5)throw new RuntimeException('Quá nhiều yêu cầu, vui lòng thử sau một giờ.');
    $attempts[]=time();$_SESSION['cas_reset_attempts']=$attempts;
    if(($_POST['action']??'')==='request'){
      $email=strtolower(trim(cas_customer_text($_POST,'email',150)));
      if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Vui lòng nhập email hợp lệ.');
      $q=cas_db()->prepare("SELECT user_id FROM users WHERE email=? AND status='active' LIMIT 1");
      $q->execute([$email]);$found=$q->fetchColumn();
      if($found){
        $secret=bin2hex(random_bytes(32));$digest=hash('sha256',$secret);
        $db=cas_db();
        $q=$db->prepare("INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 30 MINUTE))");
        $q->execute([(int)$found,$digest]);
        $base=rtrim((string)(getenv('CAS_APP_URL')?:'http://localhost/Lap_trinh_web'),'/');
        // URL fragment never reaches HTTP access logs or the Referer header.
        $link=$base.'/quen-mat-khau.php#uid='.(int)$found.'&token='.$secret;
        $sent=@mail($email,'Khoi phuc mat khau CAS HOA',"Mo lien ket trong 30 phut: ".$link."\n","From: no-reply@localhost\r\n");
        if(!$sent){
          $db->prepare("DELETE FROM password_resets WHERE token_hash=?")->execute([$digest]);
          error_log('CAS password reset email transport is not configured.');
        }
      }
      $success='Nếu email tồn tại và máy chủ được cấu hình gửi thư, liên kết đặt lại mật khẩu sẽ được gửi đến hộp thư.';
    }elseif(($_POST['action']??'')==='reset' && $resetMode){
      $password=(string)($_POST['password']??'');
      if(!cas_valid_password($password)||$password!==(string)($_POST['confirm']??''))
        throw new RuntimeException('Mật khẩu cần >=10 ký tự, chữ hoa/thường, số, ký tự đặc biệt và xác nhận khớp.');
      $db=cas_db();$db->beginTransaction();
      try{
        $q=$db->prepare('SELECT reset_id FROM password_resets WHERE user_id=? AND token_hash=? AND consumed_at IS NULL AND expires_at>NOW() FOR UPDATE');
        $q->execute([$uid,hash('sha256',$token)]);$rid=$q->fetchColumn();
        if(!$rid)throw new RuntimeException('Liên kết không hợp lệ hoặc đã hết hạn.');
        $q=$db->prepare('UPDATE users SET password_hash=? WHERE user_id=?');
        $q->execute([password_hash($password,PASSWORD_DEFAULT),$uid]);
        $q=$db->prepare('UPDATE password_resets SET consumed_at=NOW() WHERE user_id=? AND consumed_at IS NULL');
        $q->execute([$uid]);$db->commit();
        $success='Mật khẩu đã được thay đổi. Bạn có thể đăng nhập.';
        $resetMode=false;
      }catch(Throwable $e){$db->rollBack();throw $e;}
    }else throw new RuntimeException('Thao tác không hợp lệ.');
  }catch(Throwable $e){$error=cas_customer_error($e);}
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Khôi phục mật khẩu tài khoản CÁ'S HOA.">
  <title>Quên mật khẩu | CÁ'S HOA</title>
  <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <link rel="stylesheet" href="assets/css/customer.css">
</head>
<body data-page="forgot" class="auth-page">
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
          <p class="cas-eyebrow">Khôi phục tài khoản</p>
          <h1>Quên mật khẩu</h1>
          <p class="auth-description">Nhập email đã đăng ký để nhận liên kết đặt lại mật khẩu có thời hạn 30 phút.</p>
          <?php if($success!==''): ?><div class="auth-message success mb-3" role="status"><?= cas_e($success) ?></div><?php endif; ?>
          <?php if($error!==''): ?><div class="auth-message error mb-3" role="alert"><?= cas_e($error) ?></div><?php endif; ?>
          <?php if($resetMode): ?>
          <p class="auth-description">Nhập mật khẩu mới cho tài khoản. Liên kết chỉ dùng được một lần.</p>
          <form id="recoveryVerifyForm" class="auth-form" method="post" action="quen-mat-khau.php">
            <input type="hidden" name="uid" value="<?= (int)$uid ?>"><input type="hidden" name="token" value="<?= cas_e($token) ?>">
            <?= cas_csrf_form() ?><input type="hidden" name="action" value="reset">
            <div class="mb-3"><label for="newPassword" class="form-label">Mật khẩu mới</label><input type="password" id="newPassword" name="password" class="form-control cas-form-control" required></div>
            <div class="mb-3"><label for="confirmPassword" class="form-label">Xác nhận mật khẩu</label><input type="password" id="confirmPassword" name="confirm" class="form-control cas-form-control" required></div>
            <button class="btn-cas-primary w-100" type="submit">Đổi mật khẩu</button>
          </form>
          <?php else: ?>
          <form id="recoveryForm" class="auth-form" method="post" action="quen-mat-khau.php">
            <?= cas_csrf_form() ?><input type="hidden" name="action" value="request">
            <div class="mb-3"><label for="recoveryIdentifier" class="form-label">Email tài khoản</label><input id="recoveryIdentifier" name="email" class="form-control cas-form-control" type="email" placeholder="example@gmail.com" autocomplete="email" required></div>
            <button class="btn-cas-primary w-100" type="submit">Gửi liên kết khôi phục</button>
          </form>
          <?php endif; ?>
          <p class="auth-foot">Nhớ mật khẩu rồi? <a href="dang-nhap.php">Đăng nhập</a></p>
        </section>
      </div>
    </div></div>
  </main>
  
  <script src="assets/js/customer-forms.js" defer></script>
</body>
</html>
