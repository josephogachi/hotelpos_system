<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Tools - HotelPOS</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .ai-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 30px;
        }

        .ai-tool {
            background: #fff;
            border-radius: 12px;
            border-left: 5px solid #f76b1c;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            padding: 20px;
            flex: 1 1 300px;
            transition: 0.3s;
        }

        .ai-tool:hover {
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
            transform: translateY(-4px);
        }

        .ai-tool h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
        }

        .ai-tool p {
            font-size: 15px;
            color: #555;
        }

        .ai-tool i {
            font-size: 24px;
            color: #f76b1c;
            margin-right: 10px;
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
            <li><a href="reports.php"><i class="fas fa-file-export"></i> Reports</a></li>
            <li class="active"><a href="ai-tools.php"><i class="fas fa-cogs"></i> AI Tools</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <div id="content">
        <header><h2>AI Assistant Tools</h2></header>

        <div class="ai-container">
            <div class="ai-tool">
                <h3><i class="fas fa-brain"></i> Smart Inventory Insights</h3>
                <p>Analyze product movement and get suggestions on restocking and slow-moving items.</p>
            </div>

            <div class="ai-tool">
                <h3><i class="fas fa-chart-line"></i> Sales Prediction</h3>
                <p>Predict your upcoming sales based on historical trends and seasons.</p>
            </div>

            <div class="ai-tool">
                <h3><i class="fas fa-user-friends"></i> Customer Behavior</h3>
                <p>Identify customer preferences and recommend personalized offers.</p>
            </div>

            <div class="ai-tool">
                <h3><i class="fas fa-lightbulb"></i> Upsell Recommendations</h3>
                <p>Suggest combo deals or upsell items based on current order patterns.</p>
            </div>

            <div class="ai-tool">
                <h3><i class="fas fa-microphone"></i> Natural Language Reports</h3>
                <p>Ask things like "Show today's top-selling product" and get instant answers.</p>
            </div>
        </div>

        <footer>
            <p>&copy; <?= date('Y') ?> HotelPOS. All rights reserved.</p>
        </footer>
    </div>
</div>

</body>
</html>
