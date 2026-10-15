# Đề xuất phối hợp với trưởng nhóm

Ngày 14/10/2026. Chưa gửi ra GitHub; chỉ là bản đề xuất local chờ duyệt.

## Hai tệp core đã có thay đổi trước phiên

`actions/checkout.php` đã có idempotency theo Session, kiểm tra giấy gói và snapshot người đặt/giấy gói trong order_note; `includes/auth.php` đã giới hạn mật khẩu tối đa 72 byte và loại ký tự NUL. Đây là diff có trước lúc tiếp tục mục tiêu, không phải thay đổi do phiên này tạo. Không tự hoàn nguyên cũng không sửa thêm. Các test local sử dụng đúng bản này, nên kết quả không chứng minh nhánh gốc chưa có diff sẽ đạt tương tự.

Đề nghị lead review riêng: key checkout, replay trong cùng Session, tính tương thích client cũ, giới hạn lưu key, snapshot giấy gói/đơn và quy tắc password. Không đổi schema, role, giá hoặc trạng thái đơn. Đối chiếu SHA256 ban đầu/cuối trong bằng chứng CUS-07 để xác nhận không bị sửa thêm.

## CUS-02 Nhiều ảnh sản phẩm

Schema chỉ có `products.image`, chưa có bảng gallery hay thứ tự ảnh. Hiện chi tiết hiển thị một ảnh thật, không nhân bản ảnh để giả gallery. Đề xuất lead thiết kế migration ảnh phụ (product_id, đường dẫn ảnh, alt, thứ tự) và xác định nguồn ảnh. Chỉ sau duyệt mới triển khai gallery/phím điều hướng cùng kiểm thử.

## CUS-02 Trạng thái hết hàng

Schema có status sản phẩm và kho nguyên liệu nhưng chưa có quan hệ định mức từ nguyên liệu tới sản phẩm hoặc trường tồn kho thành phẩm. Hiện storefront/API chỉ nhận sản phẩm active; ẩn được loại bỏ và slug bị từ chối. Không tự suy ra số lượng bán được từ kho nguyên liệu, không tự đặt trạng thái mới. Cần lead chốt contract hết hàng, hiển thị, đặt trước và kiểm tra lại tại checkout trước khi nghiệm thu đủ tiêu chí này.

## CUS-03 Chống đơn trùng và giấy gói

Kiểm thử local xác nhận hai request cùng key tạo một đơn, giá từ DB, giấy gói được kiểm tra theo mapping. Phần này phụ thuộc diff core đã có và vẫn chờ lead review; không coi đây là phần thay đổi core đã được duyệt. Thông báo giá trên UI giữ là tạm tính, phí giao hàng được shop xác nhận theo quy tắc hiện hành.

## Môi trường và bàn giao

- Apache URL `http://localhost/Lap_trinh_web/index.php` chưa chạy, thư mục triển khai htdocs chưa có; không sửa cấu hình hệ thống hoặc chép ra ngoài repo trong phiên này. Đã chạy PHP/MariaDB XAMPP tại preview riêng trong repo.
- SMTP local nhận thư thật vào hộp thư thử riêng; không phải kiểm chứng chuyển thư qua Internet. Không đưa thư, token hoặc mật khẩu vào báo cáo/Git.
- CUS-07: commit/push/PR và CI của nhánh customer chưa thực hiện theo lệnh người dùng. Không thể ghi CI xanh hoặc PR đã được review. Bản mô tả PR và commit message sẽ được chuẩn bị local.
