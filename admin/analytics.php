<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Initialize analytics variables
$salesToday = $ordersToday = $activeUsers = $productsSold = 0;
$topProductsWeek = $topProductsMonth = [];
$hourlySales = [];
$dailySales = [];

// Total products in system
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $totalProducts = $stmt->fetchColumn() ?? 0;
} catch (PDOException $e) {
    $totalProducts = 0;
}

// Total sales today
try {
    $stmt = $pdo->query("SELECT SUM(total) AS sales FROM orders WHERE DATE(created_at) = CURDATE()");
    $salesToday = $stmt->fetchColumn() ?? 0;
} catch (PDOException $e) {}

// Orders today
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
    $ordersToday = $stmt->fetchColumn();
} catch (PDOException $e) {}

// Active users today
try {
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM orders WHERE DATE(created_at) = CURDATE()");
    $activeUsers = $stmt->fetchColumn();
} catch (PDOException $e) {}

// Products sold today
try {
    $stmt = $pdo->query("SELECT SUM(quantity) FROM order_items WHERE DATE(created_at) = CURDATE()");
    $productsSold = $stmt->fetchColumn() ?? 0;
} catch (PDOException $e) {}

// Top-selling products this week
try {
    $stmt = $pdo->query("
        SELECT p.name, SUM(oi.quantity) AS total
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE WEEK(oi.created_at) = WEEK(NOW())
        GROUP BY oi.product_id
        ORDER BY total DESC
        LIMIT 5
    ");
    $topProductsWeek = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Top-selling products this month
try {
    $stmt = $pdo->query("
        SELECT p.name, SUM(oi.quantity) AS total
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE MONTH(oi.created_at) = MONTH(NOW())
        GROUP BY oi.product_id
        ORDER BY total DESC
        LIMIT 5
    ");
    $topProductsMonth = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Hourly sales today
try {
    $stmt = $pdo->query("
        SELECT HOUR(created_at) AS hour, SUM(total) AS total
        FROM orders
        WHERE DATE(created_at) = CURDATE()
        GROUP BY hour
    ");
    $hourlySales = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {}

// Daily sales trend (past 7 days)
try {
    $stmt = $pdo->query("
        SELECT DATE(created_at) AS day, SUM(total) AS total
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY day
        ORDER BY day ASC
    ");
    $dailySales = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics - HotelPOS</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .cards-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 40px;
        }
        .card {
            flex: 1 1 220px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-icon {
            font-size: 32px;
            color: #f76b1c;
        }
        .card-details h4 {
            font-size: 15px;
            font-weight: 600;
            margin: 0;
            color: #444;
        }
        .card-details p {
            font-size: 18px;
            margin: 2px 0 0;
            font-weight: 500;
            color: #222;
        }
        .section-box {
            margin-bottom: 40px;
        }
        .section-box h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }
        .section-box ul {
            padding-left: 20px;
            line-height: 1.6;
        }
        .chart-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .info-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 40px;
}

.info-card {
    background: #fff;
    border: 1px solid #ddd;
    border-left: 4px solid #f76b1c;
    border-radius: 10px;
    padding: 20px;
    flex: 1 1 300px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    transition: 0.2s ease;
}

.info-card:hover {
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    transform: translateY(-3px);
}

.info-card h3 {
    margin-bottom: 10px;
    font-size: 17px;
    color: #444;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

.info-card ul {
    padding-left: 18px;
    line-height: 1.6;
    font-size: 15px;
    color: #333;
}

.info-card p {
    font-size: 15px;
    color: #333;
    margin: 10px 0 0;
}

    </style>
</head>
<body>

<div class="wrapper">
    <nav id="sidebar">
        <div class="sidebar-header"><h3>HotelPOS</h3></div>
        <ul class="list-unstyled components">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-utensils"></i> Products</a></li>
            <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
            <li><a href="discount.php"><i class="fas fa-percent"></i> Discounts</a></li>
            <li><a href="users.php"><i class="fas fa-users-cog"></i> Users</a></li>
            <li class="active"><a href="#"><i class="fas fa-chart-line"></i> Analytics</a></li>
            <li><a href="reports.php"><i class="fas fa-file-export"></i> Reports</a></li>
            <li><a href="ai.php"><i class="fas fa-cogs"></i> AI Tools</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <div id="content">
        <header><h2>Analytics Overview</h2></header>

        <div class="cards-container">
            <div class="card">
                <div class="card-icon"><i class="fas fa-coins"></i></div>
                <div class="card-details">
                    <h4>Total Sales Today</h4>
                    <p>Ksh <?= number_format($salesToday, 2) ?></p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-receipt"></i></div>
                <div class="card-details">
                    <h4>Orders Today</h4>
                    <p><?= $ordersToday ?></p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-users"></i></div>
                <div class="card-details">
                    <h4>Active Users</h4>
                    <p><?= $activeUsers ?></p>
                </div>
            </div>
            
        </div>
        <div class="card">
    <div class="card-icon"><i class="fas fa-box"></i></div>
    <div class="card-details">
        <h4>Total Products</h4>
        <p><?= $totalProducts ?></p>
    </div>
</div>


        
        <div class="section-box">
            <h3>Sales Trend (Last 7 Days)</h3>
            <div class="chart-container">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>

        <div class="info-cards">
    <div class="info-card">
        <h3><i class="fas fa-star"></i> Top Products This Week</h3>
        <ul>
            <?php foreach ($topProductsWeek as $product): ?>
                <li><?= htmlspecialchars($product['name']) ?> - <?= $product['total'] ?> sold</li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="info-card">
        <h3><i class="fas fa-calendar-alt"></i> Top Products This Month</h3>
        <ul>
            <?php foreach ($topProductsMonth as $product): ?>
                <li><?= htmlspecialchars($product['name']) ?> - <?= $product['total'] ?> sold</li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="info-card">
        <h3><i class="fas fa-clock"></i> Peak Hour Today</h3>
        <p>
            <?php
            if (!empty($hourlySales)) {
                arsort($hourlySales);
                $peakHour = key($hourlySales);
                echo "Peak hour: " . sprintf("%02d:00", $peakHour) . "<br>Ksh " . number_format(current($hourlySales), 2);
            } else {
                echo "No sales recorded yet today.";
            }
            ?>
        </p>
    </div>
</div>


        <footer>
            <p>&copy; <?= date('Y') ?> HotelPOS. All rights reserved.</p>
        </footer>
    </div>
</div>

<script>
    const ctx = document.getElementById('salesTrendChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($dailySales)) ?>,
            datasets: [{
                label: 'Sales (Ksh)',
                data: <?= json_encode(array_values($dailySales)) ?>,
                fill: true,
                backgroundColor: 'rgba(255, 105, 0, 0.2)',
                borderColor: '#f76b1c',
                borderWidth: 2,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true },
                x: { ticks: { color: '#333' } }
            }
        }
    });
</script>

</body>
</html>
