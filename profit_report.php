<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Profit Report';
$s = get_settings();
$cur = $s['currency'];

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

$sum = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) rev, COALESCE(SUM(profit),0) gross_profit, COUNT(*) c FROM sales WHERE DATE(created_at) BETWEEN '$from' AND '$to'"));
$expenses_sum = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) total FROM expenses WHERE DATE(created_at) BETWEEN '$from' AND '$to'"));
$net_profit = $sum['gross_profit'] - $expenses_sum['total'];
$margin = $sum['rev'] > 0 ? round(($sum['gross_profit'] / $sum['rev']) * 100, 1) : 0;

// Profit by product
$by_product = mysqli_query($conn, "SELECT si.product_name, SUM(si.quantity_sold) qty, SUM(si.total) rev, SUM(si.profit) prof
  FROM sale_items si JOIN sales s ON si.sale_id=s.id
  WHERE DATE(s.created_at) BETWEEN '$from' AND '$to'
  GROUP BY si.product_name ORDER BY prof DESC LIMIT 10");

require_once 'layout_header.php';
?>

<div class="card" style="padding:16px;margin-bottom:16px;">
  <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
    <div class="form-group" style="margin:0;">
      <label>From</label>
      <input type="date" name="from" value="<?= $from ?>">
    </div>
    <div class="form-group" style="margin:0;">
      <label>To</label>
      <input type="date" name="to" value="<?= $to ?>">
    </div>
    <button type="submit" class="btn btn-primary">Apply</button>
    <a href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>" class="btn btn-ghost">This Month</a>
    <a href="?from=<?= date('Y-01-01') ?>&to=<?= date('Y-m-d') ?>" class="btn btn-ghost">This Year</a>
  </form>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue">💵</div>
    <div class="stat-info"><p>Revenue</p><h3><?= $cur ?> <?= number_format($sum['rev'],2) ?></h3></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">📈</div>
    <div class="stat-info"><p>Gross Profit</p><h3><?= $cur ?> <?= number_format($sum['gross_profit'],2) ?></h3>
      <small class="text-muted"><?= $margin ?>% margin</small>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red">💸</div>
    <div class="stat-info"><p>Expenses</p><h3><?= $cur ?> <?= number_format($expenses_sum['total'],2) ?></h3></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple">🏆</div>
    <div class="stat-info">
      <p>Net Profit</p>
      <h3 class="<?= $net_profit >= 0 ? 'text-green' : 'text-red' ?>"><?= $cur ?> <?= number_format($net_profit,2) ?></h3>
      <small class="text-muted">After expenses</small>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-title">Most Profitable Products</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Product</th><th>Units Sold</th><th>Revenue</th><th>Profit</th><th>Margin</th></tr></thead>
      <tbody>
        <?php if (mysqli_num_rows($by_product) === 0): ?>
        <tr><td colspan="6" class="text-center text-muted" style="padding:30px;">No data for this period</td></tr>
        <?php endif; ?>
        <?php $i=1; while ($p = mysqli_fetch_assoc($by_product)):
          $pm = $p['rev'] > 0 ? round(($p['prof']/$p['rev'])*100,1) : 0;
        ?>
        <tr>
          <td class="text-muted"><?= $i++ ?></td>
          <td class="fw-bold"><?= htmlspecialchars($p['product_name']) ?></td>
          <td><?= $p['qty'] ?></td>
          <td><?= $cur ?> <?= number_format($p['rev'],2) ?></td>
          <td class="fw-bold text-green"><?= $cur ?> <?= number_format($p['prof'],2) ?></td>
          <td><span class="pill <?= $pm >= 20 ? 'pill-green' : 'pill-yellow' ?>"><?= $pm ?>%</span></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'layout_footer.php';
