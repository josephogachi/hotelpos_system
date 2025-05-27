<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Fetch all categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories - HotelPOS</title>
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

    <!-- Page Content -->
    <div id="content">
        <header>
            <h2>Manage Categories</h2>
        </header>

        <section>
            <button onclick="document.getElementById('addCategoryModal').style.display='block'">
    <i class="fas fa-plus-circle"></i> New Category
</button>


            <table>
                <thead>
                    <tr><th>Name</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= htmlspecialchars($cat['name']) ?></td>
                            <td>
                                <button onclick="openEditModal(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>')">Edit</button>
                                <a href="delete_category.php?id=<?= $cat['id'] ?>" onclick="return confirm('Delete this category?')">Delete</a>
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

<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
        <h3>New Category</h3>
        <form action="save_category.php" method="POST">
            <input type="text" name="name" placeholder="Category Name" required>
            <button type="submit">Save</button>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
        <h3>Edit Category</h3>
        <form action="update_category.php" method="POST">
            <input type="hidden" name="id" id="edit_id">
            <input type="text" name="name" id="edit_name" placeholder="Category Name" required>
            <button type="submit">Update</button>
        </form>
    </div>
</div>

<script>
    function openEditModal(id, name) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('editCategoryModal').style.display = 'block';
    }

    // Close modals when clicking outside
    window.onclick = function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    }
</script>
</body>
</html>
