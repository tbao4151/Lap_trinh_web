# NHẬT KÝ THAY ĐỔI UI/UX — CÁ'S HOA (`feature/ui-professional-polish`)

Tài liệu ghi nhận toàn bộ các thay đổi giao diện (HTML/CSS/JS) trên nhánh `feature/ui-professional-polish` tách từ `main` (`9cb1b9955296b2e5f856373d590097f8410376c5`). **Không có bất kỳ thay đổi nào tới logic nghiệp vụ PHP, SQL, cấu trúc bảng DB, auth, CSRF, đơn hàng, kho hay API.**

---

## 1. Bảo toàn nhận diện & chuẩn hóa màu sắc (Tokens)

### Storefront (`assets/css/style.css` + `assets/css/customer.css`)
- **Giữ nguyên toàn bộ token nền tảng trong `assets/css/style.css`:** `--background: #f8f6f0`, `--foreground: #24352d`, `--surface: #fffdf9`, `--surface-muted: #eef2e9`, `--primary: #71866c`, `--primary-hover: #5d725a`, `--secondary: #eacbc2`, `--accent: #d58370`, `--muted-foreground: #6d766e`, `--border: #e1e2d8`, `--success: #678368`, `--danger: #b95e52`.
- **Giữ nguyên token tăng tương phản thực tế trong `assets/css/customer.css`:** `--primary: #4e6649`, `--primary-hover: #40573c`, `--muted-foreground: #545e55`, `--danger: #a13e2d`, đồng thời loại bỏ 2 dòng khai báo màu lặp lại thừa (`.cas-muted...` và `.product-price,.cas-eyebrow`) vì các selector đó đã kế thừa từ biến CSS.
- **Điều chỉnh cặp màu chữ/nền đạt WCAG AA (`>= 4.5:1`) mà không đổi palette gốc:**
  - `.cas-cart-count` (`style.css`): đổi màu chữ từ `#fff` (`2.96:1` trên `--accent` `#d58370`) sang `var(--foreground)` (`#24352d`, đạt `4.51:1`), giữ nguyên nền hồng đào `--accent`.
  - `.auth-message.success` (`style.css`): đổi màu chữ từ `var(--success)` (`3.56:1` trên `#e4f0e2`) sang `var(--primary-hover)` (`#40573c`, đạt `6.75:1`).

### Admin (`assets/css/admin.css`)
- **Giữ nguyên toàn bộ token trong `:root`:** `--admin-bg: #f7f5ef`, `--admin-surface: #fffdf9`, `--admin-surface-soft: #f0f1eb`, `--admin-text: #21352c`, `--admin-muted: #6c776f`, `--admin-primary: #728a70`, `--admin-primary-dark: #5f765e`, `--admin-border: #dedfd6`, `--sidebar-bg: #193126`, `--sidebar-fg: #f5f1e7`, `--sidebar-active: #7d9477`.
- **Điều chỉnh cặp màu chữ/nền đạt WCAG AA trong cùng dải màu:**
  - `.admin-btn`: dùng nền và viền `var(--admin-primary-dark)` (`#5f765e`) với chữ `#fff` (`4.95:1` thay cho `3.76:1`), hover `#4b614b` (`6.75:1`).
  - `.admin-nav-link.active`: dùng nền `rgba(125, 148, 119, 0.28)` phủ trên `--sidebar-bg` kết hợp viền trái `3px solid var(--sidebar-active)` và chữ `#fff` (đạt `9.21:1` thay cho `3.29:1`).
  - `.admin-status`: dùng sắc đậm cùng tông cho chữ badge trên nền pastel (`warning`: `#7c5823` / `#f5ebda` = `5.42:1`, `danger`: `#933c32` / `#fae8e4` = `6.04:1`, `success`: `#415f44` / `#e7f0e5` = `6.11:1`), đồng thời bổ sung viền và hình khối `::before` riêng cho từng trạng thái.
  - `.admin-role`, `.admin-hero p`, `.admin-stat-label`, `.admin-stat-note`, `.admin-table th`, `.cas-muted`: dùng `#545e55` / `var(--admin-primary-dark)` để đảm bảo độ đọc rõ ở cỡ chữ nhỏ.

---

## 2. Chi tiết thay đổi theo từng tệp

### A. CSS hệ thống
- **`assets/css/style.css`**:
  - Nâng cỡ chữ các nhãn tiếng Việt quá nhỏ (`.cas-eyebrow`, `.cas-announcement`, `.cas-brand-tagline`, `.product-badge`, `.cas-footer-title`, `.cas-footer-bottom`, `.password-policy`, `.stat-chip`, `.article-meta`).
  - Cải thiện `.cas-mobile-nav` (thêm `max-height: calc(100vh - 100px); overflow-y: auto; box-shadow` và `.active`).
  - Chỉnh `.hero-image-link` thêm `position: relative`, `.product-image-wrap` thêm viền mảnh, `.quantity-picker button:hover`, `.cart-remove` dạng pill dễ bấm, `.news-card` theo flex column cùng `.news-card-link` căn đều đáy.
- **`assets/css/customer.css`**:
  - Bổ sung biến `--cas-radius-*`, `--cas-shadow-soft`.
  - Thêm style cho `.hero-header`, `.hero-caption`, `.filter-check:has(input:checked)`, `.btn-filter-reset`, `.detail-gallery` / `.detail-thumb` / `.detail-thumb.active`, `.form-required`, `.cas-faq` (ghi đè Accordion Bootstrap theo tone sage/kem), `.article-cover`, `.article-body`, và toàn bộ nhóm `.cas-advisor-*` (tách từ `tu-van-hoa.php`).
- **`assets/css/responsive.css`**:
  - Thêm `flex-wrap: wrap; gap: .65rem;` cho `.cart-line-actions` ở màn hình `< 992px` và chỉnh vùng chạm `.product-quick-add` (`40px`) ở `< 576px`.
- **`assets/css/admin.css`**:
  - Chuẩn hóa thang chữ Admin (`11px–13.5px`), nút chính/phụ/nguy hiểm (`.admin-btn`, `.admin-btn-light`, `.admin-btn-danger`), `.admin-money` (`white-space: nowrap`), `.admin-table-wrap` có khung viền & bo góc, `.cas-admin-*`, `.cas-gallery`, `.cas-swatch`, `.cas-calendar`, `.cas-profit-*`.
  - Sửa `.admin-topbar` trên `< 768px` (nút topbar thu gọn thành icon `38×38px`, không bọc dòng đè tiêu đề), bật `:focus-visible` và `@media (prefers-reduced-motion: reduce)`.

### B. JavaScript Storefront
- **`assets/js/main-r2.js`**:
  - Cập nhật `headerMarkup()`: tính đúng trạng thái `active` và `aria-current="page"` cho cả desktop nav, tài khoản và `#casMobileNav` (bao gồm phân biệt `Bó hoa` vs `Giỏ hoa`, `Tư vấn chọn hoa`, `Tin tức`, `Liên hệ`) trong khi vẫn giữ nguyên thứ tự và chuỗi text của `#casMobileNav` để không phá kiểm thử Tab.
  - Cập nhật `initHome()`: render `.hero-caption` hiển thị tên và giá sản phẩm trên ảnh hero slider.
  - Cập nhật `galleryMarkup()` & `initDetail()`: dùng `<nav class="breadcrumb-cas" aria-label="Đường dẫn">`, class `.detail-gallery` / `.detail-thumb` + `aria-pressed`, và cập nhật động `#detailWrappingNote` khi chọn giấy gói.
  - Cập nhật `initCart()` & `initCheckout()`: hiển thị phụ thu giấy gói theo từng sản phẩm (`(+X ₫/sp)`) khi phụ thu `> 0` và trình bày lại thông báo xóa sản phẩm ngừng bán trong giỏ.
- **`assets/js/customer-forms.js`**:
  - Bổ sung `.auth-password-wrap` + nút ẩn/hiện mật khẩu cho `#recoveryVerifyForm` tạo động từ URL fragment; đổi icon `bi-eye` ↔ `bi-eye-slash` khi bấm; lưu `idleHtml` để không mất icon trên nút submit khi `pageshow`.
- **`assets/js/flower-advisor.js`**:
  - Thêm empty state kèm đường dẫn tới `san-pham.php` khi không có sản phẩm khớp tiêu chí.
- **`assets/js/r2-lookup.js`**:
  - Giữ lại icon trên nút submit (`button.innerHTML = idle`) sau khi hoàn tất fetch và thêm đường phân cách gọn cho `.lookup-result`.

### C. Markup HTML trong các trang PHP Storefront
- **`index.php`**: Thay `h1.visually-hidden` bằng khối `.hero-header` hiển thị tiêu đề thương hiệu, mô tả và nút `Nhờ gợi ý mẫu hoa`.
- **`san-pham.php`**: Thay inline style trên nút đặt lại bằng `.btn-filter-reset`; thêm nút `Xem kết quả` (`d-lg-none`) đóng drawer lọc trên mobile.
- **`thanh-toan.php`**: Thêm `.form-required` (`*`) cho trường bắt buộc, bổ sung `<div class="invalid-feedback">` cho `#address`, thêm `.form-text` hướng dẫn chọn ngày trước khi chọn khung giờ ở `#receiveTime`.
- **`tra-cuu-don-hang.php`**: Xóa `.form-text` dư khiến 2 cột nhập bị lệch chiều cao, chỉnh tỷ lệ `col-md-5 / col-md-4 / col-md-3`, thêm `*` và `.invalid-feedback`.
- **`dang-nhap.php`, `dang-ky.php`, `quen-mat-khau.php`**: Đồng bộ `*` trên trường bắt buộc, thêm `aria-pressed="false"` cho nút toggle mật khẩu, bổ sung `.auth-password-wrap` và `.password-policy` cho chế độ đặt lại mật khẩu trên `quen-mat-khau.php`.
- **`tai-khoan.php`, `lich-su-don-hang.php`**: Cho nút đăng xuất full width trên `tai-khoan.php`; bổ sung thẻ điều hướng `.account-nav` và trạng thái trống `.customer-empty` trên `lich-su-don-hang.php`.
- **`tin-tuc.php`, `chi-tiet-tin-tuc.php`**: Gắn link trên ảnh & tiêu đề bài viết, thêm `.news-card-link` và empty state ở `tin-tuc.php`; thêm breadcrumb, `.article-cover`, `.article-body` và card `.customer-empty` cho trạng thái 404 ở `chi-tiet-tin-tuc.php`.
- **`lien-he.php`, `tu-van-hoa.php`**: Thêm `*` và class `.cas-faq` ở `lien-he.php`; xóa `<style>` nội tuyến, đổi 3 cột đầu sang `col-md-4` và đổi `#advisorError` sang `.auth-message.error` ở `tu-van-hoa.php`.

### D. Layout & Accessibility trong hệ thống Admin
- **`admin/_nav.php`**: Thêm icon Bootstrap Icons cho cả 22 mục điều hướng quản trị và `aria-current="page"` cho trang hiện tại (kể cả khi đang mở `sua-san-pham.php`).
- **`admin/_app.php` & `admin/_ops.php` & `admin/dang-nhap.php`**:
  - Chuyển CSS Bootstrap & Bootstrap Icons từ CDN sang `../assets/vendor/...`.
  - Xóa các khối `<style>` nội tuyến trùng lặp; đồng bộ header sidebar có logo + nút đóng mobile, topbar có nút `#adminMenuOpen`, liên kết `Về cửa hàng`, hồ sơ `tai-khoan.php`, nút `Đăng xuất`, `#adminOverlay` và script đóng/mở sidebar drawer (hỗ trợ `Escape`).
  - Trong `_app.php`: thêm badge `.admin-status` cho trạng thái đơn hàng, `.admin-money` cho cột tiền, `.admin-empty` khi bảng rỗng, `for`/`id` và `aria-label` cho các ô lọc/nhập. Giữ nguyên cấu trúc `<div class="alert alert-...">` không chèn icon bên trong để tương thích `tests/admin/test_admin_ops.py`.
- **`admin/loi-nhuan.php` & `admin/cong-thuc-hoa.php`**: Xóa `<style>` nội tuyến ở `loi-nhuan.php`, chuyển sang dùng CSS chung trong `admin.css` và `.admin-empty`; đồng bộ nút xóa `.admin-btn-danger` và `.admin-empty` trong `cong-thuc-hoa.php`.
- **`admin/modules/products.php`, `inventory.php`, `delivery.php`, `refunds.php`, `storefront.php`, `people.php`**: Bổ sung `for`/`id` hoặc `aria-label` cho toàn bộ các ô lọc/nhập/chọn và chuẩn hóa nút xóa sang `.admin-btn-danger`, ô giá sang `.admin-money`.

---

## 3. Xác nhận phần không thay đổi (Backend & Business Logic)

Toàn bộ các thành phần sau được giữ nguyên vẹn 100%, không có bất kỳ dòng thay đổi nào (đã kiểm chứng bằng `git status --short`):
- `database/**` (`schema.sql`, `seed.sql`, `migrations/001..005`)
- `config/**` (`database.php`)
- `includes/**` (`auth.php`, `bootstrap.php`, `customer.php`, `customer-recommendations.php`, `order-inventory.php`, `r2-status.php`, `storefront.php`)
- `actions/**` (`cart.php`, `checkout.php`, `contact.php`, `flower-advisor.php`, `lookup.php`)
- `admin/_actions.php`, `dang-xuat.php`, `assets/js/cart-r2.php`, `assets/js/products.php`
