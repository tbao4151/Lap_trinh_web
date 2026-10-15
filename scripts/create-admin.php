<?php
declare(strict_types=1);
// CLI only: CAS_ADMIN_EMAIL=... CAS_ADMIN_PASSWORD=... [CAS_ADMIN_ROLE=admin|staff|owner] [CAS_ADMIN_NAME=...] php scripts/create-admin.php
if (PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../includes/auth.php';
$email=strtolower(trim((string)getenv('CAS_ADMIN_EMAIL')));
$password=(string)getenv('CAS_ADMIN_PASSWORD');
$roleName=strtolower(trim((string)getenv('CAS_ADMIN_ROLE')))?:'admin';
$labels=['staff'=>'Nhân viên','admin'=>'Quản trị viên','owner'=>'Chủ sở hữu'];
if(!isset($labels[$roleName])){
    fwrite(STDERR,"CAS_ADMIN_ROLE chỉ nhận staff, admin hoặc owner.\n");
    exit(1);
}
$fullName=trim((string)getenv('CAS_ADMIN_NAME'))?:$labels[$roleName];
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||!cas_valid_password($password)){
    fwrite(STDERR,"Thiết lập CAS_ADMIN_EMAIL và CAS_ADMIN_PASSWORD (>=10, hoa/thường/số/ký tự đặc biệt).\n");
    exit(1);
}
$db=cas_db();$db->beginTransaction();
try {
    $q=$db->prepare("SELECT role_id FROM roles WHERE name=?");$q->execute([$roleName]);$role=$q->fetchColumn();
    if(!$role) throw new RuntimeException('Chạy migration và seed trước.');
    $q=$db->prepare("SELECT user_id FROM users WHERE email=?");$q->execute([$email]);
    if($q->fetchColumn()) throw new RuntimeException('Email đã tồn tại; không thay đổi mật khẩu tự động.');
    $q=$db->prepare("INSERT INTO users(role_id,full_name,email,password_hash,status) VALUES(?,?,?,?, 'active')");
    $q->execute([(int)$role,$fullName,$email,password_hash($password,PASSWORD_DEFAULT)]);
    $db->commit();echo "Đã tạo tài khoản {$labels[$roleName]}: $email\n";
} catch(Throwable $e){$db->rollBack();fwrite(STDERR,$e->getMessage()."\n");exit(1);}
