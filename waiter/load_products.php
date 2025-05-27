<?php
require_once __DIR__ . '/../includes/db.php';

$categoryId = $_GET['category_id'] ?? null;
if ($categoryId) {
    $productStmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ?");
    $productStmt->execute([$categoryId]);
} else {
    $productStmt = $pdo->query("SELECT * FROM products");
}
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $product): ?>
    <div class="product-card">
        <div class="product-img-container">
            <img src="../uploads/<?= htmlspecialchars($product['image'] ?? 'placeholder.jpg') ?>" class="product-img" alt="<?= htmlspecialchars($product['name']) ?>">
            <?php if ($product['is_popular']): ?>
                <div class="product-badge">Popular</div>
            <?php endif; ?>
        </div>
        <div class="product-content">
            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
            <div class="product-description"><?= htmlspecialchars($product['description']) ?></div>
            <div class="product-footer">
                <div class="product-price">Ksh <?= number_format($product['price'], 2) ?></div>
            </div>
            <div class="qty-control">
                <button class="qty-btn minus">−</button>
                <span class="qty-value">1</span>
                <button class="qty-btn plus">+</button>
            </div>
            <button class="add-to-order">
                <i class="fas fa-plus"></i>
                Add to Order
            </button>
        </div>
    </div>
<?php endforeach;