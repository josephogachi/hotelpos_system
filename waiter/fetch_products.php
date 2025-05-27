<?php
require_once '../includes/db.php';

$categoryId = $_GET['category_id'] ?? null;

$query = $categoryId 
    ? $pdo->prepare("SELECT * FROM products WHERE category_id = ?")
    : $pdo->query("SELECT * FROM products");

$products = $categoryId 
    ? ($query->execute([$categoryId]) ? $query->fetchAll(PDO::FETCH_ASSOC) : [])
    : $query->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $product) {
    echo "
    <div class='product-card' data-name='{$product['name']}' data-price='{$product['price']}'>
        <img src='../assets/products/{$product['image']}' alt=''>
        <h6>{$product['name']}</h6>
        <p>{$product['description']}</p>
        <strong>Ksh {$product['price']}</strong>
        <button class='btn btn-sm btn-success add-to-order'>Add to Order</button>
    </div>
    ";
}
?>
