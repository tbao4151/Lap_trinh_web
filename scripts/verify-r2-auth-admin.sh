#!/usr/bin/env bash
set -euo pipefail
export MYSQL_PWD="$DB_PASSWORD"
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" < database/migrations/001_auth.php.sql
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" < database/migrations/002_inventory_seed.sql
export CAS_ADMIN_EMAIL="admin-coursework@example.test"
export CAS_ADMIN_PASSWORD="AdminTest_2026!"
php scripts/create-admin.php
php -S 127.0.0.1:8034 >/tmp/cas-r2-admin-http.log 2>&1 &
PID=$!
trap 'kill "$PID" 2>/dev/null || true' EXIT
sleep 2
BASE="http://127.0.0.1:8034"
get_token() { python3 -c 'import re,sys;s=sys.stdin.read();m=re.search(r"name=\"csrf\" value=\"([a-f0-9]+)\"",s);assert m,"CSRF missing";print(m.group(1))'; }
db() { mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" cas_hoa --batch --skip-column-names -e "$1"; }
STATUS=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/admin/index.php")
[ "$STATUS" = "303" ] || { echo "Admin not protected";exit 1; }
TOKEN=$(curl -fsS -c /tmp/r2-admin-cookie "$BASE/admin/dang-nhap.php" | get_token)
STATUS=$(curl -s -o /dev/null -w '%{http_code}' -b /tmp/r2-admin-cookie -c /tmp/r2-admin-cookie --data-urlencode "csrf=$TOKEN" --data-urlencode "email=$CAS_ADMIN_EMAIL" --data-urlencode "password=$CAS_ADMIN_PASSWORD" "$BASE/admin/dang-nhap.php")
[ "$STATUS" = "303" ] || { echo "Admin login failed: $STATUS";exit 1; }
for page in index don-hang giao-hang khach-hang san-pham them-san-pham quan-ly-nhanh phan-loai-bo-loc kho-tong bao-cao; do
 STATUS=$(curl -s -o /tmp/r2-admin-page -w '%{http_code}' -b /tmp/r2-admin-cookie "$BASE/admin/$page.php")
 [ "$STATUS" = "200" ] && grep -q 'admin-content' /tmp/r2-admin-page || { echo "Admin page $page failed: $STATUS";exit 1; }
done
curl -fsS -b /tmp/r2-admin-cookie "$BASE/admin/sua-san-pham.php?id=1" | grep -q 'Lưu sản phẩm'
TOKEN=$(curl -fsS -b /tmp/r2-admin-cookie "$BASE/admin/them-san-pham.php" | get_token)
STATUS=$(curl -s -o /dev/null -w '%{http_code}' -b /tmp/r2-admin-cookie --data-urlencode "csrf=$TOKEN" --data-urlencode "action=product_save" --data-urlencode "name=San pham kiem thu" --data-urlencode "sku=TEST-NEW-123" --data-urlencode "slug=san-pham-test-123" --data-urlencode "price=125000" --data-urlencode "product_type=bouquet" --data-urlencode "category_id=1" --data-urlencode "tone_id=1" --data-urlencode "occasion_id=2" --data-urlencode "image=lam-tinh.png" --data-urlencode "status=active" "$BASE/admin/them-san-pham.php")
[ "$STATUS" = "303" ] || { echo "Product save failed: $STATUS";exit 1; }
ID=$(db "SELECT product_id FROM products WHERE sku='TEST-NEW-123'")
[ -n "$ID" ] || exit 1
TOKEN=$(curl -fsS -b /tmp/r2-admin-cookie "$BASE/admin/quan-ly-nhanh.php" | get_token)
STATUS=$(curl -s -o /dev/null -w '%{http_code}' -b /tmp/r2-admin-cookie --data-urlencode "csrf=$TOKEN" --data-urlencode "action=quick_save" --data-urlencode "id=$ID" --data-urlencode "price=135000" --data-urlencode "status=hidden" "$BASE/admin/quan-ly-nhanh.php")
[ "$STATUS" = "303" ] || exit 1
[ "$(db "SELECT COUNT(*) FROM products WHERE product_id=$ID AND price=135000 AND status='hidden'")" = "1" ] || exit 1
TOKEN=$(curl -fsS -b /tmp/r2-admin-cookie "$BASE/admin/don-hang.php" | get_token)
STATUS=$(curl -s -o /dev/null -w '%{http_code}' -b /tmp/r2-admin-cookie --data-urlencode "csrf=$TOKEN" --data-urlencode "action=order_update" --data-urlencode "id=1" --data-urlencode "status=confirmed" --data-urlencode "payment_status=paid" "$BASE/admin/don-hang.php")
[ "$STATUS" = "303" ] || exit 1
[ "$(db "SELECT COUNT(*) FROM orders WHERE order_id=1 AND order_status='confirmed' AND payment_status='paid'")" = "1" ] || exit 1
TOKEN=$(curl -fsS -c /tmp/r2-customer-cookie "$BASE/dang-ky.php" | get_token)
STATUS=$(curl -s -o /dev/null -w '%{http_code}' -b /tmp/r2-customer-cookie -c /tmp/r2-customer-cookie --data-urlencode "csrf=$TOKEN" --data-urlencode "full_name=Khach TDTU" --data-urlencode "phone=0922222222" --data-urlencode "email=tdtu-test@example.test" --data-urlencode "password=Customer_2026!" --data-urlencode "confirm_password=Customer_2026!" "$BASE/dang-ky.php")
[ "$STATUS" = "303" ] || { echo "Customer signup failed: $STATUS";exit 1; }
curl -fsS -b /tmp/r2-customer-cookie "$BASE/tai-khoan.php" > /tmp/r2-customer-profile
grep -q 'Khach TDTU' /tmp/r2-customer-profile
curl -fsS -b /tmp/r2-customer-cookie "$BASE/thanh-toan.php" > /tmp/r2-auth-checkout
TOKEN=$(python3 -c 'import re;s=open("/tmp/r2-auth-checkout").read();m=re.search(r"window.CAS_CSRF\s*=\s*\"([a-f0-9]+)\"",s);assert m;print(m.group(1))')
RESP=$(curl -sS -b /tmp/r2-customer-cookie --data-urlencode "csrf=$TOKEN" --data-urlencode 'cart=[{"productId":"p001","quantity":1}]' --data-urlencode 'customerName=Khach TDTU' --data-urlencode 'customerPhone=0922222222' --data-urlencode 'recipientName=Nguoi nhan test' --data-urlencode 'recipientPhone=0933333333' --data-urlencode 'fulfillment=pickup' --data-urlencode 'receiveDate=2030-01-01' --data-urlencode 'receiveTime=08:00 - 10:00' "$BASE/actions/checkout.php")
CODE=$(printf '%s' "$RESP" | python3 -c 'import json,sys;x=json.load(sys.stdin);assert x["ok"],x;print(x["orderCode"])')
curl -fsS -b /tmp/r2-customer-cookie "$BASE/lich-su-don-hang.php" > /tmp/r2-account-history
grep -q "$CODE" /tmp/r2-account-history
echo "PASS: Admin login/RBAC, 11 pages, product SQL CRUD, order update, customer registration/profile/history."
