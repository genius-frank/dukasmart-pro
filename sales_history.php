<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Sales History';
$s = get_settings();
$cur = $s['currency'];

$range = $_GET['range'] ?? 'today';
$payment_filter = clean($_GET['payment'] ?? '');

$where = "1=1";
switch ($range) {
    case 'today':     $where .= " AND DATE(s.created_at) = CURDATE()"; break;
    case 'week':       $where .= " AND s.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; break;
    case 'month':     $where .= " AND MONTH(s.created_at)=MONTH(NOW()) AND YEAR(s.created_at)=YEAR(NOW())"; break;
    case 'all':        break;
}
if ($payment_filter) $where .= " AND s.payment_method = '$payment_filter'";

// Fixed query — no invalid join on sales.product_id, item_count via subquery
$query = "SELECT s.*, u.full_name,
          (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) as item_count
          FROM sales s
          LEFT JOIN users u ON s.cashier_id = u.id
          WHERE $where
          ORDER BY s.created_at DESC";
$sales = mysqli_query($conn, $query);

// Summary totals for filtered range
$sum_query = "SELECT COUNT(*) c, COALESCE(SUM(total),0) rev, COALESCE(SUM(profit),0) prof FROM sales s WHERE $where";
$summary = mysqli_fetch_assoc(mysqli_query($conn, $sum_query));

require_once 'layout_header.php';
?>

<!-- Filters -->
<div class="card" style="padding:16px;margin-bottom:16px;">
  <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:space-between;">
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <?php foreach (['today'=>'Today','week'=>'This Week','month'=>'This Month','all'=>'All Time'] as $val => $lbl): ?>
      <a href="?range=<?= $val ?><?= $payment_filter ? "&payment=$payment_filter" : '' ?>"
         class="btn btn-sm <?= $range === $val ? 'btn-primary' : 'btn-ghost' ?>"><?= $lbl ?></a>
      <?php endforeach; ?>
    </div>
    <form method="GET" style="display:flex;gap:8px;">
      <input type="hidden" name="range" value="<?= htmlspecialchars($range) ?>">
      <select name="payment" onchange="this.form.submit()">
        <option value="">All Payments</option>
        <option value="cash" <?= $payment_filter==='cash'?'selected':'' ?>>Cash</option>
        <option value="mpesa" <?= $payment_filter==='mpesa'?'selected':'' ?>>M-Pesa</option>
        <option value="credit" <?= $payment_filter==='credit'?'selected':'' ?>>Credit</option>
      </select>
    </form>
  </div>
</div>

<!-- Summary -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue">🧾</div>
    <div class="stat-info"><p>Transactions</p><h3><?= $summary['c'] ?></h3></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">💰</div>
    <div class="stat-info"><p>Total Revenue</p><h3><?= $cur ?> <?= number_format($summary['rev'],2) ?></h3></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple">📈</div>
    <div class="stat-info"><p>Total Profit</p><h3><?= $cur ?> <?= number_format($summary['prof'],2) ?></h3></div>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Date</th><th>Cashier</th><th>Items</th><th>Payment</th><th>Total</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($sales) === 0): ?>
        <tr><td colspan="6" class="text-center text-muted" style="padding:40px;">No sales found for this filter</td></tr>
        <?php endif; ?>
        <?php while ($sale = mysqli_fetch_assoc($sales)): ?>
        <tr>
          <td><?= date('d M, H:i', strtotime($sale['created_at'])) ?></td>
          <td><?= htmlspecialchars($sale['full_name'] ?? '—') ?></td>
          <td class="text-muted"><?= $sale['item_count'] ?> item<?= $sale['item_count']!=1?'s':'' ?></td>
          <td>
            <span class="pill <?= $sale['payment_method']==='mpesa'?'pill-green':($sale['payment_method']==='credit'?'pill-yellow':'pill-blue') ?>">
              <?= strtoupper($sale['payment_method']) ?>
            </span>
          </td>
          <td class="fw-bold"><?= $cur ?> <?= number_format($sale['total'],2) ?></td>
          <td><a href="receipt.php?id=<?= $sale['id'] ?>" class="btn btn-ghost btn-sm">View →</a></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'layout_footer.php';
