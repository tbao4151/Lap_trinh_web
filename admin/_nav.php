<?php
declare(strict_types=1);
/**
 * Menu quản trị đủ 22 màn hình, nhóm như bản giao diện yêu cầu 1, chỉ hiện mục vai trò hiện tại được dùng.
 * Dùng chung cho _app.php (route của trưởng nhóm) và _ops.php (route Admin Operations).
 */
$casNavRole = $user['role'] ?? '';
$casNavAll = ['staff', 'admin', 'owner'];
$casNavGroups = [
    '' => [['index.php', 'Tổng quan', $casNavAll]],
    'Vận hành' => [
        ['don-hang.php', 'Đơn hàng', $casNavAll], ['hoan-tien.php', 'Hoàn tiền', $casNavAll],
        ['giao-hang.php', 'Giao hàng', $casNavAll], ['khach-hang.php', 'Khách hàng', $casNavAll],
        ['bao-cao.php', 'Báo cáo doanh thu', $casNavAll], ['loi-nhuan.php', 'Biểu đồ lợi nhuận', ['admin', 'owner']],
    ],
    'Sản phẩm' => [
        ['san-pham.php', 'Sản phẩm', $casNavAll], ['them-san-pham.php', 'Thêm sản phẩm', ['admin', 'owner']],
        ['quan-ly-nhanh.php', 'Quản lý nhanh', $casNavAll], ['giay-goi.php', 'Màu giấy gói', $casNavAll],
        ['phan-loai-bo-loc.php', 'Phân loại & bộ lọc', $casNavAll], ['kham-pha.php', 'Danh mục khám phá', $casNavAll],
    ],
    'Kho' => [
        ['kho-tong.php', 'Kho tổng', $casNavAll], ['cong-thuc-hoa.php', 'Công thức nguyên liệu', ['admin', 'owner']], ['kho-hoa.php', 'Kho Hoa', $casNavAll],
        ['kho-phu-kien.php', 'Kho Phụ kiện', $casNavAll], ['lich-su-kho.php', 'Lịch sử kho', $casNavAll],
    ],
    'Hệ thống' => [
        ['nhan-vien.php', 'Nhân viên & quyền', ['admin', 'owner']], ['cai-dat.php', 'Cài đặt', ['admin', 'owner']],
        ['tai-khoan.php', 'Tài khoản', $casNavAll], ['chu-so-huu.php', 'Chủ sở hữu', ['owner']],
    ],
];
$casNavNow = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<style>.cas-admin-nav-title{margin:14px 13px 4px;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;opacity:.65;font-weight:700}</style>
<nav class="cas-admin-nav px-2 pb-4" aria-label="Điều hướng quản trị">
<?php foreach ($casNavGroups as $casNavTitle => $casNavItems):
    $casNavItems = array_filter($casNavItems, static fn($i) => in_array($casNavRole, $i[2], true));
    if (!$casNavItems) continue; ?>
  <?php if ($casNavTitle !== ''): ?><p class="cas-admin-nav-title"><?= cas_e($casNavTitle) ?></p><?php endif; ?>
  <?php foreach ($casNavItems as [$casNavUrl, $casNavLabel]): ?>
  <a href="<?= cas_e($casNavUrl) ?>" class="<?= $casNavNow === $casNavUrl ? 'active' : '' ?>"><?= cas_e($casNavLabel) ?></a>
  <?php endforeach; ?>
<?php endforeach; ?>
</nav>
