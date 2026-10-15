# CÁ'S HOA — Yêu cầu 2 (PHP + MySQL) — Hướng dẫn triển khai

## Phạm vi đã thống nhất

Đồ án học thuật sử dụng **HTML5, CSS3, JavaScript, PHP và MySQL**, tách khỏi website kinh doanh `Web_Flower_Manus`.

Bản Web tĩnh yêu cầu 1 có 18 màn hình khách hàng và 22 màn hình quản trị. Phạm vi mục tiêu yêu cầu 2 là **18 trang khách hàng + 12 trang Admin = 30/40 trang (75% tính theo số trang)**. Con số này là chỉ tiêu tiến độ của nhóm, không thay thế cách giảng viên chấm 70% FrontEnd.

Các route mới trên branch `feature/requirement-2-php-mysql` là tệp `.php`. Bản `main` yêu cầu 1 được giữ nguyên.

## Cài đặt XAMPP (Windows)

1. Cài XAMPP với PHP 8.2+ và MySQL 8.0+ (hoặc MariaDB tương thích); bật Apache và MySQL.
2. Chép project vào `C:\xampp\htdocs\Lap_trinh_web` hoặc clone branch trên vào đúng thư mục.
3. Mở `http://localhost/phpmyadmin`, lần lượt import:
   - `database/schema.sql` — cấu trúc 17 bảng.
   - `database/seed.sql` — 11 sản phẩm, 6 bài viết và các bảng danh mục.
   - `database/migrations/001_auth.php.sql` — bảng token đặt lại mật khẩu và vai trò owner.
   - `database/migrations/002_inventory_seed.sql` — dữ liệu kho mẫu từ yêu cầu 1.
   - `database/migrations/003_admin_ops.sql` — Admin Operations: ảnh sản phẩm, lịch sử kho trước/sau, giấy gói có phụ thu, danh mục khám phá, lịch sử giao hàng/hoàn tiền, cài đặt shop, nhật ký quản trị. Chỉ thêm bảng/cột, chạy lại nhiều lần không lỗi và không xoá dữ liệu.
4. Kết nối PDO mặc định: host `127.0.0.1`, cổng `3306`, database `cas_hoa`, user `root`, password trống theo XAMPP thông dụng. Nếu cấu hình khác, đặt `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` trong môi trường máy chủ.
5. Truy cập `http://localhost/Lap_trinh_web/index.php`. **Không mở bằng file:// hay Live Server**.

## Cấp tài khoản quản trị an toàn

Không sử dụng tài khoản `owner@cashoa.vn / 1234` của giao diện mẫu và **không hardcode** mật khẩu vào mã nguồn.

Trong Command Prompt của Windows (thay thông tin theo người phụ trách):

```bat
cd C:\xampp\htdocs\Lap_trinh_web
set CAS_ADMIN_EMAIL=admin-nhom@example.com
set CAS_ADMIN_PASSWORD=MotMatKhauManh_2026!
C:\xampp\php\php.exe scripts\create-admin.php
```

Script chỉ chạy CLI, tạo mật khẩu bằng `password_hash()`, từ chối tài khoản/email trùng. Sau đó truy cập `http://localhost/Lap_trinh_web/admin/dang-nhap.php`.

Tạo tài khoản theo vai trò (mặc định `admin`): thêm `set CAS_ADMIN_ROLE=owner` hoặc `staff`, tuỳ chọn `set CAS_ADMIN_NAME=Họ tên`. Chỉ nhận `staff`, `admin`, `owner`. Sau khi đã có chủ sở hữu, các tài khoản nội bộ khác nên tạo trong `admin/nhan-vien.php` (admin tạo nhân viên; chủ sở hữu tạo cả quản trị viên) và nâng/hạ chủ sở hữu ở `admin/chu-so-huu.php` (luôn giữ ít nhất một chủ sở hữu hoạt động).

Thư mục `assets/uploads/` lưu ảnh Admin tải lên; cần quyền ghi cho Apache. File `.htaccess` trong thư mục chặn chạy script; ảnh tải lên không được commit (`.gitignore`).

## Chức năng đã lập trình

- Khách hàng: trang chủ/catalog/chi tiết sản phẩm đọc MySQL; giỏ hàng lưu PHP Session, JavaScript đồng bộ qua CSRF; checkout transaction, tra cứu đơn, xác nhận đơn; danh sách và chi tiết bài viết; liên hệ; đăng ký, đăng nhập, đăng xuất, hồ sơ, lịch sử đơn liên kết tài khoản; quên mật khẩu bằng token có hạn sử dụng và dùng một lần; giới thiệu và chính sách.
- Admin: đăng nhập Session với role staff/admin/owner; Dashboard; đơn hàng; giao hàng; khách hàng; sản phẩm; thêm/sửa sản phẩm; đổi giá/trạng thái nhanh; phân loại; kho tổng; báo cáo doanh thu.
- Giữ nguyên giao diện HTML/CSS/Bootstrap của yêu cầu 1 ở phía khách hàng và phong cách trang quản trị.

**Phụ thuộc phải cấu hình riêng:** PHP `mail()` cần máy chủ gửi email hoặc SMTP/sendmail hoạt động. Trang quên mật khẩu không tự giả lập OTP hay hiển thị token trên website công khai. Cần xác minh gửi/nhận email trước khi nghiệm thu toàn bộ tính năng trên máy XAMPP.

## Kiểm thử và PDF

GitHub Actions: [PHP coursework validation](https://github.com/tbao4151/Lap_trinh_web/actions). Pipeline chạy PHP lint, JavaScript lint, MySQL 8 integration và Chrome full-window screenshots. Các bài kiểm thử có trong:

- `scripts/verify-r2.sh`
- `scripts/verify-r2-auth-admin.sh`
- `scripts/capture-r2.py`
- `scripts/verify-admin-ops.sh` + `tests/admin/test_admin_ops.py` — Admin Operations: migration 003 chạy 2 lần, tài khoản owner/admin/staff tạo bằng CLI với mật khẩu ngẫu nhiên, ma trận 22 route × 5 vai trò và CRUD/permission bằng HTTP + MySQL. Kết quả và ảnh: `docs/admin-ops-test-report.md`.

### Reset database kiểm thử

Các script kiểm thử ghi dữ liệu thật vào `cas_hoa`. **Chỉ dùng trên database kiểm thử**, không chạy trên dữ liệu thật:

```bash
mysql -uroot -e "DROP DATABASE IF EXISTS cas_hoa"
mysql -uroot < database/schema.sql
mysql -uroot < database/seed.sql
mysql -uroot < database/migrations/001_auth.php.sql
mysql -uroot < database/migrations/002_inventory_seed.sql
mysql -uroot < database/migrations/003_admin_ops.sql
```

Chạy lại toàn bộ như CI (cần PHP 8.2+, MySQL client, Python 3): `bash scripts/verify-r2.sh && bash scripts/verify-r2-auth-admin.sh && bash scripts/verify-admin-ops.sh`.

Khi lần chạy CI mới nhất hoàn tất thành công, vào **Actions → workflow mới nhất → Artifacts → CAS_HOA_PHP_MySQL_BaoCaoYeuCau2**. ZIP chứa PDF 31 trang (bìa + 30 màn hình) và ảnh PNG thực có thanh địa chỉ `127.0.0.1:8033/...php`. Xem lại PDF trước khi nộp; không dùng ảnh HTML hoặc dựng thanh địa chỉ giả.

## Công việc tiếp theo

- Kiểm thử hồi quy và bảo mật bổ sung, đặc biệt luồng reset mật khẩu với dịch vụ mail thực, truy cập sai quyền, input sai và dữ liệu SQL.
- Rà soát UI responsive trên nhiều viewport và dữ liệu biên.
- 10 màn hình Admin còn lại (hoàn tiền, giấy gói, khám phá, kho hoa, kho phụ kiện, lịch sử kho, nhân viên, cài đặt, tài khoản, chủ sở hữu) đã có route PHP trên branch `feature/admin-operations-completion`; các điểm cần trưởng nhóm duyệt ghi ở `docs/admin-ops-contracts.md`.


### Nâng cấp 004 (sau tích hợp)

Sau migration `003_admin_ops.sql`, import `database/migrations/004_order_inventory_sessions.sql`. Tại Admin → Công thức nguyên liệu, khai báo định mức nguyên liệu cho từng sản phẩm trước khi chuyển đơn sang **Đang chuẩn bị**. Hệ thống xuất/hoàn nguyên liệu theo một transaction và không cho xuất thiếu tồn. Mọi phiên đăng nhập trước khi cập nhật 004 phải đăng nhập lại. Hoàn tiền từng phần/đủ được phản ánh vào `orders.payment_status` sau bước ghi nhận thủ công (không chuyển tiền thật).
