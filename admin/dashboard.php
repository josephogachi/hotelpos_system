<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Access control
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// ✅ Get today's total revenue from order_items (quantity × price)
$today_revenue_stmt = $pdo->prepare("
    SELECT SUM(oi.quantity * oi.price) AS total
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) = CURDATE()
");
$today_revenue_stmt->execute();
$today_revenue = $today_revenue_stmt->fetchColumn();
$today_revenue = $today_revenue ? number_format($today_revenue, 2) : "0.00";

// ✅ Get number of orders today
$orders_today_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
$orders_today_stmt->execute();
$orders_today = $orders_today_stmt->fetchColumn();

// ✅ Get peak hour (hour with most orders today)
$peak_stmt = $pdo->prepare("
    SELECT HOUR(created_at) as hr, COUNT(*) as cnt
    FROM orders
    WHERE DATE(created_at) = CURDATE()
    GROUP BY hr
    ORDER BY cnt DESC
    LIMIT 1
");
$peak_stmt->execute();
$peak = $peak_stmt->fetch(PDO::FETCH_ASSOC);
$peak_hour = $peak ? sprintf("%02d:00 - %02d:00", $peak['hr'], $peak['hr'] + 1) : 'N/A';

// ✅ Get top-selling product today
$top_product_stmt = $pdo->prepare("
    SELECT p.name, SUM(oi.quantity) as total_qty
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) = CURDATE()
    GROUP BY p.id
    ORDER BY total_qty DESC
    LIMIT 1
");
$top_product_stmt->execute();
$top_product = $top_product_stmt->fetch(PDO::FETCH_ASSOC);
$top_product_name = $top_product ? htmlspecialchars($top_product['name']) : 'N/A';

// ✅ Category & Discount metrics
$category_count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Handle if `is_active` column doesn’t exist
try {
    $active_discount_count = $pdo->query("SELECT COUNT(*) FROM discounts WHERE is_active = 1")->fetchColumn();
} catch (PDOException $e) {
    $active_discount_count = 0; // fallback if column doesn't exist yet
}

// ✅ Fetch all categories and products
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert success"><?= $_SESSION['success'] ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert error"><?= $_SESSION['error'] ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - HotelPOS</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="wrapper">
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3>HotelPOS</h3>
        </div>
        <ul class="list-unstyled components">
    <li class="active"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
    <li><a href="products.php"><i class="fas fa-utensils"></i> Manage Products</a></li>
    <li><a href="categories.php"><i class="fas fa-list"></i> Manage Categories</a></li>
    <li><a href="discount.php"><i class="fas fa-percentage"></i> Manage Discounts</a></li>
    <li><a href="users.php"><i class="fas fa-users-cog"></i> Manage Users</a></li>
    <li><a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
    <li><a href="reports.php"><i class="fas fa-file-export"></i> Reports</a></li>
    <li><a href="ai-tools.php"><i class="fas fa-cogs"></i> AI Tools</a></li>
    <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
</ul>

    </nav>

    <div id="content">
        <header><h2>Welcome, Admin</h2></header>

        <section class="dashboard-overview">
<section class="dashboard-overview">
    <div class="card metric">
        <i class="fas fa-coins"></i>
        <div>
            <h3>Ksh <?= $today_revenue ?></h3>
            <p>Today's Revenue</p>
        </div>
    </div>
    <div class="card metric">
        <i class="fas fa-receipt"></i>
        <div>
            <h3><?= $orders_today ?></h3>
            <p>Orders Today</p>
        </div>
    </div>
    <div class="card metric">
        <i class="fas fa-user-clock"></i>
        <div>
            <h3><?= $peak_hour ?></h3>
            <p>Peak Hour</p>
        </div>
    </div>
    <div class="card metric">
        <i class="fas fa-star"></i>
        <div>
            <h3><?= $top_product_name ?></h3>
            <p>Top Product</p>
        </div>
    </div>
</section>

             <div class="card metric">
        <i class="fas fa-list"></i>
        <div>
            <h3><?= $category_count ?>+</h3>
            <p>Categories</p>
        </div>
    </div>
    <div class="card metric">
        <i class="fas fa-tags"></i>
        <div>
            <h3><?= $active_discount_count ?> Active</h3>
            <p>Discounts</p>
        </div>
    </div>
        </section>
       




       

        <footer><p>&copy; <?= date('Y') ?> HotelPOS. All rights reserved.</p></footer>
    </div>
</div>
<div id="addCategoryModal" class="modal">
    <div class="modal-content wide">
        <span class="close" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
        <h3>Add New Category</h3>
        <form action="add_category.php" method="post">
            <label>Category Name:</label>
            <input type="text" name="name" required>

            <label>Font Awesome Icon (e.g., fas fa-pizza-slice):</label>
            <input type="text" name="icon">

            <button type="submit">Save Category</button>
        </form>
    </div>
</div>

<div id="addDiscountModal" class="modal">
    <div class="modal-content wide">
        <span class="close" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
        <h3>Add Discount</h3>
        <form action="add_discount.php" method="post">
            <label>Discount Name:</label>
            <input type="text" name="name" required>

            <label>Percentage (%):</label>
            <input type="number" name="percentage" min="1" max="100" required>

            <button type="submit">Save Discount</button>
        </form>
    </div>
</div>


<!-- ADD PRODUCT MODAL -->
<div id="addProductModal" class="modal">
    <form action="save_product.php" method="POST" enctype="multipart/form-data" class="modal-content">
        <h3>Add Product</h3>
        <label>Name:</label><input type="text" name="name" required>
        <label>Description:</label><input type="text" name="description">
        <label>Price (Ksh):</label><input type="number" name="price" required step="0.01">
        <label>Category:</label>
        <select name="category_id" required>
            <option value="">-- Select Category --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Image:</label><input type="file" name="image" accept="image/*" required>
        <div class="modal-actions">
            <button type="submit">Save</button>
            <button type="button" onclick="document.getElementById('addProductModal').style.display='none'">Cancel</button>
        </div>
    </form>
</div>
<!-- Edit Product Modal -->
<div id="editProductModal" class="modal">
    <div class="modal-content large">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2>Edit Product</h2>
        <form id="editProductForm" method="POST" enctype="multipart/form-data" action="edit_product.php">
            <input type="hidden" name="product_id" id="edit_product_id">

            <label for="edit_name">Product Name</label>
            <input type="text" name="name" id="edit_name" required>

            <label for="edit_description">Description</label>
            <textarea name="description" id="edit_description" rows="3"></textarea>

            <label for="edit_price">Price (Ksh)</label>
            <input type="number" name="price" id="edit_price" required>

            <label for="edit_category">Category</label>
            <select name="category_id" id="edit_category" required>
                <?php
                $categories = $pdo->query("SELECT id, name FROM categories")->fetchAll();
                foreach ($categories as $cat) {
                    echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
                }
                ?>
            </select>

            <label for="edit_image">Change Image (optional)</label>
            <input type="file" name="image" id="edit_image" accept="image/*">

            <button type="submit" name="update_product">Update Product</button>
        </form>
    </div>
</div>

<script>
    function openEditModal(id, name, description, price, category_id) {
    document.getElementById('edit_product_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_price').value = price;
    document.getElementById('edit_category').value = category_id;

    document.getElementById('editProductModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

function closeEditModal() {
    document.getElementById('editProductModal').style.display = 'none';
    document.body.classList.remove('modal-open');
}


    // Show Add Product Modal
    document.querySelector('.action-buttons button').addEventListener('click', () => {
        document.getElementById('addProductModal').style.display = 'block';
    });

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('addProductModal');
        if (event.target === modal) {
            modal.style.display = "none";
        }
    }
</script>


<script>
// Edit button handler
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        // You can implement a modal with pre-filled values here for edit
        alert("Edit modal can be added here.");
    });
});
</script>

</body>
</html>
