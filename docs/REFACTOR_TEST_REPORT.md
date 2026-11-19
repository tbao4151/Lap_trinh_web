# BÁO CÁO KIỂM THỬ TÁI CẤU TRÚC (`feature/project-structure-refactor`)

- **Nhánh kiểm thử:** `feature/project-structure-refactor` (tách từ mốc UI `5ae7798031be052ebc24c0efb482fb8ea22426de`)
- **Ngày chạy kiểm thử:** 19/11/2026 (`2026-11-19T16:13–16:39+07:00`)

---

## 1. Môi trường thực thi thực tế

- **XAMPP Apache thật (`D:\xampp\apache\bin\httpd.exe`):** Apache/2.4.58 (Win64) + `mod_php` PHP 8.2.12 chạy tiến trình tách biệt trên `127.0.0.1:8088` với `Alias /Lap_trinh_web "E:/Lap_trinh_web"` và `AllowOverride All` (giữ nguyên cấu hình Apache/MySQL đang bật sẵn ở cổng `80`/`443`/`3306` và `D:\xampp\htdocs\Lap_trinh_web`).
- **PHP CLI & Built-in Server:** `D:\xampp\php\php.exe` — PHP 8.2.12 (`-l` và đợt kiểm thử bổ sung trên `127.0.0.1:8033`, `127.0.0.1:8036 scripts/customer-router.php`).
- **Database thử riêng biệt lập:** MariaDB 10.4.32 (`D:\xampp\mysql\bin\mysqld.exe`) chạy trên datadir tạm tại thư mục scratch của phiên (`127.0.0.1:3307`), tên DB `cas_hoa_ui_qa` (import đủ `database/schema.sql`, `seed.sql`, `migrations/001..005` + 2 giấy gói mẫu `Kem` và `Nâu kraft`). **Tuyệt đối không đụng database thật của nhóm ở cổng `3306`.**
- **Trình duyệt kiểm thử:** Google Chrome Headless kết nối qua Chrome DevTools Protocol (Node.js v24.15.0 `qa-browser.mjs`), lưu ảnh kiểm chứng tạm ngoài repo để không ghi đè `docs/ui-audit/screenshots/`.
- **Tài khoản test:** Tạo mới ngẫu nhiên qua `scripts/create-admin.php` cho `owner`, `admin`, `staff` và đăng ký tài khoản `customer` qua form trình duyệt.

---

## 2. Kết quả từng hạng mục kiểm thử (sau toàn bộ sửa đổi code)

| Hạng mục | Lệnh / Công cụ | Phạm vi thực tế | Kết quả | Chi tiết quan sát được |
|---|---|---|---|---|
| **PHP Syntax Lint** | `D:\xampp\php\php.exe -l` | Toàn bộ **79** file `.php` trong dự án (loại trừ `assets/vendor/`) | **PASS** | `PHP_FILES=79 FAILURES=0` (`0` lỗi cú pháp trên tất cả entrypoint mới/sửa, `includes/layout.php`, `admin/_layout.php`, `admin/modules/*.php`). |
| **JavaScript Syntax Check** | `node --check` | Toàn bộ **13** file trong `assets/js/*.js` | **PASS** | `JS_FILES=13 FAILURES=0`. |
| **Python Test Compile & Diff Whitespace** | `python -m py_compile tests/admin/test_admin_ops.py` & `git diff --check` | Script test Python & toàn bộ diff hiện hành | **PASS** | Thoát mã `0`, không có lỗi thụt lề/trailing whitespace hay conflict marker. |
| **Apache `.htaccess`, `DirectoryIndex` & chặn truy cập nội bộ** | Apache 2.4.58 `httpd.exe` (`http://127.0.0.1:8088/Lap_trinh_web/`) | **43 đường dẫn nội bộ/nhạy cảm** (`.htaccess`, `.gitignore`, `.git/HEAD`, `README.md`, `database/`, `config/`, `includes/`, `scripts/`, `tests/`, `docs/`, `admin/_*.php`, `admin/modules/`, `assets/uploads/` index & probe `.php`); `DirectoryIndex` gốc & `admin/` | **PASS** | `Syntax OK`; **43/43** trả đúng HTTP `403 Forbidden` (không thực thi script probe trong `assets/uploads/`); `/Lap_trinh_web/` trả `200` (trang PHP, không trả `index.html`); `/Lap_trinh_web/admin/` trả `303` về `dang-nhap.php`. |
| **Smoke Test Routes & Static/Dynamic Assets qua Apache** | Apache 2.4.58 HTTP `GET` (`127.0.0.1:8088/Lap_trinh_web/`) | **18** trang công khai `200`, `404` cho slug không tồn tại, `303` khi chưa đăng nhập, **11** assets (`4` CSS, `bootstrap.min.css`, `bootstrap.bundle.min.js`, `bootstrap-icons.woff2`, `main-r2.js`, `products.php`, `cart-r2.php`, `garden.png`) | **PASS** | `public_routes_200: 18`, `assets_200: 11`, `detail_404: 404`, `article_404: 404`, redirect `303` hoạt động đúng; không phát sinh lỗi `500` từ `.htaccess`. |
| **Router dev server chặn file không công khai** | `php -S 127.0.0.1:8036 scripts/customer-router.php` | `README.md`, `.gitignore`, `database/schema.sql`, `admin/_layout.php`, `admin/modules/orders.php`, `config/database.php`, `docs/ui-audit/UI_AUDIT_REPORT.md` | **PASS** | **7/7** trả về HTTP `403`/`404` (`router_blocked_count: 7`) và **23/23** tệp PHP nội bộ trả `403` trên `-S 127.0.0.1:8033`. |
| **Bộ hồi quy Admin Operations & Upload qua Apache thật** | `python tests/admin/test_admin_ops.py` (`CAS_BASE_URL=http://127.0.0.1:8088/Lap_trinh_web/`, kèm đơn nền `CSH-2026-8AA303D1`) | Ma trận quyền 24 route × 5 vai trò, CSRF, tư vấn hoa không API key, sản phẩm & **upload ảnh multipart vào `assets/uploads/products/`**, phân loại, kho/tồn đồng thời, giao hàng, định mức/hoàn kho snapshot, hoàn tiền, giấy gói, khám phá, cài đặt, nhân viên/chủ sở hữu, nhật ký audit | **PASS** | **TỔNG: 247/247 kiểm tra đạt** (`exit code 0`), ảnh upload qua Apache lưu đúng vào `assets/uploads/products/` và phục vụ lại `200 OK`. |
| **Quét trình duyệt đa viewport & E2E qua Apache thật** | Chrome Headless CDP (`qa-browser.mjs` với `CAS_APP_URL=http://127.0.0.1:8088/Lap_trinh_web`) | **19** Storefront × **7** viewport (`320..1920 px`) + **24** Admin × **7** viewport + **7** luồng E2E (tạo đơn `CSH-2026-CDCA4546`, `445.000 ₫`, tra cứu đơn, tư vấn 4 mẫu hoa, đăng nhập Owner, mở/đóng `#adminSidebar` `375px`) | **PASS** | **133/133** Storefront + **168/168** Admin + **7/7** E2E đạt (`0` tràn ngang, `0` ảnh vỡ, `0` input thiếu nhãn, `0` link `.html`, `0` lỗi JS runtime); tương phản DOM AA giữ nguyên (`4.95:1–9.21:1`). |
| **Chạy qua Apache cổng 80 mặc định của người dùng / clean clone `C:\xampp\htdocs`** | VirtualHost / htdocs mặc định cổng `80` + MySQL `3306` | `D:\xampp\htdocs\Lap_trinh_web` hoặc `C:\xampp\htdocs\Lap_trinh_web` | **NOT_RUN / CHƯA NGHIỆM THU** | Không ghi đè thư mục `D:\xampp\htdocs\Lap_trinh_web` hay đụng vào database `cas_hoa` đang chạy ở cổng `3306` theo nguyên tắc không sửa dữ liệu/cấu hình hệ thống sẵn có của người dùng. |
| **Bộ test Playwright CJS & CI MySQL 8.0 / SMTP Internet** | `tests/customer/*.cjs`, `.github/workflows/php-coursework.yml` | Suite Playwright cục bộ & GitHub Actions Linux MySQL 8.0 + email thật | **NOT_RUN / CHƯA NGHIỆM THU** | Không có package `playwright` trong môi trường; chạy trên MariaDB `10.4.32` thay cho MySQL `8.0`; chưa kích hoạt CI GitHub Actions hay gửi email reset mật khẩu ra Internet. |

---

## 3. Đối chiếu với mốc lịch sử UI (`5ae77980`)

- Mốc lịch sử theo [docs/ui-audit/UI_TEST_RESULTS.md](docs/ui-audit/UI_TEST_RESULTS.md) tại commit `5ae77980`: `74/74` PHP lint, `13/13` JS syntax, `247/247` Admin Ops, `43` route × `7` viewport (`301` lượt), `7` bước E2E.
- Sau khi tái cấu trúc trên nhánh `feature/project-structure-refactor`, tổng số tệp PHP tăng từ `74` lên `79` (do bổ sung `includes/layout.php`, `admin/_layout.php`, `admin/modules/orders.php`, `analytics.php`, `recipes.php`), và hai đợt chạy độc lập (`php -S` tại `task-637` và **Apache 2.4.58 thật tại `task-709`**) xác nhận **100% số bài kiểm nêu trên tiếp tục đạt (`79/79` PHP lint, `13/13` JS, `247/247` Admin Ops, `301/301` viewport, `7/7` E2E)**, cộng thêm **`43/43` kiểm tra `.htaccess` / chặn HTTP `403` thực tế trên Apache 2.4.58**.

---

## 4. Vị trí minh chứng kiểm thử

- Báo cáo và 33 ảnh UI gốc (được bảo toàn nguyên vẹn): `docs/ui-audit/UI_AUDIT_REPORT.md`, `docs/ui-audit/UI_CHANGELOG.md`, `docs/ui-audit/UI_TEST_RESULTS.md`, `docs/ui-audit/screenshots/*.png`.
- Nhật ký chạy tự động và JSON kiểm chứng của đợt refactor (cả `php -S` và Apache 2.4.58) nằm ngoài repository trong thư mục scratch của phiên để giữ nhánh làm việc sạch dữ liệu thử nghiệm (`task-637.log`, `task-709.log`, `browser-apache-report.json`, `admin-ops-apache-results.json`, `apache_shots/*.png`).
