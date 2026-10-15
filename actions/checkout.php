<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/customer.php';
require_once __DIR__.'/../includes/storefront.php';
cas_require_post();
if(!cas_csrf_valid($_POST['csrf']??null)) cas_json(403,['ok'=>false,'message'=>'Phiên làm việc hết hạn, hãy tải lại trang.']);
$requestKey=cas_str('checkoutKey',48);
// Same-session replay returns the committed order; PHP's session lock serializes requests.
// Optional for legacy clients; the Customer form always supplies this key.
if($requestKey!=='') {
  if(!preg_match('/^[a-f0-9]{48}$/D',$requestKey)) cas_json(422,['ok'=>false,'message'=>'Mã xác nhận không hợp lệ.']);
  if(isset($_SESSION['cas_checkout_done'][$requestKey])) cas_json(200,['ok'=>true,'orderCode'=>$_SESSION['cas_checkout_done'][$requestKey]]);
  if(!hash_equals(cas_checkout_key(),$requestKey)) cas_json(409,['ok'=>false,'message'=>'Giỏ hàng đã thay đổi. Hãy tải lại trang thanh toán.']);
}
$cart=$_SESSION['cas_cart']??[];
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
$lines=[];$wrapIds=[];
foreach($cart as $item){
  if(!is_array($item)||!isset($item['productId'],$item['quantity'])||
     !is_string($item['productId'])||!preg_match('/^p[0-9]{3,}$/',$item['productId']))
    cas_json(422,['ok'=>false,'message'=>'Sản phẩm không hợp lệ.']);
  $qty=filter_var($item['quantity'],FILTER_VALIDATE_INT);
  if($qty===false||$qty<1||$qty>20) cas_json(422,['ok'=>false,'message'=>'Số lượng không hợp lệ.']);
  $id=(int)substr($item['productId'],1);
  if(isset($lines[$id])) cas_json(422,['ok'=>false,'message'=>'Sản phẩm bị trùng trong giỏ.']);
  $lines[$id]=$qty;
  if(isset($item['wrappingId'])) $wrapIds[$id]=(int)$item['wrappingId'];
}
try {
  $db=cas_db();$db->beginTransaction();
  $find=$db->prepare("SELECT price FROM products WHERE product_id=? AND status='active'");
  $ordered=[];$subtotal=0;$wrappingNotes=[];
  foreach($lines as $id=>$qty){
    $find->execute([$id]);$p=$find->fetch();
    if(!$p) throw new DomainException('Sản phẩm đã ngừng bán, vui lòng cập nhật giỏ.');
    $price=(int)round((float)$p['price']);$surcharge=0;
    if(isset($wrapIds[$id])) {
      // Bản schema trước migration 003 có giấy gói nhưng chưa có phụ thu.
      try{
        $wrap=$db->prepare("SELECT w.name,w.surcharge FROM wrapping_colors w JOIN product_wrapping pw ON pw.color_id=w.color_id WHERE pw.product_id=? AND w.color_id=? AND w.status='active'");
        $wrap->execute([$id,$wrapIds[$id]]);$selected=$wrap->fetch();
      }catch(PDOException $e){
        $wrap=$db->prepare("SELECT w.name,0 AS surcharge FROM wrapping_colors w JOIN product_wrapping pw ON pw.color_id=w.color_id WHERE pw.product_id=? AND w.color_id=? AND w.status='active'");
        $wrap->execute([$id,$wrapIds[$id]]);$selected=$wrap->fetch();
      }
      if(!$selected)throw new DomainException('Giấy gói đã ngừng áp dụng. Hãy cập nhật giỏ hàng.');
      $surcharge=(int)round((float)$selected['surcharge']);
      if($surcharge<0||$surcharge>10000000)throw new DomainException('Phụ thu giấy gói không hợp lệ.');
      $wrappingNotes[]='Mẫu '.sprintf('p%03d',$id).': '.$selected['name'].' (phụ thu '.number_format($surcharge,0,',','.').' đ / sản phẩm)';
    }
    $unitPrice=$price+$surcharge;$sum=$qty*$unitPrice;
    if($unitPrice<0||$sum>99999999999) throw new DomainException('Giá hoặc tổng tiền không hợp lệ.');
    // unit_price là giá thực thanh toán tại thời điểm đặt (bao gồm phụ thu nếu có).
    $subtotal+=$sum;$ordered[]=[$id,$qty,$unitPrice,$sum];
  }
  $shippingFee=$method==='delivery'?(int)round((float)cas_setting('default_shipping_fee')):0;
  if($shippingFee<0||$shippingFee>1000000)throw new DomainException('Phí giao hàng không hợp lệ.');
  $total=$subtotal+$shippingFee;
  if($subtotal<=0||$total>99999999999) throw new DomainException('Tổng tiền không hợp lệ.');
  $user=cas_user();
  $customerId=$user&&$user['role']==='customer'?cas_customer_id((int)$user['user_id']):null;
  if(!$customerId){
    $insertCustomer=$db->prepare("INSERT INTO customers(user_id,full_name,phone) VALUES(NULL,?,?)");
    $insertCustomer->execute([$name,$phone]);$customerId=(int)$db->lastInsertId();
  }
  $code='CSH-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(4)));
  $orderSql="INSERT INTO orders(customer_id,order_code,recipient_name,recipient_phone,delivery_method,delivery_address,receive_date,receive_time,payment_method,payment_status,order_status,card_message,order_note,subtotal,shipping_fee,total_amount) VALUES(?,?,?,?,?,?,?,?,?,'unpaid','pending_confirmation',?,?,?,?,?)";
  $stmt=$db->prepare($orderSql);
  // Preserve the selected wrapping in the order note and persist the all-inclusive unit price.
  // The price snapshot includes the selected wrapping surcharge without requiring an order-item schema change.
  $note=trim($note."\nThông tin người đặt tại thời điểm tạo đơn: ".$name.' — '.$phone);
  if($wrappingNotes) $note=trim($note."\nGiấy gói đã chọn:\n".implode("\n",$wrappingNotes));
  $stmt->execute([$customerId,$code,$recipient,$recipientPhone,$method,$method==='delivery'?$address:null,$date,$time,'cod',$card,$note,$subtotal,$shippingFee,$total]);
  $orderId=(int)$db->lastInsertId();
  $add=$db->prepare("INSERT INTO order_items(order_id,product_id,quantity,unit_price,line_total) VALUES(?,?,?,?,?)");
  foreach($ordered as $line) $add->execute([$orderId,...$line]);
  if($method==='delivery'){
    $delivery=$db->prepare("INSERT INTO deliveries(order_id,delivery_status,scheduled_at,fee) VALUES(?,'not_delivered',?,?)");
    $delivery->execute([$orderId,$date.' '.substr($time,0,5).':00',$shippingFee]);
  }
  $db->commit();
  if($requestKey!=='') {
    $_SESSION['cas_checkout_done'][$requestKey]=$code;
    $_SESSION['cas_checkout_done']=array_slice($_SESSION['cas_checkout_done'],-10,null,true);
  }
  unset($_SESSION['cas_checkout_key']);
  unset($_SESSION['cas_cart']);
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
