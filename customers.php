<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Credit Book';
$s = get_settings();
$cur = $s['currency'];

$search = clean($_GET['q'] ?? '');
$where = "1=1";
if ($search) $where .= " AND (c.name LIKE '%$search%' OR c.phone LIKE '%$search%')";

$customers = mysqli_query($conn, "SELECT c.*,
    COALESCE((SELECT SUM(total) FROM sales WHERE customer_id=c.id AND payment_method='credit'),0) total_owed,
    COALESCE((SELECT SUM(amount) FROM credit_payments WHERE customer_id=c.id),0) total_paid
    FROM customers c WHERE $where ORDER BY c.name ASC");

$total_outstanding = total_credit_outstanding();

require_once 'layout_header.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon red">📒</div>
    <div class="stat-info"><p>Total Outstanding Credit</p><h3><?= $cur ?> <?= number_format($total_outstanding,2) ?></h3></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow">👥</div>
    <div class="stat-info"><p>Customers Owing</p><h3><?= $credit_count ?></h3></div>
  </div>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
    <form method="GET" style="display:flex;gap:8px;">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Search name or phone..." style="width:220px;">
      <button type="submit" class="btn btn-primary btn-sm">Search</button>
    </form>
    <a href="add_customer.php" class="btn btn-success">➕ Add Customer</a>
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Customer</th><th>Phone</th><th>Total Credit Taken</th><th>Total Paid</th><th>Balance Owed</th><th></th></tr></thead>
      <tbody>
        <?php if (mysqli_num_rows($customers) === 0): ?>
        <tr><td colspan="6" class="text-center text-muted" style="padding:40px;">No customers yet</td></tr>
        <?php endif; ?>
        <?php while ($c = mysqli_fetch_assoc($customers)):
          $balance = $c['total_owed'] - $c['total_paid'];
        ?>
        <tr>
          <td class="fw-bold"><?= htmlspecialchars($c['name']) ?></td>
          <td class="text-muted"><?= htmlspecialchars($c['phone'] ?: '—') ?></td>
          <td><?= $cur ?> <?= number_format($c['total_owed'],2) ?></td>
          <td class="text-green"><?= $cur ?> <?= number_format($c['total_paid'],2) ?></td>
          <td>
            <span class="pill <?= $balance > 0 ? 'pill-red' : 'pill-green' ?>">
              <?= $cur ?> <?= number_format($balance,2) ?>
            </span>
          </td>
          <td><a href="customer_detail.php?id=<?= $c['id'] ?>" class="btn btn-ghost btn-sm">View →</a></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'layout_footer.php'; ?>
