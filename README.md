# CÁ'S HOA — Website bán hoa tươi và quản lý cửa hàng

**Đồ án môn Lập trình Web và Ứng dụng — Trường Đại học Tôn Đức Thắng (TDTU)**

CÁ'S HOA là hệ thống website thương mại điện tử bán hoa tươi, kết hợp giao diện mua sắm dành cho khách hàng với hệ thống quản trị cửa hàng. Dự án được phát triển theo mô hình PHP/MySQL truyền thống, có thể chạy cục bộ bằng XAMPP để nghiệm thu và trình diễn các nghiệp vụ từ giới thiệu sản phẩm, tư vấn chọn hoa, đặt hàng đến quản lý đơn, hàng tồn kho và báo cáo kinh doanh.

Đây là tài liệu **duy nhất mô tả phiên bản website đã tích hợp** trong repository này. Không cần chuyển sang nhánh tính năng riêng hoặc đọc nhật ký nâng cấp để sử dụng website.

- **Mã nguồn:** https://github.com/tbao4151/Lap_trinh_web
- **Nhánh sử dụng:** `main`
- **Website thương hiệu tham khảo:** https://cashoa.vn
- **Môi trường chạy bản PHP của đồ án:** XAMPP trên máy tính; website trực tuyến có thể sử dụng kiến trúc triển khai khác với mã nguồn PHP này.

## 1. Công nghệ sử dụng

| Thành phần | Công nghệ |
| --- | --- |
| Giao diện | HTML5, CSS3, Bootstrap 5, JavaScript |
| Backend | PHP 8.2 trở lên |
| Cơ sở dữ liệu | MySQL 8.0 hoặc MariaDB tương thích |
| Truy cập dữ liệu | PDO, prepared statements |
| Máy chủ cục bộ | Apache trong XAMPP |
| Quản lý mã nguồn | Git và GitHub |

Dự án không yêu cầu Laravel, Node.js hay Supabase để chạy bản PHP/XAMPP. Dịch vụ AI chỉ là tùy chọn cho chức năng tư vấn hoa.

## 2. Chức năng website

### 2.1. Khách truy cập và khách hàng

- Xem trang chủ, danh sách sản phẩm, các mẫu hoa nổi bật và mẫu hoa hôm nay.
- Xem thông tin từng mẫu hoa: hình ảnh, tên, giá, mô tả và các thuộc tính liên quan.
- Tìm kiếm, duyệt và lọc sản phẩm theo nhóm hoa, kiểu sản phẩm, màu sắc, dịp tặng hoặc tiêu chí được giao diện hỗ trợ.
- Khám phá gợi ý hoa theo dịp, tông màu và ngân sách.
- Đăng ký, đăng nhập, đăng xuất và sử dụng các chức năng tài khoản khách hàng.
- Sử dụng giỏ hàng: thêm sản phẩm, thay đổi số lượng và cập nhật lựa chọn trước khi đặt hàng.
- Đặt hàng qua quy trình checkout; nhập thông tin người nhận, hình thức nhận hàng, thời gian giao/nhận và lời nhắn theo biểu mẫu.
- Tra cứu tình trạng đơn hàng và theo dõi lịch sử đơn hàng khi đăng nhập.
- Xem tin tức/nội dung giới thiệu và gửi yêu cầu liên hệ.
- Sử dụng chức năng quên/đặt lại mật khẩu khi email và cấu hình gửi thư của máy chủ đáp ứng yêu cầu.

**Tư vấn chọn hoa:** `tu-van-hoa.php` hỗ trợ lọc và đề xuất mẫu hoa phù hợp. Nếu cấu hình `OPENAI_API_KEY` hợp lệ và máy chủ có cURL, website có thể gọi AI qua backend; nếu không, chức năng vẫn có gợi ý dựa trên bộ lọc sản phẩm. Không mô tả chế độ bộ lọc dự phòng là AI.

### 2.2. Quản trị viên và nhân viên

Khu vực `admin/` phục vụ các nghiệp vụ quản trị theo phân quyền được cấp:

- Đăng nhập và quản lý phiên làm việc quản trị.
- Xem bảng điều khiển, thống kê kinh doanh.
- Quản lý sản phẩm, danh mục, hình ảnh và các thông tin hiển thị trên website.
- Quản lý khách hàng, tài khoản và vai trò theo chức năng được cấp.
- Tiếp nhận, cập nhật trạng thái và xử lý đơn hàng.
- Quản lý các nghiệp vụ giao hàng, hàng tồn kho, nguyên vật liệu và lịch sử xuất/nhập khi có dữ liệu.
- Theo dõi doanh thu, hoàn tiền và báo cáo lợi nhuận gộp.
- Quản lý những nội dung và cấu hình vận hành khác được cung cấp qua menu quản trị.

**Lợi nhuận gộp:** `admin/loi-nhuan.php` sử dụng doanh thu sau hoàn tiền và giá vốn nguyên liệu được chụp tại thời điểm xuất kho. Đây là **lợi nhuận gộp, không phải lợi nhuận ròng**; chưa trừ toàn bộ chi phí vận hành. Đơn cũ chưa có snapshot giá vốn được xử lý riêng, không tự suy diễn lãi.

## 3. Kiến trúc và cấu trúc thư mục

```text
Lap_trinh_web/
├── index.php                 # Trang chủ
├── *.php                     # Trang dành cho khách hàng
├── admin/                    # Giao diện và nghiệp vụ quản trị
│   └── modules/              # Các module xử lý nội bộ
├── actions/                  # HTTP action/endpoint của khách hàng
├── assets/
│   ├── css/                  # CSS giao diện
│   ├── js/                   # JavaScript và PHP feed dữ liệu
│   ├── images/               # Hình ảnh tĩnh
│   └── uploads/              # Tệp tải lên
├── config/                   # Kết nối và cấu hình
├── includes/                 # Hàm dùng chung, layout, nghiệp vụ
├── database/
│   ├── schema.sql            # Tạo cấu trúc DB
│   ├── seed.sql              # Dữ liệu khởi tạo
│   └── migrations/           # Các bước mở rộng schema
├── scripts/                  # Script setup / kiểm tra
├── tests/                    # Kiểm thử
└── docs/                     # Tài liệu kỹ thuật hỗ trợ
```

Luồng xử lý tổng quát:

```text
Trình duyệt
    -> Apache / PHP
    -> Trang khách hàng hoặc trang quản trị
    -> Action / nghiệp vụ PHP
    -> PDO / MySQL
    -> HTML hoặc JSON trả về trình duyệt
```

Những thao tác liên quan đến tài khoản, quyền truy cập, dữ liệu đơn hàng và tồn kho cần được xử lý và kiểm tra phía server. Giao diện JavaScript không phải nguồn xác thực cuối cùng cho giá hoặc quyền.

## 4. Hướng dẫn cài đặt và chạy bằng XAMPP

### Bước 1. Chuẩn bị

- Cài XAMPP có Apache, MySQL/MariaDB và PHP **8.2+**.
- Bật các extension PHP cần thiết, tối thiểu `pdo_mysql`, `mbstring`, `fileinfo`. Bật `curl` nếu dùng dịch vụ AI.
- Cài Git hoặc tải ZIP mã nguồn của nhánh `main`.

### Bước 2. Tải mã nguồn

Mở PowerShell hoặc Command Prompt:

```powershell
cd D:\xampp\htdocs
git clone https://github.com/tbao4151/Lap_trinh_web.git
cd Lap_trinh_web
```

Nếu đã có repository trên máy, chạy trong đúng thư mục:

```powershell
git switch main
git pull origin main
```

Nếu XAMPP được cài ở `C:\xampp`, thay đường dẫn tương ứng. Không cần checkout nhánh thành viên, nhánh tính năng hay nhánh tích hợp.

### Bước 3. Khởi động dịch vụ

Mở **XAMPP Control Panel**, bật **Apache** và **MySQL**. Truy cập:

`http://localhost/phpmyadmin`

### Bước 4. Khởi tạo database

Trong phpMyAdmin, import lần lượt các file SQL theo đúng thứ tự sau:

1. `database/schema.sql`
2. `database/seed.sql`
3. `database/migrations/001_auth.php.sql`
4. `database/migrations/002_inventory_seed.sql`
5. `database/migrations/003_admin_ops.sql`
6. `database/migrations/004_order_inventory_sessions.sql`
7. `database/migrations/005_profit_cost_snapshot.sql`

`schema.sql` khai báo database **`cas_hoa`**. Import trên database phù hợp với cú pháp của từng file. **Chỉ dùng quy trình khởi tạo sạch với database mới**; không xóa hoặc import đè database đang có dữ liệu cần giữ.

Cấu hình mặc định trong `config/database.php`:

| Biến | Giá trị mặc định |
| --- | --- |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `cas_hoa` |
| `DB_USER` | `root` |
| `DB_PASSWORD` | rỗng |

Nếu máy khác cấu hình trên, đặt biến môi trường phù hợp. Không đưa mật khẩu database hay API key vào Git.

### Bước 5. Tạo tài khoản quản trị

Mở **Command Prompt** và chạy ví dụ sau (điền email/mật khẩu do nhóm tự quản lý):

```bat
cd /d D:\xampp\htdocs\Lap_trinh_web
set CAS_ADMIN_EMAIL=admin@example.com
set CAS_ADMIN_PASSWORD=MatKhauManhDoBanTuDat
set CAS_ADMIN_ROLE=owner
D:\xampp\php\php.exe scripts\create-admin.php
```

Nếu đặt dự án tại `C:\xampp`, đổi các đường dẫn `D:\xampp` thành `C:\xampp`. Không dùng các giá trị ví dụ làm thông tin đăng nhập thật.

### Bước 6. Mở website

- **Trang khách hàng:** http://localhost/Lap_trinh_web/index.php
- **Trang đăng nhập quản trị:** http://localhost/Lap_trinh_web/admin/dang-nhap.php

Địa chỉ phải mở qua Apache (`http://localhost/...`), không mở file PHP bằng `file://` hoặc Live Server.

## 5. Quy trình sử dụng và demo

### Khách hàng

1. Mở trang chủ, xem sản phẩm nổi bật và các mục khám phá.
2. Truy cập danh sách sản phẩm; lọc hoặc tìm kiếm mẫu hoa.
3. Mở trang chi tiết và thêm mẫu hoa vào giỏ hàng.
4. Xem, sửa giỏ hàng và thực hiện đặt hàng với thông tin người nhận.
5. Tra cứu đơn đã tạo hoặc vào tài khoản để kiểm tra lịch sử đơn.
6. Minh họa đăng ký, đăng nhập, đăng xuất và đặt lại mật khẩu (nếu có cấu hình email).
7. Truy cập trang tư vấn hoa để thử lọc theo nhu cầu.

### Quản trị

1. Đăng nhập bằng tài khoản đã tạo qua script.
2. Kiểm tra dashboard và các thống kê.
3. Xem/thêm/sửa dữ liệu sản phẩm, hình ảnh hoặc danh mục theo quyền.
4. Mở danh sách đơn hàng, kiểm tra chi tiết và xử lý trạng thái đơn.
5. Kiểm tra nghiệp vụ kho, giao hàng và dữ liệu giá vốn.
6. Xem báo cáo doanh thu, hoàn tiền và lợi nhuận gộp.
7. Đăng xuất khỏi khu vực quản trị.

Để demo có kết quả rõ ràng, chuẩn bị trước dữ liệu sản phẩm, hình ảnh và một số đơn hàng thử; không dùng dữ liệu khách hàng thật.

## 6. Bảo mật và kiểm tra

Các cơ chế được tổ chức trong code PHP của dự án gồm kiểm tra phiên/ủy quyền, CSRF cho action cần bảo vệ, truy cập SQL qua PDO/prepared statements và bảo vệ truy cập HTTP trực tiếp tới các file/thư mục nội bộ. Kiểm tra cấu hình Apache và `.htaccess` khi chuyển sang máy khác.

Có thể kiểm tra cơ bản:

```bash
php -l index.php
node --check assets/js/main.js
```

Nếu cần kiểm thử tích hợp sâu hơn, repository có các script:

```bash
bash scripts/verify-r2.sh
bash scripts/verify-r2-auth-admin.sh
bash scripts/verify-admin-ops.sh
```

**Cảnh báo:** Chỉ chạy script kiểm thử tích hợp trên **database thử nghiệm riêng** vì chúng có thể tạo hoặc sửa tài khoản, sản phẩm, đơn hàng và tồn kho. Một số lệnh yêu cầu Bash, PHP CLI, Node.js hoặc cấu hình test bổ sung.

## 7. Các lưu ý khi vận hành

- **Email quên mật khẩu:** XAMPP mặc định không bảo đảm gửi email Internet. Cần cấu hình SMTP/mail server có khả năng gửi thư. Chức năng tạo yêu cầu đặt lại mật khẩu và khả năng giao email là hai phần riêng biệt.
- **Tư vấn AI:** Chỉ dùng chế độ gọi AI nếu đã cấu hình API key hợp lệ ở môi trường máy chủ. Khi chưa cấu hình, vẫn có thể trình diễn gợi ý sản phẩm bằng bộ lọc.
- **Ảnh và sản phẩm:** Số lượng mẫu hiển thị phụ thuộc dữ liệu đã import và các tệp ảnh thực tế trong thư mục assets/uploads; repository không tự đồng bộ toàn bộ sản phẩm từ website trực tuyến.
- **Thanh toán và giao hàng:** Chỉ giới thiệu các phương thức thực sự đã cấu hình trong bản chạy; không đồng nhất một lựa chọn giao diện với tích hợp thanh toán/vận chuyển trực tiếp ngoài đời.
- **Triển khai:** Bản PHP/XAMPP của đồ án và website thương mại trực tuyến `cashoa.vn` không mặc nhiên dùng chung cơ sở dữ liệu, tài khoản hay hệ thống backend.

## 8. Thông tin đồ án

- **Đề tài:** Xây dựng website bán hoa tươi và quản lý cửa hàng CÁ'S HOA.
- **Học phần:** Lập trình Web và Ứng dụng.
- **Trường:** Đại học Tôn Đức Thắng.
- **Nhóm:** 3 thành viên.
- **Repository nộp và demo:** https://github.com/tbao4151/Lap_trinh_web

README này trình bày hệ thống dưới dạng **một sản phẩm tích hợp hoàn chỉnh**, thay vì chia theo từng thành viên, từng yêu cầu nhỏ hay lịch sử sửa lỗi. Các file trong `docs/` và `tests/` chỉ là tài liệu kỹ thuật và kiểm thử bổ trợ, không phải các phiên bản website khác nhau.
