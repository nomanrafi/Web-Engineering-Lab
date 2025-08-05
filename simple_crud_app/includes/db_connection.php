<?php
$servername = "localhost"; // Or your server name
$username = "root";        // Your MySQL username
$password = "";            // Your MySQL password
$dbname = "simple_crud_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>