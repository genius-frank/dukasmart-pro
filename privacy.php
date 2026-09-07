<?php
require_once 'db.php';
$settings = get_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Privacy - DukaSmart Pro</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box" style="max-width:680px;text-align:left;">
    <div class="login-logo" style="text-align:center;">
      <h1>DukaSmart Pro Privacy</h1>
      <p>Simple information about your shop data</p>
    </div>
    <h3>What the app stores</h3>
    <p class="text-muted mt-2">The app stores shop settings, user accounts, products, sales, receipts, customers, credit payments, expenses, and reports needed to operate the shop.</p>
    <h3 style="margin-top:24px;">Who can see it</h3>
    <p class="text-muted mt-2">Shop data is intended for the shop owner and authorized staff accounts. Passwords are stored as secure hashes, not plain text. Do not share accounts or passwords.</p>
    <h3 style="margin-top:24px;">Your responsibility</h3>
    <p class="text-muted mt-2">Shop owners must use accurate information, protect staff credentials, and request account removal or correction from the administrator.</p>
    <h3 style="margin-top:24px;">Contact</h3>
    <p class="text-muted mt-2">Before public launch, replace this section with your real support contact and the final legal privacy policy for your business.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:24px;">
      <a class="btn btn-primary" href="index.php">Home</a>
      <a class="btn btn-ghost" href="support.php">Support</a>
    </div>
  </div>
</div>
</body>
</html>
