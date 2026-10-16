<?php
declare(strict_types=1);
require __DIR__.'/_ops.php';
cas_page_roles(['admin','owner'],'Biểu đồ lợi nhuận gộp');
$from=(string)($_GET['from']??date('Y-m-d',strtotime('-29 days')));
$to=(string)($_GET['to']??date('Y-m-d'));
$group=(string)($_GET['group']??'day');
$validDate=static function(string $date):bool {
    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date))return false;
    $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
    return $parsed!==false&&$parsed->format('Y-m-d')===$date;
};
if(!$validDate($from)||!$validDate($to)||$from>$to||((strtotime($to)-strtotime($from))/86400)>366){
    $from=date('Y-m-d',strtotime('-29 days'));$to=date('Y-m-d');
}
if(!in_array($group,['day','month'],true))$group='day';
$format=$group==='month'?'%Y-%m':'%Y-%m-%d';
// Cost snapshot is frozen when preparing the order, NEVER based on today's material prices.
// Legacy orders without a complete cost snapshot are excluded from profit, but counted.
$sql="SELECT DATE_FORMAT(o.created_at, ?) bucket,
 COUNT(*) order_count,
 COALESCE(SUM(GREATEST(o.total_amount-COALESCE(r.refunded,0),0)),0) net_all,
 COALESCE(SUM(COALESCE(r.refunded,0)),0) refunded,
 COALESCE(SUM(CASE WHEN u.material_count>0 AND u.missing_cost=0 THEN GREATEST(o.total_amount-COALESCE(r.refunded,0),0) ELSE 0 END),0) known_revenue,
 COALESCE(SUM(CASE WHEN u.material_count>0 AND u.missing_cost=0 THEN u.cost_total ELSE 0 END),0) material_cost,
 COALESCE(SUM(CASE WHEN u.material_count>0 AND u.missing_cost=0 THEN GREATEST(o.total_amount-COALESCE(r.refunded,0),0)-u.cost_total ELSE 0 END),0) gross_profit,
 COALESCE(SUM(CASE WHEN u.material_count IS NULL OR u.missing_cost>0 THEN 1 ELSE 0 END),0) unknown_count
 FROM orders o
 LEFT JOIN (SELECT order_id,SUM(amount) refunded FROM refunds WHERE status='recorded' GROUP BY order_id) r ON r.order_id=o.order_id
 LEFT JOIN (SELECT order_id,COUNT(*) material_count,
   SUM(CASE WHEN cost_amount_snapshot IS NULL THEN 1 ELSE 0 END) missing_cost,
   SUM(COALESCE(cost_amount_snapshot,0)) cost_total
   FROM order_inventory_usage WHERE restored_at IS NULL GROUP BY order_id) u ON u.order_id=o.order_id
 WHERE o.order_status='completed' AND o.payment_status IN ('paid','partially_refunded','refunded')
 AND o.created_at >= ? AND o.created_at < DATE_ADD(?,INTERVAL 1 DAY)
 GROUP BY DATE_FORMAT(o.created_at, ?) ORDER BY bucket ASC";
$q=$db->prepare($sql);$q->execute([$format,$from,$to,$format]);$rows=$q->fetchAll();
$totals=['orders'=>0,'net_all'=>0.0,'refunded'=>0.0,'known_revenue'=>0.0,'material_cost'=>0.0,'gross_profit'=>0.0,'unknown_count'=>0];
foreach($rows as $r)foreach($totals as $k=>$v)$totals[$k]+=(float)$r[$k];
$margin=$totals['known_revenue']>0 ? $totals['gross_profit']/$totals['known_revenue']*100 : 0;
$points=count($rows);
$xFor=static fn(int $i):float=> $points<2?500:70+900*$i/($points-1);
$all=[];foreach($rows as $r){$all[]=(float)$r['known_revenue'];$all[]=(float)$r['material_cost'];$all[]=(float)$r['gross_profit'];}
$yBottom=min(0,...($all?:[0]));$yTop=max(1,...($all?:[1]));
if($yBottom===$yTop)$yTop=$yBottom+1;
$yFor=static fn(float $v):float=> 285-225*($v-$yBottom)/($yTop-$yBottom);
$series=['known_revenue'=>['name'=>'Doanh thu có giá vốn','color'=>'#52765b'],
 'material_cost'=>['name'=>'Giá vốn nguyên liệu','color'=>'#d2a154'],
 'gross_profit'=>['name'=>'Lợi nhuận gộp','color'=>'#365b8c']];
cas_ops_start('Biểu đồ lợi nhuận gộp','Phân tích kết quả các đơn đã hoàn tất và đã thanh toán bằng dữ liệu kế toán có thể kiểm chứng.','bi-graph-up-arrow');
?>
<style>
.cas-profit-metrics{display:grid;grid-template-columns:repeat(auto-fit,minmax(185px,1fr));gap:12px;margin:14px 0 22px}
.cas-profit-metric{border:1px solid #e2e6dc;border-radius:12px;padding:14px;background:#fff}
.cas-profit-metric strong{display:block;font-size:1.32rem;margin-top:8px}
.cas-profit-legend{display:flex;flex-wrap:wrap;gap:14px;font-size:.85rem;margin:12px 0}
.cas-profit-swatch{display:inline-block;width:12px;height:12px;border-radius:50%;margin-right:6px}
.cas-profit-chart{width:100%;height:auto;min-height:240px}
.cas-profit-note{color:#555e55;line-height:1.65;font-size:.9rem}
</style>
<section class="admin-card admin-section">
<form method="get" class="cas-admin-stack mb-3">
<label for="from">Từ ngày</label><input id="from" name="from" type="date" required class="admin-input" value="<?= cas_e($from) ?>">
<label for="to">Đến ngày</label><input id="to" name="to" type="date" required class="admin-input" value="<?= cas_e($to) ?>">
<label for="group">Nhóm theo</label><select id="group" class="admin-select" name="group">
<option value="day" <?= $group==='day'?'selected':'' ?>>Ngày</option><option value="month" <?= $group==='month'?'selected':'' ?>>Tháng</option>
</select><button class="admin-btn" type="submit">Xem báo cáo</button></form>
<p class="cas-profit-note">Chỉ tính đơn <strong>hoàn tất và đã thanh toán</strong>; số tiền hoàn đã ghi nhận được trừ khỏi doanh thu. Những đơn không có snapshot giá vốn nguyên liệu không được tính vào lợi nhuận.</p>
<div class="cas-profit-metrics">
<div class="cas-profit-metric">Số đơn hoàn tất / đã thanh toán<strong><?= (int)$totals['orders'] ?></strong></div>
<div class="cas-profit-metric">Doanh thu thuần toàn bộ<strong><?= cas_money($totals['net_all']) ?></strong></div>
<div class="cas-profit-metric">Số tiền đã hoàn<strong><?= cas_money($totals['refunded']) ?></strong></div>
<div class="cas-profit-metric">Doanh thu có đủ giá vốn<strong><?= cas_money($totals['known_revenue']) ?></strong></div>
<div class="cas-profit-metric">Giá vốn nguyên liệu<strong><?= cas_money($totals['material_cost']) ?></strong></div>
<div class="cas-profit-metric">Lợi nhuận gộp (đủ giá vốn)<strong><?= cas_money($totals['gross_profit']) ?></strong></div>
<div class="cas-profit-metric">Biên lợi nhuận gộp<strong><?= number_format($margin,1,',','.') ?>%</strong></div>
<div class="cas-profit-metric">Đơn chưa đủ giá vốn<strong><?= (int)$totals['unknown_count'] ?></strong></div>
</div>
</section>
<section class="admin-card admin-section">
<h2 class="admin-card-title">Xu hướng doanh thu, giá vốn và lợi nhuận gộp</h2>
<div class="cas-profit-legend"><?php foreach($series as $data): ?><span><i class="cas-profit-swatch" style="background:<?= $data['color'] ?>"></i><?= cas_e($data['name']) ?></span><?php endforeach; ?></div>
<?php if(!$rows): ?><p>Chưa có đơn phù hợp trong khoảng thời gian này.</p><?php else: ?>
<div class="admin-table-wrap">
<svg class="cas-profit-chart" role="img" aria-label="Biểu đồ đường thể hiện doanh thu, giá vốn nguyên liệu và lợi nhuận gộp theo <?= $group==='month'?'tháng':'ngày' ?>" viewBox="0 0 1000 335" xmlns="http://www.w3.org/2000/svg">
<?php for($i=0;$i<=4;$i++):$val=$yBottom+($yTop-$yBottom)*$i/4;$y=$yFor($val); ?>
<line x1="70" y1="<?= $y ?>" x2="970" y2="<?= $y ?>" stroke="#dfe4db" stroke-width="1"/>
<text x="62" y="<?= $y+4 ?>" text-anchor="end" font-size="12" fill="#607066"><?= cas_e(number_format($val/1000000,1,',','.')) ?>tr</text>
<?php endfor; ?>
<?php foreach($series as $key=>$meta):
  $coordinates=[];foreach($rows as $i=>$r)$coordinates[]=number_format($xFor($i),1,'.','').','.number_format($yFor((float)$r[$key]),1,'.','');
?>
<polyline fill="none" stroke="<?= $meta['color'] ?>" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="<?= implode(' ',$coordinates) ?>"/>
<?php foreach($rows as $i=>$r): ?><circle cx="<?= $xFor($i) ?>" cy="<?= $yFor((float)$r[$key]) ?>" r="4" fill="<?= $meta['color'] ?>"/>
<?php endforeach; ?>
<?php endforeach; ?>
<?php foreach($rows as $i=>$r): if($i%max(1,(int)ceil(count($rows)/9))!==0 && $i!==count($rows)-1)continue; ?>
<text x="<?= $xFor($i) ?>" y="313" font-size="12" text-anchor="middle" fill="#607066"><?= cas_e(substr((string)$r['bucket'],$group==='month'?0:5)) ?></text>
<?php endforeach; ?>
</svg></div>
<?php endif; ?>
</section>
<section class="admin-card admin-section">
<h2 class="admin-card-title">Đối chiếu số liệu theo <?= $group==='month'?'tháng':'ngày' ?></h2>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Kỳ</th><th>Đơn</th><th>Doanh thu có đủ giá vốn</th><th>Giá vốn</th><th>Lợi nhuận gộp</th><th>Thiếu giá vốn</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= cas_e($r['bucket']) ?></td><td><?= (int)$r['order_count'] ?></td><td><?= cas_money($r['known_revenue']) ?></td><td><?= cas_money($r['material_cost']) ?></td><td><?= cas_money($r['gross_profit']) ?></td><td><?= (int)$r['unknown_count'] ?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="6">Không có dữ liệu phù hợp.</td></tr><?php endif; ?>
</tbody></table></div>
<p class="cas-profit-note mt-3"><strong>Công thức:</strong> doanh thu thuần = tiền đơn đã hoàn tất − tiền hoàn đã ghi nhận; giá vốn nguyên liệu = lượng nguyên liệu thực xuất × đơn giá tại thời điểm xuất (được lưu trong snapshot); lợi nhuận gộp = doanh thu thuần có giá vốn − giá vốn nguyên liệu; biên gộp = lợi nhuận gộp / doanh thu tương ứng. Đây <strong>không phải lợi nhuận ròng</strong>: chưa khấu trừ tiền lương, thuê mặt bằng, chi phí giao thực tế, điện nước và các chi phí vận hành khác. Số liệu nhóm theo ngày <em>tạo đơn</em>, có thể thay đổi khi đơn được hoàn tất hoặc hoàn tiền sau đó.</p>
</section>
<?php cas_ops_end(); ?>
