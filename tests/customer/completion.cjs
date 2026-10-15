// Kiểm thử bổ sung theo từng task; chỉ dùng database preview/test riêng.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const {execFileSync} = require('node:child_process');
const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = path.resolve(__dirname, '../..');
const task = process.argv[2];
assert(/^CUS-0[2-6]$/.test(task), 'Chọn CUS-02 đến CUS-06');
const base = process.env.CAS_APP_URL || 'http://127.0.0.1:8033';
const out = path.join(root, 'docs/evidence', task);
fs.mkdirSync(out, {recursive:true});
const results = [], queries = [];
function sql(query, params=[]) {
  const result = JSON.parse(execFileSync(process.env.PHP_BIN || 'C:/xampp/php/php.exe', [path.join(__dirname,'db.php')], {
    input:JSON.stringify({sql:query,params}), encoding:'utf8',
    env:{...process.env,DB_NAME:process.env.DB_NAME || 'cas_hoa_customer_preview',DB_PORT:process.env.DB_PORT || '3307'}
  }));
  // Không ghi tham số, mật khẩu, token, email hoặc số điện thoại vào bằng chứng.
  queries.push({sql:query,rows:Array.isArray(result)?result.length:null,affected:result.affected});
  return result;
}
async function test(name, expected, run) {
  try { const actual=await run(); results.push({name,expected,actual:actual || 'Các assertion đã đạt',status:'PASS'}); console.log('PASS '+name); }
  catch(e) { results.push({name,expected,actual:e.message,status:'FAIL'}); console.log('FAIL '+name+': '+e.message); }
}
async function visit(page,route) {
  const r=await page.goto(base+'/'+route,{waitUntil:'networkidle'});
  if(r) assert.equal(r.status(),200);
  else {
    // Fragment trên cùng trang không phát sinh HTTP; tải lại để chạy bước nhận token.
    assert(route.startsWith('quen-mat-khau.php#uid='));
    const reload=await page.reload({waitUntil:'networkidle'}); assert.equal(reload.status(),200);
  }
}
async function capture(page,name) {
  for(const width of [375,768,1440]) {
    await page.setViewportSize({width,height:900});
    await page.evaluate(async()=>{document.images && await Promise.all([...document.images].map(i=>{i.loading='eager';return i.decode().catch(()=>{});}));});
    assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));
    await page.screenshot({path:path.join(out,name+'-'+width+'.png'),fullPage:true});
  }
  const reportTargets={
    'catalog-invalid-price':'#filterPanel .filter-group:last-child',
    'checkout-retry':'#checkoutMessage',
    'reset-success':'.auth-card',
    'login-contrast':'.auth-card',
    'recommendation-empty':'#catalogEmpty'
  };
  if(reportTargets[name]) await page.locator(reportTargets[name]).screenshot({path:path.join(out,'report-'+name+'.png')});
  await page.setViewportSize({width:375,height:900});
}
async function main() {
  const browser=await chromium.launch({executablePath:process.env.CHROME_BIN || 'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
  const context=await browser.newContext({viewport:{width:375,height:900}}), page=await context.newPage();
  page.setDefaultTimeout(8000);
  page.setDefaultNavigationTimeout(30000);
  if(task==='CUS-02') {
    await page.setViewportSize({width:1024,height:600});await visit(page,'index.php');
    await page.screenshot({path:path.join(root,'docs/evidence/CUS-01/report-home.png'),fullPage:false});
    await page.setViewportSize({width:375,height:900});
    await test('Từng bộ lọc khớp SQL', 'Mỗi bộ lọc loại, tone, dịp, giá và tìm kiếm trả đúng danh sách ID trong DB', async()=>{
      const cases=[['type=basket',"p.product_type='basket'"],['category=Hoa+ly',"c.name='Hoa ly'"],['tone=Xanh',"t.name='Xanh'"],['occasion=Sinh+nhật',"o.name='Sinh nhật'"],['min=300000&max=400000','p.price BETWEEN 300000 AND 400000'],['q=Lam+tinh',"p.slug='lam-tinh'"]];
      for(const [filter,condition] of cases) {
        const expected=sql("SELECT p.product_id FROM products p LEFT JOIN categories c ON c.category_id=p.category_id LEFT JOIN tones t ON t.tone_id=p.tone_id LEFT JOIN occasions o ON o.occasion_id=p.occasion_id WHERE p.status='active' AND "+condition).map(p=>'p'+String(p.product_id).padStart(3,'0')).sort();
        await visit(page,'san-pham.php?'+filter);
        const actual=await page.locator('#catalogGrid [data-add-product]').evaluateAll(nodes=>nodes.map(n=>n.dataset.addProduct).sort()); assert.deepEqual(actual,expected);
      }
      await capture(page,'catalog-search'); return '6 bộ lọc riêng lẻ khớp tập ID SQL';
    });
    await test('Giá và trạng thái sản phẩm cập nhật từ DB', 'Đổi giá phản ánh ở catalog/detail; hidden không xuất hiện và detail trả 404', async()=>{
      const old=sql('SELECT price,status FROM products WHERE product_id=1')[0];
      try {
        sql('UPDATE products SET price=? WHERE product_id=1',[456000]);
        await visit(page,'chi-tiet-san-pham.php?slug=lam-tinh'); assert.equal(await page.evaluate(()=>CAS_FIND_PRODUCT('p001').price),456000);
        await capture(page,'detail');
        sql("UPDATE products SET status='hidden' WHERE product_id=1");
        await visit(page,'san-pham.php?q=lam+tinh'); assert.equal(await page.locator('#catalogGrid .product-card').count(),0);
        assert.equal((await context.request.get(base+'/chi-tiet-san-pham.php?slug=lam-tinh')).status(),404);
      } finally {sql('UPDATE products SET price=?,status=? WHERE product_id=1',[old.price,old.status]);}
      return 'Giá 456000 đã hiển thị; hidden bị loại và trả 404; khôi phục dữ liệu thử';
    });
    await test('Khoảng giá đảo hiển thị lỗi có liên kết', 'Lỗi inline và aria-invalid khi min > max; đặt lại xóa lỗi', async()=>{
      await visit(page,'san-pham.php?min=500000&max=100000'); await page.locator('[data-bs-target="#filterPanel"]').click();
      assert(await page.locator('#catalogPriceError').isVisible()); assert.equal(await page.locator('#maxPrice').getAttribute('aria-invalid'),'true');
      await capture(page,'catalog-invalid-price'); await page.locator('[data-reset-filters]').first().click(); assert(await page.locator('#catalogPriceError').isHidden());
      assert.equal(await page.locator('#catalogSort option[value=new]').textContent(),'Mới thêm'); return 'Thông báo xuất hiện, aria-invalid=true và đặt lại xóa lỗi';
    });
  }
  if(task==='CUS-03') {
    await test('Giỏ xóa và refresh giữ đúng Session', 'Thêm hai sản phẩm, sửa số lượng, xóa một rồi refresh vẫn đúng',async()=>{
      await visit(page,'gio-hang.php'); await page.evaluate(async()=>{await CASCart.clear();await CASCart.add('p001',2);await CASCart.add('p002',1);await CASCart.update('p001',3);await CASCart.remove('p002');});
      await page.reload({waitUntil:'networkidle'}); assert.equal(await page.locator('.cart-line').count(),1);assert.equal(await page.evaluate(()=>CASCart.count()),3); await capture(page,'cart');return '1 dòng, số lượng 3 sau refresh';
    });
    await test('Pickup và lỗi lưu đơn hiển thị tại form', 'Pickup bỏ yêu cầu địa chỉ; ngày không hợp lệ tắt giờ; HTTP 503 không chuyển trang và cho thử lại',async()=>{
      await visit(page,'thanh-toan.php'); await page.check('[name=fulfillment][value=pickup]');assert.equal(await page.locator('#address').getAttribute('required'),null);assert(await page.locator('#receiveTime').isDisabled());
      await page.fill('#receiveDate','2030-01-01');assert(await page.locator('#receiveTime').isEnabled());
      for(const [id,value] of [['customerName','Khách kiểm thử'],['customerPhone','0900000000'],['recipientName','Người nhận kiểm thử'],['recipientPhone','0911111111']])await page.fill('#'+id,value);
      await page.selectOption('#receiveTime','08:00 - 10:00');await page.route('**/actions/checkout.php',route=>route.fulfill({status:503,contentType:'application/json',body:JSON.stringify({ok:false,message:'Kết nối kiểm thử tạm gián đoạn. Vui lòng thử lại.'})}));
      await page.click('#checkoutSubmit');await page.locator('#checkoutMessage.error').waitFor();assert(await page.locator('#checkoutSubmit').isEnabled());assert(page.url().endsWith('/thanh-toan.php'));await capture(page,'checkout-retry');await page.unroute('**/actions/checkout.php');
      await page.click('#checkoutSubmit');await page.waitForURL('**/dat-hang-thanh-cong.php?code=**');await page.waitForLoadState('networkidle');
      const code=await page.locator('#successOrderCode').textContent();assert.equal(sql('SELECT COUNT(*) n FROM orders WHERE order_code=?',[code])[0].n,1);
      await page.setViewportSize({width:768,height:900});await page.locator('.success-card').screenshot({path:path.join(root,'docs/evidence/CUS-07/report-order-success.png')});
      return 'Giờ disabled/enabled đúng; lỗi 503 inline, nút được bật lại; thử lại thành công và mã đơn tồn tại trong SQL';
    });
  }
  if(task==='CUS-04') {
    const password='Aa1!'+crypto.randomBytes(18).toString('hex'), suffix=Date.now().toString().slice(-8), email='completion-'+suffix+'@example.test';
    await test('Đăng ký từ chối dữ liệu sai ở server', 'Email/phone/password/xác nhận sai không tạo user',async()=>{
      const api=context.request;let r=await api.get(base+'/dang-ky.php');const csrf=(await r.text()).match(/name="csrf" value="([a-f0-9]+)"/)[1];
      const before=Number(sql('SELECT COUNT(*) n FROM users')[0].n);
      for(const change of [{email:'invalid'},{phone:'abc'},{password:'short'},{confirm_password:'mismatch'}]) {
        r=await api.post(base+'/dang-ky.php',{form:{csrf,full_name:'Khách thử',phone:'06'+suffix,email,password,confirm_password:password,...change}});assert.equal(r.status(),200);assert.match(await r.text(),/class="auth-message error"/);
      }
      assert.equal(Number(sql('SELECT COUNT(*) n FROM users')[0].n),before);return '4 trường hợp sai bị từ chối; số user không đổi';
    });
    await test('Sai mật khẩu không tạo Session đăng nhập', 'Thông báo lỗi rõ và trang tài khoản vẫn chuyển về login',async()=>{
      await visit(page,'dang-nhap.php');await page.fill('#loginIdentifier',email);await page.fill('#loginPassword',password);await page.locator('#loginForm [type=submit]').click();await page.locator('.auth-message.error').waitFor();await capture(page,'login-error');
      assert.equal((await context.request.get(base+'/tai-khoan.php',{maxRedirects:0})).status(),303);return 'Lỗi hiện tại form; tài khoản HTTP 303 về đăng nhập';
    });
    await test('Khôi phục bằng link email trong trình duyệt', 'Nhận SMTP local, URL fragment bị xóa; reset thành công; lần dùng lại bị từ chối',async()=>{
      const r=await context.request.get(base+'/dang-ky.php'),csrf=(await r.text()).match(/name="csrf" value="([a-f0-9]+)"/)[1];
      assert.equal((await context.request.post(base+'/dang-ky.php',{form:{csrf,full_name:'Khách thử khôi phục',phone:'06'+suffix,email,password,confirm_password:password},maxRedirects:0})).status(),303);
      await visit(page,'tai-khoan.php');await capture(page,'profile');await page.getByRole('button',{name:'Đăng xuất'}).click();await page.waitForURL('**/index.php');
      await visit(page,'quen-mat-khau.php');await page.locator('[name=email]').fill(email);await page.locator('#recoveryForm [type=submit]').click();await page.locator('.auth-message.success').waitFor();
      const mails=fs.readdirSync(path.join(root,'.local/mailbox')).filter(f=>f.endsWith('.eml')).map(f=>fs.readFileSync(path.join(root,'.local/mailbox',f),'utf8'));const mail=mails.find(m=>m.includes(email));assert(mail);
      const match=mail.match(/#uid=(\d+)&token=([a-f0-9]{64})/);assert(match);const fragment=match[0];
      await visit(page,'quen-mat-khau.php'+fragment);assert.equal(new URL(page.url()).hash,'');assert(!(await page.locator('body').innerText()).includes(match[2]));
      const newPassword='Aa1!'+crypto.randomBytes(18).toString('hex');await page.fill('#newPassword',newPassword);await page.fill('#confirmPassword',newPassword);await page.locator('#recoveryVerifyForm [type=submit]').click();await page.locator('.auth-message.success').waitFor();await capture(page,'reset-success');
      await visit(page,'quen-mat-khau.php'+fragment);await page.fill('#newPassword',newPassword);await page.fill('#confirmPassword',newPassword);await page.locator('#recoveryVerifyForm [type=submit]').click();await page.locator('.auth-message.error').waitFor();
      const info=sql('SELECT COUNT(*) n FROM password_resets r JOIN users u ON u.user_id=r.user_id WHERE u.email=? AND r.consumed_at IS NOT NULL',[email]);assert(Number(info[0].n)>0);
      return 'Thư nhận qua SMTP, token không hiện trên màn hình/URL sau tải; reset và từ chối dùng lại đạt; SQL consumed_at đã có';
    });
  }
  if(task==='CUS-05') {
    await test('Tương phản nút và bàn phím bộ lọc', 'Chữ nút chính đạt tỷ lệ >=4.5; mở lọc bằng Enter và có focus visible',async()=>{
      await visit(page,'san-pham.php');const button=page.locator('[data-bs-target="#filterPanel"]');await button.focus();await page.keyboard.press('Enter');await page.locator('#filterPanel').waitFor({state:'visible'});
      assert.notEqual(await button.evaluate(n=>getComputedStyle(n).outlineStyle),'none');await capture(page,'filter-open');
      await visit(page,'dang-nhap.php');const colors=await page.locator('.btn-cas-primary').evaluate(n=>({fg:getComputedStyle(n).color,bg:getComputedStyle(n).backgroundColor}));
      const lum=c=>c.match(/[\d.]+/g).slice(0,3).map(Number).map(v=>v/255).map(v=>v<=.04045?v/12.92:((v+.055)/1.055)**2.4).reduce((s,v,i)=>s+v*[.2126,.7152,.0722][i],0);
      const ratio=(Math.max(lum(colors.fg),lum(colors.bg))+.05)/(Math.min(lum(colors.fg),lum(colors.bg))+.05);assert(ratio>=4.5,'Tỷ lệ thực '+ratio.toFixed(2));await capture(page,'login-contrast');return 'Tỷ lệ nút '+ratio.toFixed(2)+':1; Enter/focus đạt';
    });
  }
  if(task==='CUS-06') {
    await test('Ngân sách gợi ý hợp lệ và chia sẻ URL không lộ hidden', 'Khoảng giá đảo bị chặn; tổ hợp không có sản phẩm có cách nới điều kiện; sản phẩm hidden không xuất hiện',async()=>{
      await visit(page,'index.php');const form=page.locator('.customer-recommendations form');await form.locator('[name=min]').fill('500000');await form.locator('[name=max]').fill('100000');await form.locator('[type=submit]').click();assert(page.url().endsWith('/index.php'));assert(await page.locator('#recommendationError').isVisible());await capture(page,'recommendation-invalid');
      await form.locator('[name=min]').fill('0');await form.locator('[name=max]').fill('1000');await form.locator('[type=submit]').click();await page.waitForURL('**/san-pham.php?**');await page.waitForLoadState('networkidle');assert(await page.locator('#catalogEmpty').isVisible());await capture(page,'recommendation-empty');
      const url=page.url();await page.reload({waitUntil:'networkidle'});assert.equal(page.url(),url);assert(await page.locator('#catalogEmpty').isVisible());
      const old=sql('SELECT status FROM products WHERE product_id=2')[0];try{sql("UPDATE products SET status='hidden' WHERE product_id=2");await visit(page,'san-pham.php?occasion=Sinh+nhật&tone=Hồng&type=bouquet&max=400000');assert.equal(await page.locator('[data-add-product=p002]').count(),0);}finally{sql('UPDATE products SET status=? WHERE product_id=2',[old.status]);}
      return 'Chặn khoảng giá đảo; empty state/URL giữ đúng; hidden không xuất hiện';
    });
  }
  fs.writeFileSync(path.join(out,'results.json'),JSON.stringify({date:new Date().toISOString(),base,task,results,queries},null,2));
  await browser.close();if(results.some(r=>r.status==='FAIL'))process.exitCode=1;
}
main().catch(e=>{console.error(e);process.exitCode=1;});
