# Bàn giao Customer — 15/10/2026

Đã hoàn tất phạm vi được phép và xuất/kiểm tra báo cáo Word 15 trang. Dấu hoàn tất cuối tệp chỉ xác nhận kết thúc mục tiêu bàn giao, không có nghĩa cả 7 task đã nghiệm thu tích hợp.

| Task | Kết quả cuối | Ghi chú |
|---|---|---|
| CUS-01 | LOCAL_PASS | Audit 18 trang, 90 lượt viewport, ảnh 375/768/1440 |
| CUS-02 | BLOCKED | Catalog đạt local; gallery/tồn kho cần lead chốt schema/contract |
| CUS-03 | LOCAL_PASS | Session/cart/checkout/retry, giá server và đơn trùng đã kiểm tra |
| CUS-04 | LOCAL_PASS | Validation/profile/cách ly tài khoản/reset SMTP local đạt |
| CUS-05 | LOCAL_PASS | Responsive và bàn phím đạt phạm vi test; tương phản nút 6,32:1 |
| CUS-06 | LOCAL_PASS | Bộ gợi ý DB, URL, hidden/empty, ngân sách đảo đạt |
| CUS-07 | BLOCKED | Test, minh chứng và Word xong; commit/push/PR/CI chưa được phép |

Kết quả: HTTP 35/35; browser 103/103; PHP 50/50; JavaScript 8/8; SQL tổng hợp 9/9; bổ sung 10/10. Tổng trọng số task đạt local 19,5/27,5, không phải điểm đã chấm. Checklist 11/12 đạt local; mục PR còn thiếu.

Bàn giao: docs/BAO_CAO_THANH_VIEN_2_CUSTOMER.docx; docs/customer-test-report.md; docs/customer-audit.md; docs/de-xuat-cho-lead.md; docs/pr-description.md. Minh chứng ở docs/evidence/CUS-01 đến CUS-07. Đã kiểm tra trực quan toàn bộ 15 trang Word, 13 bảng, 7 ảnh; kết quả ở CUS-07/docx-visual-check.json.

Đúng nhánh feature/customer-completion, HEAD 9c35a949484c654aa8a7cf9522bcefe09f7ce92c. Không commit/push/PR hoặc sửa cấu hình Git. checkout/auth/schema giữ nguyên SHA256 đầu phiên; các diff core đã tồn tại từ trước cần lead review riêng. Git diff --check đạt; cảnh báo LF/CRLF không phải lỗi kiểm thử.

## Chờ duyệt commit và bước tiếp theo

CUS-01..CUS-07 đều chưa commit; message và tệp theo task đã chuẩn bị trong docs/pr-description.md. Chỉ thực hiện đúng thao tác khi có lệnh riêng của người dùng. Lead cần chốt gallery/tồn kho, review diff core sẵn có và nghiệm thu trên Apache; SMTP Internet chưa kiểm chứng. Môi trường thử riêng nằm trong .local, không thay database của nhóm.

## Nhật ký trước khi chốt

Các dòng bên dưới là lịch sử, trạng thái cuối áp dụng theo bảng phía trên. Lỗi test harness, timeout browser và preview dừng đều được lưu, sửa hoặc khởi động lại và chạy kiểm tra thành công; không xóa bằng chứng thất bại.

# Tiến độ Thành viên 2 Customer

Ngày thực hiện: 14/10/2026 (Asia/Saigon).
Repo local: `customer-preview`; nhánh `feature/customer-completion`; HEAD ban đầu `9c35a94`.
Kế hoạch nguồn: `../02_PLAN_THANH_VIEN_2_CUSTOMER.md` tính từ thư mục workspace cha của clone; đã đọc đủ các mục 1–9.

## Quy ước

Chỉ dùng DONE, LOCAL_PASS, CODE_EXISTS, NOT_DONE hoặc BLOCKED cho trạng thái trang/task. LOCAL_PASS chỉ chứng minh môi trường thực tế đã chạy. Không commit, push, tạo/xóa/chuyển nhánh, mở PR hay đổi Git config trong phiên này.

## Checklist

- [x] CUS-01 — LOCAL_PASS — audit 18/18, HTTP 35/35, browser 103/103, lint 49/49; XAMPP Apache chưa đạt.
- [ ] CUS-02 — BLOCKED — phần catalog hiện có đạt test; gallery/tồn kho cần contract/schema do lead duyệt.
- [x] CUS-03 — LOCAL_PASS — HTTP/SQL và browser giỏ/checkout đạt; diff core trước phiên chờ lead review.
- [x] CUS-04 — LOCAL_PASS — validation, cách ly A/B, phân trang, SMTP local và reset qua browser đạt.
- [x] CUS-05 — LOCAL_PASS — 90 lượt trang/viewport đã đạt; màu nút sửa từ 3,94 lên 6,32:1 và bàn phím đạt.
- [x] CUS-06 — LOCAL_PASS — gợi ý DB, URL chia sẻ, hidden/empty và ngân sách đảo đã kiểm tra.
- [ ] CUS-07 — NOT_DONE — cần test report, audit, đề xuất lead, mô tả PR và Word.

## Nhật ký

1. Đã đọc objective và REQUIREMENT2_SETUP.md; tìm đúng clone trong `customer-preview` sau khi xác nhận workspace cha không có Git.
2. Xác nhận đúng nhánh làm việc; không đổi nhánh. Có 28 tệp tracked đã sửa cùng nhiều tệp untracked trước phiên; không tính những thay đổi đó là mới thực hiện trong phiên này.
3. `actions/checkout.php` và `includes/auth.php` đã có diff từ trước. Giữ nguyên nội dung, ghi đề xuất lead và kiểm tra SHA256 trước/sau; không tự hoàn nguyên hoặc bổ sung nghiệp vụ core.
4. Tìm thấy kế hoạch và PDF đề tài ở thư mục cha workspace, đang đọc PDF. Không có PROGRESS.md trước phiên trong clone.

## Đang làm

Kiểm tra runtime hiện tại và chạy smoke/HTTP/SQL/browser từ mã có sẵn trước khi bổ sung test còn thiếu.

## Lỗi và phụ thuộc

- URL XAMPP yêu cầu chưa xác minh đạt; có cấu hình preview 8033/MariaDB 3307/SMTP 1025 cần kiểm tra thực tế.
- Kết quả test và ảnh từ phiên trước không được mặc nhiên coi là bằng chứng cho trạng thái hiện tại.
- CI/PR chưa thực hiện vì chưa có lệnh người dùng.

## Bước tiếp theo

Audit 18 trang, hoàn thiện lần lượt CUS-01 đến CUS-07, ghi expected/actual và ảnh thật rồi xuất Word.

## Chờ duyệt commit

Chưa có task được xác nhận hoàn thành trong phiên này. Mọi thay đổi hiện có đều chưa commit; sẽ chuẩn bị đề xuất commit theo từng task sau kiểm thử.

5. CUS-01: kiểm tra mới đạt 18/18 trang; khởi tạo MariaDB riêng tại .local/mysql-completion trong repo bằng đường dẫn ngắn ASCII, không thay cấu hình hệ thống. URL Apache chưa chạy và thư mục htdocs yêu cầu chưa có. Đã đọc PDF 40 trang (báo cáo minh chứng cũ, không thay test mới).
6. Chờ duyệt commit CUS-01: docs(customer): audit 18 customer pages with local evidence. Tiếp theo CUS-02.


7. CUS-02: đã sửa nhãn Mới thêm và lỗi khoảng giá inline/aria-invalid; đã thêm tests/customer/completion.cjs. Kiểm thử từng filter với tập ID SQL đã đạt, đang chụp 375/768/1440 và kiểm tra đổi giá/ẩn sản phẩm. Đề xuất gallery/tồn kho/core ở docs/de-xuat-cho-lead.md.


8. CUS-02: 3/3 kiểm tra bổ sung đạt, ảnh thật 375/768/1440; PHP lint catalog đạt. CUS-03: 2/2 bổ sung đạt, JavaScript syntax đạt, thêm aria-busy khi lưu. Chờ duyệt các commit đã đề xuất; chưa chạy git commit.
9. CUS-04: giữ tên/email/phone khi server từ chối đăng ký, không giữ mật khẩu; bổ sung ràng buộc UI phù hợp server. Đang kiểm tra validation và reset qua browser/SMTP.


10. CUS-04: bổ sung 3/3 test đạt, PHP lint đăng ký đạt; lỗi null response do điều hướng fragment trong test đã sửa (không phải lỗi backend). Ảnh profile/login-error/reset-success ở CUS-04. Không đổi includes/auth.php.
11. CUS-05: test đo được tương phản chữ nút chỉ 3,94:1, chưa đạt 4,5:1. Đã chỉnh token màu chỉ trong customer.css; đang chạy lại. Lệnh đọc git status/diff bị hệ thống review tự động từ chối thực thi do giới hạn sử dụng, không phải kết luận không an toàn; không đổi config để lách kiểm tra.


12. CUS-05 đo lại 6,32:1; CUS-06 đã chặn khoảng ngân sách đảo bằng inline/aria-invalid, test bổ sung đạt và PHP/JS syntax đạt.
13. CUS-07 đang bổ sung ảnh 18 trang ở 3 viewport, test SQL tổng hợp, báo cáo và mô tả PR. Bỏ mật khẩu cố định trong test, chỉ sinh ngẫu nhiên trong bộ nhớ. Lần chạy hồi quy HTTP gặp lỗi tên biến crypto trong test sau thay đổi này; đã sửa, sẽ chạy lại sau bộ browser để tránh test SQL chạy đồng thời.


14. Hồi quy cuối: HTTP 35/35, browser 103/103, PHP 50/50, JS 8/8, SQL 9/9; ảnh 18 trang × 3 viewport đã đủ. Lần timeout 768px được giữ lại trong bằng chứng, sau tăng timeout điều hướng vẫn giữ assertion đã đạt. Git chỉ đọc chạy lại thành công: đúng nhánh, HEAD không đổi. SHA256 checkout/auth/schema không đổi.
15. Đang tạo Word và kiểm tra render; chưa ghi STATUS: DONE.


16. Word đã tạo và mở lại bằng python-docx (13 bảng, 7 ảnh); bộ render_docx.py không có LibreOffice bundled trên Windows. Đã dùng Word COM chỉ đọc để xuất 16 trang PDF nội bộ; đang xem PNG từng trang trước bàn giao.


17. Khi chụp vùng UI cho Word, preview đã dừng: xác minh ngoài sandbox không có process/cổng lắng nghe. Đã khởi động lại đúng datadir .local/mysql-completion có sẵn, không import lại. Lưu lần lỗi kết nối vào CUS-07/service-stopped-attempt.json; tiếp tục chụp thật.


STATUS: DONE


## Cập nhật yêu cầu ngày 15/10/2026

Hưng cho phép tạo nhánh GitHub và commit, cung cấp danh tính Hưng / hungtran2708@gmail.com. Đã tạo commit local f36a1bc cho 5 tệp core Customer có sẵn. Sau đó Hưng yêu cầu trình Word làm lại trước rồi mới đến GitHub; đã dừng mọi thao tác Git. Chưa push, chưa tạo nhánh remote Customer, chưa mở PR. Không tiếp tục Git trong lượt gửi bản Word này.

Bản Word mới theo mẫu TV3: docs/BAO_CAO_TONG_HOP_TV2_CUSTOMER.docx. TV3 chỉ là tài liệu tham khảo trình bày, không phải yêu cầu triển khai Admin. Dữ liệu báo cáo vẫn là CUS-01..CUS-07 với 5 LOCAL_PASS và 2 BLOCKED; không sao chép số liệu hoặc ảnh Admin. Bản cũ được giữ riêng. Kiểm tra hình thức của bản mới được ghi tại docs/evidence/CUS-07/docx-tv3-style-review.json khi hoàn tất.
