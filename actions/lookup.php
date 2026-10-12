<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/bootstrap.php';
cas_require_post();
if(!cas_csrf_valid($_POST['csrf']??null)) cas_json(403,['ok'=>false,'message'=>'Phiên làm việc hết hạn.']);
$history=$_SESSION['cas_lookup_attempts']??[];
$history=array_values(array_filter($history,static fn($t)=>$t>time()-600));
if(count($history)>=20) cas_json(429,['ok'=>false,'message'=>'Quá nhiều lần tra cứu. Hãy thử lại sau.']);
$history[]=time();$_SESSION['cas_lookup_attempts']=$history;
$code=strtoupper(cas_str('code',40));$phone=cas_phone(cas_str('phone',20));
if(!preg_match('/^CSH-[0-9]{4}-[A-F0-9]{8}$/',$code)||!preg_match('/^0[0-9]{9}$/',$phone))
  cas_json(422,['ok'=>false,'message'=>'Mã đơn hoặc số điện thoại chưa hợp lệ.']);
try{
  $stmt=cas_db()->prepare("SELECT order_code,created_at,order_status,recipient_name,receive_date,receive_time,total_amount FROM orders WHERE order_code=? AND recipient_phone=? LIMIT 1");
  $stmt->execute([$code,$phone]);$row=$stmt->fetch();
  if(!$row) cas_json(404,['ok'=>false,'message'=>'Không tìm thấy đơn phù hợp.']);
  cas_json(200,['ok'=>true,'order'=>[
    'code'=>$row['order_code'],'createdAt'=>$row['created_at'],'status'=>$row['order_status'],
    'recipientName'=>$row['recipient_name'],'receiveDate'=>$row['receive_date'],
    'receiveTime'=>$row['receive_time'],'total'=>(int)round((float)$row['total_amount'])
  ]]);
}catch(Throwable $e){
  error_log('CAS order lookup: '.$e->getMessage());
  cas_json(500,['ok'=>false,'message'=>'Lỗi MySQL khi tra cứu đơn.']);
}
