<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Customer Detail';
$s = get_settings();
$cur = $s['currency'];

$id = (int)($_GET['id'] ?? 0);
$cust_r = mysqli_query($conn, "SELECT * FROM customers WHERE id=$id LIMIT 1");
$customer = mysqli_fetch_assoc($cust_r);
if (!$customer) { require_once __DIR__ . '/db.php'; redirect_to('customers.php'); }

$balance = customer_balance($id);

// Credit sales by this customer
$sales = mysqli_query($conn, "SELECT s.*,
    (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id=s.id) item_count
    FROM sales s WHERE s.customer_id=$id AND s.payment_method='credit' ORDER BY s.created_at DESC");

// Payments made by this customer
$payments = mysqli_query($conn, "SELECT cp.*, u.full_name FROM credit_payments cp LEFT JOIN users u ON cp.recorded_by=u.id WHERE cp.customer_id=$id ORDER BY cp.created_at DESC");

require_once 'layout_header.php';
?>

<a href="customers.php" class="btn btn-ghost btn-sm" style="margin-bottom:16px;">← Back to Credit Book</a>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;">
  <div>
    <!-- Customer header -->
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;">
        <div>
          <h2 style="font-size:20px;margin-bottom:4px;"><?= htmlspecialchars($customer['name']) ?></h2>
          <p class="text-muted" style="font-size:13px;">
            <?= htmlspecialchars($customer['phone'] ?: 'No phone on file') ?>
            <?= $customer['address'] ? ' · ' . htmlspecialchars($customer['address']) : '' ?>
          </p>
        </div>
        <span class="pill <?= $balance > 0 ? 'pill-red' : 'pill-green' ?>" style="font-size:14px;padding:8px 16px;">
          <?= $balance > 0 ? 'OWES' : 'CLEARED' ?>
        </span>
      </div>
    </div>

    <!-- Credit sales history -->
    <div class="card">
      <div class="card-title">Credit Purchases</div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date</th><th>Items</th><th>Amount</th><th></th></tr></thead>
          <tbody>
            <?php if (mysqli_num_rows($sales) === 0): ?>
            <tr><td colspan="4" class="text-center text-muted" style="padding:24px;">No credit purchases yet</td></tr>
            <?php endif; ?>
            <?php while ($sale = mysqli_fetch_assoc($sales)): ?>
            <tr>
              <td><?= date('d M Y, H:i', strtotime($sale['created_at'])) ?></td>
              <td class="text-muted"><?= $sale['item_count'] ?> item<?= $sale['item_count']!=1?'s':'' ?></td>
              <td class="fw-bold text-red"><?= $cur ?> <?= number_format($sale['total'],2) ?></td>
              <td><a href="receipt.php?id=<?= $sale['id'] ?>" class="btn btn-ghost btn-sm">View →</a></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Payment history -->
    <div class="card">
      <div class="card-title">Payments Received</div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date</th><th>Method</th><th>Recorded By</th><th>Amount</th></tr></thead>
          <tbody>
            <?php if (mysqli_num_rows($payments) === 0): ?>
            <tr><td colspan="4" class="text-center text-muted" style="padding:24px;">No payments recorded yet</td></tr>
            <?php endif; ?>
            <?php while ($p = mysqli_fetch_assoc($payments)): ?>
            <tr>
              <td><?= date('d M Y, H:i', strtotime($p['created_at'])) ?></td>
              <td><span class="pill pill-blue"><?= strtoupper($p['payment_method']) ?></span></td>
              <td class="text-muted"><?= htmlspecialchars($p['full_name'] ?? '—') ?></td>
              <td class="fw-bold text-green"><?= $cur ?> <?= number_format($p['amount'],2) ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Balance + payment form -->
  <div>
    <div class="card" style="background:<?= $balance > 0 ? 'var(--danger)' : 'var(--accent)' ?>;color:white;">
      <div style="font-size:12px;opacity:0.85;margin-bottom:6px;">CURRENT BALANCE</div>
      <div style="font-size:28px;font-weight:700;"><?= $cur ?> <?= number_format($balance,2) ?></div>
      <div style="font-size:12px;opacity:0.85;margin-top:4px;">
        <?= $balance > 0 ? 'Still owed to the shop' : 'Fully settled ✓' ?>
      </div>
    </div>

    <?php if ($balance > 0): ?>
    <div class="card">
      <div class="card-title">💰 Record a Payment</div>
      <form method="POST" action="record_payment.php">
        <input type="hidden" name="customer_id" value="<?= $id ?>">
        <div class="form-group">
          <label>Amount (<?= $cur ?>)</label>
          <input type="number" name="amount" step="0.01" min="0.01" max="<?= $balance ?>" required placeholder="0.00">
        </div>
        <div class="form-group">
          <label>Payment Method</label>
          <select name="payment_method">
            <option value="cash">💵 Cash</option>
            <option value="mpesa">📱 M-Pesa</option>
          </select>
        </div>
        <div class="form-group">
          <label>Note (optional)</label>
          <input type="text" name="note" placeholder="e.g. Partial payment">
        </div>
        <button type="submit" class="btn btn-success btn-block">✓ Record Payment</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once 'layout_footer.php'; ?>
