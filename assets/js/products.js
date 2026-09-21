window.CAS_PRODUCTS = [
  {
    id: 'p001', sku: 'IG-LAM-TINH-400', slug: 'lam-tinh', name: 'Lam tinh', type: 'bouquet',
    price: 400000, image: 'assets/images/lam-tinh.svg',
    categories: ['Hoa ly', 'Xanh'], tone: 'Xanh', occasion: 'Tặng người thương',
    description: 'Một bó hoa mang sắc xanh dịu, phù hợp cho những lời nhắn nhẹ nhàng và tinh tế.',
    composition: 'Hoa ly phối cùng hoa lá theo mùa. Màu và kích thước có thể thay đổi nhẹ theo từng đợt hoa.', featured: true, today: true
  },
  {
    id: 'p002', sku: 'IG-GARDEN-380', slug: 'garden', name: 'Garden', type: 'bouquet',
    price: 380000, image: 'assets/images/garden.svg',
    categories: ['Hoa ly'], tone: 'Hồng', occasion: 'Sinh nhật',
    description: 'Bó hoa mang cảm giác như một khu vườn nhỏ, mềm mại và tự nhiên.',
    composition: 'Hoa ly và hoa lá theo mùa. Màu hoa ly có thể đậm hoặc nhạt khác nhau tuỳ đợt hoa.', featured: true, today: true
  },
  {
    id: 'p003', sku: 'IG-HOA-LY-350', slug: 'hoa-ly', name: 'Hoa ly', type: 'bouquet',
    price: 350000, image: 'assets/images/hoa-ly.svg',
    categories: ['Hoa ly'], tone: 'Hồng', occasion: 'Chúc mừng',
    description: 'Bó hoa ly nữ tính, tươi sáng và dễ tặng trong nhiều dịp.',
    composition: 'Hoa ly phối lá. Sắc độ hoa thay đổi tự nhiên theo từng đợt.', featured: true, today: true
  },
  {
    id: 'p004', sku: 'IG-LY-XANH-370', slug: 'ly-xanh', name: 'Ly xanh', type: 'bouquet',
    price: 370000, image: 'assets/images/ly-xanh.svg',
    categories: ['Hoa ly', 'Xanh'], tone: 'Xanh', occasion: 'Kỷ niệm',
    description: 'Tông xanh mát và lạ mắt, thích hợp cho người yêu những gam màu dịu.',
    composition: 'Bó có khoảng 3–5 bông ly tuỳ kích thước hoa ở thời điểm thực hiện.', featured: true, today: true
  },
  {
    id: 'p005', sku: 'IG-LILY-310', slug: 'lily', name: 'Lily', type: 'bouquet',
    price: 310000, image: 'assets/images/lily.svg',
    categories: ['Lily', 'Xanh'], tone: 'Xanh', occasion: 'Sinh nhật',
    description: 'Mẫu lily trẻ trung với cách gói tối giản, giữ trọng tâm ở sắc hoa.',
    composition: 'Lily và hoa lá theo mùa.', featured: false, today: false
  },
  {
    id: 'p006', sku: 'IG-MOT-BO-HOA-390', slug: 'mot-bo-hoa-mot-lan-duoc-nho-den', name: 'Một bó hoa, một lần được nhớ đến', type: 'bouquet',
    price: 390000, image: 'assets/images/mot-bo-hoa.svg',
    categories: ['Hoa hồng'], tone: 'Đỏ', occasion: 'Tặng người thương',
    description: 'Sắc đỏ sâu và ấm dành cho một lời nhắn có chủ ý.',
    composition: 'Hoa hồng phối hoa lá theo mùa.', featured: false, today: false
  },
  {
    id: 'p007', sku: 'IG-CAM-TU-CAU-450', slug: 'cam-tu-cau', name: 'Cẩm tú cầu', type: 'bouquet',
    price: 450000, image: 'assets/images/cam-tu-cau.svg',
    categories: ['Cẩm tú cầu', 'Xanh'], tone: 'Xanh', occasion: 'Chúc mừng',
    description: 'Bó cẩm tú cầu đầy đặn, thanh lịch với tông xanh dịu.',
    composition: 'Cẩm tú cầu và lá phụ theo mùa.', featured: false, today: false
  },
  {
    id: 'p008', sku: 'IG-PHI-YEN-370', slug: 'phi-yen', name: 'Phi yến', type: 'bouquet',
    price: 370000, image: 'assets/images/phi-yen.svg',
    categories: ['Phi yến'], tone: 'Tím', occasion: 'Kỷ niệm',
    description: 'Phi yến tạo dáng cao, thanh thoát và có nhiều khoảng thở.',
    composition: 'Phi yến phối hoa lá theo mùa.', featured: false, today: false
  },
  {
    id: 'p009', sku: 'IG-SON-SAC-290', slug: 'son-sac-thuy-chung', name: 'Son sắc thuỷ chung', type: 'bouquet',
    price: 290000, image: 'assets/images/son-sac-thuy-chung.svg',
    categories: ['Hoa hồng'], tone: 'Tím', occasion: 'Tặng người thương',
    description: 'Một lựa chọn nhỏ gọn với sắc tím trầm, phù hợp cho những dịp giản dị.',
    composition: 'Hoa hồng phối hoa lá theo mùa.', featured: false, today: false
  }
];

window.CAS_FORMAT_VND = function (amount) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(amount);
};

window.CAS_FIND_PRODUCT = function (slugOrId) {
  return window.CAS_PRODUCTS.find(function (product) {
    return product.slug === slugOrId || product.id === slugOrId;
  });
};
