<?php
require_once __DIR__.'/includes/customer.php';
$user=cas_require_user();
$count=cas_db()->prepare('SELECT COUNT(*) FROM orders o JOIN customers c ON c.customer_id=o.customer_id WHERE c.user_id=?');
$count->execute([$user['user_id']]);$total=(int)$count->fetchColumn();$pages=max(1,(int)ceil($total/10));
$page=max(1,min($pages,(int)(filter_var($_GET['page']??1,FILTER_VALIDATE_INT)?:1)));$offset=($page-1)*10;
$q=cas_db()->prepare("SELECT o.order_id,o.order_code,o.created_at,o.recipient_name,o.receive_date,o.receive_time,o.delivery_method,o.total_amount,o.order_status FROM orders o JOIN customers c ON c.customer_id=o.customer_id WHERE c.user_id=? ORDER BY o.created_at DESC,o.order_id DESC LIMIT 10 OFFSET $offset");
$q->execute([$user['user_id']]);$orders=$q->fetchAll();
$items=[];
if($orders){
 $ids=array_column($orders,'order_id');$marks=implode(',',array_fill(0,count($ids),'?'));
 $q=cas_db()->prepare("SELECT i.order_id,i.quantity,i.unit_price,p.name FROM order_items i JOIN products p ON p.product_id=i.product_id WHERE i.order_id IN ($marks)");
 $q->execute($ids);foreach($q as $item)$items[$item['order_id']][]=$item;
}
cas_page_start("Lịch sử đơn hàng | CÁ'S HOA", 'orders', "Xem các đơn hàng đã đặt.");
?>

  <main class="cas-page content-page">
    <div class="cas-container">
      <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
          <p class="cas-eyebrow">Tài khoản khách hàng</p>
          <h1 class="cas-title">Lịch sử đơn hàng</h1>
          <p class="cas-subtitle">Danh sách các đơn đã tạo, kèm trạng thái và thông tin người nhận.</p>
        </div>
        <span class="stat-chip"><span id="orderCount"><?= $total ?></span> đơn hàng</span>
      </div>

      <div class="row g-4">
        <div class="col-lg-9"><div id="orderHistory">
<?php if(!$orders): ?><div class="customer-empty"><i class="bi bi-receipt"></i><h2 class="font-display fs-4">Chưa có đơn hàng nào</h2><p class="cas-muted mb-3">Chưa có đơn hàng được liên kết với tài khoản này.</p><a class="btn-cas-primary" href="san-pham.php">Chọn một bó hoa</a></div><?php endif; ?>
<?php foreach($orders as $o): ?>
<article class="order-history-card mb-3">
  <div class="d-flex justify-content-between flex-wrap gap-2"><strong><?= cas_e($o['order_code']) ?></strong><span class="stat-chip"><?= cas_e(cas_order_status_label($o['order_status'])) ?></span></div>
  <div class="cas-muted small mt-2"><?= cas_e($o['created_at']) ?> · <?= cas_e($o['recipient_name']) ?></div>
  <div class="fw-bold mt-2"><?= number_format((float)$o['total_amount'],0,',','.') ?> ₫</div>
  <p class="small mt-2 mb-0"><?= $o['delivery_method']==='pickup'?'Nhận tại cửa hàng':'Giao tận nơi' ?> · <?= cas_e($o['receive_date']) ?> · <?= cas_e($o['receive_time']) ?></p>
  <ul class="customer-order-items small"><?php foreach($items[$o['order_id']]??[] as $item): ?><li><?= cas_e($item['name']) ?> × <?= (int)$item['quantity'] ?> — <?= number_format((float)$item['unit_price'],0,',','.') ?> ₫/mẫu</li><?php endforeach; ?></ul>
  <a class="btn-cas-outline mt-3" href="tra-cuu-don-hang.php?code=<?= urlencode($o['order_code']) ?>">Tra cứu đơn</a>
</article>
<?php endforeach; ?>
<nav class="customer-pager" aria-label="Trang lịch sử đơn hàng">
<?php if($page>1): ?><a class="btn-cas-outline" href="?page=<?= $page-1 ?>">Trang trước</a><?php endif; ?>
<span>Trang <?= $page ?> / <?= $pages ?></span>
<?php if($page<$pages): ?><a class="btn-cas-outline" href="?page=<?= $page+1 ?>">Trang sau</a><?php endif; ?>
</nav>
</div></div>
        <div class="col-lg-3">
          <aside class="content-card mb-3">
<?php cas_account_nav('orders', false); ?>
          </aside>
          <aside class="content-card">
            <h2 class="font-display fs-5">Cần hỗ trợ đơn hàng?</h2>
            <p class="cas-muted small lh-lg">Chuẩn bị mã đơn và số điện thoại người nhận để shop kiểm tra nhanh hơn.</p>
            <a class="btn-cas-outline w-100" href="tra-cuu-don-hang.php">Tra cứu đơn</a>
            <a class="btn-cas-primary w-100 mt-2" href="lien-he.php">Liên hệ shop</a>
          </aside>
        </div>
      </div>
    </div>
  </main>
<?php cas_page_end(); ?>
