<?php
require_once 'auth.php';
require_once 'db.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    redirect_to('dashboard.php');
}

$plans = ['starter', 'growth', 'pro'];
$plan_code = $_POST['plan_code'] ?? '';
if (!in_array($plan_code, $plans, true)) {
    redirect_to('billing.php?msg=' . urlencode('Please choose a valid plan.') . '&type=danger');
}

$stmt = mysqli_prepare($conn, 'UPDATE settings SET plan_code=? WHERE id=1');
mysqli_stmt_bind_param($stmt, 's', $plan_code);
mysqli_stmt_execute($stmt);

redirect_to('billing.php?msg=' . urlencode('Plan selected. Contact support to complete payment and activation.') . '&type=success');