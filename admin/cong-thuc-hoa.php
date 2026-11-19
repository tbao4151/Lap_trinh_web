<?php
declare(strict_types=1);
require __DIR__.'/_ops.php';
cas_page_roles(['admin','owner'],'Định mức hoa');

cas_ops_post([
 'recipe_save'=>static function() use ($db,$user): string {
     cas_require_permission('inventory.items','recipe_save');
     $product=cas_in_id('product_id');
     $item=cas_in_id('item_id');
     $quantity=cas_in_decimal('quantity',0.01,100000,'Định mức nguyên liệu cho một sản phẩm');
     $found=cas_value('SELECT COUNT(*) FROM products WHERE product_id=?',[$product]);
     if(!$found)throw new DomainException('Sản phẩm không tồn tại.');
     $status=cas_value("SELECT status FROM inventory_items WHERE item_id=?",[$item]);
     if($status!=='active')throw new DomainException('Chỉ gán nguyên liệu kho đang sử dụng.');
     $db->prepare('INSERT INTO product_recipes(product_id,item_id,quantity) VALUES(?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)')
        ->execute([$product,$item,$quantity]);
     cas_audit('recipe_save','product',$product,['item_id'=>$item,'quantity'=>$quantity]);
     return 'Đã cập nhật định mức nguyên liệu.';
 },
 'recipe_delete'=>static function() use ($db,$user): string {
     cas_require_permission('inventory.items','recipe_delete');
     $product=cas_in_id('product_id');$item=cas_in_id('item_id');
     $db->prepare('DELETE FROM product_recipes WHERE product_id=? AND item_id=?')->execute([$product,$item]);
     cas_audit('recipe_delete','product',$product,['item_id'=>$item]);
     return 'Đã xóa nguyên liệu khỏi công thức (không thay đổi các đơn đã xuất kho).';
 },
]);

$products=cas_rows('SELECT product_id,name,sku,status FROM products ORDER BY product_id');
$materials=cas_rows("SELECT item_id,name,unit,quantity,status FROM inventory_items ORDER BY item_type,name");
$selected=(int)($_GET['product_id']??($products[0]['product_id']??0));
$selectedProduct=null;
foreach($products as $p)if((int)$p['product_id']===$selected)$selectedProduct=$p;
$recipe=$selectedProduct?cas_rows('SELECT r.*,i.name,i.unit,i.quantity stock,i.status FROM product_recipes r JOIN inventory_items i ON i.item_id=r.item_id WHERE r.product_id=? ORDER BY i.name',[$selected]):[];
cas_ops_start('Định mức nguyên liệu','Công thức cho một bó / giỏ hoa. Trừ kho khi chuyển đơn sang Đang chuẩn bị; hoàn nguyên liệu nếu hủy đơn.','bi-list-check');
?>
<section class="admin-card admin-section">
<h2 class="admin-card-title">Chọn mẫu hoa</h2>
<form method="get" class="cas-admin-stack mt-3">
 <select name="product_id" class="admin-select" aria-label="Mẫu hoa" required>
 <?php foreach($products as $p): ?>
 <option value="<?= (int)$p['product_id'] ?>" <?= (int)$p['product_id']===$selected?'selected':'' ?>><?= cas_e($p['sku'].' – '.$p['name']) ?></option>
 <?php endforeach; ?>
 </select><button class="admin-btn" type="submit">Xem công thức</button>
</form>
<p class="cas-muted mt-2">Nếu một mẫu hoa chưa có định mức, hệ thống chặn chuyển đơn chứa mẫu đó sang “Đang chuẩn bị” để tránh phát sinh tồn kho sai. Lượng nguyên liệu xuất được lưu riêng theo đơn.</p>
</section>
<?php if($selectedProduct): ?>
<section class="admin-grid-2 admin-section">
<article class="admin-card">
<h2 class="admin-card-title">Thêm / thay định mức</h2>
<form method="post" class="admin-form-grid mt-3">
<?= cas_csrf_form() ?><input type="hidden" name="action" value="recipe_save"><input type="hidden" name="product_id" value="<?= $selected ?>">
<div class="full"><label class="admin-label" for="recipe_item">Nguyên liệu</label>
<select id="recipe_item" name="item_id" class="admin-select" required>
<?php foreach($materials as $m): if($m['status']!=='active')continue; ?>
<option value="<?= (int)$m['item_id'] ?>"><?= cas_e($m['name']) ?> (<?= cas_e($m['unit']) ?>; tồn <?= cas_e((string)$m['quantity']) ?>)</option>
<?php endforeach; ?>
</select></div>
<div><label class="admin-label" for="recipe_qty">Số lượng cần cho 1 sản phẩm</label><input id="recipe_qty" name="quantity" type="number" class="admin-input" min="0.01" max="100000" step="0.01" required></div>
<div class="full"><button class="admin-btn" type="submit">Lưu định mức</button></div>
</form></article>
<article class="admin-card"><h2 class="admin-card-title">Công thức hiện tại: <?= cas_e($selectedProduct['name']) ?></h2>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Nguyên liệu</th><th>Định mức / sản phẩm</th><th>Tồn kho</th><th></th></tr></thead><tbody>
<?php foreach($recipe as $r): ?><tr>
<td><?= cas_e($r['name']) ?></td>
<td><?= cas_e((string)$r['quantity'].' '.$r['unit']) ?></td>
<td><?= cas_e((string)$r['stock']) ?> <?= cas_e($r['unit']) ?></td>
<td><form method="post" data-confirm="Xóa nguyên liệu khỏi công thức?" class="cas-admin-table-form">
<?= cas_csrf_form() ?><input type="hidden" name="action" value="recipe_delete"><input type="hidden" name="product_id" value="<?= $selected ?>"><input type="hidden" name="item_id" value="<?= (int)$r['item_id'] ?>"><button class="admin-btn-light" type="submit">Xóa</button></form></td>
</tr><?php endforeach; ?>
<?php if(!$recipe): ?><tr><td colspan="4">Chưa có định mức; chưa thể xuất kho tự động cho sản phẩm này.</td></tr><?php endif; ?>
</tbody></table></div></article>
</section>
<?php endif; ?>
<?php cas_ops_end(); ?>
