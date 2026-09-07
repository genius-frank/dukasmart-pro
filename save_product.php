<?php
require_once 'auth.php';
require_once 'db.php';
[$product_limit_reached] = product_limit_reached();
if ($product_limit_reached) {
    redirect_to('view_products.php?msg=' . urlencode('Your plan product limit has been reached. Upgrade to add more products.') . '&type=warning');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { require_once __DIR__ . '/db.php'; redirect_to('add_product.php'); }

$name      = clean($_POST['product_name'] ?? '');
$cat_id    = (int)($_POST['category_id']   ?? 1);
$unit      = clean($_POST['unit_type']      ?? 'piece');
$buy_price = (float)($_POST['buying_price'] ?? 0);
$sel_price = (float)($_POST['selling_price']?? 0);
$qty       = (float)($_POST['quantity']     ?? 0);
$alert     = (int)($_POST['low_stock_alert']?? 5);

if (!$name || $sel_price <= 0) {
    redirect_to('add_product.php?msg=Fill+all+required+fields&type=danger');
}

// s i s d d d i = 7 chars, 7 variables — verified
$stmt = mysqli_prepare($conn,
    "INSERT INTO products (product_name,category_id,unit_type,buying_price,selling_price,quantity,low_stock_alert)
     VALUES (?,?,?,?,?,?,?)");
mysqli_stmt_bind_param($stmt, "sisdddi", $name, $cat_id, $unit, $buy_price, $sel_price, $qty, $alert);

if (mysqli_stmt_execute($stmt)) {
    redirect_to('view_products.php?msg=' . urlencode("\"$name\" added!") . '&type=success');
} else {
    redirect_to('add_product.php?msg=' . urlencode('Error: ' . mysqli_error($conn)) . '&type=danger');
}
exit();
