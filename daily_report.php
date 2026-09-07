<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Daily Report';
$s = get_settings();
$cur = $s['currency'];

$date = $_GET['date'] ?? date('Y-m-d');
$prev = date('Y-m-d', strtotime($date . ' -1 day'));
$next = date('Y-m-d', strtotime($date . ' +1 day'));

// Summary
$sum = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c, COALESCE(SUM(total),0) rev, COALESCE(SUM(profit),0) prof, COALESCE(SUM(tax),0) tax FROM sales WHERE DATE(created_at)='$date'"));

// By payment method
$by_payment = mysqli_query($conn, "SELECT payment_method, COUNT(*) c, COALESCE(SUM(total),0) rev FROM sales WHERE DATE(created_at)='$date' GROUP BY payment_method");

// Top products that day
$top_products = mysqli_query($conn, "SELECT si.product_name, SUM(si.quantity_sold) qty, SUM(si.total) rev, SUM(si.profit) prof
  FROM sale_items si JOIN sales s ON si.sale_id=s.id
  WHERE DATE(s.created_at)='$date'
  GROUP BY si.product_name ORDER BY rev DESC LIMIT 5");

// Transactions
$txns = mysqli_query($conn, "SELECT s.*, u.full_name FROM sales s LEFT JOIN users u ON s.cashier_id=u.id WHERE DATE(s.created_at)='$date' ORDER BY s.created_at DESC");

require_once 'layout_header.php';
?>

<!-- Date nav -->
<div class="card" style="padding:14px 20px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
  <a href="?date=<?= $prev ?>" class="btn btn-ghost btn-sm">← Previous Day</a>
  <form method="GET" style="display:flex;align-items:center;gap:10px;">
    <input type="date" name="date" value="<?= $date ?>" onchange="this.form.submit()" style="width:auto;">
  </form>
  <a href="?date=<?= $next ?>" class="btn btn-ghost btn-sm" <?= $next > date('Y-m-d') ? 'style="opacity:0.4;pointer-events:none;"' : '' ?>>Next Day →</a>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue">🧾</div>
    <div class="stat-info"><p>Transactions</p><h3><?= $sum['c'] ?></h3></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">💰</div>
    <div class="stat-info"><p>Revenue</p><h3><?= $cur ?> <?= number_format($sum['rev'],2) ?></h3></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple">📈</div>
    <div class="stat-info"><p>Profit</p><h3><?= $cur ?> <?= number_format($sum['prof'],2) ?></h3></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow">🧮</div>
    <div class="stat-info"><p>Tax Collected</p><h3><?= $cur ?> <?= number_format($sum['tax'],2) ?></h3></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
  <!-- By payment -->
  <div class="card">
    <div class="card-title">Revenue by Payment Method</div>
    <?php if (mysqli_num_rows($by_payment) === 0): ?>
      <p class="text-muted text-center" style="padding:20px;">No sales on this date</p>
    <?php endif; ?>
    <?php while ($p = mysqli_fetch_assoc($by_payment)): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);">
      <span class="pill <?= $p['payment_method']==='mpesa'?'pill-green':($p['payment_method']==='credit'?'pill-yellow':'pill-blue') ?>">
        <?= strtoupper($p['payment_method']) ?>
      </span>
      <span class="text-muted"><?= $p['c'] ?> sales</span>
      <span class="fw-bold"><?= $cur ?> <?= number_format($p['rev'],2) ?></span>
    </div>
    <?php endwhile; ?>
  </div>

  <!-- Top products -->
  <div class="card">
    <div class="card-title">Top Products Today</div>
    <?php if (mysqli_num_rows($top_products) === 0): ?>
      <p class="text-muted text-center" style="padding:20px;">No products sold</p>
    <?php endif; ?>
    <?php while ($p = mysqli_fetch_assoc($top_products)): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);">
      <div>
        <div class="fw-bold" style="font-size:13px;"><?= htmlspecialchars($p['product_name']) ?></div>
        <div class="text-muted" style="font-size:11px;"><?= $p['qty'] ?> units sold</div>
      </div>
      <span class="fw-bold text-green"><?= $cur ?> <?= number_format($p['rev'],2) ?></span>
    </div>
    <?php endwhile; ?>
  </div>
</div>

<!-- Transactions -->
<div class="card mt-2">
  <div class="card-title">All Transactions — <?= date('d M Y', strtotime($date)) ?></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Time</th><th>Cashier</th><th>Payment</th><th>Total</th><th></th></tr></thead>
      <tbody>
        <?php if (mysqli_num_rows($txns) === 0): ?>
        <tr><td colspan="5" class="text-center text-muted" style="padding:30px;">No transactions on this date</td></tr>
        <?php endif; ?>
        <?php while ($t = mysqli_fetch_assoc($txns)): ?>
        <tr>
          <td><?= date('H:i', strtotime($t['created_at'])) ?></td>
          <td><?= htmlspecialchars($t['full_name'] ?? '—') ?></td>
          <td><span class="pill pill-blue"><?= strtoupper($t['payment_method']) ?></span></td>
          <td class="fw-bold"><?= $cur ?> <?= number_format($t['total'],2) ?></td>
          <td><a href="receipt.php?id=<?= $t['id'] ?>" class="btn btn-ghost btn-sm">View →</a></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'layout_footer.php';
