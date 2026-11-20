# Biên bản bàn giao Customer · 20/11/2026
Windows 10 Pro (19045), XAMPP tại C:\xampp, Apache 2.4.58, PHP 8.2.12 với pdo_mysql, mbstring và fileinfo, MariaDB 10.4.32. Chrome dùng http://localhost/Lap_trinh_web/.
Database cas_hoa_customer_test_demo20 trên 127.0.0.1:3307 dùng dữ liệu demo. Đã nạp schema, seed và migrations 001–005 theo thứ tự. Mã nguồn main tại 04ec72b28210bcfeb7ed8706f7801c2e908ee9b6.
| Mã | Kỳ vọng | Thực tế | Kết quả |
|---|---|---|---|
| TC01 | Trang chủ, danh mục và chi tiết tải đủ nội dung, ảnh. | Ba trang HTTP 200, ảnh giải mã được. | PASS |
| TC02 | Lọc loại hoa, tông màu, từ khóa và khoảng giá thu hẹp danh sách. | 11 mẫu → 2 mẫu ở mức tối đa 400.000 đ → 1 mẫu Ly xanh ở mức 390.000 đ. | PASS |
| TC03 | Thêm, tăng số lượng và xóa hoa cập nhật tổng giỏ. | 400000 → 800000 đ, xóa còn 0 sản phẩm. | PASS |
| TC04 | Chặn đơn thiếu trường bắt buộc và tạo đơn hợp lệ. | Chặn thiếu tên; tạo đơn CSH-2026-33D793A6 | PASS |
| TC05 | Tra cứu đúng mã và điện thoại cho thấy trạng thái đơn. | CSH-2026-33D793A6 · Chờ xác nhận | PASS |
| TC06 | Đăng ký, đăng nhập lại và chỉ xem được đơn của chính mình. | Đăng ký, đăng xuất và đăng nhập lại thành công. Đơn hiển thị tại A, tài khoản B không thấy đơn. | PASS |
| TC11 | Ở 375, 768 và 1440 px, các trang không tràn ngang hoặc vỡ ảnh. | 21/21 lượt đạt (7 trang × 3 chiều rộng). | PASS |

TC02: lần đầu kỳ vọng chỉ có Ly xanh ở trần 400.000 đ. Tìm kiếm xét cả tên, loại hoa và tông màu nên Lam tinh (400.000 đ) cũng phù hợp. Lượt kiểm tra lại xác nhận hai kết quả ở trần 400.000 đ và một kết quả ở trần 390.000 đ. Không phát hiện lỗi sản phẩm trong các ca TV2 đã chạy.
TC07–TC10 và clip quản trị thuộc TV3. Kết quả trong hồ sơ này chỉ áp dụng cho dữ liệu demo và các màn hình đã kiểm thử.
## Danh mục ảnh
| Ảnh | URL | Giờ Việt Nam |
|---|---|---|
| [TC01_trang_chu](Anh/TC01_trang_chu.png) | http://localhost/Lap_trinh_web/index.php | 20/11/2026 15:39:07 |
| [TC01_danh_muc](Anh/TC01_danh_muc.png) | http://localhost/Lap_trinh_web/san-pham.php | 20/11/2026 15:39:08 |
| [TC01_chi_tiet](Anh/TC01_chi_tiet.png) | http://localhost/Lap_trinh_web/chi-tiet-san-pham.php?slug=lam-tinh | 20/11/2026 15:39:10 |
| [TC02_loc](Anh/TC02_loc.png) | http://localhost/Lap_trinh_web/san-pham.php?type=bouquet&category=Hoa+ly&tone=Xanh&min=300000&max=390000&q=ly+xanh | 20/11/2026 15:39:16 |
| [TC03_so_luong](Anh/TC03_so_luong.png) | http://localhost/Lap_trinh_web/gio-hang.php | 20/11/2026 15:39:18 |
| [TC03_gio_rong](Anh/TC03_gio_rong.png) | http://localhost/Lap_trinh_web/gio-hang.php | 20/11/2026 15:39:18 |
| [TC06_tai_khoan](Anh/TC06_tai_khoan.png) | http://localhost/Lap_trinh_web/tai-khoan.php | 20/11/2026 15:39:21 |
| [TC04_thieu_du_lieu](Anh/TC04_thieu_du_lieu.png) | http://localhost/Lap_trinh_web/thanh-toan.php | 20/11/2026 15:39:24 |
| [TC04_dat_thanh_cong](Anh/TC04_dat_thanh_cong.png) | http://localhost/Lap_trinh_web/dat-hang-thanh-cong.php?code=CSH-2026-33D793A6 | 20/11/2026 15:39:24 |
| [TC05_tra_cuu](Anh/TC05_tra_cuu.png) | http://localhost/Lap_trinh_web/tra-cuu-don-hang.php?code=CSH-2026-33D793A6 | 20/11/2026 15:39:25 |
| [TC06_lich_su](Anh/TC06_lich_su.png) | http://localhost/Lap_trinh_web/lich-su-don-hang.php | 20/11/2026 15:39:27 |
| [TC06_lich_su_B](Anh/TC06_lich_su_B.png) | http://localhost/Lap_trinh_web/lich-su-don-hang.php | 20/11/2026 15:39:30 |
| [TC11_375](Anh/TC11_375.png) | http://localhost/Lap_trinh_web/san-pham.php | 20/11/2026 15:39:32 |
| [TC11_768](Anh/TC11_768.png) | http://localhost/Lap_trinh_web/san-pham.php | 20/11/2026 15:39:38 |
| [TC11_1440](Anh/TC11_1440.png) | http://localhost/Lap_trinh_web/san-pham.php | 20/11/2026 15:39:44 |
