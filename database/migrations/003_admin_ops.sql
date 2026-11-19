-- CÁ'S HOA | Migration 003 — Admin Operations (TV3: ADM-02..ADM-06)
-- Chạy SAU schema.sql, seed.sql, 001_auth.php.sql, 002_inventory_seed.sql.
-- Tăng dần, chạy lại nhiều lần không lỗi và không xoá dữ liệu (MySQL 8.0+ và MariaDB 10.4+).
-- Cột mới được thêm qua information_schema + PREPARE vì MySQL 8 không hỗ trợ ADD COLUMN IF NOT EXISTS.
USE cas_hoa;
SET @db := DATABASE();

-- ---------------------------------------------------------------- Kho (ADM-03)
SET @q := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='inventory_items' AND column_name='category')=0,
  'ALTER TABLE inventory_items ADD COLUMN category VARCHAR(30) NOT NULL DEFAULT ''phu_kien'' AFTER item_type', 'DO 0');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;
SET @q := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='inventory_items' AND column_name='updated_at')=0,
  'ALTER TABLE inventory_items ADD COLUMN updated_at DATETIME NULL', 'DO 0');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;
-- Phân nhóm dữ liệu kho mẫu (chỉ sửa dòng còn giá trị mặc định)
UPDATE inventory_items SET category='hoa' WHERE item_type='flower' AND category='phu_kien';
UPDATE inventory_items SET category='giay_goi' WHERE item_type='accessory' AND category='phu_kien' AND name LIKE 'Giấy gói%';
UPDATE inventory_items SET category='no' WHERE item_type='accessory' AND category='phu_kien' AND (name LIKE 'Ruy băng%' OR name LIKE 'Nơ%');
-- Không cho tồn âm ở mức CSDL (bổ sung cho kiểm tra trong PHP)
SET @q := IF((SELECT COUNT(*) FROM information_schema.check_constraints WHERE constraint_schema=@db AND constraint_name='chk_inventory_qty_nonneg')=0,
  'ALTER TABLE inventory_items ADD CONSTRAINT chk_inventory_qty_nonneg CHECK (quantity >= 0 AND min_quantity >= 0 AND unit_cost >= 0)', 'DO 0');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @q := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='inventory_transactions' AND column_name='quantity_before')=0,
  'ALTER TABLE inventory_transactions ADD COLUMN quantity_before DECIMAL(12,2) NULL AFTER quantity, ADD COLUMN quantity_after DECIMAL(12,2) NULL AFTER quantity_before', 'DO 0');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;
SET @q := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='inventory_transactions' AND column_name='request_token')=0,
  'ALTER TABLE inventory_transactions ADD COLUMN request_token CHAR(32) NULL, ADD UNIQUE KEY uq_inventory_request (request_token), ADD INDEX idx_inventory_tx_created (created_at)', 'DO 0');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------- Sản phẩm & hình ảnh (ADM-02)
CREATE TABLE IF NOT EXISTS product_images (
  image_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  path VARCHAR(255) NOT NULL,
  alt VARCHAR(180),
  sort_order INT NOT NULL DEFAULT 0,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
  INDEX idx_product_images (product_id, sort_order)
) ENGINE=InnoDB;
-- Ảnh hiện có của mỗi sản phẩm trở thành ảnh đại diện
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,p.image,p.name,0,1 FROM products p
WHERE p.image IS NOT NULL AND p.image<>'' AND NOT EXISTS (SELECT 1 FROM product_images i WHERE i.product_id=p.product_id);

-- ---------------------------------------------------------------- Giấy gói (ADM-05)
SET @q := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='wrapping_colors' AND column_name='surcharge')=0,
  'ALTER TABLE wrapping_colors ADD COLUMN surcharge DECIMAL(12,2) NOT NULL DEFAULT 0, ADD COLUMN image VARCHAR(255) NULL, ADD COLUMN sort_order INT NOT NULL DEFAULT 0', 'DO 0');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;
INSERT IGNORE INTO wrapping_colors(color_id,name,hex_code,status,surcharge,sort_order) VALUES
 (1,'Kem sữa','#EDE3D4','active',0,1),
 (2,'Xanh olive','#8FA18A','active',0,2),
 (3,'Hồng nude','#DDB4AE','active',10000,3),
 (4,'Đen than','#303633','inactive',15000,4);
-- Gán mặc định 3 màu đang dùng cho các sản phẩm mẫu chưa có cấu hình giấy gói
INSERT IGNORE INTO product_wrapping(product_id,color_id)
SELECT p.product_id,w.color_id FROM products p JOIN wrapping_colors w ON w.color_id IN (1,2,3)
WHERE p.product_id<=11 AND NOT EXISTS (SELECT 1 FROM product_wrapping x WHERE x.product_id=p.product_id);

-- ---------------------------------------------------------------- Danh mục khám phá trang chủ (ADM-05)
CREATE TABLE IF NOT EXISTS discovery_groups (
  group_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  title VARCHAR(80) NOT NULL,
  subtitle VARCHAR(60),
  image VARCHAR(255) NOT NULL,
  filter_type VARCHAR(20) NOT NULL,
  filter_id INT UNSIGNED NULL,
  filter_value VARCHAR(30) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  updated_at DATETIME NULL,
  INDEX idx_discovery_order (status, sort_order)
) ENGINE=InnoDB;
INSERT IGNORE INTO discovery_groups(group_id,slug,title,subtitle,image,filter_type,filter_id,filter_value,sort_order,status) VALUES
 (1,'sinh-nhat','Sinh nhật','Theo dịp','assets/images/garden.png','occasion',2,NULL,1,'active'),
 (2,'nguoi-thuong','Người thương','Theo cảm xúc','assets/images/mot-bo-hoa.png','occasion',1,NULL,2,'active'),
 (3,'xanh-diu','Xanh dịu','Theo tone màu','assets/images/ly-xanh.png','tone',1,NULL,3,'active'),
 (4,'ky-niem','Kỷ niệm','Theo dịp','assets/images/phi-yen.png','occasion',4,NULL,4,'active');

-- ---------------------------------------------------------------- Giao hàng & hoàn tiền (ADM-04)
CREATE TABLE IF NOT EXISTS delivery_events (
  event_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  delivery_id INT UNSIGNED NOT NULL,
  from_status VARCHAR(40) NOT NULL,
  to_status VARCHAR(40) NOT NULL,
  actor_id INT UNSIGNED NULL,
  note VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (delivery_id) REFERENCES deliveries(delivery_id),
  FOREIGN KEY (actor_id) REFERENCES users(user_id),
  INDEX idx_delivery_events (delivery_id, created_at)
) ENGINE=InnoDB;

SET @q := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='refunds' AND column_name='requested_by')=0,
  'ALTER TABLE refunds ADD COLUMN requested_by INT UNSIGNED NULL, ADD COLUMN reviewed_by INT UNSIGNED NULL, ADD COLUMN reviewed_at DATETIME NULL, ADD COLUMN reference VARCHAR(120) NULL, ADD COLUMN request_token CHAR(32) NULL, ADD UNIQUE KEY uq_refund_request (request_token), ADD CONSTRAINT fk_refund_requested_by FOREIGN KEY (requested_by) REFERENCES users(user_id), ADD CONSTRAINT fk_refund_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(user_id)', 'DO 0');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;
CREATE TABLE IF NOT EXISTS refund_events (
  event_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  refund_id INT UNSIGNED NOT NULL,
  from_status VARCHAR(30) NULL,
  to_status VARCHAR(30) NOT NULL,
  actor_id INT UNSIGNED NULL,
  note VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (refund_id) REFERENCES refunds(refund_id),
  FOREIGN KEY (actor_id) REFERENCES users(user_id),
  INDEX idx_refund_events (refund_id, created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------- Cài đặt & nhật ký quản trị (ADM-06)
CREATE TABLE IF NOT EXISTS shop_settings (
  setting_key VARCHAR(60) PRIMARY KEY,
  setting_value VARCHAR(1000) NOT NULL DEFAULT '',
  updated_by INT UNSIGNED NULL,
  updated_at DATETIME NULL,
  FOREIGN KEY (updated_by) REFERENCES users(user_id)
) ENGINE=InnoDB;
INSERT IGNORE INTO shop_settings(setting_key,setting_value) VALUES
 ('shop_name','CÁ''S HOA'),
 ('hotline','0707309255'),
 ('email','hello@cashoa.vn'),
 ('address','126/13 đường số 17, Linh Xuân, Thủ Đức, TP.HCM'),
 ('opening_hours','08:00 - 20:00, từ thứ Hai đến Chủ nhật'),
 ('announcement','Có sẵn thiệp và túi · Shop xác nhận đơn sau khi nhận thông tin'),
 ('instagram','https://www.instagram.com/'),
 ('zalo','0707309255'),
 ('default_shipping_fee','30000');

CREATE TABLE IF NOT EXISTS admin_audit_log (
  log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,
  entity_type VARCHAR(40) NOT NULL,
  entity_id VARCHAR(60),
  outcome VARCHAR(20) NOT NULL DEFAULT 'ok',
  detail TEXT,
  ip VARCHAR(45),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id),
  INDEX idx_audit_created (created_at),
  INDEX idx_audit_user (user_id, created_at)
) ENGINE=InnoDB;
