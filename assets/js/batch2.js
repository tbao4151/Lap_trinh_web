(function () {
  'use strict';

  var ORDER_KEY = 'cas-hoa-static-orders';

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  function normalizePhone(value) {
    var compact = String(value || '').replace(/[\s().-]/g, '');
    if (compact.indexOf('+84') === 0) return '0' + compact.slice(3);
    if (compact.indexOf('84') === 0 && compact.length === 11) return '0' + compact.slice(2);
    return compact;
  }

  function readOrders() {
    try {
      var parsed = JSON.parse(localStorage.getItem(ORDER_KEY) || '[]');
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      return [];
    }
  }

  function writeOrders(orders) {
    localStorage.setItem(ORDER_KEY, JSON.stringify(orders.slice(0, 30)));
  }

  function randomSuffix() {
    var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    var bytes = new Uint8Array(8);
    if (window.crypto && window.crypto.getRandomValues) window.crypto.getRandomValues(bytes);
    else for (var i = 0; i < bytes.length; i += 1) bytes[i] = Math.floor(Math.random() * 256);
    return Array.from(bytes).map(function (byte) { return chars[byte % chars.length]; }).join('');
  }

  function createFromCheckout(form, detailedItems, total) {
    var data = new FormData(form);
    var now = new Date();
    var order = {
      orderCode: 'CSH-' + now.getFullYear() + '-' + randomSuffix(),
      status: 'pending_confirmation',
      totalVnd: Number(total || 0),
      createdAt: now.toISOString(),
      customerName: String(data.get('customerName') || '').trim(),
      customerPhone: normalizePhone(data.get('customerPhone')),
      recipientName: String(data.get('recipientName') || '').trim(),
      recipientPhone: normalizePhone(data.get('recipientPhone')),
      fulfillment: String(data.get('fulfillment') || 'delivery'),
      address: String(data.get('address') || '').trim(),
      receiveDate: String(data.get('receiveDate') || ''),
      receiveTime: String(data.get('receiveTime') || ''),
      cardMessage: String(data.get('cardMessage') || '').trim(),
      orderNote: String(data.get('orderNote') || '').trim(),
      items: (detailedItems || []).map(function (line) {
        return { productId: line.product.id, name: line.product.name, quantity: line.quantity, price: line.product.price, lineTotal: line.lineTotal };
      })
    };
    var orders = readOrders();
    orders.unshift(order);
    writeOrders(orders);
    try { sessionStorage.setItem('cas-hoa-latest-order', order.orderCode); } catch (error) {}
    return order;
  }

  function findOrder(code, phone) {
    var normalizedCode = String(code || '').trim().toUpperCase();
    var normalizedPhone = normalizePhone(phone);
    return readOrders().find(function (order) {
      return order.orderCode === normalizedCode && normalizePhone(order.recipientPhone) === normalizedPhone;
    }) || null;
  }

  window.CASOrders = { read: readOrders, createFromCheckout: createFromCheckout, find: findOrder };

  function setMessage(id, message, type) {
    var box = document.getElementById(id);
    if (!box) return;
    box.textContent = message;
    box.className = 'auth-message ' + (type || 'info');
  }

  function bindPasswordToggles() {
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
      button.addEventListener('click', function () {
        var input = document.getElementById(button.dataset.togglePassword);
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-label', show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        var icon = button.querySelector('i');
        if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
      });
    });
  }

  function bindGoogleDemo() {
    document.querySelectorAll('[data-auth-demo-google]').forEach(function (button) {
      button.addEventListener('click', function () {
        var card = button.closest('.auth-card');
        var existing = card && card.querySelector('.google-demo-message');
        if (!existing && card) {
          existing = document.createElement('div');
          existing.className = 'auth-message info google-demo-message';
          existing.textContent = 'Chức năng đăng nhập bằng Google chưa được kích hoạt trong phiên bản hiện tại.';
          button.insertAdjacentElement('afterend', existing);
        }
      });
    });
  }

  function initLogin() {
    var form = document.getElementById('loginForm');
    if (!form) return;
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var identifier = document.getElementById('loginIdentifier').value.trim();
      var password = document.getElementById('loginPassword').value;
      if (!identifier || !password) {
        setMessage('loginMessage', 'Vui lòng nhập Gmail hoặc số điện thoại và mật khẩu.', 'error');
        return;
      }
      var identifierOk = /^[^@\s]+@gmail\.com$/i.test(identifier) || /^0\d{9}$/.test(normalizePhone(identifier));
      if (!identifierOk) {
        setMessage('loginMessage', 'Gmail hoặc số điện thoại chưa đúng định dạng.', 'error');
        return;
      }
      setMessage('loginMessage', 'Thông tin đăng nhập hợp lệ.', 'success');
    });
  }

  function strongPassword(value) {
    return value.length >= 10 && value.length <= 128 && /[A-Z]/.test(value) && /[a-z]/.test(value) && /\d/.test(value) && /[^A-Za-z0-9]/.test(value);
  }

  function initSignup() {
    var form = document.getElementById('signupForm');
    if (!form) return;
    var phone = document.getElementById('signupPhone');
    phone.addEventListener('input', function () { phone.value = phone.value.replace(/\D/g, '').slice(0, 10); });
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var email = document.getElementById('signupEmail').value.trim();
      var password = document.getElementById('signupPassword').value;
      var confirmPassword = document.getElementById('signupConfirm').value;
      if (!/^0\d{9}$/.test(phone.value)) {
        setMessage('signupMessage', 'Số điện thoại phải gồm đúng 10 chữ số và bắt đầu bằng 0.', 'error'); return;
      }
      if (!/^[^@\s]+@gmail\.com$/i.test(email)) {
        setMessage('signupMessage', 'Vui lòng sử dụng địa chỉ Gmail @gmail.com.', 'error'); return;
      }
      if (!strongPassword(password)) {
        setMessage('signupMessage', 'Mật khẩu cần ít nhất 10 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt.', 'error'); return;
      }
      if (password !== confirmPassword) {
        setMessage('signupMessage', 'Mật khẩu xác nhận không khớp.', 'error'); return;
      }
      setMessage('signupMessage', 'Thông tin đăng ký hợp lệ. Vui lòng kiểm tra Gmail để tiếp tục xác nhận tài khoản.', 'success');
    });
  }

  function initForgot() {
    var requestForm = document.getElementById('recoveryForm');
    var verifyForm = document.getElementById('recoveryVerifyForm');
    if (!requestForm || !verifyForm) return;
    var identifier = document.getElementById('recoveryIdentifier');
    var code = document.getElementById('recoveryCode');
    code.addEventListener('input', function () { code.value = code.value.replace(/\D/g, '').slice(0, 8); });

    requestForm.addEventListener('submit', function (event) {
      event.preventDefault();
      var value = identifier.value.trim();
      var valid = /^[^@\s]+@gmail\.com$/i.test(value) || /^0\d{9}$/.test(normalizePhone(value));
      if (!valid) {
        setMessage('recoveryMessage', 'Vui lòng nhập Gmail @gmail.com hoặc số điện thoại 10 chữ số.', 'error');
        return;
      }
      setMessage('recoveryMessage', 'Thông tin hợp lệ. Vui lòng nhập mã khôi phục đã nhận.', 'success');
      requestForm.classList.add('d-none');
      verifyForm.classList.remove('d-none');
      code.focus();
    });

    verifyForm.addEventListener('submit', function (event) {
      event.preventDefault();
      if (!/^\d{8}$/.test(code.value)) {
        setMessage('recoveryVerifyMessage', 'Vui lòng nhập đủ 8 chữ số trong mã khôi phục.', 'error');
        return;
      }
      setMessage('recoveryVerifyMessage', 'Mã khôi phục hợp lệ. Bạn có thể tiếp tục đặt lại mật khẩu khi chức năng tài khoản được kích hoạt.', 'success');
    });

    document.getElementById('recoveryChange').addEventListener('click', function () {
      code.value = '';
      verifyForm.classList.add('d-none');
      requestForm.classList.remove('d-none');
      document.getElementById('recoveryVerifyMessage').className = 'auth-message d-none';
      identifier.focus();
    });
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
    })[status] || status;
  }

  function formatDate(value) {
    try { return new Date(value).toLocaleString('vi-VN'); } catch (error) { return value; }
  }

  function formatMoney(value) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(Number(value || 0));
  }

  function initLookup() {
    var form = document.getElementById('lookupForm');
    if (!form) return;
    var code = document.getElementById('lookupCode');
    var phone = document.getElementById('lookupPhone');
    phone.addEventListener('input', function () { phone.value = phone.value.replace(/\D/g, '').slice(0, 10); });

    var params = new URLSearchParams(window.location.search);
    if (params.get('code')) code.value = params.get('code').toUpperCase();
    if (params.get('phone')) phone.value = normalizePhone(params.get('phone'));

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var normalizedCode = code.value.trim().toUpperCase();
      var normalizedPhone = normalizePhone(phone.value);
      if (!/^(?:CSH-\d{4}-[A-Z0-9]{8})$/.test(normalizedCode)) {
        setMessage('lookupMessage', 'Mã đơn chưa đúng định dạng CSH-NĂM-XXXXXXXX.', 'error'); return;
      }
      if (!/^0\d{9}$/.test(normalizedPhone)) {
        setMessage('lookupMessage', 'Số điện thoại người nhận phải gồm đúng 10 chữ số.', 'error'); return;
      }
      var order = findOrder(normalizedCode, normalizedPhone);
      var results = document.getElementById('lookupResults');
      var card = document.getElementById('lookupResultCard');
      if (!order) {
        setMessage('lookupMessage', 'Không tìm thấy đơn hàng phù hợp. Hãy kiểm tra lại mã đơn và số điện thoại người nhận.', 'error');
        results.classList.add('d-none');
        return;
      }
      document.getElementById('lookupMessage').className = 'auth-message d-none';
      card.innerHTML = '<article class="lookup-result"><div class="d-flex flex-wrap justify-content-between gap-3"><div><div class="fw-bold">' + escapeHtml(order.orderCode) + '</div><div class="cas-muted small mt-1">' + escapeHtml(formatDate(order.createdAt)) + '</div></div><div class="text-end"><span class="lookup-status">' + escapeHtml(statusLabel(order.status)) + '</span><div class="fw-bold mt-2" style="color:var(--primary)">' + escapeHtml(formatMoney(order.totalVnd)) + '</div></div></div><div class="row g-3 mt-2 small"><div class="col-md-6"><span class="cas-muted">Người nhận</span><div class="fw-semibold mt-1">' + escapeHtml(order.recipientName) + '</div></div><div class="col-md-6"><span class="cas-muted">Thời gian nhận</span><div class="fw-semibold mt-1">' + escapeHtml(order.receiveDate + ' · ' + order.receiveTime) + '</div></div></div></article>';
      results.classList.remove('d-none');
      results.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    if (code.value && phone.value) form.requestSubmit();
  }

  function initSuccess() {
    var codeNode = document.getElementById('successOrderCode');
    if (!codeNode) return;
    var code = (new URLSearchParams(window.location.search).get('code') || '').trim().toUpperCase();
    if (!code) {
      try { code = sessionStorage.getItem('cas-hoa-latest-order') || ''; } catch (error) {}
    }
    var order = readOrders().find(function (item) { return item.orderCode === code; }) || null;
    codeNode.textContent = code || 'Chưa có mã đơn';

    var details = document.getElementById('successOrderDetails');
    if (!order) {
      details.innerHTML = '<p class="auth-message info mb-0">Không tìm thấy chi tiết đơn hàng. Vui lòng kiểm tra lại mã đơn hoặc quay lại cửa hàng.</p>';
      return;
    }
    details.innerHTML =
      '<div class="success-detail-row"><span>Trạng thái</span><strong>' + escapeHtml(statusLabel(order.status)) + '</strong></div>' +
      '<div class="success-detail-row"><span>Người nhận</span><strong>' + escapeHtml(order.recipientName) + '</strong></div>' +
      '<div class="success-detail-row"><span>SĐT người nhận</span><strong>' + escapeHtml(order.recipientPhone) + '</strong></div>' +
      '<div class="success-detail-row"><span>Ngày nhận</span><strong>' + escapeHtml(order.receiveDate || 'Chưa chọn') + '</strong></div>' +
      '<div class="success-detail-row"><span>Khung giờ</span><strong>' + escapeHtml(order.receiveTime || 'Chưa chọn') + '</strong></div>' +
      '<div class="success-detail-row"><span>Tổng tạm tính</span><strong style="color:var(--primary)">' + escapeHtml(formatMoney(order.totalVnd)) + '</strong></div>';
    var link = document.getElementById('successLookupLink');
    if (link) link.href = 'tra-cuu-don-hang.html?code=' + encodeURIComponent(order.orderCode) + '&phone=' + encodeURIComponent(order.recipientPhone);
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindPasswordToggles();
    bindGoogleDemo();
    var page = document.body.dataset.page;
    if (page === 'login') initLogin();
    if (page === 'signup') initSignup();
    if (page === 'forgot') initForgot();
    if (page === 'lookup') initLookup();
    if (page === 'success') initSuccess();
  });
})();