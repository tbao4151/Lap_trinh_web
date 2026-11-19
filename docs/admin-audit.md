# ADM-01 — Audit trang quản trị PHP (trước và sau khi làm Admin Operations)

Phạm vi: 22 route admin của CÁ'S HOA trên branch `feature/requirement-2-php-mysql` (12 route PHP đã có + 10 route còn ở dạng HTML tĩnh).
Cách đo: chạy thật trên PHP 8.2 + MariaDB 10.4 (XAMPP), đăng nhập 5 vai trò (khách vãng lai, khách hàng, staff, admin, owner) bằng HTTP client có cookie, ghi lại mã HTTP, chuyển hướng, thông báo lỗi và dữ liệu MySQL sau mỗi thao tác. Số liệu "trước" lấy từ lần đo trên code gốc (chưa sửa), "sau" lấy từ `tests/admin/test_admin_ops.py`.

## 1. Ma trận GET theo vai trò

Ký hiệu: `200` xem được, `303→login` bị chuyển về `admin/dang-nhap.php`, `403` trang báo không đủ quyền, `404` route chưa tồn tại.

| Route | Trước: guest / customer | Trước: staff / admin / owner | Sau: guest / customer | Sau: staff | Sau: admin | Sau: owner |
|---|---|---|---|---|---|---|
| dang-nhap.php | 200 / 200 | 303→index (đã đăng nhập) | 200 / 200 | 303→index | 303→index | 303→index |
| index.php | 303→login | 200 | 303→login | 200 | 200 | 200 |
| don-hang.php | 303→login | 200 | 303→login | 200 | 200 | 200 |
| giao-hang.php | 303→login | 200 | 303→login | 200 | 200 | 200 |
| khach-hang.php | 303→login | 200 | 303→login | 200 | 200 | 200 |
| san-pham.php | 303→login | 200 | 303→login | 200 (chỉ xem) | 200 | 200 |
| them-san-pham.php | 303→login | 200 (staff thấy form nhưng không lưu được) | 303→login | **403** | 200 | 200 |
| sua-san-pham.php?id=1 | 303→login | 200 (như trên) | 303→login | **403** | 200 | 200 |
| quan-ly-nhanh.php | 303→login | 200 | 303→login | 200 (chỉ xem) | 200 | 200 |
| phan-loai-bo-loc.php | 303→login | 200 | 303→login | 200 (chỉ xem) | 200 | 200 |
| kho-tong.php | 303→login | 200 | 303→login | 200 | 200 | 200 |
| bao-cao.php | 303→login | 200 | 303→login | 200 | 200 | 200 |
| hoan-tien.php | **404** | **404** | 303→login | 200 | 200 | 200 |
| giay-goi.php | **404** | **404** | 303→login | 200 (chỉ xem) | 200 | 200 |
| kham-pha.php | **404** | **404** | 303→login | 200 (chỉ xem) | 200 | 200 |
| kho-hoa.php | **404** | **404** | 303→login | 200 | 200 | 200 |
| kho-phu-kien.php | **404** | **404** | 303→login | 200 | 200 | 200 |
| lich-su-kho.php | **404** | **404** | 303→login | 200 | 200 | 200 |
| nhan-vien.php | **404** | **404** | 303→login | **403** | 200 | 200 |
| cai-dat.php | **404** | **404** | 303→login | **403** | 200 | 200 |
| tai-khoan.php | **404** | **404** | 303→login | 200 | 200 | 200 |
| chu-so-huu.php | **404** | **404** | 303→login | **403** | **403** | 200 |

Sau khi sửa: 22/22 route có file `.php` thật, menu (`admin/_nav.php`) trỏ đúng route PHP và tự ẩn mục không có quyền; 110/110 ô của ma trận khớp kỳ vọng (kiểm bằng test tự động, không chỉ nhìn giao diện).

## 2. Phát hiện trên code gốc và trạng thái sau

| # | Phát hiện (trước) | Cách tái hiện | Mức | Sau khi sửa |
|---|---|---|---|---|
| F1 | 10 màn hình quản trị chỉ có bản HTML, route `.php` trả 404; menu trong `_app.php` không có mục nào cho các trang này | GET `admin/kho-hoa.php` → 404 | Cao | Đã có 10 route PHP dùng MySQL; menu 22 mục chia nhóm |
| F2 | Lỗi nghiệp vụ / thiếu quyền trả HTTP 200 kèm thông báo ("Bạn không có quyền thay đổi dữ liệu.") | Staff POST `quick_save` → 200 | Trung bình | Thiếu quyền → 403, dữ liệu sai → 422, xung đột CSDL → 409; có ghi nhật ký `denied` |
| F3 | Staff mở được form thêm/sửa sản phẩm dù không được lưu | Staff GET `them-san-pham.php` → 200 | Thấp | GET trái quyền → 403 và ghi log |
| F4 | Giao hàng đổi trạng thái tuỳ ý, kể cả lùi từ "Đã giao" về "Chưa giao" | POST `delivery_update` delivered → not_delivered → 303 | Cao | Luồng trạng thái hợp lệ trong `admin/modules/delivery.php`, nhảy bước → 422, có `delivery_events` |
| F5 | SKU trùng báo lỗi chung "Dữ liệu trùng… hoặc lỗi kết nối MySQL" với HTTP 200 | Tạo sản phẩm SKU `IG-GARDEN-380` | Trung bình | Kiểm tra SKU/slug trước khi ghi, không phân biệt hoa thường → 422 với thông báo rõ |
| F6 | Sửa sản phẩm không tồn tại vẫn chuyển "đã lưu" | POST `sua-san-pham.php?id=999999` → 303 `saved=1` | Trung bình | GET/POST id không tồn tại → 404 |
| F7 | Thêm trùng tên phân loại ("Hoa ly" xuất hiện 2 lần) | POST `taxonomy_save` name=Hoa ly → 303 | Trung bình | So trùng không phân biệt hoa thường → 422; xoá phân loại đang dùng → 422 |
| F8 | Kho chỉ có một trang tổng, không có lịch sử trước/sau, không chặn gửi trùng | Xem `kho-tong.php` | Cao | Kho hoa / phụ kiện / tổng / lịch sử; phiếu có `quantity_before/after`, người làm, lý do, `request_token` UNIQUE, khoá `FOR UPDATE`, CHECK tồn ≥ 0 |
| F9 | Không có nhật ký thao tác quản trị | — | Trung bình | Bảng `admin_audit_log` + trang Chủ sở hữu có bộ lọc |
| F10 | Không có upload ảnh sản phẩm, chỉ chọn ảnh có sẵn | Form sửa sản phẩm | Trung bình | Tải nhiều ảnh, kiểm MIME thật bằng `finfo`, ≤ 2 MB, tên ngẫu nhiên, thư mục chặn chạy script |
| F11 | Thông tin cửa hàng (địa chỉ, hotline, thông báo) viết cứng trong `main-r2.js` và `lien-he.php` | Xem mã nguồn | Thấp | Bảng `shop_settings` + trang Cài đặt; trang khách đọc qua `includes/storefront.php` |
| F12 | Staff đổi được trạng thái đơn sang `cancelled` và thanh toán sang `refunded`; admin lùi đơn `completed` → `pending_confirmation/unpaid` | POST `order_update` (`_actions.php`) | Cao | **Chưa sửa** — thuộc contract đơn hàng/thanh toán của trưởng nhóm. Đề xuất ở `docs/admin-ops-contracts.md` |
| F13 | Báo cáo doanh thu mở cho cả staff | GET `bao-cao.php` bằng staff → 200 | Thấp | **Giữ nguyên** (trang của trưởng nhóm), ghi vào đề xuất |
| F14 | (Đạt) CSRF: POST thiếu token → 403 | POST không `csrf` | — | Giữ nguyên, có test |
| F15 | (Đạt) Đăng xuất: cookie cũ POST lại bị chuyển về đăng nhập | POST sau `dang-xuat.php` | — | Giữ nguyên, có test; thêm: khoá tài khoản làm phiên đang mở mất quyền ngay |

## 3. Cách tách module (không sửa đại trà file của trưởng nhóm)

- `admin/_app.php`: chỉ thay vòng lặp menu cũ bằng `require __DIR__.'/_nav.php'` (−13 dòng). `admin/_actions.php`: **không sửa**.
- `admin/_ops.php`: nền chung cho route mới (quyền theo thao tác, CSRF, PRG, mã lỗi HTTP, validate, upload ảnh, audit, layout cùng CSS `admin.css`).
- `admin/modules/{products,inventory,delivery,refunds,storefront,people}.php`: mỗi module một nhóm nghiệp vụ; route `admin/*.php` chỉ 5 dòng gọi module.
- `includes/storefront.php`: hàm chỉ đọc cho trang khách (cài đặt, khám phá, giấy gói, thư viện ảnh), tự quay về giá trị mặc định nếu CSDL chưa chạy migration 003.
- `database/migrations/003_admin_ops.sql`: tăng dần, chạy lại nhiều lần không lỗi và không đổi dữ liệu (kiểm bằng `scripts/verify-admin-ops.sh`).
