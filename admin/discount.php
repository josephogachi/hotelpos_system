 <?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Fetch all discounts
$discounts = $pdo->query("SELECT * FROM discounts ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Discounts - HotelPOS</title>
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
            <li><a href="products.php"><i class="fas fa-utensils"></i> Products</a></li>
            <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
            <li class="active"><a href="discount.php"><i class="fas fa-percentage"></i> Discounts</a></li>
            <li><a href="users.php"><i class="fas fa-users-cog"></i> Users</a></li>
            <li><a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
            <li><a href="reports.php"><i class="fas fa-file-export"></i> Reports</a></li>
            <li><a href="ai.php"><i class="fas fa-cogs"></i> AI Tools</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Content -->
    <div id="content">
        <header>
            <h2>Manage Discounts</h2>
        </header>

        <section class="actions">
            <button onclick="document.getElementById('addDiscountModal').style.display='block'">
                <i class="fas fa-plus-circle"></i> Add Discount
            </button>
        </section>

        <section class="discount-list">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Percentage</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($discounts as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['name']) ?></td>
                        <td><?= $d['percentage'] ?>%</td>
                        <td><?= $d['start_date'] ?></td>
                        <td><?= $d['end_date'] ?></td>
                        <td><?= $d['is_active'] ? 'Yes' : 'No' ?></td>
                        <td>
                            <!-- Edit functionality can be expanded -->
                            <a href="delete_discount.php?id=<?= $d['id'] ?>" onclick="return confirm('Delete this discount?')">Delete</a>
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

<!-- Add Discount Modal -->
<div id="addDiscountModal" class="modal">
    <div class="modal-content">
        <h3>Add New Discount</h3>
        <form action="save_discount.php" method="POST">
            <label>Name:</label>
            <input type="text" name="name" required>
            <label>Percentage:</label>
            <input type="number" name="percentage" min="1" max="100" required>
            <label>Start Date:</label>
            <input type="date" name="start_date" required>
            <label>End Date:</label>
            <input type="date" name="end_date" required>
            <label>Active:</label>
            <select name="is_active">
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
            <button type="submit">Save Discount</button>
        </form>
    </div>
</div>

<script>
    // Close modal on click outside
    window.onclick = function(e) {
        const modal = document.getElementById('addDiscountModal');
        if (e.target === modal) modal.style.display = "none";
    }
</script>
</body>
</html>

