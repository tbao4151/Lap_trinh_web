USE cas_hoa;
INSERT IGNORE INTO roles(role_id,name,description) VALUES
(1,'customer','Khách hàng'),(2,'staff','Nhân viên'),(3,'admin','Quản trị viên');
INSERT IGNORE INTO categories(category_id,name,slug) VALUES
(1,'Hoa ly','hoa-ly'),(2,'Lily','lily'),(3,'Hoa hồng','hoa-hong'),(4,'Cẩm tú cầu','cam-tu-cau'),(5,'Phi yến','phi-yen');
INSERT IGNORE INTO tones(tone_id,name,hex_code) VALUES
(1,'Xanh','#79B9AF'),(2,'Hồng','#E9A9C2'),(3,'Đỏ','#C54A4A'),(4,'Tím','#926DA4');
INSERT IGNORE INTO occasions(occasion_id,name) VALUES
(1,'Tặng người thương'),(2,'Sinh nhật'),(3,'Chúc mừng'),(4,'Kỷ niệm');
-- Sản phẩm được nhập từ assets/js/products.js của bản yêu cầu 1.
INSERT IGNORE INTO products(product_id,category_id,tone_id,occasion_id,sku,slug,name,product_type,price,description,composition,image,featured,today,status) VALUES
(1,1,1,1,'IG-LAM-TINH-400','lam-tinh','Lam tinh','bouquet',400000,'Một bó hoa mang sắc xanh dịu, phù hợp cho những lời nhắn nhẹ nhàng và tinh tế.','Hoa ly phối cùng hoa lá theo mùa. Màu và kích thước có thể thay đổi nhẹ theo từng đợt hoa.','assets/images/lam-tinh.png',1,1,'active'),
(2,1,2,2,'IG-GARDEN-380','garden','Garden','bouquet',380000,'Bó hoa mang cảm giác như một khu vườn nhỏ, mềm mại và tự nhiên.','Hoa ly và hoa lá theo mùa. Màu hoa ly có thể đậm hoặc nhạt khác nhau tuỳ đợt hoa.','assets/images/garden.png',1,1,'active'),
(3,1,2,3,'IG-HOA-LY-350','hoa-ly','Hoa ly','bouquet',350000,'Bó hoa ly nữ tính, tươi sáng và dễ tặng trong nhiều dịp.','Hoa ly phối lá. Sắc độ hoa thay đổi tự nhiên theo từng đợt.','assets/images/hoa-ly.png',1,1,'active'),
(4,1,1,4,'IG-LY-XANH-370','ly-xanh','Ly xanh','bouquet',370000,'Tông xanh mát và lạ mắt, thích hợp cho người yêu những gam màu dịu.','Bó có khoảng 3–5 bông ly tuỳ kích thước hoa ở thời điểm thực hiện.','assets/images/ly-xanh.png',1,1,'active'),
(5,2,1,2,'IG-LILY-310','lily','Lily','bouquet',310000,'Mẫu lily trẻ trung với cách gói tối giản, giữ trọng tâm ở sắc hoa.','Lily và hoa lá theo mùa.','assets/images/lily.png',0,0,'active'),
(6,3,3,1,'IG-MOT-BO-HOA-390','mot-bo-hoa-mot-lan-duoc-nho-den','Một bó hoa, một lần được nhớ đến','bouquet',390000,'Sắc đỏ sâu và ấm dành cho một lời nhắn có chủ ý.','Hoa hồng phối hoa lá theo mùa.','assets/images/mot-bo-hoa.png',0,0,'active'),
(7,4,1,3,'IG-CAM-TU-CAU-450','cam-tu-cau','Cẩm tú cầu','bouquet',450000,'Bó cẩm tú cầu đầy đặn, thanh lịch với tông xanh dịu.','Cẩm tú cầu và lá phụ theo mùa.','assets/images/cam-tu-cau.png',0,0,'active'),
(8,5,4,4,'IG-PHI-YEN-370','phi-yen','Phi yến','bouquet',370000,'Phi yến tạo dáng cao, thanh thoát và có nhiều khoảng thở.','Phi yến phối hoa lá theo mùa.','assets/images/phi-yen.png',0,0,'active'),
(9,3,4,1,'IG-SON-SAC-290','son-sac-thuy-chung','Son sắc thuỷ chung','bouquet',290000,'Một lựa chọn nhỏ gọn với sắc tím trầm, phù hợp cho những dịp giản dị.','Hoa hồng phối hoa lá theo mùa.','assets/images/son-sac-thuy-chung.png',0,0,'active'),
(10,4,1,3,'GH-AN-NHIEN-420','gio-hoa-an-nhien','Giỏ hoa An Nhiên','basket',420000,'Giỏ hoa xanh dịu, gọn gàng và thanh lịch cho những lời chúc nhẹ nhàng.','Cẩm tú cầu và hoa lá theo mùa.','assets/images/cam-tu-cau.png',0,1,'active'),
(11,1,2,2,'GH-DIU-DANG-390','gio-hoa-diu-dang','Giỏ hoa Dịu Dàng','basket',390000,'Giỏ hoa tông hồng mềm mại, phù hợp sinh nhật và những dịp chúc mừng.','Hoa ly phối hoa lá theo mùa.','assets/images/garden.png',0,0,'active');
INSERT IGNORE INTO news(slug,title,summary,thumbnail,content,status,published_at) VALUES
('chon-hoa-theo-mau','Chọn hoa theo màu sắc và cảm xúc','Tông xanh, hồng, đỏ hay tím tạo nên những sắc thái khác nhau cho món quà.','assets/images/lam-tinh.png','Màu sắc là một trong những yếu tố đầu tiên tạo cảm giác cho món quà. Tông xanh gợi cảm giác dịu và bình yên; tông hồng tạo sự ấm áp; đỏ mang cảm xúc trực diện còn tím có nét trầm và tinh tế.','published','2026-09-18 08:00:00'),
('viet-thiep-ngan','Viết gì trên một tấm thiệp ngắn?','Một lời nhắn không cần dài. Điều quan trọng là đúng người và đúng dịp.','assets/images/garden.png','Thiệp đi cùng hoa không cần dài. Hãy bắt đầu từ dịp tặng, giữ một ý chính và ký tên khi cần. Một câu đúng ngữ cảnh thường có giá trị hơn một đoạn quá cầu kỳ.','published','2026-09-12 08:00:00'),
('giu-hoa-tuoi-lau','Giữ hoa tươi lâu hơn sau khi nhận','Một vài thao tác khi cắm và thay nước giúp hoa tươi lâu.','assets/images/cam-tu-cau.png','Chuẩn bị bình sạch, dùng nước mới và loại bỏ lá nằm dưới mặt nước. Thay nước định kỳ, cắt lại gốc cành và tránh đặt hoa gần nắng gắt, nguồn nhiệt mạnh.','published','2026-09-05 08:00:00'),
('hoa-sinh-nhat','Gợi ý chọn hoa sinh nhật theo tính cách','Chọn mẫu hoa theo tính cách của người nhận.','assets/images/hoa-ly.png','Người thích tối giản phù hợp với ít loại hoa và màu dịu. Người thích nổi bật có thể chọn các tông rõ và bó nhiều lớp. Người yêu thiên nhiên có thể thích các mẫu phối nhiều lá.','published','2026-08-30 08:00:00'),
('hoa-chuc-mung','Hoa chúc mừng nên chọn bó hay giỏ?','Bó hoa linh hoạt; giỏ hoa thuận tiện trưng bày.','assets/images/mot-bo-hoa.png','Bó hoa dễ cầm và phù hợp tặng trực tiếp. Giỏ hoa cố định, tiện đặt trên bàn hoặc văn phòng. Hãy cân nhắc địa điểm người nhận trước khi lựa chọn.','published','2026-08-24 08:00:00'),
('chon-hoa-ky-niem','Một bó hoa cho ngày kỷ niệm','Chọn hoa có ý nghĩa riêng với người nhận.','assets/images/phi-yen.png','Một bó hoa không cần quá lớn nếu gắn với kỷ niệm của hai người. Hãy chọn màu hoặc loài hoa có ý nghĩa, thêm lời nhắn gợi nhớ và giữ tổng thể vừa đủ.','published','2026-08-16 08:00:00');
