# Báo cáo kiểm thử Customer

Ngày bắt đầu: 14/10/2026. Nhánh feature/customer-completion.

PHP 8.2.12 của XAMPP; MariaDB cổng 3307; database cas_hoa_customer_preview riêng; PHP development server tại http://127.0.0.1:8033; SMTP local 1025; Chrome do Playwright điều khiển. Dữ liệu test là giả và chỉ dùng nội bộ.

DONE: 0; LOCAL_PASS: 5; CODE_EXISTS: 0; NOT_DONE: 0; BLOCKED: 2. Tổng trọng số các task đạt local: 19,5/27,5 điểm. Không cộng 5 điểm CUS-02 hoặc 3 điểm CUS-07 khi điều kiện còn thiếu. HTTP/SQL 35/35, browser 103/103, PHP lint 50/50, JavaScript syntax 8/8, SQL tổng hợp 9/9, bổ sung 10 nhóm đều đạt.

## Cách chạy lại

Chạy tuần tự; không chạy HTTP/browser/SQL có ghi dữ liệu đồng thời. Xem docs/customer-preview.md để đặt CAS_APP_URL, DB_PORT, DB_NAME, PHP_BIN và PLAYWRIGHT_MODULE.

```text
node tests/customer/http.cjs
node tests/customer/browser.cjs
node tests/customer/completion.cjs CUS-02
node tests/customer/completion.cjs CUS-03
node tests/customer/completion.cjs CUS-04
node tests/customer/completion.cjs CUS-05
node tests/customer/completion.cjs CUS-06
php tests/customer/sql-audit.php
```

Lưu ý: chỉ database tên cas_hoa_customer_preview hoặc cas_hoa_customer_test_* được helper SQL chấp nhận. Không chạy trên dữ liệu thật. Mật khẩu sinh ngẫu nhiên không ghi ra file.

## CUS-01 Audit 18 trang khách hàng

Trạng thái: LOCAL_PASS. Đạt smoke và audit local; Apache URL yêu cầu chưa đạt.

Audit đủ 18 trang, 90 lượt trang/viewport; ảnh thật ở 375, 768, 1440. Không thấy PHP warning, lỗi JS chưa bắt, ảnh hỏng hay link .html trong phạm vi kiểm thử.

| Kiểm tra | Expected | Actual |
|---|---|---|
| 18 trang được quyền | HTTP 200, không đổi sang trang đăng nhập ngoài ý muốn | 18/18 đạt; slug sản phẩm sai trả 404 |
| 5 kích thước | 375/390/768/1024/1440 không tràn ngang, có nhãn và ảnh tải | 90/90 lượt đạt trong bộ browser cuối |

Dùng kiểm tra dữ liệu nền, sản phẩm active và bài công khai; form liên hệ và tài khoản được đối chiếu bằng các test HTTP/SQL.

Tệp: `docs/customer-audit.md`, `PROGRESS.md`, `docs/evidence/CUS-01/`.

Ảnh: `evidence/CUS-01/report-home.png`; ảnh khác ở `evidence/CUS-01/`.

## CUS-02 Catalog và chi tiết sản phẩm

Trạng thái: BLOCKED. Catalog hiện có đạt local; chưa nghiệm thu đủ gallery/tồn kho thành phẩm vì cần lead chốt schema/contract.

Tái sử dụng catalog DB và giấy gói có sẵn. Sửa nhãn Mới thêm; thêm lỗi giá inline và aria-invalid. Đối chiếu 6 bộ lọc riêng với tập ID SQL, kiểm tra tổ hợp, giá mới và hidden.

| Kiểm tra | Expected | Actual |
|---|---|---|
| Bộ lọc riêng và kết hợp | Tập sản phẩm đúng như SQL, URL giữ được | 6 bộ lọc riêng khớp SQL; bộ lọc kết hợp và tìm không dấu đạt |
| Đổi giá và ẩn | Giá mới hiện trên detail; hidden không có trong catalog, slug 404 | Giá thử 456000 đã hiện; hidden bị loại; khôi phục giá/trạng thái cũ |
| Khoảng giá đảo | Hiện lỗi rõ và đặt lại xóa lỗi | Lỗi inline/aria-invalid đạt; nhãn sắp xếp Mới thêm đúng dữ liệu |
| Từng bộ lọc khớp SQL | Mỗi bộ lọc loại, tone, dịp, giá và tìm kiếm trả đúng danh sách ID trong DB | 6 bộ lọc riêng lẻ khớp tập ID SQL |
| Giá và trạng thái sản phẩm cập nhật từ DB | Đổi giá phản ánh ở catalog/detail; hidden không xuất hiện và detail trả 404 | Giá 456000 đã hiển thị; hidden bị loại và trả 404; khôi phục dữ liệu thử |
| Khoảng giá đảo hiển thị lỗi có liên kết | Lỗi inline và aria-invalid khi min > max; đặt lại xóa lỗi | Thông báo xuất hiện, aria-invalid=true và đặt lại xóa lỗi |

SELECT sản phẩm theo type/category/tone/occasion/price/slug. UPDATE tạm giá/status trong DB thử, khôi phục trong finally. Chưa kiểm thử thao tác bấm nút Admin; đã xác minh tác động dữ liệu Admin sử dụng.

Tệp: `san-pham.php`, `assets/js/customer-catalog.js`, `tests/customer/completion.cjs`, `docs/de-xuat-cho-lead.md`.

Ảnh: `evidence/CUS-02/report-catalog-invalid-price.png`; ảnh khác ở `evidence/CUS-02/`.

## CUS-03 Giỏ hàng và trải nghiệm checkout

Trạng thái: LOCAL_PASS. Đạt trên bản local đang có. Cơ chế idempotency và snapshot trong checkout là diff từ trước phiên, cần lead review riêng.

Giữ xử lý Session/checkout có sẵn, bổ sung aria-busy khi lưu và xóa trạng thái bận khi lỗi. Kiểm thử giỏ/checkout với dữ liệu thật trong DB thử.

| Kiểm tra | Expected | Actual |
|---|---|---|
| Giỏ và lỗi mạng | Refresh giữ dữ liệu, cập nhật/xóa đúng, lỗi không giả thành công | Giỏ 1 dòng số lượng 3 sau refresh; retry và mất feed đạt |
| Giá và đơn trùng | Không tin giá gửi từ trình duyệt; hai request cùng key chỉ 1 đơn | 2 mẫu p001 có tổng 800000; hai response cùng mã và DB chỉ thêm 1 đơn |
| Ship và pickup | Địa chỉ chỉ bắt buộc khi giao; ngày/giờ/phone được kiểm tra | Giao hàng và pickup đều lưu; giá trị sai bị 422; lỗi 503 inline và có thể thử lại |
| Giỏ xóa và refresh giữ đúng Session | Thêm hai sản phẩm, sửa số lượng, xóa một rồi refresh vẫn đúng | 1 dòng, số lượng 3 sau refresh |
| Pickup và lỗi lưu đơn hiển thị tại form | Pickup bỏ yêu cầu địa chỉ; ngày không hợp lệ tắt giờ; HTTP 503 không chuyển trang và cho thử lại | Giờ disabled/enabled đúng; lỗi 503 inline, nút được bật lại; thử lại thành công và mã đơn tồn tại trong SQL |

SELECT orders/order_items theo mã thật, kiểm số đơn tăng đúng 1; kiểm tổng tiền, số dòng, phương thức giao nhận. Không sửa file actions/checkout.php trong phiên.

Tệp: `assets/js/main-r2.js`, `tests/customer/completion.cjs`, `docs/evidence/CUS-03/`.

Ảnh: `evidence/CUS-03/report-checkout-retry.png`; ảnh khác ở `evidence/CUS-03/`.

## CUS-04 Tài khoản hồ sơ và khôi phục

Trạng thái: LOCAL_PASS. Đạt SMTP local, token một lần/hết hạn và cách ly tài khoản. Chưa kiểm chứng thư gửi tới nhà cung cấp Internet.

Đăng ký giữ tên/email/phone khi server từ chối và vẫn để trống mật khẩu. Bổ sung ràng buộc UI; kiểm tra reset qua email SMTP local và trình duyệt.

| Kiểm tra | Expected | Actual |
|---|---|---|
| Validation và tài khoản | Dữ liệu sai không tạo user; email trùng báo rõ; profile lưu DB | 4 loại input sai bị từ chối; số user không đổi; đăng ký/login/logout/profile đạt |
| Lịch sử và lookup | A không xem được đơn B; phân trang; phone sai không lộ đơn | 10 đơn/trang; trang 2 có đơn cũ; A/B cách ly; lookup phone sai 404 |
| Reset qua email | SMTP nhận thư, token không hiện URL/UI, dùng một lần và hết hạn | Thư local nhận thật; reset qua browser đạt; replay/hết hạn bị từ chối; SQL consumed_at có giá trị |
| Đăng ký từ chối dữ liệu sai ở server | Email/phone/password/xác nhận sai không tạo user | 4 trường hợp sai bị từ chối; số user không đổi |
| Sai mật khẩu không tạo Session đăng nhập | Thông báo lỗi rõ và trang tài khoản vẫn chuyển về login | Lỗi hiện tại form; tài khoản HTTP 303 về đăng nhập |
| Khôi phục bằng link email trong trình duyệt | Nhận SMTP local, URL fragment bị xóa; reset thành công; lần dùng lại bị từ chối | Thư nhận qua SMTP, token không hiện trên màn hình/URL sau tải; reset và từ chối dùng lại đạt; SQL consumed_at đã có |

SELECT users/customers sau profile, JOIN orders theo user để kiểm liên kết; password_resets chỉ kiểm số bản ghi/consumed_at. Không ghi mật khẩu hoặc token vào tài liệu.

Tệp: `dang-ky.php`, `tests/customer/completion.cjs`, `docs/evidence/CUS-04/`.

Ảnh: `evidence/CUS-04/report-reset-success.png`; ảnh khác ở `evidence/CUS-04/`.

## CUS-05 Responsive và khả năng truy cập

Trạng thái: LOCAL_PASS. Đạt các kiểm tra cụ thể đã chạy, không tuyên bố chứng nhận WCAG toàn site hoặc kiểm thử screen reader toàn diện.

Giữ bố cục hiện có, chỉnh token màu riêng cho Customer. Kiểm bàn phím mở bộ lọc, focus visible, ảnh và tràn ngang.

| Kiểm tra | Expected | Actual |
|---|---|---|
| Tương phản nút | Tỷ lệ chữ nút >=4,5:1 | Trước sửa 3,94:1; sau sửa 6,32:1 |
| Responsive và bàn phím | Không overflow, label/ảnh hợp lệ, Enter và Tab có focus | 90/90 lượt trang/viewport; luồng mua mobile; dữ liệu tên dài và HTML escape đạt |
| Tương phản nút và bàn phím bộ lọc | Chữ nút chính đạt tỷ lệ >=4.5; mở lọc bằng Enter và có focus visible | Tỷ lệ nút 6.32:1; Enter/focus đạt |

Không thêm nghiệp vụ SQL. Test dữ liệu dài thay tạm tên/mô tả sản phẩm trong DB thử, rồi khôi phục nguyên giá trị.

Tệp: `assets/css/customer.css`, `tests/customer/browser.cjs`, `tests/customer/completion.cjs`, `docs/evidence/CUS-05/`.

Ảnh: `evidence/CUS-05/report-login-contrast.png`; ảnh khác ở `evidence/CUS-05/`.

## CUS-06 Gợi ý hoa theo dịp và ngân sách

Trạng thái: LOCAL_PASS. Đạt tổ hợp mẫu, URL reload, hidden và empty state trên mobile; không dùng AI API hoặc sinh sản phẩm giả.

Giữ biểu mẫu và taxonomy hiện có; thêm kiểm tra ngân sách đảo ngay tại form, lỗi inline và aria-invalid.

| Kiểm tra | Expected | Actual |
|---|---|---|
| Kết quả và URL | Chọn Sinh nhật/Hồng/bó hoa/giá tối đa 400000 trả đúng DB | Bộ browser trả Garden; URL tải lại vẫn 1 kết quả |
| Giá đảo và không kết quả | Không gửi khoảng đảo; có cách đặt lại khi không có mẫu | Form chặn khoảng đảo; empty state/URL giữ đúng; sản phẩm hidden không xuất hiện |
| Ngân sách gợi ý hợp lệ và chia sẻ URL không lộ hidden | Khoảng giá đảo bị chặn; tổ hợp không có sản phẩm có cách nới điều kiện; sản phẩm hidden không xuất hiện | Chặn khoảng giá đảo; empty state/URL giữ đúng; hidden không xuất hiện |

Đọc taxonomy từ DB; thử ẩn sản phẩm trong database riêng rồi khôi phục. Chưa thay schema hoặc quy tắc tồn kho.

Tệp: `includes/customer-recommendations.php`, `assets/js/customer-forms.js`, `tests/customer/completion.cjs`, `docs/evidence/CUS-06/`.

Ảnh: `evidence/CUS-06/report-recommendation-empty.png`; ảnh khác ở `evidence/CUS-06/`.

## CUS-07 Kiểm thử tài liệu và bàn giao

Trạng thái: BLOCKED. Test/tài liệu local đã chuẩn bị; không có commit, push, PR, review hoặc CI của nhánh customer vì chưa được người dùng ra lệnh.

Bổ sung test theo task, SQL tổng hợp, ảnh 18 trang, audit, báo cáo Word, mô tả PR và commit message. Mật khẩu kiểm thử sinh ngẫu nhiên trong bộ nhớ.

| Kiểm tra | Expected | Actual |
|---|---|---|
| Hồi quy và lint | Các test/lint thực chạy đạt, báo rõ lần thử thất bại | Kết quả cuối lấy từ JSON, kiểm SHA256 3 file core/schema không đổi |
| Bàn giao Git | Chỉ commit/push/PR sau lệnh đúng thao tác | Chưa commit - chờ duyệt; chưa push; chưa mở PR; chưa có CI mới |

9 truy vấn tổng hợp chỉ trả số lượng và sai lệch, không xuất dữ liệu cá nhân. Các đơn TEST-* dùng cho phân trang được loại khỏi kiểm tra tổng tiền đơn CSH-*.

Tệp: `tests/customer/http.cjs`, `tests/customer/browser.cjs`, `tests/customer/completion.cjs`, `tests/customer/sql-audit.php`, `scripts/build-customer-report.py`, `docs/customer-test-report.md`, `docs/pr-description.md`, `docs/BAO_CAO_THANH_VIEN_2_CUSTOMER.docx`.

Ảnh: `evidence/CUS-07/report-order-success.png`; ảnh khác ở `evidence/CUS-07/`.

## SQL kiểm tra và kết quả

Không tính đơn TEST-* dùng riêng cho test phân trang vào kiểm tra tổng tiền đơn CSH-*.

### Sản phẩm active

```sql
SELECT COUNT(*) AS n FROM products WHERE status='active';
```
Expected: 11. Actual: 11. Kết quả: PASS.

### Bài viết công khai

```sql
SELECT COUNT(*) AS n FROM news WHERE status='published';
```
Expected: 6. Actual: 6. Kết quả: PASS.

### Tổng tiền đơn khớp các dòng

```sql
SELECT COUNT(*) AS n FROM orders o WHERE o.order_code LIKE 'CSH-%' AND (o.subtotal <> COALESCE((SELECT SUM(i.line_total) FROM order_items i WHERE i.order_id=o.order_id),0) OR o.total_amount <> o.subtotal + o.shipping_fee);
```
Expected: 0. Actual: 0. Kết quả: PASS.

### Thành tiền dòng khớp giá và số lượng

```sql
SELECT COUNT(*) AS n FROM order_items WHERE line_total <> quantity * unit_price;
```
Expected: 0. Actual: 0. Kết quả: PASS.

### Khách hàng tài khoản không mồ côi

```sql
SELECT COUNT(*) AS n FROM customers c LEFT JOIN users u ON u.user_id=c.user_id WHERE c.user_id IS NOT NULL AND u.user_id IS NULL;
```
Expected: 0. Actual: 0. Kết quả: PASS.

### Token đã dùng có bản ghi

```sql
SELECT COUNT(*) AS n FROM password_resets WHERE consumed_at IS NOT NULL;
```
Expected: >= 1. Actual: 6. Kết quả: PASS.

### Liên hệ đã được lưu

```sql
SELECT COUNT(*) AS n FROM contacts;
```
Expected: >= 1. Actual: 3. Kết quả: PASS.

### Đơn giao hàng đã được lưu

```sql
SELECT COUNT(*) AS n FROM orders WHERE order_code LIKE 'CSH-%' AND delivery_method='delivery';
```
Expected: >= 1. Actual: 9. Kết quả: PASS.

### Đơn pickup đã được lưu

```sql
SELECT COUNT(*) AS n FROM orders WHERE order_code LIKE 'CSH-%' AND delivery_method='pickup';
```
Expected: >= 1. Actual: 4. Kết quả: PASS.

## Checklist mục 6

- [x] Mỗi 18 PHP page render HTTP 200 khi được quyền; slug sai/đăng nhập sai xử lý hợp lệ. Bằng chứng: HTTP 18 trang; sản phẩm 404; CUS-04 login sai; browser 90 lượt.
- [x] Filter theo tone, dịp, giá, loại, search nhiều điều kiện chính xác. Bằng chứng: CUS-02/results.json: 6 bộ lọc khớp SQL; browser tổ hợp nhiều điều kiện.
- [x] Guest add/update/delete và refresh giỏ; API Session trả giá trị đồng bộ. Bằng chứng: HTTP giỏ; browser queued plus/minus; CUS-03 giỏ sau refresh.
- [x] Checkout giao hàng và pickup; validation address/date/time/phone; DB tạo đúng orders, order_items. Bằng chứng: HTTP checkout; browser mobile delivery; sql-results.json tổng tiền và giao nhận.
- [x] Xử lý lỗi mạng khi sync giỏ; không hiển thị thành công giả. Bằng chứng: Browser abort actions/cart.php, count chưa tăng và có retry.
- [x] Đặt hàng liên tiếp/double click không gây duplicate không mong muốn. Bằng chứng: HTTP hai request cùng key; browser requestSubmit hai lần chỉ thêm 1 đơn.
- [x] Đăng ký email trùng lỗi rõ; đăng nhập/đăng xuất hợp lệ. Bằng chứng: HTTP duplicate; browser signup/profile/logout/login.
- [x] Customer A chỉ xem được lịch sử A; customer B không truy cập dữ liệu A. Bằng chứng: HTTP isolation; success page không lộ đơn A cho B; thử user_id giả.
- [x] Lookup mã+phone sai bị từ chối mà không leak order. Bằng chứng: HTTP 404 khi phone sai; payload không có phone/address; browser lookup.
- [x] Quên mật khẩu: gửi được email kiểm thử và token có hạn dùng 1 lần. Bằng chứng: SMTP local thực; HTTP token hết hạn/replay; CUS-04 reset qua browser.
- [x] Mobile 375px, tablet 768px, desktop 1440px; không overflow. Bằng chứng: 18 trang × 5 viewport; ảnh thật ba kích thước trong CUS-01 và từng task.
- [ ] Có test report + ảnh chụp thật + link PR cho mỗi nhóm task. Bằng chứng: Có test report và ảnh. Link PR chưa có vì người dùng chưa cho phép mở PR.

## Lỗi gặp trong quá trình và xử lý

- Test reset qua fragment gặp response null khi cùng trang: sửa helper kiểm tra tải lại trang, reset thực qua browser đã đạt.
- Khi thay mật khẩu cố định bằng random, khai báo crypto trùng gây lỗi test HTTP: bỏ khai báo lặp, chạy lại đạt.
- Một lượt tài khoản 768px hết thời gian 7 giây khi chờ networkidle: tăng timeout điều hướng lên 30 giây, giữ nguyên assertion và chạy lại. Kết quả lần thất bại vẫn lưu tại CUS-07/browser-attempt-before-timeout-fix.json.
- Tương phản nút 3,94:1 trước sửa; chỉnh CSS riêng Customer và đo lại 6,32:1.
- Lệnh Git chỉ đọc từng bị hệ thống duyệt tự động chặn do giới hạn sử dụng; sau thời điểm giới hạn đã kiểm tra lại thành công, không đổi git config.

## Giới hạn và đề xuất

- URL Apache http://localhost/Lap_trinh_web/index.php không mở được khi kiểm tra; thư mục triển khai htdocs chưa tồn tại. Môi trường preview nằm trong repo, không sửa cấu hình hệ thống. Kết quả local không chứng minh Apache/phpMyAdmin của nhóm đã nghiệm thu.
- CUS-02 chưa có gallery nhiều ảnh hoặc tồn kho thành phẩm. Schema chỉ có products.image, chưa có bảng ảnh/quan hệ định mức để suy ra hết hàng. Đã đề xuất lead; không tự đổi schema, role, luật giá hoặc trạng thái đơn.
- actions/checkout.php và includes/auth.php đã có diff trước phiên. Test dựa trên bản đó; SHA256 trước/sau bằng nhau. Cần lead review riêng idempotency, snapshot đơn/giấy gói và giới hạn password trước tích hợp.
- SMTP local chứng minh gửi/nhận thư thử trong máy; chưa kiểm chứng dịch vụ mail Internet. Không xuất thư, token, mật khẩu hay .env trong báo cáo.
- Ảnh là nội dung trình duyệt thật, không có thanh địa chỉ Chrome và không thay thế bộ PDF có thanh địa chỉ nếu giảng viên yêu cầu. Không dựng thanh địa chỉ giả.
- Tương phản được đo cụ thể cho nút chính, focus/label/overflow được kiểm tự động và ảnh được xem; chưa audit WCAG toàn diện hoặc mọi công nghệ hỗ trợ.
- Chưa commit, push, mở PR hoặc chạy CI nhánh mới. Điểm là tự đánh giá local theo trọng số task, không phải điểm giảng viên và không tương đương tỷ lệ GitHub Contributors.
