<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pin = trim($_POST['pin']);

    // Validate PIN (must be exactly 6 digits)
    if (!preg_match('/^\d{6}$/', $pin)) {
        $_SESSION['error'] = "PIN must be exactly 6 digits.";
        header("Location: ../index.php");
        exit;
    }

    // Use PDO to fetch user by PIN
    $stmt = $pdo->prepare("SELECT * FROM users WHERE pin = ?");
    $stmt->execute([$pin]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        // Redirect to dashboard based on role
        switch ($user['role']) {
            case 'admin':
                header("Location: ../admin/dashboard.php");
                break;
            case 'waiter':
                header("Location: ../waiter/dashboard.php");
                break;
            case 'cashier':
                header("Location: ../cashier/dashboard.php");
                break;
            case 'kitchen':
                header("Location: ../kitchen/dashboard.php");
                break;
            default:
                $_SESSION['error'] = "Unknown role. Contact admin.";
                header("Location: ../index.php");
                break;
        }
        exit;
    } else {
        $_SESSION['error'] = "Invalid PIN.";
        header("Location: ../index.php");
        exit;
    }
}
?>
