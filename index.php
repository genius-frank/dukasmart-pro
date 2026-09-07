<?php
session_start();
require_once __DIR__ . '/db.php';
if (isset($_SESSION['user_id'])) {
    redirect_to('dashboard.php');
}
?>
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DukaSmart Pro - Smart POS for Growing Shops</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-page">
    <div class="login-box" style="max-width:560px;text-align:center;">
        <div class="login-logo">
            <h1>DukaSmart Pro</h1>
            <p>Smart POS for your shop</p>
        </div>
        <p style="color:var(--muted);font-size:16px;line-height:1.7;margin:20px 0;">
            Manage sales, stock, credit customers, expenses, receipts, and business reports from one simple shop system.
        </p>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;text-align:left;color:var(--muted);margin:24px 0;">
            <div>✓ Fast point of sale</div><div>✓ Stock alerts</div>
            <div>✓ Credit book</div><div>✓ Profit reports</div>
            <div>✓ Printable receipts</div><div>✓ Phone-friendly PWA</div>
        </div>
        <a class="btn btn-primary btn-block btn-lg" href="login.php">Sign in to your shop</a>
        <p class="text-center text-muted mt-2" style="font-size:12px;">Access is created for each shop after purchase. Built for independent shops and growing businesses.</p>
        <p class="text-center mt-2" style="font-size:12px;"><a href="support.php">Support</a> · <a href="privacy.php">Privacy</a></p>
    </div>
</div>
</body>
</html>
