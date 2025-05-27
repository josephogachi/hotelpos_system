<?php
require_once __DIR__ . '/../includes/db.php';

if (!isset($_GET['order_id'])) {
    echo "Invalid order.";
    exit;
}

$orderId = intval($_GET['order_id']);

// Fetch order
$stmt = $pdo->prepare("SELECT o.*, u.full_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Order not found.";
    exit;
}

// Fetch items
$stmt = $pdo->prepare("
    SELECT p.name, oi.quantity, oi.price
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Receipt #<?= $orderId ?></title>
    <style>
        body { font-family: monospace; padding: 30px; background: #fff; color: #000; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px dashed #ccc; }
        .total { font-weight: bold; }
        .print-btn {
            margin-top: 20px;
            text-align: center;
        }
        .print-btn button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        .print-btn button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h2>HotelPOS Receipt</h2>
    <p><strong>Receipt #: </strong> <?= $order['id'] ?></p>
    <p><strong>Customer: </strong> <?= htmlspecialchars($order['full_name'] ?: 'Unknown') ?></p>
    <p><strong>Date: </strong> <?= date("Y-m-d H:i", strtotime($order['created_at'])) ?></p>

    <table>
        <thead>
            <tr>
                <th>Item</th><th>Qty</th><th>Price</th><th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= number_format($item['price'], 2) ?></td>
                    <td><?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="3" style="text-align:right;">Grand Total:</td>
                <td>Ksh <?= number_format($order['total'], 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="print-btn">
        <button onclick="window.print()">Print Receipt</button>
    </div>
</body>
</html>
