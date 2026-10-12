(function(){
'use strict';
document.addEventListener('DOMContentLoaded',function(){
 var form=document.getElementById('contactForm');if(!form)return;
 var box=document.getElementById('contactMessage');
 form.addEventListener('submit',async function(event){
  event.preventDefault();
  if(!form.checkValidity()){form.classList.add('was-validated');return;}
  var data=new FormData(form);data.set('csrf',window.CAS_CSRF||'');
  try{
   var response=await fetch('actions/contact.php',{method:'POST',body:data,credentials:'same-origin'});
   var payload=await response.json();
   if(!response.ok||!payload.ok)throw new Error(payload.message||'Không thể gửi yêu cầu.');
   box.textContent=payload.message;box.className='auth-message success';
   form.reset();form.classList.remove('was-validated');
  }catch(err){box.textContent=err.message;box.className='auth-message error';}
 });
});
})();
