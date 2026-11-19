<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && str_replace('\\', '/', strtolower((string)realpath($_SERVER['SCRIPT_FILENAME'] ?? ''))) === str_replace('\\', '/', strtolower((string)realpath(__FILE__)))) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Shared storefront HTML chrome, auth layout shell, account navigation, and status labels.
 */

function cas_order_statuses(): array
{
    return [
        'pending_confirmation' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'preparing' => 'Đang chuẩn bị',
        'ready' => 'Sẵn sàng',
        'delivering' => 'Đang giao',
        'completed' => 'Hoàn tất',
        'cancelled' => 'Đã hủy',
    ];
}

function cas_order_status_label(string $status): string
{
    return cas_order_statuses()[$status] ?? $status;
}

function cas_storefront_head(string $title, string $description): void
{
    ?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= cas_e($description) ?>">
  <title><?= cas_e($title) ?></title>
  <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <link rel="stylesheet" href="assets/css/customer.css">
</head>
<?php
}

function cas_page_start(string $title, string $page, string $description, string $bodyClass = 'd-flex flex-column'): void
{
    cas_storefront_head($title, $description);
    ?>
<body data-page="<?= cas_e($page) ?>" class="<?= cas_e($bodyClass) ?>">
  <div id="siteHeader"></div>
<?php
}

/**
 * @param string[] $extraScripts
 */
function cas_page_end(array $extraScripts = [], bool $includeCustomerForms = true): void
{
    ?>
  <div id="siteFooter"></div>

  <script src="assets/vendor/bootstrap/bootstrap.bundle.min.js" defer></script>
  <script src="assets/js/products.php" defer></script>
  <script src="assets/js/cart-r2.php" defer></script>
<?php foreach ($extraScripts as $script): ?>
  <script src="<?= cas_e($script) ?>" defer></script>
<?php endforeach; ?>
  <script src="assets/js/main-r2.js" defer></script>
  <script>window.CAS_CSRF = <?= json_encode(cas_csrf()) ?>;</script>
<?php if ($includeCustomerForms): ?>
  <script src="assets/js/customer-forms.js" defer></script>
<?php endif; ?>
</body>
</html>
<?php
}

function cas_auth_start(string $title, string $page, string $description): void
{
    cas_storefront_head($title, $description);
    ?>
<body data-page="<?= cas_e($page) ?>" class="auth-page">
  <main class="auth-main">
    <div class="auth-shell"><div class="auth-grid">
      <aside class="auth-story">
        <p class="cas-eyebrow">CÁ'S HOA · FLOWERS &amp; FEELINGS</p>
        <h2>Những điều khó nói, gửi cùng một nhành hoa.</h2>
        <p>Tài khoản giúp bạn lưu thông tin, theo dõi đơn và giữ lại những lựa chọn hoa dành cho người thương.</p>
        <div class="d-flex align-items-center gap-3 mt-4 fw-semibold" style="color:var(--primary)"><span class="cas-brand-mark"><i class="bi bi-flower1"></i></span><span>Tiệm hoa tươi online</span></div>
      </aside>
      <div><a href="index.php" class="auth-brand" aria-label="Về trang chủ CÁ'S HOA"><span class="cas-brand-mark"><i class="bi bi-flower1"></i></span><span class="cas-brand-name">CÁ'S HOA</span></a>
        <section class="auth-card">
<?php
}

function cas_auth_end(): void
{
    ?>
        </section>
      </div>
    </div></div>
  </main>

  <script src="assets/js/customer-forms.js" defer></script>
</body>
</html>
<?php
}

function cas_account_nav(string $active, bool $withLogout = false): void
{
    $items = [
        'account' => ['tai-khoan.php', 'bi-person-lines-fill', 'Thông tin cá nhân'],
        'orders'  => ['lich-su-don-hang.php', 'bi-receipt', 'Lịch sử đơn hàng'],
        'lookup'  => ['tra-cuu-don-hang.php', 'bi-search', 'Tra cứu đơn'],
    ];
    ?>
      <nav class="account-nav" aria-label="Điều hướng tài khoản">
<?php foreach ($items as $key => [$href, $icon, $label]): $isActive = $key === $active; ?>
        <a<?= $isActive ? ' class="active" aria-current="page"' : '' ?> href="<?= cas_e($href) ?>"><i class="bi <?= cas_e($icon) ?>"></i> <?= cas_e($label) ?></a>
<?php endforeach; ?>
<?php if ($withLogout): ?>
        <form action="dang-xuat.php" method="post"><input type="hidden" name="csrf" value="<?= cas_e(cas_csrf()) ?>"><button class="btn-cas-outline w-100 mt-2" type="submit">Đăng xuất</button></form>
<?php endif; ?>
      </nav>
<?php
}
