# CÁ'S HOA — Website thương mại điện tử bán hoa và quản lý cửa hàng

Đồ án môn **Lập trình Web và Ứng dụng** — nhóm 3 thành viên. Mã nguồn học phần này dùng **HTML5, CSS3, Bootstrap 5, JavaScript thuần, PHP 8.2+, MySQL 8/MariaDB tương thích**; không sử dụng Next.js hoặc Supabase.

## Chọn đúng phiên bản
- `main`: giao diện tĩnh của yêu cầu 1, được bảo toàn.
- `feature/requirement-2-php-mysql`: bản PHP/MySQL để phát triển và nghiệm thu yêu cầu 2.
- `integration/cas-hoa-final-20261015`: nhánh tích hợp code của hai thành viên (Customer và Admin Operations), đang chờ nghiệm thu/merge qua PR #1.

Khách hàng: **18 route PHP**. Quản trị: **22 route PHP**. Mốc **18+12=30/40** đã thống nhất cho yêu cầu 2 là chỉ tiêu quản lý theo màn hình, không thay thế cách chấm của giảng viên. Các trang chỉ được đánh dấu đạt khi có kiểm thử chức năng; số lượng file PHP không tự chứng minh hoàn thành.

## Chạy XAMPP từ GitHub

Yêu cầu PHP **8.2+** với `pdo_mysql`, `mbstring`, MySQL 8 hoặc MariaDB tương thích, Apache và Git.

```powershell
cd C:\xampp\htdocs
git clone https://github.com/tbao4151/Lap_trinh_web.git
cd Lap_trinh_web
git switch integration/cas-hoa-final-20261015
```

Trong XAMPP bật **Apache** và **MySQL**, vào `http://localhost/phpmyadmin`. Import SQL theo thứ tự:

1. `database/schema.sql`
2. `database/seed.sql`
3. `database/migrations/001_auth.php.sql`
4. `database/migrations/002_inventory_seed.sql`
5. `database/migrations/003_admin_ops.sql`
6. `database/migrations/004_order_inventory_sessions.sql`
7. `database/migrations/005_profit_cost_snapshot.sql`

Mặc định PDO kết nối `127.0.0.1:3306`, database `cas_hoa`, user `root`, mật khẩu rỗng (thiết lập XAMPP phổ biến). Nếu khác, đặt biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`. **Không đưa mật khẩu hoặc file .env lên GitHub.**

Tạo quản trị viên bằng PHP CLI, không sử dụng tài khoản demo tĩnh:

```bat
cd C:\xampp\htdocs\Lap_trinh_web
set CAS_ADMIN_EMAIL=admin-nhom@example.com
set CAS_ADMIN_PASSWORD=ThayBangMatKhauManh!
C:\xampp\php\php.exe scripts\create-admin.php
```

Mở `http://localhost/Lap_trinh_web/index.php` và `http://localhost/Lap_trinh_web/admin/dang-nhap.php`. Không dùng Live Server hoặc mở `file://` để thử nghiệp vụ PHP.

## Chức năng chính
- Catalog MySQL, tìm kiếm, lọc loại hoa/tone/dịp/giá; gợi ý và nhóm khám phá.
- Nhiều ảnh sản phẩm, giấy gói, giỏ hàng PHP Session, checkout giao/nhận tại cửa hàng, tạo mã đơn/tra cứu.
- Xác thực khách hàng qua Session và mật khẩu hash; hồ sơ/lịch sử đơn; quên mật khẩu bằng token qua mail transport được cấu hình.
- Admin: Dashboard, sản phẩm/ảnh/taxonomy, giá nhanh, giấy gói, kho hoa/phụ kiện, nhập/xuất, lịch sử, giao hàng, hoàn tiền, nhân viên, chủ sở hữu, cài đặt.
- PHP server kiểm phân quyền và CSRF, PDO prepared statements, transaction cho cập nhật tiền/kho, log thao tác.
- Checkout tính lại giá, phụ thu giấy gói, phí giao hàng bằng dữ liệu server; ghi snapshot tổng tiền vào MySQL.

## Kiểm thử

GitHub Actions workflow: **PHP coursework validation**, áp dụng cho Pull Request vào `feature/requirement-2-php-mysql`. Có PHP/JS lint, MySQL 8 HTTP/integration tests, Admin Operations tests (216 trường hợp), và script chụp 30 màn hình bằng Chrome thật.

```bash
bash scripts/verify-r2.sh
bash scripts/verify-r2-auth-admin.sh
bash scripts/verify-admin-ops.sh
```

Các script này cần PHP CLI, MySQL, `curl`, Python và môi trường Linux/Git Bash được cấu hình thích hợp; một số test khởi tạo hoặc thêm dữ liệu vào database `cas_hoa`, **chỉ chạy trên database kiểm thử**. Để kiểm thử trình duyệt trên Windows xem hướng dẫn `docs/customer-preview.md`.

## Nghiệm thu và những giới hạn

Tài liệu đầy đủ: `REQUIREMENT2_SETUP.md`, `docs/REQUIREMENT2_SCOPE_AND_ACCEPTANCE.md`, `docs/INTEGRATION_REVIEW_2026-10-15.md` và **`docs/INTEGRATION_HARDENING_2026-10-15.md`** (migration 004, công thức kho, hoàn tiền và thu hồi phiên).

Phải tự kiểm tra lại trên XAMPP của nhóm, gồm upload ảnh, phân quyền, checkout, tra cứu và mobile. Cần cấu hình mail/SMTP thật nếu demo quên mật khẩu qua email Internet. Định mức và trừ/hoàn nguyên liệu cần được cấu hình theo từng mẫu tại `admin/cong-thuc-hoa.php`; hoàn tiền được đối soát theo trạng thái đơn nhưng không tự chuyển tiền thật; xem danh sách ở tài liệu kiểm kê tích hợp.


## Tư vấn chọn hoa và báo cáo lợi nhuận gộp (nâng cấp 005)

- Khách hàng: `tu-van-hoa.php` lọc mẫu theo dịp, tone và ngân sách. Khi môi trường PHP có `OPENAI_API_KEY` và cURL, máy chủ gọi AI tạo lời tư vấn dựa trên mẫu đang bán trong MySQL. Nếu không có API hoặc gặp lỗi, kết quả ghi rõ `Gợi ý dựa trên bộ lọc sản phẩm`, không giả lập AI. Hạn chế 8 lần gửi/15 phút/phiên, kiểm tra CSRF; không commit API key.
- Admin/Owner: `admin/loi-nhuan.php` vẽ đồ thị theo ngày/tháng, doanh thu sau hoàn tiền, giá vốn nguyên liệu theo snapshot khi xuất, lợi nhuận gộp và tỷ suất. Đơn cũ chưa có giá vốn lịch sử được đánh dấu chưa đủ dữ liệu, không tính lợi nhuận giả. Đây KHÔNG phải lợi nhuận ròng (chưa trừ chi phí vận hành).
- Migrations `005` thêm hai cột snapshot giá vốn không phá dữ liệu lịch sử. Chỉ các lần xuất sau nâng cấp mới có snapshot mới.
