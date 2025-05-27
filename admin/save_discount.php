<?php
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $percentage = $_POST['percentage'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $active = $_POST['is_active'];

    try {
        $stmt = $pdo->prepare("INSERT INTO discounts (name, percentage, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $percentage, $start, $end, $active]);
        header("Location: discount.php");
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
}
