<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('login.php');
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$username || !$password) {
    redirect_to('login.php?error=required');
}

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);

if (!$user || !password_verify($password, $user['password'])) {
    redirect_to('login.php?error=invalid');
}

if (!$user['is_active']) {
    redirect_to('login.php?error=inactive');
}

// Regenerate session ID to prevent fixation
session_regenerate_id(true);

$_SESSION['user_id']   = $user['id'];
$_SESSION['username']  = $user['username'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['role']      = $user['role'];

redirect_to('dashboard.php');
