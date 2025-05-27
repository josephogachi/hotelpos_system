<?php
require_once __DIR__ . '/../includes/db.php';
$id = $_GET['id'];

$table = strpos($_SERVER['PHP_SELF'], 'discount') !== false ? 'discounts' : 'categories';
$pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);

header("Location: dashboard.php");
exit;
