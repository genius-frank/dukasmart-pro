<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Import Products';
$s = get_settings();
[$product_limit_reached, $product_count, $product_limit] = product_limit_reached();
require_once 'layout_header.php';
?>

<div style="max-width:900px;">
  <div class="card">
    <div class="card-title">Import many products at once</div>
    <p class="text-muted" style="line-height:1.7;">Paste one product per line. This is useful when a shop has dozens or hundreds of items. Do not include a header row.</p>
    <div class="alert alert-info" style="display:block;margin-top:16px;">
      Format: <strong>name, category, unit, buying price, selling price, quantity, low-stock alert</strong><br>
      Example: <code>Maize Flour 2kg,Food &amp; Drinks,pack,120,150,24,5</code>
    </div>
    <p class="text-muted" style="font-size:12px;">Current plan capacity: <?= $product_count ?> / <?= $product_limit ?> active products.</p>
    <?php if ($product_limit_reached): ?>
      <div class="alert alert-warning">Your current plan is full. Upgrade in Billing &amp; Plans before importing more products.</div>
    <?php else: ?>
    <form method="POST" action="bulk_save_products.php">
      <div class="form-group">
        <label>Product list</label>
        <textarea name="product_csv" rows="14" required placeholder="Maize Flour 2kg,Food & Drinks,pack,120,150,24,5
Cooking Oil 1L,Food & Drinks,liter,180,220,18,5"></textarea>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button type="submit" class="btn btn-success">Import Products</button>
        <a href="view_products.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>

<?php require_once 'layout_footer.php'; ?>
