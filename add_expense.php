<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $desc = clean($_POST['description'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $category = clean($_POST['category'] ?? 'General');
    $user_id = (int)$_SESSION['user_id'];

    if ($desc && $amount > 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO expenses (description, amount, category, recorded_by) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "sdsi", $desc, $amount, $category, $user_id);
        mysqli_stmt_execute($stmt);
    }
}
require_once __DIR__ . '/db.php';
redirect_to('expenses.php?msg=Expense+recorded&type=success');
