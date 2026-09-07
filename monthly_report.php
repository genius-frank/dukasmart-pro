<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Monthly Report';
$s = get_settings();
$cur = $s['currency'];

$month = (int)($_GET['month'] ?? date('n'));
$year  = (int)($_GET['year'] ?? date('Y'));
$prev_m = $month == 1 ? 12 : $month - 1;
$prev_y = $month == 1 ? $year - 1 : $year;
$next_m = $month == 12 ? 1 : $month + 1;
$next_y = $month == 12 ? $year + 1 : $year;
$is_current = ($month == date('n') && $year == date('Y'));

$sum = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c, COALESCE(SUM(total),0) rev, COALESCE(SUM(profit),0) prof FROM sales WHERE MONTH(created_at)=$month AND YEAR(created_at)=$year"));
$prev_sum = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) rev FROM sales WHERE MONTH(created_at)=$prev_m AND YEAR(created_at)=$prev_y"));

$change_pct = $prev_sum['rev'] > 0 ? round((($sum['rev'] - $prev_sum['rev']) / $prev_sum['rev']) * 100, 1) : 0;
$avg_sale = $sum['c'] > 0 ? $sum['rev'] / $sum['c'] : 0;

// Daily breakdown
$daily = mysqli_query($conn, "SELECT DATE(created_at) d, SUM(total) rev FROM sales WHERE MONTH(created_at)=$month AND YEAR(created_at)=$year GROUP BY DATE(created_at)");
$daily_map = [];
while ($row = mysqli_fetch_assoc($daily)) $daily_map[$row['d']] = $row['rev'];

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$max_day_rev = max(array_values($daily_map) ?: [1]);

// Top products
$top = mysqli_query($conn, "SELECT si.product_name, SUM(si.quantity_sold) qty, SUM(si.total) rev
  FROM sale_items si JOIN sales s ON si.sale_id=s.id
  WHERE MONTH(s.created_at)=$month AND YEAR(s.created_at)=$year
  GROUP BY si.product_name ORDER BY rev DESC LIMIT 10");

require_once 'layout_header.php';
$month_name = date('F Y', mktime(0,0,0,$month,1,$year));
?>

<div class="card" style="padding:14px 20px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
  <a href="?month=<?= $prev_m ?>&year=<?= $prev_y ?>" class="btn btn-ghost btn-sm">← Previous Month</a>
  <h3 style="font-size:16px;"><?= $month_name ?></h3>
  <a href="?month=<?= $next_m ?>&year=<?= $next_y ?>" class="btn btn-ghost btn-sm" <?= $is_current ? 'style="opacity:0.4;pointer-events:none;"' : '' ?>>Next Month →</a>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue">🧾</div>
    <div class="stat-info"><p>Total Sales</p><h3><?= $sum['c'] ?></h3></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">💰</div>
    <div class="stat-info">
      <p>Revenue</p><h3><?= $cur ?> <?= number_format($sum['rev'],2) ?></h3>
      <small class="<?= $change_pct >= 0 ? 'text-green' : 'text-red' ?>">
        <?= $change_pct >= 0 ? '↑' : '↓' ?> <?= abs($change_pct) ?>% vs last month
      </small>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple">📈</div>
    <div class="stat-info"><p>Total Profit</p><h3><?= $cur ?> <?= number_format($sum['prof'],2) ?></h3></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow">🎯</div>
    <div class="stat-info"><p>Avg Sale Value</p><h3><?= $cur ?> <?= number_format($avg_sale,2) ?></h3></div>
  </div>
</div>

<div class="card">
  <div class="card-title">Daily Revenue — <?= $month_name ?></div>
  <div style="max-height:340px;overflow-y:auto;">
    <?php for ($d = 1; $d <= $days_in_month; $d++):
      $date_key = sprintf('%04d-%02d-%02d', $year, $month, $d);
      $val = $daily_map[$date_key] ?? 0;
      $pct = $max_day_rev > 0 ? round(($val / $max_day_rev) * 100) : 0;
      $is_today = $date_key === date('Y-m-d');
    ?>
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
      <span style="width:28px;font-size:11px;color:var(--muted);text-align:right;<?= $is_today ? 'font-weight:700;color:var(--primary);' : '' ?>"><?= $d ?></span>
      <div style="flex:1;background:#f1f5f9;border-radius:6px;height:18px;overflow:hidden;">
        <div style="width:<?= $pct ?>%;background:<?= $is_today ? 'var(--accent)' : 'var(--primary)' ?>;height:100%;border-radius:6px;"></div>
      </div>
      <span style="width:90px;font-size:11px;font-weight:600;"><?= $cur ?> <?= number_format($val,0) ?></span>
    </div>
    <?php endfor; ?>
  </div>
</div>

<div class="card mt-2">
  <div class="card-title">Top 10 Products This Month</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Product</th><th>Units Sold</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php if (mysqli_num_rows($top) === 0): ?>
        <tr><td colspan="4" class="text-center text-muted" style="padding:30px;">No sales this month yet</td></tr>
        <?php endif; ?>
        <?php $i=1; while ($p = mysqli_fetch_assoc($top)): ?>
        <tr>
          <td class="text-muted"><?= $i++ ?></td>
          <td class="fw-bold"><?= htmlspecialchars($p['product_name']) ?></td>
          <td><?= $p['qty'] ?></td>
          <td class="fw-bold text-green"><?= $cur ?> <?= number_format($p['rev'],2) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'layout_footer.php';
