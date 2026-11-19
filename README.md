# CÁ'S HOA — Website thương mại điện tử bán hoa và quản lý cửa hàng

Đồ án môn **Lập trình Web và Ứng dụng** — nhóm 3 thành viên. Mã nguồn sử dụng **HTML5, CSS3, Bootstrap 5, JavaScript thuần, PHP 8.2+, MySQL 8 / MariaDB tương thích**; chạy trực tiếp trên **XAMPP (Apache + MySQL/MariaDB + PHP)** và không dùng framework ngoài hay dịch vụ bên thứ ba bắt buộc.

## Nhánh Git và trạng thái dự án

- **`main`** (`9cb1b995`): Nhánh nộp chính thức hiện tại (bảo toàn nền tảng trước đợt nâng cấp UI và refactor).
- **`feature/ui-professional-polish`** (`5ae77980`): Nhánh hoàn tất kiểm toán và nâng cấp UI/UX, tương phản WCAG AA, a11y và responsive (kèm báo cáo tại `docs/ui-audit/`).
- **`feature/project-structure-refactor`**: Nhánh tái cấu trúc mã nguồn từ nền `5ae77980`, **chưa merge vào `main`**. Giữ nguyên toàn bộ URL công khai, giao diện đã duyệt và logic nghiệp vụ PHP/MySQL.

Xem chi tiết kiến trúc, sơ đồ tách module và kết quả kiểm thử tái cấu trúc tại:
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- [docs/FILE_MOVE_MAP.md](docs/FILE_MOVE_MAP.md)
- [docs/REFACTOR_TEST_REPORT.md](docs/REFACTOR_TEST_REPORT.md)
- [docs/ui-audit/UI_AUDIT_REPORT.md](docs/ui-audit/UI_AUDIT_REPORT.md)

## Kiến trúc tổng quan (sau refactor)

- **Public Storefront routes (gốc dự án `*.php`)**: 19 trang khách hàng + `dang-xuat.php`, dùng chung layout HTML và điều hướng tài khoản qua `includes/layout.php`.
- **Public HTTP actions (`actions/*.php`)**: 5 endpoint POST trả JSON có CSRF và transaction (`cart.php`, `checkout.php`, `contact.php`, `flower-advisor.php`, `lookup.php`).
- **Dynamic PHP-JS feeds (`assets/js/products.php`, `assets/js/cart-r2.php`)**: Xuất dữ liệu sản phẩm/cài đặt và giỏ hàng Session an toàn cho JavaScript.
- **Public Admin routes (`admin/*.php`)**: 24 màn hình quản trị giữ nguyên URL, dùng chung khung giao diện qua `admin/_layout.php`, điều phối lõi qua `admin/_app.php` + `admin/modules/orders.php` và nhóm Admin Operations qua `admin/_ops.php` + 8 module trong `admin/modules/`.
- **Bảo vệ truy cập nội bộ**: Các thư mục `config/`, `includes/`, `database/`, `scripts/`, `tests/`, `docs/`, `admin/modules/` cùng tệp tiền tố `_` trong `admin/` và file `.htaccess` gốc / `assets/uploads/.htaccess` đều có luật chặn truy cập HTTP trực tiếp (`.htaccess` cho Apache và guard PHP `403 Forbidden`).

## Chạy XAMPP từ GitHub (Clean Clone)

Yêu cầu **XAMPP với PHP 8.2+** (`pdo_mysql`, `mbstring`, `fileinfo`), **MySQL 8.0 hoặc MariaDB tương thích**, **Apache** và **Git**.

```powershell
cd D:\xampp\htdocs   # hoặc C:\xampp\htdocs tùy nơi cài XAMPP
git clone https://github.com/tbao4151/Lap_trinh_web.git
cd Lap_trinh_web
git switch feature/project-structure-refactor
```

(Nếu phát triển trực tiếp ở thư mục riêng như `E:\Lap_trinh_web`, có thể trỏ Apache `Alias /Lap_trinh_web "E:/Lap_trinh_web"` với `AllowOverride All` hoặc đồng bộ vào `<xampp>\htdocs\Lap_trinh_web`.)

Trong XAMPP Control Panel bật **Apache** và **MySQL**, mở `http://localhost/phpmyadmin` và import các tệp SQL **theo đúng thứ tự**:

1. `database/schema.sql`
2. `database/seed.sql`
3. `database/migrations/001_auth.php.sql`
4. `database/migrations/002_inventory_seed.sql`
5. `database/migrations/003_admin_ops.sql`
6. `database/migrations/004_order_inventory_sessions.sql`
7. `database/migrations/005_profit_cost_snapshot.sql`

Mặc định `config/database.php` kết nối `127.0.0.1:3306`, database `cas_hoa`, user `root`, mật khẩu rỗng. Có thể ghi đè qua biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` (không commit `.env` hoặc mật khẩu).

Tạo tài khoản quản trị bằng PHP CLI (không dùng tài khoản mẫu cứng):

```bat
cd C:\xampp\htdocs\Lap_trinh_web
set CAS_ADMIN_EMAIL=owner-nhom@example.com
set CAS_ADMIN_PASSWORD=ThayBangMatKhauManh_2026!
set CAS_ADMIN_ROLE=owner
C:\xampp\php\php.exe scripts\create-admin.php
```

Mở trình duyệt tới `http://localhost/Lap_trinh_web/index.php` và `http://localhost/Lap_trinh_web/admin/dang-nhap.php`. Không mở trực tiếp bằng `file://` hoặc Live Server.

## Kiểm thử

- **Kiểm tra cú pháp (không cần DB):**
  - PHP lint toàn bộ `.php`: `php -l` từng file.
  - JS syntax: `node --check assets/js/*.js`.
- **Kiểm tra tích hợp trên database thử riêng (KHÔNG chạy trên database dữ liệu thật):**
  ```bash
  bash scripts/verify-r2.sh
  bash scripts/verify-r2-auth-admin.sh
  bash scripts/verify-admin-ops.sh
  ```
- Trên Windows với XAMPP cục bộ, sử dụng cơ chế DB riêng ở cổng khác hoặc đọc thêm tại `docs/REFACTOR_TEST_REPORT.md` và `docs/customer-preview.md`.

## Tư vấn chọn hoa và báo cáo lợi nhuận gộp (nâng cấp 005)

- **Khách hàng (`tu-van-hoa.php`)**: Lọc mẫu theo dịp, tông màu và ngân sách. Khi biến môi trường PHP có `OPENAI_API_KEY` và bật cURL, máy chủ gọi API tạo lời tư vấn dựa trên mẫu đang bán trong MySQL; khi không có API key hoặc lỗi mạng, hệ thống dùng chế độ `Gợi ý dựa trên bộ lọc sản phẩm`, không giả lập AI. Giới hạn 8 yêu cầu / 15 phút / phiên, kiểm tra CSRF.
- **Admin/Owner (`admin/loi-nhuan.php`)**: Biểu đồ doanh thu sau hoàn tiền, giá vốn nguyên liệu theo snapshot tại thời điểm xuất kho và lợi nhuận gộp (chưa trừ chi phí vận hành). Các đơn cũ trước migration `005` chưa có snapshot được thống kê riêng, không tính lợi nhuận giả.
