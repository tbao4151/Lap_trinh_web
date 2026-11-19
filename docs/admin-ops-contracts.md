# Đề xuất contract cần trưởng nhóm duyệt (Admin Operations)

Theo mục 5 của kế hoạch TV3, các thay đổi về vai trò, trạng thái đơn, phí giao, hoàn tiền và trừ kho phải được trưởng nhóm duyệt.
Phần Admin Operations **không** sửa `includes/auth.php`, `admin/_actions.php`, `actions/checkout.php` hay bảng `orders`/`payments`. Những điểm dưới đây là đề xuất, chưa triển khai.

## C1. Máy trạng thái đơn hàng (`admin/_actions.php`, action `order_update`)

Hiện tại: mọi vai trò nội bộ đổi được `order_status` và `payment_status` sang bất kỳ giá trị hợp lệ nào (đo được: staff đổi đơn sang `cancelled/refunded`; admin lùi `completed` → `pending_confirmation/unpaid`).

Đề xuất:

| Từ | Được chuyển sang | Ai |
|---|---|---|
| pending_confirmation | confirmed, cancelled | staff/admin/owner |
| confirmed | preparing, cancelled | staff/admin/owner |
| preparing | ready, cancelled (cần lý do) | staff/admin/owner |
| ready | delivering (đơn giao), completed (khách nhận tại shop) | staff/admin/owner |
| delivering | completed, ready (giao không thành công, cần lý do) | staff/admin/owner |
| completed, cancelled | (cuối) — chỉ owner mở lại, có lý do và log | owner |

Trạng thái hiện có trong `_actions.php`: `pending_confirmation, confirmed, preparing, ready, delivering, completed, cancelled`; thanh toán: `unpaid, partial, paid, refunded`. Nên đồng bộ `delivering` với `deliveries.delivery_status` (bảng giao hàng đã có luồng riêng trong `admin/modules/delivery.php`).
`payment_status = refunded` chỉ đặt khi tổng `refunds.status='recorded'` bằng `total_amount` (xem C2). Có thể tái sử dụng `cas_audit()` của `admin/_ops.php` để ghi lịch sử.

## C2. Hoàn tiền và trạng thái thanh toán

Đã làm (không đụng contract): `admin/hoan-tien.php` chỉ nhận đơn `payment_status='paid'`, tổng yêu cầu không vượt `total_amount`, luồng `requested → approved → recorded` (hoặc `rejected`), ghi `refund_events`. **Không** tự đổi `orders.payment_status` và không chuyển tiền thật (test `Hoàn tiền KHÔNG tự đổi orders.payment_status`).

Cần lead quyết: khi đã `recorded` đủ số tiền, có chuyển `payment_status` sang `refunded` (hoàn toàn) / `partial_refund` (một phần) hay không; nếu có thì thêm giá trị mới vào danh sách ở `_actions.php`.

## C3. Trừ kho khi đặt hàng

Hiện tại `actions/checkout.php` không trừ `inventory_items`. Kho chỉ thay đổi qua phiếu nhập/xuất/kiểm kê trong admin (có `quantity_before/after`, `request_token`, `FOR UPDATE`).
Đề xuất: bảng định mức `product_recipes(product_id, item_id, quantity)`; khi đơn chuyển `confirmed → preparing` gọi một hàm dùng chung (tách từ `cas_inventory_move()` trong `admin/modules/inventory.php`) để xuất kho theo định mức trong cùng transaction, ghi `transaction_type='out'` và mã đơn trong lý do; huỷ đơn thì nhập lại.

## C4. Giấy gói tại checkout

Đã làm: `wrapping_colors` có `surcharge`, `image`, `sort_order`; `product_wrapping` gán theo sản phẩm; trang chi tiết chỉ hiện giấy gói `active` (feed `assets/js/products.php`).
Chưa làm vì đụng contract giá đơn: chọn giấy gói trong giỏ/checkout và cộng phụ thu. Đề xuất thêm `order_items.wrapping_color_id` + `order_items.wrapping_surcharge` (chụp giá tại thời điểm đặt, giống `unit_price`), `actions/checkout.php` kiểm giấy gói còn `active` và thuộc `product_wrapping` của sản phẩm.

## C5. Phí giao và khung giờ giao

Đã làm: `shop_settings.default_shipping_fee` (chỉ hiển thị tham khảo), `admin/giao-hang.php` dùng 5 khung giờ cố định `CAS_DELIVERY_SLOTS` khớp với form checkout.
Đề xuất: checkout đọc phí từ `cas_setting('default_shipping_fee')` thay cho `shipping_fee = 0`, và cấu hình khung giờ/số đơn tối đa mỗi khung trong `shop_settings` để checkout chặn khung đã đầy.

## C6. Phiên đăng nhập sau khi đổi mật khẩu

Đã làm: khoá tài khoản hoặc đổi vai trò có hiệu lực ngay ở yêu cầu kế tiếp (vì `cas_user()` đọc lại `status` và `role` mỗi request). Đổi mật khẩu của chính mình sẽ cấp lại session id.
Chưa làm vì nằm trong `includes/auth.php`: các phiên **khác** của cùng tài khoản vẫn còn hiệu lực sau khi đổi mật khẩu. Đề xuất thêm cột `users.session_version`, tăng khi đổi/đặt lại mật khẩu, lưu vào session lúc đăng nhập và so sánh trong `cas_user()`.

## C7. Quyền xem báo cáo doanh thu

`bao-cao.php` hiện mở cho staff. Nếu nhóm muốn giới hạn, chỉ cần thêm `cas_page_roles(['admin','owner'], 'Báo cáo')` (hàm có sẵn trong `admin/_ops.php`) hoặc kiểm tra tương đương trong `_app.php`.
