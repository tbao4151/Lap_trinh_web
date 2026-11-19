# Bổ sung catalog thật từ Supabase CÁ'S HOA

Nguồn: project **Cá's Hoa** (Supabase project ref `kbfcxsbibrtafkokhfev`) tại thời điểm 19/11/2026. Chỉ truy vấn đọc: 47 sản phẩm tổng cộng, **35 published**, 12 hidden. Bộ nhập demo chứa **35 sản phẩm published + 55 ảnh thật** (bucket `product-images`, công khai, đã đối chiếu storage.objects). Giá lưu trong MySQL là `effective_price_vnd` tại thời điểm trích xuất, không phải giá cố định về sau.

## Cài XAMPP (PowerShell + phpMyAdmin)

1. Tại XAMPP: bật Apache và MySQL. Nếu dùng lại `cas_hoa` cũ, chỉ xóa database khi không cần dữ liệu. Import vào database sạch theo thứ tự: `database/schema.sql`, `database/seed.sql`, rồi `database/migrations/001_auth.php.sql` đến `005_profit_cost_snapshot.sql`.
2. Mở Terminal PowerShell **tại root dự án** và chạy:

```powershell
cd D:\XamPP\htdocs\Lap_trinh_web
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\download-cashoa-product-images.ps1
```

Lệnh tải **55 ảnh** từ bucket public vào `assets/images/cashoa_catalog/` (mỗi ảnh lưu riêng theo UUID). Nếu có ảnh tải lỗi, dừng và sửa mạng trước; không import dữ liệu chứa đường dẫn ảnh thiếu.

3. Vào phpMyAdmin → chọn database `cas_hoa` → tab Import → chọn `database/demo_cashoa_supabase_catalog.sql` → Import.
4. Kiểm tra truy vấn: `SELECT COUNT(*) FROM products;` (dự kiến **46**, gồm 11 mẫu gốc và 35 mẫu thật); `SELECT COUNT(*) FROM product_images;` (dự kiến tối thiểu **66**, gồm 11 ảnh mẫu đã được migration 003 nạp và 55 ảnh thật).
5. Mở `http://localhost/Lap_trinh_web/san-pham.php`, thử bộ lọc theo loại hoa/tông màu/dịp tặng, xem ảnh gallery và thực hiện giỏ hàng.

## Giới hạn chuyển đổi dữ liệu

- MySQL của đồ án có **một** `category_id`, **một** `tone_id`, **một** `occasion_id` mỗi sản phẩm; Supabase dùng nhiều-nhiều. Do đó mỗi mẫu được gán một nhãn tiêu biểu. Không viết lại logic PHP/MySQL của đồ án.
- Không nhập 12 sản phẩm `hidden` (tránh công khai các mẫu shop đã ẩn).
- Không nhập tài khoản, đơn hàng, thông tin khách hàng, dữ liệu kho hoặc giá vốn từ Supabase.
- Mẫu thật chưa có `product_recipes` trong MySQL. Để demo luồng trừ tồn kho, cấu hình công thức nguyên liệu trong Admin trước khi chuyển đơn sang `preparing`; không tự dựng công thức giả.
- Ảnh tải vào máy là file local **không có sẵn trong GitHub**. Mỗi máy sau khi clone cần chạy script tải ảnh. Các path đã lưu SQL đều là local để trang web tiếp tục hiển thị khi mất mạng sau khi tải.
- Script chỉ tải ảnh từ bucket Storage **public**, không dùng key hoặc token; Supabase nguồn không bị sửa đổi.

## Kiểm tra không trùng khi chạy lại

SQL sử dụng `INSERT IGNORE` cho sản phẩm theo SKU/slug và kiểm tra `NOT EXISTS` khi tạo ảnh gallery và giấy gói. Có thể chạy lại để bù dữ liệu thiếu; không tự cập nhật giá các mẫu đã nhập. Muốn cập nhật theo giá Supabase mới, cần xuất dữ liệu mới.
