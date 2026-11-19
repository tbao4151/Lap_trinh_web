<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/bootstrap.php';
cas_require_post();
if(!cas_csrf_valid($_POST['csrf']??null))cas_json(403,['ok'=>false,'message'=>'Phiên làm việc hết hạn.']);
$cart=json_decode(cas_str('cart',20000),true);
if(!is_array($cart)||count($cart)>40)cas_json(422,['ok'=>false,'message'=>'Giỏ hàng không hợp lệ.']);
$stmt=cas_db()->prepare("SELECT product_id FROM products WHERE product_id=? AND status='active'");
$result=[];$seen=[];
foreach($cart as $item){
 if(!is_array($item)||!is_string($item['productId']??null)||!preg_match('/^p[0-9]{3,}$/',$item['productId']))cas_json(422,['ok'=>false,'message'=>'Mã sản phẩm không hợp lệ.']);
 $qty=filter_var($item['quantity']??null,FILTER_VALIDATE_INT);
 if($qty===false||$qty<1||$qty>20)cas_json(422,['ok'=>false,'message'=>'Số lượng từ 1 đến 20.']);
 $id=$item['productId'];
 if(isset($seen[$id]))cas_json(422,['ok'=>false,'message'=>'Sản phẩm trong giỏ bị trùng.']);
 $seen[$id]=true;$stmt->execute([(int)substr($id,1)]);
 if(!$stmt->fetch())cas_json(422,['ok'=>false,'message'=>'Sản phẩm đã ngừng bán.']);
 $line=['productId'=>$id,'quantity'=>(int)$qty];
 if(isset($item['wrappingId'])) {
   $wrappingId=filter_var($item['wrappingId'],FILTER_VALIDATE_INT);
   if(!$wrappingId)cas_json(422,['ok'=>false,'message'=>'Giấy gói không hợp lệ.']);
   $wrap=cas_db()->prepare("SELECT w.name FROM wrapping_colors w JOIN product_wrapping pw ON pw.color_id=w.color_id WHERE pw.product_id=? AND w.color_id=? AND w.status='active'");
   $wrap->execute([(int)substr($id,1),$wrappingId]);$wrapName=$wrap->fetchColumn();
   if($wrapName===false)cas_json(422,['ok'=>false,'message'=>'Màu giấy gói không còn áp dụng. Hãy chọn lại.']);
   $line['wrappingId']=$wrappingId;$line['wrappingName']=$wrapName;
 }
 $result[]=$line;
}
if(($_SESSION['cas_cart']??[])!==$result) unset($_SESSION['cas_checkout_key']);
$_SESSION['cas_cart']=$result;
cas_json(200,['ok'=>true,'cart'=>$result]);
