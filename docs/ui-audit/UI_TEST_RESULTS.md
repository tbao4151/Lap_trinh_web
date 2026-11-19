# KẾT QUẢ KIỂM THỬ UI/UX & HỒI QUY — CÁ'S HOA

- **Nhánh kiểm thử:** `feature/ui-professional-polish` (dựa trên `main` `9cb1b9955296b2e5f856373d590097f8410376c5`)
- **Ngày chạy kiểm thử mới nhất:** 19/11/2026 (`2026-11-19T15:31+07:00`)

---

## 1. Môi trường thực thi đợt kiểm thử mới

- **PHP:** `D:\xampp\php\php.exe` — PHP 8.2.12 (CLI & built-in server `-S 127.0.0.1:8033`)
- **CSDL cô lập:** MariaDB 10.4.32 (`D:\xampp\mysql\bin\mysqld.exe`) chạy trên datadir tạm trong thư mục scratch ở cổng riêng `127.0.0.1:3307`, tên database kiểm thử `cas_hoa_ui_qa` (import đủ `schema.sql`, `seed.sql`, `migrations/001..005` + 2 giấy gói thử nghiệm `Kem` và `Nâu kraft`). **Tuyệt đối không động vào database `cas_hoa` của nhóm trên cổng `3306`.**
- **Trình duyệt:** Google Chrome Headless kết nối qua Chrome DevTools Protocol (CDP) bằng script Node.js v24.15.0 tự động (`qa-browser.mjs`).
- **Tài khoản test:** Tạo mới động bằng `scripts/create-admin.php` với mật khẩu ngẫu nhiên cho `owner`, `admin`, `staff`, và đăng ký mới tài khoản `customer` qua form trình duyệt.

---

## 2. Kết quả kiểm thử mới chạy sau lần chỉnh sửa cuối

| Hạng mục kiểm thử | Lệnh / công cụ | Phạm vi | Trạng thái | Kết quả thực tế |
|---|---|---|---|---|
| Kiểm tra cú pháp PHP | `D:\xampp\php\php.exe -l` | Toàn bộ 74 file `.php` (kể cả đã kiểm lại 3 file sửa sau cùng `_app.php`, `_ops.php`, `products.php`) | **PASS** | `0` lỗi cú pháp (`No syntax errors detected`). |
| Kiểm tra cú pháp JavaScript | `node --check` | Toàn bộ 13 file trong `assets/js/*.js` | **PASS** | `0` lỗi cú pháp. |
| Kiểm tra khoảng trắng & phạm vi sửa | `git diff --check` & `git status --short` | Toàn bộ diff nhánh `feature/ui-professional-polish` | **PASS** | `0` conflict/trailing whitespace; `0` thay đổi trong `database/`, `config/`, `includes/`, `actions/`, `admin/_actions.php`. |
| Bộ kiểm thử hồi quy Admin & Tư vấn hoa | `python tests/admin/test_admin_ops.py` (kèm 1 đơn nền từ `make_paid_order`) | Ma trận quyền 24 route × 5 vai trò, CSRF, sản phẩm/ảnh, phân loại, kho/tồn đồng thời, giao hàng, định mức/hủy hoàn kho, hoàn tiền, giấy gói, khám phá, cài đặt, nhân viên/chủ sở hữu, nhật ký audit, tư vấn hoa | **PASS** | **247/247 kiểm tra đạt** (`exit code 0`). |
| Quét đa màn hình Storefront trên trình duyệt thật | Chrome CDP (`qa-browser.mjs`) | 19 trang khách hàng × 7 viewport (`320, 375, 390, 768, 1024, 1440, 1920 px`) = **133 lượt kiểm** | **PASS** | **133/133 đạt**: `0` tràn ngang (`scrollWidth <= innerWidth + 1`), `0` ảnh lỗi (`naturalWidth > 0`), `0` input thiếu nhãn, `0` link `.html`, `0` lỗi JS runtime. |
| Quét đa màn hình Admin trên trình duyệt thật | Chrome CDP (`qa-browser.mjs`) | 24 route Admin × 7 viewport (`320, 375, 390, 768, 1024, 1440, 1920 px`) = **168 lượt kiểm** | **PASS** | **168/168 đạt**: `0` tràn ngang, `0` ảnh lỗi, `0` input thiếu nhãn, `0` link `.html`, `0` lỗi JS runtime. |
| Luồng E2E Storefront trên trình duyệt thật | Chrome CDP (`qa-browser.mjs`) | Đăng ký → Danh mục → Chi tiết (`lam-tinh`, chọn giấy gói `Nâu kraft +15.000 ₫`) → Giỏ hàng → Thanh toán → Trang thành công (`CSH-2026-EDF7CF34`) → Tra cứu đơn → Tư vấn chọn hoa | **PASS** | **5/5 bước đạt**: tạo đơn thật thành công (`445.000 ₫`), tra cứu khớp mã và SĐT, tư vấn trả về 4 thẻ mẫu hoa. |
| Luồng E2E Admin & Mobile Drawer trên trình duyệt thật | Chrome CDP (`qa-browser.mjs`) | Đăng nhập Owner → `admin/index.php` → Mở/đóng `#adminSidebar` ở `375px` (`#adminMenuOpen` / `#adminSidebarClose`) → Rà soát tất cả trang vận hành | **PASS** | **2/2 kiểm tra đạt**: đăng nhập chuyển trang đúng, drawer mobile mở (`open=true`) và đóng (`open=false`) chuẩn xác. |
| Đo tương phản WCAG AA trên DOM sống | Chrome CDP (`getComputedStyle`) | Nút chính Storefront, chữ phụ, eyebrow, nút chính Admin, badge thành công Admin, mục sidebar đang chọn | **PASS** | Storefront: `.btn-cas-primary` **6.32:1**, `.cas-subtitle` **6.25:1**, `.cas-eyebrow` **5.85:1**.<br>Admin: `.admin-btn` **4.95:1**, `.admin-status.success` **6.11:1**, `.admin-nav-link.active` **9.21:1**. |
| Bộ test Playwright sẵn có trong repo | `tests/customer/browser.cjs`, `tests/customer/http.cjs`, `tests/customer/completion.cjs` | Bộ test khách hàng có sẵn trong repo | **NOT_RUN** | Không chạy trực tiếp vì môi trường không cài package `playwright` và cần SMTP giả lập `customer-smtp.py`; luồng tương đương đã được kiểm chứng qua `qa-browser.mjs` (Chrome CDP) ở trên. |
| Kiểm thử trên MySQL 8.0 & gửi email Internet | CI `.github/workflows/php-coursework.yml` & SMTP thật | Môi trường MySQL 8.0 và hộp thư bên ngoài | **NOT_RUN** | Máy cục bộ dùng MariaDB 10.4.32; không đẩy nhánh hay chạy gửi email ra Internet. |

---

## 3. Phân biệt với kết quả kiểm thử cũ trong repo

Các tài liệu/minh chứng cũ đã tồn tại sẵn trên nhánh `main` trước khi kiểm toán UI:
- `docs/admin-ops-test-report.md` (kết quả chạy ngày 13/10/2026: 216 checks trước khi gộp thêm migration 004/005).
- `docs/evidence/CUS-01` → `CUS-07` và `docs/evidence/customer` (ảnh và JSON chụp ở giai đoạn tích hợp Requirement 2 trước đợt chỉnh UI này).

Các số liệu ở **Mục 2** phía trên hoàn toàn là **kết quả chạy mới ngày 19/11/2026 trên mã nguồn đã chỉnh UI của nhánh `feature/ui-professional-polish`**.

---

## 4. Danh sách ảnh chụp trình duyệt thật (`docs/ui-audit/screenshots/`)

Tổng cộng **33 ảnh PNG** chụp trực tiếp bằng Google Chrome Headless từ server PHP + MariaDB cô lập, đã kiểm tra trực quan bằng mắt các màn hình trọng tâm:

### Storefront (`1440px` & `375px`)
- `docs/ui-audit/screenshots/storefront-home-1440.png` & `docs/ui-audit/screenshots/storefront-home-375.png`
- `docs/ui-audit/screenshots/storefront-catalog-1440.png` & `docs/ui-audit/screenshots/storefront-catalog-375.png`
- `docs/ui-audit/screenshots/storefront-detail-1440.png` & `docs/ui-audit/screenshots/storefront-detail-375.png`
- `docs/ui-audit/screenshots/storefront-cart-1440.png`
- `docs/ui-audit/screenshots/storefront-checkout-1440.png`
- `docs/ui-audit/screenshots/storefront-success-1440.png`
- `docs/ui-audit/screenshots/storefront-lookup-1440.png` & `docs/ui-audit/screenshots/storefront-lookup-375.png`
- `docs/ui-audit/screenshots/storefront-advisor-1440.png`
- `docs/ui-audit/screenshots/storefront-contact-1440.png` & `docs/ui-audit/screenshots/storefront-contact-375.png`
- `docs/ui-audit/screenshots/storefront-news-1440.png`
- `docs/ui-audit/screenshots/storefront-article-1440.png`
- `docs/ui-audit/screenshots/storefront-account-1440.png`
- `docs/ui-audit/screenshots/storefront-orders-1440.png`
- `docs/ui-audit/screenshots/storefront-login-1440.png`
- `docs/ui-audit/screenshots/storefront-signup-1440.png`

### Admin (`1440px` & `375px`)
- `docs/ui-audit/screenshots/admin-login-1440.png`
- `docs/ui-audit/screenshots/admin-dashboard-1440.png` & `docs/ui-audit/screenshots/admin-dashboard-375.png`
- `docs/ui-audit/screenshots/admin-orders-1440.png` & `docs/ui-audit/screenshots/admin-orders-375.png`
- `docs/ui-audit/screenshots/admin-products-1440.png` & `docs/ui-audit/screenshots/admin-products-375.png`
- `docs/ui-audit/screenshots/admin-product-edit-1440.png`
- `docs/ui-audit/screenshots/admin-delivery-1440.png`
- `docs/ui-audit/screenshots/admin-inventory-1440.png` & `docs/ui-audit/screenshots/admin-inventory-375.png`
- `docs/ui-audit/screenshots/admin-profit-1440.png`
- `docs/ui-audit/screenshots/admin-owner-1440.png`
