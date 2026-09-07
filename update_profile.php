<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { require_once __DIR__ . '/db.php'; redirect_to('settings.php'); }

$full_name = clean($_POST['full_name'] ?? '');
$username  = clean($_POST['username'] ?? '');
$user_id   = (int)$_SESSION['user_id'];

if (!$full_name || !$username) {
    redirect_to('settings.php?msg=Name+and+username+are+required&type=danger');
}

// Check username not taken by someone else
$chk = mysqli_prepare($conn, "SELECT id FROM users WHERE username=? AND id != ?");
mysqli_stmt_bind_param($chk, "si", $username, $user_id);
mysqli_stmt_execute($chk);
if (mysqli_fetch_assoc(mysqli_stmt_get_result($chk))) {
    redirect_to('settings.php?msg=' . urlencode('Username already taken by another user') . '&type=danger');
}

$stmt = mysqli_prepare($conn, "UPDATE users SET full_name=?, username=? WHERE id=?");
mysqli_stmt_bind_param($stmt, "ssi", $full_name, $username, $user_id);
mysqli_stmt_execute($stmt);

// Update session so sidebar name changes immediately
$_SESSION['full_name'] = $full_name;
$_SESSION['username']  = $username;

redirect_to('settings.php?msg=' . urlencode('Profile updated! Your name now shows correctly everywhere.') . '&type=success');
