const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const crypto=require('node:crypto');
const {execFileSync}=require('node:child_process');
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const base=process.env.CAS_APP_URL||'http://127.0.0.1:8033';
const root=path.resolve(__dirname,'../..'),out=path.join(root,'docs/evidence/customer');
fs.mkdirSync(out,{recursive:true});
const results=[];
function sql(query,params=[]){return JSON.parse(execFileSync(process.env.PHP_BIN||'C:/xampp/php/php.exe',[path.join(__dirname,'db.php')],{input:JSON.stringify({sql:query,params}),encoding:'utf8',env:{...process.env,DB_NAME:process.env.DB_NAME||'cas_hoa_customer_preview',DB_PORT:process.env.DB_PORT||'3307'}}));}
async function test(name,fn){try{await fn();results.push({name,status:'PASS'});console.log('PASS '+name);}catch(e){results.push({name,status:'FAIL',message:e.message});console.log('FAIL '+name+': '+e.message);}}
async function visit(p,route){const r=await p.goto(base+'/'+route,{waitUntil:'networkidle'});assert.equal(r.status(),200);}
async function snap(p,name){await p.screenshot({path:path.join(out,name+'.png'),fullPage:true});}
async function main(){
 sql("INSERT IGNORE INTO wrapping_colors(color_id,name,hex_code,status) VALUES (9001,'Kem','#F4E8D1','active'),(9002,'Nâu kraft','#B58A60','active')");
 sql('INSERT IGNORE INTO product_wrapping(product_id,color_id) VALUES (1,9001),(1,9002),(2,9001)');
 const browser=await chromium.launch({executablePath:process.env.CHROME_BIN||'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 const context=await browser.newContext({viewport:{width:375,height:900}}),page=await context.newPage();page.setDefaultTimeout(7000);page.setDefaultNavigationTimeout(30000);
 const errors=[];page.on('pageerror',e=>errors.push(e.message));
 let orderCode;
 await test('Mobile menu and keyboard focus',async()=>{
   await visit(page,'index.php');await page.getByRole('button',{name:'Mở menu'}).click();await page.locator('#casMobileNav').waitFor({state:'visible'});
   await page.locator('#casMobileNav a').first().focus();await page.keyboard.press('Tab');assert.equal(await page.evaluate(()=>document.activeElement.textContent),'Bó hoa');
   assert.notEqual(await page.evaluate(()=>getComputedStyle(document.activeElement).outlineStyle),'none');
 });
 await test('Recommendation uses real filters and shareable URL',async()=>{
   await visit(page,'index.php');await page.locator('.customer-recommendations [name="occasion"]').selectOption('Sinh nhật');await page.locator('.customer-recommendations [name="tone"]').selectOption('Hồng');await page.locator('.customer-recommendations [name="type"]').selectOption('bouquet');await page.locator('.customer-recommendations [name="max"]').fill('400000');
   await Promise.all([page.waitForURL('**/san-pham.php?**'),page.getByRole('button',{name:'Tìm hoa phù hợp'}).click()]);await page.waitForLoadState('networkidle');
   assert.deepEqual(await page.locator('#catalogGrid .product-name').allTextContents(),['Garden']);const url=page.url();await page.reload({waitUntil:'networkidle'});assert.equal(page.url(),url);assert.equal(await page.locator('#catalogGrid .product-card').count(),1);await snap(page,'catalog-filter-375');
 });
 await test('Combined taxonomy, price, accent-insensitive search and sorting',async()=>{
   await visit(page,'san-pham.php?type=bouquet&category=Hoa+ly&tone=Xanh&occasion=Kỷ+niệm&min=300000&max=400000&q=ly+xanh');
   assert.deepEqual(await page.locator('#catalogGrid .product-name').allTextContents(),['Ly xanh']);
   await visit(page,'san-pham.php?sort=low');let prices=await page.locator('#catalogGrid .product-price').allTextContents();let values=prices.map(p=>Number(p.replace(/\D/g,'')));assert.deepEqual(values,[...values].sort((a,b)=>a-b));
   await page.selectOption('#catalogSort','name');let names=await page.locator('#catalogGrid .product-name').allTextContents();assert.deepEqual(names,[...names].sort((a,b)=>a.localeCompare(b,'vi')));
   await page.selectOption('#catalogSort','new');assert.equal(await page.locator('#catalogGrid .product-name').first().textContent(),'Giỏ hoa Dịu Dàng');
   await page.fill('#catalogSearch','zzzz-khong-co');assert(await page.locator('#catalogEmpty').isVisible());await snap(page,'catalog-empty-375');
   await page.locator('[data-reset-filters]').last().click();assert.equal(await page.locator('#catalogGrid .product-card').count(),11);
 });
 await test('Cart save failure preserves state and offers retry without false success',async()=>{
   await visit(page,'chi-tiet-san-pham.php?slug=lam-tinh');await page.route('**/actions/cart.php',route=>route.abort('failed'));
   await page.click('#detailAdd');await page.locator('#cartSyncMessage').waitFor({state:'visible'});assert.equal(await page.evaluate(()=>CASCart.count()),0);assert(!(await page.locator('#casToastBody').textContent()).includes('Đã thêm'));await snap(page,'cart-network-error-375');
   await page.unroute('**/actions/cart.php');await page.getByRole('button',{name:'Thử lưu lại'}).click();await page.waitForFunction(()=>CASCart.count()===1);await page.reload({waitUntil:'networkidle'});assert.equal(await page.evaluate(()=>CASCart.count()),1);
 });
 await test('Queued plus then minus preserves correct quantity and refresh',async()=>{
   await page.evaluate(()=>CASCart.update('p001',3));await visit(page,'gio-hang.php');
   await page.route('**/actions/cart.php',async route=>{await new Promise(r=>setTimeout(r,250));await route.continue();});
   await page.locator('[data-cart-plus]').click();await page.locator('[data-cart-minus]').click();await page.evaluate(()=>CASCart.sync());assert.equal(await page.evaluate(()=>CASCart.count()),3);
   await page.unroute('**/actions/cart.php');await page.reload({waitUntil:'networkidle'});assert.equal(await page.evaluate(()=>CASCart.count()),3);await snap(page,'cart-375');
 });
 await test('Remove multiple unavailable items in one confirmed update',async()=>{
   await page.evaluate(async()=>{await CASCart.clear();await CASCart.add('p009',1);await CASCart.add('p010',1);});
   const old=sql('SELECT product_id,status FROM products WHERE product_id IN (9,10)');
   try{sql("UPDATE products SET status='hidden' WHERE product_id IN (9,10)");await visit(page,'gio-hang.php');await page.getByRole('button',{name:'Xóa các mẫu ngừng bán'}).click();await page.waitForFunction(()=>CASCart.count()===0);assert(await page.locator('#emptyCart').isVisible());}
   finally{for(const p of old)sql('UPDATE products SET status=? WHERE product_id=?',[p.status,p.product_id]);}
 });
 await test('Product feed transport failure displays retry and keeps cart',async()=>{
   await visit(page,'san-pham.php');await page.evaluate(()=>CASCart.add('p001',1));
   await page.route('**/assets/js/products.php',route=>route.abort('failed'));await visit(page,'gio-hang.php');assert(await page.getByRole('heading',{name:'Chưa tải được danh sách hoa'}).isVisible());assert.equal(await page.evaluate(()=>CASCart.count()),1);await snap(page,'feed-error-375');
   await page.unroute('**/assets/js/products.php');await page.getByRole('button',{name:'Tải lại',exact:true}).click();await page.waitForLoadState('networkidle');assert.equal(await page.locator('.cart-line').count(),1);
 });
 await test('Wrapping choice appears in cart and checkout',async()=>{
   let wrap=sql("SELECT pw.product_id,w.color_id,w.name,p.slug FROM product_wrapping pw JOIN wrapping_colors w ON w.color_id=pw.color_id JOIN products p ON p.product_id=pw.product_id WHERE w.status='active' AND p.status='active' LIMIT 1")[0];assert(wrap,'Seed must contain a wrapping mapping');
   await visit(page,'chi-tiet-san-pham.php?slug='+wrap.slug);await page.evaluate(()=>CASCart.clear());await page.selectOption('#detailWrapping',String(wrap.color_id));await page.click('#detailAdd');await page.waitForFunction(()=>CASCart.count()===1);await snap(page,'product-wrapping-375');
   await visit(page,'gio-hang.php');assert((await page.locator('#cartLines').textContent()).includes(wrap.name));await visit(page,'thanh-toan.php');assert((await page.locator('#checkoutSummaryList').textContent()).includes(wrap.name));
 });
 await test('Mobile checkout delivery validates inputs, blocks duplicate click and saves real order',async()=>{
   await visit(page,'thanh-toan.php');assert(await page.locator('#receiveTime').isDisabled());await page.click('#checkoutSubmit');assert.equal(await page.locator('#customerName:invalid').count(),1);
   await page.fill('#customerName','Khách minh chứng');await page.fill('#customerPhone','0900000000');await page.fill('#recipientName','Người nhận minh chứng');await page.fill('#recipientPhone','0911111111');await page.fill('#address','Địa chỉ dữ liệu thử');await page.fill('#receiveDate','2030-01-01');await page.selectOption('#receiveTime','08:00 - 10:00');await page.fill('#cardMessage','Chúc bạn một ngày đẹp 🌷');await snap(page,'checkout-375');
   const before=Number(sql('SELECT COUNT(*) n FROM orders')[0].n);await page.route('**/actions/checkout.php',async route=>{await new Promise(r=>setTimeout(r,300));await route.continue();});
   await page.evaluate(()=>{document.querySelector('#checkoutForm').requestSubmit();document.querySelector('#checkoutForm').requestSubmit();});await page.waitForURL('**/dat-hang-thanh-cong.php?code=**');await page.waitForLoadState('networkidle');orderCode=await page.locator('#successOrderCode').textContent();assert.equal(Number(sql('SELECT COUNT(*) n FROM orders')[0].n),before+1);assert.equal(await page.evaluate(()=>CASCart.count()),0);assert.match(orderCode,/^CSH-/);assert(sql('SELECT order_note FROM orders WHERE order_code=?',[orderCode])[0].order_note.includes('Giấy gói đã chọn'));await snap(page,'order-success-375');await page.unroute('**/actions/checkout.php');
 });
 await test('Guest lookup rejects wrong phone and displays safe real result',async()=>{
   await visit(page,'tra-cuu-don-hang.php?code='+orderCode);await page.fill('#lookupPhone','0922222222');await page.locator('#lookupForm [type=submit]').click();await page.locator('#lookupMessage.error').waitFor();assert(!(await page.locator('#lookupResults').isVisible()));
   await page.fill('#lookupPhone','0911111111');await page.locator('#lookupForm [type=submit]').click();await page.locator('#lookupResults').waitFor({state:'visible'});assert(!(await page.locator('#lookupResultCard').textContent()).includes('Địa chỉ dữ liệu thử'));await snap(page,'lookup-375');
 });
 const suffix=Date.now().toString().slice(-8),email='browser-'+suffix+'@example.test',password='Aa1!'+crypto.randomBytes(18).toString('hex');
 await test('Registration, profile editing, logout and login through browser',async()=>{
   await visit(page,'dang-ky.php');await page.fill('#signupFullName','Khách thử giao diện');await page.fill('#signupPhone','07'+suffix);await page.fill('#signupEmail',email);await page.fill('#signupPassword',password);await page.fill('#signupConfirm',password);await page.locator('#signupForm [type=submit],form.auth-form [type=submit]').click();await page.waitForURL('**/tai-khoan.php');await page.fill('#address','Địa chỉ minh chứng của tài khoản thử');await page.locator('#profileForm [type=submit]').click();await page.waitForLoadState('networkidle');assert.equal(sql('SELECT address FROM customers c JOIN users u ON c.user_id=u.user_id WHERE u.email=?',[email])[0].address,'Địa chỉ minh chứng của tài khoản thử');
   await page.getByRole('button',{name:'Đăng xuất'}).click();await page.waitForURL('**/index.php');await visit(page,'tai-khoan.php');assert(new URL(page.url()).pathname.endsWith('/dang-nhap.php'));await page.fill('#loginIdentifier',email);await page.fill('#loginPassword',password);await page.locator('form.auth-form [type=submit]').click();await page.waitForURL('**/tai-khoan.php');
 });
 const routes=['index.php','san-pham.php','chi-tiet-san-pham.php?slug=lam-tinh','gio-hang.php','thanh-toan.php','dat-hang-thanh-cong.php','dang-nhap.php','dang-ky.php','quen-mat-khau.php','tai-khoan.php','lich-su-don-hang.php','tra-cuu-don-hang.php','tin-tuc.php','chi-tiet-tin-tuc.php?slug=chon-hoa-theo-mau','lien-he.php','gioi-thieu.php','chinh-sach-giao-hang.php','chinh-sach-doi-tra.php'];
 const anon=await browser.newContext(),anonPage=await anon.newPage();anonPage.on('pageerror',e=>errors.push(e.message));
 for(const width of [375,390,768,1024,1440]){
   await page.setViewportSize({width,height:900});await anonPage.setViewportSize({width,height:900});
   for(const route of routes){await test('Responsive '+width+'px '+route,async()=>{
     const p=['dang-nhap.php','dang-ky.php','quen-mat-khau.php'].includes(route)?anonPage:page;
     await visit(p,route);
     if(route==='thanh-toan.php'){await p.evaluate(()=>CASCart.add('p001',1));await p.reload({waitUntil:'networkidle'});}
     if(['tai-khoan.php','lich-su-don-hang.php'].includes(route))assert(new URL(p.url()).pathname.endsWith('/'+route),'Authenticated page stays on requested URL');
     const metrics=await p.evaluate(()=>({viewport:innerWidth,width:document.documentElement.scrollWidth,badLinks:[...document.querySelectorAll('a[href]')].filter(a=>/\.html(?:\?|$)/.test(a.getAttribute('href'))).length,unlabelled:[...document.querySelectorAll('input:not([type=hidden]),select,textarea')].filter(i=>i.getClientRects().length&&!i.labels?.length&&!i.getAttribute('aria-label')).map(i=>i.id||i.name)}));
     assert(metrics.width<=metrics.viewport+1,JSON.stringify(metrics));assert.equal(metrics.badLinks,0);assert.deepEqual(metrics.unlabelled,[]);
     await p.evaluate(async()=>{for(const i of document.images){i.loading='eager';}await Promise.all([...document.images].map(i=>i.decode().catch(()=>{})));});
     assert.deepEqual(await p.evaluate(()=>[...document.images].filter(i=>!i.naturalWidth).map(i=>i.getAttribute('src'))),[]);
     if([375,768,1440].includes(width)) {
       const folder=path.join(root,'docs/evidence/CUS-01');fs.mkdirSync(folder,{recursive:true});
       const file=path.join(folder,route.split('?')[0].replace('.php','')+'-'+width+'.png');
       // Chụp riêng biểu mẫu liên hệ để không đưa thông tin liên hệ cửa hàng vào báo cáo.
       if(route==='lien-he.php')await p.locator('#contactForm').screenshot({path:file});
       else await p.screenshot({path:file,fullPage:true});
       if(['index.php','san-pham.php','thanh-toan.php'].includes(route))await snap(p,route.replace('.php','')+'-'+width);
     }
   });}
 }
 await test('Long product text stays within mobile layout and does not execute HTML',async()=>{
   const old=sql('SELECT name,description FROM products WHERE product_id=9')[0];try{sql('UPDATE products SET name=?,description=? WHERE product_id=9',['TênHoaRấtDài'.repeat(10),'<img src=x onerror="window.customerXss=true">']);await page.setViewportSize({width:375,height:900});await visit(page,'chi-tiet-san-pham.php?slug=son-sac-thuy-chung');assert.equal(await page.evaluate(()=>!!window.customerXss),false);assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));}
   finally{sql('UPDATE products SET name=?,description=? WHERE product_id=9',[old.name,old.description]);}
 });
 await test('No uncaught JavaScript exceptions during customer browser flows',async()=>assert.deepEqual(errors,[]));
 fs.writeFileSync(path.join(out,'browser-results.json'),JSON.stringify({date:new Date().toISOString(),base,browser:await browser.version(),results},null,2));await browser.close();if(results.some(r=>r.status==='FAIL'))process.exitCode=1;
}
main().catch(e=>{console.error(e);process.exitCode=1;});
