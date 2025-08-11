<?php
session_start();
require_once 'database_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = htmlspecialchars($_GET['id']); // Sanitize the ID

    // Get the photo path before deleting
    $stmt = $conn->prepare("SELECT photo_path FROM biodata WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $biodata = $result->fetch_assoc();

    // Delete the biodata record
    $stmt = $conn->prepare("DELETE FROM biodata WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Delete the photo file if it exists
        if (!empty($biodata['photo_path']) && file_exists($biodata['photo_path'])) {
            // Attempt to unlink the file and provide specific feedback
            if (unlink($biodata['photo_path'])) {
                $_SESSION['success'] = "Biodata and associated photo deleted successfully!";
            } else {
                $_SESSION['success'] = "Biodata deleted successfully, but the photo file could not be deleted (permission issue or file in use).";
            }
        } else {
            $_SESSION['success'] = "Biodata deleted successfully!";
        }
    } else {
        $_SESSION['error'] = "Error deleting biodata: " . $conn->error; // Provide specific MySQL error
    }
} else {
    $_SESSION['error'] = "No biodata ID provided for deletion.";
}

header("Location: dashboard.php");
exit();
?>