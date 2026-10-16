<?php
declare(strict_types=1);
/** Inventory lifecycle bound to order state changes.
 * Called with a transaction open and the order row locked FOR UPDATE.
 * Quantities are computed in hundredths to avoid floating-point drift.
 */
require_once __DIR__.'/bootstrap.php';

function cas_inventory_quantity_units(mixed $raw): int {
    if (!is_numeric($raw)) throw new DomainException('Số lượng nguyên liệu không hợp lệ.');
    $number=(float)$raw;
    $units=(int)round($number*100);
    if ($units<=0 || abs($number*100-$units)>0.001) throw new DomainException('Định mức nguyên liệu phải lớn hơn 0 và có tối đa hai chữ số lẻ.');
    return $units;
}

/** Only makes sense when confirmed => preparing, not at checkout. */
function cas_order_consume_inventory(PDO $db,int $orderId,int $actorId): void {
    if (!$db->inTransaction()) throw new LogicException('Inventory must be written in the order transaction.');
    $q=$db->prepare('SELECT COUNT(*) FROM order_inventory_usage WHERE order_id=?');
    $q->execute([$orderId]);
    if((int)$q->fetchColumn()>0)throw new DomainException('Đơn này đã được trừ nguyên liệu, không được trừ lại.');

    $q=$db->prepare('SELECT oi.product_id,oi.quantity,p.name FROM order_items oi JOIN products p ON p.product_id=oi.product_id WHERE oi.order_id=?');
    $q->execute([$orderId]);$products=$q->fetchAll();
    if(!$products)throw new DomainException('Đơn hàng không có sản phẩm.');
    $need=[];
    $recipe=$db->prepare('SELECT item_id,quantity FROM product_recipes WHERE product_id=? ORDER BY item_id');
    foreach($products as $product){
        $recipe->execute([$product['product_id']]);$materials=$recipe->fetchAll();
        if(!$materials)throw new DomainException('Mẫu hoa "'.$product['name'].'" chưa có công thức nguyên liệu. Vui lòng cấu hình định mức trong Admin.');
        foreach($materials as $material){
            $id=(int)$material['item_id'];
            $need[$id]=($need[$id]??0)+cas_inventory_quantity_units($material['quantity'])*(int)$product['quantity'];
        }
    }
    ksort($need,SORT_NUMERIC);
    $lock=$db->prepare('SELECT name,unit,quantity,status,unit_cost FROM inventory_items WHERE item_id=? FOR UPDATE');
    $update=$db->prepare('UPDATE inventory_items SET quantity=?,updated_at=NOW() WHERE item_id=?');
    $insert=$db->prepare('INSERT INTO inventory_transactions(item_id,user_id,transaction_type,quantity,quantity_before,quantity_after,note,request_token) VALUES(?,?,?,?,?,?,?,?)');
    $usage=$db->prepare('INSERT INTO order_inventory_usage(order_id,item_id,quantity,actor_id,unit_cost_snapshot,cost_amount_snapshot) VALUES(?,?,?,?,?,?)');
    foreach($need as $id=>$units){
        $lock->execute([$id]);$item=$lock->fetch();
        if(!$item||$item['status']!=='active')throw new DomainException('Nguyên liệu #'.$id.' đã bị ngừng dùng hoặc không tồn tại.');
        $before=(int)round((float)$item['quantity']*100);
        if($before<$units)throw new DomainException('Không đủ tồn kho "'.$item['name'].'": cần '.number_format($units/100,2).' '.$item['unit'].', còn '.number_format($before/100,2).'.');
        $quantity=$units/100;$after=($before-$units)/100;
        $update->execute([$after,$id]);
        $insert->execute([$id,$actorId,'out',-$quantity,$before/100,$after,'Xuất nguyên liệu cho đơn #'.$orderId,substr(hash('sha256','order-out:'.$orderId.':'.$id),0,32)]);
        // Snapshot the current unit cost at the moment ingredients leave stock.
        $cost=(int)round((float)$item['unit_cost']*100);
        if($cost<0)throw new DomainException('Giá vốn nguyên liệu không hợp lệ.');
        $amount=round(($units/100)*($cost/100),2);
        $usage->execute([$orderId,$id,$quantity,$actorId,$cost/100,$amount]);
    }
}

/** Reverses the immutable consumption snapshot, not the current recipe. */
function cas_order_restore_inventory(PDO $db,int $orderId,int $actorId): void {
    if (!$db->inTransaction()) throw new LogicException('Inventory must be written in the order transaction.');
    $q=$db->prepare('SELECT item_id,quantity FROM order_inventory_usage WHERE order_id=? AND restored_at IS NULL ORDER BY item_id FOR UPDATE');
    $q->execute([$orderId]);$entries=$q->fetchAll();
    $lock=$db->prepare('SELECT quantity FROM inventory_items WHERE item_id=? FOR UPDATE');
    $update=$db->prepare('UPDATE inventory_items SET quantity=?,updated_at=NOW() WHERE item_id=?');
    $insert=$db->prepare('INSERT INTO inventory_transactions(item_id,user_id,transaction_type,quantity,quantity_before,quantity_after,note,request_token) VALUES(?,?,?,?,?,?,?,?)');
    $mark=$db->prepare('UPDATE order_inventory_usage SET restored_at=NOW() WHERE order_id=? AND item_id=? AND restored_at IS NULL');
    foreach($entries as $entry){
        $id=(int)$entry['item_id'];$units=cas_inventory_quantity_units($entry['quantity']);
        $lock->execute([$id]);$row=$lock->fetch();
        if(!$row)throw new DomainException('Nguyên liệu đã ghi nhận xuất không còn tồn tại: #'.$id);
        $before=(int)round((float)$row['quantity']*100);
        $after=($before+$units)/100;
        $update->execute([$after,$id]);
        $insert->execute([$id,$actorId,'in',$units/100,$before/100,$after,'Hoàn nguyên liệu do hủy đơn #'.$orderId,substr(hash('sha256','order-return:'.$orderId.':'.$id),0,32)]);
        $mark->execute([$orderId,$id]);
    }
}
