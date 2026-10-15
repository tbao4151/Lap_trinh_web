#!/usr/bin/env bash
set -euo pipefail
export MYSQL_PWD="$DB_PASSWORD"
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" < database/migrations/001_auth.php.sql
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" < database/migrations/002_inventory_seed.sql
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" < database/migrations/003_admin_ops.sql
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" < database/migrations/004_order_inventory_sessions.sql
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
 [ "$STATUS" = "200" ] && grep -q 'admin-content' /tmp/r2-admin-page || { echo "Admin page $page failed: $STATUS"; tail -n 50 /tmp/cas-r2-admin-http.log; exit 1; }
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
# Regression: no skipping from confirmed directly to completed, payment stays unchanged.
STATUS=$(curl -s -o /dev/null -w '%{http_code}' -b /tmp/r2-admin-cookie --data-urlencode "csrf=$TOKEN" --data-urlencode "action=order_update" --data-urlencode "id=1" --data-urlencode "status=completed" --data-urlencode "payment_status=paid" "$BASE/admin/don-hang.php")
[ "$STATUS" = "422" ] || { echo "Illegal order status jump was not blocked: $STATUS"; exit 1; }
[ "$(db "SELECT COUNT(*) FROM orders WHERE order_id=1 AND order_status='confirmed' AND payment_status='paid'")" = "1" ] || exit 1

TOKEN=$(curl -fsS -c /tmp/r2-customer-cookie "$BASE/dang-ky.php" | get_token)
STATUS=$(curl -s -o /dev/null -w '%{http_code}' -b /tmp/r2-customer-cookie -c /tmp/r2-customer-cookie --data-urlencode "csrf=$TOKEN" --data-urlencode "full_name=Khach TDTU" --data-urlencode "phone=0922222222" --data-urlencode "email=tdtu-test@example.test" --data-urlencode "password=Customer_2026!" --data-urlencode "confirm_password=Customer_2026!" "$BASE/dang-ky.php")
[ "$STATUS" = "303" ] || { echo "Customer signup failed: $STATUS";exit 1; }
curl -fsS -b /tmp/r2-customer-cookie "$BASE/tai-khoan.php" > /tmp/r2-customer-profile
grep -q 'Khach TDTU' /tmp/r2-customer-profile
curl -fsS -b /tmp/r2-customer-cookie "$BASE/thanh-toan.php" > /tmp/r2-auth-checkout
TOKEN=$(python3 -c 'import re;s=open("/tmp/r2-auth-checkout").read();m=re.search(r"window.CAS_CSRF\s*=\s*\"([a-f0-9]+)\"",s);assert m;print(m.group(1))')
curl -fsS -b /tmp/r2-customer-cookie -X POST --data-urlencode "csrf=$TOKEN" --data-urlencode 'cart=[{"productId":"p001","quantity":1}]' "$BASE/actions/cart.php" > /tmp/r2-auth-session-cart
RESP=$(curl -sS -b /tmp/r2-customer-cookie --data-urlencode "csrf=$TOKEN" --data-urlencode 'cart=[{"productId":"p001","quantity":1}]' --data-urlencode 'customerName=Khach TDTU' --data-urlencode 'customerPhone=0922222222' --data-urlencode 'recipientName=Nguoi nhan test' --data-urlencode 'recipientPhone=0933333333' --data-urlencode 'fulfillment=pickup' --data-urlencode 'receiveDate=2030-01-01' --data-urlencode 'receiveTime=08:00 - 10:00' "$BASE/actions/checkout.php")
CODE=$(printf '%s' "$RESP" | python3 -c 'import json,sys;x=json.load(sys.stdin);assert x["ok"],x;print(x["orderCode"])')
curl -fsS -b /tmp/r2-customer-cookie "$BASE/lich-su-don-hang.php" > /tmp/r2-account-history
grep -q "$CODE" /tmp/r2-account-history

# Verify edit-product, taxonomy, delivery, and inventory operations.
TOKEN=$(curl -fsS -b /tmp/r2-admin-cookie "$BASE/admin/sua-san-pham.php?id=$ID" | get_token)
STATUS=$(curl -s -o /dev/null -w '%{http_code}' -b /tmp/r2-admin-cookie --data-urlencode "csrf=$TOKEN" --data-urlencode "action=product_save" --data-urlencode "id=$ID" --data-urlencode "name=San pham da sua" --data-urlencode "sku=TEST-NEW-123" --data-urlencode "slug=san-pham-test-123" --data-urlencode "price=145000" --data-urlencode "product_type=bouquet" --data-urlencode "category_id=1" --data-urlencode "tone_id=1" --data-urlencode "occasion_id=2" --data-urlencode "image=lam-tinh.png" --data-urlencode "status=active" "$BASE/admin/sua-san-pham.php?id=$ID")
[ "$STATUS" = "303" ] || { echo "Edit product failure: $STATUS";exit 1; }
[ "$(db "SELECT COUNT(*) FROM products WHERE product_id=$ID AND name='San pham da sua' AND price=145000")" = "1" ] || exit 1
TOKEN=$(curl -fsS -b /tmp/r2-admin-cookie "$BASE/admin/phan-loai-bo-loc.php" | get_token)
STATUS=$(curl -s -o /dev/null -w '%{http_code}' -b /tmp/r2-admin-cookie --data-urlencode "csrf=$TOKEN" --data-urlencode "action=taxonomy_save" --data-urlencode "table=categories" --data-urlencode "name=Hoa moi kiem thu" "$BASE/admin/phan-loai-bo-loc.php")
[ "$STATUS" = "303" ] || exit 1
[ "$(db "SELECT COUNT(*) FROM categories WHERE name='Hoa moi kiem thu'")" = "1" ] || exit 1
TOKEN=$(curl -fsS -b /tmp/r2-admin-cookie "$BASE/admin/giao-hang.php" | get_token)
STATUS=$(curl -s -o /dev/null -w '%{http_code}' -b /tmp/r2-admin-cookie --data-urlencode "csrf=$TOKEN" --data-urlencode "action=delivery_update" --data-urlencode "id=1" --data-urlencode "status=out_for_delivery" "$BASE/admin/giao-hang.php")
[ "$STATUS" = "303" ] || exit 1
[ "$(db "SELECT COUNT(*) FROM deliveries WHERE delivery_id=1 AND delivery_status='out_for_delivery'")" = "1" ] || exit 1
[ "$(db "SELECT COUNT(*) FROM inventory_items")" -ge 10 ] || exit 1

# Pricing regression: admin-configured surcharge and delivery fee must be authoritative.
TOKEN=$(curl -fsS -b /tmp/r2-customer-cookie "$BASE/thanh-toan.php" | python3 -c 'import re,sys;s=sys.stdin.read();m=re.search(r"window.CAS_CSRF\s*=\s*\"([a-f0-9]+)\"",s);assert m;print(m.group(1))')
CART_JSON=$(curl -fsS -b /tmp/r2-customer-cookie --data-urlencode "csrf=$TOKEN" --data-urlencode 'cart=[{"productId":"p001","quantity":2,"wrappingId":3}]' "$BASE/actions/cart.php")
printf '%s' "$CART_JSON" | python3 -c 'import json,sys;x=json.load(sys.stdin);assert x["ok"] and x["cart"][0]["wrappingId"]==3,x'
PRICE_RESULT=$(curl -sS -b /tmp/r2-customer-cookie --data-urlencode "csrf=$TOKEN" \
 --data-urlencode "customerName=Khach test gia" --data-urlencode "customerPhone=0922222222" \
 --data-urlencode "recipientName=Nguoi nhan gia" --data-urlencode "recipientPhone=0933333333" \
 --data-urlencode "fulfillment=delivery" --data-urlencode "address=Thu Duc" \
 --data-urlencode "receiveDate=2030-01-01" --data-urlencode "receiveTime=08:00 - 10:00" "$BASE/actions/checkout.php")
PRICE_CODE=$(printf '%s' "$PRICE_RESULT" | python3 -c 'import json,sys;x=json.load(sys.stdin);assert x["ok"],x;print(x["orderCode"])')
[ "$(db "SELECT COUNT(*) FROM orders o JOIN order_items i ON i.order_id=o.order_id WHERE o.order_code='$PRICE_CODE' AND o.subtotal=820000 AND o.shipping_fee=30000 AND o.total_amount=850000 AND i.quantity=2 AND i.unit_price=410000 AND i.line_total=820000")" = "1" ] || { echo "Mismatch wrapping/ship pricing snapshot for $PRICE_CODE"; exit 1; }
[ "$(db "SELECT COUNT(*) FROM deliveries d JOIN orders o ON o.order_id=d.order_id WHERE o.order_code='$PRICE_CODE' AND d.fee=30000")" = "1" ] || exit 1
echo "PASS: wrapping surcharge, configured shipping fee, and SQL price snapshots."

echo "PASS: Admin login/RBAC, 11 pages, product SQL CRUD, order update, customer registration/profile/history."
