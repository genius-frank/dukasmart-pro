<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'sms.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
    require_once __DIR__ . '/db.php';
    redirect_to('pos.php');
}

$s              = get_settings();
$payment_method = clean($_POST['payment_method'] ?? 'cash');
$mpesa_ref      = clean($_POST['mpesa_ref']       ?? '');
$cashier_id     = (int)$_SESSION['user_id'];
$tax_rate       = (float)($s['tax_rate'] ?? 0);

// Credit customer handling
$customer_id        = null;
$selected_customer  = $_POST['customer_id'] ?? '';
$new_customer_name  = clean($_POST['new_customer_name']  ?? '');
$new_customer_phone = clean($_POST['new_customer_phone'] ?? '');

if ($payment_method === 'credit') {
    if ($selected_customer === '__new__') {
        if (!$new_customer_name) {
            $_SESSION['cart_error'] = "Please enter a customer name for credit sales.";
            redirect_to('pos.php');
        }
    } elseif (!(int)$selected_customer) {
        $_SESSION['cart_error'] = "Please select a customer for credit sales.";
        redirect_to('pos.php');
    } else {
        $customer_id = (int)$selected_customer;
    }
}

$cart     = $_SESSION['cart'];
$subtotal = array_sum(array_column($cart, 'total'));
$tax      = $subtotal * ($tax_rate / 100);
$total    = $subtotal + $tax;
$profit   = array_sum(array_map(fn($i) => ($i['selling_price'] - $i['buying_price']) * $i['qty'], $cart));

mysqli_begin_transaction($conn);
try {
    // Create new customer if needed
    if ($payment_method === 'credit' && $selected_customer === '__new__') {
        $cu = mysqli_prepare($conn, "INSERT INTO customers (name,phone) VALUES (?,?)");
        mysqli_stmt_bind_param($cu, "ss", $new_customer_name, $new_customer_phone);
        mysqli_stmt_execute($cu);
        $customer_id = mysqli_insert_id($conn);
    }

    // Insert sale header
    $stmt = mysqli_prepare($conn,
        "INSERT INTO sales (cashier_id,customer_id,payment_method,mpesa_ref,subtotal,tax,total,profit)
         VALUES (?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "iissdddd",
        $cashier_id, $customer_id, $payment_method, $mpesa_ref,
        $subtotal, $tax, $total, $profit);
    mysqli_stmt_execute($stmt);
    $sale_id = mysqli_insert_id($conn);

    // Insert line items and deduct stock
    $low_stock_alerts = []; // collect items that hit low stock
    foreach ($cart as $item) {
        $pid     = (int)$item['product_id'];
        $qty     = (float)$item['qty'];
        $bp      = (float)$item['buying_price'];
        $sp      = (float)$item['selling_price'];
        $ltotal  = $qty * $sp;
        $lprofit = ($sp - $bp) * $qty;
        $pname   = $item['product_name'];

        // Check stock
        $chk = mysqli_query($conn, "SELECT quantity, low_stock_alert, unit_type FROM products WHERE id=$pid FOR UPDATE");
        $row = mysqli_fetch_assoc($chk);
        if (!$row || $row['quantity'] < $qty) {
            throw new Exception("Insufficient stock for: $pname");
        }

        // Insert line item — iisddddd = 8 chars, 8 vars
        $si = mysqli_prepare($conn,
            "INSERT INTO sale_items (sale_id,product_id,product_name,quantity_sold,buying_price,selling_price,total,profit)
             VALUES (?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($si, "iisddddd",
            $sale_id, $pid, $pname, $qty, $bp, $sp, $ltotal, $lprofit);
        mysqli_stmt_execute($si);

        // Deduct stock
        $upd = mysqli_prepare($conn, "UPDATE products SET quantity=quantity-? WHERE id=?");
        mysqli_stmt_bind_param($upd, "di", $qty, $pid);
        mysqli_stmt_execute($upd);

        // Check new quantity — send SMS alert if at or below threshold
        $new_qty = $row['quantity'] - $qty;
        if ($new_qty <= $row['low_stock_alert']) {
            $low_stock_alerts[] = [
                'name'      => $pname,
                'quantity'  => $new_qty,
                'unit_type' => $row['unit_type'],
            ];
        }
    }

    mysqli_commit($conn);
    $_SESSION['cart'] = [];

    // Send SMS alerts AFTER committing (non-blocking)
    foreach ($low_stock_alerts as $alert) {
        send_low_stock_alert($alert['name'], $alert['quantity'], $alert['unit_type']);
    }

    redirect_to("receipt.php?id=$sale_id");

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['cart_error'] = $e->getMessage();
    redirect_to('pos.php');
}
