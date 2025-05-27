<?php
require_once __DIR__ . '/../includes/db.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'No code provided']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT r.*, o.total, o.table_number, u.full_name
    FROM receipts r
    JOIN orders o ON r.order_id = o.id
    LEFT JOIN users u ON o.user_id = u.id
    WHERE r.unique_code = ?
");
$stmt->execute([$code]);
$receipt = $stmt->fetch(PDO::FETCH_ASSOC);

if ($receipt) {
    echo json_encode(['success' => true, 'receipt' => $receipt]);
} else {
    echo json_encode(['success' => false, 'message' => 'Receipt not found']);
}
