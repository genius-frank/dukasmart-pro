<?php
require_once 'auth.php';
require_once 'db.php';
if (($_SESSION['role'] ?? '') !== 'admin') { require_once __DIR__ . '/db.php'; redirect_to('dashboard.php'); }

$id = (int)($_GET['id'] ?? 0);
if ($id && $id != $_SESSION['user_id']) {
    mysqli_query($conn, "UPDATE users SET is_active = 1 - is_active WHERE id=$id");
}
require_once __DIR__ . '/db.php';
redirect_to('settings.php?msg=User+status+updated&type=success');
