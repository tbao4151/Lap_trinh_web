<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/bootstrap.php';
cas_require_post();
if(!cas_csrf_valid($_POST['csrf']??null)) cas_json(403,['ok'=>false,'message'=>'Phiên làm việc hết hạn, hãy tải lại trang.']);
$cart=json_decode((string)($_POST['cart']??''),true);
if(!is_array($cart)||count($cart)<1||count($cart)>40) cas_json(422,['ok'=>false,'message'=>'Giỏ hàng không hợp lệ.']);
$name=cas_str('customerName',120);$phone=cas_phone(cas_str('customerPhone',20));
$recipient=cas_str('recipientName',120);$recipientPhone=cas_phone(cas_str('recipientPhone',20));
$method=cas_str('fulfillment',30);$address=cas_str('address',255);
$date=cas_str('receiveDate',10);$time=cas_str('receiveTime',50);
$card=cas_str('cardMessage',3000);$note=cas_str('orderNote',3000);
if($name===''||$recipient===''||!preg_match('/^0[0-9]{9}$/',$phone)||!preg_match('/^0[0-9]{9}$/',$recipientPhone))
  cas_json(422,['ok'=>false,'message'=>'Tên và số điện thoại người đặt/người nhận chưa hợp lệ.']);
if(!in_array($method,['delivery','pickup'],true)||($method==='delivery'&&$address===''))
  cas_json(422,['ok'=>false,'message'=>'Vui lòng nhập địa chỉ giao hàng.']);
$receive=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
if(!$receive||$receive->format('Y-m-d')!==$date||$receive<new DateTimeImmutable('today +1 day'))
  cas_json(422,['ok'=>false,'message'=>'Ngày nhận phải từ ngày mai trở đi.']);
$slots=['08:00 - 10:00','10:00 - 12:00','13:00 - 15:00','15:00 - 17:00','17:00 - 19:00'];
if(!in_array($time,$slots,true)) cas_json(422,['ok'=>false,'message'=>'Khung giờ nhận không hợp lệ.']);
$lines=[];
foreach($cart as $item){
  if(!is_array($item)||!isset($item['productId'],$item['quantity'])||
     !is_string($item['productId'])||!preg_match('/^p[0-9]{3,}$/',$item['productId']))
    cas_json(422,['ok'=>false,'message'=>'Sản phẩm không hợp lệ.']);
  $qty=filter_var($item['quantity'],FILTER_VALIDATE_INT);
  if($qty===false||$qty<1||$qty>20) cas_json(422,['ok'=>false,'message'=>'Số lượng không hợp lệ.']);
  $id=(int)substr($item['productId'],1);
  if(isset($lines[$id])) cas_json(422,['ok'=>false,'message'=>'Sản phẩm bị trùng trong giỏ.']);
  $lines[$id]=$qty;
}
try {
  $db=cas_db();$db->beginTransaction();
  $find=$db->prepare("SELECT price FROM products WHERE product_id=? AND status='active'");
  $ordered=[];$subtotal=0;
  foreach($lines as $id=>$qty){
    $find->execute([$id]);$p=$find->fetch();
    if(!$p) throw new DomainException('Sản phẩm đã ngừng bán, vui lòng cập nhật giỏ.');
    $price=(int)round((float)$p['price']);$sum=$qty*$price;
    if($price<0||$sum>99999999999) throw new DomainException('Giá hoặc tổng tiền không hợp lệ.');
    $subtotal+=$sum;$ordered[]=[$id,$qty,$price,$sum];
  }
  if($subtotal<=0||$subtotal>99999999999) throw new DomainException('Tổng tiền không hợp lệ.');
  $insertCustomer=$db->prepare("INSERT INTO customers(user_id,full_name,phone) VALUES(NULL,?,?)");
  $insertCustomer->execute([$name,$phone]);$customerId=(int)$db->lastInsertId();
  $code='CSH-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(4)));
  $orderSql="INSERT INTO orders(customer_id,order_code,recipient_name,recipient_phone,delivery_method,delivery_address,receive_date,receive_time,payment_method,payment_status,order_status,card_message,order_note,subtotal,shipping_fee,total_amount) VALUES(?,?,?,?,?,?,?,?,?,'unpaid','pending_confirmation',?,?,?,0,?)";
  $stmt=$db->prepare($orderSql);
  $stmt->execute([$customerId,$code,$recipient,$recipientPhone,$method,$method==='delivery'?$address:null,$date,$time,'cod',$card,$note,$subtotal,$subtotal]);
  $orderId=(int)$db->lastInsertId();
  $add=$db->prepare("INSERT INTO order_items(order_id,product_id,quantity,unit_price,line_total) VALUES(?,?,?,?,?)");
  foreach($ordered as $line) $add->execute([$orderId,...$line]);
  if($method==='delivery'){
    $delivery=$db->prepare("INSERT INTO deliveries(order_id,delivery_status,scheduled_at,fee) VALUES(?,'not_delivered',?,0)");
    $delivery->execute([$orderId,$date.' '.substr($time,0,5).':00']);
  }
  $db->commit();
  $_SESSION['cas_last_order_id']=$orderId;
  $_SESSION['cas_last_order_code']=$code;
  cas_json(201,['ok'=>true,'orderCode'=>$code]);
} catch(DomainException $e){
  if(isset($db)&&$db->inTransaction())$db->rollBack();
  cas_json(422,['ok'=>false,'message'=>$e->getMessage()]);
} catch(Throwable $e){
  if(isset($db)&&$db->inTransaction())$db->rollBack();
  error_log('CAS checkout: '.$e->getMessage());
  cas_json(500,['ok'=>false,'message'=>'Không thể lưu đơn. Vui lòng kiểm tra MySQL và thử lại.']);
}
