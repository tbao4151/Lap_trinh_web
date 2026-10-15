#!/usr/bin/env bash
# Kiểm thử Admin Operations (Thành viên 3, ADM-01..ADM-07) trên MySQL + PHP thật.
# Chạy sau scripts/verify-r2.sh (đã import schema + seed). Không cần mật khẩu cố định: tài khoản kiểm thử
# owner/admin/staff được tạo qua scripts/create-admin.php với mật khẩu sinh ngẫu nhiên cho mỗi lần chạy.
set -euo pipefail
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_NAME="${DB_NAME:-cas_hoa}"
export DB_HOST DB_PORT DB_USER DB_NAME
export MYSQL_PWD="${DB_PASSWORD:-}"
MYSQL=(mysql --default-character-set=utf8mb4 -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER")
db() { "${MYSQL[@]}" "$DB_NAME" --batch --skip-column-names -e "$1"; }

echo "== Migration tăng dần (001, 002, 003 x2)"
"${MYSQL[@]}" < database/migrations/001_auth.php.sql
"${MYSQL[@]}" < database/migrations/002_inventory_seed.sql
"${MYSQL[@]}" < database/migrations/003_admin_ops.sql
"${MYSQL[@]}" < database/migrations/004_order_inventory_sessions.sql
COUNTS_SQL="SELECT CONCAT_WS(',',(SELECT COUNT(*) FROM product_images),(SELECT COUNT(*) FROM wrapping_colors),(SELECT COUNT(*) FROM discovery_groups),(SELECT COUNT(*) FROM shop_settings),(SELECT COUNT(*) FROM products),(SELECT COUNT(*) FROM inventory_items))"
BEFORE="$(db "$COUNTS_SQL")"
"${MYSQL[@]}" < database/migrations/003_admin_ops.sql
"${MYSQL[@]}" < database/migrations/004_order_inventory_sessions.sql
AFTER="$(db "$COUNTS_SQL")"
[ "$BEFORE" = "$AFTER" ] || { echo "Migration 003 chạy lại làm thay đổi dữ liệu: $BEFORE -> $AFTER"; exit 1; }
for t in product_images discovery_groups delivery_events refund_events shop_settings admin_audit_log product_recipes order_inventory_usage; do
  [ "$(db "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='$t'")" = "1" ] || { echo "Thiếu bảng $t"; exit 1; }
done
[ "$(db "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='$DB_NAME' AND table_name='inventory_transactions' AND column_name IN ('quantity_before','quantity_after','request_token')")" = "3" ] || { echo "Thiếu cột lịch sử kho"; exit 1; }
[ "$(db "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='$DB_NAME' AND table_name='users' AND column_name='session_version'")" = "1" ] || { echo 'Missing session_version'; exit 1; }
echo "Migration 003/004 idempotent: $AFTER"

echo "== Tài khoản kiểm thử qua CLI (mật khẩu ngẫu nhiên)"
gen_password() { python3 -c 'import secrets;print("Ops_"+secrets.token_hex(6)+"!Z9")'; }
SUFFIX="$(python3 -c 'import secrets;print(secrets.token_hex(3))')"
export CAS_OWNER_EMAIL="owner-ops-$SUFFIX@example.test" CAS_OWNER_PASSWORD="$(gen_password)"
export CAS_ADMIN_EMAIL="admin-ops-$SUFFIX@example.test" CAS_ADMIN_PASSWORD="$(gen_password)"
export CAS_STAFF_EMAIL="staff-ops-$SUFFIX@example.test" CAS_STAFF_PASSWORD="$(gen_password)"
CAS_ADMIN_EMAIL="$CAS_OWNER_EMAIL" CAS_ADMIN_PASSWORD="$CAS_OWNER_PASSWORD" CAS_ADMIN_ROLE=owner CAS_ADMIN_NAME="Chủ sở hữu kiểm thử" php scripts/create-admin.php
CAS_ADMIN_ROLE=admin CAS_ADMIN_NAME="Quản trị kiểm thử" php scripts/create-admin.php
CAS_ADMIN_EMAIL="$CAS_STAFF_EMAIL" CAS_ADMIN_PASSWORD="$CAS_STAFF_PASSWORD" CAS_ADMIN_ROLE=staff CAS_ADMIN_NAME="Nhân viên kiểm thử" php scripts/create-admin.php
if CAS_ADMIN_EMAIL="bad-role-$SUFFIX@example.test" CAS_ADMIN_PASSWORD="$CAS_STAFF_PASSWORD" CAS_ADMIN_ROLE=customer php scripts/create-admin.php 2>/dev/null; then
  echo "create-admin.php không được tạo vai trò ngoài staff/admin/owner"; exit 1
fi

echo "== HTTP + MySQL"
case "$(uname -s)" in MINGW*|MSYS*|CYGWIN*) ;; *) export PHP_CLI_SERVER_WORKERS=4 ;; esac
PORT_HTTP="${CAS_OPS_PORT:-8035}"
php -S "127.0.0.1:$PORT_HTTP" >/tmp/cas-admin-ops-http.log 2>&1 &
PID=$!
trap 'kill "$PID" 2>/dev/null || true' EXIT
sleep 2
export CAS_BASE_URL="http://127.0.0.1:$PORT_HTTP/"
export CAS_TEST_REPORT="${CAS_TEST_REPORT:-/tmp/cas-admin-ops-report.json}"
python3 tests/admin/test_admin_ops.py
echo "PASS: Admin Operations — migration 003, 22 route x 5 vai trò, sản phẩm/ảnh, phân loại, kho, giao hàng, hoàn tiền, giấy gói, khám phá, cài đặt, nhân viên/chủ sở hữu, audit."
