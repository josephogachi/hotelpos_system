<?php
require_once __DIR__ . '/../includes/db.php';

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
    $stmt->execute(['id' => $_GET['id']]);
}

header('Location: categories.php');
exit;
