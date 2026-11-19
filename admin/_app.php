<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && str_replace('\\', '/', strtolower((string)realpath($_SERVER['SCRIPT_FILENAME'] ?? ''))) === str_replace('\\', '/', strtolower((string)realpath(__FILE__)))) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Dispatcher cho 4 route lõi Admin (dashboard, orders, customers, reports).
 */
require_once __DIR__ . '/_actions.php';

[$title, $icon] = cas_admin_core_meta((string)($cas_page ?? ''));
cas_admin_layout_start(
    $user,
    $title,
    "Hệ thống quản trị CÁ'S HOA — dữ liệu vận hành theo thời gian thực.",
    $icon,
    (string)($cas_page ?? ''),
    $error,
    $notice
);
cas_admin_render_core_page($db, $user, (string)($cas_page ?? ''));
cas_admin_layout_end();
