<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') {http_response_code(404);exit;}
if(!preg_match('/^cas_hoa_customer_(preview|test_[a-z0-9]+)$/D',getenv('DB_NAME')?:'')) throw new RuntimeException('Test database only');
require __DIR__.'/../../config/database.php';
$input=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);
$q=cas_db()->prepare($input['sql']);$q->execute($input['params']??[]);
echo json_encode($q->columnCount()?$q->fetchAll():['affected'=>$q->rowCount()],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
