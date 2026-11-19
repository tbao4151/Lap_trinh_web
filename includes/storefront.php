<?php
declare(strict_types=1);
/**
 * Dữ liệu cửa hàng do Admin cấu hình (migration 003): cài đặt shop, danh mục khám phá, giấy gói, ảnh sản phẩm.
 * Chỉ đọc. Nếu CSDL chưa chạy migration 003, các hàm trả về giá trị mặc định để trang khách vẫn hoạt động.
 */
require_once __DIR__ . '/bootstrap.php';

const CAS_SETTING_DEFAULTS = [
    'shop_name' => "CÁ'S HOA",
    'hotline' => '0707309255',
    'email' => 'hello@cashoa.vn',
    'address' => '126/13 đường số 17, Linh Xuân, Thủ Đức, TP.HCM',
    'opening_hours' => '08:00 - 20:00, từ thứ Hai đến Chủ nhật',
    'announcement' => 'Có sẵn thiệp và túi · Shop xác nhận đơn sau khi nhận thông tin',
    'instagram' => 'https://www.instagram.com/',
    'zalo' => '0707309255',
    'default_shipping_fee' => '30000',
];

function cas_settings(): array
{
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }
    $settings = CAS_SETTING_DEFAULTS;
    try {
        foreach (cas_db()->query('SELECT setting_key, setting_value FROM shop_settings') as $row) {
            if (array_key_exists($row['setting_key'], $settings)) {
                $settings[$row['setting_key']] = (string)$row['setting_value'];
            }
        }
    } catch (PDOException $e) {
        // Chưa có bảng shop_settings: dùng mặc định.
    }
    return $settings;
}

function cas_setting(string $key): string
{
    return cas_settings()[$key] ?? '';
}

/** Đường dẫn trang sản phẩm đã lọc, dùng tên taxonomy (main-r2.js so khớp theo tên). */
function cas_discovery_href(array $group): string
{
    $map = ['occasion' => 'occasion', 'tone' => 'tone', 'category' => 'category'];
    if (isset($map[$group['filter_type']]) && ($group['filter_name'] ?? '') !== '') {
        return 'san-pham.php?' . $map[$group['filter_type']] . '=' . rawurlencode($group['filter_name']);
    }
    if ($group['filter_type'] === 'type' && in_array($group['filter_value'], ['bouquet', 'basket'], true)) {
        return 'san-pham.php?type=' . $group['filter_value'];
    }
    if ($group['filter_type'] === 'today') {
        return 'san-pham.php?today=1';
    }
    return 'san-pham.php';
}

/**
 * Nhóm khám phá đang hiển thị. $onlyValid=true bỏ các nhóm trỏ tới phân loại đã ngừng dùng hoặc không tồn tại.
 * Trả về null nếu bảng chưa tồn tại (để trang chủ dùng nội dung tĩnh cũ).
 */
function cas_discovery_groups(bool $onlyActive = true): ?array
{
    try {
        $sql = "SELECT g.*,
                  CASE g.filter_type WHEN 'occasion' THEN o.name WHEN 'tone' THEN t.name WHEN 'category' THEN c.name END AS filter_name,
                  CASE g.filter_type WHEN 'occasion' THEN o.status WHEN 'tone' THEN t.status WHEN 'category' THEN c.status ELSE 'active' END AS filter_status
                FROM discovery_groups g
                LEFT JOIN occasions o ON g.filter_type='occasion' AND o.occasion_id=g.filter_id
                LEFT JOIN tones t ON g.filter_type='tone' AND t.tone_id=g.filter_id
                LEFT JOIN categories c ON g.filter_type='category' AND c.category_id=g.filter_id"
            . ($onlyActive ? " WHERE g.status='active'" : '') . ' ORDER BY g.sort_order, g.group_id';
        $rows = cas_db()->query($sql)->fetchAll();
    } catch (PDOException $e) {
        return null;
    }
    $groups = [];
    foreach ($rows as $row) {
        $taxonomy = in_array($row['filter_type'], ['occasion', 'tone', 'category'], true);
        $row['valid'] = !$taxonomy || ($row['filter_name'] !== null && $row['filter_status'] === 'active');
        $row['href'] = cas_discovery_href($row);
        if ($onlyActive && !$row['valid']) {
            continue;
        }
        $groups[] = $row;
    }
    return $groups;
}

/** product_id => danh sách giấy gói đang dùng được gán cho sản phẩm. */
function cas_product_wrapping_map(): array
{
    try {
        $rows = cas_db()->query("SELECT pw.product_id, w.name, w.hex_code, w.surcharge
            FROM product_wrapping pw JOIN wrapping_colors w ON w.color_id=pw.color_id
            WHERE w.status='active' ORDER BY w.sort_order, w.color_id")->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
    $map = [];
    foreach ($rows as $row) {
        $map[(int)$row['product_id']][] = ['name' => $row['name'], 'hex' => $row['hex_code'], 'surcharge' => (int)round((float)$row['surcharge'])];
    }
    return $map;
}

/** product_id => danh sách đường dẫn ảnh, ảnh đại diện đứng đầu. */
function cas_product_gallery_map(): array
{
    try {
        $rows = cas_db()->query('SELECT product_id, path FROM product_images ORDER BY product_id, is_primary DESC, sort_order, image_id')->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
    $map = [];
    foreach ($rows as $row) {
        $map[(int)$row['product_id']][] = $row['path'];
    }
    return $map;
}

/**
 * Bộ lọc trang Sản phẩm lấy từ phân loại đang dùng (status active) của sản phẩm đang bán,
 * cùng khoảng giá thực tế. Trả về null khi lỗi CSDL để trang dùng danh sách tĩnh cũ.
 */
function cas_catalog_filters(): ?array
{
    try {
        $db = cas_db();
        $categories = $db->query("SELECT DISTINCT c.name FROM categories c JOIN products p ON p.category_id=c.category_id AND p.status='active'
            WHERE c.status='active' ORDER BY c.name")->fetchAll(PDO::FETCH_COLUMN);
        $tones = $db->query("SELECT DISTINCT t.name, t.hex_code FROM tones t JOIN products p ON p.tone_id=t.tone_id AND p.status='active'
            WHERE t.status='active' ORDER BY t.tone_id")->fetchAll();
        $max = (float)$db->query("SELECT COALESCE(MAX(price),0) FROM products WHERE status='active'")->fetchColumn();
    } catch (PDOException $e) {
        return null;
    }
    return ['categories' => $categories, 'tones' => $tones, 'max_price' => (int)max(500000, ceil($max / 10000) * 10000)];
}
