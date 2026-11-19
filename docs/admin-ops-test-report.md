# Báo cáo kiểm thử Admin Operations (ADM-01 → ADM-07)

Branch: `feature/admin-operations-completion` (tách từ `feature/requirement-2-php-mysql`). Toàn bộ số liệu dưới đây lấy từ lần chạy thật ngày 13/10/2026, log đầy đủ ở [`docs/admin-ops/ci-local-log.txt`](admin-ops/ci-local-log.txt).

## 1. Môi trường và lệnh chạy

| Mục | Giá trị |
|---|---|
| PHP | 8.2.34 CLI (`php -S`, giống CI `shivammathur/setup-php` 8.2) |
| CSDL | MariaDB 10.4.32 (XAMPP) — CI dùng MySQL 8.0 |
| Database | `cas_hoa` xoá và tạo lại từ đầu trước khi chạy |
| Lệnh | `bash scripts/verify-r2.sh && bash scripts/verify-r2-auth-admin.sh && bash scripts/verify-admin-ops.sh` |
| Lint | `php -l` 63/63 file PHP không lỗi; `node --check assets/js/main-r2.js` đạt; `python3 -m py_compile tests/admin/test_admin_ops.py` đạt |

Kết quả: **`verify-r2.sh` PASS, `verify-r2-auth-admin.sh` PASS, `verify-admin-ops.sh` PASS — 216/216 kiểm tra đạt.**

`verify-admin-ops.sh` tự làm các bước: chạy migration 001 → 002 → 003, chạy 003 lần thứ hai và so số dòng 6 bảng (không đổi: `12,4,4,9,12,10`), kiểm tra đủ bảng/cột mới; tạo tài khoản owner/admin/staff bằng `scripts/create-admin.php` với **mật khẩu sinh ngẫu nhiên mỗi lần chạy** (không có mật khẩu cố định trong repo) và thử tạo vai trò `customer` phải bị từ chối; bật `php -S` rồi chạy `tests/admin/test_admin_ops.py` (chỉ dùng thư viện chuẩn Python: HTTP có cookie + `mysql` CLI để đối chiếu dữ liệu).

> Trên Windows, `php -S` xử lý tuần tự nên bài "xuất kho đồng thời" chạy nối tiếp; trên CI Linux script bật `PHP_CLI_SERVER_WORKERS=4` để hai yêu cầu thực sự song song. Tính đúng không phụ thuộc vào điều này vì phiếu kho khoá dòng bằng `SELECT … FOR UPDATE` trong transaction và có CHECK `quantity >= 0`.

## 2. Tổng hợp theo nhóm

| Nhóm kiểm thử | Số kiểm tra | Đạt |
|---|---:|---:|
| Ma trận quyền 22 route x 5 vai trò | 110 | 110 |
| Chặn POST trái phép / thiếu CSRF | 4 | 4 |
| ADM-02 Sản phẩm, ảnh, giá nhanh | 19 | 19 |
| ADM-02 Phân loại | 8 | 8 |
| ADM-03 Kho | 12 | 12 |
| ADM-04 Giao hàng | 7 | 7 |
| ADM-04 Hoàn tiền | 11 | 11 |
| ADM-05 Giấy gói | 7 | 7 |
| ADM-05 Khám phá | 9 | 9 |
| ADM-06 Cài đặt | 6 | 6 |
| ADM-06 Nhân viên / chủ sở hữu | 19 | 19 |
| Đăng xuất thu hồi quyền | 1 | 1 |
| Nhật ký quản trị | 3 | 3 |
| **Tổng** | **216** | **216** |

## 3. Chi tiết từng kiểm tra

### Ma trận quyền 22 route x 5 vai trò

Mỗi ô kiểm tra mã HTTP, đích chuyển hướng (`dang-nhap.php`) và trang 200 phải có nội dung quản trị. Bảng đầy đủ trước/sau: [`docs/admin-audit.md`](admin-audit.md).

| Vai trò | Kỳ vọng | Kết quả |
|---|---|---|
| guest, customer | 303 về đăng nhập ở 21 route, 200 ở `dang-nhap.php` | 44/44 đạt |
| staff | 403 ở `them-san-pham`, `sua-san-pham`, `nhan-vien`, `cai-dat`, `chu-so-huu`; 200 các trang còn lại; 303 ở trang đăng nhập | 22/22 đạt |
| admin | 403 ở `chu-so-huu`; 200 các trang còn lại | 22/22 đạt |
| owner | 200 cả 21 trang quản trị | 22/22 đạt |

### Chặn POST trái phép / thiếu CSRF

- [x] POST kho as guest bị chuyển về đăng nhập
- [x] POST kho as customer bị chuyển về đăng nhập
- [x] POST thiếu CSRF bị chặn 403
- [x] Không có giao dịch kho nào được tạo từ POST trái phép

### ADM-02 Sản phẩm, ảnh, giá nhanh

- [x] Tạo sản phẩm + 2 ảnh tải lên (multipart) -> 303 sang trang sửa
- [x] product_images lưu 2 ảnh, đúng 1 ảnh đại diện
- [x] Ảnh được lưu thật dưới assets/uploads/products
- [x] Slug tự sinh từ tên tiếng Việt
- [x] Gán giấy gói 1, 2 cho sản phẩm
- [x] Từ chối file giả ảnh (PHP đổi đuôi .png) -> 422
- [x] File giả không được lưu, sản phẩm không đổi
- [x] SKU trùng (không phân biệt hoa thường) -> 422
- [x] Giá âm -> 422
- [x] Nhân viên POST tạo sản phẩm -> 403
- [x] Sửa sản phẩm không tồn tại -> 404
- [x] Đổi ảnh đại diện cập nhật products.image
- [x] Quản lý nhanh đổi giá -> 303, products.price = 777000
- [x] Giá trong order_items cũ không đổi (price snapshot)
- [x] Feed khách hàng nhận giá mới
- [x] Ẩn sản phẩm -> không còn trong feed khách hàng
- [x] Không xoá sản phẩm đã có đơn hàng -> 422
- [x] Xoá thiếu xác nhận -> 422
- [x] Xoá sản phẩm chưa có đơn (kèm ảnh) -> 303

### ADM-02 Phân loại

- [x] Thêm tông màu mới -> 303
- [x] Tên phân loại trùng (khác hoa thường) -> 422
- [x] Nhân viên sửa phân loại -> 403
- [x] Xoá tông màu đang dùng -> 422
- [x] Ngừng dùng loại hoa -> sản phẩm vẫn giữ category_id
- [x] Loại hoa ngừng dùng không còn trong bộ lọc trang khách
- [x] Bật lại loại hoa -> xuất hiện lại trong bộ lọc
- [x] Xoá tông màu chưa dùng -> 303

### ADM-03 Kho

- [x] Tạo nguyên liệu kèm tồn đầu 10 (1 transaction) -> 303
- [x] Tồn đầu được ghi phiếu nhập
- [x] Nhân viên nhập 15 -> 303, before 10 / after 25, ghi người thực hiện
- [x] Gửi lại cùng mã phiếu (double submit) -> 422, không cộng tồn lần 2
- [x] Xuất vượt tồn -> 422, tồn giữ nguyên 25
- [x] Nhân viên kiểm kê (adjustment) -> 403
- [x] Số lượng 0 -> 422
- [x] Xuất đồng thời: đúng 1 phiếu thành công, tồn = 5 (không âm)
- [x] Admin kiểm kê đặt tồn = 8 -> 303
- [x] Lịch sử kho hiển thị phiếu theo bộ lọc nguyên liệu
- [x] Tổng phiếu của nguyên liệu = 4 (tồn đầu, nhập, xuất, kiểm kê)
- [x] Kho phụ kiện không lẫn nguyên liệu hoa

### ADM-04 Giao hàng

- [x] Nhảy cóc Chưa giao -> Đã giao bị chặn 422
- [x] Nhân viên chuyển sang Đang giao -> 303
- [x] Giao thất bại không ghi lý do -> 422
- [x] Đang giao -> Đã giao -> 303
- [x] Đã giao là trạng thái cuối -> 422
- [x] delivery_events lưu đủ 4 bước có người thực hiện
- [x] Lịch giao theo ngày (SQL) hiển thị đơn

### ADM-04 Hoàn tiền

- [x] Không tạo hoàn tiền cho đơn chưa thanh toán -> 422
- [x] Hoàn vượt số đã thanh toán -> 422
- [x] Nhân viên tạo yêu cầu hoàn 50.000 -> 303
- [x] Gửi trùng yêu cầu hoàn -> 422, chỉ 1 bản ghi
- [x] Nhân viên tự duyệt hoàn tiền -> 403
- [x] Không ghi nhận khi chưa duyệt (nhảy bước) -> 422
- [x] Admin duyệt -> 303
- [x] Ghi nhận đã hoàn thiếu mã giao dịch -> 422
- [x] Ghi nhận đã hoàn có mã giao dịch -> 303
- [x] Hoàn tiền KHÔNG tự đổi orders.payment_status (contract của lead)
- [x] refund_events có đủ 3 bước

### ADM-05 Giấy gói

- [x] Thêm giấy gói kèm ảnh, phụ thu -> 303
- [x] Nhân viên sửa giấy gói -> 403
- [x] Mã màu sai định dạng -> 422
- [x] Gán cho sản phẩm 1 -> feed khách có giấy gói + phụ thu
- [x] Xoá giấy gói đang gán -> 422
- [x] Giấy gói "ngừng dùng" không còn hiển thị cho khách
- [x] Bỏ gán rồi xoá -> 303

### ADM-05 Khám phá

- [x] Thêm nhóm khám phá theo tông màu -> 303
- [x] Trang chủ hiển thị thẻ mới với liên kết lọc đúng
- [x] Liên kết dẫn tới trang Sản phẩm có bộ lọc tông màu tương ứng
- [x] Nhân viên xoá nhóm khám phá -> 403
- [x] Nhóm không có ảnh -> 422
- [x] Tông màu ngừng dùng -> thẻ tự ẩn ở trang chủ
- [x] Admin thấy trạng thái không hợp lệ của thẻ
- [x] Không gắn nhóm vào phân loại đang ngừng dùng -> 422
- [x] Xoá nhóm khám phá -> 303

### ADM-06 Cài đặt

- [x] Hotline sai -> 422
- [x] Email sai -> 422
- [x] Nhân viên lưu cài đặt -> 403
- [x] Admin lưu cài đặt -> 303 và MySQL cập nhật
- [x] Trang Liên hệ dùng địa chỉ mới
- [x] Header / footer nhận thông báo + địa chỉ mới qua CAS_SETTINGS

### ADM-06 Nhân viên / chủ sở hữu

- [x] Nhân viên tự nâng quyền -> 403, vai trò giữ nguyên
- [x] Nhân viên POST trang chủ sở hữu -> 403
- [x] Admin tạo tài khoản Quản trị viên -> 403
- [x] Mật khẩu yếu -> 422
- [x] Admin tạo nhân viên -> 303, mật khẩu lưu dạng băm
- [x] Admin khoá nhân viên -> 303
- [x] Phiên đang mở của nhân viên bị khoá mất quyền ngay
- [x] Admin khoá chủ sở hữu -> 403
- [x] Không tự khoá chính mình -> 403
- [x] Admin đổi vai trò -> 403 (chỉ chủ sở hữu)
- [x] Chủ sở hữu nâng nhân viên lên quản trị viên -> 303
- [x] Không hạ cấp chủ sở hữu cuối cùng -> 422
- [x] Sai mật khẩu xác nhận -> 422, không đổi vai trò
- [x] Chủ sở hữu nâng admin lên chủ sở hữu (nhập lại mật khẩu) -> 303
- [x] Còn 2 chủ sở hữu -> hạ một người về quản trị viên -> 303
- [x] Luôn còn ít nhất 1 chủ sở hữu hoạt động
- [x] Nhân viên tự sửa hồ sơ -> 303
- [x] Đổi mật khẩu sai mật khẩu hiện tại -> 422
- [x] Nhật ký chủ sở hữu liệt kê các lần bị chặn

### Đăng xuất thu hồi quyền

- [x] Sau đăng xuất, POST admin bằng cookie cũ bị chuyển về đăng nhập

### Nhật ký quản trị

- [x] admin_audit_log ghi thao tác thành công
- [x] admin_audit_log ghi các lần bị chặn quyền
- [x] Nhật ký không chứa mật khẩu

## 4. Ảnh chụp trình duyệt thật

Chụp bằng Google Chrome thật (cửa sổ 1440×900, gồm thanh địa chỉ) qua Chrome DevTools Protocol; mọi thao tác trong ảnh là gửi form thật tới PHP/MySQL (đăng nhập, tải ảnh, nhập kho, tạo yêu cầu hoàn, lưu cài đặt…). Bản gốc PNG nằm trong báo cáo tổng hợp; repo giữ bản JPG 1280 px trong [`docs/admin-ops/screenshots/`](admin-ops/screenshots/).

| Ảnh | Nội dung | URL |
|---|---|---|
| [a01-dang-nhap](admin-ops/screenshots/a01-dang-nhap.jpg) | Đăng nhập quản trị (PHP Session, CSRF) | `/Lap_trinh_web/admin/dang-nhap.php` |
| [a02-dashboard-menu-moi](admin-ops/screenshots/a02-dashboard-menu-moi.jpg) | Dashboard với menu 22 route nhóm theo quyền | `/Lap_trinh_web/admin/index.php` |
| [a03-san-pham](admin-ops/screenshots/a03-san-pham.jpg) | Danh sách sản phẩm: tìm kiếm, lọc, ẩn/hiện, xoá có kiểm tra | `/Lap_trinh_web/admin/san-pham.php` |
| [a04-sua-san-pham-tai-anh](admin-ops/screenshots/a04-sua-san-pham-tai-anh.jpg) | Sửa sản phẩm: tải 2 ảnh PNG thật, lưu vào product_images | `/Lap_trinh_web/admin/sua-san-pham.php?id=1` |
| [a04b-thu-vien-anh](admin-ops/screenshots/a04b-thu-vien-anh.jpg) | Thư viện ảnh: ảnh đại diện, sắp xếp, xoá | `/Lap_trinh_web/admin/sua-san-pham.php?id=1` |
| [a05-them-san-pham](admin-ops/screenshots/a05-them-san-pham.jpg) | Thêm sản phẩm: SKU/slug/giá, phân loại, giấy gói, ảnh | `/Lap_trinh_web/admin/them-san-pham.php` |
| [a06-quan-ly-nhanh](admin-ops/screenshots/a06-quan-ly-nhanh.jpg) | Quản lý nhanh giá/trạng thái (giữ giá cũ trong đơn đã đặt) | `/Lap_trinh_web/admin/quan-ly-nhanh.php` |
| [a07-phan-loai](admin-ops/screenshots/a07-phan-loai.jpg) | Phân loại: loại hoa, tông màu, dịp tặng, số sản phẩm đang dùng | `/Lap_trinh_web/admin/phan-loai-bo-loc.php` |
| [a08-kho-tong](admin-ops/screenshots/a08-kho-tong.jpg) | Kho tổng: KPI, cảnh báo dưới mức tối thiểu, phiếu nhập/xuất | `/Lap_trinh_web/admin/kho-tong.php` |
| [a09-kho-hoa-nhap](admin-ops/screenshots/a09-kho-hoa-nhap.jpg) | Kho hoa: nhập 20 cành, ghi tồn trước/sau và người thực hiện | `/Lap_trinh_web/admin/kho-hoa.php` |
| [a10-kho-hoa-chan-am](admin-ops/screenshots/a10-kho-hoa-chan-am.jpg) | Chặn xuất vượt tồn (HTTP 422, tồn không âm) | `/Lap_trinh_web/admin/kho-hoa.php` |
| [a11-kho-phu-kien](admin-ops/screenshots/a11-kho-phu-kien.jpg) | Kho phụ kiện: giấy gói, nơ, hộp, phụ kiện | `/Lap_trinh_web/admin/kho-phu-kien.php` |
| [a12-lich-su-kho](admin-ops/screenshots/a12-lich-su-kho.jpg) | Lịch sử kho: lọc ngày/mặt hàng/loại/người, tồn trước → sau | `/Lap_trinh_web/admin/lich-su-kho.php` |
| [a13-giao-hang](admin-ops/screenshots/a13-giao-hang.jpg) | Giao hàng: lịch 7 ngày từ SQL, khung giờ, trạng thái hợp lệ | `/Lap_trinh_web/admin/giao-hang.php` |
| [a14-hoan-tien](admin-ops/screenshots/a14-hoan-tien.jpg) | Hoàn tiền: yêu cầu → duyệt → ghi nhận, không vượt số đã thu | `/Lap_trinh_web/admin/hoan-tien.php` |
| [a14b-hoan-tien-danh-sach](admin-ops/screenshots/a14b-hoan-tien-danh-sach.jpg) | Danh sách yêu cầu hoàn: người tạo, người duyệt, mã giao dịch | `/Lap_trinh_web/admin/hoan-tien.php` |
| [a15-giay-goi](admin-ops/screenshots/a15-giay-goi.jpg) | Giấy gói: phụ thu, ảnh, thứ tự, ngừng dùng | `/Lap_trinh_web/admin/giay-goi.php` |
| [a15b-giay-goi-gan-san-pham](admin-ops/screenshots/a15b-giay-goi-gan-san-pham.jpg) | Gán giấy gói cho sản phẩm | `/Lap_trinh_web/admin/giay-goi.php?assign=3#assign` |
| [a16-kham-pha](admin-ops/screenshots/a16-kham-pha.jpg) | Danh mục khám phá trang chủ: lọc theo phân loại có sẵn | `/Lap_trinh_web/admin/kham-pha.php` |
| [a17-cai-dat](admin-ops/screenshots/a17-cai-dat.jpg) | Cài đặt shop lưu MySQL (shop_settings), có kiểm tra dữ liệu | `/Lap_trinh_web/admin/cai-dat.php` |
| [a18-nhan-vien](admin-ops/screenshots/a18-nhan-vien.jpg) | Nhân viên: tìm kiếm, tạo, khoá/mở khoá, đổi vai trò theo quyền | `/Lap_trinh_web/admin/nhan-vien.php` |
| [a19-tai-khoan](admin-ops/screenshots/a19-tai-khoan.jpg) | Tài khoản của tôi: hồ sơ, đổi mật khẩu, lịch sử thao tác | `/Lap_trinh_web/admin/tai-khoan.php` |
| [a20-chu-so-huu](admin-ops/screenshots/a20-chu-so-huu.jpg) | Chủ sở hữu: bảo vệ chủ sở hữu cuối, xác nhận bằng mật khẩu | `/Lap_trinh_web/admin/chu-so-huu.php` |
| [a20b-nhat-ky-quan-tri](admin-ops/screenshots/a20b-nhat-ky-quan-tri.jpg) | Nhật ký quản trị: mọi thao tác thành công / bị chặn | `/Lap_trinh_web/admin/chu-so-huu.php` |
| [b01-nhan-vien-menu](admin-ops/screenshots/b01-nhan-vien-menu.jpg) | Nhân viên: menu chỉ hiện chức năng được phép | `/Lap_trinh_web/admin/index.php` |
| [b02-nhan-vien-403](admin-ops/screenshots/b02-nhan-vien-403.jpg) | Nhân viên mở trang Cài đặt bằng URL trực tiếp → 403 và ghi log | `/Lap_trinh_web/admin/cai-dat.php` |
| [b03-nhan-vien-giao-hang](admin-ops/screenshots/b03-nhan-vien-giao-hang.jpg) | Nhân viên cập nhật trạng thái giao hàng hợp lệ | `/Lap_trinh_web/admin/giao-hang.php` |
| [s01-trang-chu-thong-bao](admin-ops/screenshots/s01-trang-chu-thong-bao.jpg) | Trang chủ: thanh thông báo lấy từ Cài đặt shop | `/Lap_trinh_web/index.php` |
| [s01b-trang-chu-kham-pha](admin-ops/screenshots/s01b-trang-chu-kham-pha.jpg) | Trang chủ: thẻ khám phá đọc từ bảng discovery_groups | `/Lap_trinh_web/index.php` |
| [s02-san-pham-loc-kham-pha](admin-ops/screenshots/s02-san-pham-loc-kham-pha.jpg) | Thẻ khám phá dẫn tới trang Sản phẩm đã lọc đúng tông màu | `/Lap_trinh_web/san-pham.php?tone=Xanh` |
| [s03-chi-tiet-anh-giay-goi](admin-ops/screenshots/s03-chi-tiet-anh-giay-goi.jpg) | Chi tiết sản phẩm: thư viện ảnh mới + màu giấy gói đang dùng | `/Lap_trinh_web/chi-tiet-san-pham.php?slug=lam-tinh` |
| [s03b-chi-tiet-thu-vien-anh](admin-ops/screenshots/s03b-chi-tiet-thu-vien-anh.jpg) | Chi tiết sản phẩm: ảnh phụ do Admin tải lên | `/Lap_trinh_web/chi-tiet-san-pham.php?slug=lam-tinh` |
| [s04-lien-he-cai-dat](admin-ops/screenshots/s04-lien-he-cai-dat.jpg) | Liên hệ: địa chỉ, giờ mở cửa, hotline lấy từ Cài đặt shop | `/Lap_trinh_web/lien-he.php` |

## 5. Kiểm thử bằng tay bổ sung (trình duyệt)

- Hộp xác nhận (`data-confirm`) xuất hiện trước khi xoá sản phẩm/ảnh/giấy gói/nhóm khám phá, khoá tài khoản, đổi vai trò, thay đổi chủ sở hữu.
- Trang khách hàng: thanh thông báo, chân trang, trang Liên hệ đổi ngay sau khi lưu Cài đặt; thẻ khám phá dẫn tới `san-pham.php` đã chọn sẵn bộ lọc; trang chi tiết hiện ảnh phụ và giấy gói đang dùng.
- Menu nhân viên ẩn các mục không có quyền; mở trực tiếp URL bị chặn vẫn trả 403 (không chỉ ẩn menu).

## 6. Giới hạn còn lại (không báo hoàn thành ảo)

- Chưa chạy trên MySQL 8.0 tại máy; migration 003 viết theo cú pháp chung MySQL 8/MariaDB (kiểm tra `information_schema` rồi `PREPARE`) và sẽ được CI GitHub (MySQL 8.0) xác nhận khi đẩy branch.
- Thay đổi máy trạng thái đơn hàng, chuyển `payment_status` khi hoàn tiền, trừ kho theo đơn, phụ thu giấy gói ở checkout, phiên khác sau khi đổi mật khẩu: chưa làm vì thuộc contract của trưởng nhóm — xem [`docs/admin-ops-contracts.md`](admin-ops-contracts.md).
- Hoàn tiền chỉ ghi nhận thủ công (mã giao dịch), không tích hợp cổng thanh toán.
