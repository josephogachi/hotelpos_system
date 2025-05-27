<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = $_POST['category_id'];
    $name        = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price       = floatval($_POST['price']);

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageTmpPath = $_FILES['image']['tmp_name'];
        $imageName    = basename($_FILES['image']['name']);
        $imageExt     = pathinfo($imageName, PATHINFO_EXTENSION);
        $newImageName = uniqid('prod_', true) . '.' . $imageExt;
        $uploadPath   = __DIR__ . '/../uploads/' . $newImageName;

        // Move file
        if (move_uploaded_file($imageTmpPath, $uploadPath)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, image) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$category_id, $name, $description, $price, $newImageName]);
                $_SESSION['success'] = "Product added successfully.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Database error: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Failed to upload image.";
        }
    } else {
        $_SESSION['error'] = "Please upload a valid image.";
    }

    header("Location: products.php");
    exit;
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: products.php");
    exit;
}
