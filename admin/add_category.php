<?php
require_once __DIR__ . '/../includes/db.php';

$name = $_POST['name'];
$icon = $_POST['icon'] ?? '';

$stmt = $pdo->prepare("INSERT INTO categories (name, icon) VALUES (?, ?)");
$stmt->execute([$name, $icon]);

header("Location: dashboard.php");
exit;
