# Xem thử phần thành viên 2

Bản này dùng PHP và MariaDB có trong XAMPP, chạy bằng PHP development server tại **http://127.0.0.1:8033/index.php**. Cơ sở dữ liệu riêng là `cas_hoa_customer_preview`, cổng `3307`; SMTP thử nội bộ ở `1025`. Không thay database `cas_hoa` của nhóm. Đây là bản local chưa commit, chưa push và chưa tạo PR.

## Hưng xem gì trước

1. Trang chủ: chọn dịp, tông màu, kiểu hoa và ngân sách để tìm mẫu phù hợp.
2. Sản phẩm: thử kết hợp các bộ lọc, sắp xếp, sao chép URL và mở lại.
3. Mở mẫu **Lam tinh**: chọn giấy gói Kem hoặc Nâu kraft; thêm vào giỏ, đổi số lượng, tải lại trang.
4. Thanh toán: chọn giao tận nơi hoặc nhận tại shop, ngày từ ngày mai, khung giờ và người nhận. Dùng thông tin thử để tạo đơn; giữ mã thật hiển thị ở trang xác nhận.
5. Tra cứu: nhập mã đơn và SĐT người nhận; thử cả số đúng và số sai.
6. Đăng ký tài khoản thử, cập nhật hồ sơ, tạo đơn khi đăng nhập và xem lịch sử. Hai tài khoản có lịch sử độc lập.

Kem/Nâu kraft là dữ liệu minh họa chỉ thêm trong database preview. Sản phẩm gốc và bảng schema giữ nguyên. Email khôi phục được nhận thực bằng SMTP local và lưu ở `.local/mailbox/`; không gửi ra Gmail. Không đưa thư hoặc token vào Git/báo cáo.

## Khởi động lại trên máy đang xem thử

Chạy PowerShell trong thư mục `customer-preview`. Cần PHP, MariaDB của XAMPP và Python 3. Dữ liệu MariaDB phải nằm trong đường dẫn ASCII vì MariaDB 10.4 trên Windows gặp lỗi ở đường dẫn có dấu.

```powershell
./scripts/start-customer-preview.ps1 -MySqlData 'C:/duong-dan-ascii/mysql-preview' -PythonPath 'C:/duong-dan/python.exe'
```

Tham số `MySqlData` trỏ đến thư mục đã có `my.ini` cổng 3307, không trỏ vào thư mục dữ liệu MySQL đang sử dụng của nhóm. Script tái sử dụng các cổng đang chạy, ghi PID và log vào `.local/`. Các dịch vụ chỉ lắng nghe trên `127.0.0.1`.

Khi cài trên máy mới, có thể khởi tạo **một thư mục dữ liệu mới riêng** (ví dụ `C:/xampp/customer-preview-data`) bằng `C:/xampp/mysql/bin/mysql_install_db.exe --datadir=C:/xampp/customer-preview-data --port=3307`, rồi chạy script trên với đường dẫn đó. Sau khi MariaDB chạy, nhập dữ liệu thử:

```powershell
$env:DB_PORT = '3307'
$env:DB_NAME = 'cas_hoa_customer_preview'
& 'C:/xampp/php/php.exe' scripts/customer-setup.php
```

Script setup chỉ cho phép tên database `cas_hoa_customer_preview` hoặc `cas_hoa_customer_test_*`. Thiết lập Apache/phpMyAdmin thông thường của nhóm xem `REQUIREMENT2_SETUP.md`; chưa coi bản này là đã kiểm thử dưới Apache.

## Chạy lại kiểm thử

Cần Node.js, Playwright và Chrome. Các test dùng tài khoản, email `example.test`, số điện thoại và địa chỉ giả; chỉ chạy với database preview/test riêng. Test có tạo đơn và hồ sơ thử, không chạy trên dữ liệu thật.

```powershell
$env:CAS_APP_URL = 'http://127.0.0.1:8033'
$env:DB_PORT = '3307'
$env:DB_NAME = 'cas_hoa_customer_preview'
$env:PHP_BIN = 'C:/xampp/php/php.exe'
$env:CHROME_BIN = 'C:/Program Files/Google/Chrome/Application/chrome.exe'
# Khi Playwright đã cài trong project, không cần PLAYWRIGHT_MODULE.
# Nếu dùng bộ runtime riêng, đặt PLAYWRIGHT_MODULE là đường dẫn module playwright.
node tests/customer/http.cjs
node tests/customer/browser.cjs
```

Chạy hai bộ test lần lượt vì cả hai kiểm tra số bản ghi và có thay đổi dữ liệu thử tạm thời. Kết quả và ảnh chụp thật ở `docs/evidence/customer/`. Những bước xác minh trên GitHub, CI của branch mới và duyệt thay đổi checkout/auth còn chờ sau khi Hưng xem và cho phép commit.
