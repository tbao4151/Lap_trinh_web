<?php
declare(strict_types=1);
/** ADM-03 — Kho hoa, kho phụ kiện, kho tổng và lịch sử giao dịch kho. Dùng sau khi đã require admin/_ops.php. */

const CAS_INV_CATEGORIES = [
    'hoa' => ['flower', 'Hoa tươi'], 'la' => ['flower', 'Lá & cành phụ'],
    'giay_goi' => ['accessory', 'Giấy gói'], 'gio_hop' => ['accessory', 'Giỏ / hộp'],
    'no' => ['accessory', 'Nơ & ruy băng'], 'phu_kien' => ['accessory', 'Phụ kiện khác'],
];
const CAS_INV_UNITS = ['cành', 'bông', 'bó', 'tờ', 'cuộn', 'cái', 'hộp', 'giỏ', 'mét'];
const CAS_INV_TYPES = ['in' => 'Nhập kho', 'out' => 'Xuất kho', 'adjustment' => 'Kiểm kê'];
const CAS_INV_SCOPES = [
    'all' => ['Kho tổng', 'Tổng hợp nguyên liệu hoa và phụ kiện phục vụ bó, gói và giao hoa.', 'bi-building'],
    'flower' => ['Kho Hoa', 'Hoa tươi, lá và cành phụ: tồn kho, ngưỡng tối thiểu và phiếu nhập / xuất.', 'bi-flower3'],
    'accessory' => ['Kho Phụ kiện', 'Giấy gói, giỏ / hộp, nơ và phụ kiện đi kèm: tồn kho và phiếu nhập / xuất.', 'bi-box-seam'],
];

/**
 * Ghi một phiếu kho trong MỘT transaction:
 *  - khoá dòng mặt hàng (SELECT ... FOR UPDATE) để yêu cầu đồng thời xếp hàng, không tạo tồn âm;
 *  - ghi phiếu với tồn trước / sau, người thao tác, lý do và token UNIQUE (chống gửi trùng);
 *  - cập nhật inventory_items.quantity. Bất kỳ lỗi nào cũng rollback toàn bộ.
 * $quantity: số lượng nhập / xuất (>0); với kiểm kê là số tồn thực tế mới (>=0).
 */
function cas_inventory_move(PDO $db, array $user, int $itemId, string $type, float $quantity, string $reason, string $token): array
{
    if (!isset(CAS_INV_TYPES[$type])) {
        throw new DomainException('Loại phiếu kho không hợp lệ.');
    }
    if ($type !== 'adjustment' && $quantity <= 0) {
        throw new DomainException('Số lượng nhập / xuất phải lớn hơn 0.');
    }
    $db->beginTransaction();
    try {
        $q = $db->prepare('SELECT item_id, name, unit, quantity, status FROM inventory_items WHERE item_id=? FOR UPDATE');
        $q->execute([$itemId]);
        $item = $q->fetch();
        if (!$item) {
            throw new DomainException('Mặt hàng kho không tồn tại.');
        }
        if ($item['status'] !== 'active') {
            throw new DomainException('Mặt hàng "' . $item['name'] . '" đã ngừng sử dụng, không thể nhập / xuất.');
        }
        $before = (float)$item['quantity'];
        $after = match ($type) {
            'in' => $before + $quantity,
            'out' => $before - $quantity,
            'adjustment' => $quantity,
        };
        $after = round($after, 2);
        if ($after < 0) {
            throw new DomainException('Không đủ tồn: "' . $item['name'] . '" còn ' . cas_qty($before) . ' ' . $item['unit']
                . ', không thể xuất ' . cas_qty($quantity) . '.');
        }
        $delta = round($after - $before, 2);
        if ($type === 'adjustment' && abs($delta) < 0.005) {
            throw new DomainException('Số tồn kiểm kê bằng tồn hiện tại, không cần điều chỉnh.');
        }
        $ins = $db->prepare('INSERT INTO inventory_transactions(item_id,user_id,transaction_type,quantity,quantity_before,quantity_after,note,request_token)
                             VALUES(?,?,?,?,?,?,?,?)');
        $ins->execute([$itemId, $user['user_id'], $type, $delta, $before, $after, $reason, $token]);
        $txId = (int)$db->lastInsertId();
        $db->prepare('UPDATE inventory_items SET quantity=?, updated_at=NOW() WHERE item_id=?')->execute([$after, $itemId]);
        $db->commit();
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'uq_inventory_request')) {
            throw new DomainException('Phiếu này đã được ghi nhận trước đó, hệ thống không ghi lặp lần hai.');
        }
        throw $e;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    cas_audit('inventory_' . $type, 'inventory_item', $itemId, ['transaction_id' => $txId, 'before' => $before, 'after' => $after, 'reason' => $reason]);
    return ['transaction_id' => $txId, 'name' => $item['name'], 'unit' => $item['unit'], 'before' => $before, 'after' => $after, 'delta' => $delta];
}

/** Tạo / sửa danh mục nguyên liệu. Tồn đầu kỳ (nếu có) được ghi thành phiếu nhập trong cùng transaction. */
function cas_inventory_save_item(PDO $db, array $user): string
{
    cas_require_permission('inventory.items', 'inventory_item_save');
    $id = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $name = cas_in_str('name', 2, 150, 'Tên mặt hàng');
    $category = cas_in_choice('category', array_keys(CAS_INV_CATEGORIES), 'Nhóm nguyên liệu');
    $unit = cas_in_choice('unit', CAS_INV_UNITS, 'Đơn vị tính');
    $min = cas_in_decimal('min_quantity', 0, 100000, 'Mức tồn tối thiểu');
    $cost = cas_in_int('unit_cost', 0, 100000000, 'Giá nhập');
    $status = cas_in_choice('status', ['active', 'inactive'], 'Trạng thái');
    $dup = cas_value('SELECT COUNT(*) FROM inventory_items WHERE LOWER(name)=LOWER(?) AND item_id<>?', [$name, $id ?? 0]);
    if ((int)$dup > 0) {
        throw new DomainException('Đã có mặt hàng tên "' . $name . '".');
    }
    $type = CAS_INV_CATEGORIES[$category][0];
    if ($id) {
        $old = cas_rows('SELECT * FROM inventory_items WHERE item_id=?', [$id])[0] ?? null;
        if (!$old) {
            throw new DomainException('Mặt hàng kho không tồn tại.');
        }
        $db->prepare('UPDATE inventory_items SET name=?,item_type=?,category=?,unit=?,min_quantity=?,unit_cost=?,status=?,updated_at=NOW() WHERE item_id=?')
           ->execute([$name, $type, $category, $unit, $min, $cost, $status, $id]);
        cas_audit('inventory_item_update', 'inventory_item', $id, ['before' => array_intersect_key($old, array_flip(['name', 'category', 'unit', 'min_quantity', 'unit_cost', 'status'])),
            'after' => compact('name', 'category', 'unit', 'min', 'cost', 'status')]);
        return 'Đã cập nhật mặt hàng "' . $name . '".';
    }
    $opening = cas_in_decimal('opening_quantity', 0, 100000, 'Tồn đầu kỳ');
    // Mặt hàng và phiếu tồn đầu kỳ ghi cùng một transaction: không bao giờ có tồn mà thiếu lịch sử.
    $db->beginTransaction();
    $db->prepare('INSERT INTO inventory_items(item_type,category,name,unit,quantity,min_quantity,unit_cost,status,updated_at) VALUES(?,?,?,?,?,?,?,?,NOW())')
       ->execute([$type, $category, $name, $unit, $opening, $min, $cost, $status]);
    $newId = (int)$db->lastInsertId();
    if ($opening > 0) {
        $db->prepare('INSERT INTO inventory_transactions(item_id,user_id,transaction_type,quantity,quantity_before,quantity_after,note,request_token) VALUES(?,?,?,?,?,?,?,?)')
           ->execute([$newId, $user['user_id'], 'in', $opening, 0, $opening, 'Tồn đầu kỳ khi tạo mặt hàng', cas_form_token()]);
    }
    $db->commit();
    cas_audit('inventory_item_create', 'inventory_item', $newId, compact('name', 'category', 'unit', 'min', 'cost', 'opening'));
    return 'Đã thêm mặt hàng "' . $name . '"' . ($opening > 0 ? ' với tồn đầu kỳ ' . cas_qty($opening) . ' ' . $unit : '') . '.';
}

/** Xử lý phiếu nhập / xuất / kiểm kê từ form. */
function cas_inventory_handle_move(PDO $db, array $user): string
{
    $type = cas_in_choice('transaction_type', array_keys(CAS_INV_TYPES), 'Loại phiếu');
    cas_require_permission($type === 'adjustment' ? 'inventory.adjust' : 'inventory.move', 'inventory_' . $type);
    $itemId = cas_in_id('item_id');
    $quantity = cas_in_decimal('quantity', 0, 100000, 'Số lượng');
    $reason = cas_in_str('reason', 3, 255, 'Lý do');
    $r = cas_inventory_move($db, $user, $itemId, $type, $quantity, $reason, cas_in_token());
    return CAS_INV_TYPES[$type] . ' "' . $r['name'] . '": ' . cas_qty($r['before']) . ' → ' . cas_qty($r['after']) . ' ' . $r['unit']
        . ' (phiếu #' . $r['transaction_id'] . ').';
}

/** Trang Kho tổng / Kho Hoa / Kho Phụ kiện dùng chung. */
function cas_inventory_page(string $scope): void
{
    global $db, $user;
    cas_ops_post([
        'stock_move' => static fn() => cas_inventory_handle_move($db, $user),
        'item_save' => static fn() => cas_inventory_save_item($db, $user),
    ]);
    [$title, $subtitle, $icon] = CAS_INV_SCOPES[$scope];
    $where = [];
    $args = [];
    if ($scope !== 'all') {
        $where[] = 'i.item_type=?';
        $args[] = $scope;
    }
    $scopeArgs = $args;
    $scopeWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $search = trim((string)($_GET['q'] ?? ''));
    $category = (string)($_GET['category'] ?? '');
    $status = (string)($_GET['status'] ?? 'active');
    $lowOnly = isset($_GET['low']);
    if ($search !== '') {
        $where[] = 'i.name LIKE ?';
        $args[] = '%' . $search . '%';
    }
    if (isset(CAS_INV_CATEGORIES[$category])) {
        $where[] = 'i.category=?';
        $args[] = $category;
    }
    if (in_array($status, ['active', 'inactive'], true)) {
        $where[] = 'i.status=?';
        $args[] = $status;
    }
    if ($lowOnly) {
        $where[] = 'i.quantity<=i.min_quantity';
    }
    $items = cas_rows('SELECT i.* FROM inventory_items i' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY i.item_type, i.category, i.name', $args);
    $kpi = cas_rows("SELECT COUNT(*) items, SUM(i.quantity<=i.min_quantity) low, SUM(i.quantity=0) empty_stock, COALESCE(SUM(i.quantity*i.unit_cost),0) value
                     FROM inventory_items i" . ($scopeWhere ? $scopeWhere . " AND i.status='active'" : " WHERE i.status='active'"), $scopeArgs)[0];
    $flow = cas_rows("SELECT COALESCE(SUM(CASE WHEN t.quantity>0 THEN t.quantity END),0) qin, COALESCE(SUM(CASE WHEN t.quantity<0 THEN -t.quantity END),0) qout,
                        SUM(DATE(t.created_at)=CURDATE()) today
                      FROM inventory_transactions t JOIN inventory_items i ON i.item_id=t.item_id
                      WHERE t.created_at>=NOW()-INTERVAL 7 DAY" . ($scope !== 'all' ? ' AND i.item_type=?' : ''), $scopeArgs)[0];
    $active = cas_rows("SELECT item_id,name,unit,quantity FROM inventory_items i WHERE i.status='active'" . ($scope !== 'all' ? ' AND i.item_type=?' : '') . ' ORDER BY name', $scopeArgs);
    $recent = cas_rows('SELECT t.*, i.name item_name, i.unit, u.full_name actor FROM inventory_transactions t
                        JOIN inventory_items i ON i.item_id=t.item_id LEFT JOIN users u ON u.user_id=t.user_id'
                        . ($scope !== 'all' ? ' WHERE i.item_type=?' : '') . ' ORDER BY t.transaction_id DESC LIMIT 8', $scopeArgs);
    $editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
    $edit = $editId ? (cas_rows('SELECT * FROM inventory_items WHERE item_id=?', [$editId])[0] ?? null) : null;
    $categories = array_filter(CAS_INV_CATEGORIES, static fn($c) => $scope === 'all' || $c[0] === $scope);
    $canMove = cas_can($user, 'inventory.move');
    $canAdjust = cas_can($user, 'inventory.adjust');
    $canItems = cas_can($user, 'inventory.items');

    cas_ops_start($title, $subtitle, $icon);
    ?>
<section class="admin-grid-4 admin-section">
  <article class="admin-stat"><div class="admin-stat-icon"><i class="bi bi-boxes"></i></div><div class="admin-stat-label">Mặt hàng đang dùng</div><div class="admin-stat-value"><?= (int)$kpi['items'] ?></div><div class="admin-stat-note">Theo dõi trong MySQL</div></article>
  <article class="admin-stat"><div class="admin-stat-icon"><i class="bi bi-exclamation-triangle"></i></div><div class="admin-stat-label">Sắp hết / hết hàng</div><div class="admin-stat-value"><?= (int)$kpi['low'] ?> / <?= (int)$kpi['empty_stock'] ?></div><div class="admin-stat-note">Tồn ≤ mức tối thiểu</div></article>
  <article class="admin-stat"><div class="admin-stat-icon"><i class="bi bi-cash-stack"></i></div><div class="admin-stat-label">Giá trị tồn ước tính</div><div class="admin-stat-value"><?= cas_money($kpi['value']) ?></div><div class="admin-stat-note">Số lượng × giá nhập</div></article>
  <article class="admin-stat"><div class="admin-stat-icon"><i class="bi bi-arrow-left-right"></i></div><div class="admin-stat-label">Nhập / xuất 7 ngày</div><div class="admin-stat-value">+<?= cas_qty($flow['qin']) ?> / −<?= cas_qty($flow['qout']) ?></div><div class="admin-stat-note"><?= (int)$flow['today'] ?> phiếu hôm nay</div></article>
</section>

<section class="admin-grid-2 admin-section">
<article class="admin-card">
  <h2 class="admin-card-title">Phiếu nhập / xuất kho</h2>
  <?php if ($canMove): ?>
  <p class="admin-card-subtitle">Mỗi phiếu ghi người thao tác, lý do và tồn trước / sau. Kiểm kê (đặt lại số tồn thực tế) chỉ dành cho quản trị viên.</p>
  <form method="post" class="admin-form-grid mt-3">
    <?= cas_csrf_form() ?><input type="hidden" name="action" value="stock_move"><input type="hidden" name="request_token" value="<?= cas_form_token() ?>">
    <div class="full"><label class="admin-label" for="moveItem">Mặt hàng</label>
      <select class="admin-select" id="moveItem" name="item_id" required><?php foreach ($active as $a): ?><option value="<?= (int)$a['item_id'] ?>"><?= cas_e($a['name']) ?> — tồn <?= cas_qty($a['quantity']) ?> <?= cas_e($a['unit']) ?></option><?php endforeach; ?></select></div>
    <div><label class="admin-label" for="moveType">Loại phiếu</label><?= cas_select('transaction_type', $canAdjust ? CAS_INV_TYPES : array_slice(CAS_INV_TYPES, 0, 2, true), 'in', 'id="moveType"') ?></div>
    <div><label class="admin-label" for="moveQty">Số lượng (kiểm kê: tồn thực tế)</label><input class="admin-input" id="moveQty" name="quantity" type="number" min="0" max="100000" step="0.01" required></div>
    <div class="full"><label class="admin-label" for="moveReason">Lý do</label><input class="admin-input" id="moveReason" name="reason" minlength="3" maxlength="255" required placeholder="Nhập hàng từ chợ hoa, xuất cho đơn CSH-..., hao hụt..."></div>
    <div class="full"><button class="admin-btn" type="submit">Ghi phiếu kho</button></div>
  </form>
  <?php else: ?><p class="admin-card-subtitle">Bạn chỉ có quyền xem tồn kho.</p><?php endif; ?>
</article>
<article class="admin-card">
  <div class="cas-admin-stack justify-content-between"><h2 class="admin-card-title mb-0">Phiếu gần đây</h2><a class="admin-btn-light" href="lich-su-kho.php">Xem lịch sử</a></div>
  <div class="admin-list mt-3">
  <?php foreach ($recent as $t): ?>
    <div class="admin-list-item"><div><strong>#<?= (int)$t['transaction_id'] ?> · <?= cas_e($t['item_name']) ?></strong>
      <small class="d-block mt-1"><?= cas_e(CAS_INV_TYPES[$t['transaction_type']] ?? $t['transaction_type']) ?> · <?= cas_e($t['actor'] ?? '—') ?> · <?= cas_e($t['created_at']) ?> · <?= cas_e($t['note']) ?></small></div>
      <strong class="<?= (float)$t['quantity'] < 0 ? 'text-danger' : 'text-success' ?>"><?= (float)$t['quantity'] > 0 ? '+' : '' ?><?= cas_qty($t['quantity']) ?> <?= cas_e($t['unit']) ?></strong></div>
  <?php endforeach; ?>
  <?php if (!$recent): ?><div class="admin-empty">Chưa có phiếu kho.</div><?php endif; ?>
  </div>
</article>
</section>

<section class="admin-card admin-section">
  <form method="get" class="cas-admin-stack mb-3">
    <input class="admin-input" name="q" value="<?= cas_e($search) ?>" placeholder="Tìm tên nguyên liệu">
    <?= cas_select('category', ['' => 'Mọi nhóm'] + array_map(static fn($c) => $c[1], $categories), $category) ?>
    <?= cas_select('status', ['active' => 'Đang dùng', 'inactive' => 'Ngừng dùng', 'all' => 'Tất cả'], $status) ?>
    <label class="admin-check"><input type="checkbox" name="low" value="1" <?= $lowOnly ? 'checked' : '' ?>> Chỉ hàng sắp hết</label>
    <button class="admin-btn-light" type="submit">Lọc</button><span class="admin-badge"><?= count($items) ?> mặt hàng</span>
  </form>
  <div class="admin-table-wrap"><table class="admin-table">
    <thead><tr><th>Mặt hàng</th><th>Nhóm</th><th>Tồn</th><th>Tối thiểu</th><th>Giá nhập</th><th>Giá trị</th><th>Trạng thái</th><?php if ($canItems): ?><th></th><?php endif; ?></tr></thead><tbody>
    <?php foreach ($items as $i): $low = (float)$i['quantity'] <= (float)$i['min_quantity']; ?>
    <tr><td><strong><?= cas_e($i['name']) ?></strong><div class="cas-muted">Mã #<?= (int)$i['item_id'] ?></div></td>
      <td><?= cas_e(CAS_INV_CATEGORIES[$i['category']][1] ?? $i['category']) ?></td>
      <td><strong><?= cas_qty($i['quantity']) ?></strong> <?= cas_e($i['unit']) ?></td><td><?= cas_qty($i['min_quantity']) ?></td>
      <td><?= cas_money($i['unit_cost']) ?></td><td><?= cas_money((float)$i['quantity'] * (float)$i['unit_cost']) ?></td>
      <td><?= $i['status'] !== 'active' ? cas_status_badge('Ngừng dùng') : ((float)$i['quantity'] <= 0 ? cas_status_badge('Hết hàng', 'danger') : ($low ? cas_status_badge('Sắp hết', 'warning') : cas_status_badge('Đủ hàng', 'success'))) ?></td>
      <?php if ($canItems): ?><td><a class="admin-btn-light" href="?edit=<?= (int)$i['item_id'] ?>#itemForm">Sửa</a></td><?php endif; ?></tr>
    <?php endforeach; ?>
    <?php if (!$items): ?><tr><td colspan="8"><div class="admin-empty">Không có mặt hàng phù hợp bộ lọc.</div></td></tr><?php endif; ?>
    </tbody></table></div>
</section>

<?php if ($canItems): ?>
<section class="admin-card admin-section" id="itemForm" style="max-width:860px">
  <h2 class="admin-card-title"><?= $edit ? 'Sửa mặt hàng #' . (int)$edit['item_id'] : 'Thêm nguyên liệu mới' ?></h2>
  <form method="post" class="admin-form-grid mt-3">
    <?= cas_csrf_form() ?><input type="hidden" name="action" value="item_save"><?php if ($edit): ?><input type="hidden" name="item_id" value="<?= (int)$edit['item_id'] ?>"><?php endif; ?>
    <div><label class="admin-label">Tên mặt hàng</label><input class="admin-input" name="name" maxlength="150" required value="<?= cas_e($edit['name'] ?? '') ?>"></div>
    <div><label class="admin-label">Nhóm</label><?= cas_select('category', array_map(static fn($c) => $c[1], $categories), (string)($edit['category'] ?? array_key_first($categories))) ?></div>
    <div><label class="admin-label">Đơn vị tính</label><?= cas_select('unit', array_combine(CAS_INV_UNITS, CAS_INV_UNITS), (string)($edit['unit'] ?? 'cành')) ?></div>
    <div><label class="admin-label">Mức tồn tối thiểu</label><input class="admin-input" name="min_quantity" type="number" min="0" step="0.01" required value="<?= cas_e(isset($edit) && $edit ? (string)(float)$edit['min_quantity'] : '0') ?>"></div>
    <div><label class="admin-label">Giá nhập (VND)</label><input class="admin-input" name="unit_cost" type="number" min="0" max="100000000" step="500" required value="<?= cas_e(isset($edit) && $edit ? (string)(int)$edit['unit_cost'] : '0') ?>"></div>
    <div><label class="admin-label">Trạng thái</label><?= cas_select('status', ['active' => 'Đang dùng', 'inactive' => 'Ngừng dùng'], (string)($edit['status'] ?? 'active')) ?></div>
    <?php if (!$edit): ?><div><label class="admin-label">Tồn đầu kỳ (ghi thành phiếu nhập)</label><input class="admin-input" name="opening_quantity" type="number" min="0" step="0.01" value="0"></div><?php endif; ?>
    <div class="full"><button class="admin-btn" type="submit"><?= $edit ? 'Lưu mặt hàng' : 'Thêm mặt hàng' ?></button><?php if ($edit): ?> <a class="admin-btn-light" href="?">Huỷ</a><?php endif; ?>
      <p class="cas-muted mt-2 mb-0">Số tồn chỉ thay đổi qua phiếu kho để luôn có lịch sử; mặt hàng đã có phiếu không xoá được, chỉ chuyển "Ngừng dùng".</p></div>
  </form>
</section>
<?php endif;
    cas_ops_end();
}

/** Lịch sử phiếu kho: lọc theo thời gian, nguyên liệu, loại phiếu và người thao tác; phân trang. */
function cas_inventory_history_page(): void
{
    $from = (string)($_GET['from'] ?? date('Y-m-d', strtotime('-30 days')));
    $to = (string)($_GET['to'] ?? date('Y-m-d'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) || $to < $from) {
        $from = date('Y-m-d', strtotime('-30 days'));
        $to = date('Y-m-d');
    }
    $itemId = filter_var($_GET['item'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $type = (string)($_GET['type'] ?? '');
    $actor = filter_var($_GET['actor'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $where = ['t.created_at >= ?', 't.created_at < ? + INTERVAL 1 DAY'];
    $args = [$from, $to];
    if ($itemId) {
        $where[] = 't.item_id=?';
        $args[] = $itemId;
    }
    if (isset(CAS_INV_TYPES[$type])) {
        $where[] = 't.transaction_type=?';
        $args[] = $type;
    }
    if ($actor) {
        $where[] = 't.user_id=?';
        $args[] = $actor;
    }
    $whereSql = ' WHERE ' . implode(' AND ', $where);
    $sum = cas_rows('SELECT COUNT(*) n, COALESCE(SUM(CASE WHEN t.quantity>0 THEN t.quantity END),0) qin, COALESCE(SUM(CASE WHEN t.quantity<0 THEN -t.quantity END),0) qout,
                     COALESCE(SUM(t.quantity*i.unit_cost),0) value FROM inventory_transactions t JOIN inventory_items i ON i.item_id=t.item_id' . $whereSql, $args)[0];
    $perPage = 50;
    $pages = max(1, (int)ceil((int)$sum['n'] / $perPage));
    $page = min($page, $pages);
    $rows = cas_rows('SELECT t.*, i.name item_name, i.unit, u.full_name actor_name, r.name actor_role
                      FROM inventory_transactions t JOIN inventory_items i ON i.item_id=t.item_id
                      LEFT JOIN users u ON u.user_id=t.user_id LEFT JOIN roles r ON r.role_id=u.role_id'
                      . $whereSql . ' ORDER BY t.transaction_id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage), $args);
    $items = cas_rows('SELECT item_id, name FROM inventory_items ORDER BY name');
    $actors = cas_rows('SELECT DISTINCT u.user_id, u.full_name FROM inventory_transactions t JOIN users u ON u.user_id=t.user_id ORDER BY u.full_name');
    $query = static fn(int $p) => '?' . http_build_query(array_merge($_GET, ['page' => $p]));

    cas_ops_start('Lịch sử kho', 'Mọi lần nhập, xuất và kiểm kê nguyên liệu, kèm tồn trước / sau và người thực hiện.', 'bi-clock-history');
    ?>
<section class="admin-card admin-section">
  <form method="get" class="cas-admin-stack">
    <label>Từ <input class="admin-input" type="date" name="from" value="<?= cas_e($from) ?>"></label>
    <label>đến <input class="admin-input" type="date" name="to" value="<?= cas_e($to) ?>"></label>
    <select class="admin-select" name="item"><option value="">Mọi nguyên liệu</option><?php foreach ($items as $i): ?><option value="<?= (int)$i['item_id'] ?>" <?= $itemId === (int)$i['item_id'] ? 'selected' : '' ?>><?= cas_e($i['name']) ?></option><?php endforeach; ?></select>
    <?= cas_select('type', ['' => 'Mọi loại phiếu'] + CAS_INV_TYPES, $type) ?>
    <select class="admin-select" name="actor"><option value="">Mọi người thao tác</option><?php foreach ($actors as $a): ?><option value="<?= (int)$a['user_id'] ?>" <?= $actor === (int)$a['user_id'] ? 'selected' : '' ?>><?= cas_e($a['full_name']) ?></option><?php endforeach; ?></select>
    <button class="admin-btn-light" type="submit">Lọc lịch sử</button>
  </form>
</section>
<section class="admin-grid-4 admin-section">
  <article class="admin-stat"><div class="admin-stat-label">Số phiếu</div><div class="admin-stat-value"><?= (int)$sum['n'] ?></div><div class="admin-stat-note"><?= cas_e($from) ?> → <?= cas_e($to) ?></div></article>
  <article class="admin-stat"><div class="admin-stat-label">Tổng nhập</div><div class="admin-stat-value text-success">+<?= cas_qty($sum['qin']) ?></div><div class="admin-stat-note">Đơn vị theo từng mặt hàng</div></article>
  <article class="admin-stat"><div class="admin-stat-label">Tổng xuất</div><div class="admin-stat-value text-danger">−<?= cas_qty($sum['qout']) ?></div><div class="admin-stat-note">Bao gồm kiểm kê giảm</div></article>
  <article class="admin-stat"><div class="admin-stat-label">Giá trị thay đổi</div><div class="admin-stat-value"><?= cas_money($sum['value']) ?></div><div class="admin-stat-note">Theo giá nhập hiện tại</div></article>
</section>
<section class="admin-card admin-section">
  <div class="admin-table-wrap"><table class="admin-table">
    <thead><tr><th>Phiếu</th><th>Thời gian</th><th>Nguyên liệu</th><th>Loại</th><th>Thay đổi</th><th>Tồn trước → sau</th><th>Người thực hiện</th><th>Lý do</th></tr></thead><tbody>
    <?php foreach ($rows as $t): $q = (float)$t['quantity']; ?>
    <tr><td>#<?= (int)$t['transaction_id'] ?></td><td><?= cas_e($t['created_at']) ?></td><td><?= cas_e($t['item_name']) ?></td>
      <td><?= cas_status_badge(CAS_INV_TYPES[$t['transaction_type']] ?? $t['transaction_type'], $t['transaction_type'] === 'in' ? 'success' : ($t['transaction_type'] === 'out' ? 'warning' : '')) ?></td>
      <td class="<?= $q < 0 ? 'text-danger' : 'text-success' ?>"><strong><?= $q > 0 ? '+' : '' ?><?= cas_qty($q) ?></strong> <?= cas_e($t['unit']) ?></td>
      <td><?= $t['quantity_before'] === null ? '—' : cas_qty($t['quantity_before']) . ' → ' . cas_qty($t['quantity_after']) ?></td>
      <td><?= cas_e($t['actor_name'] ?? '—') ?><div class="cas-muted"><?= cas_e(CAS_ROLE_LABELS[$t['actor_role'] ?? ''] ?? '') ?></div></td><td><?= cas_e($t['note']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="8"><div class="admin-empty">Không có phiếu kho trong khoảng lọc.</div></td></tr><?php endif; ?>
    </tbody></table></div>
  <?php if ($pages > 1): ?><div class="cas-admin-stack mt-3"><?php for ($p = 1; $p <= $pages; $p++): ?><a class="<?= $p === $page ? 'admin-btn' : 'admin-btn-light' ?>" href="<?= cas_e($query($p)) ?>"><?= $p ?></a><?php endfor; ?></div><?php endif; ?>
</section>
<?php
    cas_ops_end();
}
