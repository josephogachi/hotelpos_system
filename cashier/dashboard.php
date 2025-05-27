<?php
// Enable full error reporting
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

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verify cashier access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'cashier') {
    header("Location: ../index.php");
    exit;
}

// Database connection
require_once __DIR__ . '/../includes/db.php';

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed");
    }

    $receiptId = $_POST['receipt_id'] ?? null;
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $amountReceived = $_POST['amount_received'] ?? null;
    
    if ($receiptId && in_array($paymentMethod, ['cash', 'card', 'mobile']) && is_numeric($amountReceived)) {
        try {
            $pdo->beginTransaction();
            
            // Get order total first
            $stmt = $pdo->prepare("
                SELECT o.total 
                FROM receipts r
                JOIN orders o ON r.order_id = o.id
                WHERE r.id = :receipt_id
            ");
            $stmt->execute([':receipt_id' => $receiptId]);
            $orderTotal = $stmt->fetchColumn();
            
            if ($orderTotal === false) {
                throw new Exception("Invalid receipt ID");
            }
            
            $changeGiven = $amountReceived - $orderTotal;
            
            // Update receipt status
            $stmt = $pdo->prepare("
                UPDATE receipts 
                SET payment_status = 'paid', 
                    payment_time = NOW(), 
                    cashier_id = :cashier_id,
                    payment_method = :payment_method,
                    amount_received = :amount_received,
                    change_given = :change_given
                WHERE id = :receipt_id
            ");
            $stmt->execute([
                ':cashier_id' => $_SESSION['user_id'],
                ':receipt_id' => $receiptId,
                ':payment_method' => $paymentMethod,
                ':amount_received' => $amountReceived,
                ':change_given' => $changeGiven
            ]);
            
            // Update order status
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'closed' 
                WHERE id = (
                    SELECT order_id FROM receipts WHERE id = :receipt_id
                )
            ");
            $stmt->execute([':receipt_id' => $receiptId]);
            
            $pdo->commit();
            $_SESSION['success'] = "Payment confirmed successfully! Change: Ksh " . number_format($changeGiven, 2);
            header('Location: dashboard.php');
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error confirming payment: " . $e->getMessage();
            header('Location: dashboard.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid payment data";
        header('Location:dashboard.php');
        exit();
    }
}

// Fetch pending payments
try {
    $pendingPayments = $pdo->query("
        SELECT 
            r.id AS receipt_id,
            r.receipt_number,
            r.unique_code,
            u.full_name AS waiter_name,
            o.total,
            o.table_number,
            r.created_at,
            COUNT(oi.id) AS item_count
        FROM receipts r
        JOIN orders o ON r.order_id = o.id
        JOIN users u ON o.waiter_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE r.payment_status = 'unpaid'
        GROUP BY r.id
        ORDER BY r.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Today's metrics
    $metrics = $pdo->query("
        SELECT 
            IFNULL(SUM(o.total), 0) AS total_payments,
            COUNT(DISTINCT o.waiter_id) AS active_waiters,
            (
                SELECT HOUR(r.payment_time) 
                FROM receipts r
                WHERE DATE(r.payment_time) = CURDATE()
                AND r.payment_status = 'paid'
                GROUP BY HOUR(r.payment_time)
                ORDER BY COUNT(*) DESC
                LIMIT 1
            ) AS peak_hour,
            (
                SELECT u.full_name
                FROM orders o
                JOIN receipts r ON o.id = r.order_id
                JOIN users u ON o.waiter_id = u.id
                WHERE DATE(r.payment_time) = CURDATE()
                AND r.payment_status = 'paid'
                GROUP BY o.waiter_id
                ORDER BY SUM(o.total) DESC
                LIMIT 1
            ) AS top_waiter,
            (
                SELECT SUM(o.total)
                FROM orders o
                JOIN receipts r ON o.id = r.order_id
                WHERE DATE(r.payment_time) = CURDATE()
                AND r.payment_status = 'paid'
                GROUP BY o.waiter_id
                ORDER BY SUM(o.total) DESC
                LIMIT 1
            ) AS top_waiter_amount,
            (
                SELECT COUNT(*)
                FROM receipts
                WHERE DATE(payment_time) = CURDATE()
                AND payment_status = 'paid'
            ) AS transactions_count,
            (
                SELECT AVG(total)
                FROM orders o
                JOIN receipts r ON o.id = r.order_id
                WHERE DATE(r.payment_time) = CURDATE()
                AND r.payment_status = 'paid'
            ) AS avg_order_value
        FROM orders o
        JOIN receipts r ON o.id = r.order_id
        WHERE DATE(r.payment_time) = CURDATE()
        AND r.payment_status = 'paid'
    ")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Helper functions
function formatCurrency($amount) {
    return 'Ksh ' . number_format($amount, 2);
}

function getGreeting() {
    $hour = date('H');
    if ($hour < 12) return 'Good morning';
    if ($hour < 17) return 'Good afternoon';
    return 'Good evening';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard | Hotel POS</title>
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
        
        .metric-card.peak-hour .metric-icon {
            background: var(--neutral-gradient);
        }
        
        .metric-card.top-waiter .metric-icon {
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
        
        .metric-change.positive {
            color: var(--success);
        }
        
        /* Tables */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
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
            padding: 0.35em 0.65em;
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
        
        /* Payment Modal */
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
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .metric-card {
                margin-bottom: 20px;
            }
            
            .table td, .table th {
                padding: 0.75rem;
            }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--bg-primary);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--text-secondary);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-primary);
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
        
        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 20px rgba(255, 75, 43, 0.4);
            z-index: 100;
            transition: all 0.3s ease;
            border: none;
        }
        
        .fab:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 8px 30px rgba(255, 75, 43, 0.6);
            color: white;
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
                    <a href="#" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="payments.php" class="nav-link">
                        <i class="fas fa-money-bill-wave"></i> Payments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link">
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
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show fade-in">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show fade-in">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><?= getGreeting() ?>, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Cashier') ?></h2>
                    <p class="text-muted mb-0"><?= date('l, F j, Y') ?></p>
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
                    <button class="btn btn-outline-secondary" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Metrics -->
        <div class="row">
            <div class="col-md-6 col-lg-3">
                <div class="metric-card revenue animate__animated animate__fadeIn">
                    <div class="metric-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="metric-label">Today's Revenue</div>
                    <div class="metric-value"><?= formatCurrency($metrics['total_payments'] ?? 0) ?></div>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up me-1"></i> Today
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="metric-card transactions animate__animated animate__fadeIn animate__delay-1s">
                    <div class="metric-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="metric-label">Transactions</div>
                    <div class="metric-value"><?= $metrics['transactions_count'] ?? 0 ?></div>
                    <div class="metric-change">
                        Today's count
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="metric-card peak-hour animate__animated animate__fadeIn animate__delay-2s">
                    <div class="metric-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="metric-label">Peak Hour</div>
                    <div class="metric-value"><?= isset($metrics['peak_hour']) ? $metrics['peak_hour'] . ':00' : 'N/A' ?></div>
                    <div class="metric-change">
                        Busiest time today
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="metric-card top-waiter animate__animated animate__fadeIn animate__delay-3s">
                    <div class="metric-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="metric-label">Top Waiter</div>
                    <div class="metric-value"><?= $metrics['top_waiter'] ?? 'N/A' ?></div>
                    <div class="metric-change">
                        <?= isset($metrics['top_waiter_amount']) ? formatCurrency($metrics['top_waiter_amount']) : '' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Payments -->
        <div class="card animate__animated animate__fadeIn">
            <div class="card-header">
                <h5 class="mb-0">Pending Payments</h5>
                <div class="d-flex">
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search..." id="searchInput">
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($pendingPayments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="paymentsTable">
                            <thead>
                                <tr>
                                    <th>Receipt #</th>
                                    <th>Tracking Code</th>
                                    <th>Waiter</th>
                                    <th>Amount</th>
                                    <th>Items</th>
                                    <th>Table</th>
                                    <th>Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingPayments as $payment): ?>
                                    <tr class="animate__animated animate__fadeIn">
                                        <td><?= htmlspecialchars($payment['receipt_number']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($payment['unique_code']) ?></span></td>
                                        <td><?= htmlspecialchars($payment['waiter_name'] ?? 'Unknown') ?></td>
                                        <td class="fw-bold"><?= formatCurrency($payment['total']) ?></td>
                                        <td><span class="badge bg-primary"><?= $payment['item_count'] ?></span></td>
                                        <td><?= htmlspecialchars($payment['table_number'] ?? 'N/A') ?></td>
                                        <td><?= date('H:i', strtotime($payment['created_at'])) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-success confirm-payment" 
                                                data-receipt-id="<?= $payment['receipt_id'] ?>"
                                                data-receipt-total="<?= $payment['total'] ?>">
                                                <i class="fas fa-check me-1"></i> Confirm
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h5>No pending payments</h5>
                        <p class="text-muted">All payments have been processed</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Floating Action Button -->
        <button class="fab" id="quickPaymentBtn" title="Quick Payment">
            <i class="fas fa-bolt"></i>
        </button>
    </main>

    <!-- Payment Confirmation Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <div class="modal-body">
                        <input type="hidden" name="receipt_id" id="modalReceiptId">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Payment Method</label>
                            <div class="d-flex gap-2">
                                <input type="radio" class="btn-check" name="payment_method" id="cash" value="cash" checked>
                                <label class="btn btn-outline-success flex-grow-1" for="cash">
                                    <i class="fas fa-money-bill-wave me-2"></i> Cash
                                </label>
                                
                                <input type="radio" class="btn-check" name="payment_method" id="card" value="card">
                                <label class="btn btn-outline-primary flex-grow-1" for="card">
                                    <i class="fas fa-credit-card me-2"></i> Card
                                </label>
                                
                                <input type="radio" class="btn-check" name="payment_method" id="mobile" value="mobile">
                                <label class="btn btn-outline-info flex-grow-1" for="mobile">
                                    <i class="fas fa-mobile-alt me-2"></i> Mobile
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Amount Received</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text">Ksh</span>
                                <input type="number" class="form-control form-control-lg" name="amount_received" id="amountReceived" 
                                       step="0.01" min="0" required>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Order Total: <span id="orderTotalDisplay" class="fw-bold">0.00</span></small>
                                <small>Change: <span id="changeAmount" class="fw-bold">0.00</span></small>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-outline-secondary flex-grow-1" onclick="setAmount('exact')">
                                Exact Amount
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-grow-1" onclick="setAmount('round-up')">
                                Round Up
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-grow-1" onclick="setAmount('round-down')">
                                Round Down
                            </button>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="confirm_payment" class="btn btn-success">
                            <i class="fas fa-check me-1"></i> Confirm Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
    <script>
        // Light/Dark Mode Toggle
        document.addEventListener('DOMContentLoaded', function() {
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
            
            // Payment confirmation modal
            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            const confirmButtons = document.querySelectorAll('.confirm-payment');
            
            confirmButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const receiptId = this.getAttribute('data-receipt-id');
                    const receiptTotal = parseFloat(this.getAttribute('data-receipt-total'));
                    
                    document.getElementById('modalReceiptId').value = receiptId;
                    document.getElementById('orderTotalDisplay').textContent = receiptTotal.toFixed(2);
                    document.getElementById('amountReceived').value = receiptTotal.toFixed(2);
                    document.getElementById('amountReceived').min = receiptTotal.toFixed(2);
                    updateChangeAmount();
                    
                    paymentModal.show();
                });
            });
            
            // Quick payment button
            const quickPaymentBtn = document.getElementById('quickPaymentBtn');
            quickPaymentBtn.addEventListener('click', function() {
                if (confirmButtons.length > 0) {
                    const firstPayment = confirmButtons[0];
                    firstPayment.click();
                } else {
                    alert('No pending payments available');
                }
            });
            
            // Calculate change amount
            document.getElementById('amountReceived').addEventListener('input', updateChangeAmount);
            
            function updateChangeAmount() {
                const amountReceived = parseFloat(document.getElementById('amountReceived').value) || 0;
                const orderTotal = parseFloat(document.getElementById('orderTotalDisplay').textContent) || 0;
                const change = amountReceived - orderTotal;
                
                document.getElementById('changeAmount').textContent = change.toFixed(2);
                
                if (change < 0) {
                    document.getElementById('changeAmount').classList.add('text-danger');
                    document.getElementById('changeAmount').classList.remove('text-success');
                } else {
                    document.getElementById('changeAmount').classList.add('text-success');
                    document.getElementById('changeAmount').classList.remove('text-danger');
                }
            }
            
            // Set amount helpers
            window.setAmount = function(type) {
                const orderTotal = parseFloat(document.getElementById('orderTotalDisplay').textContent) || 0;
                let amount = orderTotal;
                
                switch(type) {
                    case 'exact':
                        amount = orderTotal;
                        break;
                    case 'round-up':
                        amount = Math.ceil(orderTotal);
                        break;
                    case 'round-down':
                        amount = Math.floor(orderTotal);
                        break;
                }
                
                document.getElementById('amountReceived').value = amount.toFixed(2);
                updateChangeAmount();
            }
            
            // Search functionality
            document.getElementById('searchInput').addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#paymentsTable tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
            
            // Auto-refresh every 60 seconds
            setTimeout(() => {
                window.location.reload();
            }, 60000);
            
            // Add animations to elements when they come into view
            const animateOnScroll = function() {
                const elements = document.querySelectorAll('.metric-card, .card');
                
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
    </script>
</body>
</html>