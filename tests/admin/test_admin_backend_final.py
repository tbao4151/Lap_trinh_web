"""Kiểm thử hồi quy TV3 — BackEnd Admin (ADM-BE-01..04): đối soát doanh thu, luồng đơn/giao/kho, hoàn tiền.

Bộ này ĐỘC LẬP với tests/admin/test_admin_ops.py (đếm kết quả riêng). Chỉ dùng thư viện chuẩn Python,
tái sử dụng HTTP client / truy vấn mysql CLI của test_admin_ops.py, tự tạo tài khoản owner/admin/staff
bằng scripts/create-admin.php với mật khẩu ngẫu nhiên.

CHỈ chạy trên database thử riêng (mặc định từ chối cổng 3306 trừ khi đặt CAS_ALLOW_PORT_3306=1, ví dụ trong CI).

Biến môi trường:
  CAS_BASE_URL                                   vd http://127.0.0.1:8036/
  DB_HOST DB_PORT DB_USER DB_PASSWORD DB_NAME    kết nối mysql CLI (MYSQL_BIN tùy chọn)
  PHP_BIN                                        (tùy chọn) đường dẫn php để chạy scripts/create-admin.php
  CAS_TEST_REPORT                                (tùy chọn) ghi kết quả JSON
"""
import json
import os
import re
import secrets
import subprocess
import sys
import threading
import time

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import test_admin_ops as ops  # noqa: E402  (HTTP client, sql(), q() dùng chung)

Client, sql, sql_int, q, flash = ops.Client, ops.sql, ops.sql_int, ops.q, ops.flash
ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
RUN = secrets.token_hex(3)
RESULTS = []
TODAY = time.strftime('%Y-%m-%d')
TOMORROW = time.strftime('%Y-%m-%d', time.localtime(time.time() + 86400))


def check(name, ok, detail=''):
    RESULTS.append({'name': name, 'ok': bool(ok), 'detail': str(detail)[:400]})
    print(('PASS ' if ok else 'FAIL ') + name + ('' if ok else '  -> ' + str(detail)[:400]), flush=True)
    return ok


def money(text):
    digits = re.sub(r'[^\d-]', '', text or '')
    return int(digits) if digits not in ('', '-') else None


def metric(html, pattern):
    m = re.search(pattern, html, re.S)
    return money(m.group(1)) if m else None


# ------------------------------------------------------------------ chuẩn bị
def create_account(role):
    email = f'{role}-be-{RUN}@example.test'
    password = 'Be_' + secrets.token_hex(6) + '!Q7'
    env = dict(os.environ, CAS_ADMIN_EMAIL=email, CAS_ADMIN_PASSWORD=password, CAS_ADMIN_ROLE=role, CAS_ADMIN_NAME=f'{role} BE {RUN}')
    out = subprocess.run([os.environ.get('PHP_BIN', 'php'), os.path.join(ROOT, 'scripts', 'create-admin.php')],
                         env=env, capture_output=True, text=True, encoding='utf-8')
    if out.returncode:
        raise RuntimeError(f'Không tạo được tài khoản {role}: {out.stderr}')
    return email, password


def place_order(product, method='pickup', qty=1):
    """Đặt đơn như khách qua actions/checkout.php (không chèn SQL trực tiếp)."""
    guest = Client()
    guest.get('thanh-toan.php')
    cart = json.dumps([{'productId': product, 'quantity': qty}])
    guest.post('actions/cart.php', {'cart': cart}, files=[])
    data = {'cart': cart, 'customerName': 'Khach BE ' + RUN, 'customerPhone': '0914' + str(secrets.randbelow(900000) + 100000),
            'recipientName': 'Nguoi nhan BE', 'recipientPhone': '0935' + str(secrets.randbelow(900000) + 100000),
            'fulfillment': method, 'receiveDate': TOMORROW, 'receiveTime': '10:00 - 12:00'}
    if method == 'delivery':
        data['address'] = 'Linh Xuan, Thu Duc'
    status, body, _ = guest.post('actions/checkout.php', data, files=[])
    result = json.loads(body)
    assert result.get('ok'), (status, result)
    code = result['orderCode']
    return code, sql_int(f'SELECT order_id FROM orders WHERE order_code={q(code)}')


def set_order(client, oid, status, payment, reason=''):
    return client.post('admin/don-hang.php', {'action': 'order_update', 'id': oid, 'status': status, 'payment_status': payment, 'reason': reason})[0]


def complete_pickup(admin, oid, payment='paid'):
    for step in ('confirmed', 'preparing', 'ready', 'completed'):
        st = set_order(admin, oid, step, payment)
        assert st == 303, f'{oid} -> {step}: {st}'


def refund(staff, admin, code, amount, record=True, approve=True):
    token = secrets.token_hex(16)
    st, html, _ = staff.post('admin/hoan-tien.php', {'action': 'refund_create', 'order_code': code, 'amount': amount,
                                                     'reason': 'Kiểm thử hoàn ' + RUN, 'request_token': token})
    rid = sql_int(f'SELECT refund_id FROM refunds WHERE request_token={q(token)}')
    if st == 303 and approve:
        assert admin.post('admin/hoan-tien.php', {'action': 'refund_transition', 'id': rid, 'status': 'approved', 'note': 'Duyệt BE'})[0] == 303
        if record:
            assert admin.post('admin/hoan-tien.php', {'action': 'refund_transition', 'id': rid, 'status': 'recorded', 'reference': f'BE{RUN}{rid}'})[0] == 303
    return st, rid


def make_item(admin, label, opening, cost):
    name = f'{label} {RUN}'
    st, html, _ = admin.post('admin/kho-hoa.php', {'action': 'item_save', 'name': name, 'category': 'hoa', 'unit': 'cành',
                                                   'min_quantity': 0, 'unit_cost': cost, 'status': 'active', 'opening_quantity': opening})
    assert st == 303, (st, flash(html))
    return sql_int(f'SELECT item_id FROM inventory_items WHERE name={q(name)}'), name


def use_only_recipe(admin, product_id, item, quantity):
    """Đưa công thức của sản phẩm về đúng một nguyên liệu kiểm thử (qua màn hình Định mức, không sửa SQL)."""
    others = sql(f'SELECT item_id FROM product_recipes WHERE product_id={product_id} AND item_id<>{item}')
    for other in filter(None, others.split('\n')):
        admin.post(f'admin/cong-thuc-hoa.php?product_id={product_id}', {'action': 'recipe_delete', 'product_id': product_id, 'item_id': other})
    st, _, _ = admin.post(f'admin/cong-thuc-hoa.php?product_id={product_id}', {'action': 'recipe_save', 'product_id': product_id, 'item_id': item, 'quantity': quantity})
    assert st == 303 and sql(f'SELECT GROUP_CONCAT(item_id) FROM product_recipes WHERE product_id={product_id}') == str(item)


# Công thức tham chiếu viết ĐỘC LẬP với code PHP (subquery tương quan thay vì LEFT JOIN tổng hợp):
# doanh thu thuần = tiền đơn hoàn tất + đã thu (kể cả đã hoàn một phần / toàn bộ) − tổng hoàn tiền đã ghi nhận, không âm.
REF_NET = """GREATEST(o.total_amount - COALESCE((SELECT SUM(x.amount) FROM refunds x WHERE x.order_id=o.order_id AND x.status='recorded'),0),0)"""
REF_ELIGIBLE = "o.order_status='completed' AND o.payment_status IN ('paid','partially_refunded','refunded')"


def ref_net_all():
    return sql_int(f'SELECT COALESCE(SUM({REF_NET}),0) FROM orders o WHERE {REF_ELIGIBLE}')


def ref_net_order(oid):
    return sql_int(f'SELECT CASE WHEN {REF_ELIGIBLE} THEN {REF_NET} ELSE 0 END FROM orders o WHERE o.order_id={oid}')


def dashboard_revenue(client):
    st, html, _ = client.get('admin/index.php')
    return st, metric(html, r'<span>Doanh thu[^<]*</span><strong>([^<]+)</strong>'), html


# ------------------------------------------------------------------ ADM-BE-01 doanh thu
def test_revenue(admin, staff, item):
    product = 9  # p009 — công thức đã đưa về đúng 1 nguyên liệu kiểm thử
    orders = {}
    for key in ('paid', 'partial', 'full', 'unpaid', 'cancelled', 'legacy'):
        orders[key] = place_order('p009')
    totals = {k: sql_int(f'SELECT total_amount FROM orders WHERE order_id={oid}') for k, (_, oid) in orders.items()}

    complete_pickup(admin, orders['paid'][1])
    complete_pickup(admin, orders['partial'][1])
    complete_pickup(admin, orders['full'][1])
    complete_pickup(admin, orders['unpaid'][1], payment='unpaid')
    complete_pickup(admin, orders['legacy'][1])
    assert set_order(admin, orders['cancelled'][1], 'confirmed', 'paid') == 303
    assert set_order(admin, orders['cancelled'][1], 'cancelled', 'paid') == 303

    # Nhiều bản ghi refunds cho cùng một đơn
    refund(staff, admin, orders['partial'][0], 50000)
    refund(staff, admin, orders['partial'][0], 30000)
    refund(staff, admin, orders['full'][0], 100000)
    refund(staff, admin, orders['full'][0], totals['full'] - 100000)
    # Đơn "cũ" chưa có snapshot giá vốn (mô phỏng dữ liệu trước migration 005)
    sql(f"UPDATE order_inventory_usage SET unit_cost_snapshot=NULL, cost_amount_snapshot=NULL WHERE order_id={orders['legacy'][1]}")

    pay = {k: sql(f'SELECT payment_status FROM orders WHERE order_id={oid}') for k, (_, oid) in orders.items()}
    check('Hoàn tiền nhiều lần cập nhật đúng trạng thái thanh toán (partially_refunded / refunded)',
          pay['partial'] == 'partially_refunded' and pay['full'] == 'refunded' and pay['paid'] == 'paid', pay)

    expected = {'paid': totals['paid'], 'partial': totals['partial'] - 80000, 'full': 0, 'unpaid': 0, 'cancelled': 0, 'legacy': totals['legacy']}
    actual = {k: ref_net_order(oid) for k, (_, oid) in orders.items()}
    check('SQL đối chiếu 4 tình huống: đã thanh toán / hoàn một phần / hoàn toàn bộ (=0, không âm) / chưa thanh toán & đã hủy (=0)',
          actual == expected, f'expected={expected} actual={actual}')

    ids = ','.join(str(orders[k][1]) for k in ('partial', 'full'))
    naive = sql_int(f"SELECT COALESCE(SUM(o.total_amount),0) FROM orders o JOIN refunds r ON r.order_id=o.order_id AND r.status='recorded' WHERE o.order_id IN ({ids})")
    app_sql = sql_int(f"""SELECT COALESCE(SUM(o.total_amount),0) FROM orders o
        LEFT JOIN (SELECT order_id,SUM(amount) refunded FROM refunds WHERE status='recorded' GROUP BY order_id) r ON r.order_id=o.order_id
        WHERE o.order_id IN ({ids})""")
    check('JOIN thẳng bảng refunds (2 dòng/đơn) nhân đôi tiền đơn; subquery tổng hợp không nhân đôi',
          naive == 2 * (totals['partial'] + totals['full']) and app_sql == totals['partial'] + totals['full'], f'naive={naive} grouped={app_sql}')

    reference = ref_net_all()
    st, dash, _ = dashboard_revenue(admin)
    check('Dashboard: doanh thu thuần sau hoàn khớp SQL tham chiếu (đơn hoàn một phần được tính phần còn lại)',
          st == 200 and dash == reference, f'status={st} dashboard={dash} sql={reference}')

    st, html, _ = admin.get(f'admin/bao-cao.php?start=2000-01-01&end={TODAY}')
    report = metric(html, r'<span>Doanh thu thuần[^<]*</span><strong>([^<]+)</strong>')
    check('Báo cáo doanh thu (toàn kỳ) = Dashboard = SQL tham chiếu, cùng nhãn "Doanh thu thuần"',
          st == 200 and report == dash == reference, f'status={st} report={report} dashboard={dash} sql={reference}')

    # Lợi nhuận gộp (loi-nhuan.php) trong ngày hôm nay, so với SQL tham chiếu độc lập
    window = f"o.created_at >= {q(TODAY)} AND o.created_at < DATE_ADD({q(TODAY)}, INTERVAL 1 DAY)"
    usage = """(SELECT COUNT(*) FROM order_inventory_usage u WHERE u.order_id=o.order_id AND u.restored_at IS NULL)"""
    missing = """(SELECT COUNT(*) FROM order_inventory_usage u WHERE u.order_id=o.order_id AND u.restored_at IS NULL AND u.cost_amount_snapshot IS NULL)"""
    cost = """(SELECT COALESCE(SUM(u.cost_amount_snapshot),0) FROM order_inventory_usage u WHERE u.order_id=o.order_id AND u.restored_at IS NULL)"""
    net_today = sql_int(f'SELECT COALESCE(SUM({REF_NET}),0) FROM orders o WHERE {REF_ELIGIBLE} AND {window}')
    known = sql_int(f'SELECT COALESCE(SUM({REF_NET}),0) FROM orders o WHERE {REF_ELIGIBLE} AND {window} AND {usage}>0 AND {missing}=0')
    material = sql_int(f'SELECT COALESCE(SUM({cost}),0) FROM orders o WHERE {REF_ELIGIBLE} AND {window} AND {usage}>0 AND {missing}=0')
    unknown = sql_int(f'SELECT COUNT(*) FROM orders o WHERE {REF_ELIGIBLE} AND {window} AND ({usage}=0 OR {missing}>0)')
    eligible = sql_int(f'SELECT COUNT(*) FROM orders o WHERE {REF_ELIGIBLE} AND {window}')
    st, html, _ = admin.get(f'admin/loi-nhuan.php?from={TODAY}&to={TODAY}&group=day')
    ui = {k: metric(html, rf'cas-profit-metric">{label}<strong>([^<]+)</strong>') for k, label in (
        ('net', 'Doanh thu thuần[^<]*'), ('known', 'Doanh thu có đủ giá vốn'), ('cost', 'Giá vốn nguyên liệu'),
        ('profit', r'Lợi nhuận gộp \(đủ giá vốn\)'), ('unknown', 'Đơn chưa đủ giá vốn'))}
    exp = {'net': net_today, 'known': known, 'cost': material, 'profit': known - material, 'unknown': unknown}
    check('Lợi nhuận gộp: doanh thu thuần, giá vốn snapshot, lợi nhuận gộp, số đơn thiếu giá vốn khớp SQL',
          st == 200 and ui == exp and unknown >= 1, f'status={st} ui={ui} sql={exp}')
    shown = metric(html, r'cas-profit-metric">Số đơn hoàn tất / đã thanh toán<strong>([^<]+)</strong>')
    check('Lợi nhuận gộp: ô "Số đơn hoàn tất / đã thanh toán" bằng số đơn hợp lệ trong SQL (không cố định 0)',
          shown == eligible and eligible >= 4, f'ui={shown} sql={eligible}')
    check('Đơn thiếu snapshot giá vốn không bị suy ra từ giá hiện tại (không vào doanh thu có giá vốn)',
          known <= net_today - totals['legacy'] and 'không phải lợi nhuận ròng' in html, f'known={known} net={net_today}')

    # Yêu cầu hoàn chưa ghi nhận không được trừ doanh thu; vẫn giữ chỗ hạn mức hoàn
    before = dashboard_revenue(admin)[1]
    st, pending = refund(staff, admin, orders['paid'][0], 20000, approve=False)
    after = dashboard_revenue(admin)[1]
    check('Yêu cầu hoàn đang chờ duyệt không làm giảm doanh thu, thanh toán vẫn "paid"',
          st == 303 and before == after and sql(f"SELECT payment_status FROM orders WHERE order_id={orders['paid'][1]}") == 'paid', f'{before} -> {after}')
    st, _ = refund(staff, admin, orders['paid'][0], totals['paid'] - 20000 + 1000, approve=False)
    check('Chặn hoàn vượt khi đã có yêu cầu đang mở (tính cả yêu cầu chờ duyệt) -> 422', st == 422, st)
    admin.post('admin/hoan-tien.php', {'action': 'refund_transition', 'id': pending, 'status': 'rejected', 'note': 'Từ chối sau kiểm thử'})
    check('Từ chối yêu cầu: không đổi doanh thu', dashboard_revenue(admin)[1] == before)

    st, _ = refund(staff, admin, orders['full'][0], 1000, approve=False)
    check('Đơn đã hoàn toàn bộ không tạo thêm yêu cầu hoàn -> 422', st == 422, st)
    st, _ = refund(staff, admin, orders['partial'][0], totals['partial'] - 80000 + 1000, approve=False)
    check('Đơn hoàn một phần: chặn yêu cầu vượt phần còn lại -> 422', st == 422, st)
    st, _ = refund(staff, admin, orders['unpaid'][0], 10000, approve=False)
    check('Đơn chưa thanh toán không được hoàn -> 422', st == 422, st)
    return orders


# ------------------------------------------------------------------ ADM-BE-03 quyền, CSRF, audit
def test_refund_guards(admin, staff, orders):
    paid_code, paid_id = orders['paid']
    st = set_order(admin, paid_id, 'completed', 'refunded')
    check('Admin không được đặt "refunded" trực tiếp ở trang Đơn hàng -> 422',
          st == 422 and sql(f'SELECT payment_status FROM orders WHERE order_id={paid_id}') == 'paid', st)
    _, unpaid_id = orders['unpaid']
    st = set_order(staff, unpaid_id, 'completed', 'paid')
    check('Nhân viên không được đổi trạng thái thanh toán -> 422',
          st == 422 and sql(f'SELECT payment_status FROM orders WHERE order_id={unpaid_id}') == 'unpaid', st)

    count = sql_int('SELECT COUNT(*) FROM refunds')
    st, _, _ = staff.request('admin/hoan-tien.php', {'action': 'refund_create', 'order_code': paid_code, 'amount': 10000,
                                                     'reason': 'Thiếu CSRF', 'request_token': secrets.token_hex(16)})
    check('POST hoàn tiền thiếu CSRF -> 403, không tạo bản ghi', st == 403 and sql_int('SELECT COUNT(*) FROM refunds') == count, st)

    st, rid = refund(staff, admin, paid_code, 10000, approve=False)
    denied_before = sql_int("SELECT COUNT(*) FROM admin_audit_log WHERE action='refund_review' AND outcome='denied'")
    st2, _, _ = staff.post('admin/hoan-tien.php', {'action': 'refund_transition', 'id': rid, 'status': 'approved'})
    check('Nhân viên tự duyệt hoàn -> 403, có audit "denied", yêu cầu vẫn chờ duyệt',
          st == 303 and st2 == 403 and sql(f'SELECT status FROM refunds WHERE refund_id={rid}') == 'requested'
          and sql_int("SELECT COUNT(*) FROM admin_audit_log WHERE action='refund_review' AND outcome='denied'") == denied_before + 1, f'{st} {st2}')
    admin.post('admin/hoan-tien.php', {'action': 'refund_transition', 'id': rid, 'status': 'rejected', 'note': 'Dọn dữ liệu kiểm thử'})

    partial_refunds = sql(f"SELECT GROUP_CONCAT(refund_id) FROM refunds WHERE order_id={orders['partial'][1]} AND status='recorded'")
    logged = sql_int(f"SELECT COUNT(*) FROM admin_audit_log WHERE action='refund_recorded' AND entity_id IN ({partial_refunds})")
    leaks = sql_int("""SELECT COUNT(*) FROM admin_audit_log WHERE detail LIKE '%password%' OR detail LIKE '%csrf%'
        OR detail LIKE '%request_token%' OR detail LIKE '%$2y$%' OR detail LIKE '%$argon2%'""")
    check('Audit ghi từng lần ghi nhận hoàn; không chứa mật khẩu, hash, CSRF hay token phiếu', logged == 2 and leaks == 0, f'logged={logged} leaks={leaks}')


# ------------------------------------------------------------------ ADM-BE-02 luồng đơn / giao / kho
def test_order_flow(admin, staff, item):
    code, oid = place_order('p009')
    assert set_order(admin, oid, 'confirmed', 'paid') == 303
    st = set_order(admin, oid, 'completed', 'paid')
    check('Nhảy trạng thái confirmed -> completed bị chặn 422', st == 422 and sql(f'SELECT order_status FROM orders WHERE order_id={oid}') == 'confirmed', st)
    stock = sql(f'SELECT quantity FROM inventory_items WHERE item_id={item}')
    assert set_order(admin, oid, 'preparing', 'paid') == 303 and set_order(admin, oid, 'ready', 'paid') == 303
    st = set_order(admin, oid, 'cancelled', 'paid')
    check('Hủy đơn đã sẵn sàng không ghi lý do -> 422, không hoàn kho',
          st == 422 and sql(f'SELECT order_status FROM orders WHERE order_id={oid}') == 'ready'
          and sql_int(f'SELECT COUNT(*) FROM order_inventory_usage WHERE order_id={oid} AND restored_at IS NOT NULL') == 0, st)
    assert set_order(admin, oid, 'completed', 'paid') == 303
    after_complete = sql(f'SELECT quantity FROM inventory_items WHERE item_id={item}')
    st = set_order(admin, oid, 'cancelled', 'paid', 'Thử hủy đơn đã hoàn tất')
    check('Đơn hoàn tất là trạng thái cuối: hủy -> 422, không hoàn kho',
          st == 422 and sql(f'SELECT quantity FROM inventory_items WHERE item_id={item}') == after_complete and float(after_complete) == float(stock) - 2, st)

    # Đơn giao hàng: Đang giao -> Sẵn sàng ở trang Đơn hàng phải đồng bộ lịch giao, không làm đơn bị kẹt
    code, oid = place_order('p009', 'delivery')
    for step in ('confirmed', 'preparing', 'ready'):
        assert set_order(admin, oid, step, 'paid') == 303
    did = sql_int(f'SELECT delivery_id FROM deliveries WHERE order_id={oid}')
    st, _, _ = staff.post('admin/giao-hang.php', {'action': 'delivery_update', 'id': did, 'status': 'out_for_delivery'})
    assert st == 303 and sql(f'SELECT order_status FROM orders WHERE order_id={oid}') == 'delivering'
    st = set_order(admin, oid, 'cancelled', 'paid', 'Huỷ khi đang giao')
    check('Không hủy đơn đang giao trực tiếp (phải quay lại trước) -> 422', st == 422, st)
    events = sql_int(f'SELECT COUNT(*) FROM delivery_events WHERE delivery_id={did}')
    st = set_order(admin, oid, 'ready', 'paid', 'Shipper báo hỏng xe')
    state = sql(f'SELECT CONCAT(o.order_status,"/",d.delivery_status) FROM orders o JOIN deliveries d ON d.order_id=o.order_id WHERE o.order_id={oid}')
    check('Đang giao -> Sẵn sàng (trang Đơn hàng) đồng bộ lịch giao về "Chưa giao" và ghi lịch sử giao',
          st == 303 and state == 'ready/not_delivered' and sql_int(f'SELECT COUNT(*) FROM delivery_events WHERE delivery_id={did}') == events + 1,
          f'status={st} state={state}')
    st, html, _ = staff.post('admin/giao-hang.php', {'action': 'delivery_update', 'id': did, 'status': 'out_for_delivery'})
    st2, html2, _ = staff.post('admin/giao-hang.php', {'action': 'delivery_update', 'id': did, 'status': 'delivered'})
    check('Sau khi quay lại, đơn xuất giao lại và hoàn tất được (không bị kẹt)',
          st == 303 and st2 == 303 and sql(f'SELECT order_status FROM orders WHERE order_id={oid}') == 'completed',
          f'{st} {flash(html)} / {st2} {flash(html2)}')


def test_inventory_integrity(admin, item, orders):
    oid = orders['paid'][1]
    snap_before = sql(f'SELECT unit_cost_snapshot, cost_amount_snapshot FROM order_inventory_usage WHERE order_id={oid} AND item_id={item}')
    name = sql(f'SELECT name FROM inventory_items WHERE item_id={item}')
    st, html, _ = admin.post('admin/kho-hoa.php', {'action': 'item_save', 'item_id': item, 'name': name, 'category': 'hoa', 'unit': 'cành',
                                                   'min_quantity': 0, 'unit_cost': 99000, 'status': 'active'})
    snap_after = sql(f'SELECT unit_cost_snapshot, cost_amount_snapshot FROM order_inventory_usage WHERE order_id={oid} AND item_id={item}')
    check('Đổi giá nhập nguyên liệu không làm đổi snapshot giá vốn của đơn đã xuất kho',
          st == 303 and snap_before == snap_after == '10000.00\t20000.00', f'{st} {snap_before} -> {snap_after}')

    rows = sql(f'SELECT quantity, quantity_before, quantity_after FROM inventory_transactions WHERE item_id={item} ORDER BY transaction_id')
    ledger = [tuple(float(x) for x in r.split('\t')) for r in rows.split('\n') if r]
    chained = all(abs(b + d - a) < 0.001 for d, b, a in ledger) and all(abs(ledger[i][2] - ledger[i + 1][1]) < 0.001 for i in range(len(ledger) - 1))
    current = float(sql(f'SELECT quantity FROM inventory_items WHERE item_id={item}'))
    check('Sổ kho liên tục: trước + biến động = sau, nối tiếp từng phiếu, tổng biến động = tồn hiện tại, không âm',
          chained and abs(sum(d for d, _, _ in ledger) - current) < 0.001 and abs(ledger[-1][2] - current) < 0.001 and min(a for _, _, a in ledger) >= 0,
          f'ledger={ledger} current={current}')
    usage = sql_int(f"SELECT COALESCE(SUM(u.quantity),0) FROM order_inventory_usage u WHERE u.item_id={item} AND u.restored_at IS NULL")
    out = -sql_int(f"SELECT COALESCE(SUM(quantity),0) FROM inventory_transactions WHERE item_id={item} AND transaction_type='out'")
    back = sql_int(f"SELECT COALESCE(SUM(quantity),0) FROM inventory_transactions WHERE item_id={item} AND transaction_type='in' AND note LIKE 'Hoàn nguyên liệu%'")
    check('Xuất kho theo đơn = lượng đã dùng chưa hoàn (snapshot order_inventory_usage)', out - back == usage, f'out={out} back={back} usage={usage}')


def test_concurrent_prepare(admin_email, admin_password):
    """Hai yêu cầu chuyển 'preparing' cùng lúc tranh 2 cành cuối: chỉ 1 đơn được xuất kho, tồn không âm."""
    a = ops.login_admin(admin_email, admin_password)
    b = ops.login_admin(admin_email, admin_password)
    scarce, _ = make_item(a, 'NL hiếm', 2, 5000)
    use_only_recipe(a, 10, scarce, '2')
    first = place_order('p010')[1]
    second = place_order('p010')[1]
    for oid in (first, second):
        assert set_order(a, oid, 'confirmed', 'paid') == 303
    a.get('admin/don-hang.php')
    b.get('admin/don-hang.php')
    results = {}

    def worker(client, oid):
        results[oid] = set_order(client, oid, 'preparing', 'paid')
    threads = [threading.Thread(target=worker, args=(a, first)), threading.Thread(target=worker, args=(b, second))]
    [t.start() for t in threads]
    [t.join() for t in threads]
    stock = sql(f'SELECT quantity FROM inventory_items WHERE item_id={scarce}')
    statuses = sorted(sql(f'SELECT order_status FROM orders WHERE order_id IN ({first},{second})').split('\n'))
    used = sql_int(f'SELECT COUNT(DISTINCT order_id) FROM order_inventory_usage WHERE item_id={scarce}')
    check('Chuẩn bị đồng thời 2 đơn khi chỉ đủ tồn cho 1: đúng 1 đơn trừ kho, đơn kia rollback, tồn = 0',
          sorted(results.values()) == [303, 422] and float(stock) == 0 and statuses == ['confirmed', 'preparing'] and used == 1,
          f'results={results} stock={stock} statuses={statuses} used={used}')


def main():
    port = os.environ.get('DB_PORT', '3306')
    if port == '3306' and os.environ.get('CAS_ALLOW_PORT_3306') != '1':
        print('NOT_RUN: từ chối chạy trên cổng 3306 (database có thể chứa dữ liệu thật). Dùng DB thử riêng hoặc đặt CAS_ALLOW_PORT_3306=1 trong CI.')
        sys.exit(2)
    owner_email, owner_password = create_account('owner')
    admin_email, admin_password = create_account('admin')
    staff_email, staff_password = create_account('staff')
    admin = ops.login_admin(admin_email, admin_password)
    staff = ops.login_admin(staff_email, staff_password)
    ops.login_admin(owner_email, owner_password)

    item, _ = make_item(admin, 'NL đối soát', 100, 10000)
    use_only_recipe(admin, 9, item, '2')

    print('## ADM-BE-01 Đối soát doanh thu & lợi nhuận gộp', flush=True)
    orders = test_revenue(admin, staff, item)
    print('## ADM-BE-03 Hoàn tiền: quyền, CSRF, audit', flush=True)
    test_refund_guards(admin, staff, orders)
    print('## ADM-BE-02 Luồng đơn / giao hàng', flush=True)
    test_order_flow(admin, staff, item)
    print('## ADM-BE-02 Kho: snapshot giá vốn, sổ kho, xuất đồng thời', flush=True)
    test_inventory_integrity(admin, item, orders)
    test_concurrent_prepare(admin_email, admin_password)

    failed = [r for r in RESULTS if not r['ok']]
    print(f'\nTỔNG (test_admin_backend_final.py): {len(RESULTS) - len(failed)}/{len(RESULTS)} kiểm tra đạt.', flush=True)
    if os.environ.get('CAS_TEST_REPORT'):
        with open(os.environ['CAS_TEST_REPORT'], 'w', encoding='utf-8') as fh:
            json.dump({'run': RUN, 'base': ops.BASE, 'total': len(RESULTS), 'failed': len(failed), 'results': RESULTS}, fh, ensure_ascii=False, indent=1)
    sys.exit(1 if failed else 0)


if __name__ == '__main__':
    main()
