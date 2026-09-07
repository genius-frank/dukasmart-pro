<?php
require_once 'auth.php';
require_once 'db.php';
if (($_SESSION['role'] ?? '') !== 'admin') { require_once __DIR__ . '/db.php'; redirect_to('dashboard.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean($_POST['full_name'] ?? '');
    $username  = clean($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $limits = plan_limits();
    $cashier_limit = $limits[current_plan_code()]['cashiers'];
    $cashier_count = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM users WHERE role='cashier' AND is_active=1"))['total'];

    if ($cashier_count >= $cashier_limit) {
        redirect_to('settings.php?msg=' . urlencode('Your plan allows ' . $cashier_limit . ' active cashier account(s). Upgrade to add more.') . '&type=warning#team');
    }

    if ($full_name && $username && strlen($password) >= 6) {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username='" . mysqli_real_escape_string($conn, $username) . "'");
        if (mysqli_num_rows($check) > 0) {
            redirect_to('settings.php?msg=' . urlencode('Username already taken') . '&type=danger');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,'cashier')");
        mysqli_stmt_bind_param($stmt, "sss", $username, $hash, $full_name);
        mysqli_stmt_execute($stmt);
        redirect_to('settings.php?msg=' . urlencode("Cashier \"$full_name\" added!") . '&type=success');
    }
}
redirect_to('settings.php?msg=' . urlencode('Please fill all fields correctly') . '&type=danger');
