<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

$product_id = (int)($_GET['id'] ?? 0);
if ($product_id <= 0) { header("Location: my_shop.php"); exit(); }

// Verify ownership
$stmt = $conn->prepare("SELECT product_image FROM products WHERE product_id = ? AND seller_id = ?");
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['flash_error'] = "Product not found or access denied.";
    header("Location: my_shop.php");
    exit();
}

// Delete image file
if ($product['product_image'] && file_exists('uploads/'.$product['product_image'])) {
    unlink('uploads/'.$product['product_image']);
}

// Delete from database
$del = $conn->prepare("DELETE FROM products WHERE product_id = ? AND seller_id = ?");
$del->bind_param("ii", $product_id, $user_id);
$del->execute();
$del->close();

$_SESSION['flash_success'] = "Product deleted.";
header("Location: my_shop.php");
exit();