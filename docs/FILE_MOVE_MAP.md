# BÁO CÁO KIỂM KÊ BAN ĐẦU & SƠ ĐỒ DI CHUYỂN FILE (`feature/project-structure-refactor`)

- **Nhánh nguồn UI:** `feature/ui-professional-polish` (`5ae7798031be052ebc24c0efb482fb8ea22426de`)
- **Nhánh refactor:** `feature/project-structure-refactor` (tách từ `5ae7798031be052ebc24c0efb482fb8ea22426de`)
- **Nhánh nộp gốc (`main`):** `9cb1b9955296b2e5f856373d590097f8410376c5`

---

## 1. Báo cáo kiểm kê ban đầu trước refactor

Qua khảo sát toàn bộ cây mã nguồn thực tế (`79` file PHP khi hoàn tất, `13` file JS thuần, `4` CSS, `7` SQL, test suite & workflows), hệ thống ban đầu có những vấn đề kỹ thuật sau theo mức độ ưu tiên:

| Mức độ | Vấn đề phát hiện khi kiểm kê | Tác động |
|---|---|---|
| **P0 (Bảo mật cấu trúc)** | Thư mục nội bộ (`config/`, `includes/`, `database/`, `scripts/`, `tests/`, `docs/`, `admin/modules/`) và các tệp `admin/_app.php`, `_ops.php`, `_actions.php`, `_nav.php` nằm dưới webroot XAMPP nhưng chưa có `.htaccess` chặn truy cập và chưa có guard chống gọi trực tiếp qua HTTP. | Nếu gọi thẳng qua trình duyệt, một số tệp chạy logic thừa hoặc lộ thông báo hệ thống; thư mục chưa tắt index. |
| **P1 (Lặp HTML/Layout lớn)** | Cả **19 trang Storefront** tự lặp nguyên khối `<!doctype html> ... <head> ... CSS/Bootstrap` ở đầu và `#siteFooter` + danh sách `<script>` + `CAS_CSRF` ở cuối; **3 trang auth** lặp nguyên cột `.auth-story` và `.auth-brand`; **Admin** duy trì hai khung layout HTML gần như giống hệt nhau ở `admin/_app.php` (dài 256 dòng) và `admin/_ops.php` (dài 357 dòng). | Khi cập nhật header/asset hoặc drawer mobile phải sửa lặp lại ở nhiều file, dễ lệch nhau như từng xảy ra ở đợt audit UI. |
| **P1 (Code chết & ranh giới module Admin)** | `admin/_app.php` còn giữ nguyên 7 nhánh `switch` không bao giờ chạy tới (`delivery`, `products`, `product_add`, `product_edit`, `product_quick`, `taxonomy`, `inventory`) và `admin/_actions.php` còn giữ handler trùng với `admin/modules/products.php` / `delivery.php`. Ngoài ra `admin/loi-nhuan.php` và `admin/cong-thuc-hoa.php` nhúng toàn bộ nghiệp vụ trực tiếp trong file route thay vì tách vào `admin/modules/` như 21 route còn lại. | Gây hiểu nhầm khi đọc/bảo trì Admin và phá vỡ quy ước entrypoint 5 dòng của thư mục `admin/`. |
| **P2 (Lặp danh sách trạng thái & điều hướng)** | Danh sách ánh xạ `order_status` tiếng Việt bị khai báo rời rạc ở `dat-hang-thanh-cong.php`, `lich-su-don-hang.php` và `admin/_actions.php`; khối `<nav class="account-nav">` lặp giữa `tai-khoan.php` và `lich-su-don-hang.php`. | Dễ sai lệch nhãn trạng thái giữa trang khách và Admin (ví dụ `Đã huỷ` vs `Đã hủy`, hoặc thiếu `ready`). |
| **P2 (Router dev server)** | `scripts/customer-router.php` chặn `includes/`, `config/`, `database/`... nhưng chưa chặn `/admin/modules/` và `/admin/_*`. | Môi trường preview `php -S` với router chưa mô phỏng đầy đủ ranh giới thư mục nội bộ Admin. |

---

## 2. Xếp loại ưu tiên và thay đổi đã thực hiện (`Cũ -> Mới`)

| Hạng mục | Đường dẫn cũ / Hiện trạng | Đường dẫn mới / Trích xuất | Mức rủi ro | Tham chiếu & điểm phụ thuộc đã cập nhật |
|---|---|---|---|---|
| **Chặn truy cập HTTP cấp thư mục** | Chưa có `.htaccess` cho webroot, `admin/` và các thư mục nội bộ | Tạo mới `.htaccess` (gốc), `admin/.htaccess`, `admin/modules/.htaccess`, `config/.htaccess`, `includes/.htaccess`, `database/.htaccess`, `scripts/.htaccess`, `tests/.htaccess`, `docs/.htaccess` | **An toàn cao** | Bảo toàn `assets/uploads/.htaccess`; tương thích Apache 2.4 (`mod_authz_core`) và fallback 2.2; giữ nguyên URL `.php` công khai. |
| **Chặn truy cập PHP nội bộ trực tiếp** | Các tệp trong `config/`, `includes/`, `admin/_*`, `admin/modules/` chạy khi gọi URL trực tiếp | Thêm guard `PHP_SAPI !== 'cli' && realpath(SCRIPT_FILENAME) === realpath(__FILE__) -> 403` trên toàn bộ **23 file PHP nội bộ** | **An toàn cao** | Cập nhật thêm regex chặn `/admin/modules/` và `/admin/_` trong [scripts/customer-router.php](file:///e:/Lap_trinh_web/scripts/customer-router.php). |
| **Chuẩn hóa layout Storefront** | Lặp `<head>`, `header`/`footer`, scripts ở 19 trang khách + 3 trang auth + `<nav class="account-nav">` + bảng trạng thái | Tạo mới [includes/layout.php](file:///e:/Lap_trinh_web/includes/layout.php) (`cas_page_start`, `cas_page_end`, `cas_auth_start`, `cas_auth_end`, `cas_account_nav`, `cas_order_statuses`, `cas_order_status_label`) | **Trung bình** | Nạp tự động qua `require_once __DIR__ . '/layout.php'` trong [includes/bootstrap.php](file:///e:/Lap_trinh_web/includes/bootstrap.php); cập nhật cả 19 file Storefront PHP ở gốc để dùng helper chung mà giữ nguyên 100% DOM, ID, class và thứ tự script. |
| **Hợp nhất khung layout Admin** | Trùng lặp toàn bộ khung HTML sidebar/topbar/hero/alert/JS drawer giữa `admin/_app.php` và `admin/_ops.php` | Tạo mới [admin/_layout.php](file:///e:/Lap_trinh_web/admin/_layout.php) (`CAS_ROLE_LABELS`, `cas_money`, `cas_admin_layout_start`, `cas_admin_layout_end`) | **Trung bình** | `admin/_ops.php` nạp `_layout.php` và ủy quyền `cas_ops_start()`/`cas_ops_end()`; `admin/_app.php` gọi trực tiếp `cas_admin_layout_start()`/`cas_admin_layout_end()`. Giữ text thuần trong `<div class="alert ...">` tương thích regex `flash()` của `tests/admin/test_admin_ops.py`. |
| **Tách module lõi Admin & bỏ code chết** | Logic `order_update` + 7 nhánh chết trong `admin/_actions.php` & `admin/_app.php` | Tạo mới [admin/modules/orders.php](file:///e:/Lap_trinh_web/admin/modules/orders.php) chứa logic + 4 view đang chạy (`dashboard`, `orders`, `customers`, `reports`) | **Trung bình** | `admin/_actions.php` (21 dòng) và `admin/_app.php` (22 dòng) giữ đúng hợp đồng biến `$user`, `$db`, `$error`, `$notice`, `$statuses`, `$payments` cho `admin/index.php`, `don-hang.php`, `khach-hang.php`, `bao-cao.php`. |
| **Tách module lợi nhuận & công thức** | `admin/loi-nhuan.php` (102 dòng) và `admin/cong-thuc-hoa.php` (77 dòng) chứa toàn bộ SQL & HTML | Tạo mới [admin/modules/analytics.php](file:///e:/Lap_trinh_web/admin/modules/analytics.php) (`cas_profit_page()`) và [admin/modules/recipes.php](file:///e:/Lap_trinh_web/admin/modules/recipes.php) (`cas_recipes_page()`) | **An toàn cao** | Biến `admin/loi-nhuan.php` và `admin/cong-thuc-hoa.php` thành entrypoint 5 dòng đúng chuẩn `admin/*.php`; giữ nguyên URL và mọi kiểm thử trên `admin/loi-nhuan.php`, `admin/cong-thuc-hoa.php`. |
| **Tài liệu dự án** | `README.md` còn trỏ nhánh tích hợp cũ; chưa có sơ đồ kiến trúc & bản đồ di chuyển | Cập nhật `README.md`; tạo `docs/ARCHITECTURE.md`, `docs/FILE_MOVE_MAP.md`, `docs/REFACTOR_TEST_REPORT.md` | **An toàn cao** | Giữ nguyên toàn bộ `docs/ui-audit/` (`UI_AUDIT_REPORT.md`, `UI_CHANGELOG.md`, `UI_TEST_RESULTS.md` và 33 PNG). |

---

## 3. Các đường dẫn công khai cố ý GIỮ NGUYÊN (không di chuyển)

Nhằm đảm bảo XAMPP chạy trực tiếp tại `http://localhost/Lap_trinh_web/`, không gãy deep-link, test hay workflow CI:
- **20 tệp PHP gốc:** `index.php`, `san-pham.php`, `chi-tiet-san-pham.php`, `gio-hang.php`, `thanh-toan.php`, `dat-hang-thanh-cong.php`, `tra-cuu-don-hang.php`, `tu-van-hoa.php`, `dang-ky.php`, `dang-nhap.php`, `quen-mat-khau.php`, `dang-xuat.php`, `tai-khoan.php`, `lich-su-don-hang.php`, `tin-tuc.php`, `chi-tiet-tin-tuc.php`, `gioi-thieu.php`, `lien-he.php`, `chinh-sach-giao-hang.php`, `chinh-sach-doi-tra.php`.
- **24 route công khai trong `admin/*.php`:** giữ nguyên 100% URL, không chuyển sang front-controller hay đổi tên slug.
- **5 endpoint `actions/*.php`:** giữ nguyên để tương thích `fetch()`, test `verify-r2.sh` và `test_admin_ops.py`.
- **2 feed động PHP-JS:** `assets/js/products.php` và `assets/js/cart-r2.php`.
- **Thư mục upload & DB:** `assets/uploads/` (cùng `.htaccess`) và `database/schema.sql`, `seed.sql`, `migrations/001..005`.

---

## 4. Hạng mục rủi ro cao đã HOÃN (cần quyết định sau)

1. **Chuyển webroot sang thư mục `/public/`:** Nếu chuyển các route sang `/public/`, người dùng clone vào `C:\xampp\htdocs\Lap_trinh_web` sẽ phải đổi cấu hình Apache `DocumentRoot` hoặc thêm rewrite phụ thuộc `mod_rewrite`. Hiện hoãn để đảm bảo chạy ngay trên XAMPP mặc định.
2. **Xóa các file `.html` tĩnh từ Yêu cầu 1 và JS cũ (`assets/js/admin.js`, `main.js`, `cart.js`, `products.js`, `batch2.js`, `batch3.js`, `batch4.js`):** Các tệp này thuộc lịch sử Yêu cầu 1 / nhánh `main` để giảng viên đối chiếu; chưa xóa để tránh làm mất minh chứng đối sánh nếu chưa được chủ dự án duyệt bỏ.
3. **Chuyển `config/` và `includes/` ra ngoài thư mục repo webroot:** Không thực hiện vì dự án phải tự chạy trong một thư mục clone duy nhất đặt dưới `htdocs/`. Đã bù bằng 2 lớp phòng thủ `.htaccess` + PHP `realpath` guard.
4. **Cài đặt `tests/customer/*.cjs` chạy cục bộ với Playwright:** Do máy chưa cài npm package `playwright`, giữ nguyên kịch bản kiểm thử gốc mà không tự cài thêm `node_modules` vào repo.
