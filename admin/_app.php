<?php
declare(strict_types=1);
require_once __DIR__.'/_actions.php';
$titles=[
 'dashboard'=>'Tổng quan vận hành','orders'=>'Quản lý đơn hàng','delivery'=>'Giao hàng',
 'customers'=>'Khách hàng','products'=>'Sản phẩm','product_add'=>'Thêm sản phẩm',
 'product_edit'=>'Sửa sản phẩm','product_quick'=>'Quản lý nhanh','taxonomy'=>'Phân loại & bộ lọc',
 'inventory'=>'Kho tổng','reports'=>'Báo cáo doanh thu'
];
$icons=[
 'dashboard'=>'bi-grid','orders'=>'bi-clipboard2-check','delivery'=>'bi-truck',
 'customers'=>'bi-people','products'=>'bi-flower2','product_add'=>'bi-plus-square',
 'product_edit'=>'bi-pencil-square','product_quick'=>'bi-lightning-charge','taxonomy'=>'bi-tags',
 'inventory'=>'bi-building','reports'=>'bi-bar-chart'
];
$roleLabels=['staff'=>'Nhân viên','admin'=>'Quản trị viên','owner'=>'Chủ sở hữu'];
$statusTones=['pending_confirmation'=>'warning','confirmed'=>'success','preparing'=>'warning','ready'=>'warning','delivering'=>'warning','completed'=>'success','cancelled'=>'danger'];
$title=$titles[$cas_page]??'CÁ\'S HOA Admin';
$icon=$icons[$cas_page]??'bi-flower2';
?>
<!doctype html><html lang="vi"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= cas_e($title) ?> | CÁ'S HOA Admin</title>
<link href="../assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
<link href="../assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body data-admin-page="<?= cas_e($cas_page) ?>">
<div class="admin-app">
<aside class="admin-sidebar" id="adminSidebar">
  <div class="admin-sidebar-head">
    <a class="admin-brand" href="index.php"><span class="admin-brand-mark"><i class="bi bi-flower1" aria-hidden="true"></i></span><span><strong>CÁ'S HOA</strong><small>Admin PHP/MySQL</small></span></a>
    <button class="admin-sidebar-close" id="adminSidebarClose" type="button" aria-label="Đóng menu"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
  </div>
  <?php require __DIR__.'/_nav.php'; ?>
</aside>
<button class="admin-overlay" id="adminOverlay" type="button" aria-label="Đóng menu"></button>
<div class="admin-workspace">
<header class="admin-topbar">
 <div class="admin-topbar-left">
   <button class="admin-menu-button" id="adminMenuOpen" type="button" aria-controls="adminSidebar" aria-expanded="false" aria-label="Mở menu"><i class="bi bi-list" aria-hidden="true"></i></button>
   <div><div class="admin-topbar-title"><?= cas_e($title) ?></div><span class="admin-role"><?= cas_e($roleLabels[$user['role']]??$user['role']) ?></span></div>
 </div>
 <div class="admin-topbar-actions">
   <a class="admin-store-link" href="../index.php" aria-label="Về cửa hàng" title="Về cửa hàng"><i class="bi bi-shop" aria-hidden="true"></i><span>Về cửa hàng</span></a>
   <a class="admin-btn-light" href="tai-khoan.php" aria-label="Tài khoản của bạn" title="Tài khoản của bạn"><i class="bi bi-person-circle" aria-hidden="true"></i><span><?= cas_e($user['full_name']) ?></span></a>
   <form action="../dang-xuat.php" method="post" class="m-0"><?= cas_csrf_form() ?><button class="admin-btn-light" type="submit" aria-label="Đăng xuất" title="Đăng xuất"><i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Đăng xuất</span></button></form>
 </div>
</header>
<main class="admin-content">
<section class="admin-hero"><div><h1><?= cas_e($title) ?></h1>
<p>Hệ thống quản trị CÁ'S HOA — dữ liệu vận hành theo thời gian thực.</p></div><div class="admin-hero-icon"><i class="bi <?= cas_e($icon) ?>" aria-hidden="true"></i></div></section>
<?php if($error!==''): ?><div class="alert alert-danger admin-section" role="alert"><?= cas_e($error) ?></div><?php endif; ?>
<?php if($notice!==''): ?><div class="alert alert-success admin-section" role="status"><?= cas_e($notice) ?></div><?php endif; ?>
<?php
switch($cas_page):
case 'dashboard':
$kpis=[
 'Đơn mới 24 giờ'=>(int)$db->query('SELECT COUNT(*) FROM orders WHERE created_at >= NOW()-INTERVAL 1 DAY')->fetchColumn(),
 'Chờ xác nhận'=>(int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status='pending_confirmation'")->fetchColumn(),
 'Đang chuẩn bị'=>(int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('confirmed','preparing','ready')")->fetchColumn(),
 'Giao hôm nay'=>(int)$db->query('SELECT COUNT(*) FROM deliveries WHERE DATE(scheduled_at)=CURDATE()')->fetchColumn(),
 'Đơn đã thanh toán'=>(int)$db->query("SELECT COUNT(*) FROM orders WHERE payment_status='paid'")->fetchColumn(),
 'Nguyên liệu sắp hết'=>(int)$db->query("SELECT COUNT(*) FROM inventory_items WHERE quantity<=min_quantity AND status='active'")->fetchColumn(),
 'Doanh thu hoàn tất'=>(int)$db->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE order_status='completed' AND payment_status='paid'")->fetchColumn()
];
$latest=cas_admin_rows($db,"SELECT o.order_code,o.total_amount,o.order_status,o.created_at,c.full_name FROM orders o JOIN customers c ON c.customer_id=o.customer_id ORDER BY o.order_id DESC LIMIT 10");
?>
<div class="cas-admin-metrics admin-section">
<?php foreach($kpis as $label=>$count): ?><article class="cas-admin-metric"><span><?= cas_e($label) ?></span><strong><?= number_format($count,0,',','.') ?><?= $label==='Doanh thu hoàn tất'?' ₫':'' ?></strong></article><?php endforeach; ?>
</div>
<section class="admin-card admin-section">
<div class="cas-admin-stack justify-content-between mb-2"><h2 class="admin-card-title mb-0">Đơn gần đây</h2><a class="admin-btn-light" href="don-hang.php">Quản lý đơn hàng</a></div>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Giá trị</th><th>Trạng thái</th></tr></thead><tbody>
<?php foreach($latest as $o): $tone=$statusTones[$o['order_status']]??''; ?><tr><td><strong><?= cas_e($o['order_code']) ?></strong><div class="cas-muted"><?= cas_e($o['created_at']) ?></div></td><td><?= cas_e($o['full_name']) ?></td><td class="admin-money"><?= cas_money($o['total_amount']) ?></td><td><span class="admin-status<?= $tone?' '.$tone:'' ?>"><?= cas_e($statuses[$o['order_status']]??$o['order_status']) ?></span></td></tr><?php endforeach; ?>
<?php if(!$latest): ?><tr><td colspan="4"><div class="admin-empty">Chưa có đơn hàng nào.</div></td></tr><?php endif; ?>
</tbody></table></div></section>
<?php break;
case 'orders':
$filter=(string)($_GET['status']??'all');
$args=[];$sql="SELECT o.*,c.full_name AS customer_name,c.phone AS customer_phone FROM orders o JOIN customers c ON c.customer_id=o.customer_id";
if(isset($statuses[$filter])){$sql.=" WHERE o.order_status=?";$args[]=$filter;}
$sql.=" ORDER BY o.created_at DESC,o.order_id DESC LIMIT 200";
$rows=cas_admin_rows($db,$sql,$args);
?>
<section class="admin-card admin-section">
<form method="get" class="cas-admin-stack mb-3"><label class="admin-label mb-0" for="orderStatusSelect">Trạng thái:</label><select id="orderStatusSelect" class="admin-select" name="status"><?php foreach(['all'=>'Tất cả']+$statuses as $k=>$lbl): ?><option value="<?= cas_e((string)$k) ?>"<?= (string)$k===$filter?' selected':'' ?>><?= cas_e((string)$lbl) ?></option><?php endforeach; ?></select><button class="admin-btn-light" type="submit">Lọc đơn</button><span class="admin-badge"><?= count($rows) ?> đơn</span></form>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Mã đơn / khách</th><th>Người nhận</th><th>Giá trị</th><th>Cập nhật</th></tr></thead><tbody>
<?php foreach($rows as $o): $tone=$statusTones[$o['order_status']]??''; ?><tr>
<td><strong><?= cas_e($o['order_code']) ?></strong> <span class="admin-status<?= $tone?' '.$tone:'' ?>"><?= cas_e($statuses[$o['order_status']]??$o['order_status']) ?></span><div class="mt-1"><?= cas_e($o['customer_name']) ?> — <?= cas_e($o['customer_phone']) ?></div><div class="cas-muted"><?= cas_e($o['created_at']) ?></div></td>
<td><?= cas_e($o['recipient_name']) ?><div class="cas-muted"><?= cas_e($o['receive_date']) ?> · <?= cas_e($o['receive_time']) ?></div></td>
<td class="admin-money"><?= cas_money($o['total_amount']) ?></td>
<td><form method="post" class="cas-admin-table-form"><?= cas_csrf_form() ?><input type="hidden" name="action" value="order_update"><input type="hidden" name="id" value="<?= (int)$o['order_id'] ?>">
<?= str_replace('<select class="admin-select"','<select class="admin-select" aria-label="Trạng thái đơn '.cas_e($o['order_code']).'"',cas_admin_select('status',cas_order_choices($o['order_status'],$o['delivery_method']),$o['order_status'])) ?>
<?php if(in_array($user['role'],['admin','owner'],true)): ?>
<?= str_replace('<select class="admin-select"','<select class="admin-select" aria-label="Thanh toán đơn '.cas_e($o['order_code']).'"',cas_admin_select('payment_status',$payments,$o['payment_status'])) ?>
<?php else: ?><input type="hidden" name="payment_status" value="<?= cas_e($o['payment_status']) ?>"><span class="admin-badge"><?= cas_e($payments[$o['payment_status']]??$o['payment_status']) ?></span><?php endif; ?>
<input class="admin-input" name="reason" maxlength="255" aria-label="Lý do thay đổi trạng thái" placeholder="Lý do nếu hủy/hoàn tác">
<button class="admin-btn-light" type="submit">Lưu</button></form></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="4"><div class="admin-empty">Không có đơn hàng phù hợp bộ lọc.</div></td></tr><?php endif; ?>
</tbody></table></div></section>
<?php break;
case 'delivery':
$rows=cas_admin_rows($db,"SELECT d.delivery_id,d.delivery_status,d.scheduled_at,o.order_code,o.recipient_name,o.recipient_phone,o.delivery_address FROM deliveries d JOIN orders o ON o.order_id=d.order_id ORDER BY d.delivery_id DESC LIMIT 200");
?>
<section class="admin-card admin-section"><div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Mã đơn</th><th>Người nhận</th><th>Địa chỉ / lịch</th><th>Trạng thái</th></tr></thead><tbody>
<?php foreach($rows as $d): ?><tr><td><?= cas_e($d['order_code']) ?></td><td><?= cas_e($d['recipient_name']) ?><br><?= cas_e($d['recipient_phone']) ?></td>
<td><?= cas_e($d['delivery_address']) ?><br><?= cas_e($d['scheduled_at']) ?></td>
<td><form method="post" class="cas-admin-table-form"><?= cas_csrf_form() ?><input type="hidden" name="action" value="delivery_update"><input type="hidden" name="id" value="<?= (int)$d['delivery_id'] ?>">
<?= str_replace('<select class="admin-select"','<select class="admin-select" aria-label="Trạng thái giao"',cas_admin_select('status',$deliveries,$d['delivery_status'])) ?><button class="admin-btn-light" type="submit">Lưu</button></form></td>
</tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="4"><div class="admin-empty">Chưa có lịch giao hàng.</div></td></tr><?php endif; ?>
</tbody></table></div></section>
<?php break;
case 'customers':
$search=trim((string)($_GET['q']??''));$args=[];
$sql="SELECT c.customer_id,c.full_name,c.phone,c.email,c.address,COUNT(o.order_id) AS orders_count,COALESCE(SUM(o.total_amount),0) AS amount FROM customers c LEFT JOIN orders o ON o.customer_id=c.customer_id";
if($search!==''){$sql.=" WHERE c.full_name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?";$args=array_fill(0,3,'%'.$search.'%');}
$sql.=" GROUP BY c.customer_id ORDER BY c.customer_id DESC LIMIT 200";
$rows=cas_admin_rows($db,$sql,$args);
?>
<section class="admin-card admin-section"><form method="get" class="cas-admin-stack mb-3"><input name="q" class="admin-input" aria-label="Tìm khách hàng theo tên, SĐT hoặc email" value="<?= cas_e($search) ?>" placeholder="Tên, SĐT, email"><button class="admin-btn-light" type="submit">Tìm</button><span class="admin-badge"><?= count($rows) ?> khách</span></form>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Khách hàng</th><th>SĐT / email</th><th>Địa chỉ</th><th>Số đơn</th><th>Tổng đơn</th></tr></thead><tbody>
<?php foreach($rows as $c): ?><tr><td><strong><?= cas_e($c['full_name']) ?></strong></td><td><?= cas_e($c['phone']) ?><div class="cas-muted"><?= cas_e($c['email']) ?></div></td><td><?= cas_e($c['address']?:'—') ?></td><td><?= (int)$c['orders_count'] ?></td><td><strong><?= cas_money($c['amount']) ?></strong></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="5"><div class="admin-empty">Không tìm thấy khách hàng phù hợp.</div></td></tr><?php endif; ?>
</tbody></table></div></section>
<?php break;
case 'products':
$search=trim((string)($_GET['q']??''));$args=[];
$sql="SELECT p.*,c.name AS category_name FROM products p LEFT JOIN categories c ON c.category_id=p.category_id";
if($search!==''){$sql.=" WHERE p.name LIKE ? OR p.sku LIKE ?";$args=['%'.$search.'%','%'.$search.'%'];}
$sql.=" ORDER BY p.product_id DESC LIMIT 300";
$rows=cas_admin_rows($db,$sql,$args);
?>
<section class="admin-card admin-section">
<form method="get" class="cas-admin-stack mb-3"><input class="admin-input" name="q" aria-label="Tìm sản phẩm theo tên hoặc SKU" value="<?= cas_e($search) ?>" placeholder="Tên hoặc SKU"><button class="admin-btn-light" type="submit">Tìm kiếm</button><span class="admin-badge"><?= count($rows) ?> sản phẩm</span><a class="admin-btn" href="them-san-pham.php">Thêm sản phẩm</a></form>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Ảnh</th><th>Sản phẩm</th><th>Loại</th><th>Giá</th><th>Hiển thị</th><th>Thao tác</th></tr></thead><tbody>
<?php foreach($rows as $p): ?><tr>
<td><img class="admin-thumb" src="../<?= cas_e($p['image']) ?>" width="65" height="65" alt=""></td>
<td><strong><?= cas_e($p['name']) ?></strong><div class="cas-muted"><?= cas_e($p['sku']) ?></div></td>
<td><?= cas_e($p['category_name']) ?> / <?= cas_e($p['product_type']) ?></td><td><?= cas_money($p['price']) ?></td><td><?= cas_e($p['status']) ?></td>
<td><a class="admin-btn-light" href="sua-san-pham.php?id=<?= (int)$p['product_id'] ?>">Sửa</a></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php break;
case 'product_add':
case 'product_edit':
$edit=$cas_page==='product_edit';$product=[];
if($edit){
 $id=filter_var($_GET['id']??$_POST['id']??null,FILTER_VALIDATE_INT);
 if($id===false||$id<1){http_response_code(404);echo '<p class="alert alert-danger">Mã sản phẩm không hợp lệ.</p>';break;}
 $q=$db->prepare('SELECT * FROM products WHERE product_id=?');$q->execute([$id]);$product=$q->fetch()?:[];
 if(!$product){http_response_code(404);echo '<p class="alert alert-danger">Không tìm thấy sản phẩm.</p>';break;}
}
$categories=cas_admin_rows($db,"SELECT category_id,name FROM categories WHERE status='active' ORDER BY name");
$tones=cas_admin_rows($db,"SELECT tone_id,name FROM tones WHERE status='active' ORDER BY name");
$occasions=cas_admin_rows($db,"SELECT occasion_id,name FROM occasions WHERE status='active' ORDER BY name");
$images=glob(__DIR__.'/../assets/images/*.png')?:[];
?>
<section class="admin-card admin-section"><form method="post" class="admin-form-grid">
<?= cas_csrf_form() ?><input type="hidden" name="action" value="product_save">
<?php if($edit): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>
<?php foreach(['name'=>'Tên sản phẩm','sku'=>'SKU','slug'=>'Slug (để trống sẽ tự tạo)','price'=>'Giá bán (VND)'] as $field=>$label): ?>
<div><label class="admin-label" for="f_<?= cas_e($field) ?>"><?= cas_e($label) ?></label><input id="f_<?= cas_e($field) ?>" class="admin-input" name="<?= cas_e($field) ?>" value="<?= cas_e((string)($product[$field]??'')) ?>" <?= $field==='price'?'type="number" min="0" max="100000000"':'maxlength="190"' ?> <?= in_array($field,['name','sku','price'],true)?'required':'' ?>></div>
<?php endforeach; ?>
<div><label class="admin-label" for="f_product_type">Loại sản phẩm</label><?= str_replace('<select class="admin-select"','<select id="f_product_type" class="admin-select"',cas_admin_select('product_type',['bouquet'=>'Bó hoa','basket'=>'Giỏ hoa'],(string)($product['product_type']??'bouquet'))) ?></div>
<?php foreach([['category_id',$categories],['tone_id',$tones],['occasion_id',$occasions]] as [$field,$opts]): ?>
<div><label class="admin-label" for="f_<?= cas_e($field) ?>"><?= cas_e($field) ?></label><select id="f_<?= cas_e($field) ?>" name="<?= cas_e($field) ?>" class="admin-select"><option value="">Không chọn</option>
<?php foreach($opts as $o): $pk=$field; ?><option value="<?= (int)$o[$pk] ?>" <?= (string)($product[$field]??'')===(string)$o[$pk]?'selected':'' ?>><?= cas_e($o['name']) ?></option><?php endforeach; ?>
</select></div>
<?php endforeach; ?>
<div><label class="admin-label" for="f_image">Ảnh sản phẩm có sẵn</label><select id="f_image" name="image" class="admin-select">
<?php foreach($images as $image): $filename=basename($image); ?>
<option value="<?= cas_e($filename) ?>" <?= basename((string)($product['image']??'lam-tinh.png'))===$filename?'selected':'' ?>><?= cas_e($filename) ?></option>
<?php endforeach; ?></select></div>
<div><label class="admin-label" for="f_status">Trạng thái</label><?= str_replace('<select class="admin-select"','<select id="f_status" class="admin-select"',cas_admin_select('status',['active'=>'Đang bán','hidden'=>'Ẩn'],(string)($product['status']??'active'))) ?></div>
<div class="full"><label class="admin-label" for="f_description">Mô tả</label><textarea id="f_description" class="admin-textarea" name="description" rows="3"><?= cas_e($product['description']??'') ?></textarea></div>
<div class="full"><label class="admin-label" for="f_composition">Thành phần</label><textarea id="f_composition" class="admin-textarea" name="composition" rows="3"><?= cas_e($product['composition']??'') ?></textarea></div>
<div class="full cas-admin-stack">
<label class="admin-check"><input type="checkbox" name="featured" <?= !empty($product['featured'])?'checked':'' ?>> Sản phẩm nổi bật</label>
<label class="admin-check"><input type="checkbox" name="today" <?= !empty($product['today'])?'checked':'' ?>> Mẫu hôm nay</label></div>
<div class="full"><button class="admin-btn" type="submit">Lưu sản phẩm</button> <a class="admin-btn-light" href="san-pham.php">Hủy</a></div>
</form></section>
<?php break;
case 'product_quick':
$rows=cas_admin_rows($db,'SELECT product_id,name,sku,price,status FROM products ORDER BY product_id DESC LIMIT 200');
?>
<section class="admin-card admin-section"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Sản phẩm</th><th>Giá</th><th>Trạng thái / lưu</th></tr></thead><tbody>
<?php foreach($rows as $p): ?><tr><td><?= cas_e($p['name']) ?> — <?= cas_e($p['sku']) ?></td><td><?= cas_money($p['price']) ?></td><td>
<form class="cas-admin-table-form" method="post"><?= cas_csrf_form() ?><input type="hidden" name="action" value="quick_save"><input type="hidden" name="id" value="<?= (int)$p['product_id'] ?>">
<input class="admin-input" type="number" name="price" min="0" aria-label="Giá bán <?= cas_e($p['name']) ?>" value="<?= (int)$p['price'] ?>">
<?= str_replace('<select class="admin-select"','<select class="admin-select" aria-label="Trạng thái '.cas_e($p['name']).'"',cas_admin_select('status',['active'=>'Đang bán','hidden'=>'Ẩn'],$p['status'])) ?>
<button class="admin-btn-light" type="submit">Lưu</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php break;
case 'taxonomy':
foreach(['categories'=>'Loại hoa','tones'=>'Tông màu','occasions'=>'Dịp tặng'] as $table=>$label):
 $rows=cas_admin_rows($db,"SELECT * FROM {$table} ORDER BY name");
?>
<section class="admin-card admin-section"><h2 class="admin-card-title"><?= cas_e($label) ?></h2>
<form method="post" class="cas-admin-stack my-3"><?= cas_csrf_form() ?><input type="hidden" name="action" value="taxonomy_save"><input type="hidden" name="table" value="<?= cas_e($table) ?>">
<input class="admin-input" name="name" maxlength="100" aria-label="Tên <?= cas_e($label) ?>" required placeholder="Tên <?= cas_e($label) ?>"><button class="admin-btn" type="submit">Thêm</button></form>
<div class="cas-admin-stack"><?php foreach($rows as $r): ?><span class="admin-badge"><?= cas_e($r['name']) ?></span><?php endforeach; ?></div></section>
<?php endforeach;break;
case 'inventory':
$items=cas_admin_rows($db,'SELECT * FROM inventory_items ORDER BY item_type,name');
$qty=0;$low=0;$value=0;
foreach($items as $i){$qty+=(float)$i['quantity'];if((float)$i['quantity']<=(float)$i['min_quantity'])$low++;$value+=(float)$i['quantity']*(float)$i['unit_cost'];}
?>
<div class="cas-admin-metrics admin-section">
<article class="cas-admin-metric"><span>Mặt hàng</span><strong><?= count($items) ?></strong></article>
<article class="cas-admin-metric"><span>Cần nhập thêm</span><strong><?= $low ?></strong></article>
<article class="cas-admin-metric"><span>Giá trị tồn</span><strong><?= cas_money($value) ?></strong></article></div>
<section class="admin-card admin-section"><div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Mặt hàng</th><th>Nhóm</th><th>Tồn</th><th>Tối thiểu</th><th>Giá nhập</th></tr></thead><tbody>
<?php foreach($items as $i): ?><tr><td><?= cas_e($i['name']) ?></td><td><?= cas_e($i['item_type']) ?></td><td><?= cas_e($i['quantity']) ?> <?= cas_e($i['unit']) ?></td><td><?= cas_e($i['min_quantity']) ?></td><td><?= cas_money($i['unit_cost']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php break;
case 'reports':
$start=(string)($_GET['start']??date('Y-m-01'));$end=(string)($_GET['end']??date('Y-m-d'));
if(!preg_match('/^\d{4}-\d\d-\d\d$/',$start)||!preg_match('/^\d{4}-\d\d-\d\d$/',$end)||$end<$start){$start=date('Y-m-01');$end=date('Y-m-d');}
$q=$db->prepare("SELECT DATE(o.created_at) day,COUNT(*) order_count, SUM(o.total_amount) gross_sales,
    SUM(CASE WHEN o.order_status='completed' AND o.payment_status IN ('paid','partially_refunded','refunded')
      THEN GREATEST(0,o.total_amount-COALESCE(r.amount,0)) ELSE 0 END) confirmed_revenue
    FROM orders o LEFT JOIN (SELECT order_id,SUM(amount) amount FROM refunds WHERE status='recorded' GROUP BY order_id) r
      ON r.order_id=o.order_id
    WHERE DATE(o.created_at) BETWEEN ? AND ? GROUP BY DATE(o.created_at) ORDER BY day DESC");
$q->execute([$start,$end]);$rows=$q->fetchAll();
$totals=['orders'=>0,'gross'=>0,'revenue'=>0];
foreach($rows as $r){$totals['orders']+=(int)$r['order_count'];$totals['gross']+=(float)$r['gross_sales'];$totals['revenue']+=(float)$r['confirmed_revenue'];}
?>
<section class="admin-card admin-section">
<div class="cas-admin-stack justify-content-between mb-3">
  <form method="get" class="cas-admin-stack"><label class="admin-label mb-0" for="reportStart">Từ</label><input id="reportStart" name="start" type="date" class="admin-input" value="<?= cas_e($start) ?>"><label class="admin-label mb-0" for="reportEnd">đến</label><input id="reportEnd" name="end" type="date" class="admin-input" value="<?= cas_e($end) ?>"><button class="admin-btn-light" type="submit">Xem báo cáo</button></form>
  <?php if(in_array($user['role'],['admin','owner'],true)): ?><a class="admin-btn-light" href="loi-nhuan.php">Biểu đồ lợi nhuận gộp <i class="bi bi-arrow-right" aria-hidden="true"></i></a><?php endif; ?>
</div>
<div class="cas-admin-metrics mb-4"><article class="cas-admin-metric"><span>Tổng đơn</span><strong><?= $totals['orders'] ?></strong></article><article class="cas-admin-metric"><span>Giá trị đơn</span><strong><?= cas_money($totals['gross']) ?></strong></article><article class="cas-admin-metric"><span>Doanh thu đã hoàn tất và thanh toán</span><strong><?= cas_money($totals['revenue']) ?></strong></article></div>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Ngày</th><th>Số đơn</th><th>Giá trị tạo đơn</th><th>Doanh thu hợp lệ</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= cas_e($r['day']) ?></td><td><?= (int)$r['order_count'] ?></td><td><?= cas_money($r['gross_sales']) ?></td><td><strong><?= cas_money($r['confirmed_revenue']) ?></strong></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="4"><div class="admin-empty">Chưa có đơn hàng trong khoảng thời gian đã chọn.</div></td></tr><?php endif; ?>
</tbody></table></div></section>
<?php break;
endswitch;
?>
</main></div></div>
<script>
(function(){
  var sb=document.getElementById('adminSidebar'),ov=document.getElementById('adminOverlay'),open=document.getElementById('adminMenuOpen'),close=document.getElementById('adminSidebarClose');
  function toggle(s){if(sb)sb.classList.toggle('open',s);if(ov)ov.classList.toggle('open',s);if(open)open.setAttribute('aria-expanded',String(s));}
  if(open)open.addEventListener('click',function(){toggle(true);});
  if(close)close.addEventListener('click',function(){toggle(false);});
  if(ov)ov.addEventListener('click',function(){toggle(false);});
  document.addEventListener('keydown',function(e){if(e.key==='Escape'&&sb&&sb.classList.contains('open'))toggle(false);});
})();
</script>
</body></html>
