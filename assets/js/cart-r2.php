<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/bootstrap.php';
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store');
echo 'window.CAS_CSRF=',json_encode(cas_csrf()),";\n";
echo 'window.CAS_CART_INITIAL=',json_encode(array_values($_SESSION['cas_cart']??[]),JSON_UNESCAPED_UNICODE),";\n";
?>
(function(){
'use strict';
var state=(window.CAS_CART_INITIAL||[]).filter(function(l){return l&&l.productId&&l.quantity;});
var pending=Promise.resolve();
function read(){return state.map(function(l){return {productId:l.productId,quantity:l.quantity};});}
function notify(){window.dispatchEvent(new CustomEvent('cas-cart-updated',{detail:read()}));}
function save(){
  var snapshot=read();
  pending=pending.catch(function(){}).then(function(){
    var data=new FormData();
    data.set('csrf',window.CAS_CSRF||'');
    data.set('cart',JSON.stringify(snapshot));
    return fetch('actions/cart.php',{method:'POST',body:data,credentials:'same-origin'}).then(function(r){
      return r.json().then(function(x){if(!r.ok||!x.ok)throw new Error(x.message||'Không lưu được giỏ hàng.');});
    });
  }).catch(function(e){console.error('Đồng bộ giỏ hàng PHP:',e);});
  notify();return pending;
}
function clamp(q){return Math.max(1,Math.min(20,Number(q)||1));}
function add(id,q){
  if(!window.CAS_FIND_PRODUCT(id))return Promise.resolve();
  var line=state.find(function(l){return l.productId===id;});
  if(line)line.quantity=clamp(line.quantity+clamp(q));
  else if(state.length<40)state.push({productId:id,quantity:clamp(q)});
  return save();
}
function update(id,q){var l=state.find(function(x){return x.productId===id;});if(l){l.quantity=clamp(q);return save();}return Promise.resolve();}
function remove(id){state=state.filter(function(x){return x.productId!==id;});return save();}
function clear(){state=[];return save();}
function count(){return state.reduce(function(s,l){return s+l.quantity;},0);}
function detailed(){return state.map(function(l){var p=window.CAS_FIND_PRODUCT(l.productId);return p?{product:p,quantity:l.quantity,lineTotal:p.price*l.quantity}:null;}).filter(Boolean);}
function subtotal(){return detailed().reduce(function(s,l){return s+l.lineTotal;},0);}
window.CASCart={read:read,add:add,update:update,remove:remove,clear:clear,count:count,detailed:detailed,subtotal:subtotal,sync:function(){return pending;}};
})();
