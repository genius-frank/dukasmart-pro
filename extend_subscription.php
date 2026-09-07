<?php
// ================================================================
// Frank's admin tool — extend a shop's subscription after payment
// Usage: visit this URL on the shop's server:
//   http://localhost:8080/duka_smart_pro/extend_subscription.php?days=30&key=DUKASMART2024
// Set DUKA_SUBSCRIPTION_KEY in the hosting environment.
// ================================================================
require_once 'db.php';

$secret_key = getenv('DUKA_SUBSCRIPTION_KEY') ?: '';

$key  = $_GET['key']  ?? '';
$days = (int)($_GET['days'] ?? 30);

if ($secret_key === '' || !hash_equals($secret_key, $key)) {
    http_response_code(403);
    die("❌ Unauthorized");
}

$new_date = date('Y-m-d', strtotime("+{$days} days"));
$current  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT subscription_expires FROM settings LIMIT 1"));

// If subscription hasn't expired yet, extend from expiry date (not today)
$start_from = ($current['subscription_expires'] && $current['subscription_expires'] > date('Y-m-d'))
    ? $current['subscription_expires']
    : date('Y-m-d');

$new_expiry = date('Y-m-d', strtotime($start_from . " +{$days} days"));
mysqli_query($conn, "UPDATE settings SET subscription_expires='$new_expiry' WHERE id=1");

echo "✅ Subscription extended by {$days} days. New expiry: {$new_expiry}";
