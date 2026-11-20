# TV3 — Sửa các vấn đề phát hiện khi nghiệm thu cài mới (20/11/2026)

Nghiệm thu cài mới phân hệ Admin trên `main` @ `04ec72b` (clone sạch, 7 tệp SQL, TC07–TC11) ghi nhận 5 vấn đề I-01…I-05. Nhánh này sửa cả 5.

| Mã | Vấn đề | Cách sửa | Tệp |
|---|---|---|---|
| I-01 | Ô "Đơn đã thanh toán" trên Dashboard chỉ đếm `payment_status='paid'`, nên đơn vừa ghi nhận hoàn một phần biến mất (nghiệm thu: còn 0 dù doanh thu thuần 790.000 ₫) | Đếm `paid`, `partially_refunded`, `refunded` — cùng tập trạng thái "đã thu" với `analytics.php` | `admin/modules/orders.php` |
| I-02 | `tests/admin/test_admin_ops.py` giả định DB rỗng: chọn "đơn chưa thanh toán" bằng `payment_status<>'paid'` (trúng cả đơn hoàn một phần), so chuỗi `'24.000'` trên trang lợi nhuận, và không dọn định mức cũ của p002 | Chọn `payment_status IN ('unpaid','partial')`; so **phần chênh** giá vốn trước/sau khi đơn hoàn tất (= 24.000); xóa định mức p002 trước ca "thiếu công thức" | `tests/admin/test_admin_ops.py` |
| I-03 | XAMPP cũ kèm PHP 8.0.30 (< 8.2) vẫn chạy được một phần, lỗi khó nhận ra | `includes/bootstrap.php` dừng sớm với thông báo rõ khi `PHP_VERSION_ID < 80200` (web: HTTP 500, CLI: mã thoát 1); README thêm bước kiểm tra `php -v` | `includes/bootstrap.php`, `README.md` |
| I-04 | Staff mở được `admin/bao-cao.php` (số liệu doanh thu) trong khi Biểu đồ lợi nhuận đã chặn staff | Áp dụng đề xuất C7: `reports` chỉ cho admin/owner, staff nhận 403 + audit `denied`; ẩn menu và ô "Doanh thu thuần" trên Dashboard với staff | `admin/_app.php`, `admin/_nav.php`, `admin/modules/orders.php` |
| I-05 | `refund_events.note` khi ghi nhận không kèm ghi chú bị lưu `"| Mã GD: …"` (thừa dấu đầu dòng) | Hàm `cas_refund_event_note()` chỉ ghép các phần không rỗng | `admin/modules/refunds.php` |

## Kiểm thử

Môi trường: PHP 8.2.34 CLI, MariaDB 10.4.32 cô lập cổng 3408 (không dùng 3306), `php -S`.

| Bộ | Kết quả |
|---|---|
| `verify-r2.sh` → `verify-r2-auth-admin.sh` → `verify-admin-ops.sh` trên DB mới | exit 0 / exit 0 / **251/251** (248 cũ + 3 kiểm tra mới) |
| `verify-admin-ops.sh` chạy lại trên DB **đã có dữ liệu** (tình huống làm I-02 lộ ra) | **250/250** (trước khi sửa: 245/247 trên DB demo; 244/250 khi chưa dọn định mức cũ của p002) |
| `tests/admin/test_admin_backend_final.py` | **29/29** |
| `php -l` toàn bộ tệp PHP bằng PHP 8.0.30 và 8.2.34 | không lỗi cú pháp; với 8.0.30, `bootstrap.php` in thông báo yêu cầu 8.2 và thoát 1 |

Kiểm tra mới trong `test_admin_ops.py`: ghi chú lịch sử hoàn tiền không thừa `| `; Dashboard "Đơn đã thanh toán" khớp SQL sau khi hoàn một phần; staff không thấy doanh thu và menu Báo cáo; ma trận quyền: `GET bao-cao as staff -> 403`.
