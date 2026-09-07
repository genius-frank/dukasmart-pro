<?php
require_once __DIR__ . '/db.php';
$settings     = get_settings();
$low_stock    = low_stock_count();
$credit_count = credit_customers_count();
$initials     = strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1));
$role         = $_SESSION['role'] ?? 'cashier';
$current      = basename($_SERVER['PHP_SELF']);

function nav_link($file, $icon, $label, $current, $badge = 0) { // NOSONAR
    $active = ($current === $file) ? 'active' : '';
    $b      = $badge ? "<span class='badge'>{$badge}</span>" : '';
    echo "<a href='{$file}' class='{$active}'><span class='icon'>{$icon}</span> {$label} {$b}</a>";
}

// Check if user is STILL using the default password (any username)
// Warning disappears the moment they change it in Settings
$show_pw_warning = false;
$pw_stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id=?");
$uid = (int)$_SESSION['user_id'];
mysqli_stmt_bind_param($pw_stmt, "i", $uid);
mysqli_stmt_execute($pw_stmt);
$pw_row = mysqli_fetch_assoc(mysqli_stmt_get_result($pw_stmt));
if ($pw_row && password_verify('admin123', $pw_row['password'])) {
    $show_pw_warning = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title ?? 'DukaSmart Pro') ?> — DukaSmart Pro</title>
  <link rel="stylesheet" href="style.css">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#1e3a8a">
  <meta name="apple-mobile-web-app-capable" content="yes">
</head>
<body>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <h2>🛒 DukaSmart Pro</h2>
    <p><?= htmlspecialchars($settings['shop_name']) ?></p>
  </div>
  <nav>
    <div class="nav-label">Main</div>
    <?php nav_link('dashboard.php',     '📊', 'Dashboard',     $current) ?>
    <?php nav_link('pos.php',           '🛒', 'Point of Sale', $current) ?>

    <div class="nav-label">Inventory</div>
    <?php nav_link('view_products.php', '📦', 'Products',      $current) ?>
    <?php nav_link('add_product.php',   '➕', 'Add Product',   $current) ?>
    <?php nav_link('low_stock.php',     '⚠️', 'Low Stock',     $current, $low_stock) ?>

    <div class="nav-label">Sales</div>
    <?php nav_link('sales_history.php', '🧾', 'Sales History', $current) ?>
    <?php nav_link('customers.php',     '📒', 'Credit Book',   $current, $credit_count) ?>
    <?php nav_link('expenses.php',      '💸', 'Expenses',      $current) ?>

    <div class="nav-label">Reports</div>
    <?php nav_link('daily_report.php',   '📅', 'Daily Report',   $current) ?>
    <?php nav_link('monthly_report.php', '📆', 'Monthly Report', $current) ?>
    <?php nav_link('profit_report.php',  '💰', 'Profit Report',  $current) ?>

    <?php if ($role === 'admin'): ?>
    <div class="nav-label">Admin</div>
    <?php nav_link('founder_test.php', '🧪', 'Founder QA', $current) ?>
    <?php nav_link('billing.php', '💳', 'Billing & Plans', $current) ?>
    <?php nav_link('settings.php', '⚙️', 'Settings', $current) ?>
    <?php endif; ?>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= $initials ?></div>
      <div class="sidebar-user-info">
        <p><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></p>
        <p><?= ucfirst($role) ?></p>
      </div>
    </div>
    <a href="logout.php" style="display:block;margin-top:10px;color:rgba(255,255,255,0.45);font-size:12px;text-decoration:none;">🚪 Sign Out</a>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:14px;">
      <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
      <a href="javascript:history.back()" style="color:var(--muted);text-decoration:none;font-size:22px;line-height:1;display:<?= $current==='dashboard.php'?'none':'block' ?>;" title="Go back">←</a>
      <h1><?= htmlspecialchars($page_title ?? 'Dashboard') ?></h1>
    </div>
    <div class="topbar-actions">
      <button class="theme-toggle" type="button" aria-label="Toggle theme">🌙</button>
      <span class="connection-status" id="connection-status" title="Connection status">● Online</span>
      <a href="pos.php" class="btn btn-success btn-sm">🛒 New Sale</a>
      <span style="color:var(--muted);font-size:12px;."><?= date('D, d M Y') ?></span>
    </div>
  </div>

  <!-- Default password warning — disappears the moment you change password in Settings -->
  <?php if ($show_pw_warning): ?>
  <div class="alert alert-danger">
    🔒 <strong>Action required:</strong> You are still using the default password.
    Go to <a href="settings.php#security" style="color:inherit;font-weight:700;text-decoration:underline;">Settings → Change Password</a>
    and also update your name in
    <a href="settings.php" style="color:inherit;font-weight:700;text-decoration:underline;">Settings → Your Profile</a>.
    This warning disappears automatically once done.
  </div>
  <?php endif; ?>

  <?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-<?= htmlspecialchars($_GET['type'] ?? 'success') ?>">
    <?= htmlspecialchars($_GET['msg']) ?>
  </div>
  <?php endif; ?>

  <div id="install-banner" style="display:none;position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:var(--primary);color:white;padding:12px 20px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.2);z-index:999;align-items:center;gap:12px;font-size:13px;font-weight:500;">
    📱 Install DukaSmart on your phone!
    <button onclick="installApp()" class="btn btn-sm" style="background:#fff;color:var(--primary);">Install</button>
    <button onclick="document.getElementById('install-banner').style.display='none'" style="background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;font-size:18px;">×</button>
  </div>
