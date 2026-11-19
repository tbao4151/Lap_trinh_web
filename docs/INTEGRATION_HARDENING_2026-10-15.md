# CÁ'S HOA — Bàn giao nâng cấp nghiệp vụ 004

Ngày 15/10/2026. Nhánh triển khai: `feature/integration-hardening-20261015`, PR #2 vào `feature/requirement-2-php-mysql`.

## Cài đặt

Nếu đã import và chạy bản PHP/MySQL trước đó, sao lưu `cas_hoa` trước khi thao tác. **Không chạy lại seed để nâng cấp dữ liệu đang có.** Import tăng dần `database/migrations/004_order_inventory_sessions.sql` trong phpMyAdmin của database `cas_hoa`. Migration thêm `users.session_version`, `product_recipes`, `order_inventory_usage` và không xóa bản ghi cũ. Trên máy cài mới, import schema, seed, migrations 001 → 002 → 003 → 004.

Các phiên đăng nhập trước khi triển khai nâng cấp được yêu cầu đăng nhập lại. Sau khi đổi hoặc đặt lại mật khẩu, các phiên cũ ở trình duyệt khác sẽ không còn truy cập dữ liệu tài khoản.

## Cấu hình công thức để xuất kho tự động

Đăng nhập `admin/cong-thuc-hoa.php` bằng quyền Admin/Owner; chọn mẫu hoa và khai báo mỗi nguyên liệu cần cho **một** sản phẩm. Ví dụ bó hoa p002 cần 2 cành; đơn mua 3 bó phải có ít nhất 6 cành trong kho.

Luồng:

- `pending_confirmation → confirmed`: chưa tác động kho.
- `confirmed → preparing`: kiểm tất cả sản phẩm trong đơn có công thức, kiểm tồn và trừ nguyên liệu trong **một transaction**. Thiếu nguyên liệu/công thức sẽ trả lỗi mà không đổi trạng thái và không trừ kho. Lượng thực xuất được snapshot tại `order_inventory_usage`.
- `preparing/ready → cancelled`: hoàn đúng lượng nguyên liệu đã thực xuất, bất kể công thức sản phẩm đã thay đổi sau đó. Gửi lại thao tác không hoàn hai lần.
- `preparing → ready`: không trừ kho lần nữa.
- `ready → delivering` **qua trang giao hàng**, đồng thời ghi delivery_events; giao thất bại → `ready`, giao thành công → `completed`. Không cho giao đơn đang chờ xác nhận/đang chuẩn bị.

Không tự suy ra công thức từ tên hoa hay giá sản phẩm. Admin phải nhập công thức cho các mẫu đang bán trước khi sử dụng bước `preparing`.

## Thanh toán và hoàn tiền

Tạo yêu cầu `requested → approved → recorded` qua `admin/hoan-tien.php`. Chỉ khi admin ghi nhận mã giao dịch, tổng `refunds.status='recorded'` cập nhật `orders.payment_status` thành `partially_refunded` hoặc `refunded`. Vẫn **không có giao dịch ngân hàng tự động**. Yêu cầu đã ghi nhận và đang chờ duyệt không được vượt giá trị đơn. Báo cáo doanh thu đã thanh toán tính số thực nhận sau khi trừ các refund được ghi nhận.

## Kiểm thử

GitHub Actions cho PR #2 chạy PHP lint, JS syntax, MySQL 8, đăng ký/đăng nhập, luồng đặt hàng, ma trận quyền, định mức/kho/hoàn tiền/giao hàng, Selenium và xuất ảnh trình duyệt. Không kết luận CI đạt trước khi xem run xanh tại HEAD commit.

Bộ test bao gồm tình huống thiếu công thức, thiếu tồn, xuất đủ, thao tác lặp, hủy sau khi thay định mức, hoàn một phần/toàn bộ, phiên khác mất hiệu lực sau reset.

## Chưa nghiệm thu

- Cấu hình SMTP Internet để demo gửi email đặt lại mật khẩu (SMTP local trước đây chỉ kiểm thử transport).
- Chạy trên XAMPP/Apache/MariaDB bằng máy của nhóm; ảnh nộp báo cáo do thành viên tự chụp màn hình theo yêu cầu giảng viên.
- Đối soát nguồn tiền thật qua cổng thanh toán, logic vận hành nâng cao như đặt lịch tối đa theo khung giờ/nhà vận chuyển; không tuyên bố website đã production-ready.

**Lưu ý:** Bản báo cáo yêu cầu 2 ban đầu và ảnh cũ có thể khác sau migration 004; hãy chụp lại các màn hình nghiệp vụ thay đổi.
