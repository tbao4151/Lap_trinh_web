<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/storefront.php';
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store');
try {
  $rows=cas_db()->query("SELECT p.*,c.name category_name,t.name tone_name,o.name occasion_name FROM products p LEFT JOIN categories c ON p.category_id=c.category_id LEFT JOIN tones t ON p.tone_id=t.tone_id LEFT JOIN occasions o ON p.occasion_id=o.occasion_id WHERE p.status='active' ORDER BY p.product_id")->fetchAll();
  $products=[];
  $gallery=cas_product_gallery_map();
  $wrapping=cas_product_wrapping_map();
  foreach($rows as $p) $products[]=[
    'id'=>sprintf('p%03d',(int)$p['product_id']), 'sku'=>$p['sku'], 'slug'=>$p['slug'],'name'=>$p['name'],
    'type'=>$p['product_type'],'price'=>(float)$p['price'],'image'=>$p['image'],
    'categories'=>array_values(array_filter([$p['category_name'],$p['tone_name']])),
    'tone'=>$p['tone_name']??'','occasion'=>$p['occasion_name']??'',
    'description'=>$p['description']??'','composition'=>$p['composition']??'',
    'featured'=>(bool)$p['featured'],'today'=>(bool)$p['today'],
    'category'=>$p['category_name']??'',
    'gallery'=>$gallery[(int)$p['product_id']]??[$p['image']],
    'wrapping'=>$wrapping[(int)$p['product_id']]??[]
  ];
  echo 'window.CAS_PRODUCTS=',json_encode($products,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT),";\n";
  $settings=array_intersect_key(cas_settings(),array_flip(['shop_name','hotline','email','address','opening_hours','announcement','instagram','zalo']));
  echo 'window.CAS_SETTINGS=',json_encode($settings,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT),";\n";
} catch(Throwable $e) {
  error_log('Database products: '.$e->getMessage());
  http_response_code(503);
  echo "window.CAS_PRODUCTS=[];console.error('Không thể lấy sản phẩm từ MySQL');\n";
}
?>
window.CAS_FORMAT_VND=function(n){return new Intl.NumberFormat('vi-VN',{style:'currency',currency:'VND',maximumFractionDigits:0}).format(Number(n||0));};
window.CAS_FIND_PRODUCT=function(v){return window.CAS_PRODUCTS.find(function(p){return p.slug===v||p.id===v;});};
