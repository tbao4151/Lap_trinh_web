#!/usr/bin/env bash
# Kiểm thử đầu-cuối yêu cầu 2 trên máy có MySQL, PHP, curl và Python 3.
set -euo pipefail
HOST="${DB_HOST:-127.0.0.1}"
PORT="${DB_PORT:-3306}"
USER="${DB_USER:-root}"
PASS="${DB_PASSWORD:-}"
MYSQL="mysql --host=$HOST --port=$PORT --user=$USER"
export MYSQL_PWD="$PASS"
$MYSQL < database/schema.sql
$MYSQL < database/seed.sql
PRODUCTS="$($MYSQL --batch --skip-column-names cas_hoa -e 'SELECT COUNT(*) FROM products')"
[ "$PRODUCTS" -ge 11 ] || { echo "Missing catalog seed"; exit 1; }
TABLES="$($MYSQL --batch --skip-column-names cas_hoa -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='cas_hoa'")"
[ "$TABLES" -ge 17 ] || { echo "Missing tables"; exit 1; }
php -S 127.0.0.1:8033 >/tmp/cas-r2-php.log 2>&1 &
PID=$!
trap 'kill "$PID" 2>/dev/null || true' EXIT
sleep 2
curl -fsS -c /tmp/cas-r2-cookies http://127.0.0.1:8033/thanh-toan.php > /tmp/cas-r2-checkout.html
TOKEN="$(python3 -c 'import re; s=open("/tmp/cas-r2-checkout.html").read(); m=re.search(r"window.CAS_CSRF\s*=\s*\"([a-f0-9]+)\"",s); assert m,"Missing CSRF"; print(m.group(1))')"
curl -fsS -b /tmp/cas-r2-cookies http://127.0.0.1:8033/assets/js/products.php > /tmp/cas-r2-products.js
grep -q 'p001' /tmp/cas-r2-products.js
RESP="$(curl -sS -b /tmp/cas-r2-cookies -c /tmp/cas-r2-cookies -X POST \
  -F "csrf=$TOKEN" \
  -F 'cart=[{"productId":"p001","quantity":2}]' \
  -F 'customerName=Khach kiem thu' -F 'customerPhone=0900000000' \
  -F 'recipientName=Nguoi nhan' -F 'recipientPhone=0911111111' \
  -F 'fulfillment=delivery' -F 'address=Thu Duc, HCM' \
  -F 'receiveDate=2030-01-01' -F 'receiveTime=08:00 - 10:00' \
  http://127.0.0.1:8033/actions/checkout.php)"
CODE="$(printf '%s' "$RESP" | python3 -c 'import json,sys; x=json.load(sys.stdin); assert x["ok"],x; print(x["orderCode"])')"
LOOKUP="$(curl -sS -b /tmp/cas-r2-cookies -X POST -F "csrf=$TOKEN" -F "code=$CODE" -F 'phone=0911111111' http://127.0.0.1:8033/actions/lookup.php)"
printf '%s' "$LOOKUP" | python3 -c 'import json,sys; x=json.load(sys.stdin); assert x["ok"] and x["order"]["total"]==800000,x'
CONTACT="$(curl -sS -b /tmp/cas-r2-cookies -X POST -F "csrf=$TOKEN" -F 'name=Khach hang' -F 'phone=0900000000' -F 'subject=Tu van mau hoa' -F 'message=Toi can tu van mau hoa cho ngay ky niem' http://127.0.0.1:8033/actions/contact.php)"
printf '%s' "$CONTACT" | python3 -c 'import json,sys; x=json.load(sys.stdin); assert x["ok"],x'
curl -fsS http://127.0.0.1:8033/tin-tuc.php > /tmp/cas-r2-news.html
grep -q 'Chọn hoa' /tmp/cas-r2-news.html
curl -fsS 'http://127.0.0.1:8033/chi-tiet-tin-tuc.php?slug=chon-hoa-theo-mau' > /tmp/cas-r2-article.html
grep -q 'Chọn hoa' /tmp/cas-r2-article.html
COUNT="$($MYSQL --batch --skip-column-names cas_hoa -e 'SELECT COUNT(*) FROM orders')"
[ "$COUNT" -ge 1 ]
echo "PASS: 17 SQL tables, 11 products, checkout $CODE, lookup, contact, news."
