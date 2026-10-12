> **CẢNH BÁO PHẠM VI (2026-10-12):** Tài liệu này mô tả **bản demo/nộp tạm 13 trang khách hàng** và không đại diện cho mức hoàn thành 70% toàn bộ đồ án. Đề tài gốc có **18 trang khách hàng + 22 trang Admin = 40 trang**. Với phạm vi nhóm thống nhất, 13 ảnh trong PDF cũ chỉ tương đương 32,5% số màn hình, chưa kể các chức năng chưa hoàn chỉnh. **Không sử dụng PDF này làm bản nghiệm thu 70% toàn hệ thống.** Kế hoạch và tiêu chí nghiệm thu chính thức cho lần phát triển tiếp theo: [docs/REQUIREMENT2_SCOPE_AND_ACCEPTANCE.md](docs/REQUIREMENT2_SCOPE_AND_ACCEPTANCE.md).

# CÁ'S HOA - Hướng dẫn chạy và nộp Yêu cầu 2

**Repo học thuật:** https://github.com/tbao4151/Lap_trinh_web  
**Branch giai đoạn 2:** `feature/requirement-2-php-mysql`  
**Không sửa `main` (bản đã nộp yêu cầu 1).** Website kinh doanh `Web_Flower_Manus` không thuộc phạm vi code này.

## 1. Khởi chạy trên XAMPP

1. Tải branch này từ GitHub dưới dạng ZIP hoặc `git checkout feature/requirement-2-php-mysql`.
2. Chép toàn bộ thư mục thành `C:\\xampp\\htdocs\\Lap_trinh_web`.
3. Mở XAMPP Control Panel, khởi chạy **Apache** và **MySQL**.
4. Vào `http://localhost/phpmyadmin`, import `database/schema.sql`, sau đó import `database/seed.sql`.
5. Mặc định kết nối `127.0.0.1:3306`, database `cas_hoa`, user `root`, password rỗng. Nếu khác, cấu hình qua các biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`.
6. Mở `http://localhost/Lap_trinh_web/index.php`. Không chạy `file://` hoặc VS Code Live Server, vì PHP và MySQL cần webserver.

## 2. Phạm vi nộp gấp: 13/18 trang khách hàng

**Đã chuyển PHP và có chức năng phù hợp:**

- `index.php`, `san-pham.php`, `chi-tiet-san-pham.php`: dữ liệu sản phẩm từ MySQL, lọc/tìm kiếm phía JS.
- `gio-hang.php`: giỏ hàng LocalStorage, giữ tương tác HTML/JS của yêu cầu 1.
- `thanh-toan.php`, `dat-hang-thanh-cong.php`: PHP xử lý POST, tính lại giá từ MySQL, lưu `customers`, `orders`, `order_items` và lịch giao bằng transaction; hiển thị xác nhận bằng session.
- `tra-cuu-don-hang.php`: PHP/MySQL tra cứu theo mã đơn + số điện thoại người nhận.
- `tin-tuc.php`, `chi-tiet-tin-tuc.php`: bài viết từ bảng `news`.
- `lien-he.php`: lưu `contacts` với validate/CSRF.
- `gioi-thieu.php`, `chinh-sach-giao-hang.php`, `chinh-sach-doi-tra.php`: giao diện nội dung tĩnh chạy qua PHP.

**Chưa tính là hoàn thành:** `dang-nhap.php`, `dang-ky.php`, `quen-mat-khau.php`, `tai-khoan.php`, `lich-su-don-hang.php` (chưa triển khai xác thực PHP/MySQL). Các form demo đã vô hiệu hóa và có cảnh báo.

**Cách tính 13/18 = 72,2%** chỉ là tỷ lệ theo số trang, không thay thế tiêu chí chấm điểm do giảng viên công bố. Phần giỏ hàng vẫn dùng JS/LocalStorage và sẽ được mở rộng sau.

## 3. Cách kiểm tra

- Trang chủ phải hiển thị sản phẩm; chọn sản phẩm -> thêm giỏ -> thanh toán -> nhập đủ dữ liệu -> nhận mã đơn.
- Tra cứu đơn bằng mã vừa tạo và SĐT người nhận đã nhập.
- Mở phpMyAdmin: kiểm tra các bảng `orders`, `order_items`, `customers`, `contacts`.
- Tin tức và chi tiết phải hiển thị các bài đã seed.
- Test trên GitHub Actions: **PHP coursework validation** (syntax + MySQL 8 integration).
- Tham khảo script kiểm thử: `scripts/verify-r2.sh`.

## 4. Minh chứng PDF bắt buộc

Yêu cầu giảng viên: **PDF gồm ảnh các trang PHP có thanh địa chỉ Web**.

GitHub Actions chạy Chrome thật trên PHP/MySQL tại `http://127.0.0.1:8033`, chụp cả cửa sổ trình duyệt, rồi tạo `evidence/CAS_HOA_Yeu_Cau_2.pdf`. Khi workflow thành công, vào tab **Actions > PHP coursework validation > lần chạy mới nhất > Artifacts**, tải `CAS_HOA_PHP_MySQL_BaoCaoYeuCau2`; trong ZIP có PDF và ảnh gốc.

Trước khi nộp: mở PDF kiểm tra giao diện và thanh địa chỉ. Nếu có lỗi hiển thị trong ảnh, phải chụp lại trên XAMPP bằng Chrome và thay ảnh đúng; **không dựng ảnh địa chỉ giả**.

## 5. Phân chia phần còn lại sau hạn

Trần Gia Hưng: đăng ký, đăng nhập, quên mật khẩu, PHP session.  
Phạm Hiệp Phú: tài khoản, lịch sử đơn, bổ sung giao diện hoặc tính năng tương ứng.  
Nguyễn Văn Thiên Bảo: tích hợp, kiểm thử và xử lý xung đột.

Tiến độ hoàn thành trong tài liệu này căn cứ vào CI và mã nguồn; không tuyên bố đã thử trên XAMPP cá nhân nếu chưa thực hiện.
