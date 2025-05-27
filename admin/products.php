 
<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products - HotelPOS</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3>HotelPOS</h3>
        </div>
        <ul class="list-unstyled components">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="active"><a href="products.php"><i class="fas fa-utensils"></i> Manage Products</a></li>
            <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
            <li><a href="discount.php"><i class="fas fa-percentage"></i> Discounts</a></li>
            <li><a href="users.php"><i class="fas fa-users-cog"></i> Manage Users</a></li>
            <li><a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
            <li><a href="reports.php"><i class="fas fa-file-export"></i> Reports</a></li>
            <li><a href="ai.php"><i class="fas fa-cogs"></i> AI Tools</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Content -->
    <div id="content">
        <header>
            <h2>Manage Products</h2>
        </header>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <section class="actions">
            <button class="btn btn-primary" onclick="document.getElementById('addProductModal').style.display='block'">
                <i class="fas fa-plus-circle"></i> Add Product
            </button>
        </section>

        <section class="product-list">
            <h3>Product List</h3>
            <table>
                <thead>
                    <tr>
                        <th>Image</th><th>Name</th><th>Category</th><th>Description</th><th>Price (Ksh)</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><img src="../uploads/<?= htmlspecialchars($product['image']) ?>" height="50"></td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= htmlspecialchars($product['category_name']) ?></td>
                        <td><?= htmlspecialchars($product['description']) ?></td>
                        <td><?= number_format($product['price'], 2) ?></td>
                        <td>
                            <button class="edit-btn"
                                data-id="<?= $product['id'] ?>"
                                data-name="<?= htmlspecialchars($product['name']) ?>"
                                data-description="<?= htmlspecialchars($product['description']) ?>"
                                data-price="<?= $product['price'] ?>"
                                data-category="<?= $product['category_id'] ?>">
                                Edit
                            </button>
                            <a href="delete_product.php?id=<?= $product['id'] ?>" onclick="return confirm('Delete this product?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <footer>
            <p>&copy; <?= date('Y') ?> HotelPOS. All rights reserved.</p>
        </footer>
    </div>
</div>

<!-- Add Product Modal -->
<div id="addProductModal" class="modal">
    <div class="modal-content">
        <form action="add_product.php" method="POST" enctype="multipart/form-data">
            <h3>Add Product</h3>
            <label>Category:</label>
            <select name="category_id" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Image:</label>
            <input type="file" name="image" required>

            <label>Name:</label>
            <input type="text" name="name" required>

            <label>Description:</label>
            <textarea name="description" required></textarea>

            <label>Price (Ksh):</label>
            <input type="number" name="price" step="0.01" required>

            <div class="modal-actions">
                <button type="submit">Save</button>
                <button type="button" onclick="document.getElementById('addProductModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="modal">
    <div class="modal-content">
        <form id="editProductForm" action="edit_product.php" method="POST">
            <h3>Edit Product</h3>
            <input type="hidden" name="id" id="edit-id">

            <label>Category:</label>
            <select name="category_id" id="edit-category" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Name:</label>
            <input type="text" name="name" id="edit-name" required>

            <label>Description:</label>
            <textarea name="description" id="edit-description" required></textarea>

            <label>Price (Ksh):</label>
            <input type="number" name="price" id="edit-price" step="0.01" required>

            <div class="modal-actions">
                <button type="submit">Update</button>
                <button type="button" onclick="document.getElementById('editProductModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Edit modal logic
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.onclick = () => {
            document.getElementById('edit-id').value = button.dataset.id;
            document.getElementById('edit-name').value = button.dataset.name;
            document.getElementById('edit-description').value = button.dataset.description;
            document.getElementById('edit-price').value = button.dataset.price;
            document.getElementById('edit-category').value = button.dataset.category;
            document.getElementById('editProductModal').style.display = 'block';
        };
    });

    // Modal close on background click
    window.onclick = (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    };
</script>
</body>
</html>
