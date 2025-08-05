<?php
session_start();
include('includes/db_connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit();
    } else {
        // Handle error, e.g., display a message
        echo "Error deleting user: " . $conn->error;
    }
} else {
    // If no ID is provided, redirect to dashboard
    header("Location: dashboard.php");
    exit();
}
?>