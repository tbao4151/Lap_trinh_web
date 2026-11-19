<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Ho_Chi_Minh');
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!preg_match('/^cas_hoa_customer_(preview|test_[a-z0-9]+)$/D', getenv('DB_NAME') ?: '')) {
    throw new RuntimeException('Chỉ kiểm tra database thử riêng.');
}
require __DIR__.'/../../config/database.php';
$checks = [
    ['name'=>'Sản phẩm active', 'sql'=>"SELECT COUNT(*) AS n FROM products WHERE status='active'", 'expected'=>11],
    ['name'=>'Bài viết công khai', 'sql'=>"SELECT COUNT(*) AS n FROM news WHERE status='published'", 'expected'=>6],
    ['name'=>'Tổng tiền đơn khớp các dòng', 'sql'=>"SELECT COUNT(*) AS n FROM orders o WHERE o.order_code LIKE 'CSH-%' AND (o.subtotal <> COALESCE((SELECT SUM(i.line_total) FROM order_items i WHERE i.order_id=o.order_id),0) OR o.total_amount <> o.subtotal + o.shipping_fee)", 'expected'=>0],
    ['name'=>'Thành tiền dòng khớp giá và số lượng', 'sql'=>'SELECT COUNT(*) AS n FROM order_items WHERE line_total <> quantity * unit_price', 'expected'=>0],
    ['name'=>'Khách hàng tài khoản không mồ côi', 'sql'=>'SELECT COUNT(*) AS n FROM customers c LEFT JOIN users u ON u.user_id=c.user_id WHERE c.user_id IS NOT NULL AND u.user_id IS NULL', 'expected'=>0],
    ['name'=>'Token đã dùng có bản ghi', 'sql'=>'SELECT COUNT(*) AS n FROM password_resets WHERE consumed_at IS NOT NULL', 'minimum'=>1],
    ['name'=>'Liên hệ đã được lưu', 'sql'=>'SELECT COUNT(*) AS n FROM contacts', 'minimum'=>1],
    ['name'=>'Đơn giao hàng đã được lưu', 'sql'=>"SELECT COUNT(*) AS n FROM orders WHERE order_code LIKE 'CSH-%' AND delivery_method='delivery'", 'minimum'=>1],
    ['name'=>'Đơn pickup đã được lưu', 'sql'=>"SELECT COUNT(*) AS n FROM orders WHERE order_code LIKE 'CSH-%' AND delivery_method='pickup'", 'minimum'=>1],
];
foreach ($checks as &$check) {
    $check['actual']=(int)cas_db()->query($check['sql'])->fetchColumn();
    $pass=isset($check['expected']) ? $check['actual']===$check['expected'] : $check['actual'] >= $check['minimum'];
    $check['status']=$pass?'PASS':'FAIL';
}
unset($check);
echo json_encode(['date'=>date(DATE_ATOM),'database'=>getenv('DB_NAME'),'note'=>'Không tính đơn TEST-* dùng riêng cho test phân trang vào kiểm tra tổng tiền đơn CSH-*.', 'results'=>$checks],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);
exit(count(array_filter($checks, static fn($check)=>$check['status']==='FAIL')) ? 1 : 0);
