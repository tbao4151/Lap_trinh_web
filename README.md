# CÁ'S HOA — Đồ án Lập trình Web

Website bán hoa tươi được xây dựng cho đồ án môn Lập trình Web.

## Mục tiêu FrontEnd

Giao diện hiện có 25 màn hình và sử dụng đúng các công nghệ của giai đoạn FrontEnd:

- HTML5
- CSS3
- JavaScript thuần
- Bootstrap 5
- Bootstrap Icons
- Responsive Web Design
- LocalStorage cho một số chức năng tương tác trước khi kết nối BackEnd

## 5 trang chính có thể dùng để báo cáo giao diện

1. `index.html` — Trang chủ
2. `san-pham.html` — Danh sách sản phẩm và bộ lọc
3. `chi-tiet-san-pham.html` — Chi tiết sản phẩm
4. `lien-he.html` — Thông tin và biểu mẫu liên hệ
5. `tin-tuc.html` — Danh sách tin tức

Ngoài ra project còn có giỏ hàng, thanh toán, tra cứu đơn, tài khoản khách hàng, chính sách và khu vực quản trị.

## Danh sách 25 màn hình

### Batch 1 — Storefront
1. `index.html`
2. `san-pham.html`
3. `chi-tiet-san-pham.html`
4. `gio-hang.html`
5. `thanh-toan.html`

### Batch 2 — Đặt hàng và xác thực
6. `dat-hang-thanh-cong.html`
7. `tra-cuu-don-hang.html`
8. `dang-nhap.html`
9. `dang-ky.html`
10. `quen-mat-khau.html`

### Batch 3 — Tài khoản và nội dung
11. `tai-khoan.html`
12. `lich-su-don-hang.html`
13. `gioi-thieu.html`
14. `lien-he.html`
15. `tin-tuc.html`

### Batch 4 — Nội dung hỗ trợ và admin
16. `chi-tiet-tin-tuc.html`
17. `chinh-sach-giao-hang.html`
18. `chinh-sach-doi-tra.html`
19. `admin/dang-nhap.html`
20. `admin/index.html`

### Batch 5 — Quản lý
21. `admin/san-pham.html`
22. `admin/them-san-pham.html`
23. `admin/sua-san-pham.html`
24. `admin/don-hang.html`
25. `admin/khach-hang.html`

## Cấu trúc thư mục

- `assets/css/` — CSS giao diện người dùng và admin
- `assets/js/` — JavaScript cho sản phẩm, giỏ hàng, đơn hàng, tài khoản và admin
- `assets/images/` — ảnh PNG lưu trực tiếp trong project
- `admin/` — các màn hình quản trị

## Chạy project

Mở `index.html` bằng VS Code Live Server.

## Hướng phát triển BackEnd

Giai đoạn hoàn chỉnh sẽ dùng PHP + MySQL/MariaDB để xử lý:

- đăng ký, đăng nhập và session
- dữ liệu sản phẩm
- giỏ hàng và đặt hàng
- tra cứu trạng thái đơn
- lưu liên hệ
- CRUD sản phẩm
- quản lý đơn hàng
- quản lý khách hàng
