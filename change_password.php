<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { require_once __DIR__ . '/db.php'; redirect_to('settings.php'); }

$current = $_POST['current_password'] ?? '';
$new     = $_POST['new_password']     ?? '';
$confirm = $_POST['confirm_password'] ?? '';
$uid     = (int)$_SESSION['user_id'];

$r    = mysqli_prepare($conn, "SELECT password FROM users WHERE id=?");
mysqli_stmt_bind_param($r, "i", $uid);
mysqli_stmt_execute($r);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($r));

if (!$user || !password_verify($current, $user['password'])) {
    redirect_to('settings.php?msg=' . urlencode('Current password is wrong') . '&type=danger#security');
}
if ($new !== $confirm) {
    redirect_to('settings.php?msg=' . urlencode('New passwords do not match') . '&type=danger#security');
}
if (strlen($new) < 6) {
    redirect_to('settings.php?msg=' . urlencode('Password must be at least 6 characters') . '&type=danger#security');
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$upd  = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
mysqli_stmt_bind_param($upd, "si", $hash, $uid);
mysqli_stmt_execute($upd);

redirect_to('settings.php?msg=' . urlencode('Password changed! The security warning is now gone.') . '&type=success#security');
