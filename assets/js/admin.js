(function () {
  'use strict';

  var SESSION_KEY = 'cas-hoa-admin-session';
  var CUSTOMER_ORDER_KEY = 'cas-hoa-static-orders';
  var ADMIN_ORDER_KEY = 'cas-hoa-admin-orders';
  var PRODUCT_ADDED_KEY = 'cas-hoa-admin-products';
  var PRODUCT_OVERRIDE_KEY = 'cas-hoa-admin-product-overrides';
  var REFUND_KEY = 'cas-hoa-admin-refunds';
  var INVENTORY_KEY = 'cas-hoa-admin-inventory';
  var STOCK_HISTORY_KEY = 'cas-hoa-admin-stock-history';
  var WRAPPING_KEY = 'cas-hoa-admin-wrapping';
  var TAXONOMY_KEY = 'cas-hoa-admin-taxonomy';
  var DISCOVERY_KEY = 'cas-hoa-admin-discovery';
  var STAFF_KEY = 'cas-hoa-admin-staff';
  var SETTINGS_KEY = 'cas-hoa-admin-settings';
  var ACCOUNT_KEY = 'cas-hoa-admin-account';

  function read(key, fallback) {
    try {
      var data = JSON.parse(localStorage.getItem(key) || 'null');
      return data == null ? fallback : data;
    } catch (error) {
      return fallback;
    }
  }

  function write(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
  }

  function esc(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character];
    });
  }

  function money(value) {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND',
      maximumFractionDigits: 0
    }).format(Number(value || 0));
  }

  function number(value) {
    return new Intl.NumberFormat('vi-VN').format(Number(value || 0));
  }

  function todayISO(offset) {
    var date = new Date();
    date.setDate(date.getDate() + (offset || 0));
    return date.toISOString().slice(0, 10);
  }

  function isoAt(offsetDays, hour) {
    var date = new Date();
    date.setDate(date.getDate() + (offsetDays || 0));
    date.setHours(hour == null ? 9 : hour, 0, 0, 0);
    return date.toISOString();
  }

  function page() {
    return document.body.dataset.adminPage || '';
  }

  function statusLabel(status) {
    return ({
      pending_confirmation: 'Chờ xác nhận',
      confirmed: 'Đã xác nhận',
      preparing: 'Đang chuẩn bị',
      ready: 'Sẵn sàng',
      delivering: 'Đang giao',
      completed: 'Hoàn tất',
      cancelled: 'Đã huỷ'
    })[status] || status || 'Chờ xác nhận';
  }

  function paymentLabel(status) {
    return ({
      unpaid: 'Chưa thanh toán',
      partial: 'Đã cọc',
      paid: 'Đã thanh toán',
      refunded: 'Đã hoàn tiền'
    })[status] || status || 'Chưa thanh toán';
  }

  function deliveryLabel(status) {
    return ({
      not_delivered: 'Chưa giao',
      out_for_delivery: 'Đang giao',
      delivered: 'Đã giao',
      pickup_ready: 'Chờ khách lấy'
    })[status] || status || 'Chưa giao';
  }

  function defaultOrders() {
    return [
      {
        orderCode: 'CSH-' + new Date().getFullYear() + '-A102F4D1',
        createdAt: isoAt(0, 7),
        status: 'pending_confirmation',
        paymentStatus: 'unpaid',
        deliveryStatus: 'not_delivered',
        customerName: 'Nguyễn Minh Anh',
        customerPhone: '0903123456',
        recipientName: 'Trần Thu Hà',
        recipientPhone: '0914555666',
        receiveDate: todayISO(0),
        receiveTime: '14:00 - 16:00',
        address: 'Thủ Đức, TP.HCM',
        isPickup: false,
        shippingVnd: 35000,
        totalVnd: 435000
      },
      {
        orderCode: 'CSH-' + new Date().getFullYear() + '-B88D10C2',
        createdAt: isoAt(0, 6),
        status: 'preparing',
        paymentStatus: 'paid',
        deliveryStatus: 'not_delivered',
        customerName: 'Lê Quốc Bảo',
        customerPhone: '0938777888',
        recipientName: 'Phạm Ngọc Mai',
        recipientPhone: '0987123456',
        receiveDate: todayISO(0),
        receiveTime: '17:00 - 19:00',
        address: 'Quận 3, TP.HCM',
        isPickup: false,
        shippingVnd: 40000,
        totalVnd: 490000
      },
      {
        orderCode: 'CSH-' + new Date().getFullYear() + '-C510EE91',
        createdAt: isoAt(-1, 18),
        status: 'confirmed',
        paymentStatus: 'partial',
        deliveryStatus: 'not_delivered',
        customerName: 'Võ Thanh Tâm',
        customerPhone: '0909666777',
        recipientName: 'Võ Thanh Tâm',
        recipientPhone: '0909666777',
        receiveDate: todayISO(1),
        receiveTime: '09:00 - 11:00',
        address: '',
        isPickup: true,
        shippingVnd: 0,
        totalVnd: 390000
      },
      {
        orderCode: 'CSH-' + new Date().getFullYear() + '-D72AA3B7',
        createdAt: isoAt(-1, 9),
        status: 'completed',
        paymentStatus: 'paid',
        deliveryStatus: 'delivered',
        customerName: 'Trần Gia Hân',
        customerPhone: '0977333555',
        recipientName: 'Nguyễn Thảo Vy',
        recipientPhone: '0966111222',
        receiveDate: todayISO(-1),
        receiveTime: '13:00 - 15:00',
        address: 'Bình Thạnh, TP.HCM',
        isPickup: false,
        shippingVnd: 30000,
        totalVnd: 410000
      },
      {
        orderCode: 'CSH-' + new Date().getFullYear() + '-E900BC45',
        createdAt: isoAt(-3, 11),
        status: 'cancelled',
        paymentStatus: 'refunded',
        deliveryStatus: 'not_delivered',
        customerName: 'Đỗ Hoàng Nam',
        customerPhone: '0922444555',
        recipientName: 'Đỗ Hoàng Nam',
        recipientPhone: '0922444555',
        receiveDate: todayISO(-2),
        receiveTime: '10:00 - 12:00',
        address: 'Gò Vấp, TP.HCM',
        isPickup: false,
        shippingVnd: 30000,
        totalVnd: 380000
      }
    ];
  }

  function getAdminOrders() {
    var saved = read(ADMIN_ORDER_KEY, null);
    if (!saved) {
      saved = defaultOrders();
      write(ADMIN_ORDER_KEY, saved);
    }
    return saved;
  }

  function getCustomerOrders() {
    return read(CUSTOMER_ORDER_KEY, []);
  }

  function getOrders() {
    var result = [];
    var seen = {};
    getCustomerOrders().concat(getAdminOrders()).forEach(function (order) {
      if (!order || !order.orderCode || seen[order.orderCode]) return;
      seen[order.orderCode] = true;
      result.push(Object.assign({
        paymentStatus: 'unpaid',
        deliveryStatus: order.status === 'completed' ? 'delivered' : 'not_delivered',
        shippingVnd: 0,
        isPickup: order.fulfillment === 'pickup'
      }, order));
    });
    return result.sort(function (a, b) {
      return new Date(b.createdAt || 0) - new Date(a.createdAt || 0);
    });
  }

  function updateOrder(orderCode, patch) {
    var customer = getCustomerOrders();
    var customerIndex = customer.findIndex(function (order) { return order.orderCode === orderCode; });
    if (customerIndex >= 0) {
      customer[customerIndex] = Object.assign({}, customer[customerIndex], patch);
      write(CUSTOMER_ORDER_KEY, customer);
      return;
    }

    var admin = getAdminOrders();
    var adminIndex = admin.findIndex(function (order) { return order.orderCode === orderCode; });
    if (adminIndex >= 0) {
      admin[adminIndex] = Object.assign({}, admin[adminIndex], patch);
      write(ADMIN_ORDER_KEY, admin);
    }
  }

  function defaultInventory() {
    return [
      { id: 'fl-rose-red', group: 'flower', name: 'Hoa hồng đỏ', unit: 'cành', qty: 34, min: 18, cost: 12000 },
      { id: 'fl-lily-white', group: 'flower', name: 'Hoa ly trắng', unit: 'cành', qty: 9, min: 12, cost: 22000 },
      { id: 'fl-hydrangea-blue', group: 'flower', name: 'Cẩm tú cầu xanh', unit: 'bông', qty: 7, min: 10, cost: 38000 },
      { id: 'fl-delphinium', group: 'flower', name: 'Phi yến tím', unit: 'cành', qty: 16, min: 10, cost: 18000 },
      { id: 'fl-baby', group: 'flower', name: 'Baby trắng', unit: 'bó', qty: 12, min: 6, cost: 55000 },
      { id: 'ac-paper-kraft', group: 'accessory', name: 'Giấy gói kraft', unit: 'tờ', qty: 45, min: 20, cost: 5000 },
      { id: 'ac-paper-cream', group: 'accessory', name: 'Giấy gói kem', unit: 'tờ', qty: 18, min: 20, cost: 6500 },
      { id: 'ac-ribbon', group: 'accessory', name: 'Ruy băng lụa', unit: 'cuộn', qty: 11, min: 5, cost: 28000 },
      { id: 'ac-card', group: 'accessory', name: 'Thiệp CÁ\'S HOA', unit: 'cái', qty: 62, min: 25, cost: 2500 },
      { id: 'ac-bag', group: 'accessory', name: 'Túi đựng hoa', unit: 'cái', qty: 26, min: 15, cost: 9000 }
    ];
  }

  function getInventory() {
    var saved = read(INVENTORY_KEY, null);
    if (!saved) {
      saved = defaultInventory();
      write(INVENTORY_KEY, saved);
    }
    return saved;
  }

  function saveInventory(list) {
    write(INVENTORY_KEY, list);
  }

  function defaultHistory() {
    return [
      { id: 'hist-1', at: isoAt(0, 7), item: 'Hoa ly trắng', type: 'out', qty: -6, note: 'Xuất cho đơn B88D10C2' },
      { id: 'hist-2', at: isoAt(-1, 15), item: 'Hoa hồng đỏ', type: 'in', qty: 24, note: 'Nhập hàng buổi chiều' },
      { id: 'hist-3', at: isoAt(-1, 10), item: 'Giấy gói kem', type: 'out', qty: -5, note: 'Sử dụng cho đơn trong ngày' }
    ];
  }

  function getHistory() {
    var saved = read(STOCK_HISTORY_KEY, null);
    if (!saved) {
      saved = defaultHistory();
      write(STOCK_HISTORY_KEY, saved);
    }
    return saved;
  }

  function addHistory(entry) {
    var list = getHistory();
    list.unshift(Object.assign({ id: 'hist-' + Date.now(), at: new Date().toISOString() }, entry));
    write(STOCK_HISTORY_KEY, list.slice(0, 100));
  }

  function defaultRefunds() {
    return [
      { id: 'RF-001', orderCode: 'CSH-' + new Date().getFullYear() + '-E900BC45', customer: 'Đỗ Hoàng Nam', amount: 350000, reason: 'Khách huỷ trước khi chuẩn bị hoa', status: 'completed', createdAt: isoAt(-2, 14) },
      { id: 'RF-002', orderCode: 'CSH-' + new Date().getFullYear() + '-A102F4D1', customer: 'Nguyễn Minh Anh', amount: 100000, reason: 'Điều chỉnh một phần đơn hàng', status: 'requested', createdAt: isoAt(0, 8) }
    ];
  }

  function getRefunds() {
    var saved = read(REFUND_KEY, null);
    if (!saved) {
      saved = defaultRefunds();
      write(REFUND_KEY, saved);
    }
    return saved;
  }

  function getProducts() {
    var base = (window.CAS_PRODUCTS || []).map(function (product) { return Object.assign({}, product); });
    var overrides = read(PRODUCT_OVERRIDE_KEY, {});
    base = base.map(function (product) {
      return Object.assign({}, product, overrides[product.id] || {});
    });
    return base.concat(read(PRODUCT_ADDED_KEY, []));
  }

  function findProduct(id) {
    return getProducts().find(function (product) { return product.id === id; }) || null;
  }

  function setProductPatch(id, patch) {
    var added = read(PRODUCT_ADDED_KEY, []);
    var addedIndex = added.findIndex(function (product) { return product.id === id; });

    if (addedIndex >= 0) {
      added[addedIndex] = Object.assign({}, added[addedIndex], patch);
      write(PRODUCT_ADDED_KEY, added);
      return;
    }

    var overrides = read(PRODUCT_OVERRIDE_KEY, {});
    overrides[id] = Object.assign({}, overrides[id] || {}, patch);
    write(PRODUCT_OVERRIDE_KEY, overrides);
  }

  function defaultWrapping() {
    return [
      { id: 'wrap-cream', name: 'Kem sữa', hex: '#EDE3D4', enabled: true },
      { id: 'wrap-green', name: 'Xanh olive', hex: '#8FA18A', enabled: true },
      { id: 'wrap-pink', name: 'Hồng nude', hex: '#DDB4AE', enabled: true },
      { id: 'wrap-black', name: 'Đen than', hex: '#303633', enabled: false }
    ];
  }

  function getWrapping() {
    var saved = read(WRAPPING_KEY, null);
    if (!saved) {
      saved = defaultWrapping();
      write(WRAPPING_KEY, saved);
    }
    return saved;
  }

  function defaultTaxonomy() {
    return {
      categories: ['Hoa ly', 'Hoa hồng', 'Cẩm tú cầu', 'Phi yến', 'Lily'],
      tones: ['Xanh', 'Hồng', 'Đỏ', 'Tím', 'Trắng'],
      occasions: ['Sinh nhật', 'Chúc mừng', 'Kỷ niệm', 'Tặng người thương', 'Cảm ơn']
    };
  }

  function getTaxonomy() {
    var saved = read(TAXONOMY_KEY, null);
    if (!saved) {
      saved = defaultTaxonomy();
      write(TAXONOMY_KEY, saved);
    }
    return saved;
  }

  function defaultDiscovery() {
    return [
      { id: 'dc-1', title: 'Sinh nhật', subtitle: 'Theo dịp', image: '../assets/images/garden.png', href: '../san-pham.html?occasion=sinh-nhat', enabled: true },
      { id: 'dc-2', title: 'Người thương', subtitle: 'Theo cảm xúc', image: '../assets/images/mot-bo-hoa.png', href: '../san-pham.html?occasion=nguoi-thuong', enabled: true },
      { id: 'dc-3', title: 'Xanh dịu', subtitle: 'Theo tông màu', image: '../assets/images/ly-xanh.png', href: '../san-pham.html?tone=xanh', enabled: true },
      { id: 'dc-4', title: 'Kỷ niệm', subtitle: 'Theo dịp', image: '../assets/images/phi-yen.png', href: '../san-pham.html?occasion=ky-niem', enabled: true }
    ];
  }

  function getDiscovery() {
    var saved = read(DISCOVERY_KEY, null);
    if (!saved) {
      saved = defaultDiscovery();
      write(DISCOVERY_KEY, saved);
    }
    return saved;
  }

  function defaultStaff() {
    return [
      { id: 'st-1', name: 'Nguyễn Thiên Bảo', email: 'owner@cashoa.vn', role: 'Chủ sở hữu', status: 'active' },
      { id: 'st-2', name: 'Trần Minh Anh', email: 'sale@cashoa.vn', role: 'Bán hàng', status: 'active' },
      { id: 'st-3', name: 'Lê Hoàng Nam', email: 'ops@cashoa.vn', role: 'Vận hành', status: 'active' }
    ];
  }

  function getStaff() {
    var saved = read(STAFF_KEY, null);
    if (!saved) {
      saved = defaultStaff();
      write(STAFF_KEY, saved);
    }
    return saved;
  }

  function defaultSettings() {
    return {
      shopName: "CÁ'S HOA",
      announcement: 'Có sẵn thiệp và túi · Shop xác nhận đơn sau khi nhận thông tin',
      phone: '0707309255',
      email: 'hello@cashoa.vn',
      openingHours: '08:00 - 20:00',
      instagram: '@cashoa',
      zalo: '0707309255',
      address: '126/13 đường số 17, Linh Xuân, Thủ Đức, TP.HCM',
      defaultShipping: 30000
    };
  }

  function getSettings() {
    var saved = read(SETTINGS_KEY, null);
    if (!saved) {
      saved = defaultSettings();
      write(SETTINGS_KEY, saved);
    }
    return saved;
  }

  function getAccount() {
    var saved = read(ACCOUNT_KEY, null);
    if (!saved) {
      saved = { name: 'Nguyễn Thiên Bảo', email: 'owner@cashoa.vn', phone: '0707309255', role: 'Chủ sở hữu' };
      write(ACCOUNT_KEY, saved);
    }
    return saved;
  }

  function activeClass(name) {
    return page() === name ? ' active' : '';
  }

  function navLink(name, href, icon, label) {
    return '<a class="admin-nav-link' + activeClass(name) + '" href="' + href + '"><i class="bi ' + icon + '"></i><span>' + label + '</span></a>';
  }

  function sidebarMarkup() {
    var warehouseActive = ['warehouse', 'flowers', 'accessories', 'stock-history'].indexOf(page()) >= 0 ? ' active' : '';
    return '' +
      '<div class="admin-sidebar-head">' +
        '<a class="admin-brand" href="index.html">' +
          '<span class="admin-brand-mark"><i class="bi bi-flower1"></i></span>' +
          '<span><strong>CÁ\'S HOA</strong><small>flowers & feelings</small></span>' +
        '</a>' +
        '<button class="admin-sidebar-close" id="adminSidebarClose" type="button" aria-label="Đóng menu"><i class="bi bi-x-lg"></i></button>' +
      '</div>' +
      '<div class="admin-nav-scroll">' +
        '<div class="admin-nav-group"><nav class="admin-nav">' +
          navLink('dashboard', 'index.html', 'bi-grid', 'Tổng quan') +
        '</nav></div>' +

        '<div class="admin-nav-group"><p class="admin-nav-title">Vận hành</p><nav class="admin-nav">' +
          navLink('orders', 'don-hang.html', 'bi-clipboard2-check', 'Đơn hàng') +
          navLink('refunds', 'hoan-tien.html', 'bi-arrow-counterclockwise', 'Hoàn tiền') +
          navLink('delivery', 'giao-hang.html', 'bi-truck', 'Giao hàng') +
          navLink('customers', 'khach-hang.html', 'bi-people', 'Khách hàng') +
          navLink('reports', 'bao-cao.html', 'bi-bar-chart', 'Báo cáo doanh thu') +
        '</nav></div>' +

        '<div class="admin-nav-group"><p class="admin-nav-title">Sản phẩm</p><nav class="admin-nav">' +
          navLink('products', 'san-pham.html', 'bi-flower2', 'Sản phẩm') +
          navLink('quick-products', 'quan-ly-nhanh.html', 'bi-lightning-charge', 'Quản lý nhanh') +
          navLink('wrapping', 'giay-goi.html', 'bi-palette', 'Màu giấy gói') +
          navLink('taxonomy', 'phan-loai-bo-loc.html', 'bi-tags', 'Phân loại & Bộ lọc') +
          navLink('discovery', 'kham-pha.html', 'bi-compass', 'Danh mục khám phá') +
        '</nav></div>' +

        '<div class="admin-nav-group"><p class="admin-nav-title">Kho</p><nav class="admin-nav">' +
          '<a class="admin-nav-link' + warehouseActive + '" href="kho-tong.html"><i class="bi bi-building"></i><span>Kho tổng</span></a>' +
          '<div class="admin-nav-sub">' +
            navLink('flowers', 'kho-hoa.html', 'bi-flower3', 'Kho Hoa') +
            navLink('accessories', 'kho-phu-kien.html', 'bi-box-seam', 'Kho Phụ kiện') +
          '</div>' +
          navLink('stock-history', 'lich-su-kho.html', 'bi-clock-history', 'Lịch sử kho') +
        '</nav></div>' +

        '<div class="admin-nav-group"><p class="admin-nav-title">Hệ thống</p><nav class="admin-nav">' +
          navLink('staff', 'nhan-vien.html', 'bi-person-gear', 'Nhân viên & quyền') +
          navLink('settings', 'cai-dat.html', 'bi-gear', 'Cài đặt') +
          navLink('account', 'tai-khoan.html', 'bi-person-circle', 'Tài khoản') +
        '</nav></div>' +

        '<div class="admin-sidebar-owner">' +
          navLink('owner', 'chu-so-huu.html', 'bi-crown', 'Chủ sở hữu') +
        '</div>' +
      '</div>';
  }

  function topbarMarkup() {
    var account = getAccount();
    return '' +
      '<div class="admin-topbar-left">' +
        '<button class="admin-menu-button" id="adminMenuOpen" type="button" aria-label="Mở menu"><i class="bi bi-list"></i></button>' +
        '<div><div class="admin-topbar-title">Trung tâm điều hành</div><span class="admin-role">' + esc(account.role) + '</span></div>' +
      '</div>' +
      '<div class="admin-topbar-actions">' +
        '<a class="admin-store-link" href="../index.html"><i class="bi bi-shop"></i><span>Về cửa hàng</span></a>' +
        '<button class="admin-icon-button" id="adminLogout" type="button" aria-label="Đăng xuất"><i class="bi bi-box-arrow-right"></i></button>' +
      '</div>';
  }

  function ensureSession() {
    if (page() === 'login') return true;
    if (localStorage.getItem(SESSION_KEY) !== '1') {
      location.replace('dang-nhap.html');
      return false;
    }
    return true;
  }

  function initShell() {
    var sidebar = document.getElementById('adminSidebar');
    var topbar = document.getElementById('adminTopbar');
    var overlay = document.getElementById('adminOverlay');

    if (sidebar) sidebar.innerHTML = sidebarMarkup();
    if (topbar) topbar.innerHTML = topbarMarkup();

    function closeMenu() {
      if (sidebar) sidebar.classList.remove('open');
      if (overlay) overlay.classList.remove('open');
    }

    var open = document.getElementById('adminMenuOpen');
    var close = document.getElementById('adminSidebarClose');
    if (open) open.addEventListener('click', function () {
      sidebar.classList.add('open');
      overlay.classList.add('open');
    });
    if (close) close.addEventListener('click', closeMenu);
    if (overlay) overlay.addEventListener('click', closeMenu);

    var logout = document.getElementById('adminLogout');
    if (logout) logout.addEventListener('click', function () {
      localStorage.removeItem(SESSION_KEY);
      location.replace('dang-nhap.html');
    });
  }

  function initLogin() {
    var form = document.getElementById('adminLoginForm');
    if (!form) return;

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var email = document.getElementById('adminEmail').value.trim();
      var password = document.getElementById('adminPassword').value;
      var message = document.getElementById('adminLoginMessage');

      if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email) || password.length < 4) {
        message.textContent = 'Vui lòng nhập email hợp lệ và mật khẩu từ 4 ký tự.';
        message.className = 'admin-feedback';
        message.style.background = '#fae8e4';
        message.style.color = 'var(--admin-danger)';
        return;
      }

      localStorage.setItem(SESSION_KEY, '1');
      location.href = 'index.html';
    });
  }

  function setText(id, value) {
    var node = document.getElementById(id);
    if (node) node.textContent = value;
  }

  function dateLabel(value) {
    if (!value) return '-';
    try { return new Date(value).toLocaleString('vi-VN'); }
    catch (error) { return value; }
  }

  function initDashboard() {
    if (page() !== 'dashboard') return;

    var orders = getOrders();
    var inventory = getInventory();
    var today = todayISO(0);
    var now = Date.now();

    var new24h = orders.filter(function (order) {
      return now - new Date(order.createdAt || 0).getTime() <= 86400000;
    }).length;

    var pending = orders.filter(function (order) { return order.status === 'pending_confirmation'; }).length;
    var preparing = orders.filter(function (order) { return ['confirmed', 'preparing', 'ready'].indexOf(order.status) >= 0; }).length;
    var todayDelivery = orders.filter(function (order) { return order.receiveDate === today && order.status !== 'cancelled'; }).length;
    var todayRevenue = orders.filter(function (order) {
      return String(order.createdAt || '').slice(0, 10) === today && order.status !== 'cancelled';
    }).reduce(function (sum, order) { return sum + Number(order.totalVnd || 0); }, 0);
    var paid = orders.filter(function (order) { return order.paymentStatus === 'paid'; }).length;
    var lowStock = inventory.filter(function (item) { return Number(item.qty) <= Number(item.min); }).length;
    var tomorrow = todayISO(1);
    var nextOrders = orders.filter(function (order) {
      return [today, tomorrow].indexOf(order.receiveDate) >= 0 && order.status !== 'cancelled' && order.status !== 'completed';
    }).length;

    setText('metricNewOrders', new24h);
    setText('metricPending', pending);
    setText('metricPreparing', preparing);
    setText('metricTodayDelivery', todayDelivery);
    setText('metricTodayRevenue', money(todayRevenue));
    setText('metricPaid', paid);
    setText('metricLowStock', lowStock);
    setText('metricNextOrders', nextOrders);

    var recent = document.getElementById('dashboardRecentOrders');
    if (recent) {
      recent.innerHTML = orders.slice(0, 5).map(function (order) {
        return '<tr>' +
          '<td><strong>' + esc(order.orderCode) + '</strong><div class="admin-help">' + esc(dateLabel(order.createdAt)) + '</div></td>' +
          '<td>' + esc(order.recipientName || '-') + '</td>' +
          '<td><span class="admin-status">' + esc(statusLabel(order.status)) + '</span></td>' +
          '<td>' + esc(paymentLabel(order.paymentStatus)) + '</td>' +
          '<td><strong>' + esc(money(order.totalVnd)) + '</strong></td>' +
        '</tr>';
      }).join('');
    }

    var stock = document.getElementById('dashboardLowStock');
    if (stock) {
      var low = inventory.filter(function (item) { return Number(item.qty) <= Number(item.min); });
      stock.innerHTML = low.length ? low.map(function (item) {
        var percent = Math.min(100, Math.round((item.qty / Math.max(item.min, 1)) * 100));
        return '<div class="admin-list-item"><div><strong>' + esc(item.name) + '</strong><small class="d-block mt-1">' + esc(item.qty + ' ' + item.unit + ' · Mức tối thiểu ' + item.min) + '</small></div><div style="width:88px"><div class="admin-progress"><span style="width:' + percent + '%"></span></div></div></div>';
      }).join('') : '<div class="admin-empty">Không có nguyên liệu sắp hết.</div>';
    }
  }

  function initOrders() {
    var host = document.getElementById('adminOrderRows');
    if (!host) return;

    var search = document.getElementById('orderSearch');
    var statusFilter = document.getElementById('orderStatusFilter');
    var paymentFilter = document.getElementById('paymentStatusFilter');

    function render() {
      var query = (search.value || '').trim().toLowerCase();
      var status = statusFilter.value;
      var payment = paymentFilter.value;

      var list = getOrders().filter(function (order) {
        var matchesQuery = !query || [order.orderCode, order.customerName, order.customerPhone, order.recipientName, order.recipientPhone].join(' ').toLowerCase().includes(query);
        return matchesQuery &&
          (status === 'all' || order.status === status) &&
          (payment === 'all' || order.paymentStatus === payment);
      });

      setText('adminOrderCount', list.length + ' đơn');

      host.innerHTML = list.length ? list.map(function (order) {
        return '<tr>' +
          '<td><strong>' + esc(order.orderCode) + '</strong><div class="admin-help">' + esc(dateLabel(order.createdAt)) + '</div></td>' +
          '<td><strong>' + esc(order.customerName || '-') + '</strong><div class="admin-help">' + esc(order.customerPhone || '') + '</div></td>' +
          '<td><strong>' + esc(order.recipientName || '-') + '</strong><div class="admin-help">' + esc(order.receiveDate || '-') + ' · ' + esc(order.receiveTime || '-') + '</div></td>' +
          '<td><strong>' + esc(money(order.totalVnd)) + '</strong></td>' +
          '<td><select class="admin-select" data-order-payment="' + esc(order.orderCode) + '">' +
            ['unpaid','partial','paid','refunded'].map(function (item) {
              return '<option value="' + item + '"' + (order.paymentStatus === item ? ' selected' : '') + '>' + paymentLabel(item) + '</option>';
            }).join('') +
          '</select></td>' +
          '<td><select class="admin-select" data-order-status="' + esc(order.orderCode) + '">' +
            ['pending_confirmation','confirmed','preparing','ready','delivering','completed','cancelled'].map(function (item) {
              return '<option value="' + item + '"' + (order.status === item ? ' selected' : '') + '>' + statusLabel(item) + '</option>';
            }).join('') +
          '</select></td>' +
        '</tr>';
      }).join('') : '<tr><td colspan="6"><div class="admin-empty">Không có đơn hàng phù hợp.</div></td></tr>';

      host.querySelectorAll('[data-order-status]').forEach(function (select) {
        select.addEventListener('change', function () {
          updateOrder(select.dataset.orderStatus, { status: select.value });
        });
      });

      host.querySelectorAll('[data-order-payment]').forEach(function (select) {
        select.addEventListener('change', function () {
          updateOrder(select.dataset.orderPayment, { paymentStatus: select.value });
        });
      });
    }

    [search, statusFilter, paymentFilter].forEach(function (element) {
      if (element) element.addEventListener(element.tagName === 'INPUT' ? 'input' : 'change', render);
    });

    render();
  }

  function initRefunds() {
    var host = document.getElementById('refundRows');
    if (!host) return;

    function render() {
      var refunds = getRefunds();
      host.innerHTML = refunds.map(function (refund, index) {
        return '<tr>' +
          '<td><strong>' + esc(refund.id) + '</strong><div class="admin-help">' + esc(dateLabel(refund.createdAt)) + '</div></td>' +
          '<td>' + esc(refund.orderCode) + '</td>' +
          '<td>' + esc(refund.customer) + '</td>' +
          '<td><strong>' + esc(money(refund.amount)) + '</strong></td>' +
          '<td>' + esc(refund.reason) + '</td>' +
          '<td><select class="admin-select" data-refund-index="' + index + '">' +
            ['requested','approved','completed','rejected'].map(function (status) {
              var label = { requested:'Chờ xử lý', approved:'Đã duyệt', completed:'Đã hoàn', rejected:'Từ chối' }[status];
              return '<option value="' + status + '"' + (refund.status === status ? ' selected' : '') + '>' + label + '</option>';
            }).join('') +
          '</select></td>' +
        '</tr>';
      }).join('');

      host.querySelectorAll('[data-refund-index]').forEach(function (select) {
        select.addEventListener('change', function () {
          var list = getRefunds();
          list[Number(select.dataset.refundIndex)].status = select.value;
          write(REFUND_KEY, list);
        });
      });

      setText('refundPendingCount', refunds.filter(function (item) { return item.status === 'requested'; }).length);
      setText('refundTotalAmount', money(refunds.filter(function (item) { return item.status === 'completed'; }).reduce(function (sum, item) { return sum + item.amount; }, 0)));
    }

    render();
  }

  function initDelivery() {
    var host = document.getElementById('deliveryList');
    if (!host) return;

    var dateInput = document.getElementById('deliveryDate');
    var statusFilter = document.getElementById('deliveryFilter');
    dateInput.value = dateInput.value || todayISO(0);

    function render() {
      var date = dateInput.value;
      var status = statusFilter.value;
      var list = getOrders().filter(function (order) {
        return order.receiveDate === date &&
          order.status !== 'cancelled' &&
          (status === 'all' || order.deliveryStatus === status);
      });

      setText('deliveryCount', list.length + ' lịch');

      host.innerHTML = list.length ? list.map(function (order) {
        return '<article class="admin-order-card">' +
          '<div class="admin-toolbar between">' +
            '<div><strong>' + esc(order.orderCode) + '</strong><div class="admin-help">' + esc(order.recipientName || '-') + ' · ' + esc(order.recipientPhone || '') + '</div></div>' +
            '<span class="admin-status">' + esc(deliveryLabel(order.deliveryStatus)) + '</span>' +
          '</div>' +
          '<div class="admin-section" style="margin-top:12px">' +
            '<strong>' + esc(order.isPickup ? 'Khách nhận tại cửa hàng' : (order.address || 'Chưa có địa chỉ')) + '</strong>' +
            '<div class="admin-help">' + esc(order.receiveTime || 'Chưa có khung giờ') + ' · Phí giao: ' + esc(money(order.shippingVnd || 0)) + '</div>' +
          '</div>' +
          '<div class="admin-toolbar mt-3">' +
            '<select class="admin-select" style="max-width:190px" data-delivery-order="' + esc(order.orderCode) + '">' +
              ['not_delivered','out_for_delivery','delivered','pickup_ready'].map(function (item) {
                return '<option value="' + item + '"' + (order.deliveryStatus === item ? ' selected' : '') + '>' + deliveryLabel(item) + '</option>';
              }).join('') +
            '</select>' +
            '<a class="admin-btn-light" href="don-hang.html">Mở đơn</a>' +
          '</div>' +
        '</article>';
      }).join('') : '<div class="admin-empty">Không có lịch giao hoặc nhận hoa trong ngày này.</div>';

      host.querySelectorAll('[data-delivery-order]').forEach(function (select) {
        select.addEventListener('change', function () {
          updateOrder(select.dataset.deliveryOrder, { deliveryStatus: select.value });
          render();
        });
      });
    }

    dateInput.addEventListener('change', render);
    statusFilter.addEventListener('change', render);
    render();
  }

  function customerRows() {
    var map = {};
    getOrders().forEach(function (order) {
      var phone = order.customerPhone || order.recipientPhone || 'Không có SĐT';
      if (!map[phone]) {
        map[phone] = {
          name: order.customerName || order.recipientName || 'Khách hàng',
          phone: phone,
          orders: 0,
          total: 0,
          last: order.createdAt
        };
      }
      map[phone].orders += 1;
      if (order.status !== 'cancelled') map[phone].total += Number(order.totalVnd || 0);
      if (new Date(order.createdAt || 0) > new Date(map[phone].last || 0)) map[phone].last = order.createdAt;
    });
    return Object.values(map).sort(function (a, b) { return b.total - a.total; });
  }

  function initCustomers() {
    var host = document.getElementById('adminCustomerRows');
    if (!host) return;
    var search = document.getElementById('customerSearch');

    function render() {
      var query = (search.value || '').trim().toLowerCase();
      var list = customerRows().filter(function (customer) {
        return !query || [customer.name, customer.phone].join(' ').toLowerCase().includes(query);
      });

      setText('adminCustomerCount', list.length + ' khách hàng');

      host.innerHTML = list.length ? list.map(function (customer) {
        return '<tr>' +
          '<td><strong>' + esc(customer.name) + '</strong></td>' +
          '<td>' + esc(customer.phone) + '</td>' +
          '<td>' + customer.orders + '</td>' +
          '<td><strong>' + esc(money(customer.total)) + '</strong></td>' +
          '<td>' + esc(dateLabel(customer.last)) + '</td>' +
          '<td><span class="admin-status' + (customer.orders >= 2 ? ' success' : '') + '">' + (customer.orders >= 2 ? 'Khách quay lại' : 'Khách mới') + '</span></td>' +
        '</tr>';
      }).join('') : '<tr><td colspan="6"><div class="admin-empty">Không có khách hàng phù hợp.</div></td></tr>';
    }

    search.addEventListener('input', render);
    render();
  }

  function getRevenueByDay(days) {
    var orders = getOrders();
    var result = [];

    for (var offset = days - 1; offset >= 0; offset -= 1) {
      var date = todayISO(-offset);
      var total = orders.filter(function (order) {
        return String(order.createdAt || '').slice(0,10) === date && order.status !== 'cancelled';
      }).reduce(function (sum, order) { return sum + Number(order.totalVnd || 0); }, 0);
      result.push({ date: date, total: total });
    }
    return result;
  }

  function initReports() {
    var chart = document.getElementById('revenueChart');
    if (!chart) return;

    var range = document.getElementById('reportRange');

    function render() {
      var days = Number(range.value || 7);
      var data = getRevenueByDay(days);
      var orders = getOrders().filter(function (order) {
        var age = Date.now() - new Date(order.createdAt || 0).getTime();
        return age <= days * 86400000 && order.status !== 'cancelled';
      });
      var revenue = orders.reduce(function (sum, order) { return sum + Number(order.totalVnd || 0); }, 0);
      var paid = orders.filter(function (order) { return order.paymentStatus === 'paid'; }).reduce(function (sum, order) { return sum + Number(order.totalVnd || 0); }, 0);
      var avg = orders.length ? revenue / orders.length : 0;

      setText('reportRevenue', money(revenue));
      setText('reportOrders', orders.length);
      setText('reportPaid', money(paid));
      setText('reportAverage', money(avg));

      var max = Math.max.apply(null, data.map(function (item) { return item.total; }).concat([1]));
      chart.innerHTML = data.map(function (item) {
        var height = Math.max(4, Math.round((item.total / max) * 100));
        var label = item.date.slice(8,10) + '/' + item.date.slice(5,7);
        return '<div class="admin-chart-col"><div class="admin-chart-bar-wrap"><div class="admin-chart-bar" title="' + esc(money(item.total)) + '" style="height:' + height + '%"></div></div><small>' + label + '</small></div>';
      }).join('');

      var top = document.getElementById('reportTopProducts');
      if (top) {
        var products = getProducts().slice().sort(function (a,b) { return b.price - a.price; }).slice(0,5);
        top.innerHTML = products.map(function (product, index) {
          return '<div class="admin-list-item"><div><strong>' + (index + 1) + '. ' + esc(product.name) + '</strong><small class="d-block mt-1">' + esc(product.type === 'basket' ? 'Giỏ hoa' : 'Bó hoa') + '</small></div><strong>' + esc(money(product.price)) + '</strong></div>';
        }).join('');
      }
    }

    range.addEventListener('change', render);
    render();
  }

  function initProducts() {
    var host = document.getElementById('adminProductRows');
    if (!host) return;
    var search = document.getElementById('adminProductSearch');

    function render() {
      var query = (search.value || '').trim().toLowerCase();
      var list = getProducts().filter(function (product) {
        return !query || [product.name, product.sku, (product.categories || []).join(' ')].join(' ').toLowerCase().includes(query);
      });

      setText('adminProductCount', list.length + ' sản phẩm');

      host.innerHTML = list.length ? list.map(function (product) {
        return '<tr>' +
          '<td><img class="admin-thumb" src="../' + esc(product.image) + '" alt="' + esc(product.name) + '"></td>' +
          '<td><strong>' + esc(product.name) + '</strong><div class="admin-help">' + esc(product.sku || '') + '</div></td>' +
          '<td>' + esc(product.type === 'basket' ? 'Giỏ hoa' : 'Bó hoa') + '</td>' +
          '<td>' + esc((product.categories || []).join(', ')) + '</td>' +
          '<td><strong>' + esc(money(product.price)) + '</strong></td>' +
          '<td><span class="admin-status' + (product.today ? ' success' : '') + '">' + (product.today ? 'Mẫu hôm nay' : 'Có thể đặt') + '</span></td>' +
          '<td><a class="admin-btn-light" href="sua-san-pham.html?id=' + encodeURIComponent(product.id) + '"><i class="bi bi-pencil"></i>Sửa</a></td>' +
        '</tr>';
      }).join('') : '<tr><td colspan="7"><div class="admin-empty">Không có sản phẩm phù hợp.</div></td></tr>';
    }

    search.addEventListener('input', render);
    render();
  }

  function initQuickProducts() {
    var host = document.getElementById('quickProductRows');
    if (!host) return;

    function render() {
      var list = getProducts();
      host.innerHTML = list.map(function (product) {
        return '<tr>' +
          '<td><strong>' + esc(product.name) + '</strong><div class="admin-help">' + esc(product.sku || '') + '</div></td>' +
          '<td><input class="admin-input" style="width:130px" type="number" min="0" step="1000" value="' + Number(product.price || 0) + '" data-quick-price="' + esc(product.id) + '"></td>' +
          '<td><label class="admin-check"><input type="checkbox" data-quick-today="' + esc(product.id) + '"' + (product.today ? ' checked' : '') + '> Mẫu hôm nay</label></td>' +
          '<td><label class="admin-check"><input type="checkbox" data-quick-featured="' + esc(product.id) + '"' + (product.featured ? ' checked' : '') + '> Nổi bật</label></td>' +
          '<td><button class="admin-btn-light" type="button" data-quick-save="' + esc(product.id) + '">Lưu</button></td>' +
        '</tr>';
      }).join('');

      host.querySelectorAll('[data-quick-save]').forEach(function (button) {
        button.addEventListener('click', function () {
          var id = button.dataset.quickSave;
          var price = document.querySelector('[data-quick-price="' + id + '"]').value;
          var today = document.querySelector('[data-quick-today="' + id + '"]').checked;
          var featured = document.querySelector('[data-quick-featured="' + id + '"]').checked;
          setProductPatch(id, { price: Number(price || 0), today: today, featured: featured });
          button.textContent = 'Đã lưu';
          setTimeout(function () { button.textContent = 'Lưu'; }, 1000);
        });
      });
    }

    render();
  }

  function initProductForm() {
    var form = document.getElementById('adminProductForm');
    if (!form) return;

    var editId = new URLSearchParams(location.search).get('id');
    var editing = editId ? findProduct(editId) : null;

    if (editing) {
      setText('productFormTitle', 'Sửa sản phẩm');
      setText('productSubmitText', 'Lưu thay đổi');
      document.getElementById('productName').value = editing.name || '';
      document.getElementById('productSku').value = editing.sku || '';
      document.getElementById('productPrice').value = editing.price || 0;
      document.getElementById('productType').value = editing.type || 'bouquet';
      document.getElementById('productTone').value = editing.tone || 'Xanh';
      document.getElementById('productOccasion').value = editing.occasion || 'Sinh nhật';
      document.getElementById('productCategory').value = (editing.categories || [])[0] || '';
      document.getElementById('productImage').value = (editing.image || 'assets/images/lam-tinh.png').replace('assets/images/','');
      document.getElementById('productDescription').value = editing.description || '';
      document.getElementById('productToday').checked = !!editing.today;
      document.getElementById('productFeatured').checked = !!editing.featured;
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
      }

      var category = document.getElementById('productCategory').value.trim() || 'Hoa theo mùa';
      var data = {
        name: document.getElementById('productName').value.trim(),
        sku: document.getElementById('productSku').value.trim(),
        price: Number(document.getElementById('productPrice').value || 0),
        type: document.getElementById('productType').value,
        tone: document.getElementById('productTone').value,
        occasion: document.getElementById('productOccasion').value,
        categories: [category],
        image: 'assets/images/' + document.getElementById('productImage').value,
        description: document.getElementById('productDescription').value.trim(),
        composition: document.getElementById('productComposition').value.trim() || 'Hoa và lá theo mùa.',
        today: document.getElementById('productToday').checked,
        featured: document.getElementById('productFeatured').checked
      };

      if (editing) {
        setProductPatch(editing.id, data);
      } else {
        var added = read(PRODUCT_ADDED_KEY, []);
        data.id = 'custom-' + Date.now();
        data.slug = data.name.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/đ/g,'d').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
        added.unshift(data);
        write(PRODUCT_ADDED_KEY, added);
        form.reset();
      }

      var message = document.getElementById('productFormMessage');
      message.textContent = editing ? 'Đã cập nhật thông tin sản phẩm.' : 'Đã thêm sản phẩm mới.';
      message.className = 'admin-feedback';
    });
  }

  function initWrapping() {
    var host = document.getElementById('wrappingRows');
    var form = document.getElementById('wrappingForm');
    if (!host || !form) return;

    function render() {
      var list = getWrapping();
      host.innerHTML = list.map(function (item, index) {
        return '<tr>' +
          '<td><span class="admin-color-dot" style="background:' + esc(item.hex) + '"></span></td>' +
          '<td><strong>' + esc(item.name) + '</strong></td>' +
          '<td>' + esc(item.hex) + '</td>' +
          '<td><label class="admin-check"><input type="checkbox" data-wrap-toggle="' + index + '"' + (item.enabled ? ' checked' : '') + '> Cho phép chọn</label></td>' +
        '</tr>';
      }).join('');

      host.querySelectorAll('[data-wrap-toggle]').forEach(function (input) {
        input.addEventListener('change', function () {
          var list = getWrapping();
          list[Number(input.dataset.wrapToggle)].enabled = input.checked;
          write(WRAPPING_KEY, list);
        });
      });
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var name = document.getElementById('wrapName').value.trim();
      var hex = document.getElementById('wrapHex').value;
      if (!name) return;
      var list = getWrapping();
      list.push({ id: 'wrap-' + Date.now(), name: name, hex: hex, enabled: true });
      write(WRAPPING_KEY, list);
      form.reset();
      document.getElementById('wrapHex').value = '#D8C9B8';
      render();
    });

    render();
  }

  function initTaxonomy() {
    var hostMap = {
      categories: document.getElementById('taxonomyCategories'),
      tones: document.getElementById('taxonomyTones'),
      occasions: document.getElementById('taxonomyOccasions')
    };

    if (!hostMap.categories) return;

    function render() {
      var data = getTaxonomy();
      Object.keys(hostMap).forEach(function (key) {
        hostMap[key].innerHTML = data[key].map(function (item) {
          return '<span class="admin-chip">' + esc(item) + '</span>';
        }).join('');
      });
    }

    document.querySelectorAll('[data-taxonomy-form]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        var key = form.dataset.taxonomyForm;
        var input = form.querySelector('input');
        var value = input.value.trim();
        if (!value) return;
        var data = getTaxonomy();
        if (data[key].indexOf(value) < 0) data[key].push(value);
        write(TAXONOMY_KEY, data);
        input.value = '';
        render();
      });
    });

    render();
  }

  function initDiscovery() {
    var host = document.getElementById('discoveryAdminGrid');
    if (!host) return;

    function render() {
      var list = getDiscovery();
      host.innerHTML = list.map(function (item, index) {
        return '<article class="admin-discovery-card">' +
          '<img src="' + esc(item.image) + '" alt="' + esc(item.title) + '">' +
          '<div class="admin-discovery-card-body">' +
            '<div class="admin-help">' + esc(item.subtitle) + '</div>' +
            '<strong>' + esc(item.title) + '</strong>' +
            '<div class="admin-help">' + esc(item.href) + '</div>' +
            '<label class="admin-check mt-3"><input type="checkbox" data-discovery-toggle="' + index + '"' + (item.enabled ? ' checked' : '') + '> Hiển thị trên trang chủ</label>' +
          '</div>' +
        '</article>';
      }).join('');

      host.querySelectorAll('[data-discovery-toggle]').forEach(function (input) {
        input.addEventListener('change', function () {
          var list = getDiscovery();
          list[Number(input.dataset.discoveryToggle)].enabled = input.checked;
          write(DISCOVERY_KEY, list);
        });
      });
    }

    render();
  }

  function inventoryStatus(item) {
    if (Number(item.qty) <= Number(item.min)) return { label: 'Sắp hết', cls: ' danger' };
    if (Number(item.qty) <= Number(item.min) * 1.5) return { label: 'Cần theo dõi', cls: ' warning' };
    return { label: 'Ổn định', cls: ' success' };
  }

  function renderInventoryTable(host, group) {
    var list = getInventory().filter(function (item) { return !group || item.group === group; });
    host.innerHTML = list.map(function (item) {
      var state = inventoryStatus(item);
      return '<tr>' +
        '<td><strong>' + esc(item.name) + '</strong><div class="admin-help">' + esc(item.id) + '</div></td>' +
        '<td><span class="admin-stock-number' + (state.cls === ' danger' ? ' admin-stock-low' : '') + '">' + esc(item.qty) + '</span> ' + esc(item.unit) + '</td>' +
        '<td>' + esc(item.min + ' ' + item.unit) + '</td>' +
        '<td>' + esc(money(item.cost)) + '</td>' +
        '<td><span class="admin-status' + state.cls + '">' + state.label + '</span></td>' +
      '</tr>';
    }).join('');
  }

  function initWarehouse() {
    var allHost = document.getElementById('warehouseRows');
    if (!allHost) return;

    var list = getInventory();
    var flowers = list.filter(function (item) { return item.group === 'flower'; });
    var accessories = list.filter(function (item) { return item.group === 'accessory'; });
    var low = list.filter(function (item) { return item.qty <= item.min; });

    setText('warehouseFlowerItems', flowers.length);
    setText('warehouseAccessoryItems', accessories.length);
    setText('warehouseLowStock', low.length);
    setText('warehouseValue', money(list.reduce(function (sum, item) { return sum + item.qty * item.cost; }, 0)));

    renderInventoryTable(allHost);
  }

  function initInventoryPage(group) {
    var host = document.getElementById('inventoryRows');
    var form = document.getElementById('inventoryAdjustForm');
    if (!host || !form) return;

    function render() {
      renderInventoryTable(host, group);
      var items = getInventory().filter(function (item) { return item.group === group; });
      var select = document.getElementById('inventoryItem');
      select.innerHTML = items.map(function (item) {
        return '<option value="' + esc(item.id) + '">' + esc(item.name + ' · ' + item.qty + ' ' + item.unit) + '</option>';
      }).join('');
      setText('inventoryItemCount', items.length + ' mặt hàng');
      setText('inventoryLowCount', items.filter(function (item) { return item.qty <= item.min; }).length + ' sắp hết');
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var id = document.getElementById('inventoryItem').value;
      var delta = Number(document.getElementById('inventoryDelta').value || 0);
      var note = document.getElementById('inventoryNote').value.trim();
      if (!id || !delta) return;

      var list = getInventory();
      var index = list.findIndex(function (item) { return item.id === id; });
      if (index < 0) return;

      list[index].qty = Math.max(0, Number(list[index].qty) + delta);
      saveInventory(list);
      addHistory({
        item: list[index].name,
        type: delta > 0 ? 'in' : 'out',
        qty: delta,
        note: note || (delta > 0 ? 'Nhập kho' : 'Xuất kho')
      });

      document.getElementById('inventoryDelta').value = '';
      document.getElementById('inventoryNote').value = '';
      render();
    });

    render();
  }

  function initStockHistory() {
    var host = document.getElementById('stockHistoryRows');
    if (!host) return;

    host.innerHTML = getHistory().map(function (entry) {
      return '<tr>' +
        '<td>' + esc(dateLabel(entry.at)) + '</td>' +
        '<td><strong>' + esc(entry.item) + '</strong></td>' +
        '<td><span class="admin-status' + (entry.qty > 0 ? ' success' : ' warning') + '">' + (entry.qty > 0 ? 'Nhập' : 'Xuất') + '</span></td>' +
        '<td><strong>' + (entry.qty > 0 ? '+' : '') + esc(entry.qty) + '</strong></td>' +
        '<td>' + esc(entry.note || '-') + '</td>' +
      '</tr>';
    }).join('');
  }

  function initStaff() {
    var host = document.getElementById('staffRows');
    var form = document.getElementById('staffForm');
    if (!host || !form) return;

    function render() {
      var list = getStaff();
      host.innerHTML = list.map(function (staff) {
        return '<tr>' +
          '<td><strong>' + esc(staff.name) + '</strong><div class="admin-help">' + esc(staff.email) + '</div></td>' +
          '<td>' + esc(staff.role) + '</td>' +
          '<td><span class="admin-status success">' + (staff.status === 'active' ? 'Đang hoạt động' : 'Tạm khoá') + '</span></td>' +
        '</tr>';
      }).join('');
      setText('staffCount', list.length + ' thành viên');
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var name = document.getElementById('staffName').value.trim();
      var email = document.getElementById('staffEmail').value.trim();
      var role = document.getElementById('staffRole').value;
      if (!name || !email) return;

      var list = getStaff();
      list.push({ id: 'st-' + Date.now(), name: name, email: email, role: role, status: 'active' });
      write(STAFF_KEY, list);
      form.reset();
      render();
    });

    render();
  }

  function fillFormFromObject(form, data) {
    Object.keys(data).forEach(function (key) {
      var field = form.elements.namedItem(key);
      if (field) field.value = data[key];
    });
  }

  function initSettings() {
    var form = document.getElementById('settingsForm');
    if (!form) return;

    fillFormFromObject(form, getSettings());

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var data = {};
      new FormData(form).forEach(function (value, key) { data[key] = value; });
      data.defaultShipping = Number(data.defaultShipping || 0);
      write(SETTINGS_KEY, data);
      var message = document.getElementById('settingsMessage');
      message.textContent = 'Đã lưu cài đặt cửa hàng.';
      message.className = 'admin-feedback';
    });
  }

  function initAccount() {
    var form = document.getElementById('adminAccountForm');
    if (!form) return;
    fillFormFromObject(form, getAccount());

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var data = {};
      new FormData(form).forEach(function (value, key) { data[key] = value; });
      data.role = getAccount().role;
      write(ACCOUNT_KEY, data);
      var message = document.getElementById('accountMessage');
      message.textContent = 'Đã cập nhật thông tin tài khoản.';
      message.className = 'admin-feedback';
      initShell();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (!ensureSession()) return;

    initShell();
    initLogin();
    initDashboard();
    initOrders();
    initRefunds();
    initDelivery();
    initCustomers();
    initReports();
    initProducts();
    initQuickProducts();
    initProductForm();
    initWrapping();
    initTaxonomy();
    initDiscovery();
    initWarehouse();
    initInventoryPage(page() === 'flowers' ? 'flower' : page() === 'accessories' ? 'accessory' : '');
    initStockHistory();
    initStaff();
    initSettings();
    initAccount();
  });
})();