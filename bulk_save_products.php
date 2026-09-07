<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('bulk_products.php');
}

$rows = preg_split('/\r\n|\r|\n/', trim($_POST['product_csv'] ?? ''));
$units = ['piece', 'kg', 'g', 'liter', 'ml', 'box', 'dozen', 'pack'];
$category_ids = [];
$category_result = mysqli_query($conn, 'SELECT id, name FROM categories');
while ($category = mysqli_fetch_assoc($category_result)) {
    $category_ids[strtolower(trim($category['name']))] = (int)$category['id'];
}

[$at_limit, $product_count, $product_limit] = product_limit_reached();
$remaining = $product_limit - $product_count;
if ($remaining <= 0) {
    redirect_to('bulk_products.php?msg=' . urlencode('Your plan product limit has been reached.') . '&type=warning');
}

$stmt = mysqli_prepare($conn, 'INSERT INTO products (product_name, category_id, unit_type, buying_price, selling_price, quantity, low_stock_alert) VALUES (?, ?, ?, ?, ?, ?, ?)');
$imported = 0;
$skipped = 0;
foreach ($rows as $row) {
    if ($imported >= $remaining || trim($row) === '') {
        if (trim($row) !== '') $skipped++;
        continue;
    }
    $columns = str_getcsv($row);
    if (count($columns) < 7) {
        $skipped++;
        continue;
    }

    $name = trim($columns[0]);
    $category_name = strtolower(trim($columns[1]));
    $unit = strtolower(trim($columns[2]));
    $buying_price = (float)$columns[3];
    $selling_price = (float)$columns[4];
    $quantity = (float)$columns[5];
    $low_stock_alert = max(1, (int)$columns[6]);
    $category_id = $category_ids[$category_name] ?? 1;

    if ($name === '' || !in_array($unit, $units, true) || $selling_price <= 0 || $buying_price < 0 || $quantity < 0) {
        $skipped++;
        continue;
    }

    mysqli_stmt_bind_param($stmt, 'sisdddi', $name, $category_id, $unit, $buying_price, $selling_price, $quantity, $low_stock_alert);
    if (mysqli_stmt_execute($stmt)) {
        $imported++;
    } else {
        $skipped++;
    }
}

$message = "Imported {$imported} product" . ($imported === 1 ? '' : 's') . ".";
if ($skipped > 0) $message .= " Skipped {$skipped} invalid or over-limit row" . ($skipped === 1 ? '' : 's') . ".";
redirect_to('view_products.php?msg=' . urlencode($message) . '&type=' . ($imported > 0 ? 'success' : 'warning'));
