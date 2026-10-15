<?php
declare(strict_types=1);
/** ADM-05 / ADM-06 — Giấy gói, danh mục khám phá trang chủ và cài đặt cửa hàng (dữ liệu hiển thị ở storefront). */

const CAS_DISCOVERY_TYPES = ['occasion' => 'Theo dịp tặng', 'tone' => 'Theo tông màu', 'category' => 'Theo loại hoa', 'type' => 'Theo kiểu sản phẩm', 'today' => 'Mẫu hôm nay'];

// ------------------------------------------------------------------ Giấy gói
function cas_wrapping_save(PDO $db): string
{
    cas_require_permission('storefront.edit', 'wrapping_save');
    $id = filter_var($_POST['color_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $name = cas_in_str('name', 2, 80, 'Tên giấy gói');
    $hex = strtoupper(trim((string)($_POST['hex_code'] ?? '')));
    if (!preg_match('/^#[0-9A-F]{6}$/', $hex)) {
        throw new DomainException('Mã màu phải có dạng #RRGGBB.');
    }
    $surcharge = cas_in_int('surcharge', 0, 500000, 'Phụ thu');
    $sort = cas_in_int('sort_order', 0, 999, 'Thứ tự');
    $status = cas_in_choice('status', ['active', 'inactive'], 'Trạng thái');
    if ((int)cas_value('SELECT COUNT(*) FROM wrapping_colors WHERE LOWER(name)=LOWER(?) AND color_id<>?', [$name, $id])) {
        throw new DomainException('Đã có giấy gói tên "' . $name . '".');
    }
    $old = $id ? (cas_rows('SELECT * FROM wrapping_colors WHERE color_id=?', [$id])[0] ?? null) : null;
    if ($id && !$old) {
        throw new DomainException('Không tìm thấy giấy gói.');
    }
    $image = $old['image'] ?? null;
    $files = cas_uploaded_files('image');
    $newImage = $files ? cas_store_image($files[0], 'wrapping', 1048576) : null;
    if ($newImage || isset($_POST['remove_image'])) {
        $image = $newImage;
    }
    try {
        if ($id) {
            $db->prepare('UPDATE wrapping_colors SET name=?,hex_code=?,surcharge=?,sort_order=?,status=?,image=? WHERE color_id=?')
               ->execute([$name, $hex, $surcharge, $sort, $status, $image, $id]);
        } else {
            $db->prepare('INSERT INTO wrapping_colors(name,hex_code,surcharge,sort_order,status,image) VALUES(?,?,?,?,?,?)')
               ->execute([$name, $hex, $surcharge, $sort, $status, $image]);
            $id = (int)$db->lastInsertId();
        }
    } catch (Throwable $e) {
        cas_delete_upload($newImage);
        throw $e;
    }
    if ($old && $old['image'] !== $image) {
        cas_delete_upload($old['image']);
    }
    cas_audit($old ? 'wrapping_update' : 'wrapping_create', 'wrapping_color', $id,
        ['before' => $old ? ['surcharge' => (float)$old['surcharge'], 'status' => $old['status']] : null, 'after' => compact('name', 'hex', 'surcharge', 'status')]);
    return ($old ? 'Đã cập nhật' : 'Đã thêm') . ' giấy gói "' . $name . '"' . ($status === 'inactive' ? ' (ngừng dùng: không còn hiển thị cho khách).' : '.');
}

function cas_wrapping_assign(PDO $db): string
{
    cas_require_permission('storefront.edit', 'wrapping_assign');
    $id = cas_in_id('color_id');
    $color = cas_rows('SELECT name FROM wrapping_colors WHERE color_id=?', [$id])[0] ?? null;
    if (!$color) {
        throw new DomainException('Không tìm thấy giấy gói.');
    }
    $products = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['products'] ?? [])), static fn($v) => $v > 0)));
    if ($products && (int)cas_value('SELECT COUNT(*) FROM products WHERE product_id IN (' . implode(',', $products) . ')') !== count($products)) {
        throw new DomainException('Danh sách sản phẩm không hợp lệ.');
    }
    $db->beginTransaction();
    $db->prepare('DELETE FROM product_wrapping WHERE color_id=?')->execute([$id]);
    $ins = $db->prepare('INSERT INTO product_wrapping(product_id,color_id) VALUES(?,?)');
    foreach ($products as $pid) {
        $ins->execute([$pid, $id]);
    }
    $db->commit();
    cas_audit('wrapping_assign', 'wrapping_color', $id, ['products' => $products]);
    return 'Giấy gói "' . $color['name'] . '" áp dụng cho ' . count($products) . ' sản phẩm.';
}

function cas_wrapping_delete(PDO $db): string
{
    cas_require_permission('storefront.edit', 'wrapping_delete');
    $id = cas_in_id('color_id');
    $w = cas_rows('SELECT * FROM wrapping_colors WHERE color_id=?', [$id])[0] ?? null;
    if (!$w) {
        throw new DomainException('Không tìm thấy giấy gói.');
    }
    $used = (int)cas_value('SELECT COUNT(*) FROM product_wrapping WHERE color_id=?', [$id]);
    if ($used) {
        throw new DomainException('Giấy gói "' . $w['name'] . '" đang gán cho ' . $used . ' sản phẩm. Bỏ gán hoặc chuyển "Ngừng dùng" trước khi xoá.');
    }
    $db->prepare('DELETE FROM wrapping_colors WHERE color_id=?')->execute([$id]);
    cas_delete_upload($w['image']);
    cas_audit('wrapping_delete', 'wrapping_color', $id, ['name' => $w['name']]);
    return 'Đã xoá giấy gói "' . $w['name'] . '".';
}

function cas_wrapping_page(): void
{
    global $db, $user;
    cas_ops_post([
        'wrapping_save' => static fn() => cas_wrapping_save($db),
        'wrapping_assign' => static fn() => cas_wrapping_assign($db),
        'wrapping_delete' => static fn() => cas_wrapping_delete($db),
    ]);
    $rows = cas_rows('SELECT w.*, (SELECT COUNT(*) FROM product_wrapping pw WHERE pw.color_id=w.color_id) products FROM wrapping_colors w ORDER BY w.sort_order, w.color_id');
    $editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
    $edit = $editId ? (cas_rows('SELECT * FROM wrapping_colors WHERE color_id=?', [$editId])[0] ?? null) : null;
    $assignId = filter_var($_GET['assign'] ?? null, FILTER_VALIDATE_INT);
    $assign = $assignId ? (cas_rows('SELECT * FROM wrapping_colors WHERE color_id=?', [$assignId])[0] ?? null) : null;
    $assigned = $assign ? array_map('intval', array_column(cas_rows('SELECT product_id FROM product_wrapping WHERE color_id=?', [$assignId]), 'product_id')) : [];
    $products = $assign ? cas_rows('SELECT product_id, name, sku, status FROM products ORDER BY name') : [];
    $canEdit = cas_can($user, 'storefront.edit');

    cas_ops_start('Màu giấy gói', 'Giấy gói khách được chọn kèm phụ thu; giấy "ngừng dùng" không hiển thị ở trang khách hàng.', 'bi-palette');
    ?>
<section class="admin-grid-2 admin-section">
<article class="admin-card">
  <h2 class="admin-card-title">Danh sách giấy gói</h2>
  <div class="admin-table-wrap"><table class="admin-table">
    <thead><tr><th>Màu</th><th>Tên</th><th>Phụ thu</th><th>Sản phẩm</th><th>Trạng thái</th><?php if ($canEdit): ?><th></th><?php endif; ?></tr></thead><tbody>
    <?php foreach ($rows as $w): ?>
    <tr><td><?php if ($w['image']): ?><img src="../<?= cas_e($w['image']) ?>" width="40" height="40" style="object-fit:cover;border-radius:50%" alt=""><?php else: ?><span class="cas-swatch" style="width:34px;height:34px;background:<?= cas_e($w['hex_code']) ?>"></span><?php endif; ?></td>
      <td><strong><?= cas_e($w['name']) ?></strong><div class="cas-muted"><?= cas_e($w['hex_code']) ?> · thứ tự <?= (int)$w['sort_order'] ?></div></td>
      <td><?= (float)$w['surcharge'] > 0 ? '+' . cas_money($w['surcharge']) : 'Miễn phí' ?></td><td><?= (int)$w['products'] ?></td>
      <td><?= cas_status_badge($w['status'] === 'active' ? 'Đang dùng' : 'Ngừng dùng', $w['status'] === 'active' ? 'success' : '') ?></td>
      <?php if ($canEdit): ?><td><div class="cas-admin-table-form"><a class="admin-btn-light" href="?edit=<?= (int)$w['color_id'] ?>">Sửa</a><a class="admin-btn-light" href="?assign=<?= (int)$w['color_id'] ?>#assign">Gán SP</a>
        <form method="post" data-confirm="Xoá giấy gói này?"><?= cas_csrf_form() ?><input type="hidden" name="action" value="wrapping_delete"><input type="hidden" name="color_id" value="<?= (int)$w['color_id'] ?>"><button class="admin-btn-light text-danger" type="submit">Xoá</button></form></div></td><?php endif; ?></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6"><div class="admin-empty">Chưa có giấy gói.</div></td></tr><?php endif; ?>
    </tbody></table></div>
</article>
<article class="admin-card">
  <?php if ($canEdit): ?>
  <h2 class="admin-card-title"><?= $edit ? 'Sửa "' . cas_e($edit['name']) . '"' : 'Thêm giấy gói' ?></h2>
  <form method="post" enctype="multipart/form-data" class="admin-form-grid mt-3">
    <?= cas_csrf_form() ?><input type="hidden" name="action" value="wrapping_save"><?php if ($edit): ?><input type="hidden" name="color_id" value="<?= (int)$edit['color_id'] ?>"><?php endif; ?>
    <div class="full"><label class="admin-label">Tên giấy gói</label><input class="admin-input" name="name" maxlength="80" required value="<?= cas_e($edit['name'] ?? '') ?>" placeholder="Ví dụ: Nâu sữa"></div>
    <div><label class="admin-label">Mã màu</label><input class="admin-input" name="hex_code" type="color" value="<?= cas_e($edit['hex_code'] ?? '#D8C9B8') ?>"></div>
    <div><label class="admin-label">Phụ thu (VND)</label><input class="admin-input" name="surcharge" type="number" min="0" max="500000" step="1000" value="<?= (int)($edit['surcharge'] ?? 0) ?>"></div>
    <div><label class="admin-label">Thứ tự hiển thị</label><input class="admin-input" name="sort_order" type="number" min="0" max="999" value="<?= (int)($edit['sort_order'] ?? 0) ?>"></div>
    <div><label class="admin-label">Trạng thái</label><?= cas_select('status', ['active' => 'Đang dùng', 'inactive' => 'Ngừng dùng'], (string)($edit['status'] ?? 'active')) ?></div>
    <div class="full"><label class="admin-label">Ảnh mẫu giấy (PNG / JPG / WEBP ≤ 1 MB, tuỳ chọn)</label><input class="admin-input" type="file" name="image" accept="image/png,image/jpeg,image/webp">
      <?php if (!empty($edit['image'])): ?><label class="admin-check mt-2"><input type="checkbox" name="remove_image" value="1"> Bỏ ảnh hiện tại</label><?php endif; ?></div>
    <div class="full"><button class="admin-btn" type="submit"><?= $edit ? 'Lưu giấy gói' : 'Thêm giấy gói' ?></button><?php if ($edit): ?> <a class="admin-btn-light" href="giay-goi.php">Huỷ</a><?php endif; ?></div>
  </form>
  <?php else: ?><h2 class="admin-card-title">Chỉ xem</h2><p class="admin-card-subtitle">Tài khoản nhân viên không chỉnh sửa giấy gói.</p><?php endif; ?>
</article>
</section>
<?php if ($assign && $canEdit): ?>
<section class="admin-card admin-section" id="assign">
  <h2 class="admin-card-title">Sản phẩm dùng giấy "<?= cas_e($assign['name']) ?>"</h2>
  <form method="post" class="mt-3"><?= cas_csrf_form() ?><input type="hidden" name="action" value="wrapping_assign"><input type="hidden" name="color_id" value="<?= (int)$assign['color_id'] ?>">
    <div class="cas-admin-stack"><?php foreach ($products as $p): ?><label class="admin-check" style="min-width:260px"><input type="checkbox" name="products[]" value="<?= (int)$p['product_id'] ?>" <?= in_array((int)$p['product_id'], $assigned, true) ? 'checked' : '' ?>> <?= cas_e($p['name']) ?> <span class="cas-muted"><?= cas_e($p['sku']) ?><?= $p['status'] !== 'active' ? ' · đang ẩn' : '' ?></span></label><?php endforeach; ?></div>
    <button class="admin-btn mt-3" type="submit">Lưu gán sản phẩm</button></form>
</section>
<?php endif;
    cas_ops_end();
}

// ------------------------------------------------------------------ Danh mục khám phá
function cas_discovery_save(PDO $db): string
{
    cas_require_permission('storefront.edit', 'discovery_save');
    $id = filter_var($_POST['group_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $old = $id ? (cas_rows('SELECT * FROM discovery_groups WHERE group_id=?', [$id])[0] ?? null) : null;
    if ($id && !$old) {
        throw new DomainException('Không tìm thấy nhóm khám phá.');
    }
    $title = cas_in_str('title', 2, 80, 'Tiêu đề');
    $subtitle = cas_in_str('subtitle', 0, 60, 'Nhãn phụ');
    $type = cas_in_choice('filter_type', array_keys(CAS_DISCOVERY_TYPES), 'Điều kiện lọc');
    $filterId = null;
    $filterValue = null;
    $tables = ['occasion' => ['occasions', 'occasion_id'], 'tone' => ['tones', 'tone_id'], 'category' => ['categories', 'category_id']];
    if (isset($tables[$type])) {
        [$table, $pk] = $tables[$type];
        $filterId = cas_in_int('filter_id_' . $type, 1, PHP_INT_MAX, 'Giá trị lọc');
        $status = cas_value("SELECT status FROM {$table} WHERE {$pk}=?", [$filterId]);
        if ($status === false) {
            throw new DomainException('Giá trị phân loại đã chọn không tồn tại.');
        }
        if ($status !== 'active') {
            throw new DomainException('Phân loại đã chọn đang "ngừng dùng", không thể gắn vào nhóm khám phá.');
        }
    } elseif ($type === 'type') {
        $filterValue = cas_in_choice('filter_value', ['bouquet', 'basket'], 'Kiểu sản phẩm');
    }
    $sort = cas_in_int('sort_order', 0, 999, 'Thứ tự');
    $status = cas_in_choice('status', ['active', 'inactive'], 'Trạng thái');
    $library = trim((string)($_POST['library_image'] ?? ''));
    $files = cas_uploaded_files('image_file');
    $image = $old['image'] ?? null;
    $newImage = null;
    if ($files) {
        $newImage = cas_store_image($files[0], 'discovery', 2097152);
        $image = $newImage;
    } elseif ($library !== '') {
        if (!preg_match('/^[a-z0-9-]+\.png$/', $library) || !is_file(__DIR__ . '/../../assets/images/' . $library)) {
            throw new DomainException('Ảnh thư viện không hợp lệ.');
        }
        $image = 'assets/images/' . $library;
    }
    if (!$image) {
        throw new DomainException('Nhóm khám phá cần một ảnh (chọn từ thư viện hoặc tải lên).');
    }
    $slug = $old['slug'] ?? '';
    if (!$old) {
        $slug = cas_slugify($title) ?: 'kham-pha';
        for ($base = $slug, $n = 2; (int)cas_value('SELECT COUNT(*) FROM discovery_groups WHERE slug=?', [$slug]); $n++) {
            $slug = $base . '-' . $n;
        }
    }
    try {
        if ($old) {
            $db->prepare('UPDATE discovery_groups SET title=?,subtitle=?,image=?,filter_type=?,filter_id=?,filter_value=?,sort_order=?,status=?,updated_at=NOW() WHERE group_id=?')
               ->execute([$title, $subtitle, $image, $type, $filterId, $filterValue, $sort, $status, $id]);
        } else {
            $db->prepare('INSERT INTO discovery_groups(slug,title,subtitle,image,filter_type,filter_id,filter_value,sort_order,status,updated_at) VALUES(?,?,?,?,?,?,?,?,?,NOW())')
               ->execute([$slug, $title, $subtitle, $image, $type, $filterId, $filterValue, $sort, $status]);
            $id = (int)$db->lastInsertId();
        }
    } catch (Throwable $e) {
        cas_delete_upload($newImage);
        throw $e;
    }
    if ($old && $old['image'] !== $image) {
        cas_delete_upload($old['image']);
    }
    cas_audit($old ? 'discovery_update' : 'discovery_create', 'discovery_group', $id, compact('title', 'type', 'filterId', 'filterValue', 'status'));
    return ($old ? 'Đã cập nhật' : 'Đã thêm') . ' nhóm khám phá "' . $title . '".';
}

function cas_discovery_delete(PDO $db): string
{
    cas_require_permission('storefront.edit', 'discovery_delete');
    $id = cas_in_id('group_id');
    $g = cas_rows('SELECT * FROM discovery_groups WHERE group_id=?', [$id])[0] ?? null;
    if (!$g) {
        throw new DomainException('Không tìm thấy nhóm khám phá.');
    }
    $db->prepare('DELETE FROM discovery_groups WHERE group_id=?')->execute([$id]);
    cas_delete_upload($g['image']);
    cas_audit('discovery_delete', 'discovery_group', $id, ['title' => $g['title']]);
    return 'Đã xoá nhóm khám phá "' . $g['title'] . '".';
}

function cas_discovery_page(): void
{
    global $db, $user;
    cas_ops_post(['discovery_save' => static fn() => cas_discovery_save($db), 'discovery_delete' => static fn() => cas_discovery_delete($db)]);
    $groups = cas_discovery_groups(false) ?? [];
    $editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
    $edit = null;
    foreach ($groups as $g) {
        if ((int)$g['group_id'] === $editId) {
            $edit = $g;
        }
    }
    $options = [
        'occasion' => cas_rows("SELECT occasion_id id, name FROM occasions WHERE status='active' ORDER BY name"),
        'tone' => cas_rows("SELECT tone_id id, name FROM tones WHERE status='active' ORDER BY name"),
        'category' => cas_rows("SELECT category_id id, name FROM categories WHERE status='active' ORDER BY name"),
    ];
    $library = [];
    foreach (glob(__DIR__ . '/../../assets/images/*.png') ?: [] as $path) {
        $library[] = basename($path);
    }
    $visible = count(array_filter($groups, static fn($g) => $g['status'] === 'active' && $g['valid']));
    $canEdit = cas_can($user, 'storefront.edit');

    cas_ops_start('Danh mục khám phá', 'Các thẻ "Hoa cho mọi dịp" trên trang chủ; mỗi thẻ lọc sản phẩm theo phân loại có sẵn.', 'bi-compass');
    ?>
<section class="admin-card admin-section">
  <div class="cas-admin-stack justify-content-between"><h2 class="admin-card-title mb-0">Đang hiển thị trên trang chủ: <?= $visible ?> thẻ</h2><a class="admin-btn-light" href="../index.php#discovery-title" target="_blank" rel="noopener">Xem trang chủ</a></div>
  <div class="admin-grid-4 mt-3">
  <?php foreach ($groups as $g): ?>
    <article class="admin-card" style="padding:12px">
      <img src="../<?= cas_e($g['image']) ?>" alt="" style="width:100%;height:150px;object-fit:cover;border-radius:12px">
      <div class="mt-2"><span class="cas-muted"><?= cas_e($g['subtitle']) ?></span><h3 class="admin-card-title mb-1"><?= cas_e($g['title']) ?></h3>
        <div class="cas-muted"><?= cas_e(CAS_DISCOVERY_TYPES[$g['filter_type']] ?? $g['filter_type']) ?>: <?= cas_e($g['filter_name'] ?? ($g['filter_value'] ?? '')) ?> · thứ tự <?= (int)$g['sort_order'] ?></div>
        <div class="cas-muted text-break">→ <?= cas_e(rawurldecode($g['href'])) ?></div>
        <div class="mt-1"><?= $g['status'] !== 'active' ? cas_status_badge('Đang ẩn') : (!$g['valid'] ? cas_status_badge('Phân loại ngừng dùng – tạm ẩn', 'danger') : cas_status_badge('Đang hiển thị', 'success')) ?></div>
        <?php if ($canEdit): ?><div class="cas-admin-table-form mt-2"><a class="admin-btn-light" href="?edit=<?= (int)$g['group_id'] ?>#groupForm">Sửa</a>
          <form method="post" data-confirm="Xoá nhóm khám phá này?"><?= cas_csrf_form() ?><input type="hidden" name="action" value="discovery_delete"><input type="hidden" name="group_id" value="<?= (int)$g['group_id'] ?>"><button class="admin-btn-light text-danger" type="submit">Xoá</button></form></div><?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
  <?php if (!$groups): ?><div class="admin-empty">Chưa có nhóm khám phá — trang chủ sẽ ẩn khu vực này.</div><?php endif; ?>
  </div>
</section>
<?php if ($canEdit): ?>
<section class="admin-card admin-section" id="groupForm" style="max-width:900px">
  <h2 class="admin-card-title"><?= $edit ? 'Sửa nhóm "' . cas_e($edit['title']) . '"' : 'Thêm nhóm khám phá' ?></h2>
  <form method="post" enctype="multipart/form-data" class="admin-form-grid mt-3">
    <?= cas_csrf_form() ?><input type="hidden" name="action" value="discovery_save"><?php if ($edit): ?><input type="hidden" name="group_id" value="<?= (int)$edit['group_id'] ?>"><?php endif; ?>
    <div><label class="admin-label">Tiêu đề</label><input class="admin-input" name="title" maxlength="80" required value="<?= cas_e($edit['title'] ?? '') ?>" placeholder="Ví dụ: Cảm ơn thầy cô"></div>
    <div><label class="admin-label">Nhãn phụ</label><input class="admin-input" name="subtitle" maxlength="60" value="<?= cas_e($edit['subtitle'] ?? '') ?>" placeholder="Theo dịp"></div>
    <div><label class="admin-label">Điều kiện lọc</label><?= cas_select('filter_type', CAS_DISCOVERY_TYPES, (string)($edit['filter_type'] ?? 'occasion')) ?></div>
    <?php foreach ($options as $type => $list): ?>
    <div><label class="admin-label"><?= cas_e(CAS_DISCOVERY_TYPES[$type]) ?></label><select class="admin-select" name="filter_id_<?= $type ?>"><?php foreach ($list as $o): ?><option value="<?= (int)$o['id'] ?>" <?= ($edit['filter_type'] ?? '') === $type && (int)$edit['filter_id'] === (int)$o['id'] ? 'selected' : '' ?>><?= cas_e($o['name']) ?></option><?php endforeach; ?></select></div>
    <?php endforeach; ?>
    <div><label class="admin-label">Kiểu sản phẩm (khi lọc theo kiểu)</label><?= cas_select('filter_value', ['bouquet' => 'Bó hoa', 'basket' => 'Giỏ hoa'], (string)($edit['filter_value'] ?? 'bouquet')) ?></div>
    <div><label class="admin-label">Thứ tự</label><input class="admin-input" name="sort_order" type="number" min="0" max="999" value="<?= (int)($edit['sort_order'] ?? count($groups) + 1) ?>"></div>
    <div><label class="admin-label">Trạng thái</label><?= cas_select('status', ['active' => 'Hiển thị', 'inactive' => 'Ẩn'], (string)($edit['status'] ?? 'active')) ?></div>
    <div><label class="admin-label">Ảnh thư viện</label><select class="admin-select" name="library_image"><option value=""><?= $edit ? '— Giữ ảnh hiện tại —' : '— Chọn ảnh —' ?></option><?php foreach ($library as $file): ?><option value="<?= cas_e($file) ?>"><?= cas_e($file) ?></option><?php endforeach; ?></select></div>
    <div><label class="admin-label">Hoặc tải ảnh (≤ 2 MB)</label><input class="admin-input" type="file" name="image_file" accept="image/png,image/jpeg,image/webp"></div>
    <div class="full"><button class="admin-btn" type="submit"><?= $edit ? 'Lưu nhóm' : 'Thêm nhóm' ?></button><?php if ($edit): ?> <a class="admin-btn-light" href="kham-pha.php">Huỷ</a><?php endif; ?>
      <p class="cas-muted mt-2 mb-0">Liên kết trên trang chủ dùng tên phân loại hiện có để lọc trang Sản phẩm; nếu phân loại bị chuyển "ngừng dùng", thẻ tự ẩn và sản phẩm vẫn giữ liên kết phân loại.</p></div>
  </form>
</section>
<?php endif;
    cas_ops_end();
}

// ------------------------------------------------------------------ Cài đặt cửa hàng
const CAS_SETTING_FIELDS = [
    'shop_name' => ['Tên cửa hàng', 2, 80], 'hotline' => ['Hotline', 10, 10], 'email' => ['Email liên hệ', 5, 150],
    'address' => ['Địa chỉ cửa hàng', 5, 255], 'opening_hours' => ['Giờ hoạt động', 3, 120], 'announcement' => ['Thông báo đầu trang', 0, 200],
    'instagram' => ['Instagram (URL)', 0, 255], 'zalo' => ['Zalo', 0, 10], 'default_shipping_fee' => ['Phí giao tham khảo (VND)', 1, 7],
];

function cas_settings_save(PDO $db, array $user): string
{
    cas_require_permission('settings.edit', 'settings_save');
    $values = [];
    foreach (CAS_SETTING_FIELDS as $key => [$label, $min, $max]) {
        $values[$key] = cas_in_str($key, $min, $max, $label);
    }
    if (!preg_match('/^0\d{9}$/', $values['hotline'])) {
        throw new DomainException('Hotline phải gồm 10 chữ số, bắt đầu bằng 0.');
    }
    if ($values['zalo'] !== '' && !preg_match('/^0\d{9}$/', $values['zalo'])) {
        throw new DomainException('Số Zalo phải gồm 10 chữ số, bắt đầu bằng 0.');
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        throw new DomainException('Email liên hệ không hợp lệ.');
    }
    if ($values['instagram'] !== '' && !preg_match('#^https://(www\.)?instagram\.com/[A-Za-z0-9_./-]*$#', $values['instagram'])) {
        throw new DomainException('Instagram phải là đường dẫn https://instagram.com/...');
    }
    if (!ctype_digit($values['default_shipping_fee']) || (int)$values['default_shipping_fee'] > 500000) {
        throw new DomainException('Phí giao tham khảo phải từ 0 đến 500.000.');
    }
    $before = cas_settings();
    $changed = array_keys(array_diff_assoc($values, array_intersect_key($before, $values)));
    if (!$changed) {
        throw new DomainException('Không có thông tin nào thay đổi.');
    }
    $db->beginTransaction();
    $q = $db->prepare('INSERT INTO shop_settings(setting_key,setting_value,updated_by,updated_at) VALUES(?,?,?,NOW())
                       ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by), updated_at=NOW()');
    foreach ($values as $key => $value) {
        $q->execute([$key, $value, $user['user_id']]);
    }
    $db->commit();
    cas_audit('settings_save', 'shop_settings', null, array_combine($changed, array_map(static fn($k) => ['before' => $before[$k] ?? '', 'after' => $values[$k]], $changed)));
    return 'Đã lưu ' . count($changed) . ' thông tin cửa hàng; trang khách hàng dùng giá trị mới ngay.';
}

function cas_settings_page(): void
{
    global $db, $user;
    cas_page_roles(['admin', 'owner'], 'Cài đặt shop');
    cas_ops_post(['settings_save' => static fn() => cas_settings_save($db, $user)]);
    $s = cas_post() ? array_merge(cas_settings(), array_intersect_key($_POST, CAS_SETTING_FIELDS)) : cas_settings();
    $meta = cas_rows('SELECT s.updated_at, u.full_name FROM shop_settings s LEFT JOIN users u ON u.user_id=s.updated_by WHERE s.updated_at IS NOT NULL ORDER BY s.updated_at DESC LIMIT 1')[0] ?? null;
    cas_ops_start('Cài đặt shop', 'Thông tin công khai lưu trong MySQL: hiển thị ở thanh thông báo, chân trang và trang Liên hệ.', 'bi-gear');
    ?>
<section class="admin-card admin-section" style="max-width:900px">
  <?php if ($meta): ?><p class="cas-muted">Cập nhật lần cuối: <?= cas_e($meta['updated_at']) ?> bởi <?= cas_e($meta['full_name'] ?? '—') ?></p><?php endif; ?>
  <form method="post" class="admin-form-grid">
    <?= cas_csrf_form() ?><input type="hidden" name="action" value="settings_save">
    <?php foreach (CAS_SETTING_FIELDS as $key => [$label, $min, $max]): ?>
    <div class="<?= in_array($key, ['address', 'announcement'], true) ? 'full' : '' ?>"><label class="admin-label" for="set_<?= $key ?>"><?= cas_e($label) ?></label>
      <?php if ($key === 'announcement'): ?><textarea class="admin-textarea" id="set_<?= $key ?>" name="<?= $key ?>" rows="2" maxlength="<?= $max ?>"><?= cas_e($s[$key]) ?></textarea>
      <?php else: ?><input class="admin-input" id="set_<?= $key ?>" name="<?= $key ?>" maxlength="<?= $max ?>" value="<?= cas_e($s[$key]) ?>" <?= $min > 0 ? 'required' : '' ?> <?= $key === 'default_shipping_fee' ? 'inputmode="numeric"' : '' ?>><?php endif; ?></div>
    <?php endforeach; ?>
    <div class="full"><button class="admin-btn" type="submit">Lưu cài đặt</button>
      <p class="cas-muted mt-2 mb-0">Khung giờ giao và phí giao thực tế khi đặt hàng thuộc luồng thanh toán (trưởng nhóm); "phí giao tham khảo" hiện chỉ dùng để hiển thị.</p></div>
  </form>
</section>
<?php
    cas_ops_end();
}
