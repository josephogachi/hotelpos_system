<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

try {
    // Get today's total revenue
    $today_revenue_stmt = $pdo->prepare("
        SELECT SUM(oi.quantity * oi.price) AS total
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) = CURDATE()
    ");
    $today_revenue_stmt->execute();
    $today_revenue = $today_revenue_stmt->fetchColumn();
    $today_revenue = $today_revenue ? number_format($today_revenue, 2) : "0.00";

    // Get number of orders today
    $orders_today_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
    $orders_today_stmt->execute();
    $orders_today = $orders_today_stmt->fetchColumn();

    // Get peak hour
    $peak_stmt = $pdo->prepare("
        SELECT HOUR(created_at) as hr, COUNT(*) as cnt
        FROM orders
        WHERE DATE(created_at) = CURDATE()
        GROUP BY hr
        ORDER BY cnt DESC
        LIMIT 1
    ");
    $peak_stmt->execute();
    $peak = $peak_stmt->fetch(PDO::FETCH_ASSOC);
    $peak_hour = $peak ? sprintf("%02d:00 - %02d:00", $peak['hr'], $peak['hr'] + 1) : 'N/A';

    // Get top-selling product today
    $top_product_stmt = $pdo->prepare("
        SELECT p.name, SUM(oi.quantity) as total_qty
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) = CURDATE()
        GROUP BY p.id
        ORDER BY total_qty DESC
        LIMIT 1
    ");
    $top_product_stmt->execute();
    $top_product = $top_product_stmt->fetch(PDO::FETCH_ASSOC);
    $top_product_name = $top_product ? htmlspecialchars($top_product['name']) : 'N/A';

    echo json_encode([
        'success' => true,
        'today_revenue' => $today_revenue,
        'orders_today' => $orders_today,
        'peak_hour' => $peak_hour,
        'top_product_name' => $top_product_name
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}