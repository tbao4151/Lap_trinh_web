# Mô tả PR dự thảo cho Customer

Chưa mở PR. Nhánh dự kiến: feature/customer-completion → feature/requirement-2-php-mysql.

## Vấn đề và thay đổi

Hoàn thiện kiểm chứng luồng khách hàng đang có: hiển thị lỗi khoảng giá/ngân sách, nhãn sắp xếp, feedback giỏ/checkout, giữ dữ liệu đăng ký khi có lỗi và tăng tương phản. Thêm audit 18 trang, kiểm thử HTTP/browser/SQL, ảnh thật và báo cáo Word.

DONE: 0; LOCAL_PASS: 5; CODE_EXISTS: 0; NOT_DONE: 0; BLOCKED: 2. Tổng trọng số các task đạt local: 19,5/27,5 điểm. Không cộng 5 điểm CUS-02 hoặc 3 điểm CUS-07 khi điều kiện còn thiếu. HTTP/SQL 35/35, browser 103/103, PHP lint 50/50, JavaScript syntax 8/8, SQL tổng hợp 9/9, bổ sung 10 nhóm đều đạt.

## Các phần cần review riêng

Diff checkout/auth đã có trước phiên, không sửa thêm. Gallery và tồn kho thành phẩm cần lead chốt. Xem docs/de-xuat-cho-lead.md. Không gộp mặc nhiên các tệp core vào commit Customer.

## Chia commit và PR theo task

| Task | Commit message đề xuất | Trạng thái commit và PR |
|---|---|---|
| CUS-01 | docs(customer): audit 18 customer pages with local evidence | Chưa commit - chờ duyệt; chưa có PR |
| CUS-02 | fix(customer): clarify catalog sorting and price validation | Chưa commit - chờ duyệt; chưa có PR |
| CUS-03 | fix(customer): improve cart and checkout feedback | Chưa commit - chờ duyệt; chưa có PR |
| CUS-04 | fix(customer): retain signup details and verify account recovery | Chưa commit - chờ duyệt; chưa có PR |
| CUS-05 | fix(customer): improve contrast and keyboard accessibility | Chưa commit - chờ duyệt; chưa có PR |
| CUS-06 | fix(customer): validate recommendation budgets | Chưa commit - chờ duyệt; chưa có PR |
| CUS-07 | test(customer): add regression evidence and handoff report | Chưa commit - chờ duyệt; chưa có PR |

Một lệnh duyệt chỉ áp dụng đúng thao tác: commit task CUS-0X, push hoặc mở PR. Không tự staging/commit/push/PR. Các tệp dùng chung như main-r2.js và customer-forms.js cần chia hunk sau khi được phép; không chia commit hình thức để tăng đóng góp.

## Kiểm chứng

Xem docs/customer-test-report.md, customer-audit.md và docs/evidence/CUS-01..CUS-07. Chưa có CI mới hoặc review của lead. Không viện dẫn CI cũ trên nhánh gốc như CI của thay đổi chưa commit.
