<?php
declare(strict_types=1);
/**
 * ADM-02 — Sản phẩm, hình ảnh, giấy gói theo sản phẩm, quản lý nhanh và phân loại.
 * Giữ nguyên tên action / field cũ (product_save, quick_save, taxonomy_save) để kiểm thử CI của nhóm vẫn chạy.
 */

const CAS_PRODUCT_TYPES = ['bouquet' => 'Bó hoa', 'basket' => 'Giỏ hoa'];
const CAS_PRODUCT_STATUS = ['active' => 'Đang bán', 'hidden' => 'Ẩn khỏi cửa hàng'];
const CAS_TAXONOMIES = [
    'categories' => ['category_id', 'Loại hoa'],
    'tones' => ['tone_id', 'Tông màu'],
    'occasions' => ['occasion_id', 'Dịp tặng'],
];
const CAS_PRODUCT_MAX_IMAGES = 8;

function cas_library_images(): array
{
    $files = [];
    foreach (glob(__DIR__ . '/../../assets/images/*.png') ?: [] as $path) {
        $files[] = basename($path);
    }
    sort($files);
    return $files;
}

/** Đồng bộ products.image với ảnh đại diện (storefront hiện dùng cột này). */
function cas_sync_primary(PDO $db, int $productId): void
{
    $images = cas_rows('SELECT image_id, path, is_primary FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order, image_id', [$productId]);
    if (!$images) {
        return;
    }
    if ((int)$images[0]['is_primary'] !== 1) {
        $db->prepare('UPDATE product_images SET is_primary=(image_id=?) WHERE product_id=?')->execute([$images[0]['image_id'], $productId]);
    }
    $db->prepare('UPDATE products SET image=? WHERE product_id=?')->execute([$images[0]['path'], $productId]);
}

function cas_product_save(PDO $db, array $user, bool $edit): array
{
    cas_require_permission('catalog.edit', 'product_save');
    $id = $edit ? cas_in_id('id') : 0;
    $current = null;
    if ($edit) {
        $current = cas_rows('SELECT * FROM products WHERE product_id=?', [$id])[0] ?? null;
        if (!$current) {
            throw new DomainException('Không tìm thấy sản phẩm #' . $id . ' để cập nhật.');
        }
    }
    $name = cas_in_str('name', 2, 180, 'Tên sản phẩm');
    $sku = strtoupper(cas_in_str('sku', 3, 50, 'SKU'));
    if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{2,49}$/', $sku)) {
        throw new DomainException('SKU chỉ gồm chữ không dấu, số, "-" hoặc "_" (3–50 ký tự).');
    }
    $slugInput = strtolower(trim((string)($_POST['slug'] ?? '')));
    $slug = $slugInput !== '' ? $slugInput : cas_slugify($name);
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || strlen($slug) > 190) {
        throw new DomainException('Slug chỉ gồm chữ thường không dấu, số và dấu "-".');
    }
    $price = cas_in_int('price', 0, 100000000, 'Giá bán');
    $type = cas_in_choice('product_type', array_keys(CAS_PRODUCT_TYPES), 'Kiểu sản phẩm');
    $status = cas_in_choice('status', array_keys(CAS_PRODUCT_STATUS), 'Trạng thái');
    $description = cas_in_str('description', 0, 2000, 'Mô tả');
    $composition = cas_in_str('composition', 0, 1000, 'Thành phần');
    $taxonomy = [];
    foreach (CAS_TAXONOMIES as $table => [$pk, $label]) {
        $value = filter_var($_POST[$pk] ?? null, FILTER_VALIDATE_INT);
        $value = $value !== false && $value > 0 ? $value : null;
        if ($value !== null && !cas_value("SELECT COUNT(*) FROM {$table} WHERE {$pk}=?", [$value])) {
            throw new DomainException($label . ' đã chọn không tồn tại.');
        }
        $taxonomy[$pk] = $value;
    }
    if ((int)cas_value('SELECT COUNT(*) FROM products WHERE UPPER(sku)=? AND product_id<>?', [$sku, $id])) {
        throw new DomainException('SKU "' . $sku . '" đã được dùng cho sản phẩm khác.');
    }
    if ((int)cas_value('SELECT COUNT(*) FROM products WHERE slug=? AND product_id<>?', [$slug, $id])) {
        if ($slugInput !== '') {
            throw new DomainException('Slug "' . $slug . '" đã được dùng cho sản phẩm khác.');
        }
        $base = $slug;
        for ($n = 2; (int)cas_value('SELECT COUNT(*) FROM products WHERE slug=? AND product_id<>?', [$slug, $id]); $n++) {
            $slug = $base . '-' . $n;
        }
    }
    $library = trim((string)($_POST['image'] ?? ''));
    if ($library !== '' && !in_array(basename($library), cas_library_images(), true)) {
        throw new DomainException('Ảnh thư viện không hợp lệ.');
    }
    $libraryPath = $library !== '' ? 'assets/images/' . basename($library) : null;
    $wrapping = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['wrapping'] ?? [])), static fn($v) => $v > 0)));
    if ($wrapping && (int)cas_value('SELECT COUNT(*) FROM wrapping_colors WHERE color_id IN (' . implode(',', $wrapping) . ')') !== count($wrapping)) {
        throw new DomainException('Màu giấy gói đã chọn không tồn tại.');
    }
    $existingImages = $edit ? (int)cas_value('SELECT COUNT(*) FROM product_images WHERE product_id=?', [$id]) : 0;
    $libraryIsNew = $libraryPath !== null && !($edit && cas_value('SELECT COUNT(*) FROM product_images WHERE product_id=? AND path=?', [$id, $libraryPath]));
    $uploads = cas_uploaded_files('images');
    if ($existingImages + count($uploads) + ($libraryIsNew ? 1 : 0) > CAS_PRODUCT_MAX_IMAGES) {
        throw new DomainException('Mỗi sản phẩm tối đa ' . CAS_PRODUCT_MAX_IMAGES . ' ảnh (hiện có ' . $existingImages . ').');
    }
    if (!$edit && !$uploads && !$libraryPath) {
        throw new DomainException('Vui lòng chọn ảnh thư viện hoặc tải lên ít nhất một ảnh.');
    }
    // Lưu file trước (đã kiểm MIME / dung lượng); nếu ghi CSDL lỗi thì xoá file vừa lưu.
    $stored = [];
    try {
        foreach ($uploads as $file) {
            $stored[] = cas_store_image($file, 'products', 2097152);
        }
        $db->beginTransaction();
        $values = [$taxonomy['category_id'], $taxonomy['tone_id'], $taxonomy['occasion_id'], $sku, $slug, $name, $type, $price,
                   $description, $composition, isset($_POST['featured']) ? 1 : 0, isset($_POST['today']) ? 1 : 0, $status];
        if ($edit) {
            $db->prepare('UPDATE products SET category_id=?,tone_id=?,occasion_id=?,sku=?,slug=?,name=?,product_type=?,price=?,description=?,composition=?,featured=?,today=?,status=? WHERE product_id=?')
               ->execute([...$values, $id]);
        } else {
            $db->prepare('INSERT INTO products(category_id,tone_id,occasion_id,sku,slug,name,product_type,price,description,composition,featured,today,status,image) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
               ->execute([...$values, $libraryPath ?? $stored[0]]);
            $id = (int)$db->lastInsertId();
        }
        $sort = (int)cas_value('SELECT COALESCE(MAX(sort_order),0) FROM product_images WHERE product_id=?', [$id]);
        $addImage = $db->prepare('INSERT INTO product_images(product_id,path,alt,sort_order,is_primary) VALUES(?,?,?,?,0)');
        if ($libraryPath && !cas_value('SELECT COUNT(*) FROM product_images WHERE product_id=? AND path=?', [$id, $libraryPath])) {
            $addImage->execute([$id, $libraryPath, $name, ++$sort]);
        }
        if ($libraryPath) {
            // Ảnh thư viện được chọn trở thành ảnh đại diện (giữ hành vi của form cũ)
            $db->prepare('UPDATE product_images SET is_primary=(path=?) WHERE product_id=?')->execute([$libraryPath, $id]);
        }
        foreach ($stored as $path) {
            $addImage->execute([$id, $path, $name, ++$sort]);
        }
        cas_sync_primary($db, $id);
        $db->prepare('DELETE FROM product_wrapping WHERE product_id=?')->execute([$id]);
        $link = $db->prepare('INSERT INTO product_wrapping(product_id,color_id) VALUES(?,?)');
        foreach ($wrapping as $colorId) {
            $link->execute([$id, $colorId]);
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        foreach ($stored as $path) {
            cas_delete_upload($path);
        }
        throw $e;
    }
    cas_audit($edit ? 'product_update' : 'product_create', 'product', $id, [
        'sku' => $sku, 'price' => $price, 'status' => $status,
        'price_before' => $current ? (float)$current['price'] : null, 'status_before' => $current['status'] ?? null,
        'uploaded_images' => count($stored), 'wrapping' => $wrapping,
    ]);
    return [($edit ? 'Đã cập nhật' : 'Đã thêm') . ' sản phẩm "' . $name . '".', $edit ? 'sua-san-pham.php?id=' . $id : 'sua-san-pham.php?id=' . $id . '&created=1'];
}

function cas_product_toggle(PDO $db): string
{
    cas_require_permission('catalog.edit', 'product_toggle');
    $id = cas_in_id('id');
    $p = cas_rows('SELECT name, status FROM products WHERE product_id=?', [$id])[0] ?? null;
    if (!$p) {
        throw new DomainException('Không tìm thấy sản phẩm.');
    }
    $to = $p['status'] === 'active' ? 'hidden' : 'active';
    $db->prepare('UPDATE products SET status=? WHERE product_id=?')->execute([$to, $id]);
    cas_audit('product_' . ($to === 'active' ? 'show' : 'hide'), 'product', $id, ['from' => $p['status'], 'to' => $to]);
    return ($to === 'active' ? 'Đã hiện lại' : 'Đã ẩn') . ' sản phẩm "' . $p['name'] . '" trên cửa hàng.';
}

/** Xoá an toàn: sản phẩm đã có trong đơn hàng chỉ được ẩn, không xoá (giữ toàn vẹn order_items / báo cáo). */
function cas_product_delete(PDO $db): string
{
    cas_require_permission('catalog.edit', 'product_delete');
    $id = cas_in_id('id');
    if (($_POST['confirm'] ?? '') !== 'yes') {
        throw new DomainException('Cần đánh dấu xác nhận trước khi xoá sản phẩm.');
    }
    $p = cas_rows('SELECT name, sku FROM products WHERE product_id=?', [$id])[0] ?? null;
    if (!$p) {
        throw new DomainException('Không tìm thấy sản phẩm.');
    }
    $orders = (int)cas_value('SELECT COUNT(DISTINCT order_id) FROM order_items WHERE product_id=?', [$id]);
    if ($orders > 0) {
        cas_audit('product_delete', 'product', $id, ['orders' => $orders], 'rejected');
        throw new DomainException('Sản phẩm "' . $p['name'] . '" đã có trong ' . $orders . ' đơn hàng nên không thể xoá. Hãy chuyển sang "Ẩn khỏi cửa hàng".');
    }
    $paths = array_column(cas_rows('SELECT path FROM product_images WHERE product_id=?', [$id]), 'path');
    $db->beginTransaction();
    $db->prepare('DELETE FROM product_wrapping WHERE product_id=?')->execute([$id]);
    $db->prepare('DELETE FROM product_images WHERE product_id=?')->execute([$id]);
    $db->prepare('DELETE FROM products WHERE product_id=?')->execute([$id]);
    $db->commit();
    foreach ($paths as $path) {
        cas_delete_upload($path);
    }
    cas_audit('product_delete', 'product', $id, ['sku' => $p['sku'], 'name' => $p['name']]);
    return 'Đã xoá sản phẩm "' . $p['name'] . '" (chưa phát sinh đơn hàng).';
}

function cas_product_image_action(PDO $db, string $op): array
{
    cas_require_permission('catalog.edit', 'product_image_' . $op);
    $imageId = cas_in_id('image_id');
    $img = cas_rows('SELECT * FROM product_images WHERE image_id=?', [$imageId])[0] ?? null;
    if (!$img) {
        throw new DomainException('Không tìm thấy ảnh.');
    }
    $pid = (int)$img['product_id'];
    $db->beginTransaction();
    if ($op === 'primary') {
        $db->prepare('UPDATE product_images SET is_primary=(image_id=?) WHERE product_id=?')->execute([$imageId, $pid]);
        $message = 'Đã đặt ảnh đại diện mới.';
    } elseif ($op === 'up' || $op === 'down') {
        $list = cas_rows('SELECT image_id FROM product_images WHERE product_id=? ORDER BY sort_order, image_id', [$pid]);
        $ids = array_map('intval', array_column($list, 'image_id'));
        $pos = array_search($imageId, $ids, true);
        $swap = $op === 'up' ? $pos - 1 : $pos + 1;
        if (isset($ids[$swap])) {
            [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
        }
        $set = $db->prepare('UPDATE product_images SET sort_order=? WHERE image_id=?');
        foreach ($ids as $i => $iid) {
            $set->execute([$i + 1, $iid]);
        }
        $message = 'Đã đổi thứ tự ảnh.';
    } else {
        if ((int)cas_value('SELECT COUNT(*) FROM product_images WHERE product_id=?', [$pid]) <= 1) {
            $db->rollBack();
            throw new DomainException('Sản phẩm cần ít nhất một ảnh, không thể xoá ảnh cuối cùng.');
        }
        $db->prepare('DELETE FROM product_images WHERE image_id=?')->execute([$imageId]);
        $message = 'Đã xoá ảnh.';
    }
    cas_sync_primary($db, $pid);
    $db->commit();
    if ($op === 'delete' && !cas_value('SELECT COUNT(*) FROM product_images WHERE path=?', [$img['path']])) {
        cas_delete_upload($img['path']);
    }
    cas_audit('product_image_' . $op, 'product', $pid, ['image_id' => $imageId, 'path' => $img['path']]);
    return [$message, 'sua-san-pham.php?id=' . $pid . '#gallery'];
}

function cas_quick_save(PDO $db): string
{
    cas_require_permission('catalog.edit', 'quick_save');
    $id = cas_in_id('id');
    $price = cas_in_int('price', 0, 100000000, 'Giá bán');
    $status = cas_in_choice('status', array_keys(CAS_PRODUCT_STATUS), 'Trạng thái');
    $p = cas_rows('SELECT name, price, status FROM products WHERE product_id=?', [$id])[0] ?? null;
    if (!$p) {
        throw new DomainException('Không tìm thấy sản phẩm.');
    }
    if ((int)$p['price'] === $price && $p['status'] === $status) {
        throw new DomainException('Giá và trạng thái của "' . $p['name'] . '" không thay đổi.');
    }
    $db->prepare('UPDATE products SET price=?, status=? WHERE product_id=?')->execute([$price, $status, $id]);
    cas_audit('product_quick_update', 'product', $id, ['price_before' => (float)$p['price'], 'price_after' => $price, 'status_before' => $p['status'], 'status_after' => $status]);
    return '"' . $p['name'] . '": ' . cas_money($p['price']) . ' → ' . cas_money($price) . ', ' . CAS_PRODUCT_STATUS[$status] . '.';
}

// ------------------------------------------------------------------ phân loại
function cas_taxonomy_input(): array
{
    $table = cas_in_choice('table', array_keys(CAS_TAXONOMIES), 'Nhóm phân loại');
    return [$table, CAS_TAXONOMIES[$table][0], CAS_TAXONOMIES[$table][1]];
}

function cas_taxonomy_save(PDO $db): string
{
    cas_require_permission('catalog.edit', 'taxonomy_save');
    [$table, $pk, $label] = cas_taxonomy_input();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $name = cas_in_str('name', 2, 100, 'Tên ' . mb_strtolower($label));
    if ((int)cas_value("SELECT COUNT(*) FROM {$table} WHERE LOWER(name)=LOWER(?) AND {$pk}<>?", [$name, $id])) {
        throw new DomainException($label . ' "' . $name . '" đã tồn tại.');
    }
    $old = $id ? (cas_rows("SELECT * FROM {$table} WHERE {$pk}=?", [$id])[0] ?? null) : null;
    if ($id && !$old) {
        throw new DomainException('Không tìm thấy ' . mb_strtolower($label) . '.');
    }
    $hex = null;
    if ($table === 'tones') {
        // Không gửi mã màu khi sửa (vd chỉ bật / tắt) thì giữ màu cũ.
        $hex = strtoupper(trim((string)($_POST['hex_code'] ?? ($old['hex_code'] ?? '#CCCCCC'))));
        if (!preg_match('/^#[0-9A-F]{6}$/', $hex)) {
            throw new DomainException('Mã màu phải có dạng #RRGGBB.');
        }
    }
    if ($id) {
        $status = cas_in_choice('status', ['active', 'inactive'], 'Trạng thái');
        $db->prepare("UPDATE {$table} SET name=?, status=?" . ($table === 'tones' ? ', hex_code=?' : '') . " WHERE {$pk}=?")
           ->execute($table === 'tones' ? [$name, $status, $hex, $id] : [$name, $status, $id]);
        cas_audit('taxonomy_update', $table, $id, ['before' => $old['name'] . '/' . $old['status'], 'after' => $name . '/' . $status]);
        return 'Đã cập nhật ' . mb_strtolower($label) . ' "' . $name . '"' . ($status === 'inactive' ? ' (ngừng dùng — sản phẩm đang gán vẫn giữ liên kết).' : '.');
    }
    if ($table === 'categories') {
        $slug = cas_slugify($name) ?: 'loai-hoa';
        for ($base = $slug, $n = 2; (int)cas_value('SELECT COUNT(*) FROM categories WHERE slug=?', [$slug]); $n++) {
            $slug = $base . '-' . $n;
        }
        $db->prepare('INSERT INTO categories(name,slug) VALUES(?,?)')->execute([$name, $slug]);
    } elseif ($table === 'tones') {
        $db->prepare('INSERT INTO tones(name,hex_code) VALUES(?,?)')->execute([$name, $hex]);
    } else {
        $db->prepare('INSERT INTO occasions(name) VALUES(?)')->execute([$name]);
    }
    cas_audit('taxonomy_create', $table, $db->lastInsertId(), ['name' => $name]);
    return 'Đã thêm ' . mb_strtolower($label) . ' "' . $name . '".';
}

function cas_taxonomy_delete(PDO $db): string
{
    cas_require_permission('catalog.edit', 'taxonomy_delete');
    [$table, $pk, $label] = cas_taxonomy_input();
    $id = cas_in_id('id');
    $row = cas_rows("SELECT name FROM {$table} WHERE {$pk}=?", [$id])[0] ?? null;
    if (!$row) {
        throw new DomainException('Không tìm thấy ' . mb_strtolower($label) . '.');
    }
    $used = (int)cas_value("SELECT COUNT(*) FROM products WHERE {$pk}=?", [$id]);
    $filterType = ['categories' => 'category', 'tones' => 'tone', 'occasions' => 'occasion'][$table];
    $groups = (int)cas_value('SELECT COUNT(*) FROM discovery_groups WHERE filter_type=? AND filter_id=?', [$filterType, $id]);
    if ($used + $groups > 0) {
        cas_audit('taxonomy_delete', $table, $id, ['products' => $used, 'discovery' => $groups], 'rejected');
        throw new DomainException($label . ' "' . $row['name'] . '" đang được dùng bởi ' . $used . ' sản phẩm và ' . $groups . ' nhóm khám phá. Hãy chuyển "Ngừng dùng" thay vì xoá.');
    }
    $db->prepare("DELETE FROM {$table} WHERE {$pk}=?")->execute([$id]);
    cas_audit('taxonomy_delete', $table, $id, ['name' => $row['name']]);
    return 'Đã xoá ' . mb_strtolower($label) . ' "' . $row['name'] . '".';
}

// ------------------------------------------------------------------ trang
function cas_products_list_page(): void
{
    global $db, $user;
    cas_ops_post(['product_toggle' => static fn() => cas_product_toggle($db), 'product_delete' => static fn() => cas_product_delete($db)]);
    $search = trim((string)($_GET['q'] ?? ''));
    $status = (string)($_GET['status'] ?? '');
    $category = filter_var($_GET['category'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $where = [];
    $args = [];
    if ($search !== '') {
        $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.slug LIKE ?)';
        array_push($args, "%$search%", "%$search%", "%$search%");
    }
    if (isset(CAS_PRODUCT_STATUS[$status])) {
        $where[] = 'p.status=?';
        $args[] = $status;
    }
    if ($category) {
        $where[] = 'p.category_id=?';
        $args[] = $category;
    }
    $rows = cas_rows('SELECT p.*, c.name category_name, t.name tone_name, o.name occasion_name,
            (SELECT COUNT(*) FROM product_images i WHERE i.product_id=p.product_id) images,
            (SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi WHERE oi.product_id=p.product_id) orders
            FROM products p LEFT JOIN categories c ON c.category_id=p.category_id LEFT JOIN tones t ON t.tone_id=p.tone_id
            LEFT JOIN occasions o ON o.occasion_id=p.occasion_id' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY p.product_id DESC LIMIT 300', $args);
    $categories = cas_rows('SELECT category_id, name FROM categories ORDER BY name');
    $canEdit = cas_can($user, 'catalog.edit');

    cas_ops_start('Sản phẩm', 'Danh sách mẫu hoa trong MySQL: tìm kiếm, lọc, ẩn / hiện và xoá an toàn.', 'bi-flower2');
    ?>
<section class="admin-card admin-section">
  <form method="get" class="cas-admin-stack mb-3">
    <input class="admin-input" name="q" value="<?= cas_e($search) ?>" placeholder="Tên, SKU hoặc slug">
    <?= cas_select('status', ['' => 'Mọi trạng thái'] + CAS_PRODUCT_STATUS, $status) ?>
    <select class="admin-select" name="category"><option value="">Mọi loại hoa</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= $category === (int)$c['category_id'] ? 'selected' : '' ?>><?= cas_e($c['name']) ?></option><?php endforeach; ?></select>
    <button class="admin-btn-light" type="submit">Tìm kiếm</button><span class="admin-badge"><?= count($rows) ?> sản phẩm</span>
    <?php if ($canEdit): ?><a class="admin-btn" href="them-san-pham.php">Thêm sản phẩm</a><?php endif; ?>
  </form>
  <div class="admin-table-wrap"><table class="admin-table">
    <thead><tr><th>Ảnh</th><th>Sản phẩm</th><th>Phân loại</th><th>Giá</th><th>Hiển thị</th><th>Ảnh / đơn</th><?php if ($canEdit): ?><th>Thao tác</th><?php endif; ?></tr></thead><tbody>
    <?php foreach ($rows as $p): ?>
    <tr><td><img src="../<?= cas_e($p['image']) ?>" width="64" height="64" style="object-fit:cover;border-radius:10px" alt=""></td>
      <td><strong><?= cas_e($p['name']) ?></strong><div class="cas-muted"><?= cas_e($p['sku']) ?> · /<?= cas_e($p['slug']) ?></div></td>
      <td><?= cas_e($p['category_name'] ?? '—') ?><div class="cas-muted"><?= cas_e(CAS_PRODUCT_TYPES[$p['product_type']] ?? $p['product_type']) ?> · <?= cas_e($p['tone_name'] ?? '—') ?> · <?= cas_e($p['occasion_name'] ?? '—') ?></div></td>
      <td><strong><?= cas_money($p['price']) ?></strong></td>
      <td><?= cas_status_badge(CAS_PRODUCT_STATUS[$p['status']] ?? $p['status'], $p['status'] === 'active' ? 'success' : '') ?></td>
      <td><?= (int)$p['images'] ?> ảnh<div class="cas-muted"><?= (int)$p['orders'] ?> đơn</div></td>
      <?php if ($canEdit): ?><td><div class="cas-admin-table-form">
        <a class="admin-btn-light" href="sua-san-pham.php?id=<?= (int)$p['product_id'] ?>">Sửa</a>
        <form method="post"><?= cas_csrf_form() ?><input type="hidden" name="action" value="product_toggle"><input type="hidden" name="id" value="<?= (int)$p['product_id'] ?>"><button class="admin-btn-light" type="submit"><?= $p['status'] === 'active' ? 'Ẩn' : 'Hiện' ?></button></form>
        <form method="post" data-confirm="Xoá vĩnh viễn sản phẩm này? Sản phẩm đã có đơn hàng sẽ bị từ chối."><?= cas_csrf_form() ?><input type="hidden" name="action" value="product_delete"><input type="hidden" name="confirm" value="yes"><input type="hidden" name="id" value="<?= (int)$p['product_id'] ?>"><button class="admin-btn-light text-danger" type="submit">Xoá</button></form>
      </div></td><?php endif; ?></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7"><div class="admin-empty">Không có sản phẩm phù hợp.</div></td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php
    cas_ops_end();
}

function cas_product_form_page(bool $edit): void
{
    global $db, $user;
    cas_page_roles(['admin', 'owner'], $edit ? 'Sửa sản phẩm' : 'Thêm sản phẩm');
    cas_ops_post([
        'product_save' => static fn() => cas_product_save($db, $user, $edit),
        'image_primary' => static fn() => cas_product_image_action($db, 'primary'),
        'image_up' => static fn() => cas_product_image_action($db, 'up'),
        'image_down' => static fn() => cas_product_image_action($db, 'down'),
        'image_delete' => static fn() => cas_product_image_action($db, 'delete'),
    ]);
    $product = [];
    $images = [];
    $assigned = [];
    if ($edit) {
        $id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $product = $id ? (cas_rows('SELECT * FROM products WHERE product_id=?', [$id])[0] ?? []) : [];
        if (!$product) {
            http_response_code(404);
            cas_ops_start('Sửa sản phẩm', 'Không tìm thấy sản phẩm.', 'bi-pencil-square');
            echo '<section class="admin-card admin-section"><p>Mã sản phẩm không hợp lệ hoặc sản phẩm đã bị xoá.</p><a class="admin-btn-light" href="san-pham.php">Về danh sách</a></section>';
            cas_ops_end();
            return;
        }
        $images = cas_rows('SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order, image_id', [$id]);
        $assigned = array_map('intval', array_column(cas_rows('SELECT color_id FROM product_wrapping WHERE product_id=?', [$id]), 'color_id'));
    }
    if (cas_post() && $GLOBALS['cas_error'] !== '') {
        $product = array_merge($product, array_intersect_key($_POST, array_flip(['name', 'sku', 'slug', 'price', 'product_type', 'status', 'description', 'composition', 'category_id', 'tone_id', 'occasion_id'])));
        $assigned = array_map('intval', (array)($_POST['wrapping'] ?? []));
    }
    $options = [];
    foreach (CAS_TAXONOMIES as $table => [$pk, $label]) {
        $current = (int)($product[$pk] ?? 0);
        $options[$pk] = cas_rows("SELECT {$pk} id, name, status FROM {$table} WHERE status='active' OR {$pk}=? ORDER BY name", [$current]);
    }
    $wrappings = cas_rows('SELECT * FROM wrapping_colors ORDER BY sort_order, color_id');
    $library = cas_library_images();

    cas_ops_start($edit ? 'Sửa sản phẩm' : 'Thêm sản phẩm', $edit ? 'Cập nhật thông tin bán hàng, phân loại, ảnh và giấy gói của mẫu hoa.' : 'Nhập thông tin bán hàng, phân loại, ảnh và giấy gói cho mẫu hoa mới.', 'bi-pencil-square');
    ?>
<section class="admin-card admin-section">
<form method="post" enctype="multipart/form-data" class="admin-form-grid">
  <?= cas_csrf_form() ?><input type="hidden" name="action" value="product_save"><?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$product['product_id'] ?>"><?php endif; ?>
  <div><label class="admin-label" for="pName">Tên sản phẩm</label><input class="admin-input" id="pName" name="name" maxlength="180" required value="<?= cas_e((string)($product['name'] ?? '')) ?>"></div>
  <div><label class="admin-label" for="pSku">SKU</label><input class="admin-input" id="pSku" name="sku" maxlength="50" required pattern="[A-Za-z0-9][A-Za-z0-9_\-]{2,49}" value="<?= cas_e((string)($product['sku'] ?? '')) ?>" placeholder="BH-TEN-MAU-400"></div>
  <div><label class="admin-label" for="pSlug">Slug (để trống sẽ tạo từ tên)</label><input class="admin-input" id="pSlug" name="slug" maxlength="190" value="<?= cas_e((string)($product['slug'] ?? '')) ?>"></div>
  <div><label class="admin-label" for="pPrice">Giá bán (VND)</label><input class="admin-input" id="pPrice" name="price" type="number" min="0" max="100000000" step="1000" required value="<?= cas_e(isset($product['price']) ? (string)(int)$product['price'] : '') ?>"></div>
  <div><label class="admin-label">Kiểu sản phẩm</label><?= cas_select('product_type', CAS_PRODUCT_TYPES, (string)($product['product_type'] ?? 'bouquet')) ?></div>
  <div><label class="admin-label">Trạng thái</label><?= cas_select('status', CAS_PRODUCT_STATUS, (string)($product['status'] ?? 'active')) ?></div>
  <?php foreach (CAS_TAXONOMIES as $table => [$pk, $label]): ?>
  <div><label class="admin-label"><?= cas_e($label) ?></label><select class="admin-select" name="<?= $pk ?>"><option value="">Không chọn</option>
    <?php foreach ($options[$pk] as $o): ?><option value="<?= (int)$o['id'] ?>" <?= (int)($product[$pk] ?? 0) === (int)$o['id'] ? 'selected' : '' ?>><?= cas_e($o['name']) ?><?= $o['status'] !== 'active' ? ' (ngừng dùng)' : '' ?></option><?php endforeach; ?></select></div>
  <?php endforeach; ?>
  <div><label class="admin-label">Ảnh thư viện làm ảnh đại diện</label><select class="admin-select" name="image"><option value=""><?= $edit ? '— Giữ ảnh đại diện hiện tại —' : '— Không chọn —' ?></option>
    <?php foreach ($library as $file): ?><option value="<?= cas_e($file) ?>"><?= cas_e($file) ?></option><?php endforeach; ?></select></div>
  <div class="full"><label class="admin-label">Tải ảnh mới (PNG / JPG / WEBP, ≤ 2 MB / ảnh, tối đa <?= CAS_PRODUCT_MAX_IMAGES ?> ảnh)</label><input class="admin-input" type="file" name="images[]" accept="image/png,image/jpeg,image/webp" multiple></div>
  <div class="full"><label class="admin-label">Mô tả</label><textarea class="admin-textarea" name="description" rows="3" maxlength="2000"><?= cas_e((string)($product['description'] ?? '')) ?></textarea></div>
  <div class="full"><label class="admin-label">Thành phần</label><textarea class="admin-textarea" name="composition" rows="2" maxlength="1000"><?= cas_e((string)($product['composition'] ?? '')) ?></textarea></div>
  <div class="full"><label class="admin-label">Giấy gói cho phép chọn</label><div class="cas-admin-stack">
    <?php foreach ($wrappings as $w): ?><label class="admin-check"><input type="checkbox" name="wrapping[]" value="<?= (int)$w['color_id'] ?>" <?= in_array((int)$w['color_id'], $assigned, true) ? 'checked' : '' ?>>
      <span class="cas-swatch" style="background:<?= cas_e($w['hex_code']) ?>"></span> <?= cas_e($w['name']) ?><?= (float)$w['surcharge'] > 0 ? ' (+' . cas_money($w['surcharge']) . ')' : '' ?><?= $w['status'] !== 'active' ? ' — ngừng dùng' : '' ?></label><?php endforeach; ?>
    <?php if (!$wrappings): ?><span class="cas-muted">Chưa có màu giấy gói. <a href="giay-goi.php">Thêm tại Màu giấy gói</a>.</span><?php endif; ?></div></div>
  <div class="full cas-admin-stack"><label class="admin-check"><input type="checkbox" name="featured" <?= !empty($product['featured']) ? 'checked' : '' ?>> Sản phẩm nổi bật</label>
    <label class="admin-check"><input type="checkbox" name="today" <?= !empty($product['today']) ? 'checked' : '' ?>> Mẫu hôm nay</label></div>
  <div class="full"><button class="admin-btn" type="submit">Lưu sản phẩm</button> <a class="admin-btn-light" href="san-pham.php">Về danh sách</a>
    <?php if ($edit): ?> <a class="admin-btn-light" href="../chi-tiet-san-pham.php?slug=<?= cas_e($product['slug']) ?>" target="_blank" rel="noopener">Xem ở cửa hàng</a><?php endif; ?></div>
</form>
</section>
<?php if ($edit): ?>
<section class="admin-card admin-section" id="gallery">
  <h2 class="admin-card-title">Thư viện ảnh (<?= count($images) ?>)</h2>
  <p class="admin-card-subtitle">Ảnh đại diện hiển thị ở danh sách và đầu trang chi tiết; các ảnh còn lại hiển thị theo thứ tự bên dưới.</p>
  <div class="cas-gallery mt-3">
  <?php foreach ($images as $i => $img): ?>
    <figure><img src="../<?= cas_e($img['path']) ?>" alt="<?= cas_e($img['alt'] ?? '') ?>">
      <figcaption class="cas-muted mt-1"><?= (int)$img['is_primary'] ? cas_status_badge('Đại diện', 'success') : '#' . ($i + 1) ?></figcaption>
      <div class="cas-admin-table-form mt-1">
      <?php foreach (['image_primary' => '★', 'image_up' => '↑', 'image_down' => '↓', 'image_delete' => '✕'] as $act => $icon):
          if ($act === 'image_primary' && (int)$img['is_primary']) continue; ?>
        <form method="post" <?= $act === 'image_delete' ? 'data-confirm="Xoá ảnh này?"' : '' ?>><?= cas_csrf_form() ?><input type="hidden" name="action" value="<?= $act ?>"><input type="hidden" name="id" value="<?= (int)$product['product_id'] ?>"><input type="hidden" name="image_id" value="<?= (int)$img['image_id'] ?>"><button class="admin-btn-light" type="submit" title="<?= cas_e(['image_primary' => 'Đặt làm đại diện', 'image_up' => 'Lên', 'image_down' => 'Xuống', 'image_delete' => 'Xoá'][$act]) ?>"><?= $icon ?></button></form>
      <?php endforeach; ?></div></figure>
  <?php endforeach; ?>
  </div>
</section>
<?php endif;
    cas_ops_end();
}

function cas_quick_page(): void
{
    global $db, $user;
    cas_ops_post(['quick_save' => static fn() => cas_quick_save($db)]);
    $rows = cas_rows('SELECT p.product_id, p.name, p.sku, p.price, p.status, p.image, c.name category_name FROM products p LEFT JOIN categories c ON c.category_id=p.category_id ORDER BY p.product_id DESC LIMIT 300');
    $changes = cas_rows("SELECT a.created_at, a.detail, a.entity_id, u.full_name FROM admin_audit_log a LEFT JOIN users u ON u.user_id=a.user_id
                         WHERE a.action='product_quick_update' AND a.outcome='ok' ORDER BY a.log_id DESC LIMIT 6");
    $canEdit = cas_can($user, 'catalog.edit');
    cas_ops_start('Quản lý nhanh', 'Đổi giá bán và trạng thái hiển thị ngay trên bảng; mỗi thay đổi được ghi nhật ký trước / sau.', 'bi-lightning-charge');
    ?>
<section class="admin-card admin-section">
  <?php if (!$canEdit): ?><p class="admin-card-subtitle">Tài khoản nhân viên chỉ được xem giá; liên hệ quản trị viên để thay đổi.</p><?php endif; ?>
  <div class="admin-table-wrap"><table class="admin-table">
    <thead><tr><th>Sản phẩm</th><th>Giá hiện tại</th><th>Trạng thái</th><?php if ($canEdit): ?><th>Giá mới / trạng thái</th><?php endif; ?></tr></thead><tbody>
    <?php foreach ($rows as $p): ?>
    <tr><td><strong><?= cas_e($p['name']) ?></strong><div class="cas-muted"><?= cas_e($p['sku']) ?> · <?= cas_e($p['category_name'] ?? '—') ?></div></td>
      <td><?= cas_money($p['price']) ?></td><td><?= cas_status_badge(CAS_PRODUCT_STATUS[$p['status']] ?? $p['status'], $p['status'] === 'active' ? 'success' : '') ?></td>
      <?php if ($canEdit): ?><td><form class="cas-admin-table-form" method="post"><?= cas_csrf_form() ?><input type="hidden" name="action" value="quick_save"><input type="hidden" name="id" value="<?= (int)$p['product_id'] ?>">
        <input class="admin-input" type="number" name="price" min="0" max="100000000" step="1000" value="<?= (int)$p['price'] ?>" required><?= cas_select('status', CAS_PRODUCT_STATUS, $p['status']) ?><button class="admin-btn-light" type="submit">Lưu</button></form></td><?php endif; ?></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</section>
<section class="admin-card admin-section"><h2 class="admin-card-title">Thay đổi gần đây</h2><div class="admin-list mt-3">
  <?php foreach ($changes as $c): $d = json_decode((string)$c['detail'], true) ?: []; ?>
  <div class="admin-list-item"><div><strong>Sản phẩm #<?= cas_e($c['entity_id']) ?></strong><small class="d-block mt-1"><?= cas_e($c['full_name'] ?? '—') ?> · <?= cas_e($c['created_at']) ?></small></div>
    <span><?= cas_money($d['price_before'] ?? 0) ?> → <?= cas_money($d['price_after'] ?? 0) ?> · <?= cas_e(($d['status_before'] ?? '') . ' → ' . ($d['status_after'] ?? '')) ?></span></div>
  <?php endforeach; ?><?php if (!$changes): ?><div class="admin-empty">Chưa có thay đổi.</div><?php endif; ?></div></section>
<?php
    cas_ops_end();
}

function cas_taxonomy_page(): void
{
    global $db, $user;
    cas_ops_post(['taxonomy_save' => static fn() => cas_taxonomy_save($db), 'taxonomy_delete' => static fn() => cas_taxonomy_delete($db)]);
    $canEdit = cas_can($user, 'catalog.edit');
    cas_ops_start('Phân loại & bộ lọc', 'Loại hoa, tông màu và dịp tặng dùng cho sản phẩm, bộ lọc cửa hàng và danh mục khám phá.', 'bi-tags');
    foreach (CAS_TAXONOMIES as $table => [$pk, $label]):
        $filterType = ['categories' => 'category', 'tones' => 'tone', 'occasions' => 'occasion'][$table];
        $rows = cas_rows("SELECT x.*, (SELECT COUNT(*) FROM products p WHERE p.{$pk}=x.{$pk}) products,
                          (SELECT COUNT(*) FROM discovery_groups g WHERE g.filter_type=? AND g.filter_id=x.{$pk}) groups_count
                          FROM {$table} x ORDER BY x.status, x.name", [$filterType]);
        ?>
<section class="admin-card admin-section">
  <div class="cas-admin-stack justify-content-between"><h2 class="admin-card-title mb-0"><?= cas_e($label) ?></h2><span class="admin-badge"><?= count($rows) ?> mục</span></div>
  <?php if ($canEdit): ?>
  <form method="post" class="cas-admin-stack my-3"><?= cas_csrf_form() ?><input type="hidden" name="action" value="taxonomy_save"><input type="hidden" name="table" value="<?= $table ?>">
    <input class="admin-input" name="name" maxlength="100" required placeholder="Tên <?= cas_e(mb_strtolower($label)) ?> mới"><?php if ($table === 'tones'): ?><input type="color" name="hex_code" value="#CCCCCC" title="Mã màu"><?php endif; ?><button class="admin-btn" type="submit">Thêm</button></form>
  <?php endif; ?>
  <div class="admin-table-wrap"><table class="admin-table">
    <thead><tr><th>Tên</th><?php if ($table === 'tones'): ?><th>Màu</th><?php endif; ?><th>Đang dùng</th><th>Trạng thái</th><?php if ($canEdit): ?><th>Sửa / xoá</th><?php endif; ?></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
    <tr><td><strong><?= cas_e($r['name']) ?></strong><?php if ($table === 'categories'): ?><div class="cas-muted">/<?= cas_e($r['slug']) ?></div><?php endif; ?></td>
      <?php if ($table === 'tones'): ?><td><span class="cas-swatch" style="background:<?= cas_e($r['hex_code'] ?: '#ccc') ?>"></span> <?= cas_e($r['hex_code']) ?></td><?php endif; ?>
      <td><?= (int)$r['products'] ?> sản phẩm · <?= (int)$r['groups_count'] ?> nhóm khám phá</td>
      <td><?= cas_status_badge($r['status'] === 'active' ? 'Đang dùng' : 'Ngừng dùng', $r['status'] === 'active' ? 'success' : '') ?></td>
      <?php if ($canEdit): ?><td><div class="cas-admin-table-form">
        <form method="post" class="cas-admin-table-form"><?= cas_csrf_form() ?><input type="hidden" name="action" value="taxonomy_save"><input type="hidden" name="table" value="<?= $table ?>"><input type="hidden" name="id" value="<?= (int)$r[$pk] ?>">
          <input class="admin-input" name="name" maxlength="100" value="<?= cas_e($r['name']) ?>" required><?php if ($table === 'tones'): ?><input type="color" name="hex_code" value="<?= cas_e($r['hex_code'] ?: '#CCCCCC') ?>"><?php endif; ?>
          <?= cas_select('status', ['active' => 'Đang dùng', 'inactive' => 'Ngừng dùng'], $r['status']) ?><button class="admin-btn-light" type="submit">Lưu</button></form>
        <form method="post" data-confirm="Xoá mục phân loại này? Chỉ xoá được khi chưa sản phẩm / nhóm khám phá nào dùng."><?= cas_csrf_form() ?><input type="hidden" name="action" value="taxonomy_delete"><input type="hidden" name="table" value="<?= $table ?>"><input type="hidden" name="id" value="<?= (int)$r[$pk] ?>"><button class="admin-btn-light text-danger" type="submit">Xoá</button></form>
      </div></td><?php endif; ?></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</section>
<?php endforeach;
    cas_ops_end();
}
