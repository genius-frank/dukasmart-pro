<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = clean($_POST['name'] ?? '');
    $phone   = clean($_POST['phone'] ?? '');
    $address = clean($_POST['address'] ?? '');

    if ($name) {
        $stmt = mysqli_prepare($conn, "INSERT INTO customers (name, phone, address) VALUES (?,?,?)");
        mysqli_stmt_bind_param($stmt, "sss", $name, $phone, $address);
        mysqli_stmt_execute($stmt);
        $new_id = mysqli_insert_id($conn);
        redirect_to('customer_detail.php?id=' . $new_id . '&msg=' . urlencode('Customer added!') . '&type=success');
        exit();
    }
}
redirect_to('add_customer.php?msg=' . urlencode('Name is required') . '&type=danger');
