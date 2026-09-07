<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Low Stock Alert';
$s = get_settings();
$cur = $s['currency'];

$products = mysqli_query($conn, "SELECT p.*, c.name cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.is_active=1 AND p.quantity <= p.low_stock_alert ORDER BY (p.quantity / GREATEST(p.low_stock_alert,1)) ASC");
$count = mysqli_num_rows($products);

require_once 'layout_header.php';
?>

<?php if ($count === 0): ?>
<div class="card text-center" style="padding:60px 20px;">
  <div style="font-size:48px;margin-bottom:16px;">✅</div>
  <h3 style="font-size:18px;margin-bottom:8px;">All stocked up!</h3>
  <p class="text-muted">No products are running low right now.</p>
</div>
<?php else: ?>

<div class="alert alert-warning">
  ⚠️ <strong><?= $count ?></strong> product<?= $count > 1 ? 's' : '' ?> need restocking soon.
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Product</th>
          <th>Category</th>
          <th>Current Stock</th>
          <th>Alert Threshold</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($p = mysqli_fetch_assoc($products)): ?>
        <?php $critical = $p['quantity'] <= 0; ?>
        <tr>
          <td class="fw-bold"><?= htmlspecialchars($p['product_name']) ?></td>
          <td><span class="pill pill-blue"><?= htmlspecialchars($p['cat_name'] ?? 'General') ?></span></td>
          <td class="<?= $critical ? 'text-red fw-bold' : 'text-yellow fw-bold' ?>">
            <?= $p['quantity'] ?> <?= $p['unit_type'] ?>
          </td>
          <td class="text-muted"><?= $p['low_stock_alert'] ?> <?= $p['unit_type'] ?></td>
          <td>
            <span class="pill <?= $critical ? 'pill-red' : 'pill-yellow' ?>">
              <?= $critical ? '🔴 OUT OF STOCK' : '🟡 LOW STOCK' ?>
            </span>
          </td>
          <td>
            <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm">📦 Restock</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require_once 'layout_footer.php';
