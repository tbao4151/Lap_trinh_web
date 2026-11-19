-- CÁ'S HOA: REAL PRODUCT CATALOG FROM OWN SUPABASE PROJECT (READ-ONLY EXPORT)
-- Source: Supabase 'Cá's Hoa' project kbfcxsbibrtafkokhfev, public.products/public.product_images.
-- Snapshot: 2026-11-19 Asia/Ho_Chi_Minh. 35 PUBLISHED products, 55 public storage images.
-- Run AFTER database/schema.sql, seed.sql, and migrations 001-005.
-- Run scripts/download-cashoa-product-images.ps1 first for local/offline images.
-- Existing base 11 products are retained. Hidden products are NOT imported.
-- MySQL format has ONE category/tone/occasion per product. Selected labels are representative; full multi-tag data stays in Supabase.
-- Use a fresh demo DB. Never import into production Supabase.
SET NAMES utf8mb4;
USE cas_hoa;
START TRANSACTION;

-- Enrich categories. INSERT IGNORE protects existing slug values.
INSERT IGNORE INTO categories(name,slug,status) VALUES ('Đồng tiền','dong-tien','active');
INSERT IGNORE INTO categories(name,slug,status) VALUES ('Hồng','hong','active');
INSERT IGNORE INTO categories(name,slug,status) VALUES ('Hồng đỏ Ecu','hong-do-ecu','active');
INSERT IGNORE INTO categories(name,slug,status) VALUES ('Hồng Julibee','julibee','active');
INSERT IGNORE INTO categories(name,slug,status) VALUES ('Hồng kem dâu','hong-kem-dau','active');
INSERT IGNORE INTO categories(name,slug,status) VALUES ('Hồng ohara','hong-ohara','active');
INSERT IGNORE INTO categories(name,slug,status) VALUES ('Hồng Sophia','hong-sophia','active');
INSERT IGNORE INTO categories(name,slug,status) VALUES ('Hướng dương','huong-duong','active');
INSERT IGNORE INTO categories(name,slug,status) VALUES ('Lam tinh','lam-tinh','active');
INSERT IGNORE INTO categories(name,slug,status) VALUES ('Ly kép','lily','active');
INSERT IGNORE INTO categories(name,slug,status) VALUES ('Phăng, cẩm chướng','phang-cam-chuong','active');
INSERT IGNORE INTO categories(name,slug,status) VALUES ('Phi yến','phi-yen','active');
INSERT IGNORE INTO categories(name,slug,status) VALUES ('Pingpong','ping-pong','active');
INSERT IGNORE INTO categories(name,slug,status) VALUES ('Tú cầu','tu-cau','active');
INSERT IGNORE INTO categories(name,slug,status) VALUES ('Tulip','tulip','active');

-- Enrich available tones and occasions only when missing.
INSERT INTO tones(name,status) SELECT 'Cam','active' WHERE NOT EXISTS (SELECT 1 FROM tones WHERE name='Cam');
INSERT INTO tones(name,status) SELECT 'Đen','active' WHERE NOT EXISTS (SELECT 1 FROM tones WHERE name='Đen');
INSERT INTO tones(name,status) SELECT 'Đỏ','active' WHERE NOT EXISTS (SELECT 1 FROM tones WHERE name='Đỏ');
INSERT INTO tones(name,status) SELECT 'Hồng','active' WHERE NOT EXISTS (SELECT 1 FROM tones WHERE name='Hồng');
INSERT INTO tones(name,status) SELECT 'Màu mix garden','active' WHERE NOT EXISTS (SELECT 1 FROM tones WHERE name='Màu mix garden');
INSERT INTO tones(name,status) SELECT 'Nâu','active' WHERE NOT EXISTS (SELECT 1 FROM tones WHERE name='Nâu');
INSERT INTO tones(name,status) SELECT 'Tím','active' WHERE NOT EXISTS (SELECT 1 FROM tones WHERE name='Tím');
INSERT INTO tones(name,status) SELECT 'Trắng','active' WHERE NOT EXISTS (SELECT 1 FROM tones WHERE name='Trắng');
INSERT INTO tones(name,status) SELECT 'Vàng','active' WHERE NOT EXISTS (SELECT 1 FROM tones WHERE name='Vàng');
INSERT INTO tones(name,status) SELECT 'Xanh Dương','active' WHERE NOT EXISTS (SELECT 1 FROM tones WHERE name='Xanh Dương');
INSERT INTO tones(name,status) SELECT 'Xanh lá','active' WHERE NOT EXISTS (SELECT 1 FROM tones WHERE name='Xanh lá');
INSERT INTO occasions(name,status) SELECT 'Cảm ơn','active' WHERE NOT EXISTS (SELECT 1 FROM occasions WHERE name='Cảm ơn');
INSERT INTO occasions(name,status) SELECT 'Khai trương','active' WHERE NOT EXISTS (SELECT 1 FROM occasions WHERE name='Khai trương');
INSERT INTO occasions(name,status) SELECT 'Kỷ niệm','active' WHERE NOT EXISTS (SELECT 1 FROM occasions WHERE name='Kỷ niệm');
INSERT INTO occasions(name,status) SELECT 'Sinh nhật','active' WHERE NOT EXISTS (SELECT 1 FROM occasions WHERE name='Sinh nhật');
INSERT INTO occasions(name,status) SELECT 'Tỏ tình','active' WHERE NOT EXISTS (SELECT 1 FROM occasions WHERE name='Tỏ tình');
INSERT INTO occasions(name,status) SELECT 'Tốt nghiệp','active' WHERE NOT EXISTS (SELECT 1 FROM occasions WHERE name='Tốt nghiệp');
INSERT INTO occasions(name,status) SELECT 'Xin lỗi','active' WHERE NOT EXISTS (SELECT 1 FROM occasions WHERE name='Xin lỗi');

-- Products: preserved SKU/slug/name; effective displayed price in VND at snapshot.
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-CAMCHUONG-001', 'phang-mix-lam-tinh', 'Phăng (cẩm chướng) mix lam tinh quế', 'bouquet', 370000, '', '', 'assets/images/cashoa_catalog/a56380a1-3449-432f-8ae6-2a26fa818f8f/034f8716-1414-4649-92fe-67947206ed9b.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Lam tinh' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Hồng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Sinh nhật' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-CAMCHUONG-002', 'cam-chuong-phang-nhe-nhang', 'Cẩm chướng (phăng) nhẹ nhàng', 'bouquet', 350000, '', '', 'assets/images/cashoa_catalog/8547b8ed-851b-496b-a0f7-8b6efb09cff7/5a1093ef-31a4-4314-8e16-7550dedf9962.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Phăng, cẩm chướng' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Hồng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Tỏ tình' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-CAMCHUONG-003', 'cam-chuong-hong-mix-lam-tinh', '(Phăng) Cẩm chướng hồng mix lam tinh', 'bouquet', 420000, '', '', 'assets/images/cashoa_catalog/76a1a576-b953-46cc-917f-35d87cae8b3e/e0bf9a3a-9ca2-417c-9ed2-1b6cb79351a2.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Lam tinh' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Hồng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Tốt nghiệp' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-CAMCHUONG-004', 'cam-chuong-phang-bo-tron-giay-nhan', 'Cẩm chướng (phăng) bó tròn giấy nhăn', 'bouquet', 490000, '', '', 'assets/images/cashoa_catalog/a83c8c85-1dab-40e9-9c44-822022586763/a7b99d26-7311-4bcc-bad3-18fa418215da.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Phăng, cẩm chướng' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Hồng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Kỷ niệm' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-GARDEN-PINK-001', 'size-xxl-sophia-mix-phang-lam-tinh', '(Size XXL) Sophia mix phăng, lam tinh', 'bouquet', 1200000, 'Mix thêm hồng chùm ngoại', '', 'assets/images/cashoa_catalog/70fba3a6-debe-4186-9cfc-16da88fe2f79/2f922609-34d4-4a91-a5c4-378efe19aa0f.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng Sophia' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Hồng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Cảm ơn' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-HONGDOECU-001', 'hong-do-ecu-tron', 'Hồng đỏ Ecu tròn', 'bouquet', 750000, 'Bó hoa gồm 45-50 bông', '', 'assets/images/cashoa_catalog/7b77d8e5-eab6-4138-914d-f7b0e7fb1139/bbddb1aa-ee32-4035-8f6a-a7ae76e84fd3.webp', 0, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng đỏ Ecu' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Đỏ' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Xin lỗi' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-HONGDOECU-002', 'hong-do-ecu-2', 'Hồng đỏ Ecu 2', 'bouquet', 450000, '18 bông', '', 'assets/images/cashoa_catalog/9ea6429d-d2b1-4f6c-9462-5fce763df104/5289c59f-e555-435d-8d70-1b9c287fdbc9.webp', 0, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng đỏ Ecu' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Đỏ' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Xin lỗi' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-HONGDOECU-003', 'ecu-goi-que', 'Ecu gói quế', 'bouquet', 220000, '7 bông', '', 'assets/images/cashoa_catalog/46f107f7-79a4-439c-ba0d-c03ecf613e49/15480b70-7f0b-4f43-8955-10b9874a574c.webp', 0, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng đỏ Ecu' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Đỏ' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Tỏ tình' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-HONGDOECU-004', 'hoa-hong-do-ecu', 'Hoa hồng đỏ Ecu', 'bouquet', 550000, '30 bông', '', 'assets/images/cashoa_catalog/84ad54f1-6553-418b-8632-aa5150435157/95987b3c-47b4-4038-b977-46f19c5b68b0.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng đỏ Ecu' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Đỏ' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Tỏ tình' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-HONGDOECU-005', 'hoa-hong-do-ecu-2', 'Hoa hồng đỏ Ecu 3', 'bouquet', 350000, '', '', 'assets/images/cashoa_catalog/1fee298e-1f08-4d29-a421-121ac034a03f/935c8bc5-dbc3-423d-81f8-aee00e9bfbb9.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng đỏ Ecu' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Đỏ' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Kỷ niệm' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-HONGJOLIBEE-001', 'hong-jolibee', 'Hồng Julibee', 'bouquet', 420000, '', '', 'assets/images/cashoa_catalog/304eca36-c60f-44ee-8012-740402b9d1a5/e77c578b-63b2-4d8b-9f75-d143f51d4e3a.webp', 1, 0, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Cam' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Kỷ niệm' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-HUONGDUONG-004', 'huong-duong-nhuy-den', 'Hướng dương nhụy đen mix tana', 'bouquet', 380000, '', '', 'assets/images/cashoa_catalog/23f52dd3-3a79-480b-a9cf-1c25f9e2b874/ff6416f9-8bb2-4ad0-9b2e-5a7afed74e43.webp', 0, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hướng dương' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Vàng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Cảm ơn' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-LAMTINH-001', 'blue-reverie', 'Sao xanh (Lam tinh)', 'bouquet', 400000, 'Được kết từ hoa sao xanh và tú cầu xanh dương, mang cảm giác trong trẻo và dịu nhẹ như một khoảng trời riêng. Sắc xanh gợi về sự bình yên, tin tưởng và những cảm xúc đẹp được giữ lại thật lâu.', '', 'assets/images/cashoa_catalog/1b463aec-4caf-4cbc-bf57-ced437436d4a/c0fdda15-ec1d-40fc-b141-007c3ec6b108.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Lam tinh' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Xanh Dương' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Sinh nhật' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-LILY-002', 'ly-kep-tim-mix', 'Chạng vạng', 'bouquet', 290000, 'Tone tím nhẹ nhàng, bông lily khác loại tùy thời điểm.', '', 'assets/images/cashoa_catalog/55828f63-af1e-482c-9622-52540f3a9c26/1aef43a5-bcba-4f40-9e6b-882bbb89bbda.jpg', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Ly kép' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Tím' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Xin lỗi' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-LILY-004', 'ly-kep-mix-tone-hong-size-m', '(Size M) Ly kép mix tone hồng', 'bouquet', 220000, '', '', 'assets/images/cashoa_catalog/5996f873-720c-4515-a834-498a086feaa9/723b59bb-2c3d-4e51-aa3d-f9b063ec78f8.webp', 0, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Ly kép' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Hồng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Sinh nhật' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-LILY-005', 'size-l-ly-kep-mix-tone-hong', '(Size L) Ly kép mix tone hồng', 'bouquet', 450000, '', '', 'assets/images/cashoa_catalog/bd7f4366-960a-4428-8c8b-12c4fe8582a7/2a07fd2c-b35b-49e2-b12c-cd89cc585ed9.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng kem dâu' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Hồng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Tỏ tình' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-MAUMIX-001', 'sophia-mix-phang-lam-tinh', '(Size L) Sophia mix phăng, lam tinh', 'bouquet', 420000, '', '', 'assets/images/cashoa_catalog/0b3f8d1f-0f4e-4c05-9c98-ef5442a5be86/c4f54456-f6b4-49f4-80ba-f08d3e894354.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng Sophia' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Hồng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Kỷ niệm' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-MAUMIX-BLUE-001', 'hong-mix-lam-tinh-tone-xanh-duong', 'Hồng mix lam tinh tone xanh dương', 'bouquet', 420000, '', '', 'assets/images/cashoa_catalog/95aa0cf0-9b23-40cb-a1eb-3ab2784e433c/55a1d26d-a34d-4c8f-8d3c-7a5bce477585.webp', 0, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng kem dâu' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Xanh Dương' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Kỷ niệm' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-SAOXANHLAMTINH-001', 'sao-xanh-lam-tinh-que-xo-dua', 'Sao xanh (Lam tinh) quế xơ dừa', 'bouquet', 420000, '', '', 'assets/images/cashoa_catalog/6120050e-ff67-4d56-b16d-1aba2d130974/e2482c3e-ad03-4575-b0e8-8281f191d604.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Lam tinh' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Xanh Dương' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Cảm ơn' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-SOPHIA-001', 'sophia-mix-phang-que-size-m', 'Sophia mix phăng quế size M', 'bouquet', 280000, '', '', 'assets/images/cashoa_catalog/a559bbca-0103-48ff-9a8a-c519806a4c48/ce8f0b33-ee4d-470d-98a4-da57ee5dabb3.jpg', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng Sophia' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Đen' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Sinh nhật' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-SOPHIA-002', 'sophia-1', 'Sophia 1', 'bouquet', 420000, '', '', 'assets/images/cashoa_catalog/3022292d-0a2e-42c0-b83b-892cad79ec42/c7a3c373-a0ce-4ffd-8e8e-8c724ae078fd.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng Sophia' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Hồng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Xin lỗi' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-SOPHIA-003', 'sophia-mix-phang', 'Sophia mix phăng', 'bouquet', 600000, '', '', 'assets/images/cashoa_catalog/d529f596-48f6-4be0-af80-b4382d24852e/108f85ac-ea8c-4717-b5c1-94904e3cefd3.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng Sophia' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Hồng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Sinh nhật' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-SOPHIA-004', 'sophia-mix-phang-lam-tinh-tron', 'Sophia mix phăng, lam tinh tròn', 'bouquet', 420000, '', '', 'assets/images/cashoa_catalog/8e1c5d66-77a5-427e-a5b3-aca98c56afc9/a9ff4190-f462-4e4f-9727-3bea3a62e8d8.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng Sophia' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Hồng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Tỏ tình' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-SOPHIAMIX-001', 'size-m-sophia-mix-phang-lam-tinh-que', '(Size M) Sophia mix phăng, lam tinh quế', 'bouquet', 330000, '', '', 'assets/images/cashoa_catalog/dc1b9878-1664-419f-8708-0e1c1b746f82/9b7d64d9-4470-4ce7-8f5f-d99d3d5a1807.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng Sophia' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Hồng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Tốt nghiệp' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-SOPHIAMIX-002', 'sophia-mix-phang-que', 'Sophia mix phăng quế', 'bouquet', 300000, 'Có bé gấu Tốt nghiệp', '', 'assets/images/cashoa_catalog/5824129c-83a2-40e3-9e8d-e720fc2dd0df/d8ea80df-240c-4c85-98f7-e31f6b0405b3.webp', 0, 0, 'active',
  (SELECT category_id FROM categories WHERE name='Hồng Sophia' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Hồng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Tốt nghiệp' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-TRAITIM-001', 'tu-cau-mix-hong-trai-tim', 'Tú cầu mix hồng trái tim', 'bouquet', 420000, '', '', 'assets/images/cashoa_catalog/bb823b36-3d9b-4987-a117-11c38812a561/e432650f-3839-42e3-8406-61c135a2904b.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Tú cầu' ORDER BY category_id LIMIT 1),NULL,(SELECT occasion_id FROM occasions WHERE name='Cảm ơn' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-TUCAU-002', 'tu-cau-mix-dong-tien', 'Tú cầu mix đồng tiền', 'bouquet', 190000, '', '', 'assets/images/cashoa_catalog/7699793d-5757-4549-93b0-b93eaa5696f4/4c85dd4d-9254-44a4-86b2-f2f72425fd76.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Tú cầu' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Xanh Dương' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Xin lỗi' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-TUCAU-003', 'tu-cau-bo-mix-dong-tien-giay-bo', 'Tú cầu bơ mix đồng tiền giấy bố', 'bouquet', 320000, '', '', 'assets/images/cashoa_catalog/ff1907a8-5f13-4443-b583-d47b8557baea/8425df69-6b12-4778-a787-f1e9c75654b6.webp', 0, 0, 'active',
  (SELECT category_id FROM categories WHERE name='Tú cầu' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Hồng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Xin lỗi' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-TUCAU-004', 'size-xxl-tu-cau-mix-tron', '(Size XXL) tú cầu mix tròn', 'bouquet', 500000, '', '', 'assets/images/cashoa_catalog/5d4e2d4b-5286-4571-bb77-3b415a604b86/47ca096f-3be8-4a97-ae65-a06878db111c.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Tú cầu' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Xanh Dương' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Sinh nhật' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-TUCAU-005', 'tu-cau-nho-nhe', 'Tú cầu nhỏ nhẹ', 'bouquet', 100000, '', '', 'assets/images/cashoa_catalog/87040b29-ec8d-479b-b487-bc787a62341c/caf4fe8b-7eb2-45d0-89b7-1c563ee15521.webp', 0, 0, 'active',
  (SELECT category_id FROM categories WHERE name='Tú cầu' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Xanh Dương' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Tỏ tình' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-TUCAU-006', 'tu-cau-que-trang', 'Tú cầu quế trắng', 'bouquet', 190000, '', '', 'assets/images/cashoa_catalog/05c87b60-b7f2-4733-85cd-d4e2ec76f086/9e60d2e4-a8fe-40f1-8875-452a8d5e6326.webp', 0, 0, 'active',
  (SELECT category_id FROM categories WHERE name='Tú cầu' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Xanh Dương' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Tốt nghiệp' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-TUCAUMIX-001', 'tu-cau-mix-diem-hong', 'Tú cầu mix điểm hồng', 'bouquet', 490000, 'Tú cầu xanh dương mix chút điểm hồng', '', 'assets/images/cashoa_catalog/5f10b491-50b7-4324-b0ff-5baa70b49fcb/6ea30f59-eba8-40e8-a633-39c00e55286c.jpg', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Tú cầu' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Màu mix garden' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Kỷ niệm' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-TUCAUXANHDUONG-002', 'tu-cau-xanh-duong-que-size-l', '(Size L) Tú cầu xanh dương quế', 'bouquet', 380000, 'Màu có thể đậm nhạt tùy đợt. Màu giấy gói có thể chọn: đen, trắng. Không lấy bé Tốt nghiệp giảm 30k.', '', 'assets/images/cashoa_catalog/581afe7d-fd32-47e4-859b-143bf6d59463/584db5b7-d3be-48c7-89b9-3e4d960d33b0.jpg', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Tú cầu' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Trắng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Cảm ơn' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'BO-TULIP-001', 'tulip-hong-nhe-nhang', 'Tulip hồng nhẹ nhàng', 'bouquet', 900000, 'bó 10 bông tulip', '', 'assets/images/cashoa_catalog/0560586f-ee68-4bcf-95df-0e07ddabf0ab/303e00c1-6e97-44ee-b430-c2a86c2d43a5.webp', 1, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Tulip' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Hồng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Sinh nhật' ORDER BY occasion_id LIMIT 1)
);
INSERT IGNORE INTO products(sku,slug,name,product_type,price,description,composition,image,featured,today,status,category_id,tone_id,occasion_id) VALUES (
  'CA-BO-HUONGDUONGMIX-P1UH3P', 'huong-duong-mix', 'Hướng dương mix', 'bouquet', 500000, '', '', 'assets/images/cashoa_catalog/276fa986-f1ea-4ac9-ace5-1bce10e3c0db/42b56c7c-f145-44e8-bf97-05423ea612c0.webp', 0, 1, 'active',
  (SELECT category_id FROM categories WHERE name='Hướng dương' ORDER BY category_id LIMIT 1),(SELECT tone_id FROM tones WHERE name='Trắng' ORDER BY tone_id LIMIT 1),(SELECT occasion_id FROM occasions WHERE name='Tốt nghiệp' ORDER BY occasion_id LIMIT 1)
);

-- Full gallery: cover first; safe to re-import thanks to NOT EXISTS checks.
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/a56380a1-3449-432f-8ae6-2a26fa818f8f/034f8716-1414-4649-92fe-67947206ed9b.webp','Phăng (cẩm chướng) mix lam tinh',0,1 FROM products p
WHERE p.sku='BO-CAMCHUONG-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/a56380a1-3449-432f-8ae6-2a26fa818f8f/034f8716-1414-4649-92fe-67947206ed9b.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/a56380a1-3449-432f-8ae6-2a26fa818f8f/c6a09ca2-8ce0-4446-803d-cc129f75125d.webp','Phăng (cẩm chướng) mix lam tinh',1,0 FROM products p
WHERE p.sku='BO-CAMCHUONG-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/a56380a1-3449-432f-8ae6-2a26fa818f8f/c6a09ca2-8ce0-4446-803d-cc129f75125d.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/a56380a1-3449-432f-8ae6-2a26fa818f8f/b62e1848-2867-418a-bd60-44af83e874dd.webp','Phăng (cẩm chướng) mix lam tinh',2,0 FROM products p
WHERE p.sku='BO-CAMCHUONG-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/a56380a1-3449-432f-8ae6-2a26fa818f8f/b62e1848-2867-418a-bd60-44af83e874dd.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/8547b8ed-851b-496b-a0f7-8b6efb09cff7/5a1093ef-31a4-4314-8e16-7550dedf9962.webp','Cẩm chướng (phăng) nhẹ nhàng',0,1 FROM products p
WHERE p.sku='BO-CAMCHUONG-002' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/8547b8ed-851b-496b-a0f7-8b6efb09cff7/5a1093ef-31a4-4314-8e16-7550dedf9962.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/76a1a576-b953-46cc-917f-35d87cae8b3e/e0bf9a3a-9ca2-417c-9ed2-1b6cb79351a2.webp','Ảnh sản phẩm',0,1 FROM products p
WHERE p.sku='BO-CAMCHUONG-003' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/76a1a576-b953-46cc-917f-35d87cae8b3e/e0bf9a3a-9ca2-417c-9ed2-1b6cb79351a2.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/a83c8c85-1dab-40e9-9c44-822022586763/a7b99d26-7311-4bcc-bad3-18fa418215da.webp','Cẩm chướng (phăng) bó tròn giấy nhăn',0,1 FROM products p
WHERE p.sku='BO-CAMCHUONG-004' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/a83c8c85-1dab-40e9-9c44-822022586763/a7b99d26-7311-4bcc-bad3-18fa418215da.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/a83c8c85-1dab-40e9-9c44-822022586763/16be16d6-a79d-492e-98ee-33c5f0e150aa.webp','Cẩm chướng (phăng) bó tròn giấy nhăn',1,0 FROM products p
WHERE p.sku='BO-CAMCHUONG-004' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/a83c8c85-1dab-40e9-9c44-822022586763/16be16d6-a79d-492e-98ee-33c5f0e150aa.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/70fba3a6-debe-4186-9cfc-16da88fe2f79/2f922609-34d4-4a91-a5c4-378efe19aa0f.webp','Ảnh sản phẩm',0,1 FROM products p
WHERE p.sku='BO-GARDEN-PINK-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/70fba3a6-debe-4186-9cfc-16da88fe2f79/2f922609-34d4-4a91-a5c4-378efe19aa0f.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/70fba3a6-debe-4186-9cfc-16da88fe2f79/0639a5bc-83a4-4f19-882d-3e2595dfdc54.webp','Ảnh sản phẩm',1,0 FROM products p
WHERE p.sku='BO-GARDEN-PINK-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/70fba3a6-debe-4186-9cfc-16da88fe2f79/0639a5bc-83a4-4f19-882d-3e2595dfdc54.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/7b77d8e5-eab6-4138-914d-f7b0e7fb1139/bbddb1aa-ee32-4035-8f6a-a7ae76e84fd3.webp','Hồng đỏ Ecu tròn',0,1 FROM products p
WHERE p.sku='BO-HONGDOECU-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/7b77d8e5-eab6-4138-914d-f7b0e7fb1139/bbddb1aa-ee32-4035-8f6a-a7ae76e84fd3.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/7b77d8e5-eab6-4138-914d-f7b0e7fb1139/6a2ab346-669b-41a4-9773-c8ca3224287f.webp','Hồng đỏ Ecu tròn',1,0 FROM products p
WHERE p.sku='BO-HONGDOECU-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/7b77d8e5-eab6-4138-914d-f7b0e7fb1139/6a2ab346-669b-41a4-9773-c8ca3224287f.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/9ea6429d-d2b1-4f6c-9462-5fce763df104/5289c59f-e555-435d-8d70-1b9c287fdbc9.webp','Hồng đỏ Ecu 2',0,1 FROM products p
WHERE p.sku='BO-HONGDOECU-002' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/9ea6429d-d2b1-4f6c-9462-5fce763df104/5289c59f-e555-435d-8d70-1b9c287fdbc9.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/46f107f7-79a4-439c-ba0d-c03ecf613e49/15480b70-7f0b-4f43-8955-10b9874a574c.webp','Ecu gói quế',0,1 FROM products p
WHERE p.sku='BO-HONGDOECU-003' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/46f107f7-79a4-439c-ba0d-c03ecf613e49/15480b70-7f0b-4f43-8955-10b9874a574c.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/46f107f7-79a4-439c-ba0d-c03ecf613e49/add6a0dc-ad99-4b62-81fb-a7e8bf0177b4.webp','Ecu gói quế',1,0 FROM products p
WHERE p.sku='BO-HONGDOECU-003' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/46f107f7-79a4-439c-ba0d-c03ecf613e49/add6a0dc-ad99-4b62-81fb-a7e8bf0177b4.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/84ad54f1-6553-418b-8632-aa5150435157/95987b3c-47b4-4038-b977-46f19c5b68b0.webp','Hoa hồng đỏ Ecu',0,1 FROM products p
WHERE p.sku='BO-HONGDOECU-004' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/84ad54f1-6553-418b-8632-aa5150435157/95987b3c-47b4-4038-b977-46f19c5b68b0.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/1fee298e-1f08-4d29-a421-121ac034a03f/935c8bc5-dbc3-423d-81f8-aee00e9bfbb9.webp','Hoa hồng đỏ Ecu 2',0,1 FROM products p
WHERE p.sku='BO-HONGDOECU-005' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/1fee298e-1f08-4d29-a421-121ac034a03f/935c8bc5-dbc3-423d-81f8-aee00e9bfbb9.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/1fee298e-1f08-4d29-a421-121ac034a03f/31ee9431-4d2f-427c-bd72-63337858c82d.webp','Hoa hồng đỏ Ecu 2',1,0 FROM products p
WHERE p.sku='BO-HONGDOECU-005' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/1fee298e-1f08-4d29-a421-121ac034a03f/31ee9431-4d2f-427c-bd72-63337858c82d.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/304eca36-c60f-44ee-8012-740402b9d1a5/e77c578b-63b2-4d8b-9f75-d143f51d4e3a.webp','Hồng Jolibee',0,1 FROM products p
WHERE p.sku='BO-HONGJOLIBEE-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/304eca36-c60f-44ee-8012-740402b9d1a5/e77c578b-63b2-4d8b-9f75-d143f51d4e3a.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/304eca36-c60f-44ee-8012-740402b9d1a5/7c545e5b-f688-4acd-9ed7-d2f92be10b76.webp','Hồng Jolibee',1,0 FROM products p
WHERE p.sku='BO-HONGJOLIBEE-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/304eca36-c60f-44ee-8012-740402b9d1a5/7c545e5b-f688-4acd-9ed7-d2f92be10b76.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/23f52dd3-3a79-480b-a9cf-1c25f9e2b874/ff6416f9-8bb2-4ad0-9b2e-5a7afed74e43.webp','Hướng dương nhụy đen mix tana',0,1 FROM products p
WHERE p.sku='BO-HUONGDUONG-004' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/23f52dd3-3a79-480b-a9cf-1c25f9e2b874/ff6416f9-8bb2-4ad0-9b2e-5a7afed74e43.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/1b463aec-4caf-4cbc-bf57-ced437436d4a/c0fdda15-ec1d-40fc-b141-007c3ec6b108.webp','Blue Reverie',0,1 FROM products p
WHERE p.sku='BO-LAMTINH-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/1b463aec-4caf-4cbc-bf57-ced437436d4a/c0fdda15-ec1d-40fc-b141-007c3ec6b108.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/1b463aec-4caf-4cbc-bf57-ced437436d4a/a1be7d2d-3d1a-4f99-aac2-b6e02d08a85f.webp','Blue Reverie',1,0 FROM products p
WHERE p.sku='BO-LAMTINH-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/1b463aec-4caf-4cbc-bf57-ced437436d4a/a1be7d2d-3d1a-4f99-aac2-b6e02d08a85f.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/55828f63-af1e-482c-9622-52540f3a9c26/1aef43a5-bcba-4f40-9e6b-882bbb89bbda.jpg','Ly kép tím mix',0,1 FROM products p
WHERE p.sku='BO-LILY-002' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/55828f63-af1e-482c-9622-52540f3a9c26/1aef43a5-bcba-4f40-9e6b-882bbb89bbda.jpg');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/55828f63-af1e-482c-9622-52540f3a9c26/57d0966e-dd36-45dc-a2d9-fd86cbd15f84.jpg','Ly kép tím mix',1,0 FROM products p
WHERE p.sku='BO-LILY-002' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/55828f63-af1e-482c-9622-52540f3a9c26/57d0966e-dd36-45dc-a2d9-fd86cbd15f84.jpg');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/5996f873-720c-4515-a834-498a086feaa9/723b59bb-2c3d-4e51-aa3d-f9b063ec78f8.webp','Ảnh sản phẩm',0,1 FROM products p
WHERE p.sku='BO-LILY-004' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/5996f873-720c-4515-a834-498a086feaa9/723b59bb-2c3d-4e51-aa3d-f9b063ec78f8.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/bd7f4366-960a-4428-8c8b-12c4fe8582a7/2a07fd2c-b35b-49e2-b12c-cd89cc585ed9.webp','(Size L) Ly kép mix tone hồng',0,1 FROM products p
WHERE p.sku='BO-LILY-005' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/bd7f4366-960a-4428-8c8b-12c4fe8582a7/2a07fd2c-b35b-49e2-b12c-cd89cc585ed9.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/0b3f8d1f-0f4e-4c05-9c98-ef5442a5be86/c4f54456-f6b4-49f4-80ba-f08d3e894354.webp','Ảnh sản phẩm',0,1 FROM products p
WHERE p.sku='BO-MAUMIX-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/0b3f8d1f-0f4e-4c05-9c98-ef5442a5be86/c4f54456-f6b4-49f4-80ba-f08d3e894354.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/95aa0cf0-9b23-40cb-a1eb-3ab2784e433c/55a1d26d-a34d-4c8f-8d3c-7a5bce477585.webp','Hồng mix lam tinh tone xanh dương',0,1 FROM products p
WHERE p.sku='BO-MAUMIX-BLUE-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/95aa0cf0-9b23-40cb-a1eb-3ab2784e433c/55a1d26d-a34d-4c8f-8d3c-7a5bce477585.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/6120050e-ff67-4d56-b16d-1aba2d130974/e2482c3e-ad03-4575-b0e8-8281f191d604.webp','Sao xanh (Lam tinh) quế xơ dừa',0,1 FROM products p
WHERE p.sku='BO-SAOXANHLAMTINH-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/6120050e-ff67-4d56-b16d-1aba2d130974/e2482c3e-ad03-4575-b0e8-8281f191d604.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/6120050e-ff67-4d56-b16d-1aba2d130974/480b671b-db46-4ee9-a876-0ba8df95cb3b.webp','Sao xanh (Lam tinh) quế xơ dừa',1,0 FROM products p
WHERE p.sku='BO-SAOXANHLAMTINH-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/6120050e-ff67-4d56-b16d-1aba2d130974/480b671b-db46-4ee9-a876-0ba8df95cb3b.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/a559bbca-0103-48ff-9a8a-c519806a4c48/ce8f0b33-ee4d-470d-98a4-da57ee5dabb3.jpg','Sophia mix phăng quế hồng size nhỏ',0,1 FROM products p
WHERE p.sku='BO-SOPHIA-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/a559bbca-0103-48ff-9a8a-c519806a4c48/ce8f0b33-ee4d-470d-98a4-da57ee5dabb3.jpg');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/a559bbca-0103-48ff-9a8a-c519806a4c48/b571d3ad-a16e-47be-8e6f-0528f304dd5f.jpg','Sophia mix phăng quế hồng size nhỏ',1,0 FROM products p
WHERE p.sku='BO-SOPHIA-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/a559bbca-0103-48ff-9a8a-c519806a4c48/b571d3ad-a16e-47be-8e6f-0528f304dd5f.jpg');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/3022292d-0a2e-42c0-b83b-892cad79ec42/c7a3c373-a0ce-4ffd-8e8e-8c724ae078fd.webp','Sophia 1',0,1 FROM products p
WHERE p.sku='BO-SOPHIA-002' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/3022292d-0a2e-42c0-b83b-892cad79ec42/c7a3c373-a0ce-4ffd-8e8e-8c724ae078fd.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/d529f596-48f6-4be0-af80-b4382d24852e/108f85ac-ea8c-4717-b5c1-94904e3cefd3.webp','Sophia mix phăng',0,1 FROM products p
WHERE p.sku='BO-SOPHIA-003' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/d529f596-48f6-4be0-af80-b4382d24852e/108f85ac-ea8c-4717-b5c1-94904e3cefd3.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/d529f596-48f6-4be0-af80-b4382d24852e/68a50b9e-5b18-4901-ba97-69d2aa499a10.webp','Sophia mix phăng',1,0 FROM products p
WHERE p.sku='BO-SOPHIA-003' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/d529f596-48f6-4be0-af80-b4382d24852e/68a50b9e-5b18-4901-ba97-69d2aa499a10.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/8e1c5d66-77a5-427e-a5b3-aca98c56afc9/a9ff4190-f462-4e4f-9727-3bea3a62e8d8.webp','Sophia mix phăng, lam tinh tròn',0,1 FROM products p
WHERE p.sku='BO-SOPHIA-004' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/8e1c5d66-77a5-427e-a5b3-aca98c56afc9/a9ff4190-f462-4e4f-9727-3bea3a62e8d8.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/8e1c5d66-77a5-427e-a5b3-aca98c56afc9/df606cf1-b92c-4d4b-9188-88fecf1370b7.webp','Sophia mix phăng, lam tinh tròn',1,0 FROM products p
WHERE p.sku='BO-SOPHIA-004' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/8e1c5d66-77a5-427e-a5b3-aca98c56afc9/df606cf1-b92c-4d4b-9188-88fecf1370b7.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/dc1b9878-1664-419f-8708-0e1c1b746f82/9b7d64d9-4470-4ce7-8f5f-d99d3d5a1807.webp','(Size M) Sophia mix phăng, lam tinh quế',0,1 FROM products p
WHERE p.sku='BO-SOPHIAMIX-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/dc1b9878-1664-419f-8708-0e1c1b746f82/9b7d64d9-4470-4ce7-8f5f-d99d3d5a1807.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/5824129c-83a2-40e3-9e8d-e720fc2dd0df/d8ea80df-240c-4c85-98f7-e31f6b0405b3.webp','Sophia mix phăng quế',0,1 FROM products p
WHERE p.sku='BO-SOPHIAMIX-002' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/5824129c-83a2-40e3-9e8d-e720fc2dd0df/d8ea80df-240c-4c85-98f7-e31f6b0405b3.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/bb823b36-3d9b-4987-a117-11c38812a561/e432650f-3839-42e3-8406-61c135a2904b.webp','Ảnh sản phẩm',0,1 FROM products p
WHERE p.sku='BO-TRAITIM-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/bb823b36-3d9b-4987-a117-11c38812a561/e432650f-3839-42e3-8406-61c135a2904b.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/bb823b36-3d9b-4987-a117-11c38812a561/95f47d38-1acd-4a0b-9fca-09e04c52a1c5.webp','Ảnh sản phẩm',1,0 FROM products p
WHERE p.sku='BO-TRAITIM-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/bb823b36-3d9b-4987-a117-11c38812a561/95f47d38-1acd-4a0b-9fca-09e04c52a1c5.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/7699793d-5757-4549-93b0-b93eaa5696f4/4c85dd4d-9254-44a4-86b2-f2f72425fd76.webp','Tú cầu mix đồng tiền',0,1 FROM products p
WHERE p.sku='BO-TUCAU-002' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/7699793d-5757-4549-93b0-b93eaa5696f4/4c85dd4d-9254-44a4-86b2-f2f72425fd76.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/ff1907a8-5f13-4443-b583-d47b8557baea/8425df69-6b12-4778-a787-f1e9c75654b6.webp','Tú cầu bơ mix đồng tiền',0,1 FROM products p
WHERE p.sku='BO-TUCAU-003' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/ff1907a8-5f13-4443-b583-d47b8557baea/8425df69-6b12-4778-a787-f1e9c75654b6.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/5d4e2d4b-5286-4571-bb77-3b415a604b86/47ca096f-3be8-4a97-ae65-a06878db111c.webp','(Size XXL) tú cầu mix tròn',0,1 FROM products p
WHERE p.sku='BO-TUCAU-004' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/5d4e2d4b-5286-4571-bb77-3b415a604b86/47ca096f-3be8-4a97-ae65-a06878db111c.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/5d4e2d4b-5286-4571-bb77-3b415a604b86/5f8c9479-3761-4dd5-94d6-e1ea49540aec.webp','(Size XXL) tú cầu mix tròn',1,0 FROM products p
WHERE p.sku='BO-TUCAU-004' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/5d4e2d4b-5286-4571-bb77-3b415a604b86/5f8c9479-3761-4dd5-94d6-e1ea49540aec.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/87040b29-ec8d-479b-b487-bc787a62341c/caf4fe8b-7eb2-45d0-89b7-1c563ee15521.webp','Tú cầu nhỏ nhẹ',0,1 FROM products p
WHERE p.sku='BO-TUCAU-005' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/87040b29-ec8d-479b-b487-bc787a62341c/caf4fe8b-7eb2-45d0-89b7-1c563ee15521.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/05c87b60-b7f2-4733-85cd-d4e2ec76f086/9e60d2e4-a8fe-40f1-8875-452a8d5e6326.webp','Ảnh sản phẩm',0,1 FROM products p
WHERE p.sku='BO-TUCAU-006' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/05c87b60-b7f2-4733-85cd-d4e2ec76f086/9e60d2e4-a8fe-40f1-8875-452a8d5e6326.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/5f10b491-50b7-4324-b0ff-5baa70b49fcb/6ea30f59-eba8-40e8-a633-39c00e55286c.jpg','Tú cầu mix điểm hồng',0,1 FROM products p
WHERE p.sku='BO-TUCAUMIX-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/5f10b491-50b7-4324-b0ff-5baa70b49fcb/6ea30f59-eba8-40e8-a633-39c00e55286c.jpg');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/5f10b491-50b7-4324-b0ff-5baa70b49fcb/8b2efbbe-2880-4c05-9e3a-5b57fa630203.jpg','Tú cầu mix điểm hồng',1,0 FROM products p
WHERE p.sku='BO-TUCAUMIX-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/5f10b491-50b7-4324-b0ff-5baa70b49fcb/8b2efbbe-2880-4c05-9e3a-5b57fa630203.jpg');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/581afe7d-fd32-47e4-859b-143bf6d59463/584db5b7-d3be-48c7-89b9-3e4d960d33b0.jpg','Tú cầu xanh dương quế',0,1 FROM products p
WHERE p.sku='BO-TUCAUXANHDUONG-002' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/581afe7d-fd32-47e4-859b-143bf6d59463/584db5b7-d3be-48c7-89b9-3e4d960d33b0.jpg');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/581afe7d-fd32-47e4-859b-143bf6d59463/0c9537bb-0fd9-404b-b925-e18f5a4ac45b.jpg','Tú cầu xanh dương quế',1,0 FROM products p
WHERE p.sku='BO-TUCAUXANHDUONG-002' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/581afe7d-fd32-47e4-859b-143bf6d59463/0c9537bb-0fd9-404b-b925-e18f5a4ac45b.jpg');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/0560586f-ee68-4bcf-95df-0e07ddabf0ab/303e00c1-6e97-44ee-b430-c2a86c2d43a5.webp','Tulip hồng nhẹ nhàng',0,1 FROM products p
WHERE p.sku='BO-TULIP-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/0560586f-ee68-4bcf-95df-0e07ddabf0ab/303e00c1-6e97-44ee-b430-c2a86c2d43a5.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/0560586f-ee68-4bcf-95df-0e07ddabf0ab/b27c4c8a-257a-49dd-a78b-b8a12b10bc56.webp','Tulip hồng nhẹ nhàng',1,0 FROM products p
WHERE p.sku='BO-TULIP-001' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/0560586f-ee68-4bcf-95df-0e07ddabf0ab/b27c4c8a-257a-49dd-a78b-b8a12b10bc56.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/276fa986-f1ea-4ac9-ace5-1bce10e3c0db/42b56c7c-f145-44e8-bf97-05423ea612c0.webp','Hướng dương mix',0,1 FROM products p
WHERE p.sku='CA-BO-HUONGDUONGMIX-P1UH3P' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/276fa986-f1ea-4ac9-ace5-1bce10e3c0db/42b56c7c-f145-44e8-bf97-05423ea612c0.webp');
INSERT INTO product_images(product_id,path,alt,sort_order,is_primary)
SELECT p.product_id,'assets/images/cashoa_catalog/276fa986-f1ea-4ac9-ace5-1bce10e3c0db/a91bb8f3-0fc2-4ac4-bce8-a1e755af9644.webp','Hướng dương mix',1,0 FROM products p
WHERE p.sku='CA-BO-HUONGDUONGMIX-P1UH3P' AND NOT EXISTS (SELECT 1 FROM product_images x WHERE x.product_id=p.product_id AND x.path='assets/images/cashoa_catalog/276fa986-f1ea-4ac9-ace5-1bce10e3c0db/a91bb8f3-0fc2-4ac4-bce8-a1e755af9644.webp');

-- Available wrapping options for imported products; existing settings retained.
INSERT INTO product_wrapping(product_id,color_id)
SELECT p.product_id,w.color_id FROM products p CROSS JOIN wrapping_colors w
WHERE p.sku IN ('BO-CAMCHUONG-001', 'BO-CAMCHUONG-002', 'BO-CAMCHUONG-003', 'BO-CAMCHUONG-004', 'BO-GARDEN-PINK-001', 'BO-HONGDOECU-001', 'BO-HONGDOECU-002', 'BO-HONGDOECU-003', 'BO-HONGDOECU-004', 'BO-HONGDOECU-005', 'BO-HONGJOLIBEE-001', 'BO-HUONGDUONG-004', 'BO-LAMTINH-001', 'BO-LILY-002', 'BO-LILY-004', 'BO-LILY-005', 'BO-MAUMIX-001', 'BO-MAUMIX-BLUE-001', 'BO-SAOXANHLAMTINH-001', 'BO-SOPHIA-001', 'BO-SOPHIA-002', 'BO-SOPHIA-003', 'BO-SOPHIA-004', 'BO-SOPHIAMIX-001', 'BO-SOPHIAMIX-002', 'BO-TRAITIM-001', 'BO-TUCAU-002', 'BO-TUCAU-003', 'BO-TUCAU-004', 'BO-TUCAU-005', 'BO-TUCAU-006', 'BO-TUCAUMIX-001', 'BO-TUCAUXANHDUONG-002', 'BO-TULIP-001', 'CA-BO-HUONGDUONGMIX-P1UH3P') AND w.color_id IN (1,2,3)
AND NOT EXISTS (SELECT 1 FROM product_wrapping x WHERE x.product_id=p.product_id AND x.color_id=w.color_id);

COMMIT;

-- Verification
SELECT COUNT(*) AS imported_products FROM products WHERE sku IN ('BO-CAMCHUONG-001', 'BO-CAMCHUONG-002', 'BO-CAMCHUONG-003', 'BO-CAMCHUONG-004', 'BO-GARDEN-PINK-001', 'BO-HONGDOECU-001', 'BO-HONGDOECU-002', 'BO-HONGDOECU-003', 'BO-HONGDOECU-004', 'BO-HONGDOECU-005', 'BO-HONGJOLIBEE-001', 'BO-HUONGDUONG-004', 'BO-LAMTINH-001', 'BO-LILY-002', 'BO-LILY-004', 'BO-LILY-005', 'BO-MAUMIX-001', 'BO-MAUMIX-BLUE-001', 'BO-SAOXANHLAMTINH-001', 'BO-SOPHIA-001', 'BO-SOPHIA-002', 'BO-SOPHIA-003', 'BO-SOPHIA-004', 'BO-SOPHIAMIX-001', 'BO-SOPHIAMIX-002', 'BO-TRAITIM-001', 'BO-TUCAU-002', 'BO-TUCAU-003', 'BO-TUCAU-004', 'BO-TUCAU-005', 'BO-TUCAU-006', 'BO-TUCAUMIX-001', 'BO-TUCAUXANHDUONG-002', 'BO-TULIP-001', 'CA-BO-HUONGDUONGMIX-P1UH3P');
SELECT COUNT(*) AS imported_gallery_images FROM product_images i JOIN products p ON p.product_id=i.product_id WHERE p.sku IN ('BO-CAMCHUONG-001', 'BO-CAMCHUONG-002', 'BO-CAMCHUONG-003', 'BO-CAMCHUONG-004', 'BO-GARDEN-PINK-001', 'BO-HONGDOECU-001', 'BO-HONGDOECU-002', 'BO-HONGDOECU-003', 'BO-HONGDOECU-004', 'BO-HONGDOECU-005', 'BO-HONGJOLIBEE-001', 'BO-HUONGDUONG-004', 'BO-LAMTINH-001', 'BO-LILY-002', 'BO-LILY-004', 'BO-LILY-005', 'BO-MAUMIX-001', 'BO-MAUMIX-BLUE-001', 'BO-SAOXANHLAMTINH-001', 'BO-SOPHIA-001', 'BO-SOPHIA-002', 'BO-SOPHIA-003', 'BO-SOPHIA-004', 'BO-SOPHIAMIX-001', 'BO-SOPHIAMIX-002', 'BO-TRAITIM-001', 'BO-TUCAU-002', 'BO-TUCAU-003', 'BO-TUCAU-004', 'BO-TUCAU-005', 'BO-TUCAU-006', 'BO-TUCAUMIX-001', 'BO-TUCAUXANHDUONG-002', 'BO-TULIP-001', 'CA-BO-HUONGDUONGMIX-P1UH3P');
