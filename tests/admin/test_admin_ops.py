"""Kiểm thử đầu-cuối Admin Operations (TV3, ADM-01..ADM-07) trên PHP + MySQL thật.

Chạy qua scripts/verify-admin-ops.sh (tạo tài khoản owner/admin/staff bằng scripts/create-admin.php rồi bật php -S).
Chỉ dùng thư viện chuẩn Python. Mỗi bước kiểm tra cả mã HTTP lẫn dữ liệu trong MySQL.

Biến môi trường:
  CAS_BASE_URL                                   vd http://127.0.0.1:8035/
  DB_HOST DB_PORT DB_USER DB_PASSWORD DB_NAME    kết nối mysql CLI
  CAS_OWNER_EMAIL/CAS_OWNER_PASSWORD, CAS_ADMIN_EMAIL/..., CAS_STAFF_EMAIL/...
  CAS_TEST_REPORT                                (tuỳ chọn) ghi kết quả JSON
"""
import http.cookiejar
import json
import os
import re
import secrets
import struct
import subprocess
import sys
import threading
import time
import urllib.error
import urllib.parse
import urllib.request
import zlib

BASE = os.environ.get('CAS_BASE_URL', 'http://127.0.0.1:8035/').rstrip('/') + '/'
RUN = secrets.token_hex(3)
RESULTS = []

ROUTES = ['dang-nhap', 'index', 'don-hang', 'giao-hang', 'khach-hang', 'san-pham', 'them-san-pham', 'sua-san-pham?id=1',
          'quan-ly-nhanh', 'phan-loai-bo-loc', 'kho-tong', 'bao-cao', 'loi-nhuan',
          'hoan-tien', 'giay-goi', 'kham-pha', 'cong-thuc-hoa', 'kho-hoa', 'kho-phu-kien', 'lich-su-kho', 'nhan-vien', 'cai-dat', 'tai-khoan', 'chu-so-huu']
STAFF_FORBIDDEN = {'loi-nhuan', 'cong-thuc-hoa', 'them-san-pham', 'sua-san-pham?id=1', 'nhan-vien', 'cai-dat', 'chu-so-huu'}
ADMIN_FORBIDDEN = {'chu-so-huu'}


# ------------------------------------------------------------------ hạ tầng
def check(name, ok, detail=''):
    RESULTS.append({'name': name, 'ok': bool(ok), 'detail': str(detail)[:300]})
    print(('PASS ' if ok else 'FAIL ') + name + ('' if ok else '  -> ' + str(detail)[:300]), flush=True)
    return ok


def sql(query):
    cmd = [os.environ.get('MYSQL_BIN', 'mysql'), '--default-character-set=utf8mb4', '-h', os.environ.get('DB_HOST', '127.0.0.1'),
           '-P', os.environ.get('DB_PORT', '3306'), '-u', os.environ.get('DB_USER', 'root'), os.environ.get('DB_NAME', 'cas_hoa'),
           '--batch', '--skip-column-names']
    env = dict(os.environ, MYSQL_PWD=os.environ.get('DB_PASSWORD', ''))
    # Câu SQL đi qua stdin (UTF-8) thay vì tham số dòng lệnh để giữ nguyên tiếng Việt trên mọi hệ điều hành.
    out = subprocess.run(cmd, input=query, capture_output=True, text=True, encoding='utf-8', env=env)
    if out.returncode:
        raise RuntimeError(out.stderr)
    return out.stdout.strip()


def sql_int(query):
    value = sql(query)
    return int(float(value)) if value not in ('', 'NULL') else 0


def q(value):
    return "'" + str(value).replace('\\', '\\\\').replace("'", "''") + "'"


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


class Client:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar), NoRedirect())
        self.csrf = ''

    def request(self, path, data=None, files=None):
        headers = {}
        body = None
        if files is not None:
            boundary = '----cas' + secrets.token_hex(8)
            parts = []
            for key, value in (data or {}).items():
                for item in (value if isinstance(value, list) else [value]):
                    parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{item}\r\n'.encode())
            for key, (filename, content, ctype) in files:
                parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"; filename="{filename}"\r\nContent-Type: {ctype}\r\n\r\n'.encode() + content + b'\r\n')
            body = b''.join(parts) + f'--{boundary}--\r\n'.encode()
            headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary
        elif data is not None:
            body = urllib.parse.urlencode(data, doseq=True).encode()
        req = urllib.request.Request(BASE + path, data=body, headers=headers)
        try:
            resp = self.opener.open(req, timeout=30)
            status, text, loc = resp.status, resp.read().decode('utf-8', 'replace'), resp.headers.get('Location', '')
        except urllib.error.HTTPError as e:
            status, text, loc = e.code, e.read().decode('utf-8', 'replace'), e.headers.get('Location', '')
        m = re.search(r'name="csrf" value="([a-f0-9]+)"', text) or re.search(r'window\.CAS_CSRF\s*=\s*"([a-f0-9]+)"', text)
        if m:
            self.csrf = m.group(1)
        return status, text, loc

    def get(self, path):
        return self.request(path)

    def post(self, path, data, files=None, csrf=True):
        if csrf and not self.csrf:
            self.get(path)
        payload = dict(data)
        if csrf:
            payload['csrf'] = self.csrf
        return self.request(path, payload, files)

    def form_token(self, path):
        _, html, _ = self.get(path)
        m = re.search(r'name="request_token" value="([a-f0-9]{32})"', html)
        return m.group(1) if m else secrets.token_hex(16)


def login_admin(email, password):
    c = Client()
    c.get('admin/dang-nhap.php')
    status, _, loc = c.post('admin/dang-nhap.php', {'email': email, 'password': password})
    if status != 303 or 'index.php' not in loc:
        raise RuntimeError(f'Không đăng nhập được {email}: {status} {loc}')
    c.get('admin/index.php')
    return c


def png(width=6, height=6, rgb=(214, 140, 160)):
    raw = b''.join(b'\x00' + bytes(rgb) * width for _ in range(height))

    def chunk(kind, data):
        return struct.pack('>I', len(data)) + kind + data + struct.pack('>I', zlib.crc32(kind + data) & 0xffffffff)
    return b'\x89PNG\r\n\x1a\n' + chunk(b'IHDR', struct.pack('>IIBBBBB', width, height, 8, 2, 0, 0, 0)) + chunk(b'IDAT', zlib.compress(raw)) + chunk(b'IEND', b'')


def strong_password():
    return 'Tv3_' + secrets.token_hex(6) + '!A9'


def flash(html):
    m = re.search(r'alert-(?:danger|success)[^>]*>([^<]+)<', html)
    return m.group(1).strip() if m else ''


# ------------------------------------------------------------------ chuẩn bị
def make_paid_order(admin):
    """Đặt đơn giao hàng như khách (actions/checkout.php) rồi Admin xác nhận đã thu qua luồng don-hang.php có sẵn."""
    guest = Client()
    guest.get('thanh-toan.php')
    cart = json.dumps([{'productId': 'p002', 'quantity': 1}])
    guest.post('actions/cart.php', {'cart': cart}, files=[])
    status, body, _ = guest.post('actions/checkout.php', {
        'cart': cart, 'customerName': 'Khach TV3 ' + RUN, 'customerPhone': '0912' + str(secrets.randbelow(900000) + 100000),
        'recipientName': 'Nguoi nhan TV3', 'recipientPhone': '0933' + str(secrets.randbelow(900000) + 100000),
        'fulfillment': 'delivery', 'address': 'Linh Xuan, Thu Duc', 'receiveDate': time.strftime('%Y-%m-%d', time.localtime(time.time() + 86400)),
        'receiveTime': '10:00 - 12:00'}, files=[])
    result = json.loads(body)
    assert result.get('ok'), result
    code = result['orderCode']
    order_id = sql_int(f"SELECT order_id FROM orders WHERE order_code={q(code)}")
    admin.get('admin/don-hang.php')
    admin.post('admin/don-hang.php', {'action': 'order_update', 'id': order_id, 'status': 'confirmed', 'payment_status': 'paid'})
    return code, order_id


# ------------------------------------------------------------------ các nhóm kiểm thử
def test_flower_advisor():
    guest=Client()
    status, html, _ = guest.get('tu-van-hoa.php')
    check('Trang tư vấn hoa tải thành công, có token và biểu mẫu',status==200 and 'advisorForm' in html and bool(guest.csrf),status)
    status,body,_=guest.post('actions/flower-advisor.php',{'message':'Tìm hoa sinh nhật','budget':'500000'})
    try: obj=json.loads(body)
    except ValueError: obj={}
    check('Không có API key: gợi ý đúng dạng bộ lọc, mẫu đang bán',status==200 and obj.get('ok') and obj.get('mode')=='rules'
          and isinstance(obj.get('products'),list) and len(obj['products'])>0
          and all(isinstance(p.get('price'),int) for p in obj['products']),f'{status} {body[:180]}')
    status,body,_=guest.post('actions/flower-advisor.php',{'message':'Tìm hoa','budget':'-1'})
    check('Ngân sách âm bị chặn',status==422,status)
    status,body,_=guest.post('actions/flower-advisor.php',{'message':'Tìm hoa'},csrf=False)
    check('Tư vấn từ chối POST thiếu CSRF',status==403,status)


def test_role_matrix(sessions):
    for route in ROUTES:
        path = 'admin/' + (route.replace('?', '.php?', 1) if '?' in route else route + '.php')
        for role, client in sessions.items():
            status, html, loc = client.get(path)
            if route == 'dang-nhap':
                expected = 200 if role in ('guest', 'customer') else 303
            elif role in ('guest', 'customer'):
                expected = 303
            elif role == 'staff' and route in STAFF_FORBIDDEN or role == 'admin' and route in ADMIN_FORBIDDEN:
                expected = 403
            else:
                expected = 200
            ok = status == expected and (expected != 303 or 'dang-nhap.php' in loc or route == 'dang-nhap') and (expected != 200 or 'admin-content' in html or route == 'dang-nhap')
            check(f'GET {route} as {role} -> {expected}', ok, f'{status} {loc}')


def test_post_guards(sessions, staff):
    before = sql_int('SELECT COUNT(*) FROM inventory_transactions')
    for role in ('guest', 'customer'):
        status, _, loc = sessions[role].post('admin/kho-tong.php', {'action': 'stock_move', 'item_id': 1, 'transaction_type': 'in', 'quantity': 5,
                                                                    'reason': 'tan cong', 'request_token': secrets.token_hex(16)}, csrf=False)
        check(f'POST kho as {role} bị chuyển về đăng nhập', status == 303 and 'dang-nhap.php' in loc, f'{status} {loc}')
    status, _, _ = staff.request('admin/kho-tong.php', {'action': 'stock_move', 'item_id': 1, 'transaction_type': 'in', 'quantity': 5,
                                                        'reason': 'thieu csrf', 'request_token': secrets.token_hex(16)})
    check('POST thiếu CSRF bị chặn 403', status == 403, status)
    check('Không có giao dịch kho nào được tạo từ POST trái phép', sql_int('SELECT COUNT(*) FROM inventory_transactions') == before)


def test_products(admin, staff):
    sku = f'TV3-{RUN}'.upper()
    status, _, loc = admin.post('admin/them-san-pham.php', {
        'action': 'product_save', 'name': f'Bó thử TV3 {RUN}', 'sku': sku, 'slug': '', 'price': 315000, 'product_type': 'bouquet',
        'category_id': 1, 'tone_id': 2, 'occasion_id': 2, 'status': 'active', 'description': 'Sản phẩm kiểm thử', 'composition': 'Hoa hồng',
        'wrapping[]': ['1', '2']}, files=[('images[]', ('anh-1.png', png(), 'image/png')), ('images[]', ('anh-2.png', png(rgb=(120, 160, 140)), 'image/png'))])
    pid = sql_int(f'SELECT product_id FROM products WHERE sku={q(sku)}')
    check('Tạo sản phẩm + 2 ảnh tải lên (multipart) -> 303 sang trang sửa', status == 303 and f'id={pid}' in loc and pid > 0, f'{status} {loc}')
    images = sql(f'SELECT path FROM product_images WHERE product_id={pid} ORDER BY sort_order').splitlines()
    check('product_images lưu 2 ảnh, đúng 1 ảnh đại diện', len(images) == 2 and sql_int(f'SELECT SUM(is_primary) FROM product_images WHERE product_id={pid}') == 1, images)
    check('Ảnh được lưu thật dưới assets/uploads/products', all(p.startswith('assets/uploads/products/') for p in images)
          and all(admin.get(p)[0] == 200 for p in images), images)
    check('Slug tự sinh từ tên tiếng Việt', sql(f'SELECT slug FROM products WHERE product_id={pid}') == f'bo-thu-tv3-{RUN}')
    check('Gán giấy gói 1, 2 cho sản phẩm', sql_int(f'SELECT COUNT(*) FROM product_wrapping WHERE product_id={pid}') == 2)

    status, html, _ = admin.post(f'admin/sua-san-pham.php?id={pid}', {'action': 'product_save', 'id': pid, 'name': 'Ảnh giả', 'sku': sku + 'X',
                                 'price': 100000, 'product_type': 'bouquet', 'category_id': 1, 'tone_id': 1, 'occasion_id': 1, 'status': 'active'},
                                 files=[('images[]', ('virus.png', b'<?php echo 1; ?>', 'image/png'))])
    check('Từ chối file giả ảnh (PHP đổi đuôi .png) -> 422', status == 422 and 'PNG, JPG hoặc WEBP' in html, f'{status} {flash(html)}')
    check('File giả không được lưu, sản phẩm không đổi', sql_int(f"SELECT COUNT(*) FROM products WHERE sku={q(sku + 'X')}") == 0)
    status, html, _ = admin.post('admin/them-san-pham.php', {'action': 'product_save', 'name': 'Trùng SKU', 'sku': sku.lower(), 'price': 200000,
                                 'product_type': 'bouquet', 'category_id': 1, 'tone_id': 1, 'occasion_id': 1, 'status': 'active', 'image': 'lam-tinh.png'})
    check('SKU trùng (không phân biệt hoa thường) -> 422', status == 422, f'{status} {flash(html)}')
    status, _, _ = admin.post('admin/them-san-pham.php', {'action': 'product_save', 'name': 'Giá âm', 'sku': 'NEG-' + RUN, 'price': -1,
                              'product_type': 'bouquet', 'category_id': 1, 'tone_id': 1, 'occasion_id': 1, 'status': 'active', 'image': 'lam-tinh.png'})
    check('Giá âm -> 422', status == 422, status)
    status, _, _ = staff.post('admin/them-san-pham.php', {'action': 'product_save', 'name': 'Staff tạo', 'sku': 'STAFF-' + RUN, 'price': 200000,
                              'product_type': 'bouquet', 'category_id': 1, 'tone_id': 1, 'occasion_id': 1, 'status': 'active', 'image': 'lam-tinh.png'})
    check('Nhân viên POST tạo sản phẩm -> 403', status == 403 and sql_int(f"SELECT COUNT(*) FROM products WHERE sku='STAFF-{RUN}'") == 0, status)
    status, _, _ = admin.get('admin/sua-san-pham.php?id=999999')
    check('Sửa sản phẩm không tồn tại -> 404', status == 404, status)

    first_image = sql_int(f'SELECT image_id FROM product_images WHERE product_id={pid} ORDER BY sort_order DESC LIMIT 1')
    admin.post(f'admin/sua-san-pham.php?id={pid}', {'action': 'image_primary', 'id': pid, 'image_id': first_image})
    check('Đổi ảnh đại diện cập nhật products.image', sql(f'SELECT image FROM products WHERE product_id={pid}')
          == sql(f'SELECT path FROM product_images WHERE image_id={first_image}'))

    # Giá nhanh: đổi giá không làm thay đổi đơn cũ (giá chụp tại thời điểm đặt)
    item = sql('SELECT order_item_id, product_id, unit_price FROM order_items ORDER BY order_item_id LIMIT 1').split('\t')
    old_price = sql(f'SELECT price FROM products WHERE product_id={item[1]}')
    status, _, _ = admin.post('admin/quan-ly-nhanh.php', {'action': 'quick_save', 'id': item[1], 'price': 777000, 'status': 'active'})
    check('Quản lý nhanh đổi giá -> 303, products.price = 777000', status == 303 and sql_int(f'SELECT price FROM products WHERE product_id={item[1]}') == 777000, status)
    check('Giá trong order_items cũ không đổi (price snapshot)', sql(f'SELECT unit_price FROM order_items WHERE order_item_id={item[0]}') == item[2])
    _, feed, _ = Client().get('assets/js/products.php')
    check('Feed khách hàng nhận giá mới', '"price":777000' in feed)
    admin.post('admin/quan-ly-nhanh.php', {'action': 'quick_save', 'id': item[1], 'price': int(float(old_price)), 'status': 'active'})

    status, _, _ = admin.post('admin/san-pham.php', {'action': 'product_toggle', 'id': pid})
    _, feed, _ = Client().get('assets/js/products.php')
    check('Ẩn sản phẩm -> không còn trong feed khách hàng', status == 303 and sku not in feed, status)
    status, html, _ = admin.post('admin/san-pham.php', {'action': 'product_delete', 'id': item[1], 'confirm': 'yes'})
    check('Không xoá sản phẩm đã có đơn hàng -> 422', status == 422 and sql_int(f'SELECT COUNT(*) FROM products WHERE product_id={item[1]}') == 1, f'{status} {flash(html)}')
    status, _, _ = admin.post('admin/san-pham.php', {'action': 'product_delete', 'id': pid})
    check('Xoá thiếu xác nhận -> 422', status == 422, status)
    status, _, _ = admin.post('admin/san-pham.php', {'action': 'product_delete', 'id': pid, 'confirm': 'yes'})
    check('Xoá sản phẩm chưa có đơn (kèm ảnh) -> 303', status == 303 and sql_int(f'SELECT COUNT(*) FROM product_images WHERE product_id={pid}') == 0, status)
    return pid


def test_taxonomy(admin, staff):
    name = f'Cam đất {RUN}'
    status, _, _ = admin.post('admin/phan-loai-bo-loc.php', {'action': 'taxonomy_save', 'table': 'tones', 'name': name, 'hex_code': '#C98B5B', 'status': 'active'})
    tone_id = sql_int(f'SELECT tone_id FROM tones WHERE name={q(name)}')
    check('Thêm tông màu mới -> 303', status == 303 and tone_id > 0, status)
    status, _, _ = admin.post('admin/phan-loai-bo-loc.php', {'action': 'taxonomy_save', 'table': 'tones', 'name': name.upper(), 'hex_code': '#000000', 'status': 'active'})
    check('Tên phân loại trùng (khác hoa thường) -> 422', status == 422, status)
    status, _, _ = staff.post('admin/phan-loai-bo-loc.php', {'action': 'taxonomy_save', 'table': 'tones', 'name': 'Staff tone ' + RUN, 'status': 'active'})
    check('Nhân viên sửa phân loại -> 403', status == 403, status)
    status, _, _ = admin.post('admin/phan-loai-bo-loc.php', {'action': 'taxonomy_delete', 'table': 'tones', 'id': 1})
    check('Xoá tông màu đang dùng -> 422', status == 422 and sql_int('SELECT COUNT(*) FROM tones WHERE tone_id=1') == 1, status)

    # Ngừng dùng loại hoa: sản phẩm giữ liên kết, bộ lọc khách hàng ẩn mục đó
    cat = sql("SELECT category_id, name FROM categories c WHERE status='active' AND EXISTS(SELECT 1 FROM products p WHERE p.category_id=c.category_id AND p.status='active') ORDER BY category_id DESC LIMIT 1").split('\t')
    linked = sql_int(f'SELECT COUNT(*) FROM products WHERE category_id={cat[0]}')
    status, _, _ = admin.post('admin/phan-loai-bo-loc.php', {'action': 'taxonomy_save', 'table': 'categories', 'id': cat[0], 'name': cat[1], 'status': 'inactive'})
    _, page, _ = Client().get('san-pham.php')
    check('Ngừng dùng loại hoa -> sản phẩm vẫn giữ category_id', status == 303 and sql_int(f'SELECT COUNT(*) FROM products WHERE category_id={cat[0]}') == linked, status)
    check('Loại hoa ngừng dùng không còn trong bộ lọc trang khách', f'value="{cat[1]}"' not in page)
    admin.post('admin/phan-loai-bo-loc.php', {'action': 'taxonomy_save', 'table': 'categories', 'id': cat[0], 'name': cat[1], 'status': 'active'})
    _, page, _ = Client().get('san-pham.php')
    check('Bật lại loại hoa -> xuất hiện lại trong bộ lọc', f'value="{cat[1]}"' in page)
    status, _, _ = admin.post('admin/phan-loai-bo-loc.php', {'action': 'taxonomy_delete', 'table': 'tones', 'id': tone_id})
    check('Xoá tông màu chưa dùng -> 303', status == 303 and sql_int(f'SELECT COUNT(*) FROM tones WHERE tone_id={tone_id}') == 0, status)


def test_inventory(admin, staff):
    status, _, _ = admin.post('admin/kho-hoa.php', {'action': 'item_save', 'name': f'Hoa thử {RUN}', 'category': 'hoa', 'unit': 'cành',
                              'min_quantity': 5, 'unit_cost': 12000, 'status': 'active', 'opening_quantity': 10})
    item = sql_int(f"SELECT item_id FROM inventory_items WHERE name={q('Hoa thử ' + RUN)}")
    check('Tạo nguyên liệu kèm tồn đầu 10 (1 transaction) -> 303', status == 303 and sql_int(f'SELECT quantity FROM inventory_items WHERE item_id={item}') == 10, status)
    check('Tồn đầu được ghi phiếu nhập', sql_int(f"SELECT COUNT(*) FROM inventory_transactions WHERE item_id={item} AND transaction_type='in'") == 1)

    token = staff.form_token('admin/kho-hoa.php')
    status, _, _ = staff.post('admin/kho-hoa.php', {'action': 'stock_move', 'item_id': item, 'transaction_type': 'in', 'quantity': 15, 'reason': 'Nhập từ chợ hoa', 'request_token': token})
    row = sql(f'SELECT quantity_before, quantity_after, user_id FROM inventory_transactions WHERE item_id={item} ORDER BY transaction_id DESC LIMIT 1').split('\t')
    staff_id = sql_int(f"SELECT user_id FROM users WHERE email={q(os.environ['CAS_STAFF_EMAIL'])}")
    check('Nhân viên nhập 15 -> 303, before 10 / after 25, ghi người thực hiện', status == 303 and [float(row[0]), float(row[1])] == [10, 25] and int(row[2]) == staff_id, f'{status} {row}')
    status, html, _ = staff.post('admin/kho-hoa.php', {'action': 'stock_move', 'item_id': item, 'transaction_type': 'in', 'quantity': 15, 'reason': 'Nhập từ chợ hoa', 'request_token': token})
    check('Gửi lại cùng mã phiếu (double submit) -> 422, không cộng tồn lần 2', status == 422 and sql_int(f'SELECT quantity FROM inventory_items WHERE item_id={item}') == 25, f'{status} {flash(html)}')
    status, html, _ = staff.post('admin/kho-hoa.php', {'action': 'stock_move', 'item_id': item, 'transaction_type': 'out', 'quantity': 999, 'reason': 'Xuất quá tồn', 'request_token': secrets.token_hex(16)})
    check('Xuất vượt tồn -> 422, tồn giữ nguyên 25', status == 422 and sql_int(f'SELECT quantity FROM inventory_items WHERE item_id={item}') == 25, f'{status} {flash(html)}')
    status, _, _ = staff.post('admin/kho-hoa.php', {'action': 'stock_move', 'item_id': item, 'transaction_type': 'adjustment', 'quantity': 3, 'reason': 'Kiểm kê', 'request_token': secrets.token_hex(16)})
    check('Nhân viên kiểm kê (adjustment) -> 403', status == 403, status)
    status, _, _ = staff.post('admin/kho-hoa.php', {'action': 'stock_move', 'item_id': item, 'transaction_type': 'out', 'quantity': 0, 'reason': 'Số 0', 'request_token': secrets.token_hex(16)})
    check('Số lượng 0 -> 422', status == 422, status)

    # Hai phiếu xuất 20 gửi đồng thời trên tồn 25: chỉ một phiếu được ghi, tồn không âm
    results = []

    def worker(client):
        results.append(client.post('admin/kho-hoa.php', {'action': 'stock_move', 'item_id': item, 'transaction_type': 'out', 'quantity': 20,
                                                         'reason': 'Xuất đồng thời', 'request_token': secrets.token_hex(16)})[0])
    other = login_admin(os.environ['CAS_ADMIN_EMAIL'], os.environ['CAS_ADMIN_PASSWORD'])
    other.get('admin/kho-hoa.php')
    staff.get('admin/kho-hoa.php')
    threads = [threading.Thread(target=worker, args=(c,)) for c in (staff, other)]
    [t.start() for t in threads]
    [t.join() for t in threads]
    qty = sql_int(f'SELECT quantity FROM inventory_items WHERE item_id={item}')
    check('Xuất đồng thời: đúng 1 phiếu thành công, tồn = 5 (không âm)', sorted(results) == [303, 422] and qty == 5, f'{results} qty={qty}')

    status, _, _ = admin.post('admin/kho-hoa.php', {'action': 'stock_move', 'item_id': item, 'transaction_type': 'adjustment', 'quantity': 8, 'reason': 'Kiểm kê cuối ngày', 'request_token': secrets.token_hex(16)})
    check('Admin kiểm kê đặt tồn = 8 -> 303', status == 303 and sql_int(f'SELECT quantity FROM inventory_items WHERE item_id={item}') == 8, status)
    _, html, _ = admin.get(f'admin/lich-su-kho.php?item={item}')
    check('Lịch sử kho hiển thị phiếu theo bộ lọc nguyên liệu', 'Kiểm kê cuối ngày' in html and 'Nhập từ chợ hoa' in html)
    check('Tổng phiếu của nguyên liệu = 4 (tồn đầu, nhập, xuất, kiểm kê)', sql_int(f'SELECT COUNT(*) FROM inventory_transactions WHERE item_id={item}') == 4)
    _, html, _ = admin.get('admin/kho-phu-kien.php')
    check('Kho phụ kiện không lẫn nguyên liệu hoa', f'Hoa thử {RUN}' not in html)
    return item


def test_delivery(admin, staff, order_id):
    # Cross-module: configure recipe, reserve actual stock only on preparing,
    # let delivery move the order ready -> delivering -> completed.
    item = sql_int(f"SELECT item_id FROM inventory_items WHERE name={q('Hoa thử ' + RUN)}")
    product_id = 2  # make_paid_order builds an order for p002
    status, _, _ = admin.post('admin/don-hang.php', {'action': 'order_update', 'id': order_id, 'status': 'preparing', 'payment_status': 'paid'})
    check('Thiếu công thức: chặn chuẩn bị và giữ trạng thái/stock', status == 422 and sql(f'SELECT order_status FROM orders WHERE order_id={order_id}') == 'confirmed'
          and sql_int(f'SELECT quantity FROM inventory_items WHERE item_id={item}') == 8, status)
    status, _, _ = admin.post('admin/cong-thuc-hoa.php?product_id=2',
        {'action':'recipe_save','product_id':product_id,'item_id':item,'quantity':'2'})
    check('Admin cấu hình định mức 2 cành cho p002 -> 303',status==303
          and sql_int(f'SELECT quantity FROM product_recipes WHERE product_id=2 AND item_id={item}')==2,status)
    status, _, _ = admin.post('admin/don-hang.php', {'action': 'order_update', 'id': order_id, 'status': 'preparing', 'payment_status': 'paid'})
    check('Chuyển preparing tự trừ 2 cành, có snapshot',status==303 and sql(f'SELECT order_status FROM orders WHERE order_id={order_id}')=='preparing'
          and sql_int(f'SELECT quantity FROM inventory_items WHERE item_id={item}')==6
          and sql_int(f'SELECT quantity FROM order_inventory_usage WHERE order_id={order_id} AND item_id={item}')==2,status)
    check('Giá vốn nguyên liệu lưu cố định lúc xuất kho',
          sql(f'SELECT unit_cost_snapshot, cost_amount_snapshot FROM order_inventory_usage WHERE order_id={order_id} AND item_id={item}') == '12000.00\t24000.00')
    status_report, html_report, _ = admin.get('admin/loi-nhuan.php?group=day')
    check('Báo cáo lợi nhuận có đồ thị và số liệu SQL', status_report == 200 and 'Biểu đồ lợi nhuận gộp' in html_report
          and 'Doanh thu có đủ giá vốn' in html_report, status_report)
    status, _, _ = admin.post('admin/don-hang.php', {'action': 'order_update', 'id': order_id, 'status': 'preparing', 'payment_status': 'paid'})
    check('Gửi lại preparing không trừ tồn lần hai',status==303 and sql_int(f'SELECT quantity FROM inventory_items WHERE item_id={item}')==6,status)
    status, _, _ = admin.post('admin/don-hang.php', {'action': 'order_update', 'id': order_id, 'status': 'ready', 'payment_status': 'paid'})
    check('Đơn hoàn tất chuẩn bị -> ready',status==303 and sql(f'SELECT order_status FROM orders WHERE order_id={order_id}')=='ready',status)
    did = sql_int(f'SELECT delivery_id FROM deliveries WHERE order_id={order_id}')
    status, _, _ = staff.post('admin/giao-hang.php', {'action': 'delivery_update', 'id': did, 'status': 'delivered'})
    check('Nhảy cóc Chưa giao -> Đã giao bị chặn 422', status == 422 and sql(f'SELECT delivery_status FROM deliveries WHERE delivery_id={did}') == 'not_delivered', status)
    status, _, _ = staff.post('admin/giao-hang.php', {'action': 'delivery_update', 'id': did, 'status': 'out_for_delivery'})
    check('Giao hàng đồng bộ đơn -> delivering',status==303 and sql(f'SELECT order_status FROM orders WHERE order_id={order_id}')=='delivering',status)
    status, _, _ = staff.post('admin/giao-hang.php', {'action': 'delivery_update', 'id': did, 'status': 'not_delivered'})
    check('Giao thất bại không ghi lý do -> 422', status == 422, status)
    staff.post('admin/giao-hang.php', {'action': 'delivery_update', 'id': did, 'status': 'not_delivered', 'note': 'Khách vắng nhà'})
    check('Giao thất bại trở lại ready', sql(f'SELECT order_status FROM orders WHERE order_id={order_id}')=='ready')
    staff.post('admin/giao-hang.php', {'action': 'delivery_update', 'id': did, 'status': 'out_for_delivery'})
    status, _, _ = staff.post('admin/giao-hang.php', {'action': 'delivery_update', 'id': did, 'status': 'delivered'})
    check('Đã giao đồng bộ hoàn tất đơn', status == 303 and sql(f'SELECT delivery_status FROM deliveries WHERE delivery_id={did}') == 'delivered'
          and sql(f'SELECT order_status FROM orders WHERE order_id={order_id}')=='completed', status)
    status_profit, profit_html, _ = admin.get('admin/loi-nhuan.php?group=day')
    check('Đơn hoàn tất lên đồ thị lợi nhuận, giá vốn snapshot 24.000 đ', status_profit==200
          and '24.000' in profit_html and 'gross_profit' not in profit_html,
          status_profit)

    status, _, _ = admin.post('admin/giao-hang.php', {'action': 'delivery_update', 'id': did, 'status': 'not_delivered', 'note': 'Thử quay lại'})
    check('Đã giao là trạng thái cuối -> 422', status == 422, status)
    check('delivery_events lưu đủ 4 bước có người thực hiện', sql_int(f'SELECT COUNT(*) FROM delivery_events WHERE delivery_id={did} AND actor_id IS NOT NULL') == 4)
    day = sql(f'SELECT DATE(scheduled_at) FROM deliveries WHERE delivery_id={did}')
    _, html, _ = admin.get(f'admin/giao-hang.php?date={day}')
    code = sql(f'SELECT order_code FROM orders WHERE order_id={order_id}')
    check('Lịch giao theo ngày (SQL) hiển thị đơn', code in html)


def test_inventory_cancellation(admin, order_id_for_reference):
    # Independent order, with recipe already created during test_delivery.
    code, order_id = make_paid_order(admin)
    item = sql_int(f"SELECT item_id FROM inventory_items WHERE name={q('Hoa thử ' + RUN)}")
    before = sql_int(f'SELECT quantity FROM inventory_items WHERE item_id={item}')
    status, _, _ = admin.post('admin/cong-thuc-hoa.php?product_id=2',
        {'action':'recipe_save','product_id':2,'item_id':item,'quantity':'1000'})
    check('Có thể quản lý định mức từ Admin',status==303,status)
    status, _, _ = admin.post('admin/don-hang.php', {'action':'order_update','id':order_id,'status':'preparing','payment_status':'paid'})
    check('Thiếu tồn: rollback trạng thái đơn và số tồn kho',status==422 and sql(f'SELECT order_status FROM orders WHERE order_id={order_id}')=='confirmed'
          and sql_int(f'SELECT quantity FROM inventory_items WHERE item_id={item}')==before,status)
    admin.post('admin/cong-thuc-hoa.php?product_id=2',
        {'action':'recipe_save','product_id':2,'item_id':item,'quantity':'2'})
    status, _, _ = admin.post('admin/don-hang.php', {'action':'order_update','id':order_id,'status':'preparing','payment_status':'paid'})
    check('Đủ tồn: trừ kho chính xác theo định mức',status==303 and sql_int(f'SELECT quantity FROM inventory_items WHERE item_id={item}')==before-2,status)
    admin.post('admin/cong-thuc-hoa.php?product_id=2',
        {'action':'recipe_save','product_id':2,'item_id':item,'quantity':'3'})
    status, _, _ = admin.post('admin/don-hang.php', {'action':'order_update','id':order_id,'status':'cancelled','payment_status':'paid','reason':'Khách hủy đơn trước khi làm hoa'})
    check('Hủy đơn hoàn lại 2 theo snapshot, không hoàn 3 theo công thức mới',status==303 and sql_int(f'SELECT quantity FROM inventory_items WHERE item_id={item}')==before
          and sql_int(f"SELECT COUNT(*) FROM order_inventory_usage WHERE order_id={order_id} AND restored_at IS NOT NULL")==1,status)
    status, _, _ = admin.post('admin/don-hang.php', {'action':'order_update','id':order_id,'status':'cancelled','payment_status':'paid'})
    check('Gửi lại trạng thái huỷ không hoàn kho lần hai',status==303 and sql_int(f'SELECT quantity FROM inventory_items WHERE item_id={item}')==before,status)
    return code, order_id


def test_refunds(admin, staff, order_code, order_id):
    unpaid = sql("SELECT order_code FROM orders WHERE payment_status<>'paid' LIMIT 1")
    if unpaid:
        status, _, _ = staff.post('admin/hoan-tien.php', {'action': 'refund_create', 'order_code': unpaid, 'amount': 10000, 'reason': 'Khách đổi ý', 'request_token': secrets.token_hex(16)})
        check('Không tạo hoàn tiền cho đơn chưa thanh toán -> 422', status == 422, status)
    total = sql_int(f'SELECT total_amount FROM orders WHERE order_id={order_id}')
    status, _, _ = staff.post('admin/hoan-tien.php', {'action': 'refund_create', 'order_code': order_code, 'amount': total + 1000, 'reason': 'Hoàn vượt', 'request_token': secrets.token_hex(16)})
    check('Hoàn vượt số đã thanh toán -> 422', status == 422, status)
    token = secrets.token_hex(16)
    status, _, _ = staff.post('admin/hoan-tien.php', {'action': 'refund_create', 'order_code': order_code, 'amount': 50000, 'reason': 'Hoa bị dập một phần', 'request_token': token})
    rid = sql_int(f'SELECT refund_id FROM refunds WHERE request_token={q(token)}')
    check('Nhân viên tạo yêu cầu hoàn 50.000 -> 303', status == 303 and rid > 0, status)
    status, _, _ = staff.post('admin/hoan-tien.php', {'action': 'refund_create', 'order_code': order_code, 'amount': 50000, 'reason': 'Hoa bị dập một phần', 'request_token': token})
    check('Gửi trùng yêu cầu hoàn -> 422, chỉ 1 bản ghi', status == 422 and sql_int(f'SELECT COUNT(*) FROM refunds WHERE order_id={order_id}') == 1, status)
    status, _, _ = staff.post('admin/hoan-tien.php', {'action': 'refund_transition', 'id': rid, 'status': 'approved'})
    check('Nhân viên tự duyệt hoàn tiền -> 403', status == 403 and sql(f'SELECT status FROM refunds WHERE refund_id={rid}') == 'requested', status)
    status, _, _ = admin.post('admin/hoan-tien.php', {'action': 'refund_transition', 'id': rid, 'status': 'recorded', 'reference': 'FT123'})
    check('Không ghi nhận khi chưa duyệt (nhảy bước) -> 422', status == 422, status)
    status, _, _ = admin.post('admin/hoan-tien.php', {'action': 'refund_transition', 'id': rid, 'status': 'approved', 'note': 'Đồng ý hoàn'})
    check('Admin duyệt -> 303', status == 303 and sql(f'SELECT status FROM refunds WHERE refund_id={rid}') == 'approved', status)
    status, _, _ = admin.post('admin/hoan-tien.php', {'action': 'refund_transition', 'id': rid, 'status': 'recorded'})
    check('Ghi nhận đã hoàn thiếu mã giao dịch -> 422', status == 422, status)
    status, _, _ = admin.post('admin/hoan-tien.php', {'action': 'refund_transition', 'id': rid, 'status': 'recorded', 'reference': 'FT' + RUN})
    check('Ghi nhận đã hoàn có mã giao dịch -> 303', status == 303 and sql(f'SELECT reference FROM refunds WHERE refund_id={rid}') == 'FT' + RUN, status)
    check('Hoàn tiền đồng bộ trạng thái thanh toán một phần', sql(f'SELECT payment_status FROM orders WHERE order_id={order_id}') == 'partially_refunded')
    token2=secrets.token_hex(16)
    status, _, _ = staff.post('admin/hoan-tien.php',{'action':'refund_create','order_code':order_code,
        'amount':total-50000,'reason':'Hoàn phần tiền còn lại','request_token':token2})
    second=sql_int(f'SELECT refund_id FROM refunds WHERE request_token={q(token2)}')
    check('Đơn hoàn một phần tiếp tục được hoàn phần còn lại',status==303 and second>rid,status)
    admin.post('admin/hoan-tien.php',{'action':'refund_transition','id':second,'status':'approved','note':'Duyệt'})
    status, _, _ = admin.post('admin/hoan-tien.php',{'action':'refund_transition','id':second,'status':'recorded','reference':'FULL'+RUN})
    check('Ghi nhận hoàn đủ: orders.payment_status=refunded',status==303 and sql(f'SELECT payment_status FROM orders WHERE order_id={order_id}')=='refunded',status)
    check('refund_events có đủ 3 bước', sql_int(f'SELECT COUNT(*) FROM refund_events WHERE refund_id={rid}') == 3)


def test_wrapping(admin, staff):
    name = f'Nâu kraft {RUN}'
    status, _, _ = admin.post('admin/giay-goi.php', {'action': 'wrapping_save', 'name': name, 'hex_code': '#B08A64', 'surcharge': 12000, 'sort_order': 9, 'status': 'active'},
                              files=[('image', ('kraft.png', png(rgb=(176, 138, 100)), 'image/png'))])
    wid = sql_int(f'SELECT color_id FROM wrapping_colors WHERE name={q(name)}')
    check('Thêm giấy gói kèm ảnh, phụ thu -> 303', status == 303 and wid > 0 and sql(f'SELECT image FROM wrapping_colors WHERE color_id={wid}').startswith('assets/uploads/wrapping/'), status)
    status, _, _ = staff.post('admin/giay-goi.php', {'action': 'wrapping_save', 'name': 'Staff ' + RUN, 'hex_code': '#111111', 'surcharge': 0, 'sort_order': 1, 'status': 'active'})
    check('Nhân viên sửa giấy gói -> 403', status == 403, status)
    status, _, _ = admin.post('admin/giay-goi.php', {'action': 'wrapping_save', 'name': 'Sai màu ' + RUN, 'hex_code': 'red', 'surcharge': 0, 'sort_order': 1, 'status': 'active'})
    check('Mã màu sai định dạng -> 422', status == 422, status)
    status, _, _ = admin.post(f'admin/giay-goi.php?assign={wid}', {'action': 'wrapping_assign', 'color_id': wid, 'products[]': ['1']})
    _, feed, _ = Client().get('assets/js/products.php')
    check('Gán cho sản phẩm 1 -> feed khách có giấy gói + phụ thu', status == 303 and name in feed and '"surcharge":12000' in feed, status)
    status, _, _ = admin.post('admin/giay-goi.php', {'action': 'wrapping_delete', 'color_id': wid})
    check('Xoá giấy gói đang gán -> 422', status == 422, status)
    admin.post(f'admin/giay-goi.php?edit={wid}', {'action': 'wrapping_save', 'color_id': wid, 'name': name, 'hex_code': '#B08A64', 'surcharge': 12000, 'sort_order': 9, 'status': 'inactive'})
    _, feed, _ = Client().get('assets/js/products.php')
    check('Giấy gói "ngừng dùng" không còn hiển thị cho khách', name not in feed)
    admin.post(f'admin/giay-goi.php?assign={wid}', {'action': 'wrapping_assign', 'color_id': wid})
    status, _, _ = admin.post('admin/giay-goi.php', {'action': 'wrapping_delete', 'color_id': wid})
    check('Bỏ gán rồi xoá -> 303', status == 303 and sql_int(f'SELECT COUNT(*) FROM wrapping_colors WHERE color_id={wid}') == 0, status)


def test_discovery(admin, staff):
    tone = sql("SELECT tone_id, name FROM tones WHERE status='active' AND tone_id IN (SELECT tone_id FROM products WHERE status='active') ORDER BY tone_id LIMIT 1").split('\t')
    title = f'Nhẹ nhàng {RUN}'
    status, _, _ = admin.post('admin/kham-pha.php', {'action': 'discovery_save', 'title': title, 'subtitle': 'Theo tông màu', 'filter_type': 'tone',
                              'filter_id_tone': tone[0], 'sort_order': 0, 'status': 'active', 'library_image': 'lam-tinh.png'})
    gid = sql_int(f'SELECT group_id FROM discovery_groups WHERE title={q(title)}')
    check('Thêm nhóm khám phá theo tông màu -> 303', status == 303 and gid > 0, status)
    _, home, _ = Client().get('index.php')
    href = 'san-pham.php?tone=' + urllib.parse.quote(tone[1])
    check('Trang chủ hiển thị thẻ mới với liên kết lọc đúng', title in home and href.replace('&', '&amp;') in home, href)
    _, catalog, _ = Client().get(href)
    check('Liên kết dẫn tới trang Sản phẩm có bộ lọc tông màu tương ứng', f'value="{tone[1]}"' in catalog)
    status, _, _ = staff.post('admin/kham-pha.php', {'action': 'discovery_delete', 'group_id': gid})
    check('Nhân viên xoá nhóm khám phá -> 403', status == 403, status)
    status, _, _ = admin.post('admin/kham-pha.php', {'action': 'discovery_save', 'title': 'Thiếu ảnh ' + RUN, 'filter_type': 'tone', 'filter_id_tone': tone[0], 'sort_order': 1, 'status': 'active'})
    check('Nhóm không có ảnh -> 422', status == 422, status)

    admin.post('admin/phan-loai-bo-loc.php', {'action': 'taxonomy_save', 'table': 'tones', 'id': tone[0], 'name': tone[1], 'status': 'inactive'})
    _, home, _ = Client().get('index.php')
    _, admin_page, _ = admin.get('admin/kham-pha.php')
    check('Tông màu ngừng dùng -> thẻ tự ẩn ở trang chủ', title not in home)
    check('Admin thấy trạng thái không hợp lệ của thẻ', 'Phân loại ngừng dùng' in admin_page)
    status, _, _ = admin.post(f'admin/kham-pha.php?edit={gid}', {'action': 'discovery_save', 'group_id': gid, 'title': title, 'filter_type': 'tone', 'filter_id_tone': tone[0], 'sort_order': 0, 'status': 'active'})
    check('Không gắn nhóm vào phân loại đang ngừng dùng -> 422', status == 422, status)
    admin.post('admin/phan-loai-bo-loc.php', {'action': 'taxonomy_save', 'table': 'tones', 'id': tone[0], 'name': tone[1], 'status': 'active'})
    status, _, _ = admin.post('admin/kham-pha.php', {'action': 'discovery_delete', 'group_id': gid})
    check('Xoá nhóm khám phá -> 303', status == 303 and sql_int(f'SELECT COUNT(*) FROM discovery_groups WHERE group_id={gid}') == 0, status)


def test_settings(admin, staff):
    current = dict(line.split('\t', 1) for line in sql('SELECT setting_key, setting_value FROM shop_settings').splitlines())
    data = {'action': 'settings_save', **current}
    status, _, _ = admin.post('admin/cai-dat.php', {**data, 'hotline': '12345'})
    check('Hotline sai -> 422', status == 422, status)
    status, _, _ = admin.post('admin/cai-dat.php', {**data, 'email': 'khong-phai-email'})
    check('Email sai -> 422', status == 422, status)
    status, _, _ = staff.post('admin/cai-dat.php', {**data, 'address': 'Staff đổi địa chỉ'})
    check('Nhân viên lưu cài đặt -> 403', status == 403, status)
    address = f'12 Võ Văn Ngân {RUN}, Thủ Đức, TP.HCM'
    status, _, _ = admin.post('admin/cai-dat.php', {**data, 'address': address, 'announcement': f'Freeship nội thành {RUN}'})
    check('Admin lưu cài đặt -> 303 và MySQL cập nhật', status == 303 and sql("SELECT setting_value FROM shop_settings WHERE setting_key='address'") == address, status)
    _, contact, _ = Client().get('lien-he.php')
    _, feed, _ = Client().get('assets/js/products.php')
    check('Trang Liên hệ dùng địa chỉ mới', address in contact)
    check('Header / footer nhận thông báo + địa chỉ mới qua CAS_SETTINGS', f'Freeship nội thành {RUN}' in feed and address in feed)
    admin.post('admin/cai-dat.php', data)


def test_people(admin, staff, owner):
    staff_email = sql(f"SELECT email FROM users WHERE email={q(os.environ['CAS_STAFF_EMAIL'])}")
    staff_id = sql_int(f'SELECT user_id FROM users WHERE email={q(staff_email)}')
    admin_id = sql_int(f"SELECT user_id FROM users WHERE email={q(os.environ['CAS_ADMIN_EMAIL'])}")
    owner_id = sql_int(f"SELECT user_id FROM users WHERE email={q(os.environ['CAS_OWNER_EMAIL'])}")

    status, _, _ = staff.post('admin/nhan-vien.php', {'action': 'staff_role', 'id': staff_id, 'role': 'admin'})
    check('Nhân viên tự nâng quyền -> 403, vai trò giữ nguyên', status == 403 and sql(f'SELECT r.name FROM users u JOIN roles r USING(role_id) WHERE user_id={staff_id}') == 'staff', status)
    status, _, _ = staff.post('admin/chu-so-huu.php', {'action': 'owner_change', 'op': 'promote', 'id': staff_id, 'confirm_password': os.environ['CAS_STAFF_PASSWORD']})
    check('Nhân viên POST trang chủ sở hữu -> 403', status == 403, status)

    new_email, new_pass = f'nv-{RUN}@example.test', strong_password()
    status, _, _ = admin.post('admin/nhan-vien.php', {'action': 'staff_create', 'full_name': 'Nhân viên mới', 'email': new_email, 'role': 'admin', 'password': new_pass, 'password_confirm': new_pass})
    check('Admin tạo tài khoản Quản trị viên -> 403', status == 403 and sql_int(f'SELECT COUNT(*) FROM users WHERE email={q(new_email)}') == 0, status)
    status, _, _ = admin.post('admin/nhan-vien.php', {'action': 'staff_create', 'full_name': 'Nhân viên mới', 'email': new_email, 'role': 'staff', 'password': 'yeu', 'password_confirm': 'yeu'})
    check('Mật khẩu yếu -> 422', status == 422, status)
    status, _, _ = admin.post('admin/nhan-vien.php', {'action': 'staff_create', 'full_name': 'Nhân viên mới', 'email': new_email, 'phone': '0987' + str(secrets.randbelow(900000) + 100000),
                              'role': 'staff', 'password': new_pass, 'password_confirm': new_pass})
    new_id = sql_int(f'SELECT user_id FROM users WHERE email={q(new_email)}')
    check('Admin tạo nhân viên -> 303, mật khẩu lưu dạng băm', status == 303 and new_id > 0 and sql(f'SELECT password_hash FROM users WHERE user_id={new_id}').startswith('$2y$'), status)
    newbie = login_admin(new_email, new_pass)
    new_secret = strong_password()
    version_before=sql_int(f'SELECT session_version FROM users WHERE user_id={new_id}')
    status, _, _ = admin.post('admin/nhan-vien.php',{'action':'staff_reset_password','id':new_id,
        'new_password':new_secret,'new_password_confirm':new_secret,'confirm_password':os.environ['CAS_ADMIN_PASSWORD']})
    check('Admin reset mật khẩu tăng session_version',status==303 and sql_int(f'SELECT session_version FROM users WHERE user_id={new_id}')==version_before+1,status)
    status, _, loc = newbie.get('admin/index.php')
    check('Phiên cũ bị thu hồi sau reset mật khẩu',status==303 and 'dang-nhap.php' in loc,f'{status} {loc}')
    newbie=login_admin(new_email,new_secret)
    status, _, _ = admin.post('admin/nhan-vien.php', {'action': 'staff_status', 'id': new_id, 'status': 'locked'})
    check('Admin khoá nhân viên -> 303', status == 303, status)
    status, _, loc = newbie.get('admin/index.php')
    check('Phiên đang mở của nhân viên bị khoá mất quyền ngay', status == 303 and 'dang-nhap.php' in loc, f'{status} {loc}')
    status, _, _ = admin.post('admin/nhan-vien.php', {'action': 'staff_status', 'id': owner_id, 'status': 'locked'})
    check('Admin khoá chủ sở hữu -> 403', status == 403 and sql(f'SELECT status FROM users WHERE user_id={owner_id}') == 'active', status)
    status, _, _ = admin.post('admin/nhan-vien.php', {'action': 'staff_status', 'id': admin_id, 'status': 'locked'})
    check('Không tự khoá chính mình -> 403', status == 403, status)
    status, _, _ = admin.post('admin/nhan-vien.php', {'action': 'staff_role', 'id': new_id, 'role': 'admin'})
    check('Admin đổi vai trò -> 403 (chỉ chủ sở hữu)', status == 403, status)
    status, _, _ = owner.post('admin/nhan-vien.php', {'action': 'staff_role', 'id': new_id, 'role': 'admin'})
    check('Chủ sở hữu nâng nhân viên lên quản trị viên -> 303', status == 303 and sql(f'SELECT r.name FROM users u JOIN roles r USING(role_id) WHERE user_id={new_id}') == 'admin', status)

    # Chủ sở hữu cuối cùng được bảo vệ
    owners = sql_int("SELECT COUNT(*) FROM users u JOIN roles r USING(role_id) WHERE r.name='owner' AND u.status='active'")
    if owners == 1:
        status, html, _ = owner.post('admin/chu-so-huu.php', {'action': 'owner_change', 'op': 'demote', 'id': owner_id, 'confirm_password': os.environ['CAS_OWNER_PASSWORD']})
        check('Không hạ cấp chủ sở hữu cuối cùng -> 422', status == 422 and 'cuối cùng' in html, f'{status} {flash(html)}')
    status, _, _ = owner.post('admin/chu-so-huu.php', {'action': 'owner_change', 'op': 'promote', 'id': admin_id, 'confirm_password': 'sai-mat-khau'})
    check('Sai mật khẩu xác nhận -> 422, không đổi vai trò', status == 422 and sql(f'SELECT r.name FROM users u JOIN roles r USING(role_id) WHERE user_id={admin_id}') == 'admin', status)
    status, _, _ = owner.post('admin/chu-so-huu.php', {'action': 'owner_change', 'op': 'promote', 'id': admin_id, 'confirm_password': os.environ['CAS_OWNER_PASSWORD']})
    check('Chủ sở hữu nâng admin lên chủ sở hữu (nhập lại mật khẩu) -> 303', status == 303 and sql(f'SELECT r.name FROM users u JOIN roles r USING(role_id) WHERE user_id={admin_id}') == 'owner', status)
    status, _, _ = owner.post('admin/chu-so-huu.php', {'action': 'owner_change', 'op': 'demote', 'id': admin_id, 'confirm_password': os.environ['CAS_OWNER_PASSWORD']})
    check('Còn 2 chủ sở hữu -> hạ một người về quản trị viên -> 303', status == 303 and sql(f'SELECT r.name FROM users u JOIN roles r USING(role_id) WHERE user_id={admin_id}') == 'admin', status)
    check('Luôn còn ít nhất 1 chủ sở hữu hoạt động', sql_int("SELECT COUNT(*) FROM users u JOIN roles r USING(role_id) WHERE r.name='owner' AND u.status='active'") >= 1)

    # Hồ sơ và mật khẩu của chính mình
    status, _, _ = staff.post('admin/tai-khoan.php', {'action': 'profile_save', 'full_name': f'Nhân viên kho {RUN}', 'phone': ''})
    check('Nhân viên tự sửa hồ sơ -> 303', status == 303 and sql(f'SELECT full_name FROM users WHERE user_id={staff_id}') == f'Nhân viên kho {RUN}', status)
    status, _, _ = staff.post('admin/tai-khoan.php', {'action': 'password_change', 'current_password': 'sai', 'new_password': strong_password(), 'new_password_confirm': 'x'})
    check('Đổi mật khẩu sai mật khẩu hiện tại -> 422', status == 422, status)
    _, html, _ = owner.get('admin/chu-so-huu.php?outcome=denied')
    check('Nhật ký chủ sở hữu liệt kê các lần bị chặn', 'staff_role' in html and 'Bị chặn' in html)


def test_logout_revocation():
    c = login_admin(os.environ['CAS_STAFF_EMAIL'], os.environ['CAS_STAFF_PASSWORD'])
    c.get('admin/kho-tong.php')
    old_csrf = c.csrf
    c.post('dang-xuat.php', {})
    before = sql_int('SELECT COUNT(*) FROM inventory_transactions')
    status, _, loc = c.request('admin/kho-tong.php', {'csrf': old_csrf, 'action': 'stock_move', 'item_id': 1, 'transaction_type': 'in', 'quantity': 1,
                                                       'reason': 'Sau đăng xuất', 'request_token': secrets.token_hex(16)})
    check('Sau đăng xuất, POST admin bằng cookie cũ bị chuyển về đăng nhập', status == 303 and 'dang-nhap.php' in loc and sql_int('SELECT COUNT(*) FROM inventory_transactions') == before, f'{status} {loc}')


def test_audit():
    check('admin_audit_log ghi thao tác thành công', sql_int("SELECT COUNT(*) FROM admin_audit_log WHERE outcome='ok'") > 10)
    check('admin_audit_log ghi các lần bị chặn quyền', sql_int("SELECT COUNT(*) FROM admin_audit_log WHERE outcome='denied'") >= 5)
    check('Nhật ký không chứa mật khẩu', sql_int("SELECT COUNT(*) FROM admin_audit_log WHERE detail LIKE '%password%' AND detail LIKE '%Tv3_%'") == 0)


def main():
    sessions = {'guest': Client()}
    customer = Client()
    customer.get('dang-ky.php')
    cpass = strong_password()
    customer.post('dang-ky.php', {'full_name': 'Khách TV3', 'phone': '0966' + str(secrets.randbelow(900000) + 100000), 'email': f'khach-{RUN}@example.test',
                                  'password': cpass, 'confirm_password': cpass})
    sessions['customer'] = customer
    staff = login_admin(os.environ['CAS_STAFF_EMAIL'], os.environ['CAS_STAFF_PASSWORD'])
    admin = login_admin(os.environ['CAS_ADMIN_EMAIL'], os.environ['CAS_ADMIN_PASSWORD'])
    owner = login_admin(os.environ['CAS_OWNER_EMAIL'], os.environ['CAS_OWNER_PASSWORD'])
    sessions.update(staff=staff, admin=admin, owner=owner)

    groups = [
        ('AI tư vấn / gợi ý bộ lọc', test_flower_advisor),
        ('Ma trận quyền 24 route x 5 vai trò', lambda: test_role_matrix(sessions)),
        ('Chặn POST trái phép / thiếu CSRF', lambda: test_post_guards(sessions, staff)),
        ('ADM-02 Sản phẩm, ảnh, giá nhanh', lambda: test_products(admin, staff)),
        ('ADM-02 Phân loại', lambda: test_taxonomy(admin, staff)),
        ('ADM-03 Kho', lambda: test_inventory(admin, staff)),
    ]
    for title, fn in groups:
        print(f'## {title}', flush=True)
        fn()
    order_code, order_id = make_paid_order(admin)
    for title, fn in [('ADM-04 Giao hàng', lambda: test_delivery(admin, staff, order_id)),
                      ('Định mức, thiếu tồn, hủy/hoàn kho', lambda: test_inventory_cancellation(admin, order_id)),
                      ('ADM-04 Hoàn tiền', lambda: test_refunds(admin, staff, order_code, order_id)),
                      ('ADM-05 Giấy gói', lambda: test_wrapping(admin, staff)),
                      ('ADM-05 Khám phá', lambda: test_discovery(admin, staff)),
                      ('ADM-06 Cài đặt', lambda: test_settings(admin, staff)),
                      ('ADM-06 Nhân viên / chủ sở hữu', lambda: test_people(admin, staff, owner)),
                      ('Đăng xuất thu hồi quyền', test_logout_revocation),
                      ('Nhật ký quản trị', test_audit)]:
        print(f'## {title}', flush=True)
        fn()

    failed = [r for r in RESULTS if not r['ok']]
    print(f'\nTỔNG: {len(RESULTS) - len(failed)}/{len(RESULTS)} kiểm tra đạt.', flush=True)
    if os.environ.get('CAS_TEST_REPORT'):
        with open(os.environ['CAS_TEST_REPORT'], 'w', encoding='utf-8') as fh:
            json.dump({'run': RUN, 'base': BASE, 'total': len(RESULTS), 'failed': len(failed), 'results': RESULTS}, fh, ensure_ascii=False, indent=1)
    sys.exit(1 if failed else 0)


if __name__ == '__main__':
    main()
