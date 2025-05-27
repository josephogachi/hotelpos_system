 
<?php
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = intval($_POST['order_id']);
    $stmt = $pdo->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
    $stmt->execute([$orderId]);
}

header("Location: dashboard.php");
exit;
