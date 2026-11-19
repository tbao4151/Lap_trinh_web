<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && str_replace('\\', '/', strtolower((string)realpath($_SERVER['SCRIPT_FILENAME'] ?? ''))) === str_replace('\\', '/', strtolower((string)realpath(__FILE__)))) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Bootstrapper cho nhóm route lõi Admin (index, don-hang, khach-hang, bao-cao).
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/order-inventory.php';
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/modules/orders.php';

$user = cas_require_user(true);
$db = cas_db();
$error = '';
$notice = isset($_GET['saved']) ? 'Thao tác đã lưu vào MySQL.' : '';
$statuses = cas_admin_order_statuses();
$payments = cas_admin_payment_statuses();

cas_admin_handle_order_post($db, $user, (string)($cas_page ?? ''), $error);
