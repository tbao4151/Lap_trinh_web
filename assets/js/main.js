(function () {
  'use strict';

  function pageUrl(page) { return page; }

  function headerMarkup() {
    var page = document.body.dataset.page || '';
    function active(name) { return page === name ? ' active' : ''; }
    return '' +
      '<div class="cas-site-shell">' +
        '<div class="cas-announcement">Có sẵn thiệp và túi · Shop xác nhận đơn sau khi nhận thông tin</div>' +
        '<header>' +
          '<div class="cas-container cas-navbar d-flex align-items-center justify-content-between gap-3">' +
            '<a href="index.html" class="cas-brand" aria-label="Về trang chủ CÁ\'S HOA">' +
              '<span class="cas-brand-mark"><i class="bi bi-flower1"></i></span>' +
              '<span><span class="cas-brand-name">CÁ\'S HOA</span><span class="cas-brand-tagline">flowers & feelings</span></span>' +
            '</a>' +
            '<nav class="d-none d-md-flex align-items-center gap-4" aria-label="Điều hướng chính">' +
              '<a class="cas-nav-link' + active('home') + '" href="index.html">Trang chủ</a>' +
              '<a class="cas-nav-link' + active('catalog') + '" href="san-pham.html?type=bouquet">Bó hoa</a>' +
              '<a class="cas-nav-link" href="san-pham.html?type=basket">Giỏ hoa</a>' +
              '<a class="cas-nav-link" href="#footer">Liên hệ</a>' +
            '</nav>' +
            '<div class="d-flex align-items-center gap-1">' +
              '<a class="cas-icon-btn" href="san-pham.html" aria-label="Tìm kiếm sản phẩm"><i class="bi bi-search"></i></a>' +
              '<a class="cas-account-link d-none d-sm-inline-flex" href="#" data-demo-link>Đăng nhập</a>' +
              '<a class="cas-icon-btn surface position-relative" href="gio-hang.html" aria-label="Giỏ hàng"><i class="bi bi-bag"></i><span class="cas-cart-count d-none" id="headerCartCount">0</span></a>' +
              '<button class="cas-icon-btn d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#casMobileNav" aria-controls="casMobileNav" aria-expanded="false" aria-label="Mở menu"><i class="bi bi-list fs-5"></i></button>' +
            '</div>' +
          '</div>' +
          '<div class="collapse cas-mobile-nav d-md-none" id="casMobileNav"><nav class="cas-container py-2"><a href="index.html">Trang chủ</a><a href="san-pham.html?type=bouquet">Bó hoa</a><a href="san-pham.html?type=basket">Giỏ hoa</a><a href="gio-hang.html">Giỏ hàng</a><a href="#footer">Liên hệ</a></nav></div>' +
        '</header>' +
      '</div>';
  }

  function footerMarkup() {
    return '' +
      '<footer class="cas-footer" id="footer">' +
        '<div class="cas-container cas-footer-main"><div class="row g-4">' +
          '<div class="col-md-5"><div class="cas-brand"><span class="cas-brand-mark"><i class="bi bi-flower1"></i></span><span class="cas-brand-name">CÁ\'S HOA</span></div><p class="mt-3 mb-0" style="max-width:380px">Tiệm hoa tươi online. Hoa cho những điều khó nói. Luôn kèm sẵn thiệp và túi.</p></div>' +
          '<div class="col-6 col-md-3"><div class="cas-footer-title">Liên hệ</div><p class="mt-3 mb-0"><a href="https://www.instagram.com" target="_blank" rel="noreferrer">Instagram</a><br><a href="#" data-demo-link>Zalo</a><br><a href="#" data-demo-link>Tra cứu đơn</a></p></div>' +
          '<div class="col-6 col-md-4"><div class="cas-footer-title">Địa chỉ</div><p class="mt-3 mb-0">126/13 đường số 17<br>Linh Xuân, Thủ Đức, TP.HCM</p></div>' +
        '</div></div>' +
        '<div class="cas-footer-bottom"><div class="cas-container d-flex flex-column flex-sm-row gap-2 justify-content-between"><span>© 2026 CÁ\'S HOA</span><span>FrontEnd môn Lập trình Web · HTML · CSS · JavaScript · Bootstrap</span></div></div>' +
      '</footer>';
  }

  function renderLayout() {
    var header = document.getElementById('siteHeader');
    var footer = document.getElementById('siteFooter');
    if (header) header.innerHTML = headerMarkup();
    if (footer) footer.innerHTML = footerMarkup();
    updateCartCount();
  }

  function updateCartCount() {
    var badge = document.getElementById('headerCartCount');
    if (!badge || !window.CASCart) return;
    var count = window.CASCart.count();
    badge.textContent = String(count);
    badge.classList.toggle('d-none', count === 0);
  }

  function showToast(message) {
    var toastEl = document.getElementById('casToast');
    if (!toastEl) {
      var wrapper = document.createElement('div');
      wrapper.className = 'toast-container position-fixed bottom-0 end-0 p-3';
      wrapper.style.zIndex = '1080';
      wrapper.innerHTML = '<div id="casToast" class="toast cas-toast" role="status" aria-live="polite" aria-atomic="true"><div class="d-flex"><div class="toast-body" id="casToastBody"></div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Đóng"></button></div></div>';
      document.body.appendChild(wrapper);
      toastEl = document.getElementById('casToast');
    }
    document.getElementById('casToastBody').textContent = message;
    bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 2400 }).show();
  }

  function productCard(product) {
    var badge = product.today ? '<span class="product-badge">Mẫu hôm nay</span>' : '';
    return '<article class="product-card">' +
      '<div class="product-image-wrap">' + badge +
        '<a href="chi-tiet-san-pham.html?slug=' + encodeURIComponent(product.slug) + '"><img src="' + product.image + '" alt="' + product.name + '" loading="lazy"></a>' +
        '<button class="product-quick-add" type="button" data-add-product="' + product.id + '" aria-label="Thêm ' + product.name + ' vào giỏ"><i class="bi bi-plus-lg"></i></button>' +
      '</div>' +
      '<div class="product-info"><a href="chi-tiet-san-pham.html?slug=' + encodeURIComponent(product.slug) + '"><h3 class="product-name">' + product.name + '</h3></a><div class="product-price">' + window.CAS_FORMAT_VND(product.price) + '</div><div class="product-meta">' + product.categories.join(' · ') + '</div></div>' +
    '</article>';
  }

  function bindAddButtons(root) {
    (root || document).querySelectorAll('[data-add-product]').forEach(function (button) {
      button.addEventListener('click', function () {
        var product = window.CAS_FIND_PRODUCT(button.dataset.addProduct);
        if (!product) return;
        window.CASCart.add(product.id, 1);
        updateCartCount();
        showToast('Đã thêm “' + product.name + '” vào giỏ hàng.');
      });
    });
  }

  function initHome() {
    var heroTrack = document.getElementById('heroTrack');
    var featuredGrid = document.getElementById('featuredGrid');
    if (heroTrack) {
      var heroProducts = window.CAS_PRODUCTS.slice(0, 6);
      var chunks = [heroProducts.slice(0, 3), heroProducts.slice(3, 6)];
      heroTrack.innerHTML = chunks.map(function (chunk, index) {
        return '<div class="carousel-item' + (index === 0 ? ' active' : '') + '"><div class="row g-2 g-sm-3 g-lg-4">' + chunk.map(function (product) {
          return '<div class="col-4"><a class="hero-image-link" href="chi-tiet-san-pham.html?slug=' + product.slug + '"><img src="' + product.image + '" alt="' + product.name + '"></a></div>';
        }).join('') + '</div></div>';
      }).join('');
    }
    if (featuredGrid) {
      featuredGrid.innerHTML = window.CAS_PRODUCTS.filter(function (p) { return p.featured; }).slice(0, 4).map(function (product) {
        return '<div class="col-6 col-lg-3">' + productCard(product) + '</div>';
      }).join('');
      bindAddButtons(featuredGrid);
    }
  }

  function currentCatalogFilters() {
    var params = new URLSearchParams(window.location.search);
    return { type: params.get('type') || 'all', today: params.get('today') === '1' };
  }

  function initCatalog() {
    var grid = document.getElementById('catalogGrid');
    if (!grid) return;
    var searchInput = document.getElementById('catalogSearch');
    var sortSelect = document.getElementById('catalogSort');
    var resultCount = document.getElementById('resultCount');
    var empty = document.getElementById('catalogEmpty');
    var typeChecks = document.querySelectorAll('[name="productType"]');
    var categoryChecks = document.querySelectorAll('[name="flowerCategory"]');
    var toneChecks = document.querySelectorAll('[name="tone"]');
    var maxPrice = document.getElementById('maxPrice');
    var maxPriceLabel = document.getElementById('maxPriceLabel');
    var urlFilter = currentCatalogFilters();

    if (urlFilter.type !== 'all') {
      typeChecks.forEach(function (input) { input.checked = input.value === urlFilter.type; });
    }

    function checkedValue(nodes, fallback) {
      var item = Array.from(nodes).find(function (node) { return node.checked; });
      return item ? item.value : fallback;
    }

    function render() {
      var query = (searchInput.value || '').trim().toLowerCase();
      var type = checkedValue(typeChecks, 'all');
      var category = checkedValue(categoryChecks, 'all');
      var tone = checkedValue(toneChecks, 'all');
      var ceiling = Number(maxPrice.value || 500000);
      if (maxPriceLabel) maxPriceLabel.textContent = window.CAS_FORMAT_VND(ceiling);

      var list = window.CAS_PRODUCTS.filter(function (product) {
        var matchesQuery = !query || [product.name, product.categories.join(' '), product.occasion].join(' ').toLowerCase().includes(query);
        var matchesType = type === 'all' || product.type === type;
        var matchesCategory = category === 'all' || product.categories.includes(category);
        var matchesTone = tone === 'all' || product.tone === tone;
        var matchesPrice = product.price <= ceiling;
        var matchesToday = !urlFilter.today || product.today;
        return matchesQuery && matchesType && matchesCategory && matchesTone && matchesPrice && matchesToday;
      });

      var sort = sortSelect.value;
      if (sort === 'low') list.sort(function (a, b) { return a.price - b.price; });
      if (sort === 'high') list.sort(function (a, b) { return b.price - a.price; });
      if (sort === 'name') list.sort(function (a, b) { return a.name.localeCompare(b.name, 'vi'); });

      grid.innerHTML = list.map(function (product) { return '<div class="col-6 col-xl-4">' + productCard(product) + '</div>'; }).join('');
      if (resultCount) resultCount.textContent = list.length + ' sản phẩm';
      if (empty) empty.classList.toggle('d-none', list.length !== 0);
      bindAddButtons(grid);
    }

    [searchInput, sortSelect, maxPrice].forEach(function (el) { if (el) el.addEventListener('input', render); });
    typeChecks.forEach(function (el) { el.addEventListener('change', render); });
    categoryChecks.forEach(function (el) { el.addEventListener('change', render); });
    toneChecks.forEach(function (el) { el.addEventListener('change', render); });
    document.querySelectorAll('[data-reset-filters]').forEach(function (button) {
      button.addEventListener('click', function () {
        searchInput.value = '';
        sortSelect.value = 'featured';
        typeChecks.forEach(function (el) { el.checked = el.value === 'all'; });
        categoryChecks.forEach(function (el) { el.checked = el.value === 'all'; });
        toneChecks.forEach(function (el) { el.checked = el.value === 'all'; });
        maxPrice.value = '500000';
        urlFilter.today = false;
        render();
      });
    });
    render();
  }

  function initDetail() {
    var host = document.getElementById('productDetailHost');
    if (!host) return;
    var params = new URLSearchParams(window.location.search);
    var product = window.CAS_FIND_PRODUCT(params.get('slug')) || window.CAS_PRODUCTS[0];
    document.title = product.name + ' | CÁ\'S HOA';
    host.innerHTML = '<div class="breadcrumb-cas"><a href="index.html">Trang chủ</a> / <a href="san-pham.html">Sản phẩm</a> / <span>' + product.name + '</span></div>' +
      '<div class="row g-4 g-lg-5 align-items-start"><div class="col-lg-6"><div class="detail-image-main"><img src="' + product.image + '" alt="' + product.name + '"></div></div>' +
      '<div class="col-lg-6"><section class="detail-panel"><p class="cas-eyebrow">' + (product.today ? 'Mẫu hôm nay' : 'Đặt trước') + '</p><h1 class="cas-title" style="font-size:clamp(2rem,4vw,3.2rem)">' + product.name + '</h1><div class="detail-price">' + window.CAS_FORMAT_VND(product.price) + '</div><p class="detail-description">' + product.description + '</p>' +
      '<div class="detail-divider"></div><div class="detail-feature"><i class="bi bi-envelope-paper-heart"></i><div><strong>Kèm thiệp và túi</strong><div class="cas-muted small mt-1">Mỗi đơn hoa đều có sẵn thiệp và túi phù hợp.</div></div></div><div class="detail-feature"><i class="bi bi-flower2"></i><div><strong>Hoa tươi theo mùa</strong><div class="cas-muted small mt-1">' + product.composition + '</div></div></div><div class="detail-divider"></div>' +
      '<div class="d-flex flex-wrap align-items-center gap-3"><div class="quantity-picker"><button type="button" id="qtyMinus" aria-label="Giảm số lượng"><i class="bi bi-dash"></i></button><input id="detailQty" value="1" inputmode="numeric" aria-label="Số lượng"><button type="button" id="qtyPlus" aria-label="Tăng số lượng"><i class="bi bi-plus"></i></button></div><button class="btn-cas-primary flex-grow-1" id="detailAdd"><i class="bi bi-bag-plus"></i> Thêm vào giỏ hàng</button></div>' +
      '<a class="btn-cas-outline w-100 mt-3" href="gio-hang.html">Xem giỏ hàng</a></section></div></div>';

    var qtyInput = document.getElementById('detailQty');
    function normalizeQty() { qtyInput.value = String(Math.max(1, Math.min(20, Number(qtyInput.value) || 1))); }
    document.getElementById('qtyMinus').addEventListener('click', function () { qtyInput.value = String((Number(qtyInput.value) || 1) - 1); normalizeQty(); });
    document.getElementById('qtyPlus').addEventListener('click', function () { qtyInput.value = String((Number(qtyInput.value) || 1) + 1); normalizeQty(); });
    qtyInput.addEventListener('change', normalizeQty);
    document.getElementById('detailAdd').addEventListener('click', function () {
      normalizeQty();
      window.CASCart.add(product.id, Number(qtyInput.value));
      updateCartCount();
      showToast('Đã thêm “' + product.name + '” vào giỏ hàng.');
    });

    var related = document.getElementById('relatedGrid');
    if (related) {
      related.innerHTML = window.CAS_PRODUCTS.filter(function (p) { return p.id !== product.id; }).slice(0, 4).map(function (p) { return '<div class="col-6 col-lg-3">' + productCard(p) + '</div>'; }).join('');
      bindAddButtons(related);
    }
  }

  function initCart() {
    var list = document.getElementById('cartLines');
    var empty = document.getElementById('emptyCart');
    var summary = document.getElementById('cartSummary');
    if (!list) return;

    function render() {
      var lines = window.CASCart.detailed();
      list.innerHTML = lines.map(function (line) {
        return '<article class="cart-line"><a class="cart-line-image" href="chi-tiet-san-pham.html?slug=' + line.product.slug + '"><img src="' + line.product.image + '" alt="' + line.product.name + '"></a><div><a href="chi-tiet-san-pham.html?slug=' + line.product.slug + '" class="cart-line-name">' + line.product.name + '</a><div class="cart-line-price">' + window.CAS_FORMAT_VND(line.product.price) + '</div><div class="cas-muted small mt-2">' + line.product.categories.join(' · ') + '</div></div><div class="cart-line-actions text-lg-end"><div class="quantity-picker"><button type="button" data-cart-minus="' + line.product.id + '" aria-label="Giảm số lượng"><i class="bi bi-dash"></i></button><input value="' + line.quantity + '" readonly aria-label="Số lượng"><button type="button" data-cart-plus="' + line.product.id + '" aria-label="Tăng số lượng"><i class="bi bi-plus"></i></button></div><div class="mt-2 fw-bold">' + window.CAS_FORMAT_VND(line.lineTotal) + '</div><button class="cart-remove mt-2" type="button" data-cart-remove="' + line.product.id + '">Xóa</button></div></article>';
      }).join('');
      empty.classList.toggle('d-none', lines.length > 0);
      summary.classList.toggle('d-none', lines.length === 0);
      var subtotal = window.CASCart.subtotal();
      document.querySelectorAll('[data-cart-subtotal]').forEach(function (node) { node.textContent = window.CAS_FORMAT_VND(subtotal); });
      document.querySelectorAll('[data-cart-total]').forEach(function (node) { node.textContent = window.CAS_FORMAT_VND(subtotal); });
      updateCartCount();

      list.querySelectorAll('[data-cart-minus]').forEach(function (button) {
        button.addEventListener('click', function () {
          var line = window.CASCart.read().find(function (item) { return item.productId === button.dataset.cartMinus; });
          if (!line) return;
          if (line.quantity <= 1) window.CASCart.remove(line.productId); else window.CASCart.update(line.productId, line.quantity - 1);
          render();
        });
      });
      list.querySelectorAll('[data-cart-plus]').forEach(function (button) {
        button.addEventListener('click', function () {
          var line = window.CASCart.read().find(function (item) { return item.productId === button.dataset.cartPlus; });
          if (!line) return;
          window.CASCart.update(line.productId, line.quantity + 1);
          render();
        });
      });
      list.querySelectorAll('[data-cart-remove]').forEach(function (button) {
        button.addEventListener('click', function () { window.CASCart.remove(button.dataset.cartRemove); render(); showToast('Đã xóa sản phẩm khỏi giỏ hàng.'); });
      });
    }
    render();
  }

  function initCheckout() {
    var summaryList = document.getElementById('checkoutSummaryList');
    var form = document.getElementById('checkoutForm');
    if (!summaryList || !form) return;
    var lines = window.CASCart.detailed();
    var emptyNotice = document.getElementById('checkoutEmpty');
    var submitButton = document.getElementById('checkoutSubmit');
    if (!lines.length) {
      emptyNotice.classList.remove('d-none');
      form.classList.add('d-none');
      document.getElementById('checkoutAside').classList.add('d-none');
      return;
    }
    summaryList.innerHTML = lines.map(function (line) {
      return '<div class="checkout-mini-line"><img src="' + line.product.image + '" alt="' + line.product.name + '"><div><strong>' + line.product.name + '</strong><small>Số lượng: ' + line.quantity + '</small></div><span class="small fw-bold">' + window.CAS_FORMAT_VND(line.lineTotal) + '</span></div>';
    }).join('');
    document.querySelectorAll('[data-checkout-subtotal], [data-checkout-total]').forEach(function (node) { node.textContent = window.CAS_FORMAT_VND(window.CASCart.subtotal()); });

    function toggleAddress() {
      var delivery = document.querySelector('[name="fulfillment"]:checked').value === 'delivery';
      document.getElementById('deliveryFields').classList.toggle('d-none', !delivery);
      document.getElementById('address').required = delivery;
    }
    document.querySelectorAll('[name="fulfillment"]').forEach(function (radio) { radio.addEventListener('change', toggleAddress); });
    toggleAddress();

    var dateInput = document.getElementById('receiveDate');
    if (dateInput) {
      var tomorrow = new Date(Date.now() + 86400000);
      var year = tomorrow.getFullYear();
      var month = String(tomorrow.getMonth() + 1).padStart(2, '0');
      var day = String(tomorrow.getDate()).padStart(2, '0');
      dateInput.min = year + '-' + month + '-' + day;
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
      submitButton.disabled = true;
      submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Đang kiểm tra';
      window.setTimeout(function () {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="bi bi-check2-circle"></i> Xác nhận đặt hoa';
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('frontendDemoModal'));
        modal.show();
      }, 550);
    });
  }

  function initDemoLinks() {
    document.querySelectorAll('[data-demo-link]').forEach(function (link) {
      link.addEventListener('click', function (event) { event.preventDefault(); showToast('Màn hình này sẽ được bổ sung ở batch tiếp theo.'); });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    renderLayout();
    initDemoLinks();
    var page = document.body.dataset.page;
    if (page === 'home') initHome();
    if (page === 'catalog') initCatalog();
    if (page === 'detail') initDetail();
    if (page === 'cart') initCart();
    if (page === 'checkout') initCheckout();
    window.addEventListener('cas-cart-updated', updateCartCount);
  });
})();
