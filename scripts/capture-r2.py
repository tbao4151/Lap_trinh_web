#!/usr/bin/env python3
"""Chụp Chrome thật (bao gồm thanh địa chỉ) và ghép PDF minh chứng."""
import json
import os
from pathlib import Path
import subprocess
import time
import sys
from urllib.parse import urlparse, parse_qs

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select, WebDriverWait
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A4, landscape
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from PIL import Image

BASE="http://127.0.0.1:8033/"
OUT=Path("evidence/r2")
OUT.mkdir(parents=True,exist_ok=True)
FONT=Path("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf")
if FONT.exists():
    pdfmetrics.registerFont(TTFont("VietFont",str(FONT)))
    FONT_NAME="VietFont"
else:
    FONT_NAME="Helvetica"
entries=[]

def capture(driver,number,title,page):
    url=BASE+page
    driver.get(url)
    WebDriverWait(driver,12).until(lambda d:d.execute_script("return document.readyState")=="complete")
    time.sleep(1.5)
    name=f"{number:02d}_{page.split('?')[0].replace('.php','').replace('-','_')}.png"
    shot=OUT/name
    subprocess.run(["scrot","-z",str(shot)],check=True)
    with Image.open(shot) as img:
        if img.width < 1000 or img.height < 600:
            raise RuntimeError(f"Invalid screenshot dimensions: {shot}: {img.size}")
    entries.append({"title":title,"url":url,"screenshot":str(shot)})
    print(f"Captured {shot}: {url}",flush=True)

php=subprocess.Popen(["php","-S","127.0.0.1:8033"],stdout=open("/tmp/cas-r2-screen-php.log","w"),stderr=subprocess.STDOUT)
driver=None
try:
    time.sleep(2)
    opts=Options()
    opts.add_argument("--no-sandbox")
    opts.add_argument("--disable-dev-shm-usage")
    opts.add_argument("--disable-gpu")
    opts.add_argument("--no-first-run")
    opts.add_experimental_option("excludeSwitches", ["enable-automation"])
    opts.add_argument("--no-default-browser-check")
    opts.add_argument("--disable-search-engine-choice-screen")
    opts.add_argument("--window-size=1440,960")
    opts.add_argument("--window-position=0,0")
    driver=webdriver.Chrome(options=opts)
    driver.set_window_size(1440,960)
    driver.set_window_position(0,0)
    capture(driver,1,"Trang chủ - sản phẩm từ MySQL","index.php")
    capture(driver,2,"Danh sách sản phẩm - tìm kiếm và bộ lọc","san-pham.php")
    capture(driver,3,"Chi tiết sản phẩm theo slug","chi-tiet-san-pham.php?slug=lam-tinh")
    driver.get(BASE+"san-pham.php")
    driver.execute_async_script("var done=arguments[0]; window.CASCart.add('p001',2).then(function(){done(true)}).catch(function(e){done(e.message)})")
    capture(driver,4,"Giỏ hàng - hai sản phẩm đã chọn","gio-hang.php")
    capture(driver,5,"Thanh toán - biểu mẫu PHP","thanh-toan.php")
    # Lập đơn thật qua giao diện của trình duyệt, để minh chứng trang thành công.
    driver.find_element(By.ID,"customerName").send_keys("Khách kiểm thử")
    driver.find_element(By.ID,"customerPhone").send_keys("0900000000")
    driver.find_element(By.ID,"address").send_keys("Linh Xuân, Thủ Đức")
    driver.execute_script("document.getElementById('receiveDate').value='2030-01-01'")
    Select(driver.find_element(By.ID,"receiveTime")).select_by_visible_text("08:00 - 10:00")
    driver.find_element(By.ID,"recipientName").send_keys("Người nhận kiểm thử")
    driver.find_element(By.ID,"recipientPhone").send_keys("0911111111")
    driver.execute_script("document.getElementById('checkoutSubmit').click()")
    WebDriverWait(driver,15).until(lambda d:"dat-hang-thanh-cong.php?code=" in d.current_url)
    code=parse_qs(urlparse(driver.current_url).query)["code"][0]
    time.sleep(1.5)
    subprocess.run(["scrot","-z",str(OUT/"06_dat_hang_thanh_cong.png")],check=True)
    entries.append({"title":"Xác nhận đơn đã lưu MySQL","url":driver.current_url,"screenshot":str(OUT/"06_dat_hang_thanh_cong.png")})
    print(f"Created order {code}",flush=True)
    # Tra cứu đơn PHP/MySQL thực tế.
    driver.get(BASE+"tra-cuu-don-hang.php")
    driver.find_element(By.ID,"lookupCode").clear()
    driver.find_element(By.ID,"lookupCode").send_keys(code)
    driver.find_element(By.ID,"lookupPhone").send_keys("0911111111")
    driver.execute_script("document.querySelector('#lookupForm button[type=submit]').click()")
    WebDriverWait(driver,12).until(lambda d: d.find_element(By.ID,"lookupResults").is_displayed())
    time.sleep(1)
    subprocess.run(["scrot","-z",str(OUT/"07_tra_cuu_don_hang.png")],check=True)
    entries.append({"title":"Tra cứu trạng thái đơn từ MySQL","url":BASE+"tra-cuu-don-hang.php","screenshot":str(OUT/"07_tra_cuu_don_hang.png")})
    capture(driver,8,"Tin tức - danh sách lấy từ MySQL","tin-tuc.php")
    capture(driver,9,"Chi tiết tin tức lấy từ MySQL","chi-tiet-tin-tuc.php?slug=chon-hoa-theo-mau")
    driver.get(BASE+"lien-he.php")
    driver.find_element(By.NAME,"name").send_keys("Khách kiểm thử")
    driver.find_element(By.NAME,"phone").send_keys("0900000000")
    Select(driver.find_element(By.NAME,"subject")).select_by_visible_text("Tư vấn mẫu hoa")
    driver.find_element(By.NAME,"message").send_keys("Tôi cần tư vấn hoa cho ngày kỷ niệm.")
    driver.execute_script("document.querySelector('#contactForm button[type=submit]').click()")
    WebDriverWait(driver,12).until(lambda d:"thành công" in d.find_element(By.ID,"contactMessage").text)
    time.sleep(1)
    subprocess.run(["scrot","-z",str(OUT/"10_lien_he.png")],check=True)
    entries.append({"title":"Liên hệ - lưu thành công vào MySQL","url":BASE+"lien-he.php","screenshot":str(OUT/"10_lien_he.png")})
    capture(driver,11,"Giới thiệu","gioi-thieu.php")
    capture(driver,12,"Chính sách giao hàng","chinh-sach-giao-hang.php")
    capture(driver,13,"Chính sách đổi trả","chinh-sach-doi-tra.php")
finally:
    if driver:
        driver.quit()
    php.terminate()
    try: php.wait(timeout=5)
    except subprocess.TimeoutExpired:php.kill()

if len(entries)!=13:
    raise RuntimeError(f"Expected 13 genuine screenshots, found {len(entries)}")
(OUT/"manifest.json").write_text(json.dumps(entries,ensure_ascii=False,indent=2),encoding="utf-8")
pdf=Path("evidence/CAS_HOA_Yeu_Cau_2.pdf")
pdf.parent.mkdir(parents=True,exist_ok=True)
c=canvas.Canvas(str(pdf),pagesize=landscape(A4))
w,h=landscape(A4)
def text(x,y,t,size=12):
    c.setFont(FONT_NAME,size);c.drawString(x,y,t)
text(50,h-78,"TRƯỜNG ĐẠI HỌC TÔN ĐỨC THẮNG",17)
text(50,h-108,"BÁO CÁO YÊU CẦU 2 - LẬP TRÌNH WEB VÀ ỨNG DỤNG",15)
text(50,h-148,"XÂY DỰNG WEBSITE BÁN HOA TƯƠI VÀ QUẢN LÝ CỬA HÀNG",14)
text(50,h-188,"Nhóm 5: Nguyễn Văn Thiên Bảo, Trần Gia Hưng, Phạm Hiệp Phú",11)
text(50,h-220,"Giao diện HTML/CSS/JavaScript, xử lý PHP 8.2 và cơ sở dữ liệu MySQL 8",11)
text(50,h-260,"Bản minh chứng giai đoạn nộp gấp: 13/18 trang khách hàng.",11)
text(50,h-290,"Các chức năng tài khoản và đăng nhập sẽ hoàn thiện ở giai đoạn sau.",10)
text(50,h-335,"Ảnh chụp được ghi trực tiếp từ trình duyệt Chrome chạy trên môi trường kiểm thử.",10)
text(50,h-355,"Mỗi ảnh thể hiện cả thanh địa chỉ thực của trình duyệt.",10)
c.showPage()
for i,item in enumerate(entries,1):
    text(25,h-28,f"{i:02d}. {item['title']}",13)
    text(25,h-46,item["url"],9)
    img=Image.open(item["screenshot"])
    iw,ih=img.size; maxw=w-50; maxh=h-90
    scale=min(maxw/iw,maxh/ih)
    draw_w=iw*scale;draw_h=ih*scale
    c.drawImage(item["screenshot"],(w-draw_w)/2,33+(maxh-draw_h)/2,width=draw_w,height=draw_h)
    text(25,17,f"CÁ'S HOA - Yêu cầu 2 | Trang {i}",9)
    c.showPage()
c.save()
print(f"PDF READY: {pdf} ({pdf.stat().st_size} bytes; {len(entries)} screenshots)",flush=True)
