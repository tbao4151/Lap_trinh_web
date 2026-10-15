<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/bootstrap.php';
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store');
echo 'window.CAS_CART_INITIAL=',json_encode(array_values($_SESSION['cas_cart']??[]), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT),";\n";
?>
(function(){
'use strict';
var state=window.CAS_CART_INITIAL||[], queue=Promise.resolve(), failed=null, retryOperation=null;
function read(){return state.map(function(l){return Object.assign({},l);});}
function notify(){window.dispatchEvent(new CustomEvent('cas-cart-updated',{detail:read()}));}
function status(message){
  var box=document.getElementById('cartSyncMessage');
  if(!box){box=document.createElement('div');box.id='cartSyncMessage';box.className='customer-message';box.setAttribute('role','alert');document.body.prepend(box);}
  box.replaceChildren();box.hidden=!message;
  if(message){box.append(document.createTextNode(message+' '));var retry=document.createElement('button');retry.type='button';retry.className='btn-cas-outline';retry.textContent='Thử lưu lại';retry.onclick=function(){if(retryOperation)retryOperation().catch(function(){});};box.append(retry);}
}
function change(mutator){
  var operation=function(){return change(mutator);};
  var request=queue.then(async function(){
    var next=read();mutator(next);
    var data=new FormData();data.set('csrf',window.CAS_CSRF||'');data.set('cart',JSON.stringify(next));
    var response=await fetch('actions/cart.php',{method:'POST',body:data,credentials:'same-origin'});
    var result=await response.json();
    if(!response.ok||!result.ok)throw new Error(result.message||'Chưa lưu được giỏ hàng.');
    state=result.cart;failed=null;retryOperation=null;status('');notify();return read();
  });
  queue=request.catch(function(error){failed=error;retryOperation=operation;status(error.message||'Mất kết nối. Giỏ hàng chưa thay đổi.');});
  return request;
}
function qty(n){return Math.max(1,Math.min(20,Math.trunc(Number(n)||1)));}
function add(id,q,wrappingId){return change(function(next){if(!window.CAS_FIND_PRODUCT(id))throw new Error('Sản phẩm không còn bán.');var line=next.find(function(l){return l.productId===id;});if(line)line.quantity=qty(line.quantity+qty(q));else {if(next.length>=40)throw new Error('Giỏ tối đa 40 mẫu hoa.');next.push({productId:id,quantity:qty(q)});}if(wrappingId){next.find(function(l){return l.productId===id;}).wrappingId=Number(wrappingId);}});}
function update(id,q){return change(function(next){var line=next.find(function(l){return l.productId===id;});if(line)line.quantity=qty(q);});}
function adjust(id,delta){return change(function(next){var index=next.findIndex(function(l){return l.productId===id;});if(index<0)return;var q=next[index].quantity+delta;if(q<1)next.splice(index,1);else next[index].quantity=qty(q);});}
function removeUnavailable(){return change(function(next){for(var i=next.length-1;i>=0;i--)if(!window.CAS_FIND_PRODUCT(next[i].productId))next.splice(i,1);});}
function remove(id){return change(function(next){var i=next.findIndex(function(l){return l.productId===id;});if(i>=0)next.splice(i,1);});}
function clear(){return change(function(next){next.length=0;});}
function detailed(){return state.map(function(l){var p=window.CAS_FIND_PRODUCT(l.productId);return p?{product:p,quantity:l.quantity,lineTotal:(p.price+(p.wrappings||[]).filter(function(w){return Number(w.id)===Number(l.wrappingId);}).reduce(function(s,w){return s+Math.max(0,Number(w.surcharge)||0);},0))*l.quantity,wrappingName:l.wrappingName||''}:null;}).filter(Boolean);}
window.CASCart={read:read,add:add,update:update,adjust:adjust,remove:remove,removeUnavailable:removeUnavailable,clear:clear,
 count:function(){return state.reduce(function(s,l){return s+l.quantity;},0);},detailed:detailed,
 subtotal:function(){return detailed().reduce(function(s,l){return s+l.lineTotal;},0);},
 sync:async function(){await queue;if(failed)throw failed;},
 unavailable:function(){return state.filter(function(l){return !window.CAS_FIND_PRODUCT(l.productId);});}
};
})();
