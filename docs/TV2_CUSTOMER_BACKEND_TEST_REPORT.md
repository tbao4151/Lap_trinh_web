# TV2 · Nghiệm thu backend khách hàng (19/11/2026)

Nền `d68e0a8e09b691e60c7e103d3d2b0bc4cdcc0fe5`. PHP 8.2.12, MariaDB 10.4.32 trên loopback:3307, datadir preview riêng ngoài XAMPP dùng chung. HTTP :8059 và SMTP nội bộ :1025. Suite Customer dùng schema cas_hoa_customer_test_tv219. Hai script shell hardcode tên cas_hoa nên dùng schema này trên **instance thử nghiệm 3307** (xác nhận chưa tồn tại trước khi chạy). Không sử dụng 3306.

## Phát hiện và thay đổi

- actions/cart.php:15: trước sửa, HTTP POST hai dòng p001 và p0001 nhận 200. Cart kiểm trùng theo chuỗi, nhưng SQL và checkout dùng cùng ID số 1. Checkout từ chối giỏ đã được cart chấp nhận. Sau sửa dùng ID số cho kiểm trùng, trả 422 ở cart và giữ giỏ cũ. Giữ nguyên mã chuỗi trong phản hồi.
- actions/checkout.php:12,44,95–101: checkout lấy hàng từ session, đọc giá và giấy gói từ DB rồi ghi orders/order_items/deliveries trong transaction. Lookup đối chiếu mã đơn và điện thoại. Với key, retry trả về cùng mã. Với client cũ bỏ key, POST lần đầu thành công, request lặp khi giỏ đã xóa nhận 422. Không thấy tạo trùng trong thử nghiệm; không thay đổi checkout. Client tạo lại giỏ sau đó là một lượt mua mới. Bản đồ key nằm trong session, không đảm bảo idempotency qua sự cố tiến trình giữa DB commit và lưu session. HTTP test gửi song song nhưng PHP dev server phục vụ tuần tự.
- tests/customer/http.cjs:37 trở đi: thêm 8 ca độc lập và mở rộng assertion DB/HTTP. Sửa kỳ vọng cũ 800000/400000 bỏ sót shipping 30000: trước khi điều chỉnh có 3 assertion sai. Kỳ vọng mới đọc giá và phí từ DB. Kiểm tra snapshot với phụ thu thử 7000 và thay đổi giá sau đặt; khôi phục fixture sau kiểm tra.
- tests/customer/http.cjs:186 trở đi: kiểm tra đăng ký, hash, đổi session ID, logout, reset và giới hạn yêu cầu. Sau reset kiểm tra phiên cũ phải đăng nhập lại trước khi chạy các test trang tài khoản.

## Kết quả thực tế

| Ca | Kỳ vọng | Thực tế |
|---|---|---|
| Cart trùng ID dạng alias / số lượng âm, quá 20 | 422 | PASS |
| CSRF / POST dạng mảng | 403 / 422 | PASS |
| Giỏ phiên mới / ngày cũ / phone sai | 422 | PASS |
| Hàng ngừng bán / giấy gói bị ẩn giữa giỏ và checkout | 422, số đơn không tăng | PASS |
| Replay có key | Cùng mã, chỉ thêm một đơn và một dòng hàng | PASS |
| Replay không key / pickup | 201 và 422, một đơn, phí 0, không dòng deliveries | PASS |
| Delivery / giá client giả / lookup | Giá DB, phí đúng ở deliveries, chỉ trả thông tin khi đúng điện thoại | PASS |
| Snapshot giá và giấy gói | Giá cũ gồm phụ thu, ghi chú giữ nguyên sau sửa catalog | PASS |
| Hai tài khoản / đăng nhập / logout | History riêng, hash trong DB, ID phiên đổi, cookie cũ mất quyền | PASS |
| Reset nội bộ | TTL gần 30 phút, từ chối token hết hạn/đã dùng, session_version +1, phiên cũ mất quyền | PASS |
| Reset email lạ và giới hạn | Phản hồi chung, lần thứ sáu bị giới hạn trong cùng phiên | PASS |
| Email Internet | NOT_RUN | Chỉ thử SMTP loopback |
| scripts/verify-r2-auth-admin.sh | exit 0 | PASS, gồm checkout legacy và snapshot SQL |
| scripts/verify-r2.sh | exit 0 | FAIL sau checkout legacy, lookup tổng 830000 và contact. Trang tin trả HTTP 200 nhưng grep “Chọn hoa” không khớp trong run Windows. Chưa kết luận nguyên nhân nội dung/encoding; đề nghị trưởng nhóm kiểm tra fixture và trang tin. |

HTTP Customer **43 PASS / 0 FAIL**; hai script shell **1 PASS / 1 FAIL**. PHP lint, Node syntax và git diff --check đạt. Không thay đổi URL, HTML, CSS, DB schema/migrations hay Admin.

## Chạy lại

Trên DB thử sạch, chạy trong thư mục repo. Dùng cùng cấu hình DB cho cả PHP server và Node. Cần PHP PDO MySQL, mbstring, Playwright cho Node. Trên Windows đặt PLAYWRIGHT_MODULE nếu chưa cài module trong repo. Chạy SMTP và PHP ở hai terminal cùng thư mục, cùng biến môi trường.

~~~powershell
$env:DB_HOST='127.0.0.1'
$env:DB_PORT='3307'
$env:DB_NAME='cas_hoa_customer_test_tv219'
$env:CAS_APP_URL='http://127.0.0.1:8059'
$env:CAS_HTTP_RESULTS='.local/http-final.json'
New-Item -Force -ItemType Directory .local/sessions
php scripts/customer-setup.php
python scripts/customer-smtp.py
# Terminal PHP
php -d SMTP=127.0.0.1 -d smtp_port=1025 -S 127.0.0.1:8059 scripts/customer-router.php
# Terminal test
php -l actions/cart.php
node --check tests/customer/http.cjs
node tests/customer/http.cjs
~~~

Hai script bash cần instance MariaDB test dùng riêng, schema cas_hoa mới, DB_HOST=127.0.0.1, DB_PORT=3307, DB_NAME=cas_hoa, DB_USER/DB_PASSWORD của test. Chạy bash scripts/verify-r2.sh rồi bash scripts/verify-r2-auth-admin.sh. Trên máy này Git Bash cần ánh xạ /tmp sang đường dẫn Windows cho Python native, stdout LF, và đọc hết pipe grep để tránh curl exit 23. Các lần đầu dừng vì /tmp hoặc pipe trước khi hoàn tất. Chạy lại script Admin cần fixture mới vì tài khoản thử đã tồn tại.

## Log Customer (không chứa thông tin đăng nhập)

~~~
PASS Reject CSRF and malformed arrays
PASS Cart validation and persistence
PASS Checkout rejects invalid date, phone and missing address
PASS Other session starts with empty cart
PASS Duplicate aliases rejected before checkout
PASS Past date rejected
PASS Product withdrawn after cart save prevents order writes
PASS Legacy pickup replay writes one order and no delivery
PASS Server prices, transaction and replay protection
PASS Guest lookup correct phone, wrong phone and limited fields
PASS Success page reflects current database order status
PASS Wrapping validates mapping, state and canonical name
PASS Wrapping and unit price snapshots survive catalog edits
PASS Product 404 and admin visibility reflects database
PASS Register customer A and reject duplicates
PASS Profile update and authenticated order association
PASS Stored password is hashed and login rotates session
PASS Registration validation rejects invalid email, phone and arrays
PASS Unknown reset email returns generic response and rate limit
PASS Signed-in checkout preserves submitted buyer name and phone snapshot
PASS Customer isolation and direct admin access
PASS History pagination includes older orders
PASS Local SMTP delivers reset link; token is single-use and expires
PASS Contact persisted and rate limited
PASS Non-scalar GET inputs do not produce PHP warnings or expose private orders
PASS HTTP page index.php
PASS HTTP page san-pham.php
PASS HTTP page chi-tiet-san-pham.php?slug=lam-tinh
PASS HTTP page gio-hang.php
PASS HTTP page thanh-toan.php
PASS HTTP page dat-hang-thanh-cong.php
PASS HTTP page dang-nhap.php
PASS HTTP page dang-ky.php
PASS HTTP page quen-mat-khau.php
PASS HTTP page tai-khoan.php
PASS HTTP page lich-su-don-hang.php
PASS HTTP page tra-cuu-don-hang.php
PASS HTTP page tin-tuc.php
PASS HTTP page chi-tiet-tin-tuc.php?slug=chon-hoa-theo-mau
PASS HTTP page lien-he.php
PASS HTTP page gioi-thieu.php
PASS HTTP page chinh-sach-giao-hang.php
PASS HTTP page chinh-sach-doi-tra.php
~~~
