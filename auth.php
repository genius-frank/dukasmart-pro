<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
if (!isset($_SESSION['user_id'])) {
    redirect_to('login.php');
}
// Check subscription after auth
require_once __DIR__ . '/subscription.php';
check_subscription();
