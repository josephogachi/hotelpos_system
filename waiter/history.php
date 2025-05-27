<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Fetch order history
$historyStmt = $pdo->prepare("
    SELECT o.*, u.name as waiter_name 
    FROM orders o
    JOIN users u ON o.waiter_id = u.id
    WHERE o.status = 'closed'
    ORDER BY o.created_at DESC
    LIMIT 50
");
$historyStmt->execute();
$orderHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History | Waiter Dashboard</title>
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
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Order History</h4>
                <div class="btn-group">
                    <button class="btn btn-outline active">Today</button>
                    <button class="btn btn-outline">This Week</button>
                    <button class="btn btn-outline">This Month</button>
                </div>
            </div>
            
            <?php if (empty($orderHistory)): ?>
                <div class="alert alert-info">No order history found</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Table</th>
                                <th>Waiter</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderHistory as $order): ?>
                                <tr>
                                    <td><?= $order['id'] ?></td>
                                    <td><?= $order['table_number'] ?></td>
                                    <td><?= htmlspecialchars($order['waiter_name']) ?></td>
                                    <td>
                                        <?php
                                        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
                                        $countStmt->execute([$order['id']]);
                                        echo $countStmt->fetchColumn();
                                        ?>
                                    </td>
                                    <td>Ksh <?= number_format($order['total'], 2) ?></td>
                                    <td><?= date('M j, h:i A', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <button class="btn btn-outline view-order" data-order-id="<?= $order['id'] ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="btn btn-outline print-order" data-order-id="<?= $order['id'] ?>">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Content loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary print-order">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // View order details
    document.querySelectorAll('.view-order').forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            fetch(`get_order_details.php?order_id=${orderId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('orderDetailsContent').innerHTML = html;
                    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
                    modal.show();
                });
        });
    });
</script>
</body>
</html>