<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Edit Product';
$s = get_settings();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { require_once __DIR__ . '/db.php'; redirect_to('view_products.php'); }

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = clean($_POST['product_name'] ?? '');
    $cat_id    = (int)($_POST['category_id'] ?? 1);
    $unit      = clean($_POST['unit_type'] ?? 'piece');
    $buy_price = (float)($_POST['buying_price'] ?? 0);
    $sel_price = (float)($_POST['selling_price'] ?? 0);
    $qty       = (float)($_POST['quantity'] ?? 0);
    $alert     = (int)($_POST['low_stock_alert'] ?? 5);
    $pid       = (int)$_POST['product_id'];

    $stmt = mysqli_prepare($conn, "UPDATE products SET product_name=?, category_id=?, unit_type=?, buying_price=?, selling_price=?, quantity=?, low_stock_alert=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "sisdddii", $name, $cat_id, $unit, $buy_price, $sel_price, $qty, $alert, $pid);
    mysqli_stmt_execute($stmt);

    redirect_to('view_products.php?msg=' . urlencode("\"$name\" updated successfully!") . '&type=success');
    exit();
}

// Load product
$r = mysqli_query($conn, "SELECT * FROM products WHERE id=$id AND is_active=1 LIMIT 1");
$p = mysqli_fetch_assoc($r);
if (!$p) { require_once __DIR__ . '/db.php'; redirect_to('view_products.php'); }

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

require_once 'layout_header.php';
?>

<div style="max-width:700px;">
  <div class="card">
    <form method="POST">
      <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
      <div class="form-grid">
        <div class="form-group" style="grid-column:1/-1;">
          <label>Product Name *</label>
          <input type="text" name="product_name" required value="<?= htmlspecialchars($p['product_name']) ?>">
        </div>
        <div class="form-group">
          <label>Category</label>
          <select name="category_id">
            <?php while ($c = mysqli_fetch_assoc($categories)): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id'] == $p['category_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Unit Type</label>
          <select name="unit_type">
            <?php foreach (['piece','kg','g','liter','ml','box','dozen','pack'] as $u): ?>
            <option value="<?= $u ?>" <?= $u === $p['unit_type'] ? 'selected' : '' ?>><?= ucfirst($u) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Buying Price (<?= $s['currency'] ?>)</label>
          <input type="number" name="buying_price" step="0.01" min="0" value="<?= $p['buying_price'] ?>">
        </div>
        <div class="form-group">
          <label>Selling Price (<?= $s['currency'] ?>)</label>
          <input type="number" name="selling_price" step="0.01" min="0" value="<?= $p['selling_price'] ?>">
        </div>
        <div class="form-group">
          <label>Current Stock</label>
          <input type="number" name="quantity" step="0.5" min="0" value="<?= $p['quantity'] ?>">
        </div>
        <div class="form-group">
          <label>Low Stock Alert (units)</label>
          <input type="number" name="low_stock_alert" min="1" value="<?= $p['low_stock_alert'] ?>">
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px;">
        <button type="submit" class="btn btn-success">✓ Update Product</button>
        <a href="view_products.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once 'layout_footer.php';
