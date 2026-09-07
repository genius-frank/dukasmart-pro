<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Add Product';
$s = get_settings();
[$product_limit_reached, $product_count, $product_limit] = product_limit_reached();
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
require_once 'layout_header.php';
?>

<div style="max-width:700px;">
<?php if ($product_limit_reached): ?>
  <div class="alert alert-warning">Your <?= htmlspecialchars(ucfirst(current_plan_code())) ?> plan has reached its <?= $product_limit ?>-product limit. Upgrade in Billing &amp; Plans to add more.</div>
<?php else: ?>
  <div class="card">
    <form method="POST" action="save_product.php">
      <div class="form-grid">
        <div class="form-group" style="grid-column:1/-1;">
          <label>Product Name *</label>
          <input type="text" name="product_name" required placeholder="e.g. Unga Jogoo 2kg" autofocus>
        </div>
        <div class="form-group">
          <label>Category</label>
          <select name="category_id">
            <?php while ($c = mysqli_fetch_assoc($categories)): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Unit Type</label>
          <select name="unit_type">
            <?php foreach (['piece','kg','g','liter','ml','box','dozen','pack'] as $u): ?>
            <option value="<?= $u ?>"><?= ucfirst($u) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Buying Price (<?= $s['currency'] ?>) *</label>
          <input type="number" name="buying_price" step="0.01" min="0" required placeholder="0.00">
        </div>
        <div class="form-group">
          <label>Selling Price (<?= $s['currency'] ?>) *</label>
          <input type="number" name="selling_price" step="0.01" min="0" required placeholder="0.00">
        </div>
        <div class="form-group">
          <label>Opening Stock (Quantity) *</label>
          <input type="number" name="quantity" step="0.5" min="0" required placeholder="0">
        </div>
        <div class="form-group">
          <label>Low Stock Alert (units)</label>
          <input type="number" name="low_stock_alert" min="1" value="5">
        </div>
      </div>

      <!-- Profit preview -->
      <div id="profit-preview" class="alert alert-info" style="display:none;">
        💰 Profit per unit: <strong id="profit-amount"></strong>
        &nbsp;|&nbsp; Margin: <strong id="profit-margin"></strong>
      </div>

      <div style="display:flex;gap:10px;margin-top:8px;">
        <button type="submit" class="btn btn-success">✓ Save Product</button>
        <a href="view_products.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
<?php endif; ?>
</div>

<script>
const bp = document.querySelector('[name="buying_price"]');
const sp = document.querySelector('[name="selling_price"]');
const preview = document.getElementById('profit-preview');
const amt = document.getElementById('profit-amount');
const mrg = document.getElementById('profit-margin');

function updateProfit() {
  const b = parseFloat(bp.value) || 0;
  const s = parseFloat(sp.value) || 0;
  if (b > 0 && s > 0) {
    const profit  = s - b;
    const margin  = ((profit / s) * 100).toFixed(1);
    preview.style.display = 'flex';
    preview.className = `alert ${profit >= 0 ? 'alert-success' : 'alert-danger'}`;
    amt.textContent = `<?= $s['currency'] ?> ${profit.toFixed(2)}`;
    mrg.textContent = `${margin}%`;
  } else {
    preview.style.display = 'none';
  }
}
bp.addEventListener('input', updateProfit);
sp.addEventListener('input', updateProfit);
</script>

<?php require_once 'layout_footer.php';
