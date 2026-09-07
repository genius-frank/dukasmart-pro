<?php
require_once 'auth.php';
require_once 'db.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    redirect_to('dashboard.php');
}

$page_title = 'Founder QA';
$message = '';
$message_type = 'success';

function demo_query($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        throw new RuntimeException(mysqli_error($conn));
    }
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        mysqli_begin_transaction($conn);
        $action = $_POST['action'] ?? '';

        if ($action === 'seed') {
          demo_query("DELETE FROM credit_payments WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'DEMO - %')");
          demo_query("DELETE FROM sale_items WHERE sale_id IN (SELECT id FROM sales WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'DEMO - %'))");
          demo_query("DELETE FROM sales WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'DEMO - %')");
          demo_query("DELETE FROM expenses WHERE description LIKE 'DEMO - %'");
          demo_query("DELETE FROM products WHERE product_name LIKE 'DEMO - %'");
          demo_query("DELETE FROM customers WHERE name LIKE 'DEMO - %'");

            $products = [
                ['DEMO - Maize Flour 2kg', 1, 120, 150, 24, 5],
                ['DEMO - Cooking Oil 1L', 1, 180, 220, 18, 5],
                ['DEMO - Sugar 1kg', 1, 110, 140, 3, 5],
                ['DEMO - Bar Soap', 1, 55, 75, 30, 8],
                ['DEMO - Exercise Book', 5, 35, 50, 12, 4],
            ];
            $product_ids = [];
            $product_stmt = mysqli_prepare($conn, 'INSERT INTO products (category_id, product_name, buying_price, selling_price, quantity, low_stock_alert) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($products as $product) {
                $category_id = $product[1];
                $product_name = $product[0];
                $buying_price = $product[2];
                $selling_price = $product[3];
                $quantity = $product[4];
                $low_stock_alert = $product[5];
                mysqli_stmt_bind_param($product_stmt, 'isdddi', $category_id, $product_name, $buying_price, $selling_price, $quantity, $low_stock_alert);
                mysqli_stmt_execute($product_stmt);
                $product_ids[] = mysqli_insert_id($conn);
            }

            $customer_stmt = mysqli_prepare($conn, 'INSERT INTO customers (name, phone, address) VALUES (?, ?, ?)');
            $customer_name = 'DEMO - Jane Wanjiku';
            $customer_phone = '0700000001';
            $customer_address = 'Demo customer';
            mysqli_stmt_bind_param($customer_stmt, 'sss', $customer_name, $customer_phone, $customer_address);
            mysqli_stmt_execute($customer_stmt);
            $credit_customer_id = mysqli_insert_id($conn);

            $second_customer_name = 'DEMO - Peter Otieno';
            $second_customer_phone = '0700000002';
            mysqli_stmt_bind_param($customer_stmt, 'sss', $second_customer_name, $second_customer_phone, $customer_address);
            mysqli_stmt_execute($customer_stmt);
            $cash_customer_id = mysqli_insert_id($conn);

            $expense_stmt = mysqli_prepare($conn, 'INSERT INTO expenses (description, amount, category, recorded_by) VALUES (?, ?, ?, ?)');
            $expense_description = 'DEMO - Transport and supplies';
            $expense_amount = 850;
            $expense_category = 'Operations';
            $recorded_by = (int)$_SESSION['user_id'];
            mysqli_stmt_bind_param($expense_stmt, 'sdsi', $expense_description, $expense_amount, $expense_category, $recorded_by);
            mysqli_stmt_execute($expense_stmt);

            $sale_stmt = mysqli_prepare($conn, 'INSERT INTO sales (cashier_id, customer_id, payment_method, subtotal, tax, total, profit) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $item_stmt = mysqli_prepare($conn, 'INSERT INTO sale_items (sale_id, product_id, product_name, quantity_sold, buying_price, selling_price, total, profit) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stock_stmt = mysqli_prepare($conn, 'UPDATE products SET quantity = quantity - ? WHERE id = ?');

            $cash_method = 'cash';
            $cash_qty = 2;
            $cash_subtotal = 300;
            $cash_tax = 0;
            $cash_total = 300;
            $cash_profit = 60;
            mysqli_stmt_bind_param($sale_stmt, 'iisdddd', $recorded_by, $cash_customer_id, $cash_method, $cash_subtotal, $cash_tax, $cash_total, $cash_profit);
            mysqli_stmt_execute($sale_stmt);
            $cash_sale_id = mysqli_insert_id($conn);
            $cash_product_name = $products[0][0];
            $cash_buying = $products[0][2];
            $cash_selling = $products[0][3];
            $cash_item_total = 300;
            $cash_item_profit = 60;
            mysqli_stmt_bind_param($item_stmt, 'iisddddd', $cash_sale_id, $product_ids[0], $cash_product_name, $cash_qty, $cash_buying, $cash_selling, $cash_item_total, $cash_item_profit);
            mysqli_stmt_execute($item_stmt);
            mysqli_stmt_bind_param($stock_stmt, 'di', $cash_qty, $product_ids[0]);
            mysqli_stmt_execute($stock_stmt);

            $credit_method = 'credit';
            $credit_qty = 1;
            $credit_subtotal = 220;
            $credit_total = 220;
            $credit_profit = 40;
            mysqli_stmt_bind_param($sale_stmt, 'iisdddd', $recorded_by, $credit_customer_id, $credit_method, $credit_subtotal, $cash_tax, $credit_total, $credit_profit);
            mysqli_stmt_execute($sale_stmt);
            $credit_sale_id = mysqli_insert_id($conn);
            $credit_product_name = $products[1][0];
            $credit_buying = $products[1][2];
            $credit_selling = $products[1][3];
            mysqli_stmt_bind_param($item_stmt, 'iisddddd', $credit_sale_id, $product_ids[1], $credit_product_name, $credit_qty, $credit_buying, $credit_selling, $credit_total, $credit_profit);
            mysqli_stmt_execute($item_stmt);
            mysqli_stmt_bind_param($stock_stmt, 'di', $credit_qty, $product_ids[1]);
            mysqli_stmt_execute($stock_stmt);

            mysqli_commit($conn);
            $message = 'Sample workspace loaded: products, low stock, cash sale, credit sale, customer balances, and expense are ready.';
        } elseif ($action === 'reset') {
            demo_query("DELETE FROM credit_payments WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'DEMO - %')");
            demo_query("DELETE FROM sale_items WHERE sale_id IN (SELECT id FROM sales WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'DEMO - %'))");
            demo_query("DELETE FROM sales WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'DEMO - %')");
            demo_query("DELETE FROM expenses WHERE description LIKE 'DEMO - %'");
            demo_query("DELETE FROM products WHERE product_name LIKE 'DEMO - %'");
            demo_query("DELETE FROM customers WHERE name LIKE 'DEMO - %'");
            mysqli_commit($conn);
            $message = 'Sample records removed. Your original records were left untouched.';
        }
    } catch (Throwable $exception) {
        mysqli_rollback($conn);
        $message = 'Test data action failed: ' . $exception->getMessage();
        $message_type = 'danger';
    }
}

$counts = [];
foreach (['products', 'customers', 'sales', 'sale_items', 'expenses'] as $table) {
    $row = mysqli_fetch_assoc(demo_query("SELECT COUNT(*) AS total FROM {$table}"));
    $counts[$table] = (int)$row['total'];
}
$demo_products = (int)mysqli_fetch_assoc(demo_query("SELECT COUNT(*) AS total FROM products WHERE product_name LIKE 'DEMO - %'"))['total'];
$demo_sales = (int)mysqli_fetch_assoc(demo_query("SELECT COUNT(*) AS total FROM sales WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'DEMO - %')"))['total'];
$demo_customers = (int)mysqli_fetch_assoc(demo_query("SELECT COUNT(*) AS total FROM customers WHERE name LIKE 'DEMO - %'"))['total'];

require_once 'layout_header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="card" style="border-left:4px solid var(--accent);">
  <div class="card-title">Founder QA</div>
  <p style="color:var(--muted);max-width:760px;">Use this private admin page as your launch checklist. Load a temporary sample workspace when testing, then remove it before recording or serving a real shop.</p>
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;">
    <form method="POST"><input type="hidden" name="action" value="seed"><button class="btn btn-success" type="submit">Load Sample Workspace</button></form>
    <form method="POST" onsubmit="return confirm('Remove all sample records? Your original records will remain.');"><input type="hidden" name="action" value="reset"><button class="btn btn-ghost" type="submit">Clear Sample Records</button></form>
  </div>
</div>

<div class="stats-grid" style="margin-top:20px;">
  <div class="stat-card"><div class="stat-icon green">✓</div><div class="stat-info"><p>Database</p><h3>Connected</h3><small>MySQL is responding</small></div></div>
  <div class="stat-card"><div class="stat-icon blue">📦</div><div class="stat-info"><p>Products</p><h3><?= $counts['products'] ?></h3><small><?= $demo_products ?> sample records</small></div></div>
  <div class="stat-card"><div class="stat-icon purple">🧾</div><div class="stat-info"><p>Sales</p><h3><?= $counts['sales'] ?></h3><small><?= $demo_sales ?> sample sales</small></div></div>
  <div class="stat-card"><div class="stat-icon yellow">📒</div><div class="stat-info"><p>Customers</p><h3><?= $counts['customers'] ?></h3><small><?= $demo_customers ?> sample customers</small></div></div>
</div>

<div class="dashboard-panels" style="margin-top:20px;">
  <div class="card">
    <div class="card-title">Test workflows</div>
    <div style="display:grid;gap:10px;">
      <a class="btn btn-success" href="pos.php">1. Complete a sale</a>
      <a class="btn btn-primary" href="add_product.php">2. Add a product</a>
      <a class="btn btn-warning" href="customers.php">3. Record a credit payment</a>
      <a class="btn btn-ghost" href="view_products.php">4. Check inventory and low stock</a>
      <a class="btn btn-ghost" href="daily_report.php">5. Review the daily report</a>
      <a class="btn btn-ghost" href="sales_history.php">6. Open sales history and receipt</a>
      <a class="btn btn-ghost" href="settings.php">7. Finish profile and security setup</a>
    </div>
  </div>
  <div class="card">
    <div class="card-title">Launch checks</div>
    <ul style="display:grid;gap:12px;padding-left:20px;color:var(--muted);">
      <li>Change the default admin password.</li>
      <li>Set shop name, phone, address, currency, and receipt footer.</li>
      <li>Add your real products and opening stock.</li>
      <li>Create cashier accounts and test permissions.</li>
      <li>Complete one cash, M-Pesa, and credit sale.</li>
      <li>Print or save a receipt from sales history.</li>
      <li>Confirm daily, monthly, and profit reports.</li>
      <li>Back up the database before public deployment.</li>
    </ul>
  </div>
</div>

<?php require_once 'layout_footer.php'; ?>
