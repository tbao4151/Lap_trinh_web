(function () {
  'use strict';
  var PROFILE_KEY = 'cas-hoa-profile';
  var ORDER_KEY = 'cas-hoa-static-orders';
  var CONTACT_KEY = 'cas-hoa-contact-messages';

  function read(key, fallback) {
    try { var data = JSON.parse(localStorage.getItem(key) || 'null'); return data == null ? fallback : data; }
    catch (error) { return fallback; }
  }
  function write(key, value) { localStorage.setItem(key, JSON.stringify(value)); }
  function esc(value) { return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[c]; }); }
  function money(value) { return new Intl.NumberFormat('vi-VN', { style:'currency', currency:'VND', maximumFractionDigits:0 }).format(Number(value || 0)); }
  function statusLabel(status) { return ({pending_confirmation:'Chờ xác nhận',confirmed:'Đã xác nhận',preparing:'Đang chuẩn bị',ready:'Sẵn sàng',delivering:'Đang giao',completed:'Hoàn tất',cancelled:'Đã huỷ'})[status] || status || 'Chờ xác nhận'; }

  function initAccount() {
    var form = document.getElementById('profileForm');
    if (!form) return;
    var profile = read(PROFILE_KEY, {});
    ['fullName','phone','email','address'].forEach(function (id) { if (profile[id]) document.getElementById(id).value = profile[id]; });
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var phone = document.getElementById('phone').value.replace(/\D/g,'');
      var email = document.getElementById('email').value.trim();
      if (phone && !/^0\d{9}$/.test(phone)) { show('profileMessage','Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0.','error'); return; }
      if (email && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) { show('profileMessage','Email chưa đúng định dạng.','error'); return; }
      var next = {
        fullName: document.getElementById('fullName').value.trim(),
        phone: phone,
        email: email,
        address: document.getElementById('address').value.trim()
      };
      write(PROFILE_KEY, next);
      document.getElementById('profileName').textContent = next.fullName || 'Khách hàng';
      show('profileMessage','Đã lưu thông tin cá nhân.','success');
    });
    document.getElementById('profileName').textContent = profile.fullName || 'Khách hàng';
  }

  function show(id, message, type) {
    var node = document.getElementById(id);
    if (!node) return;
    node.textContent = message;
    node.className = 'auth-message ' + (type || 'info');
  }

  function initOrderHistory() {
    var host = document.getElementById('orderHistory');
    if (!host) return;
    var orders = read(ORDER_KEY, []);
    document.getElementById('orderCount').textContent = String(orders.length);
    if (!orders.length) {
      host.innerHTML = '<div class="empty-state"><i class="bi bi-receipt fs-3" style="color:var(--primary)"></i><h2 class="font-display fs-4 mt-3">Chưa có đơn hàng.</h2><p class="cas-muted small">Hãy thử tạo một đơn từ trang thanh toán để kiểm tra lịch sử đơn.</p><a class="btn-cas-primary mt-2" href="san-pham.html">Chọn hoa</a></div>';
      return;
    }
    host.innerHTML = orders.map(function (order) {
      return '<article class="order-history-card"><div class="d-flex flex-wrap justify-content-between gap-3"><div><strong>' + esc(order.orderCode) + '</strong><div class="cas-muted small mt-1">' + esc(new Date(order.createdAt).toLocaleString('vi-VN')) + '</div></div><div class="text-end"><span class="stat-chip">' + esc(statusLabel(order.status)) + '</span><div class="fw-bold mt-2" style="color:var(--primary)">' + esc(money(order.totalVnd)) + '</div></div></div><div class="row g-2 mt-2 small"><div class="col-md-6"><span class="cas-muted">Người nhận:</span> ' + esc(order.recipientName) + '</div><div class="col-md-6"><span class="cas-muted">Ngày nhận:</span> ' + esc(order.receiveDate || '-') + '</div></div><a class="btn-cas-outline mt-3" href="tra-cuu-don-hang.html?code=' + encodeURIComponent(order.orderCode) + '&phone=' + encodeURIComponent(order.recipientPhone) + '">Tra cứu đơn</a></article>';
    }).join('');
  }

  function initContact() {
    var form = document.getElementById('contactForm');
    if (!form) return;
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
      var data = new FormData(form);
      var messages = read(CONTACT_KEY, []);
      messages.unshift({ name:String(data.get('name')||''), phone:String(data.get('phone')||''), subject:String(data.get('subject')||''), message:String(data.get('message')||''), createdAt:new Date().toISOString() });
      write(CONTACT_KEY, messages.slice(0,20));
      form.reset();
      form.classList.remove('was-validated');
      show('contactMessage','Cảm ơn bạn. Nội dung liên hệ đã được ghi nhận.','success');
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var page = document.body.dataset.page;
    if (page === 'account') initAccount();
    if (page === 'orders') initOrderHistory();
    if (page === 'contact') initContact();
  });
})();