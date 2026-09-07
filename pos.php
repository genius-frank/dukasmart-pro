<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Point of Sale';
$s = get_settings();
$cur = $s['currency'];

// ── Cart in session ───────────────────────────────────────────
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Add item to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $pid = (int)$_POST['product_id'];
        $qty = max(1, (float)$_POST['qty']);
        $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id=? AND is_active=1 LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $pid);
        mysqli_stmt_execute($stmt);
        $prod = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if ($prod) {
            if ($qty > $prod['quantity']) {
                $_SESSION['cart_error'] = "Only {$prod['quantity']} {$prod['unit_type']}(s) in stock for {$prod['product_name']}";
            } else {
                $found = false;
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['product_id'] == $pid) {
                        $new_qty = $item['qty'] + $qty;
                        if ($new_qty > $prod['quantity']) {
                            $_SESSION['cart_error'] = "Cannot add more — only {$prod['quantity']} in stock.";
                        } else {
                            $item['qty']   = $new_qty;
                            $item['total'] = $new_qty * $item['selling_price'];
                        }
                        $found = true; break;
                    }
                }
                if (!$found) {
                    $_SESSION['cart'][] = [
                        'product_id'    => $prod['id'],
                        'product_name'  => $prod['product_name'],
                        'qty'           => $qty,
                        'buying_price'  => $prod['buying_price'],
                        'selling_price' => $prod['selling_price'],
                        'total'         => $qty * $prod['selling_price'],
                        'unit_type'     => $prod['unit_type'],
                    ];
                }
            }
        }
    }
    if ($_POST['action'] === 'remove') {
        $idx = (int)$_POST['idx'];
        array_splice($_SESSION['cart'], $idx, 1);
    }
    if ($_POST['action'] === 'clear') {
        $_SESSION['cart'] = [];
    }
    require_once __DIR__ . '/db.php';
    redirect_to('pos.php');
}

// ── Load products ─────────────────────────────────────────────
$search = clean($_GET['q'] ?? '');
$cat_filter = (int)($_GET['cat'] ?? 0);

$where = "p.is_active=1 AND p.quantity > 0";
if ($search)     $where .= " AND p.product_name LIKE '%$search%'";
if ($cat_filter) $where .= " AND p.category_id=$cat_filter";

$products = mysqli_query($conn, "SELECT p.*, c.name cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE $where ORDER BY p.product_name ASC");
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
$all_customers = mysqli_query($conn, "SELECT id, name, phone FROM customers ORDER BY name ASC");

// Cart totals
$cart_subtotal = array_sum(array_column($_SESSION['cart'], 'total'));
$cart_profit   = array_sum(array_map(fn($i) => ($i['selling_price'] - $i['buying_price']) * $i['qty'], $_SESSION['cart']));
$tax_rate      = (float)($s['tax_rate'] ?? 0);
$tax_amount    = $cart_subtotal * ($tax_rate / 100);
$cart_total    = $cart_subtotal + $tax_amount;

$cart_error = $_SESSION['cart_error'] ?? '';
unset($_SESSION['cart_error']);

require_once 'layout_header.php';
?>

<?php if ($cart_error): ?>
<div class="alert alert-warning">⚠️ <?= htmlspecialchars($cart_error) ?></div>
<?php endif; ?>

<div class="pos-grid">

  <!-- Products panel -->
  <div class="product-panel">
    <div class="card pos-toolbar">
      <form method="GET" class="pos-search-form">
        <div class="search-field-wrap">
          <span class="search-icon">🔍</span>
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search products...">
        </div>
        <select name="cat" onchange="this.form.submit()">
          <option value="0">All Categories</option>
          <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
          <option value="<?= $cat['id'] ?>" <?= $cat_filter == $cat['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['name']) ?>
          </option>
          <?php endwhile; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
      </form>
    </div>

    <!-- Product grid -->
    <div class="product-grid">
      <?php if (mysqli_num_rows($products) === 0): ?>
        <div class="card text-center text-muted product-empty-state">
          📦 No products found<?= $search ? " for \"$search\"" : '' ?>
        </div>
      <?php endif; ?>
      <?php while ($p = mysqli_fetch_assoc($products)): ?>
      <div class="card product-card" data-product-id="<?= $p['id'] ?>"
           onclick="quickAdd(<?= $p['id'] ?>, '<?= addslashes($p['product_name']) ?>', <?= $p['selling_price'] ?>)"
           role="button" tabindex="0"
           onkeydown="if(event.key === 'Enter' || event.key === ' ') { event.preventDefault(); quickAdd(<?= $p['id'] ?>, '<?= addslashes($p['product_name']) ?>', <?= $p['selling_price'] ?>); }">
        <div class="product-icon">📦</div>
        <div class="product-name"><?= htmlspecialchars($p['product_name']) ?></div>
        <div class="product-price"><?= $cur ?> <?= number_format($p['selling_price'], 2) ?></div>
        <div class="product-stock">
          Stock: <?= $p['quantity'] ?> <?= $p['unit_type'] ?>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>

  <!-- Cart panel -->
  <div class="cart-panel">
    <div class="card cart-card">
      <div class="cart-header">
        <h3>🛒 Cart (<?= count($_SESSION['cart']) ?>)</h3>
        <?php if (!empty($_SESSION['cart'])): ?>
        <form method="POST" class="inline-form" onsubmit="return confirm('Clear cart?')">
          <input type="hidden" name="action" value="clear">
          <button class="btn btn-danger btn-sm">Clear</button>
        </form>
        <?php endif; ?>
      </div>

      <?php if (empty($_SESSION['cart'])): ?>
      <div class="cart-empty">
        <div class="big-icon">🛒</div>
        <p>Cart is empty</p>
        <small class="text-muted">Click a product or use search to add items</small>
      </div>
      <?php else: ?>

      <!-- Cart items -->
      <?php foreach ($_SESSION['cart'] as $idx => $item): ?>
      <div class="cart-item">
        <div class="cart-summary">
          <div class="cart-item-name"><?= htmlspecialchars($item['product_name']) ?></div>
          <div class="cart-item-meta">
            <?= $item['qty'] ?> × <?= $cur ?> <?= number_format($item['selling_price'], 2) ?>
          </div>
        </div>
        <div class="cart-actions">
          <span class="fw-bold"><?= $cur ?> <?= number_format($item['total'], 2) ?></span>
          <form method="POST" class="inline-form">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="idx" value="<?= $idx ?>">
            <button class="remove-item-btn">×</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- Totals -->
      <div class="totals-box">
        <div class="cart-total-row">
          <span>Subtotal</span>
          <span><?= $cur ?> <?= number_format($cart_subtotal, 2) ?></span>
        </div>
        <?php if ($tax_rate > 0): ?>
        <div class="cart-total-row">
          <span>Tax (<?= $tax_rate ?>%)</span>
          <span><?= $cur ?> <?= number_format($tax_amount, 2) ?></span>
        </div>
        <?php endif; ?>
        <div class="cart-total-row grand">
          <span>TOTAL</span>
          <span><?= $cur ?> <?= number_format($cart_total, 2) ?></span>
        </div>
      </div>

      <!-- Checkout form -->
      <form method="POST" action="process_sale.php" class="checkout-form">
        <div class="form-group">
          <label>Payment Method</label>
          <select name="payment_method" required>
            <option value="cash">💵 Cash</option>
            <option value="mpesa">📱 M-Pesa</option>
            <option value="credit">📋 Credit</option>
          </select>
        </div>
        <div class="form-group" id="mpesa-field" style="display:none;">
          <label>M-Pesa Reference</label>
          <input type="text" name="mpesa_ref" placeholder="e.g. QHJ34TY67X" maxlength="20">
        </div>
        <div class="form-group" id="credit-field" style="display:none;">
          <label>Customer (required for credit)</label>
          <select name="customer_id" id="customer-select">
            <option value="">-- Select existing customer --</option>
            <?php mysqli_data_seek($all_customers, 0); while ($cu = mysqli_fetch_assoc($all_customers)): ?>
            <option value="<?= $cu['id'] ?>"><?= htmlspecialchars($cu['name']) ?><?= $cu['phone'] ? ' — ' . htmlspecialchars($cu['phone']) : '' ?></option>
            <?php endwhile; ?>
            <option value="__new__">+ Add new customer</option>
          </select>
          <div id="new-customer-fields" style="display:none;margin-top:10px;">
            <input type="text" name="new_customer_name" placeholder="Customer name" style="margin-bottom:8px;">
            <input type="text" name="new_customer_phone" placeholder="Phone (optional)">
          </div>
        </div>
        <button type="submit" class="btn btn-success btn-block btn-lg">
          ✓ Complete Sale — <?= $cur ?> <?= number_format($cart_total, 2) ?>
        </button>
      </form>
      <?php endif; ?>
    </div>

    <!-- Quick add form -->
    <div class="card quick-add-card">
      <div class="card-title">Quick Add by Name</div>
      <form method="POST" class="quick-add-form">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" id="quick-pid" value="">
        <div class="quick-add-row">
          <input type="number" name="qty" id="quick-qty" value="1" min="1" step="0.5">
          <button type="submit" class="btn btn-primary" id="quick-btn" disabled>Add to Cart</button>
        </div>
        <div id="quick-label" class="quick-label">Click a product above to select it</div>
      </form>
    </div>
  </div>
</div>

<script>
// Show M-Pesa or Credit fields depending on payment method
document.querySelector('select[name="payment_method"]')?.addEventListener('change', function() {
  document.getElementById('mpesa-field').style.display  = this.value === 'mpesa' ? 'block' : 'none';
  document.getElementById('credit-field').style.display = this.value === 'credit' ? 'block' : 'none';
});

// Toggle new-customer inputs when "+ Add new customer" is chosen
document.getElementById('customer-select')?.addEventListener('change', function() {
  document.getElementById('new-customer-fields').style.display = this.value === '__new__' ? 'block' : 'none';
});

// Quick add — clicking product card fills the form
function quickAdd(pid, name, price) {
  const pidEl = document.getElementById('quick-pid');
  const btn = document.getElementById('quick-btn');
  const label = document.getElementById('quick-label');
  const productCards = document.querySelectorAll('.product-card');

  pidEl.value = pid;
  btn.disabled = false;
  label.innerHTML = `Selected: <strong>${name}</strong> @ <?= $cur ?> ${parseFloat(price).toFixed(2)}`;

  productCards.forEach(function(card) {
    card.classList.toggle('selected', Number(card.dataset.productId) === Number(pid));
  });
}
</script>

<?php require_once 'layout_footer.php';
