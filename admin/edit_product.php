<?php
require_once __DIR__ . '/../includes/db.php';

if (isset($_POST['update_product'])) {
    $id = $_POST['product_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];

    $query = "UPDATE products SET name = ?, description = ?, price = ?, category_id = ?";
    $params = [$name, $description, $price, $category_id];

    // Check for image
    if (!empty($_FILES['image']['name'])) {
        $target = "../uploads/" . time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
        $query .= ", image = ?";
        $params[] = $target;
    }

    $query .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $pdo->prepare($query);
    if ($stmt->execute($params)) {
        header("Location: dashboard.php?msg=Product+Updated");
    } else {
        echo "Error updating product";
    }
}
?>
