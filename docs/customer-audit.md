# Audit 18 trang Customer

Ngày kiểm tra: 14/10/2026. Nhánh `feature/customer-completion`.
Môi trường thực: PHP 8.2.12 của XAMPP, MariaDB riêng cổng 3307, database `cas_hoa_customer_preview`, PHP development server `http://127.0.0.1:8033`. SMTP local cổng 1025. Apache tại URL yêu cầu chưa chạy; không ghi DONE cho XAMPP.

Bằng chứng mới: `evidence/customer/http-results.json`, `browser-results.json`, `../CUS-07/php-lint.json`. HTTP 35/35, trình duyệt 103/103, PHP lint 50/50. Bộ trình duyệt thực hiện 18 trang × 5 viewport (375, 390, 768, 1024, 1440), kiểm tra overflow, ảnh, label và link .html; tài khoản/hồ sơ được mở trong phiên đã đăng nhập.

| Trang | Trạng thái | Smoke và nghiệp vụ đã kiểm tra | Bằng chứng |
|---|---|---|---|
| `index.php` | LOCAL_PASS | Sản phẩm MySQL và gợi ý chuyển bộ lọc | HTTP page index.php; Responsive 375/390/768/1024/1440 |
| `san-pham.php` | LOCAL_PASS | Lọc kết hợp, giá/tên/mới, tìm không dấu và rỗng | HTTP page san-pham.php; Responsive 375/390/768/1024/1440 |
| `chi-tiet-san-pham.php` | LOCAL_PASS | Slug đúng, slug sai 404, giấy gói, mô tả được escape | HTTP page chi-tiet-san-pham.php?slug=lam-tinh; Responsive 375/390/768/1024/1440 |
| `gio-hang.php` | LOCAL_PASS | Thêm/sửa/xóa, refresh Session, lỗi mạng và retry | HTTP page gio-hang.php; Responsive 375/390/768/1024/1440 |
| `thanh-toan.php` | LOCAL_PASS | Giao hàng/pickup, validation, giá server, double click | HTTP page thanh-toan.php; Responsive 375/390/768/1024/1440 |
| `dat-hang-thanh-cong.php` | LOCAL_PASS | Mã đơn thật, trạng thái theo SQL, không lộ đơn khác | HTTP page dat-hang-thanh-cong.php; Responsive 375/390/768/1024/1440 |
| `dang-nhap.php` | LOCAL_PASS | Đăng nhập, logout và truy cập sau logout | HTTP page dang-nhap.php; Responsive 375/390/768/1024/1440 |
| `dang-ky.php` | LOCAL_PASS | Tạo tài khoản MySQL và email trùng | HTTP page dang-ky.php; Responsive 375/390/768/1024/1440 |
| `quen-mat-khau.php` | LOCAL_PASS | Email SMTP local, token dùng một lần và hết hạn | HTTP page quen-mat-khau.php; Responsive 375/390/768/1024/1440 |
| `tai-khoan.php` | LOCAL_PASS | Cập nhật hồ sơ MySQL trong phiên đăng nhập | HTTP page tai-khoan.php; Responsive 375/390/768/1024/1440 |
| `lich-su-don-hang.php` | LOCAL_PASS | Phân trang và cách ly A/B bằng user_id trong Session | HTTP page lich-su-don-hang.php; Responsive 375/390/768/1024/1440 |
| `tra-cuu-don-hang.php` | LOCAL_PASS | Mã và phone đúng/sai, không trả địa chỉ/phone | HTTP page tra-cuu-don-hang.php; Responsive 375/390/768/1024/1440 |
| `tin-tuc.php` | LOCAL_PASS | HTTP 200, ảnh tải và điều hướng PHP | HTTP page tin-tuc.php; Responsive 375/390/768/1024/1440 |
| `chi-tiet-tin-tuc.php` | LOCAL_PASS | Bài theo slug thật, HTTP 200 và ảnh | HTTP page chi-tiet-tin-tuc.php?slug=chon-hoa-theo-mau; Responsive 375/390/768/1024/1440 |
| `lien-he.php` | LOCAL_PASS | POST lưu liên hệ, CSRF và giới hạn gửi | HTTP page lien-he.php; Responsive 375/390/768/1024/1440 |
| `gioi-thieu.php` | LOCAL_PASS | HTTP 200, ảnh, label và điều hướng PHP | HTTP page gioi-thieu.php; Responsive 375/390/768/1024/1440 |
| `chinh-sach-giao-hang.php` | LOCAL_PASS | HTTP 200, ảnh, label và điều hướng PHP | HTTP page chinh-sach-giao-hang.php; Responsive 375/390/768/1024/1440 |
| `chinh-sach-doi-tra.php` | LOCAL_PASS | HTTP 200, ảnh, label và điều hướng PHP | HTTP page chinh-sach-doi-tra.php; Responsive 375/390/768/1024/1440 |

## Lỗi và giới hạn phát hiện

- Nhãn sắp xếp Mới cập nhật chưa đúng dữ liệu: schema không có updated_at, mã hiện sắp theo product_id. Đã sửa thành Mới thêm và test đạt.
- Bộ lọc đặt custom validity cho khoảng giá đảo nhưng không hiện lỗi inline. Đã sửa inline/aria-invalid và test đạt.
- Schema chỉ có products.image; không có gallery hay tồn kho thành phẩm. Đề xuất lead trước khi thay đổi mô hình.
- Hai tệp core actions/checkout.php và includes/auth.php đã sửa từ trước phiên. Test hiện tại sử dụng các thay đổi này; cần lead review riêng.
- Các kết quả trên không chứng minh CI/PR hoặc email qua Internet.
- Không có lỗi PHP/JS chưa bắt trong bộ test; không phát hiện ảnh hỏng hoặc overflow ở 90 lượt trang/viewport.

## Cách tái hiện và phạm vi

Chạy lần lượt tests/customer/http.cjs rồi tests/customer/browser.cjs theo docs/customer-preview.md với database test riêng. Không chạy các test có ghi SQL trên dữ liệu thật. Bản audit không xác nhận toàn bộ accessibility chỉ bằng kiểm tra nhãn và overflow.

Ảnh CUS-01: evidence/CUS-01/index-375.png, index-768.png và index-1440.png; ảnh mới chạy ngày 14/10/2026.

