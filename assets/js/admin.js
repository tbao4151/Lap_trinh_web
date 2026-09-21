(function(){
'use strict';
var SESSION='cas-hoa-admin-session';
var ORDER_KEY='cas-hoa-static-orders';
var ADDED_KEY='cas-hoa-admin-products';
var OVERRIDE_KEY='cas-hoa-admin-product-overrides';

function read(k,f){try{var d=JSON.parse(localStorage.getItem(k)||'null');return d==null?f:d}catch(e){return f}}
function write(k,v){localStorage.setItem(k,JSON.stringify(v))}
function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[c]})}
function money(v){return new Intl.NumberFormat('vi-VN',{style:'currency',currency:'VND',maximumFractionDigits:0}).format(Number(v||0))}
function statusLabel(s){return({pending_confirmation:'Chờ xác nhận',confirmed:'Đã xác nhận',preparing:'Đang chuẩn bị',ready:'Sẵn sàng',delivering:'Đang giao',completed:'Hoàn tất',cancelled:'Đã huỷ'})[s]||s||'Chờ xác nhận'}
function page(){return document.body.dataset.adminPage||''}
function active(name){return page()===name?' active':''}
function getProducts(){
  var base=(window.CAS_PRODUCTS||[]).map(function(p){return Object.assign({},p)});
  var overrides=read(OVERRIDE_KEY,{});
  base=base.map(function(p){return Object.assign({},p,overrides[p.id]||{})});
  return base.concat(read(ADDED_KEY,[]));
}
function findProduct(id){return getProducts().find(function(p){return p.id===id})||null}
function sidebar(){return '<div class="admin-brand"><span class="admin-brand-mark"><i class="bi bi-flower1"></i></span><span>CÁ\'S HOA</span></div><nav class="admin-nav"><a class="'+active('dashboard')+'" href="index.html"><i class="bi bi-grid"></i>Tổng quan</a><a class="'+active('products')+'" href="san-pham.html"><i class="bi bi-flower2"></i>Sản phẩm</a><a class="'+active('product-form')+'" href="them-san-pham.html"><i class="bi bi-plus-circle"></i>Thêm sản phẩm</a><a class="'+active('orders')+'" href="don-hang.html"><i class="bi bi-receipt"></i>Đơn hàng</a><a class="'+active('customers')+'" href="khach-hang.html"><i class="bi bi-people"></i>Khách hàng</a><a href="../index.html"><i class="bi bi-shop"></i>Xem cửa hàng</a></nav>'}
function topbar(){return '<div><div class="admin-kicker">Khu vực quản trị</div><strong>CÁ\'S HOA Admin</strong></div><button id="adminLogout" class="admin-btn-light" type="button"><i class="bi bi-box-arrow-right me-1"></i>Đăng xuất</button>'}
function ensure(){if(page()==='login')return true;if(localStorage.getItem(SESSION)!=='1'){location.replace('dang-nhap.html');return false}return true}
function shell(){var s=document.getElementById('adminSidebar'),t=document.getElementById('adminTopbar');if(s)s.innerHTML=sidebar();if(t)t.innerHTML=topbar();var l=document.getElementById('adminLogout');if(l)l.onclick=function(){localStorage.removeItem(SESSION);location.replace('dang-nhap.html')}}
function login(){var f=document.getElementById('adminLoginForm');if(!f)return;f.addEventListener('submit',function(e){e.preventDefault();var u=document.getElementById('adminEmail').value.trim(),p=document.getElementById('adminPassword').value,m=document.getElementById('adminLoginMessage');if(!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(u)||p.length<4){m.textContent='Vui lòng nhập email hợp lệ và mật khẩu từ 4 ký tự.';m.className='alert alert-danger mt-3 mb-0';return}localStorage.setItem(SESSION,'1');location.href='index.html'})}
function dashboard(){if(page()!=='dashboard')return;var orders=read(ORDER_KEY,[]),products=getProducts();document.getElementById('dashProducts').textContent=products.length;document.getElementById('dashOrders').textContent=orders.length;document.getElementById('dashPending').textContent=orders.filter(function(o){return o.status==='pending_confirmation'}).length;document.getElementById('dashRevenue').textContent=money(orders.reduce(function(s,o){return s+Number(o.totalVnd||0)},0));var host=document.getElementById('recentOrders');host.innerHTML=orders.length?orders.slice(0,5).map(function(o){return '<tr><td><strong>'+esc(o.orderCode)+'</strong></td><td>'+esc(o.recipientName)+'</td><td><span class="admin-badge">'+esc(statusLabel(o.status))+'</span></td><td>'+esc(money(o.totalVnd))+'</td></tr>'}).join(''):'<tr><td colspan="4">Chưa có đơn demo.</td></tr>'}

function renderProducts(){
  var host=document.getElementById('adminProductRows');if(!host)return;
  var search=document.getElementById('adminProductSearch');
  function render(){
    var q=(search.value||'').trim().toLowerCase();
    var list=getProducts().filter(function(p){return !q||[p.name,p.sku,(p.categories||[]).join(' ')].join(' ').toLowerCase().includes(q)});
    document.getElementById('adminProductCount').textContent=list.length+' sản phẩm';
    host.innerHTML=list.map(function(p){return '<tr><td><img src="../'+esc(p.image)+'" alt="" style="width:54px;height:54px;object-fit:cover;border-radius:12px"></td><td><strong>'+esc(p.name)+'</strong><div class="text-secondary small">'+esc(p.sku||'')+'</div></td><td>'+esc(p.type==='basket'?'Giỏ hoa':'Bó hoa')+'</td><td>'+esc(money(p.price))+'</td><td><span class="admin-badge">'+(p.today?'Đang bán hôm nay':'Có thể đặt')+'</span></td><td><a class="admin-btn-light" href="sua-san-pham.html?id='+encodeURIComponent(p.id)+'">Sửa</a></td></tr>'}).join('');
    if(!list.length)host.innerHTML='<tr><td colspan="6">Không có sản phẩm phù hợp.</td></tr>';
  }
  search.addEventListener('input',render);render();
}

function productForm(){
  var form=document.getElementById('adminProductForm');if(!form)return;
  var editId=new URLSearchParams(location.search).get('id');
  var editing=editId?findProduct(editId):null;
  if(editing){
    document.getElementById('productFormTitle').textContent='Sửa sản phẩm';
    document.getElementById('productSubmitText').textContent='Lưu thay đổi';
    document.getElementById('productName').value=editing.name||'';
    document.getElementById('productSku').value=editing.sku||'';
    document.getElementById('productPrice').value=editing.price||0;
    document.getElementById('productType').value=editing.type||'bouquet';
    document.getElementById('productTone').value=editing.tone||'Xanh';
    document.getElementById('productOccasion').value=editing.occasion||'Sinh nhật';
    document.getElementById('productImage').value=(editing.image||'assets/images/lam-tinh.png').replace('assets/images/','');
    document.getElementById('productDescription').value=editing.description||'';
    document.getElementById('productToday').checked=!!editing.today;
  }
  form.addEventListener('submit',function(e){
    e.preventDefault();
    if(!form.checkValidity()){form.classList.add('was-validated');return}
    var data={
      name:document.getElementById('productName').value.trim(),
      sku:document.getElementById('productSku').value.trim(),
      price:Number(document.getElementById('productPrice').value||0),
      type:document.getElementById('productType').value,
      tone:document.getElementById('productTone').value,
      occasion:document.getElementById('productOccasion').value,
      image:'assets/images/'+document.getElementById('productImage').value,
      description:document.getElementById('productDescription').value.trim(),
      today:document.getElementById('productToday').checked,
      categories:[document.getElementById('productCategory').value.trim()||'Hoa theo mùa'],
      composition:'Hoa và lá theo mùa.',
      featured:false
    };
    var msg=document.getElementById('productFormMessage');
    if(editing){
      var added=read(ADDED_KEY,[]),idx=added.findIndex(function(p){return p.id===editing.id});
      if(idx>=0){added[idx]=Object.assign({},added[idx],data);write(ADDED_KEY,added)}
      else{var overrides=read(OVERRIDE_KEY,{});overrides[editing.id]=Object.assign({},overrides[editing.id]||{},data);write(OVERRIDE_KEY,overrides)}
      msg.textContent='Đã lưu thay đổi sản phẩm trong dữ liệu FrontEnd demo.';
    }else{
      var addedList=read(ADDED_KEY,[]);
      data.id='custom-'+Date.now();
      data.slug=data.name.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/đ/g,'d').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
      addedList.unshift(data);write(ADDED_KEY,addedList);
      msg.textContent='Đã thêm sản phẩm vào dữ liệu FrontEnd demo.';
      form.reset();
    }
    msg.className='alert alert-success mt-3 mb-0';
  });
}

function orders(){
  var host=document.getElementById('adminOrderRows');if(!host)return;
  var list=read(ORDER_KEY,[]);
  function render(){
    host.innerHTML=list.length?list.map(function(o,i){return '<tr><td><strong>'+esc(o.orderCode)+'</strong><div class="text-secondary small">'+esc(new Date(o.createdAt).toLocaleString('vi-VN'))+'</div></td><td>'+esc(o.customerName||'-')+'<div class="text-secondary small">'+esc(o.customerPhone||'')+'</div></td><td>'+esc(o.recipientName||'-')+'<div class="text-secondary small">'+esc(o.recipientPhone||'')+'</div></td><td>'+esc(money(o.totalVnd))+'</td><td><select class="form-select form-select-sm admin-input" data-order-status="'+i+'">'+['pending_confirmation','confirmed','preparing','ready','delivering','completed','cancelled'].map(function(s){return '<option value="'+s+'"'+(o.status===s?' selected':'')+'>'+statusLabel(s)+'</option>'}).join('')+'</select></td></tr>'}).join(''):'<tr><td colspan="5">Chưa có đơn demo.</td></tr>';
    host.querySelectorAll('[data-order-status]').forEach(function(sel){sel.addEventListener('change',function(){var idx=Number(sel.dataset.orderStatus);list[idx].status=sel.value;write(ORDER_KEY,list)})});
  } render();
}

function customers(){
  var host=document.getElementById('adminCustomerRows');if(!host)return;
  var orders=read(ORDER_KEY,[]),map={};
  orders.forEach(function(o){
    var phone=o.customerPhone||o.recipientPhone||'Không có SĐT';
    if(!map[phone])map[phone]={name:o.customerName||'Khách hàng',phone:phone,orders:0,total:0,last:o.createdAt};
    map[phone].orders+=1;map[phone].total+=Number(o.totalVnd||0);if(new Date(o.createdAt)>new Date(map[phone].last))map[phone].last=o.createdAt;
  });
  var list=Object.values(map);
  document.getElementById('adminCustomerCount').textContent=list.length+' khách hàng';
  host.innerHTML=list.length?list.map(function(c){return '<tr><td><strong>'+esc(c.name)+'</strong></td><td>'+esc(c.phone)+'</td><td>'+c.orders+'</td><td>'+esc(money(c.total))+'</td><td>'+esc(new Date(c.last).toLocaleDateString('vi-VN'))+'</td></tr>'}).join(''):'<tr><td colspan="5">Chưa có dữ liệu khách hàng từ đơn demo.</td></tr>';
}

document.addEventListener('DOMContentLoaded',function(){
  if(!ensure())return;
  shell();login();dashboard();renderProducts();productForm();orders();customers();
});
})();