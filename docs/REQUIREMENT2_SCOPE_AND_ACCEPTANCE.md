# CÁ'S HOA – Chuẩn hóa phạm vi Yêu cầu 2 (đồ án TDTU)

## 1. Cơ sở đánh giá

- Yêu cầu giảng viên: sau khi đã có giao diện tĩnh và database, viết PHP hoàn thành **tối thiểu 70% FrontEnd**; sản phẩm nộp là PDF ảnh chụp màn hình các trang PHP **có thanh địa chỉ Web**.
- Phạm vi nhóm tự chọn để đảm bảo đầy đủ đồ án: tính **cả giao diện khách hàng (18 trang) và giao diện quản trị (22 trang)** đã có trong bản yêu cầu 1.
- Tổng: **40 trang**. Mốc tham khảo theo số trang: 28/40 = 70%, 30/40 = 75%; mục tiêu cuối: 40/40. Đây là KPI quản trị tiến độ nội bộ, không khẳng định là thang chấm của giảng viên.
- **KHÔNG tính một trang là hoàn thành chỉ vì đổi .html -> .php hoặc có ảnh chụp.** Mọi tương tác đã thiết kế phải có hành vi đúng và lưu/đọc dữ liệu server nếu nghiệp vụ đòi hỏi.

## 2. Kiểm kê trang khách hàng – 18

| # | Trang gốc | Điều kiện nghiệm thu chính |
|---|---|---|
| 1 | index.html | PHP trang chủ, lấy sản phẩm thật từ MySQL |
| 2 | san-pham.html | Danh sách, tìm kiếm, lọc/sắp xếp chính xác trên dữ liệu DB |
| 3 | chi-tiet-san-pham.html | Chi tiết theo slug/ID thật, xử lý 404, thêm giỏ |
| 4 | gio-hang.html | Thêm/xóa/sửa số lượng, tính và lưu trạng thái giỏ nhất quán |
| 5 | thanh-toan.html | PHP validation, kiểm giá/đơn giá từ DB, transaction lưu đơn |
| 6 | dat-hang-thanh-cong.html | Lấy mã và nội dung đơn thật từ server, hạn chế lộ dữ liệu |
| 7 | dang-nhap.html | Session PHP, kiểm mật khẩu hash, logout, rate-limit |
| 8 | dang-ky.html | Tạo user thật trong MySQL, email/số điện thoại hợp lệ |
| 9 | quen-mat-khau.html | Quy trình token đặt lại mật khẩu đúng chuẩn, không dùng OTP giả |
|10 | tai-khoan.html | Hồ sơ cá nhân được xác thực/ủy quyền, cập nhật MySQL |
|11 | lich-su-don-hang.html | Chỉ hiển thị đơn của người đăng nhập |
|12 | tra-cuu-don-hang.html | Tra cứu đơn MySQL bằng mã và thông tin xác minh |
|13 | tin-tuc.html | Danh sách bài viết lấy từ MySQL |
|14 | chi-tiet-tin-tuc.html | Hiển thị tin theo slug, 404 nếu không có |
|15 | lien-he.html | Kiểm tra và lưu liên hệ MySQL, chống CSRF |
|16 | gioi-thieu.html | Trang nội dung PHP hoạt động, điều hướng đúng |
|17 | chinh-sach-giao-hang.html | Trang nội dung PHP hoạt động, điều hướng đúng |
|18 | chinh-sach-doi-tra.html | Trang nội dung PHP hoạt động, điều hướng đúng |

## 3. Kiểm kê trang quản trị – 22

| Nhóm | Trang gốc | Yêu cầu tối thiểu để nghiệm thu |
|---|---|---|
| Tổng quan | admin/index.html | KPI, đơn gần đây, tồn kho từ dữ liệu MySQL |
| Vận hành | admin/don-hang.html | Danh sách, lọc, cập nhật trạng thái theo quyền |
| Vận hành | admin/hoan-tien.html | Yêu cầu hoàn tiền; trạng thái, lịch sử cập nhật |
| Vận hành | admin/giao-hang.html | Danh sách giao, cập nhật trạng thái |
| Vận hành | admin/khach-hang.html | Danh sách, tìm kiếm, hồ sơ khách |
| Vận hành | admin/bao-cao.html | Báo cáo doanh thu bằng SQL theo khoảng thời gian |
| Sản phẩm | admin/san-pham.html | Danh sách/hiển thị, tìm kiếm, CRUD sản phẩm |
| Sản phẩm | admin/them-san-pham.html | Thêm sản phẩm với validate, ảnh, phân loại |
| Sản phẩm | admin/sua-san-pham.html | Cập nhật dữ liệu sản phẩm, xử lý ID sai |
| Sản phẩm | admin/quan-ly-nhanh.html | Đổi giá/trạng thái với kiểm quyền và audit |
| Sản phẩm | admin/giay-goi.html | Quản lý màu giấy gói và gán sản phẩm |
| Sản phẩm | admin/phan-loai-bo-loc.html | CRUD categories/tones/occasions |
| Sản phẩm | admin/kham-pha.html | Cấu hình nội dung khám phá trang chủ |
| Kho | admin/kho-tong.html | Tổng hợp tồn/giá trị và cảnh báo |
| Kho | admin/kho-hoa.html | Nhập/xuất/cập nhật kho hoa |
| Kho | admin/kho-phu-kien.html | Nhập/xuất/cập nhật kho phụ kiện |
| Kho | admin/lich-su-kho.html | Truy vấn giao dịch kho và người thao tác |
| Hệ thống | admin/dang-nhap.html | Xác thực server, session, kiểm role |
| Hệ thống | admin/nhan-vien.html | Quản lý nhân viên và phân quyền |
| Hệ thống | admin/cai-dat.html | Cài đặt cửa hàng lưu MySQL |
| Hệ thống | admin/tai-khoan.html | Hồ sơ admin được xác thực |
| Hệ thống | admin/chu-so-huu.html | Trang chủ sở hữu với quyền cao nhất, chống tự khóa tài khoản |

## 4. Trạng thái thực tế của branch yêu cầu 2

- Giữ bản gốc trên main. Branch tích hợp: `feature/requirement-2-php-mysql`.
- Đã có `database/schema.sql` gồm 17 bảng theo mô hình giai đoạn trước, `database/seed.sql`, PDO/bootstrap.
- Đã thêm **18 file .php khách hàng** nhưng một số file chỉ là wrapper và 5 trang tài khoản **chưa hoàn thành**.
- Nhóm chức năng đã có xử lý MySQL và được kiểm tra tích hợp tự động: sản phẩm, đặt hàng, tra cứu đơn, liên hệ, tin tức. Giỏ đang dùng LocalStorage, backend kiểm lại giá ở lúc checkout.
- **22 trang quản trị vẫn là HTML tĩnh với admin.js/LocalStorage, chưa có hệ thống Admin PHP/MySQL thực sự.**
- Bản PDF cũ chỉ gồm 13 màn hình khách hàng. **Không được ghi nó đạt 70% của 40 trang.**
- Không dùng tài khoản/mật khẩu demo trong `admin/dang-nhap.html` làm hệ thống xác thực thực tế.

## 5. Yêu cầu kiến trúc trước khi phát triển tiếp

1. HTML5, CSS3, JavaScript thuần, PHP, MySQL; giữ UI đã nộp, không tự ý thay Next.js/React/Supabase.
2. Phân tầng: `config/` (kết nối), `includes/` (common+auth+validation), `repositories/` (PDO data), `services/` (business rules), `actions/` (HTTP handlers), `admin/` (UI admin), `database/` (schema/migrations/seeds), `tests/` (integration).
3. Bổ sung migration cho chức năng chưa nằm trong 17 bảng: reset-password tokens, settings, khám phá... theo use-case đã có; không xóa dữ liệu gốc.
4. Auth PHP Session, password_hash/verify, phân quyền customer/staff/admin/owner; mọi mutation validate đầu vào + CSRF + prepared statements. Không tin dữ liệu giá/role/status từ browser.
5. Mọi module phải dùng cùng database và mô hình sản phẩm/đơn/kho; admin cập nhật phải phản ánh ở trang khách hàng, khách đặt phải xuất hiện trong admin.
6. Không “chữa” lỗi PHP bằng thay dữ liệu thật thành LocalStorage demo.
7. Chỉ báo hoàn thành từng trang khi có code, test thành công và ảnh minh chứng.

## 6. Lộ trình triển khai bài bản

- **Giai đoạn A – Audit & thiết kế:** khớp các màn hình, entity, luồng nghiệp vụ, tiêu chí nghiệm thu; sửa schema qua migration, seed và tài liệu setup.
- **Giai đoạn B – Nền tảng:** PHP layout + PDO + session/auth/RBAC + CSRF + validate + routing/link; test cơ sở.
- **Giai đoạn C – FrontEnd khách hàng:** hoàn chỉnh 18/18 trang, không tính demo; end-to-end duyệt hoa -> cart -> checkout -> MySQL -> tra cứu, tài khoản thực.
- **Giai đoạn D – Admin:** hoàn chỉnh 22/22 màn hình có giao diện và menu đúng; ưu tiên đăng nhập, dashboard, đơn hàng, sản phẩm CRUD, khách hàng, giao hàng, kho, rồi các màn hình chuyên sâu.
- **Giai đoạn E – Tích hợp & QA:** SQL integrity, auth/authorization, test HTTP/browser, PHP lint, regression, responsive, xuất PDF ảnh Chrome thật (có thanh địa chỉ).

### Mốc trung gian 70% theo tổng 40 màn hình

Chỉ đánh dấu mốc này **khi đã có 28 trang nghiệm thu thực tế** (ví dụ đủ 18 customer + 10 Admin cốt lõi), không phải chỉ 28 file PHP.

10 Admin cốt lõi đề xuất: `dang-nhap`, `index`, `don-hang`, `san-pham`, `them-san-pham`, `sua-san-pham`, `khach-hang`, `giao-hang`, `kho-tong`, `phan-loai-bo-loc`. Tuy nhiên cần xác minh 18 trang customer đều vượt test trước khi tính mốc.

## 7. Tổ chức nhóm

- **Bảo ~50% công sức khó:** kiến trúc, schema/migration, authentication/authorization dùng chung, đặt hàng, đơn hàng/quản trị, tồn kho, tích hợp & QA.
- **Thành viên 2 ~25%:** module tài khoản khách hàng, hồ sơ/lịch sử, kiểm thử và tài liệu.
- **Thành viên 3 ~25%:** catalog/nội dung, sản phẩm Admin CRUD, taxonomy, giao diện và minh chứng.
- Mỗi người phát triển trên branch riêng xuất phát từ branch tích hợp; PR phải qua review, CI; không sửa production `Web_Flower_Manus`.
- Chưa giao branch/module cho các thành viên trước khi chốt interface PHP, schema và tiêu chí nghiệm thu.

## 8. Lưu ý báo cáo

- Bản PDF tạo trước đây là **bản minh chứng tạm 13 trang**, không được dùng làm bằng chứng tiến độ 70% toàn hệ thống.
- Ảnh chụp phải là Web PHP chạy thật, bao gồm thanh địa chỉ; ảnh trang Admin cần đăng nhập/quyền hợp lệ và dữ liệu thật.
- Tỷ lệ 70% trong đề có thể được giảng viên đánh giá theo **chức năng** thay vì số file; quy ước 28/40 của nhóm chỉ là mức quản trị công việc bảo thủ, phải đảm bảo chất lượng các chức năng.
