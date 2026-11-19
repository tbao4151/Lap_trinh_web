# CÁ'S HOA — Website thương mại điện tử bán hoa và quản lý cửa hàng

Đồ án môn **Lập trình Web và Ứng dụng** — nhóm 3 thành viên. Mã nguồn sử dụng **HTML5, CSS3, Bootstrap 5, JavaScript thuần, PHP 8.2+, MySQL 8 / MariaDB tương thích**; chạy trực tiếp trên **XAMPP (Apache + MySQL/MariaDB + PHP)** và không dùng framework ngoài hay dịch vụ bên thứ ba bắt buộc.

## Nhánh Git và trạng thái dự án

- **`main`**: Nhánh mặc định dùng cho bản nộp và clean clone. Sau khi Pull Request tích hợp cuối được nghiệm thu và merge, nhánh này có UI mới, kiến trúc PHP/MySQL và các sửa lỗi Customer/Admin.
- **`feature/ui-professional-polish`** (`5ae77980`): Lịch sử nâng cấp UI/UX, WCAG AA và responsive (báo cáo tại `docs/ui-audit/`).
- **`feature/project-structure-refactor`** (`d68e0a8e`): Lịch sử tái cấu trúc layout, module và bảo mật đường dẫn nội bộ.
- **`feature/tv2-customer-backend-final`** (`7bda713`, tác giả Jouise-source): Kiểm tra và sửa giỏ hàng, mở rộng bộ kiểm thử Customer ([PR #5](https://github.com/tbao4151/Lap_trinh_web/pull/5)).
- **`feature/tv3-admin-backend-final`** (`c70b877`, tác giả MysticMoth999): Sửa thống kê doanh thu sau hoàn tiền và luồng giao hàng, bổ sung kiểm thử Admin ([PR #6](https://github.com/tbao4151/Lap_trinh_web/pull/6)).
- **`integration/cas-hoa-final-20261119`**: Nhánh nghiệm thu tích hợp, với CI PHP 8.2/MySQL 8.0 trước khi đưa lên `main` ([PR #7](https://github.com/tbao4151/Lap_trinh_web/pull/7)).

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
# Mặc định Git clone nhánh main; không cần chuyển nhánh khác.
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
- Kiểm thử mở rộng: `tests/admin/test_admin_backend_final.py` (đối soát doanh thu, trạng thái giao hàng, giá vốn) và `tests/customer/http.cjs` (đăng nhập, giỏ hàng, checkout, reset mật khẩu). Cần cài môi trường test và chạy **chỉ trên database cô lập**; tham khảo `docs/TV2_CUSTOMER_BACKEND_TEST_REPORT.md` và `docs/TV3_ADMIN_BACKEND_TEST_REPORT.md`.
- Trên Windows với XAMPP cục bộ, sử dụng cơ chế DB riêng ở cổng khác hoặc đọc thêm tại `docs/REFACTOR_TEST_REPORT.md` và `docs/customer-preview.md`.

## Tư vấn chọn hoa và báo cáo lợi nhuận gộp (nâng cấp 005)

- **Khách hàng (`tu-van-hoa.php`)**: Lọc mẫu theo dịp, tông màu và ngân sách. Khi biến môi trường PHP có `OPENAI_API_KEY` và bật cURL, máy chủ gọi API tạo lời tư vấn dựa trên mẫu đang bán trong MySQL; khi không có API key hoặc lỗi mạng, hệ thống dùng chế độ `Gợi ý dựa trên bộ lọc sản phẩm`, không giả lập AI. Giới hạn 8 yêu cầu / 15 phút / phiên, kiểm tra CSRF.
- **Admin/Owner (`admin/loi-nhuan.php`)**: Biểu đồ doanh thu sau hoàn tiền, giá vốn nguyên liệu theo snapshot tại thời điểm xuất kho và lợi nhuận gộp (chưa trừ chi phí vận hành). Các đơn cũ trước migration `005` chưa có snapshot được thống kê riêng, không tính lợi nhuận giả.

## Giới hạn đã biết

- Gửi email đặt lại mật khẩu ra Internet phụ thuộc cấu hình SMTP của máy chủ; bài test chỉ xác minh SMTP loopback, không cam kết XAMPP mặc định gửi email được.
- Không chạy các script kiểm thử tích hợp với database có dữ liệu kinh doanh. Chúng có thể tạo đơn, tài khoản và cập nhật sản phẩm/kho.
