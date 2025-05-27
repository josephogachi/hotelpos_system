<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Validate and sanitize input
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid product ID.";
    header("Location: dashboard.php");
    exit;
}

$product_id = intval($_GET['id']);

// Optional: Delete the product image file too
$stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if ($product && $product['image']) {
    $image_path = __DIR__ . '/../uploads/' . $product['image'];
    if (file_exists($image_path)) {
        unlink($image_path); // delete image
    }
}

// Delete from DB
$stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
$deleted = $stmt->execute([$product_id]);

if ($deleted) {
    $_SESSION['success'] = "Product deleted successfully.";
} else {
    $_SESSION['error'] = "Failed to delete product.";
}

header("Location: dashboard.php");
exit;
