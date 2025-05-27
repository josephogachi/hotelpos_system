<?php
require_once __DIR__ . '/../includes/db.php';

$labels = [];
$sales = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('D', strtotime($date));

    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM orders WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $sales[] = $stmt->fetchColumn() ?? 0;
}

echo json_encode(['labels' => $labels, 'sales' => $sales]);
