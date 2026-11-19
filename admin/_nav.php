<?php
declare(strict_types=1);
/**
 * Menu quản trị đủ 24 route, nhóm theo nghiệp vụ và chỉ hiển thị mục vai trò hiện tại được dùng.
 * Dùng chung cho _app.php và _ops.php.
 */
$casNavRole = $user['role'] ?? '';
$casNavAll = ['staff', 'admin', 'owner'];
$casNavGroups = [
    '' => [['index.php', 'Tổng quan', $casNavAll, 'bi-grid']],
    'Vận hành' => [
        ['don-hang.php', 'Đơn hàng', $casNavAll, 'bi-clipboard2-check'],
        ['hoan-tien.php', 'Hoàn tiền', $casNavAll, 'bi-arrow-counterclockwise'],
        ['giao-hang.php', 'Giao hàng', $casNavAll, 'bi-truck'],
        ['khach-hang.php', 'Khách hàng', $casNavAll, 'bi-people'],
        ['bao-cao.php', 'Báo cáo doanh thu', $casNavAll, 'bi-bar-chart'],
        ['loi-nhuan.php', 'Biểu đồ lợi nhuận', ['admin', 'owner'], 'bi-graph-up-arrow'],
    ],
    'Sản phẩm' => [
        ['san-pham.php', 'Sản phẩm', $casNavAll, 'bi-flower2'],
        ['them-san-pham.php', 'Thêm sản phẩm', ['admin', 'owner'], 'bi-plus-square'],
        ['quan-ly-nhanh.php', 'Quản lý nhanh', $casNavAll, 'bi-lightning-charge'],
        ['giay-goi.php', 'Màu giấy gói', $casNavAll, 'bi-palette'],
        ['phan-loai-bo-loc.php', 'Phân loại & bộ lọc', $casNavAll, 'bi-tags'],
        ['kham-pha.php', 'Danh mục khám phá', $casNavAll, 'bi-compass'],
    ],
    'Kho' => [
        ['kho-tong.php', 'Kho tổng', $casNavAll, 'bi-building'],
        ['cong-thuc-hoa.php', 'Công thức nguyên liệu', ['admin', 'owner'], 'bi-list-check'],
        ['kho-hoa.php', 'Kho Hoa', $casNavAll, 'bi-flower3'],
        ['kho-phu-kien.php', 'Kho Phụ kiện', $casNavAll, 'bi-box-seam'],
        ['lich-su-kho.php', 'Lịch sử kho', $casNavAll, 'bi-clock-history'],
    ],
    'Hệ thống' => [
        ['nhan-vien.php', 'Nhân viên & quyền', ['admin', 'owner'], 'bi-person-gear'],
        ['cai-dat.php', 'Cài đặt', ['admin', 'owner'], 'bi-gear'],
        ['tai-khoan.php', 'Tài khoản', $casNavAll, 'bi-person-circle'],
        ['chu-so-huu.php', 'Chủ sở hữu', ['owner'], 'bi-shield-check'],
    ],
];
$casNavNow = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<nav class="cas-admin-nav admin-nav-scroll" aria-label="Điều hướng quản trị">
<?php foreach ($casNavGroups as $casNavTitle => $casNavItems):
    $casNavItems = array_filter($casNavItems, static fn($i) => in_array($casNavRole, $i[2], true));
    if (!$casNavItems) continue; ?>
  <?php if ($casNavTitle !== ''): ?><p class="cas-admin-nav-title"><?= cas_e($casNavTitle) ?></p><?php endif; ?>
  <?php foreach ($casNavItems as [$casNavUrl, $casNavLabel, , $casNavIcon]):
      $casNavActive = $casNavNow === $casNavUrl || ($casNavUrl === 'san-pham.php' && $casNavNow === 'sua-san-pham.php'); ?>
  <a href="<?= cas_e($casNavUrl) ?>" class="admin-nav-link<?= $casNavActive ? ' active' : '' ?>"<?= $casNavActive ? ' aria-current="page"' : '' ?>><i class="bi <?= cas_e($casNavIcon) ?>" aria-hidden="true"></i><span><?= cas_e($casNavLabel) ?></span></a>
  <?php endforeach; ?>
<?php endforeach; ?>
</nav>
