<?php
// product_delete_admin.php
require_once 'functions.php';
requireAdminLogin();
global $conn;

// Only allow GET with id param or you can switch to POST if you prefer
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: products_admin.php');
    exit();
}



// Delete product; FK constraints will cascade to productvariant,
// productcategory, productgender, cart items, etc. as defined in your schema.[file:1]
$sql  = "DELETE FROM product WHERE product_id = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
}

// Optionally set a flash message in session
// $_SESSION['admin_success'] = 'Product deleted';

header('Location: products_admin.php');
exit();
