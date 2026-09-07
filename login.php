<?php
session_start();
if (isset($_SESSION['user_id'])) {
  require_once __DIR__ . '/db.php';
  redirect_to('dashboard.php');
}
$error = $_GET['error'] ?? '';
$errors = [
    'invalid'  => '⚠️ Wrong username or password.',
    'inactive' => '⚠️ Your account has been deactivated.',
    'required' => '⚠️ Please enter username and password.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — DukaSmart Pro</title>
  <link rel="stylesheet" href="style.css">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#1e3a8a">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <h1>🛒 DukaSmart Pro</h1>
      <p>Secure access for your shop</p>
    </div>

    <?php if ($error && isset($errors[$error])): ?>
      <div class="alert alert-danger"><?= $errors[$error] ?></div>
    <?php endif; ?>

    <form method="POST" action="login_process.php">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Enter username"
               autocomplete="username" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter password"
               autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:8px;">
        Sign In →
      </button>
    </form>

    <p class="text-center mt-2"><a href="forgot_password.php">Forgot your password?</a></p>
    <p class="text-center text-muted mt-2" style="font-size:12px;line-height:1.6;">
      Your shop owner account is created after purchase. Use the username and temporary password supplied to you, then change the password in Settings.
    </p>
    <p class="text-center mt-2" style="font-size:12px;"><a href="support.php">Support</a> · <a href="privacy.php">Privacy</a></p>
  </div>
</div>
</body>
</html>
