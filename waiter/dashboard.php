<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Fetch categories
$categoryStmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch products (default: first category or all)
$categoryId = $_GET['category_id'] ?? null;
if ($categoryId) {
    $productStmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ?");
    $productStmt->execute([$categoryId]);
} else {
    $productStmt = $pdo->query("SELECT * FROM products");
}
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiter Dashboard | HotelPOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF4E50;
            --primary-light: #FF7A5C;
            --secondary: #F9D423;
            --dark: #2D3748;
            --light: #F7FAFC;
            --gray: #E2E8F0;
            --success: #48BB78;
            --warning: #ED8936;
            --danger: #F56565;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F5F7FA;
            color: var(--dark);
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            min-height: 100vh;
            background: white;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.05);
            position: relative;
            z-index: 10;
            padding: 1.5rem 0;
        }

        .sidebar .logo-container {
            text-align: center;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }

        .hotel-logo {
            width: 80%;
            max-width: 120px;
            height: auto;
            margin-bottom: 0.5rem;
        }

        .hotel-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 0;
        }

        .sidebar .nav-link {
            color: var(--dark);
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 1rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
        }

        .sidebar .nav-link:hover {
            background: linear-gradient(to right, rgba(255, 78, 80, 0.1), rgba(249, 212, 35, 0.1));
            color: var(--primary);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 4px 6px rgba(255, 78, 80, 0.3);
        }

        .sidebar .nav-link.active i {
            color: white;
        }

        /* Main Content */
        .main-content {
            padding: 1.5rem;
            background-color: #F5F7FA;
        }

        .header {
            background: white;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .user-name {
            font-weight: 600;
            margin-bottom: 0;
        }

        .user-role {
            font-size: 0.75rem;
            color: #718096;
            background-color: var(--gray);
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
        }

        /* Category Tabs */
        .category-tabs {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .category-btn {
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            background-color: white;
            color: var(--dark);
            font-weight: 500;
            border: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            text-decoration: none !important;
        }

        .category-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-decoration: none;
        }

        .category-btn.active {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 4px 6px rgba(255, 78, 80, 0.3);
        }

        .category-btn.active i {
            color: white;
        }

        /* Product Cards */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.25rem;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid var(--gray);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .product-img-container {
            height: 120px;
            overflow: hidden;
            position: relative;
        }

        .product-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-img {
            transform: scale(1.05);
        }

        .product-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .product-content {
            padding: 0.75rem;
        }

        .product-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-description {
            font-size: 0.75rem;
            color: #718096;
            margin-bottom: 0.5rem;
            height: 36px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
        }

        .product-price {
            font-weight: 700;
            color: var(--primary);
            font-size: 1rem;
        }

        .product-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .qty-control {
            display: flex;
            align-items: center;
            background-color: var(--gray);
            border-radius: 20px;
            padding: 0.25rem;
        }

        .qty-btn {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .qty-btn:hover {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
        }

        .qty-value {
            width: 24px;
            text-align: center;
            font-size: 0.85rem;
        }

        .add-to-order {
            width: 100%;
            padding: 0.5rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .add-to-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 78, 80, 0.3);
        }

        /* Receipt Panel */
        .receipt-panel {
            background: white;
            height: 100vh;
            padding: 1.5rem;
            box-shadow: -4px 0 15px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .receipt-logo-img {
            width: 80px;
            height: auto;
            margin-bottom: 0.5rem;
        }

        .receipt-hotel-name {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .receipt-meta {
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 1rem;
        }

        .receipt-items {
            flex-grow: 1;
            overflow-y: auto;
            margin-bottom: 1rem;
        }

        .receipt-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px dashed var(--gray);
        }

        .receipt-item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .item-qty {
            font-size: 0.8rem;
            color: #718096;
            margin-left: 0.5rem;
        }

        .item-price {
            font-weight: 600;
        }

        .receipt-total {
            padding: 1rem 0;
            border-top: 2px solid var(--gray);
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            justify-content: space-between;
        }

        .receipt-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .action-btn {
            position: relative;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            text-align: center;
            min-height: 50px;
        }

        .action-btn i {
            font-size: 1.1rem;
        }

        .action-btn .btn-text {
            flex-grow: 1;
        }

        .action-btn.primary {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            grid-column: span 2;
        }

        .action-btn.primary:hover {
            box-shadow: 0 4px 8px rgba(255, 78, 80, 0.3);
            transform: translateY(-2px);
        }

        .action-btn.secondary {
            background-color: var(--success);
            color: white;
        }

        .action-btn.secondary:hover {
            background-color: #3d8b40;
        }

        .action-btn.danger {
            background-color: var(--danger);
            color: white;
        }

        .action-btn.danger:hover {
            background-color: #E53E3E;
        }

        .action-btn.dark {
            background-color: #2D3748;
            color: white;
        }

        .action-btn.dark:hover {
            background-color: #1A202C;
        }

        .action-divider {
            grid-column: span 2;
            height: 1px;
            background-color: var(--gray);
            margin: 0.5rem 0;
        }

        .print-status,
        .send-status {
            font-size: 0.8rem;
            margin-left: 5px;
            display: none;
        }

        /* Loading animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-left: 5px;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 1rem 0;
            }
            .sidebar .logo-container {
                padding: 0 0.5rem;
            }
            .hotel-name {
                display: none;
            }
            .hotel-logo {
                width: 50px;
            }
            .sidebar .nav-link span {
                display: none;
            }
            .sidebar .nav-link {
                justify-content: center;
                padding: 0.75rem;
            }
        }

        /* Alert placeholder */
        #alert-placeholder {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            width: 300px;
        }
    </style>
</head>
<body>
<div id="alert-placeholder"></div>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 col-lg-2 sidebar">
            <div class="logo-container">
                <img src="../assets/images/logo.jfif" alt="Hotel Logo" class="hotel-logo">
                <div class="hotel-name">SUNSET HOTEL</div>
            </div>
            <nav class="nav flex-column">
                <a href="#" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="orders.php" class="nav-link">
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
        <div class="col-md-7 col-lg-7 main-content">
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

            <!-- Category Tabs -->
            <!-- Replace your Category Tabs section with this: -->
<div class="category-tabs">
    <?php foreach ($categories as $cat): ?>
        <button class="category-btn <?= ($categoryId == $cat['id']) ? 'active' : '' ?>" 
                data-category-id="<?= $cat['id'] ?>">
            <i class="<?= match(strtolower($cat['name'])) {
                'breakfast' => 'fas fa-coffee',
                'lunch' => 'fas fa-utensils',
                'quick foods' => 'fas fa-hamburger',
                'drinks' => 'fas fa-glass-martini-alt',
                'pizza' => 'fas fa-pizza-slice',
                default => 'fas fa-tags',
            } ?>"></i>
            <span><?= htmlspecialchars($cat['name']) ?></span>
        </button>
    <?php endforeach; ?>
</div>

            <!-- Products Grid -->
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-img-container">
                            <img src="../uploads/<?= htmlspecialchars($product['image'] ?? 'placeholder.jpg') ?>" class="product-img" alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php if ($product['is_popular']): ?>
                                <div class="product-badge">Popular</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-content">
                            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                            <div class="product-description"><?= htmlspecialchars($product['description']) ?></div>
                            <div class="product-footer">
                                <div class="product-price">Ksh <?= number_format($product['price'], 2) ?></div>
                            </div>
                            <div class="qty-control">
                                <button class="qty-btn minus">−</button>
                                <span class="qty-value">1</span>
                                <button class="qty-btn plus">+</button>
                            </div>
                            <button class="add-to-order">
                                <i class="fas fa-plus"></i>
                                Add to Order
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Receipt Panel -->
        <div class="col-md-3 col-lg-3 receipt-panel">
            <div class="receipt-header">
                <img src="../assets/images/logo.jfif" alt="Hotel Logo" class="receipt-logo-img">
                <div class="receipt-hotel-name">SUNSET HOTEL</div>
                <div class="receipt-meta">
                    <div>Table: 05</div>
                    <div>Waiter: <?= $_SESSION['user_name'] ?? 'N/A' ?></div>
                    <div id="receipt-date"><?= date('d M Y, h:i A') ?></div>
                </div>
            </div>
            
            <div class="receipt-items" id="order-items">
                <!-- Order items will be added here dynamically -->
                <div class="text-center text-muted py-4">No items added yet</div>
            </div>
            
            <div class="receipt-total">
                <span>Total:</span>
                <span id="total-amount">Ksh 0.00</span>
            </div>
            
            <div class="receipt-actions">
                <button class="action-btn primary print-btn" title="Print receipt for customer">
                    <i class="fas fa-print"></i>
                    <span class="btn-text">Print Receipt</span>
                    <span class="print-status"></span>
                </button>
                <button class="action-btn secondary send-kitchen-btn" title="Send order to kitchen system">
                    <i class="fas fa-utensils"></i>
                    <span class="btn-text">Send to Kitchen</span>
                    <span class="send-status"></span>
                </button>
                <div class="action-divider"></div>
                <button class="action-btn danger clear-order-btn" title="Clear current order">
                    <i class="fas fa-eraser"></i>
                    <span class="btn-text">Clear Order</span>
                </button>
                <button class="action-btn dark save-order-btn" title="Save order for later">
                    <i class="fas fa-save"></i>
                    <span class="btn-text">Save Draft</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmModalLabel">Confirmation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="confirmModalBody">
        Are you sure?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmModalConfirmBtn">Confirm</button>
      </div>
    </div>
  </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Global variables to track order state
    let orderItems = [];
    let totalAmount = 0;
    
    // Update current time
    function updateTime() {
        const now = new Date();
        document.getElementById('current-time').textContent = now.toLocaleTimeString();
        document.getElementById('receipt-date').textContent = now.toLocaleDateString('en-US', {
            day: 'numeric', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }
    setInterval(updateTime, 1000);
    updateTime();

    // Quantity controls
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const control = this.closest('.qty-control');
            const valueEl = control.querySelector('.qty-value');
            let value = parseInt(valueEl.textContent);
            
            if (this.classList.contains('minus') && value > 1) {
                value--;
            } else if (this.classList.contains('plus')) {
                value++;
            }
            
            valueEl.textContent = value;
        });
    });

    // Add to order functionality
    document.querySelectorAll('.add-to-order').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.product-card');
            const name = card.querySelector('.product-name').textContent;
            const priceText = card.querySelector('.product-price').textContent;
            const price = parseFloat(priceText.replace('Ksh ', ''));
            const qty = parseInt(card.querySelector('.qty-value').textContent);
            
            // Check if item already exists in order
            const existingItemIndex = orderItems.findIndex(item => item.name === name);
            
            if (existingItemIndex >= 0) {
                // Update existing item quantity
                orderItems[existingItemIndex].qty += qty;
            } else {
                // Add new item to order
                orderItems.push({
                    name: name,
                    price: price,
                    qty: qty
                });
            }
            
            // Update total
            totalAmount += (price * qty);
            
            // Update receipt display
            updateReceiptDisplay();
            
            // Visual feedback
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Added';
            this.style.background = 'var(--success)';
            setTimeout(() => {
                this.innerHTML = originalHTML;
                this.style.background = 'linear-gradient(to right, var(--primary), var(--secondary))';
            }, 1000);
        });
    });

    // Update receipt display
    function updateReceiptDisplay() {
        const orderItemsEl = document.getElementById('order-items');
        const totalAmountEl = document.getElementById('total-amount');
        
        if (orderItems.length === 0) {
            orderItemsEl.innerHTML = '<div class="text-center text-muted py-4">No items added yet</div>';
            totalAmountEl.textContent = 'Ksh 0.00';
            return;
        }
        
        let itemsHTML = '';
        orderItems.forEach(item => {
            itemsHTML += `
                <div class="receipt-item">
                    <div>
                        <span class="item-name">${item.name}</span>
                        <span class="item-qty">x${item.qty}</span>
                    </div>
                    <div class="item-price">Ksh ${(item.price * item.qty).toFixed(2)}</div>
                </div>
            `;
        });
        
        orderItemsEl.innerHTML = itemsHTML;
        totalAmountEl.textContent = `Ksh ${totalAmount.toFixed(2)}`;
    }

    // Print Receipt Button - Updated version
document.querySelector('.print-btn').addEventListener('click', async function() {
    if (orderItems.length === 0) {
        showAlert('warning', 'No items to print!');
        return;
    }
    
    const btn = this;
    const statusEl = btn.querySelector('.print-status');
    
    try {
        // Show loading state
        btn.disabled = true;
        statusEl.innerHTML = '<span class="loading-spinner"></span> Printing...';
        statusEl.style.display = 'inline';
        
        // Prepare order data
        const orderData = {
            order_items: orderItems,
            total_amount: totalAmount,
            table_number: '05', // You should get this from your UI
            waiter_name: '<?= $_SESSION['user_name'] ?? 'Waiter' ?>',
            print_receipt: true
        };

        // Make the request
        const response = await fetch('receipt_print.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            statusEl.innerHTML = '<i class="fas fa-check"></i> Sent to Printer';
            showAlert('success', 'Order completed successfully');
            
            // Clear the current order after successful print
            orderItems = [];
            totalAmount = 0;
            updateReceiptDisplay();
            
            // Open print dialog for the receipt
            if (data.receipt_html) {
                const printWindow = window.open('', '_blank');
                printWindow.document.write(data.receipt_html);
                printWindow.document.close();
                printWindow.focus();
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 500);
            }
        } else {
            throw new Error(data.message || 'Unknown error occurred');
        }
    } catch (error) {
        console.error('Printing error:', error);
        statusEl.innerHTML = '<i class="fas fa-times"></i> Failed';
        showAlert('danger', `Printing failed: ${error.message}`);
    } finally {
        setTimeout(() => {
            btn.disabled = false;
            statusEl.style.display = 'none';
        }, 2000);
    }
});

    // Send to Kitchen Button
    document.querySelector('.send-kitchen-btn').addEventListener('click', function() {
        if (orderItems.length === 0) {
            showAlert('warning', 'No items to send!');
            return;
        }
        
        const btn = this;
        const statusEl = btn.querySelector('.send-status');
        
        // Show loading state
        btn.disabled = true;
        statusEl.innerHTML = '<span class="loading-spinner"></span> Sending...';
        statusEl.style.display = 'inline';
        
        // Simulate API call to kitchen system
        setTimeout(() => {
            // In a real app, you would make an AJAX call here
            statusEl.innerHTML = '<i class="fas fa-check"></i> Sent to Kitchen';
            
            // Optionally clear the order after sending
            // orderItems = [];
            // totalAmount = 0;
            // updateReceiptDisplay();
            
            setTimeout(() => {
                btn.disabled = false;
                statusEl.style.display = 'none';
            }, 2000);
        }, 1500);
    });

    // Clear Order Button
    document.querySelector('.clear-order-btn').addEventListener('click', function() {
        if (orderItems.length === 0) return;
        
        showConfirmDialog(
            'Clear Current Order',
            'Are you sure you want to clear this order? All items will be removed.',
            'danger',
            () => {
                orderItems = [];
                totalAmount = 0;
                updateReceiptDisplay();
                showAlert('success', 'Order cleared successfully');
            }
        );
    });

    // Save Draft Button
    document.querySelector('.save-order-btn').addEventListener('click', function() {
        if (orderItems.length === 0) {
            showAlert('warning', 'No items to save!');
            return;
        }
        
        // In a real app, you would save to database
        showAlert('success', 'Order draft saved successfully');
    });

    // Helper function to show alerts
    function showAlert(type, message) {
        // Using Bootstrap alerts if available
        if (typeof bootstrap !== 'undefined') {
            const alertPlaceholder = document.getElementById('alert-placeholder');
            const wrapper = document.createElement('div');
            wrapper.innerHTML = [
                `<div class="alert alert-${type} alert-dismissible fade show" role="alert">`,
                `   <div>${message}</div>`,
                '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
                '</div>'
            ].join('');
            
            alertPlaceholder.append(wrapper);
            
            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                const alert = bootstrap.Alert.getOrCreateInstance(wrapper.querySelector('.alert'));
                alert.close();
            }, 3000);
        } else {
            // Fallback to simple alert
            alert(`${type.toUpperCase()}: ${message}`);
        }
    }

    // Helper function for confirmation dialogs
    function showConfirmDialog(title, message, type, confirmCallback) {
        // Check if Bootstrap is loaded
        if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            // Fallback to native confirm
            if (confirm(`${title}\n\n${message}`)) {
                confirmCallback();
            }
            return;
        }

        // Ensure modal exists in DOM
        let modalEl = document.getElementById('confirmModal');
        if (!modalEl) {
            console.error('Confirm modal element not found in DOM');
            return;
        }

        // Get or create modal instance
        let modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) {
            modal = new bootstrap.Modal(modalEl);
        }

        // Update modal content
        document.getElementById('confirmModalLabel').textContent = title;
        document.getElementById('confirmModalBody').textContent = message;
        
        // Update confirm button style
        const confirmBtn = document.getElementById('confirmModalConfirmBtn');
        confirmBtn.className = `btn btn-${type}`;
        
        // Clear previous event listeners
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        // Add new event listener
        document.getElementById('confirmModalConfirmBtn').addEventListener('click', function() {
            modal.hide();
            confirmCallback();
        });
        
        // Show modal
        modal.show();
    }

    // Initialize the order display
    updateReceiptDisplay();
    // Load order items from session storage on page load
function loadOrderFromStorage() {
    const savedOrder = sessionStorage.getItem('currentOrder');
    if (savedOrder) {
        const parsed = JSON.parse(savedOrder);
        orderItems = parsed.items || [];
        totalAmount = parsed.total || 0;
        updateReceiptDisplay();
    }
}

// Save order items to session storage
function saveOrderToStorage() {
    sessionStorage.setItem('currentOrder', JSON.stringify({
        items: orderItems,
        total: totalAmount
    }));
}

// Update the updateReceiptDisplay function to save to storage
function updateReceiptDisplay() {
    const orderItemsEl = document.getElementById('order-items');
    const totalAmountEl = document.getElementById('total-amount');
    
    if (orderItems.length === 0) {
        orderItemsEl.innerHTML = '<div class="text-center text-muted py-4">No items added yet</div>';
        totalAmountEl.textContent = 'Ksh 0.00';
    } else {
        let itemsHTML = '';
        orderItems.forEach(item => {
            itemsHTML += `
                <div class="receipt-item">
                    <div>
                        <span class="item-name">${item.name}</span>
                        <span class="item-qty">x${item.qty}</span>
                    </div>
                    <div class="item-price">Ksh ${(item.price * item.qty).toFixed(2)}</div>
                </div>
            `;
        });
        orderItemsEl.innerHTML = itemsHTML;
        totalAmountEl.textContent = `Ksh ${totalAmount.toFixed(2)}`;
    }
    
    saveOrderToStorage();
}

// Add category switching functionality
document.querySelectorAll('.category-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const categoryId = this.dataset.categoryId;
        
        // Update active state
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Load products via AJAX
        loadProducts(categoryId);
    });
});

// AJAX function to load products
function loadProducts(categoryId) {
    fetch(`load_products.php?category_id=${categoryId}`)
        .then(response => response.text())
        .then(html => {
            document.querySelector('.products-grid').innerHTML = html;
            setupProductEventListeners();
        })
        .catch(error => {
            console.error('Error loading products:', error);
        });
}

// Setup event listeners for newly loaded products
function setupProductEventListeners() {
    // Quantity controls
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const control = this.closest('.qty-control');
            const valueEl = control.querySelector('.qty-value');
            let value = parseInt(valueEl.textContent);
            
            if (this.classList.contains('minus') && value > 1) {
                value--;
            } else if (this.classList.contains('plus')) {
                value++;
            }
            
            valueEl.textContent = value;
        });
    });

    // Add to order buttons
    document.querySelectorAll('.add-to-order').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.product-card');
            const name = card.querySelector('.product-name').textContent;
            const priceText = card.querySelector('.product-price').textContent;
            const price = parseFloat(priceText.replace('Ksh ', ''));
            const qty = parseInt(card.querySelector('.qty-value').textContent);
            
            // Check if item already exists in order
            const existingItemIndex = orderItems.findIndex(item => item.name === name);
            
            if (existingItemIndex >= 0) {
                // Update existing item quantity
                orderItems[existingItemIndex].qty += qty;
            } else {
                // Add new item to order
                orderItems.push({
                    name: name,
                    price: price,
                    qty: qty
                });
            }
            
            // Update total
            totalAmount += (price * qty);
            
            // Update receipt display
            updateReceiptDisplay();
            
            // Visual feedback
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Added';
            this.style.background = 'var(--success)';
            setTimeout(() => {
                this.innerHTML = originalHTML;
                this.style.background = 'linear-gradient(to right, var(--primary), var(--secondary))';
            }, 1000);
        });
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadOrderFromStorage();
    setupProductEventListeners();
});
// Auto-refresh dashboard metrics every 30 seconds
function refreshDashboardMetrics() {
    fetch('get_dashboard_metrics.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update Today's Revenue
                document.querySelector('.metric:nth-child(1) h3').textContent = `Ksh ${data.today_revenue}`;
                
                // Update Orders Today
                document.querySelector('.metric:nth-child(2) h3').textContent = data.orders_today;
                
                // Update Peak Hour
                document.querySelector('.metric:nth-child(3) h3').textContent = data.peak_hour;
                
                // Update Top Product
                document.querySelector('.metric:nth-child(4) h3').textContent = data.top_product_name;
            }
        })
        .catch(error => console.error('Error refreshing metrics:', error));
}

// Refresh immediately and then every 30 seconds
refreshDashboardMetrics();
setInterval(refreshDashboardMetrics, 30000); // 30 seconds
</script>
</body>
</html>