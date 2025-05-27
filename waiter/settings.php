<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Get current user data
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Waiter Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="waiter-styles.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar (same as orders.php) -->
        <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 col-lg-2 sidebar">
            <div class="logo-container">
                <img src="../assets/images/logo.jfif" alt="Hotel Logo" class="hotel-logo">
                <div class="hotel-name">SUNSET HOTEL</div>
            </div>
            <nav class="nav flex-column">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="orders.php" class="nav-link active">
                    <i class="fas fa-list"></i>
                    <span>Orders</span>
                </a>
                <a href="history.php" class="nav-link">
                    <i class="fas fa-history"></i>
                    <span>History</span>
                </a>
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="../auth/logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
        <!-- Main Content -->
        <div class="col-md-10 col-lg-10 main-content">
            <!-- Header (same as orders.php) -->
            
            <h4 class="mb-4">My Settings</h4>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" 
                                           value="<?= htmlspecialchars($user['name']) ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" 
                                           value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" 
                                           value="<?= htmlspecialchars($user['phone'] ?? 'N/A') ?>" readonly>
                                </div>
                                <button type="button" class="btn btn-outline">
                                    Request Profile Update
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control">
                                </div>
                                <button type="button" class="btn btn-primary">
                                    Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>Notification Preferences</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifyNewOrders" checked>
                                    <label class="form-check-label" for="notifyNewOrders">
                                        New order notifications
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="notifyOrderUpdates" checked>
                                    <label class="form-check-label" for="notifyOrderUpdates">
                                        Order status updates
                                    </label>
                                </div>
                                <button type="button" class="btn btn-primary">
                                    Save Preferences
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Same time update script as orders.php
</script>
</body>
</html>