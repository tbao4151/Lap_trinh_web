<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && str_replace('\\', '/', strtolower((string)realpath($_SERVER['SCRIPT_FILENAME'] ?? ''))) === str_replace('\\', '/', strtolower((string)realpath(__FILE__)))) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Gross profit and cost-snapshot analytics module (admin/loi-nhuan.php).
 *
 * ĐỊNH NGHĨA DOANH THU DÙNG CHUNG cho Dashboard, Báo cáo doanh thu và Lợi nhuận gộp:
 *  - chỉ đơn order_status='completed' VÀ payment_status IN ('paid','partially_refunded','refunded');
 *    đơn chưa thu / mới cọc / chưa hoàn tất / đã hủy không phải doanh thu;
 *  - tiền đã hoàn = tổng refunds.status='recorded' của đơn, được GỘP THEO ĐƠN trước khi JOIN
 *    nên nhiều lần hoàn của cùng một đơn không nhân đôi tiền đơn;
 *  - doanh thu thuần (sau hoàn) = GREATEST(total_amount − tiền đã hoàn, 0): hoàn đủ = 0, không âm;
 *  - yêu cầu hoàn đang chờ duyệt / đã duyệt / bị từ chối KHÔNG trừ doanh thu cho tới khi được ghi nhận.
 */
const CAS_REVENUE_NET_LABEL = 'Doanh thu thuần (sau hoàn)';

function cas_revenue_eligible_sql(string $order = 'o'): string
{
    return "$order.order_status='completed' AND $order.payment_status IN ('paid','partially_refunded','refunded')";
}

function cas_revenue_refund_join(string $order = 'o', string $alias = 'r'): string
{
    return "LEFT JOIN (SELECT order_id,SUM(amount) refunded FROM refunds WHERE status='recorded' GROUP BY order_id) $alias ON $alias.order_id=$order.order_id";
}

function cas_revenue_net_sql(string $order = 'o', string $alias = 'r'): string
{
    return "GREATEST($order.total_amount-COALESCE($alias.refunded,0),0)";
}

/** Tổng doanh thu thuần của các đơn hợp lệ (toàn thời gian, hoặc theo ngày tạo đơn khi truyền $from/$to dạng Y-m-d). */
function cas_revenue_net_total(PDO $db, ?string $from = null, ?string $to = null): float
{
    $sql = 'SELECT COALESCE(SUM(' . cas_revenue_net_sql() . '),0) FROM orders o ' . cas_revenue_refund_join() . ' WHERE ' . cas_revenue_eligible_sql();
    $args = [];
    if ($from !== null && $to !== null) {
        $sql .= ' AND o.created_at >= ? AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)';
        $args = [$from, $to];
    }
    $q = $db->prepare($sql);
    $q->execute($args);
    return (float)$q->fetchColumn();
}

function cas_profit_page(): void
{
    global $db;
    cas_page_roles(['admin', 'owner'], 'Biểu đồ lợi nhuận gộp');
    $from = (string)($_GET['from'] ?? date('Y-m-d', strtotime('-29 days')));
    $to = (string)($_GET['to'] ?? date('Y-m-d'));
    $group = (string)($_GET['group'] ?? 'day');
    $validDate = static function (string $date): bool {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)) {
            return false;
        }
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    };
    if (!$validDate($from) || !$validDate($to) || $from > $to || ((strtotime($to) - strtotime($from)) / 86400) > 366) {
        $from = date('Y-m-d', strtotime('-29 days'));
        $to = date('Y-m-d');
    }
    if (!in_array($group, ['day', 'month'], true)) {
        $group = 'day';
    }
    $format = $group === 'month' ? '%Y-%m' : '%Y-%m-%d';
    // Cost snapshot is frozen when preparing the order, NEVER based on today's material prices.
    // Legacy orders without a complete cost snapshot are excluded from profit, but counted.
    $net = cas_revenue_net_sql();
    $sql = "SELECT DATE_FORMAT(o.created_at, '$format') bucket,
 COUNT(*) order_count,
 COALESCE(SUM($net),0) net_all,
 COALESCE(SUM(COALESCE(r.refunded,0)),0) refunded,
 COALESCE(SUM(CASE WHEN u.material_count>0 AND u.missing_cost=0 THEN $net ELSE 0 END),0) known_revenue,
 COALESCE(SUM(CASE WHEN u.material_count>0 AND u.missing_cost=0 THEN u.cost_total ELSE 0 END),0) material_cost,
 COALESCE(SUM(CASE WHEN u.material_count>0 AND u.missing_cost=0 THEN $net-u.cost_total ELSE 0 END),0) gross_profit,
 COALESCE(SUM(CASE WHEN u.material_count IS NULL OR u.missing_cost>0 THEN 1 ELSE 0 END),0) unknown_count
 FROM orders o
 " . cas_revenue_refund_join() . "
 LEFT JOIN (SELECT order_id,COUNT(*) material_count,
   SUM(CASE WHEN cost_amount_snapshot IS NULL THEN 1 ELSE 0 END) missing_cost,
   SUM(COALESCE(cost_amount_snapshot,0)) cost_total
   FROM order_inventory_usage WHERE restored_at IS NULL GROUP BY order_id) u ON u.order_id=o.order_id
 WHERE " . cas_revenue_eligible_sql() . "
 AND o.created_at >= ? AND o.created_at < DATE_ADD(?,INTERVAL 1 DAY)
 GROUP BY DATE_FORMAT(o.created_at, '$format') ORDER BY bucket ASC";
    $q = $db->prepare($sql);
    $q->execute([$from, $to]);
    $rows = $q->fetchAll();
    // Khóa trùng tên cột SQL (order_count): trước đây dùng 'orders' nên ô "Số đơn" luôn bằng 0 và PHP báo Undefined array key.
    $totals = ['order_count' => 0, 'net_all' => 0.0, 'refunded' => 0.0, 'known_revenue' => 0.0, 'material_cost' => 0.0, 'gross_profit' => 0.0, 'unknown_count' => 0];
    foreach ($rows as $r) {
        foreach ($totals as $k => $v) {
            $totals[$k] += (float)$r[$k];
        }
    }
    $margin = $totals['known_revenue'] > 0 ? $totals['gross_profit'] / $totals['known_revenue'] * 100 : 0;
    $points = count($rows);
    $xFor = static fn(int $i): float => $points < 2 ? 500 : 70 + 900 * $i / ($points - 1);
    $all = [];
    foreach ($rows as $r) {
        $all[] = (float)$r['known_revenue'];
        $all[] = (float)$r['material_cost'];
        $all[] = (float)$r['gross_profit'];
    }
    $yBottom = min(0, ...($all ?: [0]));
    $yTop = max(1, ...($all ?: [1]));
    if ($yBottom === $yTop) {
        $yTop = $yBottom + 1;
    }
    $yFor = static fn(float $v): float => 285 - 225 * ($v - $yBottom) / ($yTop - $yBottom);
    $series = [
        'known_revenue' => ['name' => 'Doanh thu có giá vốn', 'color' => '#52765b'],
        'material_cost' => ['name' => 'Giá vốn nguyên liệu', 'color' => '#d2a154'],
        'gross_profit' => ['name' => 'Lợi nhuận gộp', 'color' => '#365b8c'],
    ];
    cas_ops_start('Biểu đồ lợi nhuận gộp', 'Phân tích kết quả các đơn đã hoàn tất và đã thanh toán bằng dữ liệu kế toán có thể kiểm chứng.', 'bi-graph-up-arrow');
    ?>
<section class="admin-card admin-section">
<form method="get" class="cas-admin-stack mb-3">
<label class="admin-label mb-0" for="from">Từ ngày</label><input id="from" name="from" type="date" required class="admin-input" value="<?= cas_e($from) ?>">
<label class="admin-label mb-0" for="to">Đến ngày</label><input id="to" name="to" type="date" required class="admin-input" value="<?= cas_e($to) ?>">
<label class="admin-label mb-0" for="group">Nhóm theo</label><select id="group" class="admin-select" name="group">
<option value="day" <?= $group === 'day' ? 'selected' : '' ?>>Ngày</option><option value="month" <?= $group === 'month' ? 'selected' : '' ?>>Tháng</option>
</select><button class="admin-btn" type="submit">Xem báo cáo</button></form>
<p class="cas-profit-note">Chỉ tính đơn <strong>hoàn tất và đã thanh toán</strong>; số tiền hoàn đã ghi nhận được trừ khỏi doanh thu. Những đơn không có snapshot giá vốn nguyên liệu không được tính vào lợi nhuận.</p>
<div class="cas-profit-metrics">
<div class="cas-profit-metric">Số đơn hoàn tất / đã thanh toán<strong><?= (int)$totals['order_count'] ?></strong></div>
<div class="cas-profit-metric"><?= cas_e(CAS_REVENUE_NET_LABEL) ?><strong><?= cas_money($totals['net_all']) ?></strong></div>
<div class="cas-profit-metric">Số tiền đã hoàn<strong><?= cas_money($totals['refunded']) ?></strong></div>
<div class="cas-profit-metric">Doanh thu có đủ giá vốn<strong><?= cas_money($totals['known_revenue']) ?></strong></div>
<div class="cas-profit-metric">Giá vốn nguyên liệu<strong><?= cas_money($totals['material_cost']) ?></strong></div>
<div class="cas-profit-metric">Lợi nhuận gộp (đủ giá vốn)<strong><?= cas_money($totals['gross_profit']) ?></strong></div>
<div class="cas-profit-metric">Biên lợi nhuận gộp<strong><?= number_format($margin, 1, ',', '.') ?>%</strong></div>
<div class="cas-profit-metric">Đơn chưa đủ giá vốn<strong><?= (int)$totals['unknown_count'] ?></strong></div>
</div>
</section>
<section class="admin-card admin-section">
<h2 class="admin-card-title">Xu hướng doanh thu, giá vốn và lợi nhuận gộp</h2>
<div class="cas-profit-legend"><?php foreach ($series as $data): ?><span><i class="cas-profit-swatch" style="background:<?= $data['color'] ?>"></i><?= cas_e($data['name']) ?></span><?php endforeach; ?></div>
<?php if (!$rows): ?><div class="admin-empty mt-3">Chưa có đơn phù hợp trong khoảng thời gian này.</div><?php else: ?>
<div class="admin-table-wrap">
<svg class="cas-profit-chart" role="img" aria-label="Biểu đồ đường thể hiện doanh thu, giá vốn nguyên liệu và lợi nhuận gộp theo <?= $group === 'month' ? 'tháng' : 'ngày' ?>" viewBox="0 0 1000 335" xmlns="http://www.w3.org/2000/svg">
<?php for ($i = 0; $i <= 4; $i++): $val = $yBottom + ($yTop - $yBottom) * $i / 4; $y = $yFor($val); ?>
<line x1="70" y1="<?= $y ?>" x2="970" y2="<?= $y ?>" stroke="#dfe4db" stroke-width="1"/>
<text x="62" y="<?= $y + 4 ?>" text-anchor="end" font-size="12" fill="#607066"><?= cas_e(number_format($val / 1000000, 1, ',', '.')) ?>tr</text>
<?php endfor; ?>
<?php foreach ($series as $key => $meta):
    $coordinates = [];
    foreach ($rows as $i => $r) {
        $coordinates[] = number_format($xFor($i), 1, '.', '') . ',' . number_format($yFor((float)$r[$key]), 1, '.', '');
    }
    ?>
<polyline fill="none" stroke="<?= $meta['color'] ?>" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="<?= implode(' ', $coordinates) ?>"/>
<?php foreach ($rows as $i => $r): ?><circle cx="<?= $xFor($i) ?>" cy="<?= $yFor((float)$r[$key]) ?>" r="4" fill="<?= $meta['color'] ?>"/>
<?php endforeach; ?>
<?php endforeach; ?>
<?php foreach ($rows as $i => $r): if ($i % max(1, (int)ceil(count($rows) / 9)) !== 0 && $i !== count($rows) - 1) {
        continue;
    } ?>
<text x="<?= $xFor($i) ?>" y="313" font-size="12" text-anchor="middle" fill="#607066"><?= cas_e(substr((string)$r['bucket'], $group === 'month' ? 0 : 5)) ?></text>
<?php endforeach; ?>
</svg></div>
<?php endif; ?>
</section>
<section class="admin-card admin-section">
<h2 class="admin-card-title">Đối chiếu số liệu theo <?= $group === 'month' ? 'tháng' : 'ngày' ?></h2>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Kỳ</th><th>Đơn</th><th>Doanh thu có đủ giá vốn</th><th>Giá vốn</th><th>Lợi nhuận gộp</th><th>Thiếu giá vốn</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= cas_e($r['bucket']) ?></td><td><?= (int)$r['order_count'] ?></td><td><?= cas_money($r['known_revenue']) ?></td><td><?= cas_money($r['material_cost']) ?></td><td><?= cas_money($r['gross_profit']) ?></td><td><?= (int)$r['unknown_count'] ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6">Không có dữ liệu phù hợp.</td></tr><?php endif; ?>
</tbody></table></div>
<p class="cas-profit-note mt-3"><strong>Công thức:</strong> doanh thu thuần = tiền đơn đã hoàn tất − tiền hoàn đã ghi nhận; giá vốn nguyên liệu = lượng nguyên liệu thực xuất × đơn giá tại thời điểm xuất (được lưu trong snapshot); lợi nhuận gộp = doanh thu thuần có giá vốn − giá vốn nguyên liệu; biên gộp = lợi nhuận gộp / doanh thu tương ứng. Đây <strong>không phải lợi nhuận ròng</strong>: chưa khấu trừ tiền lương, thuê mặt bằng, chi phí giao thực tế, điện nước và các chi phí vận hành khác. Số liệu nhóm theo ngày <em>tạo đơn</em>, có thể thay đổi khi đơn được hoàn tất hoặc hoàn tiền sau đó.</p>
</section>
<?php
    cas_ops_end();
}
