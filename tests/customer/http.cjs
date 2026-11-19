const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const crypto=require('node:crypto');
const {execFileSync}=require('node:child_process');
const {request}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const base=process.env.CAS_APP_URL||'http://127.0.0.1:8033';
const root=path.resolve(__dirname,'../..');
const results=[];
function sql(query,params=[]){return JSON.parse(execFileSync(process.env.PHP_BIN||'C:/xampp/php/php.exe',[path.join(__dirname,'db.php')],{input:JSON.stringify({sql:query,params}),encoding:'utf8',env:{...process.env,DB_NAME:process.env.DB_NAME||'cas_hoa_customer_preview',DB_PORT:process.env.DB_PORT||'3307'}}));}
async function test(name,fn){try{await fn();results.push({name,status:'PASS'});console.log('PASS '+name);}catch(e){results.push({name,status:'FAIL',message:e.message});console.log('FAIL '+name+': '+e.message);}}
async function html(c,url){let r=await c.get(base+'/'+url);return r.text();}
function csrf(s){const m=s.match(/name="csrf" value="([a-f0-9]+)"/)||s.match(/window.CAS_CSRF\s*=\s*"([a-f0-9]+)"/);assert(m,'CSRF available');return m[1];}
async function post(c,url,form){return c.post(base+'/'+url,{form,maxRedirects:0});}
async function main(){
 const guest=await request.newContext(),a=await request.newContext(),b=await request.newContext();
 const suffix=Date.now().toString().slice(-8),email='customer-'+suffix+'@example.test',phone='09'+suffix,password='Aa1!'+crypto.randomBytes(18).toString('hex');
 let token=csrf(await html(guest,'thanh-toan.php')),code,key;
 await test('Reject CSRF and malformed arrays',async()=>{
   assert.equal((await post(guest,'actions/cart.php',{csrf:'bad',cart:'[]'})).status(),403);
   assert.equal((await post(guest,'actions/contact.php',{'csrf[]':'bad',name:'Test'})).status(),403);
   assert.equal((await post(guest,'actions/contact.php',{csrf:token,'name[]':'bad'})).status(),422);
 });
 await test('Cart validation and persistence',async()=>{
   let r=await post(guest,'actions/cart.php',{csrf:token,cart:JSON.stringify([{productId:'p001',quantity:2}])});assert.equal(r.status(),200);
   assert.match(await html(guest,'assets/js/cart-r2.php'),/"quantity":2/);
   for(const quantity of [0,-1,21,1.5])assert.equal((await post(guest,'actions/cart.php',{csrf:token,cart:JSON.stringify([{productId:'p001',quantity}])})).status(),422);
 });
 const checkout={csrf:token,customerName:'Khách kiểm thử',customerPhone:'+84 900 000 000',recipientName:'Người nhận kiểm thử',recipientPhone:'0911111111',fulfillment:'delivery',address:'Địa chỉ thử nghiệm',receiveDate:'2030-01-01',receiveTime:'08:00 - 10:00',cardMessage:'Gửi bạn 🌷',orderNote:'Dữ liệu kiểm thử'};
 await test('Checkout rejects invalid date, phone and missing address',async()=>{
   for(const fields of [{receiveDate:'2030-02-30'},{customerPhone:'abc0900000000'},{address:''},{receiveTime:'00:00 - 01:00'}])assert.equal((await post(guest,'actions/checkout.php',{...checkout,...fields})).status(),422);
 });
 await test('Server prices, transaction and replay protection',async()=>{
   const page=await html(guest,'thanh-toan.php');key=page.match(/name="checkoutKey" value="([a-f0-9]+)"/)[1];
   const count=Number(sql('SELECT COUNT(*) n FROM orders')[0].n);
   const forms={...checkout,checkoutKey:key,total_amount:'1',price:'1'};
   const responses=await Promise.all([post(guest,'actions/checkout.php',forms),post(guest,'actions/checkout.php',forms)]);
   const payloads=await Promise.all(responses.map(r=>r.json()));assert(payloads.every(p=>p.ok));code=payloads[0].orderCode;assert.equal(payloads[1].orderCode,code);
   assert.equal(Number(sql('SELECT COUNT(*) n FROM orders')[0].n),count+1);
   const order=sql('SELECT * FROM orders WHERE order_code=?',[code])[0];assert.equal(Number(order.total_amount),800000);assert.equal(order.card_message,'Gửi bạn 🌷');
   assert.equal(Number(sql('SELECT COUNT(*) n FROM order_items WHERE order_id=?',[order.order_id])[0].n),1);
 });
 await test('Guest lookup correct phone, wrong phone and limited fields',async()=>{
   let r=await post(guest,'actions/lookup.php',{csrf:token,code,phone:'0911111111'});let data=await r.json();assert(data.ok);assert.equal(data.order.total,800000);assert(!('recipientPhone' in data.order));assert(!('address' in data.order));
   assert.equal((await post(guest,'actions/lookup.php',{csrf:token,code,phone:'0922222222'})).status(),404);
 });
 await test('Success page reflects current database order status',async()=>{
   const order=sql('SELECT order_id,order_status FROM orders WHERE order_code=?',[code])[0];
   try {
     sql("UPDATE orders SET order_status='completed' WHERE order_id=?",[order.order_id]);
     const page=await html(guest,'dat-hang-thanh-cong.php?code='+encodeURIComponent(code));
     assert.match(page,/<span>Trạng thái<\/span><strong>Hoàn tất<\/strong>/);
     assert(!page.includes('<strong>Chờ xác nhận</strong>'));
   } finally {sql('UPDATE orders SET order_status=? WHERE order_id=?',[order.order_status,order.order_id]);}
 });
 await test('Wrapping validates mapping, state and canonical name',async()=>{
   const c=await request.newContext(),name='Giấy kiểm thử HTTP '+suffix;let wrapId;
   try {
     sql("INSERT INTO wrapping_colors(name,hex_code,status) VALUES(?,'#FFFFFF','active')",[name]);
     wrapId=sql('SELECT color_id FROM wrapping_colors WHERE name=?',[name])[0].color_id;
     sql('INSERT INTO product_wrapping(product_id,color_id) VALUES(1,?)',[wrapId]);
     const t=csrf(await html(c,'thanh-toan.php'));
     let r=await post(c,'actions/cart.php',{csrf:t,cart:JSON.stringify([{productId:'p001',quantity:1,wrappingId:wrapId,wrappingName:'Forged name'}])});
     assert.equal(r.status(),200);let data=await r.json();assert.equal(data.cart[0].wrappingName,name);
     for(const item of [
       {productId:'p002',quantity:1,wrappingId:wrapId},
       {productId:'p001',quantity:1,wrappingId:0},
       {productId:'p001',quantity:1,wrappingId:[wrapId]},
       {productId:'p001',quantity:1,wrappingId:99999999}
     ])assert.equal((await post(c,'actions/cart.php',{csrf:t,cart:JSON.stringify([item])})).status(),422);
     sql("UPDATE wrapping_colors SET status='hidden' WHERE color_id=?",[wrapId]);
     assert.equal((await post(c,'actions/cart.php',{csrf:t,cart:JSON.stringify([{productId:'p001',quantity:1,wrappingId:wrapId}])})).status(),422);
     const keyPage=await html(c,'thanh-toan.php'),k=keyPage.match(/name="checkoutKey" value="([a-f0-9]+)"/)[1];
     const count=Number(sql('SELECT COUNT(*) n FROM orders')[0].n);
     r=await post(c,'actions/checkout.php',{...checkout,csrf:t,checkoutKey:k});assert.equal(r.status(),422);
     assert.equal(Number(sql('SELECT COUNT(*) n FROM orders')[0].n),count,'Invalid wrapping rolls back order');
     assert.match(await html(c,'assets/js/cart-r2.php'),new RegExp('"wrappingId":'+wrapId));
   } finally {
     if(wrapId){sql('DELETE FROM product_wrapping WHERE color_id=?',[wrapId]);sql('DELETE FROM wrapping_colors WHERE color_id=?',[wrapId]);}
     await c.dispose();
   }
 });
 await test('Wrapping snapshot survives later rename and has no surcharge',async()=>{
   const c=await request.newContext(),name='Giấy snapshot HTTP '+suffix;let wrapId;
   try {
     sql("INSERT INTO wrapping_colors(name,hex_code,status) VALUES(?,'#FFFFFF','active')",[name]);
     wrapId=sql('SELECT color_id FROM wrapping_colors WHERE name=?',[name])[0].color_id;
     sql('INSERT INTO product_wrapping(product_id,color_id) VALUES(1,?)',[wrapId]);
     const t=csrf(await html(c,'thanh-toan.php'));
     assert.equal((await post(c,'actions/cart.php',{csrf:t,cart:JSON.stringify([{productId:'p001',quantity:1,wrappingId:wrapId}])})).status(),200);
     const page=await html(c,'thanh-toan.php'),k=page.match(/name="checkoutKey" value="([a-f0-9]+)"/)[1];
     const response=await post(c,'actions/checkout.php',{...checkout,csrf:t,checkoutKey:k});assert.equal(response.status(),201);
     const placed=await response.json();const order=sql('SELECT order_note,total_amount FROM orders WHERE order_code=?',[placed.orderCode])[0];
     assert(order.order_note.includes('Mẫu p001: '+name));assert.equal(Number(order.total_amount),400000);
     sql('UPDATE wrapping_colors SET name=? WHERE color_id=?',['Tên đổi sau khi đặt',wrapId]);
     assert.equal(sql('SELECT order_note FROM orders WHERE order_code=?',[placed.orderCode])[0].order_note,order.order_note);
   } finally {
     if(wrapId){sql('DELETE FROM product_wrapping WHERE color_id=?',[wrapId]);sql('DELETE FROM wrapping_colors WHERE color_id=?',[wrapId]);}
     await c.dispose();
   }
 });
 await test('Product 404 and admin visibility reflects database',async()=>{
   assert.equal((await guest.get(base+'/chi-tiet-san-pham.php?slug=no-such-product')).status(),404);
   try {sql("UPDATE products SET status='hidden' WHERE product_id=11");assert(!String(await html(guest,'assets/js/products.php')).includes('p011'));assert.equal((await guest.get(base+'/chi-tiet-san-pham.php?slug=gio-hoa-diu-dang')).status(),404);}
   finally{sql("UPDATE products SET status='active' WHERE product_id=11");}
 });
 await test('Register customer A and reject duplicates',async()=>{
   let t=csrf(await html(a,'dang-ky.php'));let r=await post(a,'dang-ky.php',{csrf:t,full_name:'Customer A',phone,email,password,confirm_password:password});assert.equal(r.status(),303);
   assert.match(await html(a,'tai-khoan.php'),/Customer A/);
   t=csrf(await html(b,'dang-ky.php'));r=await post(b,'dang-ky.php',{csrf:t,full_name:'Customer B',phone,email,password,confirm_password:password});assert.match(await r.text(),/đã được đăng ký/);
 });
 await test('Profile update and authenticated order association',async()=>{
   let t=csrf(await html(a,'tai-khoan.php'));let r=await post(a,'tai-khoan.php',{csrf:t,full_name:'Customer A Updated',phone,email,address:'Địa chỉ mẫu A'});assert.equal(r.status(),303);
   assert.equal(sql('SELECT full_name FROM users WHERE email=?',[email])[0].full_name,'Customer A Updated');
   t=csrf(await html(a,'thanh-toan.php'));await post(a,'actions/cart.php',{csrf:t,cart:'[{"productId":"p002","quantity":1}]'});
   const page=await html(a,'thanh-toan.php'),k=page.match(/name="checkoutKey" value="([a-f0-9]+)"/)[1];
   const result=await (await post(a,'actions/checkout.php',{...checkout,csrf:t,checkoutKey:k,fulfillment:'pickup',address:''})).json();assert(result.ok);code=result.orderCode;
   assert((await html(a,'lich-su-don-hang.php')).includes(code));
 });
 await test('Signed-in checkout preserves submitted buyer name and phone snapshot',async()=>{
   const order=sql('SELECT o.order_note,c.full_name,c.phone,u.email FROM orders o JOIN customers c ON c.customer_id=o.customer_id JOIN users u ON u.user_id=c.user_id WHERE o.order_code=?',[code])[0];
   assert.equal(order.email,email);assert.equal(order.full_name,'Customer A Updated');assert.equal(order.phone,phone);
   assert(order.order_note.includes('Thông tin người đặt tại thời điểm tạo đơn: Khách kiểm thử — 0900000000'));
   const t=csrf(await html(a,'tai-khoan.php'));
   assert.equal((await post(a,'tai-khoan.php',{csrf:t,full_name:'Customer A Later',phone,email,address:'Địa chỉ mẫu A'})).status(),303);
   assert.equal(sql('SELECT order_note FROM orders WHERE order_code=?',[code])[0].order_note,order.order_note,'Later profile edits retain buyer snapshot');
 });
 await test('Customer isolation and direct admin access',async()=>{
   let t=csrf(await html(b,'dang-ky.php'));let r=await post(b,'dang-ky.php',{csrf:t,full_name:'Customer B',phone:'08'+suffix,email:'b-'+email,password,confirm_password:password});assert.equal(r.status(),303);
   assert(!(await html(b,'lich-su-don-hang.php?user_id=1')).includes(code));
   assert(!(await html(b,'dat-hang-thanh-cong.php?code='+code)).includes('success-detail-row'));
   assert.equal((await b.get(base+'/admin/index.php',{maxRedirects:0})).status(),303);
 });
 await test('History pagination includes older orders',async()=>{
   const source=sql('SELECT * FROM orders WHERE order_code=?',[code])[0];
   for(let i=0;i<11;i++)sql("INSERT INTO orders(customer_id,order_code,recipient_name,recipient_phone,delivery_method,receive_date,receive_time,subtotal,shipping_fee,total_amount) VALUES(?,?,?,?,'pickup','2030-01-01','08:00 - 10:00',380000,0,380000)",[source.customer_id,'TEST-'+suffix+'-'+i,'Người nhận thử','0911111111']);
   const first=await html(a,'lich-su-don-hang.php');const second=await html(a,'lich-su-don-hang.php?page=2');
   assert.equal((first.match(/<article class="order-history-card/g)||[]).length,10);assert(second.includes(code));assert(first.includes('Trang sau'));
 });
 await test('Local SMTP delivers reset link; token is single-use and expires',async()=>{
   const c=await request.newContext();let t=csrf(await html(c,'quen-mat-khau.php'));
   let r=await post(c,'quen-mat-khau.php',{csrf:t,action:'request',email});assert.equal(r.status(),200);
   const mails=fs.readdirSync(path.join(root,'.local/mailbox')).map(f=>fs.readFileSync(path.join(root,'.local/mailbox',f),'utf8'));
   const mail=mails.find(m=>m.includes(email));assert(mail,'Email received by local SMTP');
   const match=mail.match(/#uid=(\d+)&token=([a-f0-9]{64})/);assert(match,'Private fragment reset link');
   const newPassword='Aa1!'+crypto.randomBytes(18).toString('hex');
   r=await post(c,'quen-mat-khau.php',{csrf:t,action:'reset',uid:match[1],token:match[2],password:newPassword,confirm:newPassword});assert.match(await r.text(),/Mật khẩu đã được thay đổi/);
   r=await post(c,'quen-mat-khau.php',{csrf:t,action:'reset',uid:match[1],token:match[2],password:newPassword,confirm:newPassword});assert.match(await r.text(),/không hợp lệ hoặc đã hết hạn/);
   const expired=crypto.randomBytes(32).toString('hex');sql("INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,DATE_SUB(NOW(),INTERVAL 1 MINUTE))",[match[1],crypto.createHash('sha256').update(expired).digest('hex')]);
   r=await post(c,'quen-mat-khau.php',{csrf:t,action:'reset',uid:match[1],token:expired,password:newPassword,confirm:newPassword});assert.match(await r.text(),/không hợp lệ hoặc đã hết hạn/);
   t=csrf(await html(c,'dang-nhap.php'));r=await post(c,'dang-nhap.php',{csrf:t,identifier:email,password:newPassword});assert.equal(r.status(),303);
   await c.dispose();
 });
 await test('Contact persisted and rate limited',async()=>{
   const form={csrf:token,name:'Kiểm thử',phone:'0900000000',subject:'Tư vấn mẫu hoa',message:'Tôi cần tư vấn mẫu hoa màu xanh.'};
   assert.equal((await post(guest,'actions/contact.php',form)).status(),201);assert.equal((await post(guest,'actions/contact.php',form)).status(),429);
 });
 await test('Non-scalar GET inputs do not produce PHP warnings or expose private orders',async()=>{
   for(const [route,status,c] of [
     ['chi-tiet-san-pham.php?slug[]=lam-tinh',404,guest],
     ['chi-tiet-tin-tuc.php?slug[]=chon-hoa-theo-mau',404,guest],
     ['dat-hang-thanh-cong.php?code[]='+encodeURIComponent(code),200,a],
     ['quen-mat-khau.php?uid[]=1&token[]=bad',200,guest],
     ['lich-su-don-hang.php?page[]=2',200,a],
     ['san-pham.php?type[]=bouquet&q[]=x',200,guest],
     ['tra-cuu-don-hang.php?code[]=bad',200,guest]
   ]){
     const r=await c.get(base+'/'+route);assert.equal(r.status(),status,route);const text=await r.text();
     assert(!/Fatal error|Warning:|Stack trace:/.test(text),route);
     if(route.startsWith('dat-hang-thanh-cong'))assert(!text.includes('class="success-detail-row"'));
   }
 });
 const routes=['index.php','san-pham.php','chi-tiet-san-pham.php?slug=lam-tinh','gio-hang.php','thanh-toan.php','dat-hang-thanh-cong.php','dang-nhap.php','dang-ky.php','quen-mat-khau.php','tai-khoan.php','lich-su-don-hang.php','tra-cuu-don-hang.php','tin-tuc.php','chi-tiet-tin-tuc.php?slug=chon-hoa-theo-mau','lien-he.php','gioi-thieu.php','chinh-sach-giao-hang.php','chinh-sach-doi-tra.php'];
 for(const route of routes)await test('HTTP page '+route,async()=>{const c=['tai-khoan.php','lich-su-don-hang.php'].includes(route)?a:guest;const r=await c.get(base+'/'+route);assert.equal(r.status(),200);assert.equal(new URL(r.url()).pathname,new URL(base+'/'+route).pathname,'Expected page, without redirect to login');assert(!/Fatal error|Warning:|Stack trace:/.test(await r.text()));});
 fs.mkdirSync(path.join(root,'docs/evidence/customer'),{recursive:true});fs.writeFileSync(path.join(root,'docs/evidence/customer/http-results.json'),JSON.stringify({date:new Date().toISOString(),base,results},null,2));
 await Promise.all([guest.dispose(),a.dispose(),b.dispose()]);if(results.some(r=>r.status==='FAIL'))process.exitCode=1;
}
main().catch(e=>{console.error(e);process.exitCode=1;});
