<?php
session_start();
require_once 'database_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Don't allow deleting your own account
    if ($id == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own account!";
        header("Location: view_user.php");
        exit();
    }
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "User deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting user!";
    }
}

header("Location: view_user.php");
exit();
?>
