# TV3 — Audit và hoàn thiện BackEnd Admin (ADM-BE-01..04)

- Nhánh: `feature/tv3-admin-backend-final`, tách từ `feature/project-structure-refactor` @ `d68e0a8e09b691e60c7e103d3d2b0bc4cdcc0fe5` (worktree sạch khi tạo nhánh).
- Môi trường kiểm thử: PHP 8.2.34 CLI (`php -S`), **MariaDB 10.4.32 cô lập ở cổng 3407**, datadir tạm riêng, DB `cas_hoa` được tạo mới bằng `schema.sql` + `seed.sql` + migrations 001–005. Không chạm DB cổng 3306.
- Ngày chạy: 2026-11-19.

## 1. Tóm tắt

| Mã | Vấn đề | Bằng chứng | Kết luận |
|---|---|---|---|
| BE-01-A | Dashboard "Doanh thu hoàn tất" chỉ cộng `payment_status='paid'` → **bỏ sót toàn bộ đơn hoàn một phần**, lệch với Báo cáo doanh thu và Lợi nhuận gộp | Test FAIL trước sửa: dashboard 1.450.000 ₫, SQL tham chiếu 1.870.000 ₫ (lệch đúng bằng phần còn lại của các đơn hoàn một phần) | **Đã sửa** — 3 màn dùng chung 1 định nghĩa |
| BE-01-B | Trang Lợi nhuận gộp: ô "Số đơn hoàn tất / đã thanh toán" **luôn = 0**; PHP log `Undefined array key "orders"` (analytics.php:59) do khóa `$totals['orders']` không trùng cột SQL `order_count` | Test FAIL trước sửa: UI 0, SQL 10; log PHP Warning | **Đã sửa** |
| BE-02-A | Trang Đơn hàng cho chuyển `delivering → ready` nhưng **không đồng bộ bảng `deliveries`** (vẫn `out_for_delivery`) → đơn bị kẹt: Giao hàng không xuất giao lại được (422) và không xác nhận đã giao được (422); chỉ còn cách hủy | Test FAIL trước sửa: `ready/out_for_delivery`, 2 thao tác giao hàng sau đó đều 422 | **Đã sửa** — đưa lịch giao về `not_delivered` + ghi `delivery_events` trong cùng transaction |
| BE-02/03 | Các nghiệp vụ còn lại (khóa `FOR UPDATE`, trừ/hoàn kho theo snapshot, không trừ/hoàn 2 lần, giá vốn bất biến, chặn hoàn vượt, quyền, CSRF, chống gửi trùng, audit) | 24 assertion PASS cả trước và sau sửa | **Đúng nghiệp vụ, không sửa** (có test chứng minh) |

## 2. ADM-BE-01 — Định nghĩa doanh thu thống nhất

### Quyết định nghiệp vụ

**Doanh thu thuần (sau hoàn)** — nhãn chung `CAS_REVENUE_NET_LABEL` trên cả 3 màn:

- Chỉ tính đơn `order_status='completed'` **và** `payment_status IN ('paid','partially_refunded','refunded')`. Đơn chưa thu (`unpaid`), mới cọc (`partial`), chưa hoàn tất hoặc đã hủy → không phải doanh thu.
- Tiền đã hoàn = tổng `refunds.amount` có `status='recorded'`, **gộp theo đơn trong subquery trước khi JOIN** → nhiều lần hoàn của một đơn không nhân đôi tiền đơn.
- Doanh thu thuần = `GREATEST(total_amount − tiền đã hoàn, 0)` → hoàn đủ = 0, không âm.
- Yêu cầu hoàn đang chờ / đã duyệt / bị từ chối **không** trừ doanh thu cho tới khi được ghi nhận (`recorded`); nhưng vẫn giữ chỗ hạn mức hoàn để chặn hoàn vượt.
- Dashboard: toàn thời gian. Báo cáo doanh thu và Lợi nhuận gộp: theo **ngày tạo đơn** trong khoảng đã chọn (giữ nguyên như trước). Với khoảng bao trùm mọi đơn, Báo cáo = Dashboard (có test).
- "Giá trị tạo đơn / Giá trị đơn" trên Báo cáo vẫn là **doanh số gộp mọi trạng thái** (giữ nguyên ý nghĩa cũ, nhãn đã phân biệt với doanh thu thuần).

**Lợi nhuận gộp nguyên liệu** giữ nguyên ý nghĩa: doanh thu thuần của đơn có đủ snapshot giá vốn − giá vốn nguyên liệu theo snapshot lúc xuất kho. Không phải lợi nhuận ròng (trang vẫn ghi rõ). Đơn thiếu snapshot **không** suy ra từ giá hiện tại: không vào "Doanh thu có đủ giá vốn", được đếm riêng ở "Đơn chưa đủ giá vốn". Đơn hoàn toàn bộ vẫn mang giá vốn nguyên liệu đã xuất → lợi nhuận gộp của đơn đó âm; đây là đúng thực tế (nguyên liệu đã dùng), không bị chặn về 0.

### Truy vấn trước / sau

Trước (`admin/modules/orders.php`, dashboard):
```sql
SELECT COALESCE(SUM(total_amount),0) FROM orders
WHERE order_status='completed' AND payment_status='paid'
```
Sau (hàm dùng chung `cas_revenue_net_total()` trong `admin/modules/analytics.php`, được `orders.php` dùng cho Dashboard và Báo cáo, `cas_profit_page()` dùng cho Lợi nhuận gộp):
```sql
SELECT COALESCE(SUM(GREATEST(o.total_amount-COALESCE(r.refunded,0),0)),0)
FROM orders o
LEFT JOIN (SELECT order_id,SUM(amount) refunded FROM refunds WHERE status='recorded' GROUP BY order_id) r
  ON r.order_id=o.order_id
WHERE o.order_status='completed' AND o.payment_status IN ('paid','partially_refunded','refunded')
```
Báo cáo doanh thu và Lợi nhuận gộp trước đó đã đúng công thức này nhưng viết lặp lại; nay ghép từ cùng 3 hàm `cas_revenue_eligible_sql()`, `cas_revenue_refund_join()`, `cas_revenue_net_sql()` để không lệch lại về sau.

### Đối chiếu số liệu SQL ↔ UI (DB sạch, lần chạy `e0c4d2`)

Truy vấn tham chiếu trong test viết **độc lập** với code PHP (subquery tương quan thay vì LEFT JOIN tổng hợp).

| Đơn | Tình huống | Trạng thái / thanh toán | Tổng đơn | Số bản ghi hoàn đã ghi nhận | Đã hoàn | Doanh thu thuần | Giá vốn snapshot |
|---|---|---|---:|---:|---:|---:|---:|
| #6 | Đã thanh toán | completed / paid | 290.000 | 0 | 0 | **290.000** | 20.000 |
| #7 | Hoàn một phần (2 lần: 50.000 + 30.000) | completed / partially_refunded | 290.000 | 2 | 80.000 | **210.000** | 20.000 |
| #8 | Hoàn toàn bộ (2 lần: 100.000 + 190.000) | completed / refunded | 290.000 | 2 | 290.000 | **0** | 20.000 |
| #9 | Hoàn tất nhưng chưa thu | completed / unpaid | 290.000 | 0 | 0 | **0** (loại) | 20.000 |
| #10 | Đã hủy (đã thu) | cancelled / paid | 290.000 | 0 | 0 | **0** (loại) | — |
| #11 | Thiếu snapshot giá vốn (mô phỏng đơn cũ) | completed / paid | 290.000 | 0 | 0 | 290.000 | NULL |

Toàn DB: công thức cũ của Dashboard = **1.190.000 ₫**; công thức thống nhất = **1.400.000 ₫**; chênh 210.000 ₫ = đúng doanh thu còn lại của đơn #7. Sau sửa, test đọc HTML đã render và khẳng định: Dashboard = Báo cáo (`start=2000-01-01`) = SQL tham chiếu; trên `loi-nhuan.php` các ô Doanh thu thuần, Doanh thu có đủ giá vốn, Giá vốn nguyên liệu, Lợi nhuận gộp, Đơn chưa đủ giá vốn, Số đơn đều bằng SQL tham chiếu cho cùng khoảng ngày.

Bẫy JOIN được chứng minh bằng test: `SUM(o.total_amount)` khi JOIN thẳng `refunds` (2 dòng/đơn) cho #7 + #8 ra 1.160.000 (gấp đôi), còn subquery gộp theo đơn ra đúng 580.000.

## 3. ADM-BE-02 — Luồng đơn, giao hàng, kho

Đã rà `cas_admin_handle_order_post`, `cas_delivery_update`, `cas_order_consume_inventory`, `cas_order_restore_inventory`, `cas_inventory_move`:

- Đơn: khóa `orders ... FOR UPDATE` trước mọi thay đổi; bảng chuyển trạng thái chặn nhảy cóc (`confirmed → completed` = 422); `completed`/`cancelled` là trạng thái cuối; hủy khi `preparing/ready` cần lý do; gửi lại cùng trạng thái không trừ/hoàn kho lần hai.
- Kho: trừ theo `product_recipes` khi vào `preparing`, cộng dồn theo nguyên liệu, khóa `inventory_items` theo thứ tự `item_id` tăng dần (tránh deadlock), tính theo đơn vị 1/100 để không lệch số thực; thiếu công thức hoặc thiếu tồn → DomainException → rollback cả trạng thái đơn và tồn. Hoàn kho theo snapshot `order_inventory_usage` (không theo công thức mới), đánh dấu `restored_at` nên không hoàn hai lần.
- Giá vốn: `unit_cost_snapshot`/`cost_amount_snapshot` chỉ được INSERT lúc xuất kho, không có câu UPDATE nào ghi đè (grep toàn repo) → bất biến khi đổi giá nhập (có test).
- Lỗi duy nhất tìm thấy là BE-02-A (mục 1). Sửa trong `orders.php`: khi `delivering → ready`, trong cùng transaction khóa `deliveries ... FOR UPDATE`, đặt `not_delivered`, ghi lý do và một dòng `delivery_events` (actor = người thao tác). Giống hệt kết quả của thao tác "Giao không thành công" ở màn Giao hàng.

## 4. ADM-BE-03 — Hoàn tiền, phân quyền, audit

Không phát hiện lỗi; có test chứng minh: hoàn từng phần nhiều lần → `partially_refunded`, đủ → `refunded`; chặn hoàn vượt (tính cả yêu cầu đang mở); đơn đã hoàn đủ / chưa thanh toán không tạo yêu cầu mới; admin không đặt `refunded` trực tiếp ở trang Đơn hàng; nhân viên không đổi thanh toán và không tự duyệt hoàn (403 + audit `denied`); thiếu CSRF → 403 và không tạo bản ghi; audit ghi mỗi lần `refund_recorded` và không chứa `password`, hash bcrypt/argon2, `csrf` hay `request_token`. Không có tích hợp chuyển tiền tự động.

## 5. ADM-BE-04 — Kết quả kiểm thử

### Bộ mới `tests/admin/test_admin_backend_final.py` (29 assertion, độc lập, đếm riêng)

| # | Ca kiểm thử | Expected | Trước sửa | Sau sửa |
|---|---|---|---|---|
| 1 | Hoàn nhiều lần cập nhật đúng trạng thái thanh toán | partially_refunded / refunded | PASS | PASS |
| 2 | SQL đối chiếu 4 tình huống doanh thu | 290k / 210k / 0 / 0 | PASS | PASS |
| 3 | JOIN thẳng refunds nhân đôi; subquery gộp không nhân đôi | 1.160k vs 580k | PASS | PASS |
| 4 | Dashboard = SQL tham chiếu | bằng nhau | **FAIL** (1.450k vs 1.870k) | PASS |
| 5 | Báo cáo (toàn kỳ) = Dashboard = SQL, cùng nhãn | bằng nhau | **FAIL** | PASS |
| 6 | Lợi nhuận gộp: 5 ô số liệu = SQL | bằng nhau | PASS | PASS |
| 7 | Ô "Số đơn hoàn tất / đã thanh toán" = SQL | 10 | **FAIL** (0) | PASS |
| 8 | Đơn thiếu snapshot không vào doanh thu có giá vốn; ghi rõ "không phải lợi nhuận ròng" | đúng | PASS | PASS |
| 9 | Yêu cầu hoàn chờ duyệt không giảm doanh thu | không đổi | PASS | PASS |
| 10 | Hoàn vượt khi có yêu cầu đang mở | 422 | PASS | PASS |
| 11 | Từ chối yêu cầu không đổi doanh thu | không đổi | PASS | PASS |
| 12 | Đơn đã hoàn đủ không tạo yêu cầu mới | 422 | PASS | PASS |
| 13 | Đơn hoàn một phần: chặn vượt phần còn lại | 422 | PASS | PASS |
| 14 | Đơn chưa thanh toán không được hoàn | 422 | PASS | PASS |
| 15 | Admin đặt `refunded` ở trang Đơn hàng | 422 | PASS | PASS |
| 16 | Nhân viên đổi thanh toán | 422 | PASS | PASS |
| 17 | POST hoàn tiền thiếu CSRF | 403, không ghi | PASS | PASS |
| 18 | Nhân viên tự duyệt hoàn | 403 + audit denied | PASS | PASS |
| 19 | Audit hoàn tiền không lộ mật khẩu/hash/token | 0 dòng lộ | PASS | PASS |
| 20 | Nhảy `confirmed → completed` | 422 | PASS | PASS |
| 21 | Hủy đơn `ready` không lý do | 422, không hoàn kho | PASS | PASS |
| 22 | Hủy đơn đã hoàn tất | 422, không hoàn kho | PASS | PASS |
| 23 | Hủy trực tiếp đơn đang giao | 422 | PASS | PASS |
| 24 | `delivering → ready` đồng bộ lịch giao + lịch sử | ready/not_delivered | **FAIL** (ready/out_for_delivery) | PASS |
| 25 | Sau khi quay lại: xuất giao lại và hoàn tất | 303, 303, completed | **FAIL** (422, 422) | PASS |
| 26 | Đổi giá nhập không đổi snapshot giá vốn | 10.000 / 20.000 | PASS | PASS |
| 27 | Sổ kho liên tục, tổng biến động = tồn, không âm | đúng | PASS | PASS |
| 28 | Xuất kho theo đơn = usage chưa hoàn | bằng nhau | PASS | PASS |
| 29 | 2 đơn chuẩn bị đồng thời tranh tồn cho 1 đơn | [303, 422], tồn 0 | PASS | PASS |

Tổng: **trước sửa 24/29** (5 FAIL ứng với 3 lỗi), **sau sửa 29/29** — chạy 2 lần: trên DB đã có dữ liệu và trên DB tạo mới.

### Bộ cũ (không sửa, không bỏ qua)

| Bộ | Trước sửa | Sau sửa |
|---|---|---|
| `scripts/verify-r2.sh` | PASS | PASS |
| `scripts/verify-r2-auth-admin.sh` | NOT_RUN ở baseline | PASS |
| `tests/admin/test_admin_ops.py` (qua `verify-admin-ops.sh`) | 248/248 | **248/248** |

### Kiểm tra khác

- `php -l` mọi file PHP thay đổi: không lỗi. `python -m py_compile` 2 file test: OK. `git diff --check`: sạch.
- Log `php -S` của bộ mới: 0 Warning/Notice sau sửa (trước sửa có Warning analytics.php:59).
- UI/CSS/URL: không đổi file CSS, layout, route; chỉ đổi **chữ nhãn** số liệu doanh thu ("Doanh thu hoàn tất" / "Doanh thu đã hoàn tất và thanh toán" / "Doanh thu hợp lệ" / "Doanh thu thuần toàn bộ" → "Doanh thu thuần (sau hoàn)") theo yêu cầu đặt nhãn rõ ràng.
- Bộ mới tự từ chối chạy nếu `DB_PORT=3306` (trả mã 2, in `NOT_RUN`), trừ khi đặt `CAS_ALLOW_PORT_3306=1` (dành cho CI có DB dùng một lần).

### Cách chạy lại

Sau khi DB thử đã có schema + seed + migrations (vd chạy `scripts/verify-r2.sh` với `DB_PORT` trỏ tới DB thử):
```bash
export DB_HOST=127.0.0.1 DB_PORT=3407 DB_NAME=cas_hoa DB_USER=root DB_PASSWORD=
php -S 127.0.0.1:8036 &
CAS_BASE_URL=http://127.0.0.1:8036/ python3 tests/admin/test_admin_backend_final.py
```

## 6. Rủi ro, giới hạn và đề xuất cho trưởng nhóm

1. **Chưa gắn bộ mới vào CI.** `scripts/verify-admin-ops.sh` và `.github/workflows/` nằm ngoài phạm vi được sửa. Đề xuất thêm một bước sau "Admin Ops" trong workflow (DB MySQL của CI là DB dùng một lần): khởi động `PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:8036 &` rồi chạy `CAS_ALLOW_PORT_3306=1 CAS_BASE_URL=http://127.0.0.1:8036/ python3 tests/admin/test_admin_backend_final.py`.
2. **Đồng thời thật:** trên Windows, `php -S` xử lý tuần tự nên ca #29 chứng minh tính đúng của khóa/rollback chứ chưa chứng minh tranh chấp song song thật. Trên Linux với `PHP_CLI_SERVER_WORKERS=4` (như CI đang dùng cho `verify-admin-ops.sh`) ca này chạy song song thật.
3. Ca #8/#11 mô phỏng "đơn cũ thiếu snapshot" bằng `UPDATE order_inventory_usage SET cost_amount_snapshot=NULL` trên DB thử (dữ liệu trước migration 005 không thể tạo qua UI hiện tại).
4. Ngoài phạm vi, chưa sửa: khi chạy bộ cũ, log PHP có `Cannot modify header information` ở `includes/customer.php:8-9` (output bắt đầu từ `index.php:55`) — thuộc khu vực Customer, chuyển cho người phụ trách.
5. Dashboard dùng kỳ "toàn thời gian", còn Báo cáo mặc định "đầu tháng → hôm nay". Hai số chỉ bằng nhau khi Báo cáo chọn khoảng bao trùm mọi đơn; nếu nhóm muốn Dashboard hiển thị theo tháng thì chỉ cần truyền `$from/$to` vào `cas_revenue_net_total()`.

## 7. File thay đổi

- `admin/modules/analytics.php` — định nghĩa doanh thu dùng chung, sửa khóa `order_count`, nhãn thống nhất.
- `admin/modules/orders.php` — Dashboard và Báo cáo dùng định nghĩa chung; đồng bộ lịch giao khi `delivering → ready`.
- `tests/admin/test_admin_backend_final.py` — bộ hồi quy mới (29 assertion).
- `docs/TV3_ADMIN_BACKEND_TEST_REPORT.md` — báo cáo này.
