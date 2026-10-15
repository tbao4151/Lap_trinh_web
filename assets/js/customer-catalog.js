(function () {
  'use strict';
  function folded(text) { return String(text || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[đĐ]/g, 'd').toLocaleLowerCase('vi'); }
  window.casInitCatalog = function (card, bindButtons) {
    var grid = document.getElementById('catalogGrid');
    if (!grid) return;
    var search = document.getElementById('catalogSearch'), sort = document.getElementById('catalogSort');
    var occasion = document.getElementById('occasionFilter'), min = document.getElementById('minPrice'), max = document.getElementById('maxPrice');
    var today = document.getElementById('todayFilter'), params = new URLSearchParams(location.search);
    var aliases = {'sinh-nhat':'Sinh nhật','nguoi-thuong':'Tặng người thương','ky-niem':'Kỷ niệm','chuc-mung':'Chúc mừng','xanh':'Xanh','hong':'Hồng','do':'Đỏ','tim':'Tím'};
    function selected(name) { var input = document.querySelector('[name="'+name+'"]:checked'); return input ? input.value : 'all'; }
    function radio(name, value) { document.querySelectorAll('[name="'+name+'"]').forEach(function (input) { input.checked = input.value === value; }); }
    function restore() {
      params = new URLSearchParams(location.search);
      search.value = params.get('q') || ''; sort.value = params.get('sort') || 'featured';
      ['productType','flowerCategory','tone'].forEach(function (name, i) { var value=params.get(['type','category','tone'][i]) || 'all'; radio(name,aliases[value]||value); });
      occasion.value = aliases[params.get('occasion')] || params.get('occasion') || '';
      min.value = params.get('min') || ''; max.value = params.get('max') || ''; today.checked = params.get('today') === '1';
      render(false);
    }
    function render(updateUrl) {
      var low = min.value === '' ? 0 : Number(min.value), high = max.value === '' ? Infinity : Number(max.value);
      min.setCustomValidity(low < 0 ? 'Giá phải từ 0 trở lên.' : '');
      max.setCustomValidity(high < low || high < 0 ? 'Giá tối đa phải lớn hơn hoặc bằng giá tối thiểu.' : '');
      var priceError=document.getElementById('catalogPriceError');
      priceError.textContent=min.validationMessage || max.validationMessage;
      priceError.hidden=!priceError.textContent;
      min.setAttribute('aria-invalid',String(!min.validity.valid));
      max.setAttribute('aria-invalid',String(!max.validity.valid));
      var words=folded(search.value).trim().split(/\s+/).filter(Boolean);
      var list=(window.CAS_PRODUCTS||[]).filter(function(p){
        var haystack=folded([p.name,p.sku,p.categories.join(' '),p.occasion,p.tone].join(' '));
        return words.every(function(w){return haystack.includes(w);}) &&
          (selected('productType')==='all'||p.type===selected('productType')) &&
          (selected('flowerCategory')==='all'||p.categories.includes(selected('flowerCategory'))) &&
          (selected('tone')==='all'||p.tone===selected('tone')) && (!occasion.value||p.occasion===occasion.value) &&
          p.price>=low && p.price<=high && (!today.checked||p.today);
      });
      if(sort.value==='low')list.sort(function(a,b){return a.price-b.price;});
      else if(sort.value==='high')list.sort(function(a,b){return b.price-a.price;});
      else if(sort.value==='name')list.sort(function(a,b){return a.name.localeCompare(b.name,'vi');});
      else if(sort.value==='new')list.sort(function(a,b){return b.numericId-a.numericId;});
      else list.sort(function(a,b){return Number(b.featured)-Number(a.featured)||b.numericId-a.numericId;});
      grid.innerHTML=list.map(function(p){return '<div class="col-6 col-xl-4">'+card(p)+'</div>';}).join('');
      bindButtons(grid);
      document.getElementById('resultCount').textContent=list.length+' sản phẩm';
      document.getElementById('catalogEmpty').classList.toggle('d-none',list.length>0);
      if(updateUrl!==false){
        var next=new URLSearchParams();
        var values={q:search.value.trim(),type:selected('productType'),category:selected('flowerCategory'),tone:selected('tone'),occasion:occasion.value,min:min.value,max:max.value,sort:sort.value,today:today.checked?'1':''};
        Object.keys(values).forEach(function(k){if(values[k]&&values[k]!=='all'&&!(k==='sort'&&values[k]==='featured'))next.set(k,values[k]);});
        history.replaceState(null,'',location.pathname+(next.toString()?'?'+next:''));
      }
    }
    [search,sort,occasion,min,max,today].forEach(function(input){input.addEventListener('input',render);});
    document.querySelectorAll('[name="productType"],[name="flowerCategory"],[name="tone"]').forEach(function(input){input.addEventListener('change',render);});
    document.querySelectorAll('[data-reset-filters]').forEach(function(button){button.addEventListener('click',function(){history.replaceState(null,'',location.pathname);restore();});});
    window.addEventListener('popstate',restore); restore();
  };
})();
