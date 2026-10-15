<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
$name = getenv('DB_NAME') ?: 'cas_hoa_customer_preview';
if (!preg_match('/^cas_hoa_customer_(preview|test_[a-z0-9]+)$/D', $name)) {
    throw new RuntimeException('Chỉ khởi tạo database Customer preview/test riêng.');
}
$db = new PDO('mysql:host='.(getenv('DB_HOST') ?: '127.0.0.1').';port='.(getenv('DB_PORT') ?: '3307').';charset=utf8mb4',
    getenv('DB_USER') ?: 'root', getenv('DB_PASSWORD') ?: '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
foreach (['schema.sql','seed.sql','migrations/001_auth.php.sql','migrations/002_inventory_seed.sql','migrations/003_admin_ops.sql','migrations/004_order_inventory_sessions.sql'] as $file) {
    $sql = file_get_contents(__DIR__.'/../database/'.$file);
    $sql = str_replace(['CREATE DATABASE IF NOT EXISTS cas_hoa ', 'USE cas_hoa;'], ['CREATE DATABASE IF NOT EXISTS `'.$name.'` ', 'USE `'.$name.'`;'], $sql);
    $db->exec($sql);
}
// Sample wrapping data for the isolated preview only; original shared seed is unchanged.
$db->exec("INSERT IGNORE INTO wrapping_colors(color_id,name,hex_code,status) VALUES (9001,'Kem','#F4E8D1','active'),(9002,'Nâu kraft','#B58A60','active')");
$db->exec("INSERT IGNORE INTO product_wrapping(product_id,color_id) VALUES (1,9001),(1,9002),(2,9001)");
echo "Customer database ready: $name\n";
