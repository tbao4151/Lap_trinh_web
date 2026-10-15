<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/storefront.php';
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store');
try {
  $rows=cas_db()->query("SELECT p.*,c.name category_name,t.name tone_name,o.name occasion_name FROM products p LEFT JOIN categories c ON p.category_id=c.category_id LEFT JOIN tones t ON p.tone_id=t.tone_id LEFT JOIN occasions o ON p.occasion_id=o.occasion_id WHERE p.status='active' ORDER BY p.product_id")->fetchAll();
  $displayWrapping=cas_product_wrapping_map();
  $wrapping=[];
  foreach(cas_db()->query("SELECT pw.product_id,w.color_id,w.name FROM product_wrapping pw JOIN wrapping_colors w ON w.color_id=pw.color_id WHERE w.status='active' ORDER BY w.color_id") as $w) {
    $extra=0;
    foreach($displayWrapping[(int)$w['product_id']]??[] as $option)
      if($option['name']===$w['name']){$extra=(int)$option['surcharge'];break;}
    $wrapping[(int)$w['product_id']][]=['id'=>(int)$w['color_id'],'name'=>$w['name'],'surcharge'=>$extra];
  }
  $products=[];
  $gallery=cas_product_gallery_map();
  foreach($rows as $p) $products[]=[
    'id'=>sprintf('p%03d',(int)$p['product_id']), 'numericId'=>(int)$p['product_id'], 'sku'=>$p['sku'], 'slug'=>$p['slug'],'name'=>$p['name'],
    'type'=>$p['product_type'],'price'=>(float)$p['price'],'image'=>$p['image'],
    'categories'=>array_values(array_filter([$p['category_name']])),
    'tone'=>$p['tone_name']??'','occasion'=>$p['occasion_name']??'',
    'description'=>$p['description']??'','composition'=>$p['composition']??'',
    'featured'=>(bool)$p['featured'],'today'=>(bool)$p['today'],
    'wrappings'=>$wrapping[(int)$p['product_id']]??[],
    'category'=>$p['category_name']??'',
    'gallery'=>$gallery[(int)$p['product_id']]??[$p['image']],
    'wrapping'=>$displayWrapping[(int)$p['product_id']]??[]
  ];
  echo 'window.CAS_PRODUCTS=',json_encode($products,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT),";\n";
  $settings=array_intersect_key(cas_settings(),array_flip(['shop_name','hotline','email','address','opening_hours','announcement','instagram','zalo','default_shipping_fee']));
  echo 'window.CAS_SETTINGS=',json_encode($settings,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT),";\n";
} catch(Throwable $e) {
  error_log('Database products: '.$e->getMessage());
  http_response_code(503);
  echo "window.CAS_PRODUCTS=[];window.CAS_PRODUCTS_ERROR=true;\n";
}
?>
window.CAS_FORMAT_VND=function(n){return new Intl.NumberFormat('vi-VN',{style:'currency',currency:'VND',maximumFractionDigits:0}).format(Number(n||0));};
window.CAS_FIND_PRODUCT=function(v){return window.CAS_PRODUCTS.find(function(p){return p.slug===v||p.id===v;});};
