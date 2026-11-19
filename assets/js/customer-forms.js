(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('[data-budget-form]').forEach(function(form){
      var min=form.elements.min,max=form.elements.max,error=document.getElementById('recommendationError');
      function validateBudget(){
        var low=min.value===''?0:Number(min.value),high=max.value===''?Infinity:Number(max.value);
        min.setCustomValidity(low<0?'Ngân sách phải từ 0 trở lên.':'');
        max.setCustomValidity(high<low?'Ngân sách đến phải lớn hơn hoặc bằng ngân sách từ.':'');
        error.textContent=min.validationMessage||max.validationMessage;error.hidden=!error.textContent;
        min.setAttribute('aria-invalid',String(!min.validity.valid));max.setAttribute('aria-invalid',String(!max.validity.valid));
      }
      min.addEventListener('input',validateBudget);max.addEventListener('input',validateBudget);validateBudget();
    });
    if(document.body.dataset.page==='forgot' && location.hash){
      var values=new URLSearchParams(location.hash.slice(1)), uid=values.get('uid'), token=values.get('token');
      history.replaceState(null,'',location.pathname);
      if(/^\d+$/.test(uid||'') && /^[a-f0-9]{64}$/.test(token||'')){
        var request=document.getElementById('recoveryForm');
        if(request){
          var csrf=request.querySelector('[name="csrf"]').value;
          request.id='recoveryVerifyForm';
          request.innerHTML='<input type="hidden" name="csrf"><input type="hidden" name="action" value="reset"><input type="hidden" name="uid"><input type="hidden" name="token"><label class="form-label" for="newPassword">Mật khẩu mới</label><input id="newPassword" name="password" type="password" autocomplete="new-password" class="form-control cas-form-control" required><p class="password-policy">Từ 10 ký tự, tối đa 72 byte; gồm chữ hoa, thường, số và ký tự đặc biệt.</p><label class="form-label mt-3" for="confirmPassword">Xác nhận mật khẩu</label><input id="confirmPassword" name="confirm" type="password" autocomplete="new-password" class="form-control cas-form-control" required><button type="submit" class="btn-cas-primary w-100 mt-3">Đổi mật khẩu</button>';
          request.elements.csrf.value=csrf;request.elements.uid.value=uid;request.elements.token.value=token;
          document.querySelector('.auth-card h1').textContent='Đặt mật khẩu mới';
          document.querySelector('.auth-description').textContent='Liên kết có thời hạn 30 phút và chỉ sử dụng một lần.';
        }
      }
    }
    document.querySelectorAll('[data-toggle-password]').forEach(function(button){
      button.addEventListener('click',function(){var input=document.getElementById(button.dataset.togglePassword);var show=input.type==='password';input.type=show?'text':'password';button.setAttribute('aria-label',show?'Ẩn mật khẩu':'Hiện mật khẩu');button.setAttribute('aria-pressed',String(show));});
    });
    document.querySelectorAll('form.auth-form,#profileForm').forEach(function(form){
      form.addEventListener('submit',function(event){
        if(!form.checkValidity()){event.preventDefault();form.classList.add('was-validated');form.reportValidity();return;}
        var button=form.querySelector('[type="submit"]'); if(button){button.dataset.idleText=button.textContent;button.disabled=true;button.textContent='Đang xử lý…';}
      });
    });
    window.addEventListener('pageshow',function(){document.querySelectorAll('form.auth-form [type="submit"],#profileForm [type="submit"]').forEach(function(button){button.disabled=false;if(button.dataset.idleText)button.textContent=button.dataset.idleText;});});
  });
})();
