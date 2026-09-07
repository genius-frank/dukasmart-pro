<?php
require_once 'auth.php';
require_once 'db.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    redirect_to('dashboard.php');
}

$user_id = (int)($_POST['user_id'] ?? 0);
$new_password = $_POST['new_password'] ?? '';

if ($user_id <= 0 || strlen($new_password) < 6) {
    redirect_to('settings.php?msg=' . urlencode('Choose a user and enter a password of at least 6 characters.') . '&type=danger#team');
}

$stmt = mysqli_prepare($conn, 'SELECT id, username, full_name FROM users WHERE id=? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {
    redirect_to('settings.php?msg=' . urlencode('That user account was not found.') . '&type=danger#team');
}

$hash = password_hash($new_password, PASSWORD_DEFAULT);
$update = mysqli_prepare($conn, 'UPDATE users SET password=? WHERE id=?');
mysqli_stmt_bind_param($update, 'si', $hash, $user_id);
mysqli_stmt_execute($update);

redirect_to('settings.php?msg=' . urlencode('Password reset for ' . $user['full_name'] . '. Share it securely after verifying their identity.') . '&type=success#team');
