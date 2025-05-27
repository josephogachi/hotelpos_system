<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $pin = trim($_POST['pin']);
    $role = $_POST['role'];

    if (empty($name) || empty($full_name) || empty($username) || empty($pin) || empty($role)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: users.php");
        exit;
    }

    try {
        // Check for duplicate username or PIN
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR pin = ?");
        $checkStmt->execute([$username, $pin]);
        $exists = $checkStmt->fetchColumn();

        if ($exists > 0) {
            $_SESSION['error'] = "Username or PIN already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, full_name, username, pin, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $full_name, $username, $pin, $role]);

            $_SESSION['success'] = "User added successfully.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    header("Location: users.php");
    exit;
}
?>
