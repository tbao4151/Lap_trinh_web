# CÁ'S HOA — Đồ án Lập trình Web

Website bán hoa tươi được xây dựng cho đồ án môn Lập trình Web.

## FrontEnd

Project sử dụng:

- HTML5
- CSS3
- JavaScript thuần
- Bootstrap 5
- Bootstrap Icons
- Responsive Web Design
- LocalStorage cho các tương tác FrontEnd trước khi kết nối BackEnd

## Các trang khách hàng

Project có đầy đủ nhóm trang chính:

- Trang chủ
- Danh sách và chi tiết sản phẩm
- Giỏ hàng và thanh toán
- Đăng nhập, đăng ký, quên mật khẩu
- Tài khoản và lịch sử đơn
- Tra cứu đơn hàng
- Giới thiệu, liên hệ
- Tin tức và chi tiết bài viết
- Chính sách giao hàng và đổi trả

## Khu vực quản trị

Thư mục `admin/` gồm các nhóm chức năng:

### Tổng quan
- Dashboard vận hành với 8 chỉ số chính
- Đơn gần đây
- Cảnh báo nguyên liệu sắp hết

### Vận hành
- Đơn hàng
- Hoàn tiền
- Giao hàng
- Khách hàng
- Báo cáo doanh thu

### Sản phẩm
- Quản lý sản phẩm
- Thêm / sửa sản phẩm
- Quản lý nhanh giá và trạng thái
- Màu giấy gói
- Phân loại & bộ lọc
- Danh mục khám phá trang chủ

### Kho
- Kho tổng
- Kho Hoa
- Kho Phụ kiện
- Lịch sử nhập / xuất kho

### Hệ thống
- Nhân viên & quyền
- Cài đặt shop
- Tài khoản quản trị
- Khu vực chủ sở hữu

## Chạy local

Có thể mở bằng VS Code Live Server hoặc đặt project trong `htdocs` của XAMPP.

## Giai đoạn BackEnd

Giai đoạn hoàn chỉnh sẽ dùng PHP + MySQL/MariaDB để thay dữ liệu LocalStorage bằng dữ liệu phía server, session đăng nhập và CRUD trong cơ sở dữ liệu.
