-- CÁ'S HOA inventory data from static admin prototype; import after schema.
USE cas_hoa;
INSERT IGNORE INTO inventory_items(item_id,item_type,name,unit,quantity,min_quantity,unit_cost,status) VALUES
(1,'flower','Hoa hồng đỏ','cành',34,18,12000,'active'),
(2,'flower','Hoa ly trắng','cành',9,12,22000,'active'),
(3,'flower','Cẩm tú cầu xanh','bông',7,10,38000,'active'),
(4,'flower','Phi yến tím','cành',16,10,18000,'active'),
(5,'flower','Baby trắng','bó',12,6,55000,'active'),
(6,'accessory','Giấy gói kraft','tờ',45,20,5000,'active'),
(7,'accessory','Giấy gói kem','tờ',18,20,6500,'active'),
(8,'accessory','Ruy băng lụa','cuộn',11,5,28000,'active'),
(9,'accessory','Thiệp CÁ''S HOA','cái',62,25,2500,'active'),
(10,'accessory','Túi đựng hoa','cái',26,15,9000,'active');
