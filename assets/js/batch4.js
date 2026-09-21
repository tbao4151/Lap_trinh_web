(function () {
  'use strict';
  var articles = {
    'chon-hoa-theo-mau': {
      category:'Gợi ý chọn hoa', date:'18.09.2026', title:'Chọn hoa theo màu sắc và cảm xúc',
      image:'assets/images/lam-tinh.png',
      lead:'Màu sắc là một trong những yếu tố đầu tiên tạo cảm giác cho món quà. Không có một công thức cứng nhắc, nhưng mỗi gam màu thường gợi ra một nhịp cảm xúc khác nhau.',
      sections:[
        ['Tông xanh: nhẹ và bình yên','Xanh phù hợp khi bạn muốn món quà có cảm giác dịu, mát và ít phô trương. Có thể phối cùng lá hoặc hoa sáng màu để tổng thể có nhiều khoảng thở.'],
        ['Tông hồng: mềm mại và gần gũi','Hồng dễ dùng trong sinh nhật, lời cảm ơn hoặc những dịp cần sự ấm áp. Sắc hồng nhạt tạo cảm giác tinh tế, trong khi hồng đậm có điểm nhấn rõ hơn.'],
        ['Tông đỏ và tím: nhiều chiều sâu','Đỏ tạo cảm giác mạnh và trực diện hơn, còn tím thường có nét trầm, lạ và trưởng thành. Khi chọn hai tông này, nên cân nhắc tính cách người nhận và thông điệp trên thiệp.']
      ]
    },
    'viet-thiep-ngan': {
      category:'Thiệp hoa', date:'12.09.2026', title:'Viết gì trên một tấm thiệp ngắn?',
      image:'assets/images/garden.png',
      lead:'Thiệp đi cùng hoa không cần dài. Một câu đúng ngữ cảnh thường có giá trị hơn một đoạn quá cầu kỳ.',
      sections:[
        ['Bắt đầu từ dịp tặng','Sinh nhật, lời cảm ơn, chúc mừng hay một ngày bình thường sẽ quyết định giọng điệu của tấm thiệp. Hãy viết điều gần với cách bạn vẫn nói.'],
        ['Giữ một ý chính','Một tấm thiệp ngắn có thể chỉ gồm lời chúc, một câu cảm ơn hoặc một điều bạn muốn người nhận nhớ. Không cần cố gắng đưa quá nhiều ý vào cùng lúc.'],
        ['Ký tên nếu cần','Nếu hoa được giao thay bạn, việc ký tên hoặc để lại một dấu hiệu nhận biết giúp người nhận hiểu rõ món quà đến từ ai.']
      ]
    },
    'giu-hoa-tuoi-lau': {
      category:'Chăm hoa', date:'05.09.2026', title:'Giữ hoa tươi lâu hơn sau khi nhận',
      image:'assets/images/cam-tu-cau.png',
      lead:'Hoa tươi thay đổi theo môi trường. Một vài thao tác cơ bản sau khi nhận sẽ giúp hoa giữ trạng thái tốt hơn trong những ngày đầu.',
      sections:[
        ['Chuẩn bị bình sạch','Rửa bình và dùng nước sạch trước khi cắm. Phần lá nằm dưới mặt nước nên được loại bỏ để hạn chế nước nhanh bẩn.'],
        ['Thay nước định kỳ','Khi nước đục hoặc có mùi, hãy thay nước và vệ sinh lại bình. Có thể cắt lại một đoạn nhỏ ở gốc cành để hoa hút nước tốt hơn.'],
        ['Tránh nguồn nhiệt mạnh','Không đặt hoa sát bếp, dưới nắng gắt hoặc ngay luồng gió nóng. Một vị trí thoáng, mát và có ánh sáng dịu thường phù hợp hơn.']
      ]
    }
  };
  function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[c];});}
  document.addEventListener('DOMContentLoaded', function () {
    if (document.body.dataset.page !== 'article') return;
    var slug = new URLSearchParams(location.search).get('slug') || 'chon-hoa-theo-mau';
    var article = articles[slug] || articles['chon-hoa-theo-mau'];
    document.title = article.title + " | CÁ'S HOA";
    var host = document.getElementById('articleHost');
    host.innerHTML = '<div class="article-meta">'+esc(article.date)+' · '+esc(article.category)+'</div><h1 class="cas-title" style="max-width:820px">'+esc(article.title)+'</h1><p class="article-lead">'+esc(article.lead)+'</p><img class="article-cover" src="'+esc(article.image)+'" alt="'+esc(article.title)+'"><div class="article-body">'+article.sections.map(function(s){return '<section><h2>'+esc(s[0])+'</h2><p>'+esc(s[1])+'</p></section>';}).join('')+'</div><div class="d-flex flex-wrap gap-2 mt-4"><a href="tin-tuc.html" class="btn-cas-outline">Quay lại tin tức</a><a href="san-pham.html" class="btn-cas-primary">Xem hoa</a></div>';
  });
})();