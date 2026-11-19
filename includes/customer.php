<?php
declare(strict_types=1);
require_once __DIR__.'/auth.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
if(cas_post()) {
    foreach($_POST as $value) {
        if(!is_string($value)) {http_response_code(422);exit('Thông tin gửi lên không hợp lệ.');}
    }
}

function cas_customer_text(array $data, string $key, int $max=255): string {
    $value=$data[$key]??'';
    if(!is_string($value) || mb_strlen($value)>$max) throw new RuntimeException('Thông tin nhập không hợp lệ hoặc quá dài.');
    return trim($value);
}
function cas_customer_error(Throwable $error): string {
    if($error instanceof PDOException || $error instanceof Error) {
        error_log('CAS customer: '.$error->getMessage());
        return 'Chưa thể xử lý yêu cầu. Vui lòng thử lại sau.';
    }
    return $error->getMessage();
}
function cas_checkout_key(): string {
    if(empty($_SESSION['cas_checkout_key'])) $_SESSION['cas_checkout_key']=bin2hex(random_bytes(24));
    return $_SESSION['cas_checkout_key'];
}
function cas_catalog_options(): array {
    $result=[];
    foreach(['categories','tones','occasions'] as $table) {
        $result[$table]=cas_db()->query("SELECT name FROM $table WHERE status='active' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    }
    return $result;
}
