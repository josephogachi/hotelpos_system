<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Fetch active orders
$ordersStmt = $pdo->prepare("
    SELECT o.*, u.name as waiter_name 
    FROM orders o
    JOIN users u ON o.waiter_id = u.id
    WHERE o.status = 'pending' OR o.status = 'confirmed'
    ORDER BY o.created_at DESC
");
$ordersStmt->execute();
$activeOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | Waiter Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="waiter-styles.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
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
            <div class="header">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['user_name'] ?? 'W', 0, 1)) ?>
                    </div>
                    <div>
                        <h5 class="user-name"><?= $_SESSION['user_name'] ?? 'Waiter' ?></h5>
                        <div class="user-role"><?= $_SESSION['user_role'] ?? 'waiter' ?></div>
                    </div>
                </div>
                <div class="text-end">
                    <div class="text-muted" id="current-time"></div>
                    <div class="badge bg-success">Online</div>
                </div>
            </div>

            <h4 class="mb-4">Active Orders</h4>
            
            <?php if (empty($activeOrders)): ?>
                <div class="alert alert-info">No active orders found</div>
            <?php else: ?>
                <?php foreach ($activeOrders as $order): ?>
                    <div class="card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5>Order #<?= $order['id'] ?></h5>
                                <div class="text-muted">
                                    Table <?= $order['table_number'] ?> • 
                                    <?= date('h:i A', strtotime($order['created_at'])) ?>
                                </div>
                            </div>
                            <div>
                                <span class="status-badge status-<?= $order['status'] ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <?php
                            $itemsStmt = $pdo->prepare("
                                SELECT * FROM order_items 
                                WHERE order_id = ?
                            ");
                            $itemsStmt->execute([$order['id']]);
                            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            
                            <?php foreach ($items as $item): ?>
                                <div class="order-item">
                                    <div>
                                        <span><?= htmlspecialchars($item['product_name']) ?></span>
                                        <small class="text-muted">x<?= $item['quantity'] ?></small>
                                    </div>
                                    <div>Ksh <?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="order-item font-weight-bold">
                                <div>Total</div>
                                <div>Ksh <?= number_format($order['total'], 2) ?></div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <?php if ($order['status'] === 'pending'): ?>
                                <button class="btn btn-primary confirm-order" data-order-id="<?= $order['id'] ?>">
                                    <i class="fas fa-check"></i> Confirm
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-outline print-order" data-order-id="<?= $order['id'] ?>">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Update time
    function updateTime() {
        document.getElementById('current-time').textContent = new Date().toLocaleTimeString();
    }
    setInterval(updateTime, 1000);
    updateTime();

    // Order actions
    document.querySelectorAll('.confirm-order').forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            fetch(`update_order_status.php?order_id=${orderId}&status=confirmed`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to confirm order: ' + (data.message || 'Unknown error'));
                    }
                });
        });
    });

    document.querySelectorAll('.print-order').forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            window.open(`print_order.php?order_id=${orderId}`, '_blank');
        });
    });
</script>
</body>
</html>