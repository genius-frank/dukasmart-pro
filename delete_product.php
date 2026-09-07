<?php
// delete_product.php
require_once 'auth.php';
require_once 'db.php';
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    // Soft delete (keep for sales history)
    $stmt = mysqli_prepare($conn, "UPDATE products SET is_active=0 WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
}
require_once __DIR__ . '/db.php';
redirect_to('view_products.php?msg=Product+deleted&type=success');
