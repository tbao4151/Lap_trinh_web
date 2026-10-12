# Yêu cầu 2 - PHP + MySQL (bản nộp gấp)

**Không sửa branch main đã nộp yêu cầu 1.** Branch tích hợp: `feature/requirement-2-php-mysql`.

## Cài đặt với XAMPP

1. Dùng PHP 8.1+ và MySQL 8.0+/MariaDB tương thích.
2. Đặt dự án ở `htdocs/Lap_trinh_web`.
3. Mở phpMyAdmin, chạy `database/schema.sql` trước, sau đó `database/seed.sql`.
4. Mặc định kết nối: host 127.0.0.1, MySQL port 3306, database cas_hoa, user root, password rỗng (cấu hình XAMPP thông dụng). Có thể đổi qua biến môi trường DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD.
5. Truy cập `http://localhost/Lap_trinh_web/index.php` (không mở bằng file:// hoặc Live Server).
6. Trang sản phẩm cần chạy `assets/js/products.php` để lấy dữ liệu MySQL; DB phải được import trước.

## Phạm vi gấp cho yêu cầu 2

Giữ giao diện Bootstrap/HTML của yêu cầu 1, phát triển luồng PHP/MySQL chính: danh mục sản phẩm, chi tiết, giỏ, thanh toán lưu đơn, tra cứu đơn, tin tức, liên hệ, thông tin. Các mục xác thực tài khoản và Admin để nhóm triển khai sau.

**Bảo mật:** Không tin giá do JS truyền lên; checkout phải tính lại từ bảng products; dùng PDO prepared statements, transaction, CSRF token. Không dùng dữ liệu demo LocalStorage làm bằng chứng MySQL.

## Kiểm thử nộp

Chụp ảnh thật các trang ở localhost với thanh địa chỉ trong ảnh. Test chọn sản phẩm, thanh toán, tra cứu đúng mã đơn & điện thoại, kiểm tra dữ liệu trong MySQL. Chưa kiểm thử tại XAMPP/MySQL của người nộp thì không tuyên bố đã hoàn thành 100%.
