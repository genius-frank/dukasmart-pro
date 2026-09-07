<?php
require_once 'auth.php';
require_once 'db.php';
if (($_SESSION['role'] ?? '') !== 'admin') { require_once __DIR__ . '/db.php'; redirect_to('dashboard.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name      = clean($_POST['shop_name']      ?? '');
    $shop_address   = clean($_POST['shop_address']   ?? '');
    $shop_phone     = clean($_POST['shop_phone']     ?? '');
    $currency       = clean($_POST['currency']       ?? 'KES');
    $tax_rate       = (float)($_POST['tax_rate']     ?? 0);
    $receipt_footer = clean($_POST['receipt_footer'] ?? '');

    $stmt = mysqli_prepare($conn,
        "UPDATE settings SET shop_name=?,shop_address=?,shop_phone=?,currency=?,tax_rate=?,receipt_footer=? WHERE id=1");
    mysqli_stmt_bind_param($stmt, "ssssds",
        $shop_name, $shop_address, $shop_phone, $currency, $tax_rate, $receipt_footer);
    mysqli_stmt_execute($stmt);
}
redirect_to('settings.php?msg=' . urlencode('Settings saved! SMS alerts will go to your shop phone.') . '&type=success');
