<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Products';
$s   = get_settings();
$cur = $s['currency'];

$search = clean($_GET['q'] ?? '');
$cat    = (int)($_GET['cat'] ?? 0);
$where  = "p.is_active=1";
if ($search) $where .= " AND p.product_name LIKE '%$search%'";
if ($cat)    $where .= " AND p.category_id=$cat";

$products   = mysqli_query($conn, "SELECT p.*, c.name cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE $where ORDER BY p.product_name ASC");
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
$threshold  = (int)($s['low_stock_threshold'] ?? 5);
[$product_limit_reached, $product_count, $product_limit] = product_limit_reached();

require_once 'layout_header.php';
?>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Search products..." style="width:220px;">
      <select name="cat" onchange="this.form.submit()">
        <option value="0">All Categories</option>
        <?php while ($c = mysqli_fetch_assoc($categories)): ?>
        <option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endwhile; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Search</button>
      <?php if ($search || $cat): ?>
      <a href="view_products.php" class="btn btn-ghost btn-sm">Clear</a>
      <?php endif; ?>
    </form>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a href="bulk_products.php" class="btn btn-primary">↥ Import Products</a>
      <a href="add_product.php" class="btn btn-success">➕ Add Product</a>
    </div>
  </div>
  <div class="text-muted" style="font-size:12px;margin-bottom:14px;">Plan capacity: <?= $product_count ?> / <?= $product_limit ?> active products</div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Product Name</th>
          <th>Category</th>
          <th>Buying Price</th>
          <th>Selling Price</th>
          <th>Profit/Unit</th>
          <th>Stock</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($products) === 0): ?>
        <tr><td colspan="8" class="text-center text-muted" style="padding:40px;">
          No products found <?= $search ? "for \"$search\"" : '' ?>
        </td></tr>
        <?php endif; ?>
        <?php $i = 1; while ($p = mysqli_fetch_assoc($products)): ?>
        <?php $low = $p['quantity'] <= $threshold; $profit = $p['selling_price'] - $p['buying_price']; ?>
        <tr>
          <td class="text-muted"><?= $i++ ?></td>
          <td class="fw-bold"><?= htmlspecialchars($p['product_name']) ?></td>
          <td><span class="pill pill-blue"><?= htmlspecialchars($p['cat_name'] ?? 'General') ?></span></td>
          <td><?= $cur ?> <?= number_format($p['buying_price'], 2) ?></td>
          <td><?= $cur ?> <?= number_format($p['selling_price'], 2) ?></td>
          <td class="<?= $profit > 0 ? 'text-green' : 'text-red' ?> fw-bold">
            <?= $cur ?> <?= number_format($profit, 2) ?>
          </td>
          <td>
            <span class="pill <?= $low ? 'pill-red' : 'pill-green' ?>">
              <?= $p['quantity'] ?> <?= $p['unit_type'] ?><?= $low ? ' ⚠️' : '' ?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
              <a href="delete_product.php?id=<?= $p['id'] ?>"
                 onclick="return confirm('Delete <?= addslashes($p['product_name']) ?>? This cannot be undone.')"
                 class="btn btn-danger btn-sm">🗑️</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'layout_footer.php';
