<?php
require_once __DIR__ . '/../includes/db.php';

$name = $_POST['name'];
$percentage = (int) $_POST['percentage'];

$stmt = $pdo->prepare("INSERT INTO discounts (name, percentage) VALUES (?, ?)");
$stmt->execute([$name, $percentage]);

header("Location: dashboard.php");
exit;
