# CÁ'S HOA — Kiểm kê tích hợp ba thành viên (15/10/2026)

## Phạm vi và các nhánh
- Repository môn học: tbao4151/Lap_trinh_web (PHP 8.2+, MySQL/MariaDB). Không lấy mã nguồn từ Web_Flower_Manus.
- Nền gốc: `feature/requirement-2-php-mysql` tại 9c35a94.
- Người phụ trách Customer: `feature/customer-completion` (3 commit so với nền gốc).
- Người phụ trách Admin: `feature/admin-operations-completion` (7 commit so với nền gốc).
- Nhánh tích hợp: `integration/cas-hoa-final-20261015`; PR #1 nhắm về nhánh PHP/MySQL, không chạm `main`.
- Merge commit hai cha giữ lại lịch sử phát triển của cả hai người. Sáu tệp có chỉnh sửa trùng được giải quyết theo chức năng thay vì chọn một nhánh đè nhánh kia.

## Đã tích hợp và kiểm tra
- 18 route khách hàng PHP và 22 route Admin PHP tồn tại trong mã nguồn tích hợp.
- Khách: danh mục và tìm kiếm lấy MySQL, gợi ý theo dịp/ngân sách, giỏ Session, checkout, mã đơn, hồ sơ, lịch sử và tra cứu, responsive.
- Admin: sản phẩm, ảnh nhiều tấm, danh mục, khám phá, giấy gói, kho tổng/hoa/phụ kiện, lịch sử xuất nhập, giao hàng, hoàn tiền theo workflow, nhân viên/quyền, shop settings, chủ sở hữu và audit.
- Từ MySQL 8 CI lần đầu phát hiện bí danh SQL `empty` làm trang kho tổng lỗi 500; đã sửa thành `empty_stock`.
- Đơn hàng: bổ sung kiểm chuyển trạng thái phía server, hạn chế sửa payment bởi staff, ràng buộc trạng thái giao và ghi audit.
- Tích hợp hiển thị ảnh phụ và discovery, giá giấy gói theo DB; checkout tính phí giao từ shop settings và phụ thu giấy gói tại server. `order_items.unit_price` lưu giá sau phụ thu và `order_note` ghi lại giấy gói đã chọn; tiền dòng/tổng đơn bảo toàn tính nhất quán.
- CI tự động: PHP/JS syntax, MySQL 8 smoke, auth/customer/admin, Admin Operations 216 checks, Chrome screenshot và PDF 30 trang (các kết quả tương ứng xem trực tiếp Actions của PR). 
- CI đã thành công tại commit 79bce82 trước cập nhật giá; chỉ coi cập nhật giá đạt sau khi workflow ở HEAD hoàn tất thành công.

## Những hạng mục cần xác minh khi nghiệm thu
1. Cài lại bằng XAMPP: clone nhánh tích hợp, import đúng thứ tự `database/schema.sql`, `database/seed.sql`, `database/migrations/001_auth.php.sql`, `002_inventory_seed.sql`, `003_admin_ops.sql`; tạo Admin bằng `scripts/create-admin.php`. Không làm việc bằng Live Server trên file PHP.
2. Mở thử 18 trang khách, 22 trang admin trong Chrome ở mobile/desktop, kiểm upload thật và môi trường Apache+MariaDB riêng của nhóm.
3. Chạy luồng xuyên suốt: đăng nhập/khách vãng lai → chọn hoa + màu giấy gói → giỏ → giao/nhận tại shop → đơn DB → admin xử lý → tra cứu và báo cáo.
4. Quên mật khẩu: SMTP local đã có bằng chứng; gửi email Internet cần cấu hình và xác minh độc lập.
5. Chụp ảnh trực tiếp từ trình duyệt trên máy nộp bài. CI có ảnh trình duyệt thật, nhưng không nên thay ảnh tự chụp khi giáo viên yêu cầu.
6. Kiểm tra điều kiện chấm yêu cầu 2 và thông tin nhóm trước khi cập nhật báo cáo.

## Những nâng cấp chưa được tuyên bố đã triển khai
- Công thức nguyên liệu cho mỗi mẫu hoa và tự động trừ/hoàn kho theo trạng thái đơn (nâng cấp chuyên sâu; hiện kho quản lý phiếu nhập/xuất thủ công).
- Đồng bộ trạng thái đơn và giao hàng theo một event/transaction duy nhất trên tất cả đường dẫn xử lý.
- Đối soát hoàn tiền từng phần/toàn phần với `orders.payment_status`; không mô phỏng chuyển khoản thật.
- Thu hồi mọi phiên thiết bị khác sau khi đổi/đặt lại mật khẩu bằng session_version.
- Giới hạn quyền xem báo cáo tài chính cho staff (cần chốt chính sách quản trị).
- Snapshots giấy gói bằng cột cấu trúc riêng trên `order_items` (hiện lưu trong `order_note`, `unit_price` đã gộp phụ thu).

Không tự tuyên bố 100% production-ready chỉ vì CI xanh: email Internet, môi trường máy nhóm và các chính sách vận hành này vẫn cần nghiệm thu.
