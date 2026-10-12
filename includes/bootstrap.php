<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Ho_Chi_Minh');
if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params(['httponly'=>true, 'secure'=>$secure, 'samesite'=>'Lax', 'path'=>'/']);
    session_start();
}
require_once __DIR__ . '/../config/database.php';

function cas_e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function cas_csrf(): string
{
    if (empty($_SESSION['cas_csrf'])) {
        $_SESSION['cas_csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['cas_csrf'];
}
function cas_csrf_valid(?string $value): bool
{
    return is_string($value) && hash_equals(cas_csrf(), $value);
}
function cas_json(int $status, array $result): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    exit;
}
function cas_require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        cas_json(405, ['ok'=>false,'message'=>'Phương thức không được hỗ trợ.']);
    }
}
function cas_str(string $field, int $max = 255): string
{
    $value = trim((string)($_POST[$field] ?? ''));
    if (mb_strlen($value, 'UTF-8') > $max) {
        cas_json(422, ['ok'=>false,'message'=>'Nội dung quá dài: '.$field]);
    }
    return $value;
}
function cas_phone(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}
