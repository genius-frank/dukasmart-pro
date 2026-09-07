<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Expenses';
$s = get_settings();
$cur = $s['currency'];

$month_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())"))['t'];
$expenses = mysqli_query($conn, "SELECT e.*, u.full_name FROM expenses e LEFT JOIN users u ON e.recorded_by=u.id ORDER BY e.created_at DESC LIMIT 50");

require_once 'layout_header.php';
?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;">
  <div>
    <div class="card">
      <div class="card-title">Recent Expenses</div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date</th><th>Description</th><th>Category</th><th>Recorded By</th><th>Amount</th></tr></thead>
          <tbody>
            <?php if (mysqli_num_rows($expenses) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted" style="padding:30px;">No expenses recorded yet</td></tr>
            <?php endif; ?>
            <?php while ($e = mysqli_fetch_assoc($expenses)): ?>
            <tr>
              <td><?= date('d M Y', strtotime($e['created_at'])) ?></td>
              <td class="fw-bold"><?= htmlspecialchars($e['description']) ?></td>
              <td><span class="pill pill-gray"><?= htmlspecialchars($e['category']) ?></span></td>
              <td class="text-muted"><?= htmlspecialchars($e['full_name'] ?? '—') ?></td>
              <td class="fw-bold text-red"><?= $cur ?> <?= number_format($e['amount'],2) ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div>
    <div class="card" style="background:var(--primary);color:white;">
      <div style="font-size:12px;opacity:0.8;margin-bottom:6px;">THIS MONTH'S EXPENSES</div>
      <div style="font-size:26px;font-weight:700;"><?= $cur ?> <?= number_format($month_total,2) ?></div>
    </div>

    <div class="card">
      <div class="card-title">Record New Expense</div>
      <form method="POST" action="add_expense.php">
        <div class="form-group">
          <label>Description</label>
          <input type="text" name="description" required placeholder="e.g. Electricity bill">
        </div>
        <div class="form-group">
          <label>Amount (<?= $cur ?>)</label>
          <input type="number" name="amount" step="0.01" min="0" required placeholder="0.00">
        </div>
        <div class="form-group">
          <label>Category</label>
          <select name="category">
            <?php foreach (['Rent','Electricity','Water','Transport','Stock Purchase','Salaries','Maintenance','General'] as $c): ?>
            <option value="<?= $c ?>"><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-danger btn-block">💸 Record Expense</button>
      </form>
    </div>
  </div>
</div>

<?php require_once 'layout_footer.php';
