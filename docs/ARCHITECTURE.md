# KIẾN TRÚC HỆ THỐNG CÁ'S HOA

Tài liệu mô tả kiến trúc thực tế của repository trên nhánh `feature/project-structure-refactor` (tách từ mốc UI/UX `5ae7798031be052ebc24c0efb482fb8ea22426de`). Toàn bộ đường dẫn công khai dành cho XAMPP/Apache và giao diện người dùng sau đợt audit UI đều được giữ nguyên.

---

## 1. Cấu trúc thư mục hiện hành

```text
Lap_trinh_web/
├── .htaccess                      # Chặn liệt kê thư mục, dotfile, file .sql/.md/.log/.sh/.py/.ps1/.cjs/.docx
├── .gitignore                     # Loại trừ .env, *.log, .local/, __pycache__, file upload runtime
├── README.md                      # Hướng dẫn nhánh Git, cài đặt XAMPP, import SQL và kiểm thử
├── REQUIREMENT2_SETUP.md          # Tài liệu hướng dẫn triển khai Yêu cầu 2 gốc của nhóm
│
├── index.php                      # Các entrypoint Storefront công khai (19 trang khách + dang-xuat.php)
├── san-pham.php
├── chi-tiet-san-pham.php
├── gio-hang.php
├── thanh-toan.php
├── dat-hang-thanh-cong.php
├── tra-cuu-don-hang.php
├── tu-van-hoa.php
├── dang-ky.php / dang-nhap.php / quen-mat-khau.php / dang-xuat.php
├── tai-khoan.php / lich-su-don-hang.php
├── tin-tuc.php / chi-tiet-tin-tuc.php
├── gioi-thieu.php / lien-he.php / chinh-sach-giao-hang.php / chinh-sach-doi-tra.php
│
├── actions/                       # Public HTTP POST JSON endpoints (kiểm CSRF + validation + MySQL)
│   ├── cart.php                   # Đồng bộ giỏ hàng phiên với xác thực sản phẩm & giấy gói từ DB
│   ├── checkout.php               # Tạo đơn trong transaction, tính giá & phụ thu/phí ship từ server
│   ├── contact.php                # Lưu yêu cầu liên hệ (giới hạn tần suất 30 giây/lần)
│   ├── flower-advisor.php         # Tư vấn chọn hoa theo bộ lọc DB hoặc OpenAI Responses API
│   └── lookup.php                 # Tra cứu đơn hàng bằng mã đơn + SĐT người nhận (rate-limit 20/10 phút)
│
├── admin/                         # Khu vực quản trị (giữ nguyên 24 URL công khai admin/*.php)
│   ├── .htaccess                  # Tắt Indexes, chặn truy cập HTTP trực tiếp vào các file _*.php
│   ├── dang-nhap.php              # Trang đăng nhập nội bộ (staff / admin / owner)
│   ├── index.php / don-hang.php / khach-hang.php / bao-cao.php     # 4 route lõi qua _app.php
│   ├── san-pham.php / them-san-pham.php / sua-san-pham.php / ...   # 19 route vận hành qua _ops.php
│   ├── loi-nhuan.php              # Entrypoint mỏng gọi modules/analytics.php
│   ├── cong-thuc-hoa.php          # Entrypoint mỏng gọi modules/recipes.php
│   ├── _layout.php                # Khung HTML Admin dùng chung (header, sidebar, topbar, alert, drawer JS)
│   ├── _nav.php                   # Menu điều hướng 22 mục theo nhóm và quyền (staff/admin/owner)
│   ├── _actions.php               # Bootstrapper cho 4 route lõi (gọi modules/orders.php)
│   ├── _app.php                   # Dispatcher cho 4 route lõi (gọi _layout.php + modules/orders.php)
│   ├── _ops.php                   # Nền chung RBAC, validate input, upload ảnh, POST handler & audit log
│   └── modules/                   # 9 module nghiệp vụ Admin tách biệt (chặn truy cập HTTP trực tiếp)
│       ├── .htaccess              # Require all denied
│       ├── analytics.php          # Biểu đồ lợi nhuận gộp & snapshot giá vốn (cas_profit_page)
│       ├── delivery.php           # Lịch giao hàng 7 ngày & luồng trạng thái giao (cas_delivery_page)
│       ├── inventory.php          # Kho tổng, kho hoa, kho phụ kiện & lịch sử kho (FOR UPDATE)
│       ├── orders.php             # Lõi dashboard, đơn hàng (trừ/hoàn kho), khách hàng, báo cáo doanh thu
│       ├── people.php             # Nhân viên, hồ sơ cá nhân, chủ sở hữu & nhật ký audit
│       ├── products.php           # Sản phẩm, thư viện nhiều ảnh, giá nhanh & phân loại
│       ├── recipes.php            # Định mức nguyên liệu cho từng sản phẩm (cas_recipes_page)
│       ├── refunds.php            # Quy trình hoàn tiền thủ công & đồng bộ payment_status
│       └── storefront.php         # Giấy gói, nhóm khám phá trang chủ & cài đặt cửa hàng
│
├── assets/
│   ├── css/                       # style.css, customer.css, responsive.css, admin.css
│   ├── js/                        # main-r2.js, customer-*.js, flower-advisor.js, r2-*.js + feeds PHP-JS
│   │   ├── products.php           # Xuất window.CAS_PRODUCTS & window.CAS_SETTINGS từ MySQL
│   │   └── cart-r2.php            # Xuất window.CASCart đồng bộ phiên server
│   ├── images/                    # Ảnh sản phẩm tĩnh đóng gói theo repo
│   ├── uploads/                   # Ảnh tải lên từ Admin (.htaccess tắt PHP/script & X-Content-Type-Options)
│   └── vendor/                    # Bootstrap CSS/JS & Bootstrap Icons cục bộ (không phụ thuộc CDN)
│
├── config/
│   ├── .htaccess                  # Require all denied
│   └── database.php               # Kết nối PDO singleton (`cas_db()`), đọc env hoặc mặc định XAMPP
│
├── includes/                      # Lớp hạ tầng, bảo mật và giao diện dùng chung (chặn HTTP trực tiếp)
│   ├── .htaccess                  # Require all denied
│   ├── bootstrap.php              # Session cookie HttpOnly/SameSite, escape XSS (`cas_e`), CSRF, JSON helpers
│   ├── layout.php                 # Khung HTML dùng chung Storefront, auth shell, account nav, nhãn trạng thái
│   ├── auth.php                   # Đăng nhập/xuất, `session_version`, `cas_require_user()`, CSRF form
│   ├── customer.php               # Validation text & error sanitizer cho các route khách hàng
│   ├── customer-recommendations.php # Partial form gợi ý hoa đặt ở trang chủ
│   ├── order-inventory.php        # Trừ kho và hoàn kho nguyên liệu theo snapshot (trong transaction)
│   ├── r2-status.php              # Đánh dấu phiên bản nhánh Yêu cầu 2
│   └── storefront.php             # Truy vấn đọc cài đặt shop, nhóm khám phá, giấy gói, gallery & bộ lọc
│
├── database/
│   ├── .htaccess                  # Require all denied
│   ├── schema.sql                 # Cấu trúc 17 bảng lõi
│   ├── seed.sql                   # Dữ liệu mẫu sản phẩm, tin tức và danh mục
│   └── migrations/                # 001..005 tăng dần, giữ nguyên thứ tự cài đặt
│
├── scripts/                       # Công cụ CLI tạo admin, dev server router & verify scripts (.htaccess chặn web)
├── tests/                         # Bộ kiểm thử Python Admin Ops (`247` kiểm tra) và Customer CJS (.htaccess chặn web)
└── docs/                          # Tài liệu kiến trúc, mapping refactor, test reports và `docs/ui-audit/` (.htaccess chặn web)
```

---

## 2. Trách nhiệm các tầng & luồng xử lý Request

### A. Luồng Storefront (`HTTP -> Root Entrypoint -> Includes -> PDO/MySQL`)

1. Trình duyệt gửi request tới file công khai ở gốc website (ví dụ `index.php`, `san-pham.php`, `chi-tiet-san-pham.php`).
2. Entrypoint nạp một trong các tệp tầng nền trong `includes/` (`bootstrap.php`, `auth.php`, `customer.php`, `storefront.php`). Các tệp này đều tự động nạp `config/database.php` và `includes/layout.php`.
3. Các trang gọi `cas_page_start(...)` (hoặc `cas_auth_start(...)` cho nhóm đăng nhập/đăng ký/quên mật khẩu) để render `<head>`, thẻ mở `<body>` với `data-page` và phần thân riêng của từng màn hình, cuối trang gọi `cas_page_end(...)` (hoặc `cas_auth_end()`).
4. Phía trình duyệt tải `assets/js/products.php` (đọc bảng `products`, `product_images`, `product_wrapping`, `shop_settings`) và `assets/js/cart-r2.php` (đồng bộ `$_SESSION['cas_cart']`), sau đó chạy script tương tác thuần JS (`main-r2.js`, `customer-catalog.js`, `flower-advisor.js`, `r2-lookup.js`, `r2-contact.js`, `customer-forms.js`).
5. Mọi thao tác ghi dữ liệu của khách (giỏ hàng, đặt đơn, liên hệ, tư vấn) gửi POST tới `actions/*.php` kèm token `window.CAS_CSRF`, được server kiểm tra và thực thi trên MySQL qua PDO prepared statements.

### B. Luồng Admin (`HTTP -> admin/*.php -> Layout & Module -> Includes -> MySQL`)

1. Trình duyệt truy cập 1 trong 24 URL `admin/*.php` hiện có (`admin/index.php`, `admin/don-hang.php`, `admin/san-pham.php`, `admin/loi-nhuan.php`, ...).
2. **Nhóm 4 route lõi** (`index.php`, `don-hang.php`, `khach-hang.php`, `bao-cao.php`):
   - Nạp `admin/_app.php` → nạp `admin/_actions.php` → nạp `includes/auth.php`, `includes/order-inventory.php`, `admin/_layout.php`, `admin/modules/orders.php`.
   - Kiểm tra đăng nhập & quyền qua `cas_require_user(true)`, xử lý POST `order_update` (trừ/hoàn kho nguyên liệu trong `transaction`), sau đó render bằng `cas_admin_layout_start()` + `cas_admin_render_core_page()` + `cas_admin_layout_end()`.
3. **Nhóm 19 route vận hành & phân tích** (gồm cả `loi-nhuan.php` và `cong-thuc-hoa.php`):
   - Nạp `admin/_ops.php` (nạp `includes/auth.php`, `includes/storefront.php`, `admin/_layout.php`), rồi nạp đúng 1 module trong `admin/modules/` và gọi hàm `cas_*_page()`.
   - Kiểm tra quyền chi tiết theo bảng `CAS_PERMISSIONS` (`cas_page_roles()`, `cas_require_permission()`), xử lý POST qua `cas_ops_post()` với transaction rollback an toàn và ghi `admin_audit_log`, rồi render bằng `cas_ops_start()` / `cas_ops_end()` (ủy quyền trực tiếp tới `cas_admin_layout_start()` / `cas_admin_layout_end()`).
4. `admin/dang-nhap.php` giữ luồng đăng nhập riêng biệt dùng `includes/auth.php` và `assets/css/admin.css`.

---

## 3. Lớp phòng thủ nhiều tầng (Defense-in-Depth)

1. **Chặn truy cập HTTP trực tiếp ở cấp web server:**
   - Apache: các file `.htaccess` đặt tại gốc dự án, `admin/`, `admin/modules/`, `config/`, `includes/`, `database/`, `scripts/`, `tests/`, `docs/` sử dụng `Options -Indexes` và `Require all denied` (kèm fallback Apache 2.2) để từ chối duyệt thư mục hoặc mở trực tiếp file nội bộ, file `.sql`/`.md`/`.log`/`.py`/`.sh` và dotfiles.
   - `assets/uploads/.htaccess` chặn chạy PHP/script trong thư mục upload và gắn `X-Content-Type-Options: nosniff`.
   - PHP dev server router ([scripts/customer-router.php](scripts/customer-router.php)): đồng bộ chặn `/admin/modules/`, `/admin/_*` và các đường dẫn nội bộ tương ứng.
2. **Guard cấp PHP trên 23 file nội bộ (`config/`, `includes/`, `admin/_*`, `admin/modules/`):**
   - Mỗi file PHP không phải URL công khai đều kiểm tra `PHP_SAPI !== 'cli'` và so sánh `realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)`; nếu bị gọi trực tiếp qua HTTP (kể cả khi `AllowOverride None` làm Apache bỏ qua `.htaccess` hoặc chạy `php -S` không qua router) lập tức trả về `403 Forbidden`.
3. **Bảo toàn cơ chế an toàn ứng dụng:**
   - Session cookie `HttpOnly` + `SameSite=Lax`, thu hồi mọi phiên qua `users.session_version`.
   - CSRF token dùng `hash_equals()`, thoát HTML bằng `cas_e()`, 100% truy vấn dùng PDO prepared statements với `ATTR_EMULATE_PREPARES => false`.
   - Upload ảnh kiểm tra `finfo`, `getimagesize()`, giới hạn kích thước và sinh tên file ngẫu nhiên.
