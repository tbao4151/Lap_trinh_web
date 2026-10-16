(function(){
  'use strict';
  const form=document.getElementById('advisorForm');
  if(!form)return;
  const send=document.getElementById('advisorSubmit');
  const box=document.getElementById('advisorResult');
  const error=document.getElementById('advisorError');
  const answer=document.getElementById('advisorAnswer');
  const mode=document.getElementById('advisorMode');
  const items=document.getElementById('advisorItems');
  form.addEventListener('submit',async function(e){
    e.preventDefault();
    error.hidden=true;box.hidden=true;send.disabled=true;send.textContent='Đang chọn mẫu hoa...';
    const body=new FormData(form);body.set('csrf',window.CAS_CSRF||'');
    try{
      const response=await fetch('actions/flower-advisor.php',{method:'POST',body,credentials:'same-origin',headers:{'Accept':'application/json'}});
      const data=await response.json();
      if(!response.ok||!data.ok)throw new Error(data.message||'Không thể xử lý yêu cầu lúc này.');
      mode.textContent=data.mode==='ai'?'Tư vấn bằng AI':'Gợi ý dựa trên bộ lọc sản phẩm';
      answer.textContent=String(data.message||'');
      items.replaceChildren();
      (data.products||[]).forEach(function(product){
        const cell=document.createElement('div');cell.className='col-md-6';
        const a=document.createElement('a');a.className='cas-advisor-card';
        a.href='chi-tiet-san-pham.php?slug='+encodeURIComponent(product.slug);
        if(product.image){const img=document.createElement('img');img.src=product.image;img.alt=product.name;a.appendChild(img);}
        const wrap=document.createElement('div');
        const title=document.createElement('strong');title.textContent=product.name;
        const price=document.createElement('div');price.className='cas-advisor-note';price.textContent=new Intl.NumberFormat('vi-VN').format(product.price)+' ₫';
        wrap.append(title,price);a.appendChild(wrap);cell.appendChild(a);items.appendChild(cell);
      });
      box.hidden=false;box.scrollIntoView({behavior:'smooth',block:'nearest'});
    }catch(e){error.textContent=e.message||'Không thể kết nối. Vui lòng thử lại.';error.hidden=false;}
    finally{send.disabled=false;send.textContent='Tìm mẫu hoa phù hợp';}
  });
})();