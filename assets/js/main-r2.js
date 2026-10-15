(function () {
  'use strict';

  function escapeHtml(value) { return String(value == null ? '' : value).replace(/[&<>"']/g, function(c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]; }); }
  function esc(v) { return escapeHtml(v); }
  function shop(key, fallback) { var settings=window.CAS_SETTINGS||{}; return typeof settings[key]==='string'?settings[key]:fallback; }
  async function cartAction(button, operation, message) {
    if(button.disabled)return;
    button.disabled=true;
    button.setAttribute('aria-busy','true');
    try { await operation(); if(message)showToast(message); }
    catch(error){showToast(error.message || 'Chưa lưu được giỏ hàng. Hãy thử lại.');}
    finally {button.disabled=false;button.removeAttribute('aria-busy');}
  }

  function headerMarkup() {
    var page = document.body.dataset.page || '';
    function active(name) { return page === name ? ' active' : ''; }
    return '' +
      '<div class="cas-site-shell">' +
        (shop('announcement', 'Có sẵn thiệp và túi · Shop xác nhận đơn sau khi nhận thông tin') ? '<div class="cas-announcement">' + esc(shop('announcement', '')) + '</div>' : '') +
        '<header>' +
          '<div class="cas-container cas-navbar d-flex align-items-center justify-content-between gap-3">' +
            '<a href="index.php" class="cas-brand" aria-label="Về trang chủ CÁ\'S HOA">' +
              '<span class="cas-brand-mark"><i class="bi bi-flower1"></i></span>' +
              '<span><span class="cas-brand-name">CÁ\'S HOA</span><span class="cas-brand-tagline">flowers & feelings</span></span>' +
            '</a>' +
            '<nav class="d-none d-md-flex align-items-center gap-4" aria-label="Điều hướng chính">' +
              '<a class="cas-nav-link' + active('home') + '" href="index.php">Trang chủ</a>' +
              '<a class="cas-nav-link' + active('catalog') + '" href="san-pham.php?type=bouquet">Bó hoa</a>' +
              '<a class="cas-nav-link" href="san-pham.php?type=basket">Giỏ hoa</a>' +
              '<a class="cas-nav-link" href="tin-tuc.php">Tin tức</a><a class="cas-nav-link" href="lien-he.php">Liên hệ</a>' +
            '</nav>' +
            '<div class="d-flex align-items-center gap-1">' +
              '<a class="cas-icon-btn" href="san-pham.php" aria-label="Tìm kiếm sản phẩm"><i class="bi bi-search"></i></a>' +
              '<a class="cas-account-link d-none d-sm-inline-flex" href="tai-khoan.php">Tài khoản</a>' +
              '<a class="cas-icon-btn surface position-relative" href="gio-hang.php" aria-label="Giỏ hàng"><i class="bi bi-bag"></i><span class="cas-cart-count d-none" id="headerCartCount">0</span></a>' +
              '<button class="cas-icon-btn d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#casMobileNav" aria-controls="casMobileNav" aria-expanded="false" aria-label="Mở menu"><i class="bi bi-list fs-5"></i></button>' +
            '</div>' +
          '</div>' +
          '<div class="collapse cas-mobile-nav d-md-none" id="casMobileNav"><nav class="cas-container py-2"><a href="index.php">Trang chủ</a><a href="san-pham.php?type=bouquet">Bó hoa</a><a href="san-pham.php?type=basket">Giỏ hoa</a><a href="gio-hang.php">Giỏ hàng</a><a href="tra-cuu-don-hang.php">Tra cứu đơn</a><a href="tai-khoan.php">Tài khoản</a><a href="tin-tuc.php">Tin tức</a><a href="lien-he.php">Liên hệ</a></nav></div>' +
        '</header>' +
      '</div>';
  }

  function footerMarkup() {
    return '' +
      '<footer class="cas-footer" id="footer">' +
        '<div class="cas-container cas-footer-main"><div class="row g-4">' +
          '<div class="col-md-5"><div class="cas-brand"><span class="cas-brand-mark"><i class="bi bi-flower1"></i></span><span class="cas-brand-name">CÁ\'S HOA</span></div><p class="mt-3 mb-0" style="max-width:380px">Tiệm hoa tươi online. Hoa cho những điều khó nói. Luôn kèm sẵn thiệp và túi.</p></div>' +
          '<div class="col-6 col-md-3"><div class="cas-footer-title">Liên hệ</div><p class="mt-3 mb-0"><a href="' + esc(shop('instagram', 'https://www.instagram.com')) + '" target="_blank" rel="noreferrer">Instagram</a><br><a href="tel:' + esc(shop('hotline', '0707309255')) + '">' + esc(shop('hotline', '0707309255')) + '</a><br><a href="lien-he.php">Liên hệ</a><br><a href="tra-cuu-don-hang.php">Tra cứu đơn</a></p></div>' +
          '<div class="col-6 col-md-2"><div class="cas-footer-title">Thông tin</div><p class="mt-3 mb-0"><a href="gioi-thieu.php">Giới thiệu</a><br><a href="tin-tuc.php">Tin tức</a><br><a href="chinh-sach-giao-hang.php">Giao hàng</a><br><a href="chinh-sach-doi-tra.php">Đổi trả</a></p></div><div class="col-12 col-md-2"><div class="cas-footer-title">Địa chỉ</div><p class="mt-3 mb-0">' + esc(shop('address', '126/13 đường số 17, Linh Xuân, Thủ Đức, TP.HCM')) + '</p></div>' +
        '</div></div>' +
        '<div class="cas-footer-bottom"><div class="cas-container d-flex flex-column flex-sm-row gap-2 justify-content-between"><span>© 2026 CÁ\'S HOA</span><span>Hoa tươi · Thiệp gửi lời thương</span></div></div>' +
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
    if(window.bootstrap) bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4000 }).show(); else toastEl.classList.add('show');
  }

  function productCard(product) {
    var badge = product.today ? '<span class="product-badge">Mẫu hôm nay</span>' : '';
    return '<article class="product-card">' +
      '<div class="product-image-wrap">' + badge +
        '<a href="chi-tiet-san-pham.php?slug=' + encodeURIComponent(product.slug) + '"><img src="' + escapeHtml(product.image) + '" alt="' + escapeHtml(product.name) + '" loading="lazy"></a>' +
        '<button class="product-quick-add" type="button" data-add-product="' + escapeHtml(product.id) + '" aria-label="Thêm ' + escapeHtml(product.name) + ' vào giỏ"><i class="bi bi-plus-lg"></i></button>' +
      '</div>' +
      '<div class="product-info"><a href="chi-tiet-san-pham.php?slug=' + encodeURIComponent(product.slug) + '"><h3 class="product-name">' + escapeHtml(product.name) + '</h3></a><div class="product-price">' + window.CAS_FORMAT_VND(product.price) + '</div><div class="product-meta">' + escapeHtml(product.categories.join(' · ')) + '</div></div>' +
    '</article>';
  }

  function bindAddButtons(root) {
    (root || document).querySelectorAll('[data-add-product]').forEach(function (button) {
      button.addEventListener('click', function () {
        var product = window.CAS_FIND_PRODUCT(button.dataset.addProduct);
        if (!product) return;
        cartAction(button, function(){return window.CASCart.add(product.id,1);}, 'Đã thêm “'+product.name+'” vào giỏ hàng.');
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
          return '<div class="col-4"><a class="hero-image-link" href="chi-tiet-san-pham.php?slug=' + encodeURIComponent(product.slug) + '"><img src="' + escapeHtml(product.image) + '" alt="' + escapeHtml(product.name) + '"></a></div>';
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


  function galleryMarkup(product) {
    var images=Array.isArray(product.gallery)?product.gallery:[];
    if(images.length<2)return '';
    return '<div class="d-flex flex-wrap gap-2 mt-3" aria-label="Ảnh sản phẩm">' +
      images.map(function(src,index){
        return '<button type="button" class="p-0 border rounded-3 overflow-hidden bg-white'+(index===0?' border-dark':'')+'" style="width:72px;height:72px" data-gallery-src="'+escapeHtml(src)+'" aria-label="Xem ảnh '+(index+1)+'"><img src="'+escapeHtml(src)+'" alt="" style="width:100%;height:100%;object-fit:cover"></button>';
      }).join('')+'</div>';
  }

  function initDetail() {
    var host = document.getElementById('productDetailHost');
    if (!host) return;
    var params = new URLSearchParams(window.location.search);
    var product = window.CAS_FIND_PRODUCT(params.get('slug')); if (!product) { host.innerHTML='<section class="customer-empty"><h1>Không tìm thấy mẫu hoa</h1><p>Mẫu hoa đã ngừng bán hoặc đường dẫn không tồn tại.</p><a class="btn-cas-primary" href="san-pham.php">Xem các mẫu khác</a></section>'; document.querySelector('.related-section').hidden=true; return; }
    document.title = product.name + ' | CÁ\'S HOA';
    host.innerHTML = '<div class="breadcrumb-cas"><a href="index.php">Trang chủ</a> / <a href="san-pham.php">Sản phẩm</a> / <span>' + escapeHtml(product.name) + '</span></div>' +
      '<div class="row g-4 g-lg-5 align-items-start"><div class="col-lg-6"><div class="detail-image-main"><img id="detailMainImage" src="' + escapeHtml(product.image) + '" alt="' + escapeHtml(product.name) + '"></div>' + galleryMarkup(product) + '</div>' +
      '<div class="col-lg-6"><section class="detail-panel"><p class="cas-eyebrow">' + (product.today ? 'Mẫu hôm nay' : 'Đặt trước') + '</p><h1 class="cas-title" style="font-size:clamp(2rem,4vw,3.2rem)">' + escapeHtml(product.name) + '</h1><div class="detail-price">' + window.CAS_FORMAT_VND(product.price) + '</div><p class="detail-description">' + escapeHtml(product.description) + '</p>' +
      '<div class="detail-divider"></div><div class="detail-feature"><i class="bi bi-envelope-paper-heart"></i><div><strong>Kèm thiệp và túi</strong><div class="cas-muted small mt-1">Mỗi đơn hoa đều có sẵn thiệp và túi phù hợp.</div></div></div><div class="detail-feature"><i class="bi bi-flower2"></i><div><strong>Hoa tươi theo mùa</strong><div class="cas-muted small mt-1">' + escapeHtml(product.composition) + '</div></div></div><div class="detail-divider"></div>' +
      ((product.wrappings||[]).length ? '<label class="form-label" for="detailWrapping">Màu giấy gói</label><select id="detailWrapping" class="form-select cas-form-select mb-3">'+product.wrappings.map(function(w){return '<option value="'+w.id+'">'+escapeHtml(w.name)+(Number(w.surcharge)>0?' (+'+window.CAS_FORMAT_VND(w.surcharge)+')':'')+'</option>';}).join('')+'</select><p class="cas-muted small">Phụ thu giấy gói (nếu có) được tính vào tạm tính giỏ hàng theo giá hiện tại.</p>' : '') +
      '<div class="d-flex flex-wrap align-items-center gap-3"><div class="quantity-picker"><button type="button" id="qtyMinus" aria-label="Giảm số lượng"><i class="bi bi-dash"></i></button><input id="detailQty" value="1" inputmode="numeric" aria-label="Số lượng"><button type="button" id="qtyPlus" aria-label="Tăng số lượng"><i class="bi bi-plus"></i></button></div><button class="btn-cas-primary flex-grow-1" id="detailAdd"><i class="bi bi-bag-plus"></i> Thêm vào giỏ hàng</button></div>' +
      '<a class="btn-cas-outline w-100 mt-3" href="gio-hang.php">Xem giỏ hàng</a></section></div></div>';

    host.querySelectorAll('[data-gallery-src]').forEach(function(button){
      button.addEventListener('click',function(){
        var image=document.getElementById('detailMainImage');
        if(image)image.src=button.dataset.gallerySrc;
        host.querySelectorAll('[data-gallery-src]').forEach(function(b){b.classList.toggle('border-dark',b===button);});
      });
    });
    var qtyInput = document.getElementById('detailQty');
    function normalizeQty() { qtyInput.value = String(Math.max(1, Math.min(20, Math.trunc(Number(qtyInput.value)) || 1))); }
    document.getElementById('qtyMinus').addEventListener('click', function () { qtyInput.value = String((Number(qtyInput.value) || 1) - 1); normalizeQty(); });
    document.getElementById('qtyPlus').addEventListener('click', function () { qtyInput.value = String((Number(qtyInput.value) || 1) + 1); normalizeQty(); });
    qtyInput.addEventListener('change', normalizeQty);
    document.getElementById('detailAdd').addEventListener('click', function () {
      normalizeQty();
      cartAction(this, function(){return window.CASCart.add(product.id,Number(qtyInput.value),document.getElementById('detailWrapping')?.value);}, 'Đã thêm “'+product.name+'” vào giỏ hàng.');
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
        return '<article class="cart-line"><a class="cart-line-image" href="chi-tiet-san-pham.php?slug=' + encodeURIComponent(line.product.slug) + '"><img src="' + escapeHtml(line.product.image) + '" alt="' + escapeHtml(line.product.name) + '"></a><div><a href="chi-tiet-san-pham.php?slug=' + encodeURIComponent(line.product.slug) + '" class="cart-line-name">' + escapeHtml(line.product.name) + '</a><div class="cart-line-price">' + window.CAS_FORMAT_VND(line.product.price) + '</div><div class="cas-muted small mt-2">' + escapeHtml(line.product.categories.join(' · ')) + '</div>'+(line.wrappingName?'<div class="cas-muted small mt-1">Giấy gói: '+escapeHtml(line.wrappingName)+'</div>':'')+'</div><div class="cart-line-actions text-lg-end"><div class="quantity-picker"><button type="button" data-cart-minus="' + escapeHtml(line.product.id) + '" aria-label="Giảm số lượng"><i class="bi bi-dash"></i></button><input value="' + line.quantity + '" readonly aria-label="Số lượng"><button type="button" data-cart-plus="' + escapeHtml(line.product.id) + '" aria-label="Tăng số lượng"><i class="bi bi-plus"></i></button></div><div class="mt-2 fw-bold">' + window.CAS_FORMAT_VND(line.lineTotal) + '</div><button class="cart-remove mt-2" type="button" data-cart-remove="' + escapeHtml(line.product.id) + '">Xóa</button></div></article>';
      }).join('');
      var unavailable=window.CASCart.unavailable();
      if(unavailable.length){var row=document.createElement('div');row.className='auth-message error';row.textContent=unavailable.length+' mẫu hoa trong giỏ đã ngừng bán. ';var button=document.createElement('button');button.type='button';button.className='btn-cas-outline';button.textContent='Xóa các mẫu ngừng bán';button.onclick=function(){cartAction(button,function(){return window.CASCart.removeUnavailable();});};row.append(button);list.append(row);}
      empty.classList.toggle('d-none', lines.length > 0 || unavailable.length>0);
      summary.classList.toggle('d-none', lines.length === 0);
      var subtotal = window.CASCart.subtotal();
      document.querySelectorAll('[data-cart-subtotal]').forEach(function (node) { node.textContent = window.CAS_FORMAT_VND(subtotal); });
      document.querySelectorAll('[data-cart-total]').forEach(function (node) { node.textContent = window.CAS_FORMAT_VND(subtotal); });
      updateCartCount();

      list.querySelectorAll('[data-cart-minus]').forEach(function (button) {
        button.addEventListener('click', function () {
          var line = window.CASCart.read().find(function (item) { return item.productId === button.dataset.cartMinus; });
          if (!line) return;
          cartAction(button,function(){return window.CASCart.adjust(line.productId,-1);});
        });
      });
      list.querySelectorAll('[data-cart-plus]').forEach(function (button) {
        button.addEventListener('click', function () {
          var line = window.CASCart.read().find(function (item) { return item.productId === button.dataset.cartPlus; });
          if (!line) return;
          cartAction(button,function(){return window.CASCart.adjust(line.productId,1);});
        });
      });
      list.querySelectorAll('[data-cart-remove]').forEach(function (button) {
        button.addEventListener('click', function () { cartAction(button,function(){return window.CASCart.remove(button.dataset.cartRemove);},'Đã xóa sản phẩm khỏi giỏ hàng.'); });
      });
    }
    window.addEventListener('cas-cart-updated',render);
    render();
  }

  function initCheckout() {
    var summaryList = document.getElementById('checkoutSummaryList');
    var form = document.getElementById('checkoutForm');
    if (!summaryList || !form) return;
    var lines = window.CASCart.detailed();
    if(window.CASCart.unavailable().length){form.innerHTML='<div class="auth-message error">Có mẫu hoa không còn bán. <a href="gio-hang.php">Cập nhật giỏ hàng</a> trước khi đặt.</div>';return;}
    var emptyNotice = document.getElementById('checkoutEmpty');
    var submitButton = document.getElementById('checkoutSubmit');
    if (!lines.length) {
      emptyNotice.classList.remove('d-none');
      form.classList.add('d-none');
      document.getElementById('checkoutAside').classList.add('d-none');
      return;
    }
    summaryList.innerHTML = lines.map(function (line) {
      return '<div class="checkout-mini-line"><img src="' + escapeHtml(line.product.image) + '" alt="' + escapeHtml(line.product.name) + '"><div><strong>' + escapeHtml(line.product.name) + '</strong><small>Số lượng: ' + line.quantity + '</small>'+(line.wrappingName?'<small>Giấy gói: '+escapeHtml(line.wrappingName)+'</small>':'')+'</div><span class="small fw-bold">' + window.CAS_FORMAT_VND(line.lineTotal) + '</span></div>';
    }).join('');
    document.querySelectorAll('[data-checkout-subtotal]').forEach(function(node){node.textContent=window.CAS_FORMAT_VND(window.CASCart.subtotal());});
    function updateCheckoutTotals(){
      var isDelivery=document.querySelector('[name="fulfillment"]:checked').value==='delivery';
      var fee=isDelivery?Math.max(0,Number((window.CAS_SETTINGS||{}).default_shipping_fee)||0):0;
      document.querySelectorAll('[data-checkout-shipping]').forEach(function(node){node.textContent=window.CAS_FORMAT_VND(fee);});
      document.querySelectorAll('[data-checkout-total]').forEach(function(node){node.textContent=window.CAS_FORMAT_VND(window.CASCart.subtotal()+fee);});
    }

    function toggleAddress() {
      var delivery = document.querySelector('[name="fulfillment"]:checked').value === 'delivery';
      document.getElementById('deliveryFields').classList.toggle('d-none', !delivery);
      document.getElementById('address').required = delivery;
      updateCheckoutTotals();
    }
    document.querySelectorAll('[name="fulfillment"]').forEach(function (radio) { radio.addEventListener('change', toggleAddress); });
    toggleAddress();

    var dateInput=document.getElementById('receiveDate'),slot=document.getElementById('receiveTime');
    function checkDate(){var invalid=!dateInput.value || dateInput.value<dateInput.min;slot.disabled=invalid;if(invalid)slot.value='';}
    dateInput.addEventListener('change',checkDate);checkDate();
    var message=document.getElementById('checkoutMessage');
    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      if(submitButton.disabled)return;
      if (!form.checkValidity()) { form.classList.add('was-validated'); form.reportValidity(); return; }
      submitButton.disabled = true;
      submitButton.setAttribute('aria-busy','true');
      var text = submitButton.innerHTML; submitButton.textContent = 'Đang lưu đơn...';
      try {
        var data = new FormData(form); data.set('csrf',window.CAS_CSRF||'');
        data.set('cart',JSON.stringify(window.CASCart.read()));
        await window.CASCart.sync();
        var response = await fetch('actions/checkout.php',{method:'POST',body:data,credentials:'same-origin'});
        var result=await response.json();
        if(!response.ok||!result.ok) throw new Error(result.message||'Không thể tạo đơn');
        location.href='dat-hang-thanh-cong.php?code='+encodeURIComponent(result.orderCode);
      } catch(e) { message.textContent=e.message||'Mất kết nối. Hãy thử lại; đơn đã nhận sẽ không tạo lần hai.';message.className='auth-message error checkout-message';message.focus();submitButton.disabled=false;submitButton.removeAttribute('aria-busy');submitButton.innerHTML=text; }
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
    if((window.CAS_PRODUCTS_ERROR || !Array.isArray(window.CAS_PRODUCTS)) && ['home','catalog','detail','cart','checkout'].includes(page)){
      var host=document.querySelector('main'),error=document.createElement('section');
      error.className='customer-empty cas-container';error.setAttribute('role','alert');
      error.innerHTML='<h1>Chưa tải được danh sách hoa</h1><p>Kết nối tạm thời gián đoạn. Giỏ hàng của bạn vẫn được giữ.</p><button type="button" class="btn-cas-primary">Tải lại</button>';
      error.querySelector('button').onclick=function(){location.reload();};host.replaceChildren(error);return;
    }
    if (page === 'home') initHome();
    if (page === 'catalog' && window.casInitCatalog) window.casInitCatalog(productCard, bindAddButtons);
    if (page === 'detail') initDetail();
    if (page === 'cart') initCart();
    if (page === 'checkout') initCheckout();
    window.addEventListener('cas-cart-updated', updateCartCount);
  });
})();
