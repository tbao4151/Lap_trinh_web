<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/bootstrap.php';
cas_require_post();
if(!cas_csrf_valid($_POST['csrf']??null)) cas_json(403,['ok'=>false,'message'=>'Phiên làm việc hết hạn.']);
$name=cas_str('name',120);$phone=cas_phone(cas_str('phone',20));
$subject=cas_str('subject',120);$message=cas_str('message',5000);
if($name===''||!preg_match('/^0[0-9]{9}$/',$phone)||$subject===''||mb_strlen($message,'UTF-8')<10)
  cas_json(422,['ok'=>false,'message'=>'Nhập họ tên, số điện thoại và nội dung ít nhất 10 ký tự.']);
if(($_SESSION['cas_contact_last']??0)>time()-30)
  cas_json(429,['ok'=>false,'message'=>'Vui lòng chờ 30 giây trước khi gửi lại.']);
try{
 $stmt=cas_db()->prepare("INSERT INTO contacts(customer_id,name,phone,subject,message,status) VALUES(NULL,?,?,?,?,'new')");
 $stmt->execute([$name,$phone,$subject,$message]);
 $_SESSION['cas_contact_last']=time();
 cas_json(201,['ok'=>true,'message'=>'Yêu cầu liên hệ đã được lưu thành công.']);
}catch(Throwable $e){
 error_log('CAS contact: '.$e->getMessage());
 cas_json(500,['ok'=>false,'message'=>'Không thể lưu liên hệ. Vui lòng kiểm tra MySQL.']);
}
