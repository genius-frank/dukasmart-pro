<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Dashboard';
$s   = get_settings();
$cur = $s['currency'];

$today = date('Y-m-d');

$sum_today = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c, COALESCE(SUM(total),0) rev, COALESCE(SUM(profit),0) prof
     FROM sales WHERE DATE(created_at)='$today'"));

$sum_month = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c, COALESCE(SUM(total),0) rev
     FROM sales WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())"));

$prod_count      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM products WHERE is_active=1"))['c'];
$low             = low_stock_count();
$credit_owed     = total_credit_outstanding();
$credit_count_db = credit_customers_count();

// Last 7 days for chart
$chart_data = [];
$chart_r = mysqli_query($conn,
    "SELECT DATE(created_at) d, COALESCE(SUM(total),0) rev
     FROM sales WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)
     GROUP BY DATE(created_at) ORDER BY d ASC");
while ($row = mysqli_fetch_assoc($chart_r)) $chart_data[$row['d']] = $row['rev'];

// Recent sales
$recent = mysqli_query($conn,
    "SELECT s.*, u.full_name, c.name customer_name
     FROM sales s
     LEFT JOIN users u ON s.cashier_id=u.id
     LEFT JOIN customers c ON s.customer_id=c.id
     ORDER BY s.created_at DESC LIMIT 8");

require_once 'layout_header.php';
?>

<!-- Summary strip -->
<div class="summary-strip">
  <div class="summary-item success">
    <span class="summary-label">Sales today</span>
    <strong><?= $sum_today['c'] ?></strong>
  </div>
  <div class="summary-item primary">
    <span class="summary-label">Gross profit</span>
    <strong><?= $cur ?> <?= number_format($sum_today['prof'],2) ?></strong>
  </div>
  <div class="summary-item warning">
    <span class="summary-label">Low stock</span>
    <strong><?= $low ?></strong>
  </div>
  <div class="summary-item danger">
    <span class="summary-label">Credit due</span>
    <strong><?= $cur ?> <?= number_format($credit_owed,2) ?></strong>
  </div>
</div>

<!-- Stat cards -->
<div class="stats-grid">
  <div class="stat-card stat-card--featured">
    <div class="stat-icon green">💰</div>
    <div class="stat-info">
      <p>Today's Revenue</p>
      <h3><?= $cur ?> <?= number_format($sum_today['rev'],2) ?></h3>
      <small><?= $sum_today['c'] ?> sales today</small>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue">📈</div>
    <div class="stat-info">
      <p>Today's Profit</p>
      <h3><?= $cur ?> <?= number_format($sum_today['prof'],2) ?></h3>
      <small>Gross margin</small>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple">📆</div>
    <div class="stat-info">
      <p>This Month</p>
      <h3><?= $cur ?> <?= number_format($sum_month['rev'],2) ?></h3>
      <small><?= $sum_month['c'] ?> sales</small>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow">📦</div>
    <div class="stat-info">
      <p>Total Products</p>
      <h3><?= $prod_count ?></h3>
      <small><?php if ($low > 0): ?><span class="text-red">⚠️ <?= $low ?> low stock</span><?php else: ?>All stocked ✓<?php endif; ?></small>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red">📒</div>
    <div class="stat-info">
      <p>Credit Outstanding</p>
      <h3 class="<?= $credit_owed > 0 ? 'text-red' : '' ?>"><?= $cur ?> <?= number_format($credit_owed,2) ?></h3>
      <small><a href="customers.php" style="color:var(--accent);text-decoration:none;"><?= $credit_count_db ?> customer<?= $credit_count_db!=1?'s':'' ?> owing →</a></small>
    </div>
  </div>
</div>

<!-- Quick actions -->
<div class="quick-actions">
  <a href="pos.php" class="btn btn-success action-button">🛒 New Sale</a>
  <a href="add_product.php" class="btn btn-primary action-button">➕ Add Product</a>
  <a href="customers.php" class="btn btn-warning action-button">📒 Credit Book</a>
  <a href="daily_report.php" class="btn btn-ghost action-button">📊 Today's Report</a>
</div>

<!-- Chart + Recent sales -->
<div class="dashboard-panels">
  <!-- 7-day chart -->
  <div class="card chart-card">
    <div class="card-title">Revenue — Last 7 Days</div>
    <?php
    $max = max(array_values($chart_data) ?: [1]);
    for ($i = 6; $i >= 0; $i--):
      $d   = date('Y-m-d', strtotime("-$i days"));
      $val = $chart_data[$d] ?? 0;
      $pct = $max > 0 ? round(($val / $max) * 100) : 0;
      $lbl = date('D', strtotime($d));
      $isToday = ($d === $today);
    ?>
    <div class="chart-row">
      <span class="chart-day <?= $isToday ? 'today' : '' ?>"><?= $lbl ?></span>
      <div class="chart-bar-wrap">
        <div class="chart-bar <?= $isToday ? 'today' : '' ?>" style="width:<?= $pct ?>%;"></div>
      </div>
      <span class="chart-value"><?= $cur ?> <?= number_format($val,0) ?></span>
    </div>
    <?php endfor; ?>
  </div>

  <!-- Recent sales -->
  <div class="card">
    <div class="card-title">Recent Sales</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Time</th><th>Cashier</th><th>Payment</th><th>Total</th></tr></thead>
        <tbody>
          <?php if (mysqli_num_rows($recent) === 0): ?>
          <tr><td colspan="4" class="text-center text-muted" style="padding:30px;">No sales yet today</td></tr>
          <?php endif; ?>
          <?php while ($sale = mysqli_fetch_assoc($recent)): ?>
          <tr>
            <td style="font-size:12px;"><?= date('H:i', strtotime($sale['created_at'])) ?></td>
            <td style="font-size:12px;"><?= htmlspecialchars($sale['full_name'] ?? '—') ?></td>
            <td>
              <span class="pill <?= $sale['payment_method']==='mpesa'?'pill-green':($sale['payment_method']==='credit'?'pill-red':'pill-blue') ?>">
                <?= strtoupper($sale['payment_method']) ?>
              </span>
            </td>
            <td class="fw-bold" style="font-size:13px;"><?= $cur ?> <?= number_format($sale['total'],2) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <a href="sales_history.php" class="btn btn-ghost btn-sm mt-2">View All Sales →</a>
  </div>
</div>

<?php require_once 'layout_footer.php'; ?>
