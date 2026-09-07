<?php
require_once 'auth.php';
require_once 'db.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    redirect_to('dashboard.php');
}

$prices = ['starter' => 500, 'growth' => 900, 'pro' => 1500];
$plan_code = $_POST['plan_code'] ?? '';
$reference = trim($_POST['payment_reference'] ?? '');

if (!isset($prices[$plan_code]) || strlen($reference) < 4 || strlen($reference) > 80) {
    redirect_to('billing.php?msg=' . urlencode('Choose a plan and enter a valid payment reference.') . '&type=danger');
}

$amount = $prices[$plan_code];
$stmt = mysqli_prepare($conn, 'INSERT INTO subscription_payments (plan_code, amount, payment_reference) VALUES (?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'sds', $plan_code, $amount, $reference);
mysqli_stmt_execute($stmt);

redirect_to('billing.php?msg=' . urlencode('Payment reference submitted. Support will verify it and activate your plan.') . '&type=success');