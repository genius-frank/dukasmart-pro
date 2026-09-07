<?php
require_once 'db.php';

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    if ($username === '') {
        $message = 'Enter your username so the shop administrator can verify your account.';
        $message_type = 'danger';
    } else {
        $message = 'Request received. Contact your shop administrator with your username and shop details. They must verify your identity before issuing a new password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Password Help - DukaSmart Pro</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <h1>DukaSmart Pro</h1>
      <p>Password recovery</p>
    </div>
    <?php if ($message): ?>
      <div class="alert alert-<?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <p style="color:var(--muted);line-height:1.7;margin-bottom:18px;">
      For account safety, password resets are verified by the shop administrator. This prevents another person from taking over a shop account.
    </p>
    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" autocomplete="username" required autofocus>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">Request Password Help</button>
    </form>
    <p class="text-center mt-2"><a href="login.php">Back to sign in</a></p>
  </div>
</div>
</body>
</html>
