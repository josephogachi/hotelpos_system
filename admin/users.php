<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Fetch users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - HotelPOS</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header"><h3>HotelPOS</h3></div>
        <ul class="list-unstyled components">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-utensils"></i> Products</a></li>
            <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
            <li><a href="discount.php"><i class="fas fa-percent"></i> Discounts</a></li>
            <li class="active"><a href="#"><i class="fas fa-users-cog"></i> Users</a></li>
            <li><a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
            <li><a href="reports.php"><i class="fas fa-file-export"></i> Reports</a></li>
            <li><a href="ai.php"><i class="fas fa-cogs"></i> AI Tools</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <header><h2>Manage Users</h2></header>

        <section class="user-list">
            <h3>Users</h3>
            <button id="addUserBtn" class="add-btn">Add User</button>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>PIN</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td><?= htmlspecialchars($user['pin']) ?></td>
                        <td><?= htmlspecialchars($user['created_at']) ?></td>
                        <td>
                            <button class="editUserBtn"
                                data-id="<?= $user['id'] ?>"
                                data-name="<?= htmlspecialchars($user['name']) ?>"
                                data-full-name="<?= htmlspecialchars($user['full_name']) ?>"
                                data-username="<?= htmlspecialchars($user['username']) ?>"
                                data-role="<?= $user['role'] ?>">
                                Edit
                            </button>
                            <a href="delete_user.php?id=<?= $user['id'] ?>" onclick="return confirm('Delete this user?')">Delete</a>
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

<!-- Add/Edit Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeUserModal()">&times;</span>
        <h3 id="userModalTitle">Add/Edit User</h3>
        <form action="save_user.php" method="POST">
            <input type="hidden" name="id" id="user_id">
            <input type="text" name="name" id="user_name" placeholder="Short Name" required>
            <input type="text" name="full_name" id="user_full_name" placeholder="Full Name" required>
            <input type="text" name="username" id="user_username" placeholder="Username" required>
            <input type="text" name="pin" id="user_password" placeholder="6-digit PIN" maxlength="6" required>
            <select name="role" id="user_role" required>
                <option value="waiter">Waiter</option>
                <option value="cashier">Cashier</option>
                <option value="kitchen">Kitchen</option>
                <option value="admin">Admin</option>
            </select>
            <button type="submit">Save</button>
        </form>
    </div>
</div>

<script>
    const addBtn = document.getElementById('addUserBtn');
    const modal = document.getElementById('userModal');
    const modalTitle = document.getElementById('userModalTitle');
    const userId = document.getElementById('user_id');
    const nameField = document.getElementById('user_name');
    const fullNameField = document.getElementById('user_full_name');
    const usernameField = document.getElementById('user_username');
    const passwordField = document.getElementById('user_password');
    const roleField = document.getElementById('user_role');

    addBtn.onclick = () => {
        modalTitle.textContent = "Add User";
        userId.value = "";
        nameField.value = "";
        fullNameField.value = "";
        usernameField.value = "";
        passwordField.value = "";
        roleField.value = "waiter";
        modal.style.display = "block";
    };

    document.querySelectorAll('.editUserBtn').forEach(btn => {
        btn.onclick = () => {
            modalTitle.textContent = "Edit User";
            userId.value = btn.dataset.id;
            nameField.value = btn.dataset.name;
            fullNameField.value = btn.dataset.fullName;
            usernameField.value = btn.dataset.username;
            passwordField.value = "";
            roleField.value = btn.dataset.role;
            modal.style.display = "block";
        };
    });

    function closeUserModal() {
        modal.style.display = "none";
    }

    window.onclick = (e) => {
        if (e.target === modal) modal.style.display = "none";
    };
</script>

</body>
</html>
