<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Receipt';
$s = get_settings();
$cur = $s['currency'];

$sale_id = (int)($_GET['id'] ?? 0);
if (!$sale_id) { require_once __DIR__ . '/db.php'; redirect_to('pos.php'); }

$r = mysqli_query($conn, "SELECT s.*, u.full_name, c.name customer_name FROM sales s LEFT JOIN users u ON s.cashier_id=u.id LEFT JOIN customers c ON s.customer_id=c.id WHERE s.id=$sale_id LIMIT 1");
$sale = mysqli_fetch_assoc($r);
if (!$sale) { require_once __DIR__ . '/db.php'; redirect_to('dashboard.php'); }

$items = mysqli_query($conn, "SELECT * FROM sale_items WHERE sale_id=$sale_id");

require_once 'layout_header.php';
?>

<div style="max-width:500px;margin:0 auto;">
  <div class="receipt-box" id="receipt-print">
    <h2>🛒 <?= htmlspecialchars($s['shop_name']) ?></h2>
    <div class="shop-sub">
      <?= htmlspecialchars($s['shop_address'] ?? '') ?>
      <?php if ($s['shop_phone']): ?> · <?= htmlspecialchars($s['shop_phone']) ?><?php endif; ?>
    </div>

    <hr class="receipt-divider">

    <div class="receipt-row">
      <span>Receipt #</span>
      <span class="fw-bold"><?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?></span>
    </div>
    <div class="receipt-row">
      <span>Date</span>
      <span><?= date('d M Y H:i', strtotime($sale['created_at'])) ?></span>
    </div>
    <div class="receipt-row">
      <span>Cashier</span>
      <span><?= htmlspecialchars($sale['full_name'] ?? '—') ?></span>
    </div>
    <?php if ($sale['customer_name']): ?>
    <div class="receipt-row">
      <span>Customer</span>
      <span class="fw-bold"><?= htmlspecialchars($sale['customer_name']) ?></span>
    </div>
    <?php endif; ?>
    <div class="receipt-row">
      <span>Payment</span>
      <span><?= strtoupper($sale['payment_method']) ?>
        <?php if ($sale['mpesa_ref']): ?>
          <small>(<?= htmlspecialchars($sale['mpesa_ref']) ?>)</small>
        <?php endif; ?>
      </span>
    </div>

    <hr class="receipt-divider">

    <!-- Items -->
    <div style="font-size:11px;color:var(--muted);display:grid;grid-template-columns:1fr auto auto;gap:4px 10px;font-weight:600;margin-bottom:6px;">
      <span>ITEM</span><span>QTY</span><span>TOTAL</span>
    </div>
    <?php while ($item = mysqli_fetch_assoc($items)): ?>
    <div style="display:grid;grid-template-columns:1fr auto auto;gap:4px 10px;margin-bottom:6px;font-size:13px;">
      <span><?= htmlspecialchars($item['product_name']) ?></span>
      <span class="text-muted"><?= $item['quantity_sold'] ?></span>
      <span class="fw-bold"><?= $cur ?> <?= number_format($item['total'], 2) ?></span>
    </div>
    <div style="color:var(--muted);font-size:11px;padding-left:0;margin-bottom:8px;grid-column:1/-1;">
      @ <?= $cur ?> <?= number_format($item['selling_price'], 2) ?> each
    </div>
    <?php endwhile; ?>

    <hr class="receipt-divider">

    <?php if ($sale['tax'] > 0): ?>
    <div class="receipt-row">
      <span>Subtotal</span>
      <span><?= $cur ?> <?= number_format($sale['subtotal'], 2) ?></span>
    </div>
    <div class="receipt-row">
      <span>Tax</span>
      <span><?= $cur ?> <?= number_format($sale['tax'], 2) ?></span>
    </div>
    <hr class="receipt-divider">
    <?php endif; ?>

    <div class="receipt-row receipt-total">
      <span>TOTAL PAID</span>
      <span><?= $cur ?> <?= number_format($sale['total'], 2) ?></span>
    </div>

    <hr class="receipt-divider">
    <p style="text-align:center;color:var(--muted);font-size:12px;margin-top:8px;">
      <?= htmlspecialchars($s['receipt_footer'] ?? 'Thank you for shopping with us!') ?>
    </p>
  </div>

  <!-- Actions -->
  <div class="no-print" style="display:flex;gap:10px;margin-top:20px;justify-content:center;">
    <button onclick="window.print()" class="btn btn-primary">🖨️ Print Receipt</button>
    <button onclick="shareReceipt()" class="btn btn-ghost">↗ Share Receipt</button>
    <a href="pos.php" class="btn btn-success">🛒 New Sale</a>
    <a href="dashboard.php" class="btn btn-ghost">📊 Dashboard</a>
  </div>
</div>

<script>
function shareReceipt() {
  const text = <?= json_encode($s['shop_name'] . ' receipt #' . str_pad($sale_id, 6, '0', STR_PAD_LEFT) . ' - ' . $cur . ' ' . number_format($sale['total'], 2)) ?>;
  if (navigator.share) {
    navigator.share({ title: 'DukaSmart Receipt', text: text }).catch(function () {});
    return;
  }
  window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank', 'noopener');
}
</script>

<?php require_once 'layout_footer.php';
