<?php
$db_host = getenv('DUKA_DB_HOST') ?: 'localhost';
$db_user = getenv('DUKA_DB_USER') ?: 'root';
$db_pass = getenv('DUKA_DB_PASS') ?: ''; // Local XAMPP fallback; set environment variables in production.
$db_name = getenv('DUKA_DB_NAME') ?: 'duka_smart';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("<div style='font-family:Arial;padding:40px;color:red;'>
        <h2>⚠️ Database Connection Failed</h2>
        <p>" . mysqli_connect_error() . "</p>
        <p>Make sure XAMPP MySQL is running and you have imported schema.sql</p>
    </div>");
}
mysqli_set_charset($conn, "utf8mb4");

function get_settings() { // NOSONAR
    global $conn;
    $r = mysqli_query($conn, "SELECT * FROM settings LIMIT 1");
    return mysqli_fetch_assoc($r) ?: ['shop_name'=>'DukaSmart','currency'=>'KES'];
}

function fmt($amount) {
    $s = get_settings();
    return $s['currency'] . ' ' . number_format((float)$amount, 2);
}

function clean($val) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($val))));
}

function low_stock_count() { // NOSONAR
    global $conn;
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM products WHERE quantity <= low_stock_alert AND is_active=1");
    return (int)mysqli_fetch_assoc($r)['c'];
}

function customer_balance($customer_id) { // NOSONAR
    global $conn;
    $cid  = (int)$customer_id;
    $owed = (float)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) t FROM sales WHERE customer_id=$cid AND payment_method='credit'"))['t'];
    $paid = (float)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) t FROM credit_payments WHERE customer_id=$cid"))['t'];
    return $owed - $paid;
}

function credit_customers_count() { // NOSONAR
    global $conn;
    $r = mysqli_query($conn, "SELECT c.id,
        (COALESCE((SELECT SUM(total) FROM sales WHERE customer_id=c.id AND payment_method='credit'),0)
         - COALESCE((SELECT SUM(amount) FROM credit_payments WHERE customer_id=c.id),0)) bal
        FROM customers c HAVING bal > 0");
    return (int)mysqli_num_rows($r);
}

function total_credit_outstanding() { // NOSONAR
    global $conn;
    $owed = (float)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) t FROM sales WHERE payment_method='credit'"))['t'];
    $paid = (float)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) t FROM credit_payments"))['t'];
    return $owed - $paid;
}

function current_plan_code() {
    $settings = get_settings();
    return in_array($settings['plan_code'] ?? 'starter', ['starter', 'growth', 'pro'], true)
        ? $settings['plan_code']
        : 'starter';
}

function plan_limits() {
    return [
        'starter' => ['products' => 100, 'cashiers' => 1],
        'growth' => ['products' => 1000, 'cashiers' => 5],
        'pro' => ['products' => 10000, 'cashiers' => 25],
    ];
}

function product_limit_reached() {
    global $conn;
    $limits = plan_limits();
    $limit = $limits[current_plan_code()]['products'];
    $count = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM products WHERE is_active=1"))['total'];
    return [$count >= $limit, $count, $limit];
}

function app_base_path() {
    $uri = $_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';

    if ($path === '/' || $path === '') {
        return '';
    }

    if (str_ends_with($uri, '/')) {
        return rtrim($path, '/');
    }

    $segments = explode('/', trim($path, '/'));
    if (count($segments) <= 1) {
        return '';
    }
    array_pop($segments);
    return '/' . implode('/', $segments);
}

function app_url($path = '') {
    $path = '/' . ltrim((string)$path, '/');
    $base = app_base_path();
    return $base === '' ? $path : $base . $path;
}

function redirect_to($path) {
    $url = app_url($path);
    header('Location: ' . $url, true, 302);
    exit();
}
