<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

try {
    $orderId = $_GET['order_id'] ?? null;
    $status = $_GET['status'] ?? null;
    
    if (!$orderId || !$status) {
        throw new Exception('Missing parameters');
    }
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}