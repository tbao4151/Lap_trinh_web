<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && str_replace('\\', '/', strtolower((string)realpath($_SERVER['SCRIPT_FILENAME'] ?? ''))) === str_replace('\\', '/', strtolower((string)realpath(__FILE__)))) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Shared HTML layout chrome for all Admin routes (_app.php and _ops.php).
 */

if (!defined('CAS_ROLE_LABELS')) {
    define('CAS_ROLE_LABELS', ['staff' => 'Nhân viên', 'admin' => 'Quản trị viên', 'owner' => 'Chủ sở hữu', 'customer' => 'Khách hàng']);
}

if (!function_exists('cas_money')) {
    function cas_money(mixed $value): string
    {
        return number_format((float)$value, 0, ',', '.') . ' ₫';
    }
}

function cas_admin_layout_start(array $user, string $title, string $subtitle, string $icon = 'bi-flower2', string $pageKey = '', string $error = '', string $notice = ''): void
{
    $bodyPage = $pageKey !== '' ? $pageKey : basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
    ?>
<!doctype html><html lang="vi"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= cas_e($title) ?> | CÁ'S HOA Admin</title>
<link href="../assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
<link href="../assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body data-admin-page="<?= cas_e($bodyPage) ?>">
<div class="admin-app">
<aside class="admin-sidebar" id="adminSidebar">
  <div class="admin-sidebar-head">
    <a class="admin-brand" href="index.php"><span class="admin-brand-mark"><i class="bi bi-flower1" aria-hidden="true"></i></span><span><strong>CÁ'S HOA</strong><small>Admin PHP/MySQL</small></span></a>
    <button class="admin-sidebar-close" id="adminSidebarClose" type="button" aria-label="Đóng menu"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
  </div>
  <?php require __DIR__ . '/_nav.php'; ?>
</aside>
<button class="admin-overlay" id="adminOverlay" type="button" aria-label="Đóng menu"></button>
<div class="admin-workspace">
<header class="admin-topbar">
 <div class="admin-topbar-left">
   <button class="admin-menu-button" id="adminMenuOpen" type="button" aria-controls="adminSidebar" aria-expanded="false" aria-label="Mở menu"><i class="bi bi-list" aria-hidden="true"></i></button>
   <div><div class="admin-topbar-title"><?= cas_e($title) ?></div><span class="admin-role"><?= cas_e(CAS_ROLE_LABELS[$user['role']] ?? $user['role']) ?></span></div>
 </div>
 <div class="admin-topbar-actions">
   <a class="admin-store-link" href="../index.php" aria-label="Về cửa hàng" title="Về cửa hàng"><i class="bi bi-shop" aria-hidden="true"></i><span>Về cửa hàng</span></a>
   <a class="admin-btn-light" href="tai-khoan.php" aria-label="Tài khoản của bạn" title="Tài khoản của bạn"><i class="bi bi-person-circle" aria-hidden="true"></i><span><?= cas_e($user['full_name']) ?></span></a>
   <form action="../dang-xuat.php" method="post" class="m-0"><?= cas_csrf_form() ?><button class="admin-btn-light" type="submit" aria-label="Đăng xuất" title="Đăng xuất"><i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Đăng xuất</span></button></form>
 </div>
</header>
<main class="admin-content">
<section class="admin-hero"><div><h1><?= cas_e($title) ?></h1><p><?= cas_e($subtitle) ?></p></div><div class="admin-hero-icon"><i class="bi <?= cas_e($icon) ?>" aria-hidden="true"></i></div></section>
<?php if ($error !== ''): ?><div class="alert alert-danger admin-section" role="alert"><?= cas_e($error) ?></div><?php endif; ?>
<?php if ($notice !== ''): ?><div class="alert alert-success admin-section" role="status"><?= cas_e($notice) ?></div><?php endif; ?>
<?php
}

function cas_admin_layout_end(): void
{
    echo "\n</main></div></div>\n<script>document.querySelectorAll('form[data-confirm]').forEach(function(f){f.addEventListener('submit',function(e){if(!window.confirm(f.dataset.confirm))e.preventDefault();});});(function(){var sb=document.getElementById('adminSidebar'),ov=document.getElementById('adminOverlay'),open=document.getElementById('adminMenuOpen'),close=document.getElementById('adminSidebarClose');function toggle(s){if(sb)sb.classList.toggle('open',s);if(ov)ov.classList.toggle('open',s);if(open)open.setAttribute('aria-expanded',String(s));}if(open)open.addEventListener('click',function(){toggle(true);});if(close)close.addEventListener('click',function(){toggle(false);});if(ov)ov.addEventListener('click',function(){toggle(false);});document.addEventListener('keydown',function(e){if(e.key==='Escape'&&sb&&sb.classList.contains('open'))toggle(false);});})();</script>\n</body></html>";
}
