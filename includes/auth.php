<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function cas_user(): ?array {
    if (empty($_SESSION['cas_uid'])) return null;
    $q=cas_db()->prepare("SELECT u.user_id,u.full_name,u.email,u.phone,u.status,r.name AS role FROM users u JOIN roles r ON r.role_id=u.role_id WHERE u.user_id=? LIMIT 1");
    $q->execute([(int)$_SESSION['cas_uid']]);$user=$q->fetch();
    if (!$user || $user['status']!=='active') { unset($_SESSION['cas_uid']);return null; }
    return $user;
}
function cas_login(string $identifier,string $password,array $allowedRoles=['customer']): bool {
    $identifier=trim($identifier);
    $sql="SELECT u.user_id,u.password_hash,u.status,r.name AS role FROM users u JOIN roles r ON r.role_id=u.role_id WHERE u.email=? OR u.phone=? LIMIT 1";
    $q=cas_db()->prepare($sql);$q->execute([$identifier,cas_phone($identifier)]);$u=$q->fetch();
    if(!$u || $u['status']!=='active' || !in_array($u['role'],$allowedRoles,true) || !password_verify($password,$u['password_hash'])) return false;
    session_regenerate_id(true);
    $_SESSION['cas_uid']=(int)$u['user_id'];
    unset($_SESSION['cas_csrf']);
    return true;
}
function cas_logout(): void {
    $_SESSION=[];
    if(ini_get('session.use_cookies')) {
        $params=session_get_cookie_params();
        setcookie(session_name(),'',time()-42000,$params['path'],$params['domain'],$params['secure'],$params['httponly']);
    }
    session_destroy();
}
function cas_require_user(bool $admin=false): array {
    $user=cas_user();
    $allowed=$admin?['staff','admin','owner']:['customer'];
    if(!$user||!in_array($user['role'],$allowed,true)){
        header('Location: '.($admin?'dang-nhap.php':'dang-nhap.php?next='.rawurlencode(basename($_SERVER['SCRIPT_NAME']??'tai-khoan.php'))),true,303);
        exit;
    }
    return $user;
}
function cas_csrf_form(): string {
    return '<input type="hidden" name="csrf" value="'.cas_e(cas_csrf()).'">';
}
function cas_assert_csrf(): void {
    if(!cas_csrf_valid($_POST['csrf']??null)){http_response_code(403);throw new RuntimeException('Phiên đã hết hạn. Tải lại trang và thử lại.');}
}
function cas_post(): bool {return ($_SERVER['REQUEST_METHOD']??'GET')==='POST';}
function cas_redirect(string $url): never {header('Location: '.$url,true,303);exit;}
function cas_valid_password(string $password): bool {
    return strlen($password)>=10 && preg_match('/[A-Z]/',$password) && preg_match('/[a-z]/',$password) && preg_match('/[0-9]/',$password) && preg_match('/[^A-Za-z0-9]/',$password);
}
function cas_customer_id(int $userId): ?int {
    $q=cas_db()->prepare("SELECT customer_id FROM customers WHERE user_id=? LIMIT 1");$q->execute([$userId]);
    $id=$q->fetchColumn();return $id===false?null:(int)$id;
}
