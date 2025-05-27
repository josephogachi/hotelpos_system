<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Check if ID is provided and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid discount ID.";
    header("Location: discount.php");
    exit;
}

$discount_id = (int) $_GET['id'];

try {
    // Prepare and execute delete
    $stmt = $pdo->prepare("DELETE FROM discounts WHERE id = ?");
    $stmt->execute([$discount_id]);

    if ($stmt->rowCount()) {
        $_SESSION['success'] = "Discount deleted successfully.";
    } else {
        $_SESSION['error'] = "Discount not found or already deleted.";
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

// Redirect back to the discount page
header("Location: discount.php");
exit;
