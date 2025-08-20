<?php

// logout.php - Handles user logout



// Start the session to access session variables

session_start();



// Include the database connection file (optional for logout, but good practice if other session-related DB operations were needed)

require_once 'db_connect.php';



// Unset all session variables

$_SESSION = array();



// Destroy the session

session_destroy();



// Redirect to the login page

header('Location: login.php');

exit();

?>