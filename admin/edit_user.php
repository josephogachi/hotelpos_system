<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $pin = $_POST['pin'];

    if (empty($name) || empty($full_name) || empty($username) || empty($role) || empty($pin)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: users.php");
        exit;
    }

    try {
        // Check for duplicate username or pin (excluding current user)
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR pin = ?) AND id != ?");
        $checkStmt->execute([$username, $pin, $id]);
        if ($checkStmt->fetch()) {
            $_SESSION['error'] = "Username or PIN already in use by another user.";
            header("Location: users.php");
            exit;
        }

        // Update user data
        $stmt = $pdo->prepare("UPDATE users SET name = ?, full_name = ?, username = ?, role = ?, pin = ? WHERE id = ?");
        $stmt->execute([$name, $full_name, $username, $role, $pin, $id]);

        $_SESSION['success'] = "User updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    header("Location: users.php");
    exit;
}
?>
