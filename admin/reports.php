<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

function getWaiterPerformance($pdo, $period = 'daily') {
    $dateCondition = $period === 'daily' ? 
        "DATE(o.created_at) = CURDATE()" : 
        "MONTH(o.created_at) = MONTH(CURRENT_DATE()) AND YEAR(o.created_at) = YEAR(CURRENT_DATE())";

$stmt = $pdo->prepare("
    SELECT 
        u.id AS user_id,
        u.full_name,
        u.active,
        COUNT(DISTINCT o.id) AS order_count,
        SUM(oi.price * oi.quantity) AS total_amount,
        HOUR(o.created_at) AS peak_hour
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN order_items oi ON o.id = oi.order_id
    WHERE $dateCondition
    GROUP BY u.id, HOUR(o.created_at)
    ORDER BY u.id, order_count DESC
");

    $stmt->execute();

    $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group peak hour and summarize by user
    $waiters = [];
    foreach ($rawData as $row) {
        $userId = $row['user_id'];
        if (!isset($waiters[$userId])) {
            $waiters[$userId] = [
                'full_name' => $row['full_name'],
                'active' => $row['active'], 
                'order_count' => 0,
                'total_amount' => 0,
                'hourly_distribution' => [],
            ];
        }
        $waiters[$userId]['order_count'] += $row['order_count'];
        $waiters[$userId]['total_amount'] += $row['total_amount'];
        $hour = $row['peak_hour'];
        if (!isset($waiters[$userId]['hourly_distribution'][$hour])) {
            $waiters[$userId]['hourly_distribution'][$hour] = 0;
        }
        $waiters[$userId]['hourly_distribution'][$hour]++;
    }

    // Determine peak working hour
    foreach ($waiters as &$w) {
        $peakHour = array_keys($w['hourly_distribution'], max($w['hourly_distribution']))[0] ?? 'N/A';
        $w['peak_hour'] = $peakHour;
        unset($w['hourly_distribution']); // clean up
    }

    return $waiters;
}


$dailyPerformance = getWaiterPerformance($pdo, 'daily');
$monthlyPerformance = getWaiterPerformance($pdo, 'monthly');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - HotelPOS</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .report-table th, .report-table td {
            border: 1px solid #ddd;
            padding: 12px 10px;
            text-align: center;
            font-size: 14px;
        }
        .report-table th {
            background-color: #f76b1c;
            color: #fff;
        }
        .report-table td {
            background-color: #fff;
            color: #333;
        }
        h2.section-title {
            margin: 30px 0 15px;
            font-size: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 5px;
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
            <li><a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
            <li class="active"><a href="#"><i class="fas fa-file-export"></i> Reports</a></li>
            <li><a href="ai.php"><i class="fas fa-cogs"></i> AI Tools</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <div id="content">
        <header><h2>Reports - Waiter/Waitress Performance</h2></header>

        <h2 class="section-title"><i class="fas fa-calendar-day"></i> Daily Performance</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Orders</th>
                    <th>Total Amount (Ksh)</th>
                    <th>Peak Hour</th>
                    <th>Status</th>
                </tr>
            </thead>
           <tbody>
    <?php foreach ($dailyPerformance as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= $row['order_count'] ?></td>
            <td>Ksh <?= number_format($row['total_amount'], 2) ?></td>
            <td>
                <?= is_numeric($row['peak_hour']) ? sprintf('%02d:00', $row['peak_hour']) : 'N/A' ?>
            </td>
            <td>
                <?= $row['active'] ? '<span class="badge active">Active</span>' : '<span class="badge inactive">Inactive</span>' ?>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

        </table>

        <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Monthly Performance</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Orders</th>
                    <th>Total Amount (Ksh)</th>
                    <th>Peak Hour</th>
                    <th>Status</th>
                </tr>
            </thead>
           <tbody>
    <?php foreach ($monthlyPerformance as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= $row['order_count'] ?></td>
            <td>Ksh <?= number_format($row['total_amount'], 2) ?></td>
            <td>
                <?= is_numeric($row['peak_hour']) ? sprintf('%02d:00', $row['peak_hour']) : 'N/A' ?>
            </td>
            <td>
                <?= $row['active'] ? '<span class="badge active">Active</span>' : '<span class="badge inactive">Inactive</span>' ?>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

        </table>

        <footer>
            <p>&copy; <?= date('Y') ?> HotelPOS. All rights reserved.</p>
        </footer>
    </div>
</div>

</body>
</html>
