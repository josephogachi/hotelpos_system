<?php
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiptId = $_POST['receipt_id'] ?? null;
    $method = $_POST['payment_method'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    
    try {
        $pdo->beginTransaction();
        
        // Update receipt payment status
        $stmt = $pdo->prepare("UPDATE receipts SET payment_status = 'paid', payment_time = NOW() WHERE id = ?");
        $stmt->execute([$receiptId]);
        
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders o JOIN receipts r ON o.id = r.order_id SET o.status = 'closed' WHERE r.id = ?");
        $stmt->execute([$receiptId]);
        
        // Record payment
        $stmt = $pdo->prepare("INSERT INTO payments (receipt_id, amount, method, processed_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$receiptId, $amount, $method, $_SESSION['user_id']]);
        
        // Update receipt status
        $stmt = $pdo->prepare("INSERT INTO receipt_status (receipt_id, status) VALUES (?, 'paid')");
        $stmt->execute([$receiptId]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}