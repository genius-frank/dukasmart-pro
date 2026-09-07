<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $amount      = (float)($_POST['amount'] ?? 0);
    $method      = clean($_POST['payment_method'] ?? 'cash');
    $note        = clean($_POST['note'] ?? '');
    $user_id     = (int)$_SESSION['user_id'];

    $balance = customer_balance($customer_id);

    if ($customer_id && $amount > 0 && $amount <= $balance + 0.01) {
        $stmt = mysqli_prepare($conn, "INSERT INTO credit_payments (customer_id, amount, payment_method, note, recorded_by) VALUES (?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "idssi", $customer_id, $amount, $method, $note, $user_id);
        mysqli_stmt_execute($stmt);
        redirect_to('customer_detail.php?id=' . $customer_id . '&msg=' . urlencode('Payment recorded!') . '&type=success');
        exit();
    }
    redirect_to('customer_detail.php?id=' . $customer_id . '&msg=' . urlencode('Invalid amount - cannot exceed balance owed') . '&type=danger');
    exit();
}
require_once __DIR__ . '/db.php';
redirect_to('customers.php');
