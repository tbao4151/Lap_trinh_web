<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
$user=cas_require_user(true);
$db=cas_db();
$error='';$notice=isset($_GET['saved'])?'Thao tác đã lưu vào MySQL.':'';
function cas_admin_edit(array $user): void {
  if(!in_array($user['role'],['admin','owner'],true)) throw new RuntimeException('Bạn không có quyền thay đổi dữ liệu.');
}
function cas_admin_string(string $field,int $max): string {
  $v=trim((string)($_POST[$field]??''));
  if(mb_strlen($v)>$max)throw new RuntimeException('Dữ liệu quá dài: '.$field);
  return $v;
}
function cas_admin_number(string $field): int {
  $v=filter_var($_POST[$field]??null,FILTER_VALIDATE_INT);
  if($v===false || $v<1)throw new RuntimeException('ID không hợp lệ.');
  return $v;
}
function cas_admin_product_input(PDO $db): array {
  $name=cas_admin_string('name',180);$sku=cas_admin_string('sku',50);
  $slug=strtolower(trim(cas_admin_string('slug',190)));
  if($slug==='')$slug=strtolower(str_replace('_','-',preg_replace('/[^A-Za-z0-9_-]/','',$sku)));
  $price=filter_var($_POST['price']??null,FILTER_VALIDATE_INT);
  $type=(string)($_POST['product_type']??'');
  $status=(string)($_POST['status']??'active');
  if(mb_strlen($name)<2||!preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{2,49}$/',$sku)||!preg_match('/^[a-z0-9-]{2,190}$/',$slug))
    throw new RuntimeException('Tên, SKU hoặc slug không hợp lệ.');
  if($price===false||$price<0||$price>100000000)throw new RuntimeException('Giá không hợp lệ.');
  if(!in_array($type,['bouquet','basket'],true)||!in_array($status,['active','hidden'],true))
    throw new RuntimeException('Kiểu hoặc trạng thái sản phẩm không hợp lệ.');
  $idFields=[];
  foreach(['category_id','tone_id','occasion_id'] as $field){
    $id=filter_var($_POST[$field]??null,FILTER_VALIDATE_INT);
    $idFields[]=$id!==false&&$id>0?$id:null;
  }
  $filename=basename((string)($_POST['image']??''));
  if(!preg_match('/^[a-zA-Z0-9_-]+\.png$/',$filename)||!is_file(__DIR__.'/../assets/images/'.$filename))
    throw new RuntimeException('Vui lòng chọn hình ảnh có sẵn hợp lệ.');
  return array_merge($idFields,[
    $sku,$slug,$name,$type,$price,cas_admin_string('description',10000),
    cas_admin_string('composition',10000),'assets/images/'.$filename,
    isset($_POST['featured'])?1:0,isset($_POST['today'])?1:0,$status
  ]);
}
if(cas_post()){
  try{
    cas_assert_csrf();
    $act=(string)($_POST['action']??'');
    if($act==='order_update' && $cas_page==='orders'){
      $id=cas_admin_number('id');
      $status=(string)($_POST['status']??'');
      $payment=(string)($_POST['payment_status']??'');
      if(!in_array($status,['pending_confirmation','confirmed','preparing','ready','delivering','completed','cancelled'],true) ||
         !in_array($payment,['unpaid','partial','paid','refunded'],true))
         throw new RuntimeException('Trạng thái không hợp lệ.');
      $q=$db->prepare('UPDATE orders SET order_status=?,payment_status=? WHERE order_id=?');
      $q->execute([$status,$payment,$id]);
    }elseif($act==='delivery_update' && $cas_page==='delivery'){
      $id=cas_admin_number('id');$status=(string)($_POST['status']??'');
      if(!in_array($status,['not_delivered','out_for_delivery','delivered','pickup_ready'],true))
        throw new RuntimeException('Trạng thái giao không hợp lệ.');
      $q=$db->prepare('UPDATE deliveries SET delivery_status=? WHERE delivery_id=?');$q->execute([$status,$id]);
    }elseif($act==='product_save' && in_array($cas_page,['product_add','product_edit'],true)){
      cas_admin_edit($user);$values=cas_admin_product_input($db);
      if($cas_page==='product_edit'){
        $id=cas_admin_number('id');$values[]=$id;
        $sql='UPDATE products SET category_id=?,tone_id=?,occasion_id=?,sku=?,slug=?,name=?,product_type=?,price=?,description=?,composition=?,image=?,featured=?,today=?,status=? WHERE product_id=?';
      }else{
        $sql='INSERT INTO products(category_id,tone_id,occasion_id,sku,slug,name,product_type,price,description,composition,image,featured,today,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
      }
      $q=$db->prepare($sql);$q->execute($values);
      cas_redirect('san-pham.php?saved=1');
    }elseif($act==='quick_save' && $cas_page==='product_quick'){
      cas_admin_edit($user);$id=cas_admin_number('id');
      $price=filter_var($_POST['price']??null,FILTER_VALIDATE_INT);
      $status=(string)($_POST['status']??'');
      if($price===false||$price<0||$price>100000000||!in_array($status,['active','hidden'],true))
        throw new RuntimeException('Giá hoặc trạng thái sai.');
      $q=$db->prepare('UPDATE products SET price=?,status=? WHERE product_id=?');$q->execute([$price,$status,$id]);
    }elseif($act==='taxonomy_save' && $cas_page==='taxonomy'){
      cas_admin_edit($user);
      $table=(string)($_POST['table']??'');
      if(!in_array($table,['categories','tones','occasions'],true))throw new RuntimeException('Nhóm phân loại sai.');
      $name=cas_admin_string('name',100);
      if(mb_strlen($name)<2)throw new RuntimeException('Tên phân loại không hợp lệ.');
      if($table==='categories'){
        $slug='cat-'.strtolower(bin2hex(random_bytes(4)));
        $q=$db->prepare('INSERT INTO categories(name,slug) VALUES(?,?)');$q->execute([$name,$slug]);
      }else{
        $q=$db->prepare("INSERT INTO {$table}(name) VALUES(?)");$q->execute([$name]);
      }
    }else{
      throw new RuntimeException('Hành động không được hỗ trợ.');
    }
    cas_redirect(basename($_SERVER['SCRIPT_NAME']).'?saved=1');
  }catch(Throwable $e){
    $error=$e instanceof PDOException?'Dữ liệu trùng, đang được sử dụng hoặc lỗi kết nối MySQL.':$e->getMessage();
  }
}
function cas_admin_rows(PDO $db,string $sql,array $args=[]): array{
  $q=$db->prepare($sql);$q->execute($args);return $q->fetchAll();
}
function cas_money(mixed $value): string{return number_format((float)$value,0,',','.').' ₫';}
function cas_admin_select(string $field,array $options,string $selected): string{
  $s='<select class="admin-select" name="'.cas_e($field).'">';
  foreach($options as $key=>$label){
    $s.='<option value="'.cas_e((string)$key).'"'.((string)$key===$selected?' selected':'').'>'.cas_e((string)$label).'</option>';
  }
  return $s.'</select>';
}
const statuses=['pending_confirmation'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','preparing'=>'Đang chuẩn bị','ready'=>'Sẵn sàng','delivering'=>'Đang giao','completed'=>'Hoàn tất','cancelled'=>'Đã hủy'];
const payments=['unpaid'=>'Chưa thu','partial'=>'Đã cọc','paid'=>'Đã thu','refunded'=>'Đã hoàn'];
const deliveries=['not_delivered'=>'Chưa giao','out_for_delivery'=>'Đang giao','delivered'=>'Đã giao','pickup_ready'=>'Chờ lấy'];
