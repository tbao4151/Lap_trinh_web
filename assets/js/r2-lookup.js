(function(){
'use strict';
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});}
var labels={pending_confirmation:'Chờ xác nhận',confirmed:'Đã xác nhận',preparing:'Đang chuẩn bị',ready:'Sẵn sàng',delivering:'Đang giao',completed:'Hoàn tất',cancelled:'Đã huỷ'};
document.addEventListener('DOMContentLoaded',function(){
 var form=document.getElementById('lookupForm');if(!form)return;
 var code=document.getElementById('lookupCode'),phone=document.getElementById('lookupPhone');
 code.value=new URLSearchParams(location.search).get('code')||'';
 var box=document.getElementById('lookupMessage'),host=document.getElementById('lookupResultCard'),result=document.getElementById('lookupResults');
 form.addEventListener('submit',async function(e){
  e.preventDefault();var button=form.querySelector('[type="submit"]');if(button.disabled)return;
  if(!form.checkValidity()){form.classList.add('was-validated');form.reportValidity();return;}
  var idle=button.textContent;button.disabled=true;button.textContent='Đang tra cứu…';
  result.classList.add('d-none');box.className='auth-message d-none';
  var data=new FormData();data.set('csrf',window.CAS_CSRF||'');data.set('code',code.value.trim());data.set('phone',phone.value.trim());
  try{
   var response=await fetch('actions/lookup.php',{method:'POST',body:data,credentials:'same-origin'});
   var payload=await response.json();
   if(!response.ok||!payload.ok)throw new Error(payload.message||'Không thể tra cứu.');
   var o=payload.order;
   host.innerHTML='<article class="lookup-result"><div class="d-flex flex-wrap justify-content-between gap-3"><div><strong>'+esc(o.code)+'</strong><div class="cas-muted small mt-1">'+esc(o.createdAt)+'</div></div><div class="text-end"><span class="lookup-status">'+esc(labels[o.status]||o.status)+'</span><div class="fw-bold mt-2">'+new Intl.NumberFormat('vi-VN').format(o.total)+' ₫</div></div></div><div class="row g-3 mt-2 small"><div class="col-md-6"><span class="cas-muted">Người nhận</span><div class="fw-semibold">'+esc(o.recipientName)+'</div></div><div class="col-md-6"><span class="cas-muted">Ngày nhận</span><div class="fw-semibold">'+esc(o.receiveDate)+' · '+esc(o.receiveTime)+'</div></div></div></article>';
   result.classList.remove('d-none');
  }catch(err){box.textContent=err.message;box.className='auth-message error';}
  finally{button.disabled=false;button.textContent=idle;}
 });
});
})();
