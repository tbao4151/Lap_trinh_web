<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/bootstrap.php';
cas_require_post();
if(!cas_csrf_valid($_POST['csrf']??null))cas_json(403,['ok'=>false,'message'=>'Phiên làm việc hết hạn.']);
$cart=json_decode((string)($_POST['cart']??''),true);
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
 $result[]=['productId'=>$id,'quantity'=>(int)$qty];
}
$_SESSION['cas_cart']=$result;
cas_json(200,['ok'=>true,'cart'=>$result]);
