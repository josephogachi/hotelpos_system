<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start secure session
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);

// Verify cashier access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'cashier') {
    header("Location: ../index.php");
    exit;
}

// Database connection
require_once __DIR__ . '/../includes/db.php';

// Date range filter (default to current month)
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$reportType = $_GET['report_type'] ?? 'daily_summary';

// Fetch report data
try {
    // Daily Summary Report
    if ($reportType === 'daily_summary') {
        $stmt = $pdo->prepare("
            SELECT 
                DATE(payment_time) AS date,
                COUNT(*) AS transactions,
                SUM(total) AS total_sales,
                SUM(CASE WHEN payment_method = 'cash' THEN total ELSE 0 END) AS cash_sales,
                SUM(CASE WHEN payment_method = 'card' THEN total ELSE 0 END) AS card_sales,
                SUM(CASE WHEN payment_method = 'mobile' THEN total ELSE 0 END) AS mobile_sales
            FROM receipts r
            JOIN orders o ON r.order_id = o.id
            WHERE payment_status = 'paid'
            AND DATE(payment_time) BETWEEN :start_date AND :end_date
            GROUP BY DATE(payment_time)
            ORDER BY DATE(payment_time) DESC
        ");
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Product Sales Report
    elseif ($reportType === 'product_sales') {
        $stmt = $pdo->prepare("
            SELECT 
                p.name AS product_name,
                SUM(oi.quantity) AS total_quantity,
                SUM(oi.quantity * oi.price) AS total_sales
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN receipts r ON o.id = r.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE r.payment_status = 'paid'
            AND DATE(r.payment_time) BETWEEN :start_date AND :end_date
            GROUP BY p.name
            ORDER BY total_sales DESC
        ");
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Waiter Performance Report
    elseif ($reportType === 'waiter_performance') {
        $stmt = $pdo->prepare("
            SELECT 
                u.full_name AS waiter_name,
                COUNT(DISTINCT o.id) AS orders_served,
                SUM(o.total) AS total_sales,
                AVG(o.total) AS avg_order_value
            FROM orders o
            JOIN receipts r ON o.id = r.order_id
            JOIN users u ON o.waiter_id = u.id
            WHERE r.payment_status = 'paid'
            AND DATE(r.payment_time) BETWEEN :start_date AND :end_date
            GROUP BY o.waiter_id
            ORDER BY total_sales DESC
        ");
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Summary stats for the header
    $summaryStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_transactions,
            SUM(o.total) AS total_sales,
            SUM(CASE WHEN r.payment_method = 'cash' THEN o.total ELSE 0 END) AS cash_sales,
            SUM(CASE WHEN r.payment_method = 'card' THEN o.total ELSE 0 END) AS card_sales,
            SUM(CASE WHEN r.payment_method = 'mobile' THEN o.total ELSE 0 END) AS mobile_sales
        FROM receipts r
        JOIN orders o ON r.order_id = o.id
        WHERE r.payment_status = 'paid'
        AND DATE(r.payment_time) BETWEEN :start_date AND :end_date
    ");
    $summaryStmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
    $summaryData = $summaryStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Helper functions
function formatCurrency($amount) {
    return 'Ksh ' . number_format($amount ?? 0, 2);
}

function formatDate($date) {
    return date('M j, Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports | Hotel POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #ff416c, #ff4b2b);
            --secondary-gradient: linear-gradient(135deg, #4CAF50, #8BC34A);
            --neutral-gradient: linear-gradient(135deg, #757575, #9E9E9E);
            --bg-primary-light: #f8f9fa;
            --bg-secondary-light: #ffffff;
            --text-primary-light: #212529;
            --text-secondary-light: #6c757d;
            --border-color-light: #dee2e6;
            --sidebar-bg-light: linear-gradient(180deg, #2c3e50, #34495e);
            --card-shadow-light: 0 4px 20px rgba(0, 0, 0, 0.08);
            --success-light: #28a745;
            --warning-light: #ffc107;
            --danger-light: #dc3545;
            --info-light: #17a2b8;
            
            --bg-primary-dark: #121212;
            --bg-secondary-dark: #1e1e1e;
            --text-primary-dark: #f8f9fa;
            --text-secondary-dark: #adb5bd;
            --border-color-dark: #495057;
            --sidebar-bg-dark: linear-gradient(180deg, #121212, #1e1e1e);
            --card-shadow-dark: 0 4px 20px rgba(0, 0, 0, 0.2);
            --success-dark: #00c853;
            --warning-dark: #ffab00;
            --danger-dark: #ff5252;
            --info-dark: #00b8d4;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: all 0.3s ease;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Light/Dark Mode Variables */
        .light-mode {
            --bg-primary: var(--bg-primary-light);
            --bg-secondary: var(--bg-secondary-light);
            --text-primary: var(--text-primary-light);
            --text-secondary: var(--text-secondary-light);
            --border-color: var(--border-color-light);
            --sidebar-bg: var(--sidebar-bg-light);
            --card-shadow: var(--card-shadow-light);
            --success: var(--success-light);
            --warning: var(--warning-light);
            --danger: var(--danger-light);
            --info: var(--info-light);
        }
        
        .dark-mode {
            --bg-primary: var(--bg-primary-dark);
            --bg-secondary: var(--bg-secondary-dark);
            --text-primary: var(--text-primary-dark);
            --text-secondary: var(--text-secondary-dark);
            --border-color: var(--border-color-dark);
            --sidebar-bg: var(--sidebar-bg-dark);
            --card-shadow: var(--card-shadow-dark);
            --success: var(--success-dark);
            --warning: var(--warning-dark);
            --danger: var(--danger-dark);
            --info: var(--info-dark);
        }
        
        /* Sidebar Styles */
        .sidebar {
            background: var(--sidebar-bg);
            color: white;
            min-height: 100vh;
            position: fixed;
            width: 250px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .sidebar-brand {
            padding: 1.5rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand h4 {
            margin: 0;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }
        
        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 10px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            transition: all 0.3s ease;
            padding: 20px;
            min-height: 100vh;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Header */
        .page-header {
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-header h2 {
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .page-header .text-muted {
            font-size: 0.9rem;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            background-color: var(--bg-secondary);
            transition: all 0.3s ease;
            margin-bottom: 24px;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Metrics Cards */
        .metric-card {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            padding: 20px;
            height: 100%;
            background: var(--bg-secondary);
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
        }
        
        .metric-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            position: absolute;
            right: 20px;
            top: 20px;
            opacity: 0.9;
        }
        
        .metric-card.revenue .metric-icon {
            background: var(--primary-gradient);
        }
        
        .metric-card.transactions .metric-icon {
            background: var(--secondary-gradient);
        }
        
        .metric-card.cash .metric-icon {
            background: linear-gradient(135deg, #4CAF50, #8BC34A);
        }
        
        .metric-card.digital .metric-icon {
            background: linear-gradient(135deg, #2196F3, #03A9F4);
        }
        
        .metric-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 10px 0 5px;
        }
        
        .metric-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }
        
        .metric-change {
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }
        
        /* Tables */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .table {
            margin-bottom: 0;
            color: var(--text-primary);
        }
        
        .table th {
            background-color: rgba(0, 0, 0, 0.05);
            border-bottom-width: 1px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        
        .table td, .table th {
            padding: 1rem;
            vertical-align: middle;
            border-color: var(--border-color);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.03);
        }
        
        /* Buttons */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
            border: none;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            box-shadow: 0 4px 15px rgba(255, 75, 43, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 75, 43, 0.4);
        }
        
        .btn-success {
            background: var(--secondary-gradient);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);
        }
        
        .btn-outline-secondary {
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--bg-primary);
        }
        
        /* Forms */
        .form-control, .form-select {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 75, 43, 0.25);
        }
        
        /* Badges */
        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
            border-radius: 8px;
        }
        
        /* Alerts */
        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: var(--card-shadow);
        }
        
        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* User Profile */
        .user-profile {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 0;
            line-height: 1.2;
        }
        
        .user-role {
            font-size: 0.75rem;
            opacity: 0.8;
        }
        
        /* Empty State */
        .empty-state {
            padding: 40px 0;
            text-align: center;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        
        /* Sidebar Toggle for Mobile */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background: var(--primary-gradient);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        @media (max-width: 992px) {
            .sidebar-toggle {
                display: flex;
            }
        }
        
        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background: var(--primary-gradient);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        /* Modal */
        .modal-content {
            border: none;
            border-radius: 12px;
            background-color: var(--bg-secondary);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            border-bottom: 1px solid var(--border-color);
        }
        
        .modal-footer {
            border-top: 1px solid var(--border-color);
        }
    </style>
</head>
<body class="light-mode">
    <!-- Sidebar Toggle for Mobile -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h4>HOTEL POS</h4>
        </div>
        
        <div class="d-flex flex-column h-100">
            <ul class="nav flex-column mb-auto">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="payments.php" class="nav-link">
                        <i class="fas fa-money-bill-wave"></i> Payments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link active">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
            
            <div class="user-profile">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['full_name'] ?? 'C', 0, 1)) ?>
                </div>
                <div class="user-info">
                    <h6 class="user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Cashier') ?></h6>
                    <span class="user-role">Cashier</span>
                </div>
                <a href="../auth/logout.php" class="text-white">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Sales Reports</h2>
                    <p class="text-muted mb-0">Analyze sales performance and trends</p>
                </div>
                <div class="d-flex align-items-center">
                    <div class="me-3 d-flex align-items-center">
                        <i class="fas fa-sun me-2"></i>
                        <label class="toggle-switch">
                            <input type="checkbox" id="modeToggle">
                            <span class="toggle-slider"></span>
                        </label>
                        <i class="fas fa-moon ms-2"></i>
                    </div>
                    <button class="btn btn-outline-secondary me-2" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="fas fa-file-export me-1"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card animate__animated animate__fadeIn">
            <div class="card-header">
                <h5 class="mb-0">Report Filters</h5>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?= htmlspecialchars($startDate) ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?= htmlspecialchars($endDate) ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="report_type">
                            <option value="daily_summary" <?= $reportType === 'daily_summary' ? 'selected' : '' ?>>Daily Summary</option>
                            <option value="product_sales" <?= $reportType === 'product_sales' ? 'selected' : '' ?>>Product Sales</option>
                            <option value="waiter_performance" <?= $reportType === 'waiter_performance' ? 'selected' : '' ?>>Waiter Performance</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Apply
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-4">
                <div class="metric-card revenue animate__animated animate__fadeIn">
                    <div class="metric-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="metric-label">Total Sales</div>
                    <div class="metric-value"><?= formatCurrency($summaryData['total_sales'] ?? 0) ?></div>
                    <div class="metric-change">
                        <?= formatDate($startDate) ?> to <?= formatDate($endDate) ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="metric-card transactions animate__animated animate__fadeIn animate__delay-1s">
                    <div class="metric-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="metric-label">Transactions</div>
                    <div class="metric-value"><?= $summaryData['total_transactions'] ?? 0 ?></div>
                    <div class="metric-change">
                        Completed orders
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="metric-card cash animate__animated animate__fadeIn animate__delay-2s">
                    <div class="metric-icon">
                        <i class="fas fa-money-bill"></i>
                    </div>
                    <div class="metric-label">Cash Payments</div>
                    <div class="metric-value"><?= formatCurrency($summaryData['cash_sales'] ?? 0) ?></div>
                    <div class="metric-change">
                        <?= round(($summaryData['cash_sales']/$summaryData['total_sales'])*100 ?? 0) ?>% of total
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="metric-card digital animate__animated animate__fadeIn animate__delay-3s">
                    <div class="metric-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="metric-label">Digital Payments</div>
                    <div class="metric-value"><?= formatCurrency(($summaryData['card_sales'] ?? 0) + ($summaryData['mobile_sales'] ?? 0)) ?></div>
                    <div class="metric-change">
                        Card & Mobile combined
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Data -->
        <div class="card animate__animated animate__fadeIn">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <?= match($reportType) {
                        'daily_summary' => 'Daily Sales Summary',
                        'product_sales' => 'Product Sales Analysis',
                        'waiter_performance' => 'Waiter Performance',
                        default => 'Sales Report'
                    } ?>
                </h5>
                <small class="text-muted"><?= count($reportData) ?> records found</small>
            </div>
            <div class="card-body">
                <?php if (!empty($reportData)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <?php if ($reportType === 'daily_summary'): ?>
                                    <tr>
                                        <th>Date</th>
                                        <th>Transactions</th>
                                        <th>Total Sales</th>
                                        <th>Cash</th>
                                        <th>Card</th>
                                        <th>Mobile</th>
                                    </tr>
                                <?php elseif ($reportType === 'product_sales'): ?>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity Sold</th>
                                        <th>Total Sales</th>
                                        <th>Avg. Price</th>
                                    </tr>
                                <?php elseif ($reportType === 'waiter_performance'): ?>
                                    <tr>
                                        <th>Waiter</th>
                                        <th>Orders Served</th>
                                        <th>Total Sales</th>
                                        <th>Avg. Order Value</th>
                                    </tr>
                                <?php endif; ?>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $row): ?>
                                    <tr class="animate__animated animate__fadeIn">
                                        <?php if ($reportType === 'daily_summary'): ?>
                                            <td><?= formatDate($row['date'] ?? '') ?></td>
                                            <td><?= $row['transactions'] ?? 0 ?></td>
                                            <td class="fw-bold"><?= formatCurrency($row['total_sales'] ?? 0) ?></td>
                                            <td><?= formatCurrency($row['cash_sales'] ?? 0) ?></td>
                                            <td><?= formatCurrency($row['card_sales'] ?? 0) ?></td>
                                            <td><?= formatCurrency($row['mobile_sales'] ?? 0) ?></td>
                                        <?php elseif ($reportType === 'product_sales'): ?>
                                            <td><?= htmlspecialchars($row['product_name'] ?? 'N/A') ?></td>
                                            <td><?= $row['total_quantity'] ?? 0 ?></td>
                                            <td class="fw-bold"><?= formatCurrency($row['total_sales'] ?? 0) ?></td>
                                            <td><?= formatCurrency(($row['total_sales'] ?? 0) / max(1, $row['total_quantity'] ?? 1)) ?></td>
                                        <?php elseif ($reportType === 'waiter_performance'): ?>
                                            <td><?= htmlspecialchars($row['waiter_name'] ?? 'Unknown') ?></td>
                                            <td><?= $row['orders_served'] ?? 0 ?></td>
                                            <td class="fw-bold"><?= formatCurrency($row['total_sales'] ?? 0) ?></td>
                                            <td><?= formatCurrency($row['avg_order_value'] ?? 0) ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h5>No report data found</h5>
                        <p class="text-muted">Try adjusting your date range or filters</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Report Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Export Format</label>
                        <select class="form-select" id="exportFormat">
                            <option value="csv">CSV (Excel)</option>
                            <option value="pdf">PDF Document</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <div class="form-control"><?= formatDate($startDate) ?> to <?= formatDate($endDate) ?></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="exportReport()">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Light/Dark Mode Toggle
            const modeToggle = document.getElementById('modeToggle');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const currentMode = localStorage.getItem('theme') || (prefersDark ? 'dark' : 'light');
            
            if (currentMode === 'dark') {
                document.body.classList.add('dark-mode');
                modeToggle.checked = true;
            }
            
            modeToggle.addEventListener('change', function() {
                document.body.classList.toggle('dark-mode');
                const isDark = document.body.classList.contains('dark-mode');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            });
            
            // Sidebar toggle for mobile
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar');
            
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
            
            // Auto-submit form when filters change
            document.getElementById('report_type').addEventListener('change', function() {
                this.closest('form').submit();
            });
            
            // Add animations to elements when they come into view
            const animateOnScroll = function() {
                const elements = document.querySelectorAll('.card');
                
                elements.forEach(element => {
                    const elementPosition = element.getBoundingClientRect().top;
                    const screenPosition = window.innerHeight / 1.3;
                    
                    if (elementPosition < screenPosition) {
                        element.classList.add('animate__fadeInUp');
                    }
                });
            };
            
            window.addEventListener('scroll', animateOnScroll);
            animateOnScroll(); // Run once on page load
        });

        // Export functionality
        function exportReport() {
            const format = document.getElementById('exportFormat').value;
            
            if (format === 'csv') {
                exportToCSV();
            } else {
                exportToPDF();
            }
            
            bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
        }

        function exportToCSV() {
            // Get table data
            const rows = [];
            const headers = [];
            
            // Get headers
            document.querySelectorAll('table thead th').forEach(th => {
                headers.push(th.textContent.trim());
            });
            rows.push(headers.join(','));
            
            // Get data rows
            document.querySelectorAll('table tbody tr').forEach(tr => {
                const row = [];
                tr.querySelectorAll('td').forEach(td => {
                    row.push(td.textContent.trim());
                });
                rows.push(row.join(','));
            });
            
            // Create CSV content
            const csvContent = rows.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            
            // Trigger download
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', `sales_report_<?= $startDate ?>_to_<?= $endDate ?>.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.setFontSize(18);
            doc.text('Sales Report - <?= formatDate($startDate) ?> to <?= formatDate($endDate) ?>', 14, 15);
            
            // Add summary
            doc.setFontSize(12);
            doc.text(`Total Sales: <?= formatCurrency($summaryData['total_sales'] ?? 0) ?>`, 14, 25);
            doc.text(`Total Transactions: <?= $summaryData['total_transactions'] ?? 0 ?>`, 14, 30);
            
            // Add table
            const headers = [];
            document.querySelectorAll('table thead th').forEach(th => {
                headers.push(th.textContent.trim());
            });
            
            const data = [];
            document.querySelectorAll('table tbody tr').forEach(tr => {
                const row = [];
                tr.querySelectorAll('td').forEach(td => {
                    row.push(td.textContent.trim());
                });
                data.push(row);
            });
            
            doc.autoTable({
                head: [headers],
                body: data,
                startY: 40,
                styles: { fontSize: 9 }
            });
            
            // Save the PDF
            doc.save(`sales_report_<?= $startDate ?>_to_<?= $endDate ?>.pdf`);
        }
    </script>
</body>
</html>