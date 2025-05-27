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

// Get filter parameters
$dateFilter = $_GET['date'] ?? date('Y-m-d');
$paymentMethod = $_GET['method'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Build base query
$query = "SELECT 
    r.id, r.receipt_number, r.payment_method, r.payment_time, 
    r.amount_received, r.change_given, r.payment_status,
    u.full_name AS cashier_name, 
    o.total, o.table_number,
    waiter.full_name AS waiter_name
FROM receipts r
JOIN users u ON r.cashier_id = u.id
JOIN orders o ON r.order_id = o.id
JOIN users waiter ON o.waiter_id = waiter.id
WHERE DATE(r.payment_time) = :dateFilter";

// Add filters
$params = [':dateFilter' => $dateFilter];

if ($paymentMethod !== 'all') {
    $query .= " AND r.payment_method = :paymentMethod";
    $params[':paymentMethod'] = $paymentMethod;
}

if (!empty($searchQuery)) {
    $query .= " AND (r.receipt_number LIKE :searchQuery OR waiter.full_name LIKE :searchQuery)";
    $params[':searchQuery'] = "%$searchQuery%";
}

$query .= " ORDER BY r.payment_time DESC";

// Execute query
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Helper functions
function formatCurrency($amount) {
    return 'Ksh ' . number_format($amount, 2);
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Transactions | Hotel POS</title>
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
            padding: 0.5em 0.75em;
            font-weight: 500;
            border-radius: 8px;
        }
        
        .badge-paid {
            background: var(--secondary-gradient);
            color: white;
        }
        
        .badge-unpaid {
            background: var(--primary-gradient);
            color: white;
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
        
        /* Payment Method Badges */
        .badge-cash {
            background: linear-gradient(135deg, #4CAF50, #8BC34A);
            color: white;
        }
        
        .badge-card {
            background: linear-gradient(135deg, #2196F3, #03A9F4);
            color: white;
        }
        
        .badge-mobile {
            background: linear-gradient(135deg, #9C27B0, #E91E63);
            color: white;
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
                    <a href="payments.php" class="nav-link active">
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
        <!-- Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Payment Transactions</h2>
                    <p class="text-muted mb-0">View and filter payment history</p>
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
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card animate__animated animate__fadeIn">
            <div class="card-header">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="method" class="form-label">Payment Method</label>
                        <select class="form-select" id="method" name="method">
                            <option value="all" <?= $paymentMethod === 'all' ? 'selected' : '' ?>>All Methods</option>
                            <option value="cash" <?= $paymentMethod === 'cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="card" <?= $paymentMethod === 'card' ? 'selected' : '' ?>>Card</option>
                            <option value="mobile" <?= $paymentMethod === 'mobile' ? 'selected' : '' ?>>Mobile</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Receipt # or Waiter" value="<?= htmlspecialchars($searchQuery) ?>">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="card animate__animated animate__fadeIn">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Payment History</h5>
                <small class="text-muted"><?= count($payments) ?> records found</small>
            </div>
            <div class="card-body">
                <?php if (!empty($payments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Receipt #</th>
                                    <th>Date/Time</th>
                                    <th>Waiter</th>
                                    <th>Table</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th>Received</th>
                                    <th>Change</th>
                                    <th>Cashier</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr class="animate__animated animate__fadeIn">
                                        <td><strong><?= htmlspecialchars($payment['receipt_number'] ?? 'N/A') ?></strong></td>
                                        <td><?= formatDateTime($payment['payment_time'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($payment['waiter_name'] ?? 'Unknown') ?></td>
                                        <td><?= htmlspecialchars($payment['table_number'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $payment['payment_method'] ?? '' ?>">
                                                <?= ucfirst(htmlspecialchars($payment['payment_method'] ?? 'Unknown')) ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold"><?= formatCurrency($payment['total'] ?? 0) ?></td>
                                        <td><?= formatCurrency($payment['amount_received'] ?? 0) ?></td>
                                        <td><?= formatCurrency($payment['change_given'] ?? 0) ?></td>
                                        <td><?= htmlspecialchars($payment['cashier_name'] ?? 'Unknown') ?></td>
                                        <td>
                                            <span class="badge <?= ($payment['payment_status'] ?? '') === 'paid' ? 'badge-paid' : 'badge-unpaid' ?>">
                                                <?= ucfirst(htmlspecialchars($payment['payment_status'] ?? 'unknown')) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h5>No payment records found</h5>
                        <p class="text-muted">Try adjusting your filters</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
            
            // Auto-submit form when date or method changes
            document.getElementById('date').addEventListener('change', function() {
                this.closest('form').submit();
            });
            
            document.getElementById('method').addEventListener('change', function() {
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
    </script>
</body>
</html>