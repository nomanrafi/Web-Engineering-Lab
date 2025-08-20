<?php
session_start();
// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Add headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include the database connection file
require_once 'db_connect.php';

// Fetch User-specific data for the header
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

$user_info_query = "SELECT role, division, territory FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($user_info_query);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user_info = $user_result->fetch_assoc();
$user_role = $user_info['role'] ?? 'Unknown Role';
$user_territory = $user_info['territory'] ?? 'Global';
$user_division = $user_info['division'] ?? 'Headquarters';

$initials = strtoupper(substr($user_name, 0, 1) . substr(strstr($user_name, ' '), 1, 1));
$header_location = $user_division . ' • ' . $user_territory;

// --- Add User Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $role = trim($_POST['role']);
    $password = $_POST['password'];
    $status = trim($_POST['status']);
    $division = trim($_POST['division']) ?? null;
    $district = trim($_POST['district']) ?? null;
    $upazila = trim($_POST['upazila']) ?? null;
    $territory = trim($_POST['territory']) ?? null;
    $reports_to_full_name = trim($_POST['reports_to']) ?? null;
    
    // Validation
    if (empty($full_name) || empty($email) || empty($phone_number) || empty($role) || empty($password)) {
        $_SESSION['user_message'] = "All fields except for location details are required.";
        $_SESSION['user_is_error'] = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['user_message'] = "Invalid email format.";
        $_SESSION['user_is_error'] = true;
    } else {
        $check_email_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check_email_stmt->bind_param("s", $email);
        $check_email_stmt->execute();
        $check_email_stmt->store_result();
        
        if ($check_email_stmt->num_rows > 0) {
            $_SESSION['user_message'] = "Error: User with this email already exists.";
            $_SESSION['user_is_error'] = true;
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $reports_to_user_id = null;
            if (!empty($reports_to_full_name)) {
                $find_manager_stmt = $conn->prepare("SELECT user_id FROM users WHERE full_name = ?");
                $find_manager_stmt->bind_param("s", $reports_to_full_name);
                $find_manager_stmt->execute();
                $manager_result = $find_manager_stmt->get_result();
                if ($manager_result->num_rows > 0) {
                    $manager_row = $manager_result->fetch_assoc();
                    $reports_to_user_id = $manager_row['user_id'];
                }
                $find_manager_stmt->close();
            }
            
            $stmt_add_user = $conn->prepare("INSERT INTO users (full_name, email, phone_number, role, password, status, division, district, upazila, territory, reports_to_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt_add_user === false) {
                $_SESSION['user_message'] = "Database error: Could not prepare statement.";
                $_SESSION['user_is_error'] = true;
            } else {
                $stmt_add_user->bind_param("ssssssssssi", $full_name, $email, $phone_number, $role, $hashed_password, $status, $division, $district, $upazila, $territory, $reports_to_user_id);
                if ($stmt_add_user->execute()) {
                    $_SESSION['user_message'] = "User added successfully!";
                    $_SESSION['user_is_error'] = false;
                } else {
                    $_SESSION['user_message'] = "Error adding user: " . $stmt_add_user->error;
                    $_SESSION['user_is_error'] = true;
                }
                $stmt_add_user->close();
            }
        }
        $check_email_stmt->close();
    }
    header('Location: user_management.php');
    exit();
}

// --- Update User Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $user_id_to_update = trim($_POST['user_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $role = trim($_POST['role']);
    $status = trim($_POST['status']);
    $division = trim($_POST['division']) ?? null;
    $district = trim($_POST['district']) ?? null;
    $upazila = trim($_POST['upazila']) ?? null;
    $territory = trim($_POST['territory']) ?? null;
    $reports_to_full_name = trim($_POST['reports_to']) ?? null;

    if (empty($user_id_to_update) || empty($full_name) || empty($email) || empty($phone_number) || empty($role) || empty($status)) {
        $_SESSION['user_message'] = "All fields except for location details are required.";
        $_SESSION['user_is_error'] = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['user_message'] = "Invalid email format.";
        $_SESSION['user_is_error'] = true;
    } else {
        $reports_to_user_id = null;
        if (!empty($reports_to_full_name)) {
            $find_manager_stmt = $conn->prepare("SELECT user_id FROM users WHERE full_name = ? AND user_id != ?");
            $find_manager_stmt->bind_param("si", $reports_to_full_name, $user_id_to_update);
            $find_manager_stmt->execute();
            $manager_result = $find_manager_stmt->get_result();
            if ($manager_result->num_rows > 0) {
                $manager_row = $manager_result->fetch_assoc();
                $reports_to_user_id = $manager_row['user_id'];
            }
            $find_manager_stmt->close();
        }

        $stmt_update_user = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone_number = ?, role = ?, status = ?, division = ?, district = ?, upazila = ?, territory = ?, reports_to_user_id = ? WHERE user_id = ?");
        
        if ($stmt_update_user === false) {
            $_SESSION['user_message'] = "Database error: Could not prepare update statement.";
            $_SESSION['user_is_error'] = true;
        } else {
            $stmt_update_user->bind_param("sssssssssii", $full_name, $email, $phone_number, $role, $status, $division, $district, $upazila, $territory, $reports_to_user_id, $user_id_to_update);
            if ($stmt_update_user->execute()) {
                if ($stmt_update_user->affected_rows > 0) {
                    $_SESSION['user_message'] = "User updated successfully!";
                    $_SESSION['user_is_error'] = false;
                } else {
                    $_SESSION['user_message'] = "No changes were made or user not found.";
                    $_SESSION['user_is_error'] = false;
                }
            } else {
                $_SESSION['user_message'] = "Error updating user: " . $stmt_update_user->error;
                $_SESSION['user_is_error'] = true;
            }
            $stmt_update_user->close();
        }
    }
    header('Location: user_management.php');
    exit();
}

// --- Delete User Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['user_id'])) {
    $user_id_to_delete = filter_var($_GET['user_id'], FILTER_SANITIZE_NUMBER_INT);
    
    if ($user_id_to_delete) {
        $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        if ($stmt_delete_user === false) {
            $_SESSION['user_message'] = "Database error: Could not prepare delete statement.";
            $_SESSION['user_is_error'] = true;
        } else {
            $stmt_delete_user->bind_param("i", $user_id_to_delete);
            if ($stmt_delete_user->execute()) {
                if ($stmt_delete_user->affected_rows > 0) {
                    $_SESSION['user_message'] = "User deleted successfully!";
                    $_SESSION['user_is_error'] = false;
                } else {
                    $_SESSION['user_message'] = "Error: User not found.";
                    $_SESSION['user_is_error'] = true;
                }
            } else {
                $_SESSION['user_message'] = "Error deleting user: " . $stmt_delete_user->error;
                $_SESSION['user_is_error'] = true;
            }
            $stmt_delete_user->close();
        }
    } else {
        $_SESSION['user_message'] = "Invalid user ID.";
        $_SESSION['user_is_error'] = true;
    }
    header('Location: user_management.php');
    exit();
}

// --- Fetch Dynamic Data for User Metrics ---
$total_users = 0;
$active_users = 0;
$inactive_users = 0;
$new_registrations = 0;
// Total Users
$query_total_users = "SELECT COUNT(*) as total FROM users";
$result_total_users = $conn->query($query_total_users);
if ($result_total_users && $result_total_users->num_rows > 0) {
    $row_total_users = $result_total_users->fetch_assoc();
    $total_users = $row_total_users['total'] ?? 0;
}
// Active Users
$query_active_users = "SELECT COUNT(*) as total FROM users WHERE status = 'Active'";
$result_active_users = $conn->query($query_active_users);
if ($result_active_users && $result_active_users->num_rows > 0) {
    $row_active_users = $result_active_users->fetch_assoc();
    $active_users = $row_active_users['total'] ?? 0;
}
// Inactive Users
$query_inactive_users = "SELECT COUNT(*) as total FROM users WHERE status = 'Inactive'";
$result_inactive_users = $conn->query($query_inactive_users);
if ($result_inactive_users && $result_inactive_users->num_rows > 0) {
    $row_inactive_users = $result_inactive_users->fetch_assoc();
    $inactive_users = $row_inactive_users['total'] ?? 0;
}
// New Registrations (assuming 'New' status in the users table)
$query_new_registrations = "SELECT COUNT(*) as total FROM users WHERE status = 'New'";
$result_new_registrations = $conn->query($query_new_registrations);
if ($result_new_registrations && $result_new_registrations->num_rows > 0) {
    $row_new_registrations = $result_new_registrations->fetch_assoc();
    $new_registrations = $row_new_registrations['total'] ?? 0;
}
// --- Fetch User List Data ---
$user_list = [];
$query_user_list = "
    SELECT
        u.user_id,
        u.full_name,
        u.email,
        u.phone_number,
        u.role,
        u.division,
        u.district,
        u.upazila,
        u.territory,
        u.status,
        u.last_login,
        COALESCE(SUM(o.order_total), 0) AS total_sales,
        COUNT(DISTINCT o.order_id) AS orders_created,
        COUNT(DISTINCT c.customer_id) AS customers_managed,
        m.full_name AS reports_to_name
    FROM
        users u
    LEFT JOIN
        orders o ON u.user_id = o.sales_rep_id
    LEFT JOIN
        customers c ON u.user_id = c.added_by_user_id
    LEFT JOIN
        users m ON u.reports_to_user_id = m.user_id
    GROUP BY
        u.user_id, u.full_name, u.email, u.phone_number, u.role, u.division, u.district, u.territory, u.status, u.last_login, m.full_name
    ORDER BY
        u.full_name ASC;
";
$result_user_list = $conn->query($query_user_list);
if ($result_user_list && $result_user_list->num_rows > 0) {
    while ($row = $result_user_list->fetch_assoc()) {
        $user_list[] = $row;
    }
}
// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Navigator - User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" xintegrity="sha512-Fo3rlalHpgO702sN1l8/p5pT+c+W5U/6a7F6S3a5b0S3e5w1/b0R7+v5F5t5C5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        :root {
            --sidebar-bg: #1a1a1a;
            --main-bg: #0c0e0c;
            --card-bg: #1c1c1c;
            --text-color: #e0e0e0;
            --subtle-text: #a0a0a0;
            --highlight-green: #00ff66;
            --highlight-blue: #00bfff;
            --highlight-yellow: #ffd700;
            --highlight-red: #ff4d4d;
            --gradient-start: #00ff66;
            --gradient-end: #00cc55;
            --border-color: rgba(0, 255, 100, 0.1);
            --shadow-color: rgba(0, 0, 0, 0.3);
        }
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            background-color: var(--main-bg);
            color: var(--text-color);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }
        /* Sidebar Styling (Consistent with other pages) */
        .sidebar {
            width: 250px;
            min-width: 250px;
            background-color: var(--sidebar-bg);
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            top: 0;
            left: 0;
            overflow-y: auto;
            transition: transform 0.3s ease-in-out;
            z-index: 1001;
        }
        .sidebar.active {
            transform: translateX(0);
        }
        .sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-header .logo-icon {
            font-size: 2.5rem;
            color: var(--highlight-green);
            margin-right: 10px;
        }
        .sidebar-header .app-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-color);
        }
        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-nav li {
            margin-bottom: 10px;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 10px;
            color: var(--subtle-text);
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s, color 0.2s, box-shadow 0.2s;
        }
        .sidebar-nav a i {
            margin-right: 12px;
            font-size: 1.1rem;
        }
        .sidebar-nav a:hover {
            background-color: rgba(0, 255, 100, 0.1);
            color: var(--highlight-green);
            box-shadow: 0 0 8px rgba(0, 255, 100, 0.2);
        }
        .sidebar-nav a.active {
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            color: #1a1a1a;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 255, 100, 0.4);
        }
        /* Main Content Styling */
        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 290px;
            width: calc(100% - 290px);
            min-width: 0;
        }
        .main-content.overlay::before {
            content: '';
            display: block;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            pointer-events: auto;
        }
        /* Header Styling (Consistent with Dashboard) */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        .header-left {
            display: flex;
            align-items: center;
        }
        .header-left .menu-toggle {
            background: none;
            border: none;
            color: var(--text-color);
            font-size: 1.5rem;
            cursor: pointer;
        }
        .header-left .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        .header-right {
            display: flex;
            align-items: center;
        }
        .header-right .user-profile {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
        }
        .header-right .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--highlight-green);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-left: 10px;
        }
        .header-right .user-role {
            font-size: 0.8rem;
            color: var(--subtle-text);
            margin-left: 5px;
        }
        /* User Management Specific Styles */
        .user-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 20px;
        }
        .user-controls .search-bar {
            flex: 1;
            position: relative;
            min-width: 200px;
        }
        .user-controls .search-bar input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-color);
            font-size: 0.9rem;
            outline: none;
            box-sizing: border-box;
        }
        .user-controls .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--subtle-text);
            pointer-events: none;
        }
        .user-controls .status-filter {
            width: 150px;
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            padding: 12px 35px 12px 15px;
            border-radius: 10px;
            font-size: 0.9rem;
            appearance: none;
            -webkit-appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23a0a0a0"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 18px;
            cursor: pointer;
        }
        .user-controls .add-user-btn {
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            color: #1a1a1a;
            padding: 10px 15px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s;
        }
        .user-controls .add-user-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 255, 100, 0.4);
        }
        .user-summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .summary-card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid var(--border-color);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }
        .summary-card .icon {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        .summary-card .value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .summary-card .label {
            font-size: 0.8rem;
            color: var(--subtle-text);
        }
        .summary-card.green::before { background-color: var(--highlight-green); }
        .summary-card.blue::before { background-color: var(--highlight-blue); }
        .summary-card.yellow::before { background-color: var(--highlight-yellow); }
        .summary-card.red::before { background-color: var(--highlight-red); }
        .summary-card.green .value,
        .summary-card.green .icon { color: var(--highlight-green); }
        .summary-card.blue .value,
        .summary-card.blue .icon { color: var(--highlight-blue); }
        .summary-card.yellow .value,
        .summary-card.yellow .icon { color: var(--highlight-yellow); }
        .summary-card.red .value,
        .summary-card.red .icon { color: var(--highlight-red); }
        .user-list-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .user-card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
        }
        .user-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .user-avatar-lg {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--highlight-green);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-right: 15px;
        }
        .user-info-main {
            flex-grow: 1;
            text-align: left;
        }
        .user-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
        }
        .user-contact-info {
            font-size: 0.85rem;
            color: var(--subtle-text);
            margin-top: 5px;
        }
        .user-status-badge {
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: auto;
        }
        .user-status-badge.active { background-color: rgba(0, 255, 100, 0.2); color: var(--highlight-green); }
        .user-status-badge.inactive { background-color: rgba(255, 77, 77, 0.2); color: var(--highlight-red); }
        .user-status-badge.new { background-color: rgba(0, 191, 255, 0.2); color: var(--highlight-blue); }
        .user-details {
            font-size: 0.9rem;
            color: var(--text-color);
            margin-bottom: 20px;
        }
        .user-details .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        .user-details .detail-row span:first-child {
            color: var(--subtle-text);
            font-size: 0.85rem;
        }
        .user-actions {
            display: flex;
            justify-content: space-around;
            gap: 10px;
            margin-top: auto;
        }
        .user-actions .btn {
            flex-grow: 1;
            padding: 10px 15px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .user-actions .btn:hover {
            transform: translateY(-2px);
        }
        .user-actions .btn.view-profile {
            background-color: rgba(0, 191, 255, 0.2);
            color: var(--highlight-blue);
        }
        .user-actions .btn.view-profile:hover {
            box-shadow: 0 4px 10px rgba(0, 191, 255, 0.4);
        }
        .user-actions .btn.edit {
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            color: #1a1a1a;
        }
        .user-actions .btn.edit:hover {
            box-shadow: 0 4px 10px rgba(0, 255, 100, 0.4);
        }
        .user-actions .btn.delete {
            background-color: rgba(255, 77, 77, 0.2);
            color: var(--highlight-red);
        }
        .user-actions .btn.delete:hover {
            box-shadow: 0 4px 10px rgba(255, 77, 77, 0.4);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1002;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: var(--card-bg);
            margin: auto;
            padding: 30px;
            border: 1px solid var(--border-color);
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
            animation-name: animatetop;
            animation-duration: 0.4s;
            position: relative;
        }
        .modal-content h2 {
            color: var(--highlight-green);
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        .modal-content .form-group {
            margin-bottom: 15px;
        }
        .modal-content label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-color);
            font-size: 0.9rem;
        }
        .modal-content input[type="text"],
        .modal-content input[type="email"],
        .modal-content input[type="password"],
        .modal-content select {
            width: calc(100% - 20px);
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--field-bg);
            color: var(--text-color);
            font-size: 0.9rem;
            box-sizing: border-box;
        }
        .modal-content input[type="text"]:focus,
        .modal-content input[type="email"]:focus,
        .modal-content input[type="password"]:focus,
        .modal-content select:focus {
            border-color: var(--highlight-green);
            outline: none;
            box-shadow: 0 0 5px rgba(0, 255, 100, 0.3);
        }
        .modal-content .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .modal-content .modal-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s, box-shadow 0.2s;
        }
        .modal-content .modal-actions .cancel-btn {
            background-color: var(--subtle-text);
            color: var(--main-bg);
        }
        .modal-content .modal-actions .cancel-btn:hover {
            background-color: #808080;
        }
        .modal-content .modal-actions .submit-btn {
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            color: #1a1a1a;
        }
        .modal-content .modal-actions .submit-btn:hover {
            box-shadow: 0 4px 10px rgba(0, 255, 100, 0.4);
        }
        /* Close Button */
        .close-button {
            color: var(--subtle-text);
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            right: 20px;
            top: 10px;
        }
        .close-button:hover,
        .close-button:focus {
            color: var(--text-color);
            text-decoration: none;
            cursor: pointer;
        }
        /* Animations */
        @-webkit-keyframes animatetop {
            from {top:-300px; opacity:0} 
            to {top:0; opacity:1}
        }
        @keyframes animatetop {
            from {top:-300px; opacity:0}
            to {top:0; opacity:1}
        }
        .alert-container {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }
        .alert-success {
            background-color: rgba(0, 255, 100, 0.2);
            color: var(--highlight-green);
            border: 1px solid var(--highlight-green);
        }
        .alert-error {
            background-color: rgba(255, 77, 77, 0.2);
            color: var(--highlight-red);
            border: 1px solid var(--highlight-red);
        }
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            body {
                display: block;
            }
            .sidebar {
                transform: translateX(-100%);
                z-index: 1001;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            .main-content.overlay::before {
                content: '';
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.6);
                z-index: 1000;
                pointer-events: auto;
            }
            .header-left .menu-toggle {
                display: block;
            }
            .header-right .user-profile .user-name {
                display: none;
            }
            .user-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .user-controls .search-bar,
            .user-controls .filter-select,
            .user-controls .add-user-btn {
                width: 100%;
            }
            .user-summary-cards {
                grid-template-columns: 1fr;
            }
            .user-list-grid {
                grid-template-columns: 1fr;
            }
            .user-details {
                grid-template-columns: 1fr;
                gap: 5px;
            }
            .user-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .modal-content {
                width: 95%;
                padding: 20px;
            }
        }
        @media (min-width: 769px) {
            .sidebar {
                position: fixed;
                left: 0;
                transform: translateX(0);
            }
            .header-left .menu-toggle {
                display: none;
            }
            .main-content {
                margin-left: 290px;
            }
            .user-list-grid {
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-chart-line logo-icon"></i>
            <span class="app-name">Sales Navigator</span>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="customer.php"><i class="fas fa-users"></i> Customer Management</a></li>
                <li><a href="order.php"><i class="fas fa-shopping-cart"></i> Order Management</a></li>
                <li><a href="product.php"><i class="fas fa-box"></i> Product Management</a></li>
                <li><a href="target.php"><i class="fas fa-bullseye"></i> Target Management</a></li>
                <li><a href="sales.php"><i class="fas fa-chart-bar"></i> Sales Analytics</a></li>
                <li><a href="leaderboard.php"><i class="fas fa-trophy"></i> Leaderboard</a></li>
                <li><a href="#" class="active"><i class="fas fa-user-cog"></i> User Management</a></li>
                <li><a href="#"><i class="fas fa-map-marker-alt"></i> Location Tracking</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>
    <div class="main-content" id="mainContent">
        <header class="header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                <span class="page-title">User Management</span>
                <span style="font-size: 0.9rem; color: var(--subtle-text); margin-left: 10px;">Manage all system users and their access</span>
            </div>
            <div class="header-right">
                <select class="time-filter">
                    <option>All Roles</option>
                    <option>HOM</option>
                    <option>NSM</option>
                    <option>DSM</option>
                    <option>ASM</option>
                    <option>TSM</option>
                    <option>SR</option>
                </select>
                <a href="#" class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                    <span class="user-role"><?php echo htmlspecialchars($user_role); ?></span>
                </a>
            </div>
        </header>
        <div class="user-controls">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search users by name, email, or role...">
            </div>
            <select class="filter-select">
                <option>All Status</option>
                <option>Active</option>
                <option>Inactive</option>
            </select>
            <a href="#" class="add-user-btn" id="addUserBtn">
                <i class="fas fa-plus"></i> Add New User
            </a>
        </div>
        <?php 
        // Display session messages from the Add User operation
        if (isset($_SESSION['user_message'])): ?>
            <div class="alert-container <?php echo $_SESSION['user_is_error'] ? 'alert-error' : 'alert-success'; ?>">
                <?php echo htmlspecialchars($_SESSION['user_message']); ?>
            </div>
            <?php 
            unset($_SESSION['user_message']); 
            unset($_SESSION['user_is_error']);
            ?>
        <?php endif; ?>
        <div class="user-summary-cards">
            <div class="summary-card green">
                <i class="fas fa-users icon"></i>
                <div class="value"><?php echo htmlspecialchars($total_users); ?></div>
                <div class="label">Total Users</div>
            </div>
            <div class="summary-card blue">
                <i class="fas fa-user-check icon"></i>
                <div class="value"><?php echo htmlspecialchars($active_users); ?></div>
                <div class="label">Active Users</div>
            </div>
            <div class="summary-card yellow">
                <i class="fas fa-user-slash icon"></i>
                <div class="value"><?php echo htmlspecialchars($inactive_users); ?></div>
                <div class="label">Inactive Users</div>
            </div>
            <div class="summary-card red">
                <i class="fas fa-user-plus icon"></i>
                <div class="value"><?php echo htmlspecialchars($new_registrations); ?></div>
                <div class="label">New Registrations</div>
            </div>
        </div>
        <div class="user-list-grid">
            <?php if (empty($user_list)): ?>
                <p>No users found.</p>
            <?php else: ?>
                <?php foreach ($user_list as $user): 
                    $user_initials = strtoupper(substr($user['full_name'], 0, 1) . (strpos($user['full_name'], ' ') !== false ? substr(strstr($user['full_name'], ' '), 1, 1) : ''));
                    $user_status_class = strtolower($user['status']);
                    $user_location = '';
                    if (!empty($user['division']) && !empty($user['territory'])) {
                        $user_location = $user['division'] . ', ' . $user['territory'];
                    } elseif (!empty($user['division'])) {
                        $user_location = $user['division'];
                    }
                ?>
                    <div class="user-card" data-full-name="<?php echo htmlspecialchars($user['full_name']); ?>" data-role="<?php echo htmlspecialchars($user['role']); ?>" data-status="<?php echo htmlspecialchars($user['status']); ?>">
                        <div class="user-card-header">
                            <div class="user-avatar-lg" style="background-color: var(--highlight-green);">
                                <?php echo htmlspecialchars($user_initials); ?>
                            </div>
                            <div class="user-info-main">
                                <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                <div class="user-contact-info">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                                    <br>
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone_number']); ?>
                                    <br>
                                    <i class="fas fa-user-tag"></i> Role: <?php echo htmlspecialchars($user['role']); ?>
                                    <br>
                                    <?php if (!empty($user_location)): ?>
                                        <i class="fas fa-map-marker-alt"></i> Location: <?php echo htmlspecialchars($user_location); ?>
                                        <br>
                                    <?php endif; ?>
                                    <?php if (!empty($user['reports_to_name'])): ?>
                                        <i class="fas fa-sitemap"></i> Reports to: <?php echo htmlspecialchars($user['reports_to_name']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="user-status-badge <?php echo $user_status_class; ?>">
                                <?php echo htmlspecialchars($user['status']); ?>
                            </span>
                        </div>
                        <div class="user-details">
                            <div class="detail-row">
                                <span>Last Login:</span> <span><?php echo htmlspecialchars($user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span>Total Sales:</span> <span>৳<?php echo htmlspecialchars(number_format($user['total_sales'], 0)); ?></span>
                            </div>
                            <div class="detail-row">
                                <span>Orders Created:</span> <span><?php echo htmlspecialchars($user['orders_created']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span>Customers Managed:</span> <span><?php echo htmlspecialchars($user['customers_managed']); ?></span>
                            </div>
                        </div>
                        <div class="user-actions">
                            <a href="#" class="btn view-profile"><i class="fas fa-eye"></i> View Profile</a>
                            <a href="#" class="btn edit" onclick="openEditUserModal(<?php echo htmlspecialchars($user['user_id']); ?>)"><i class="fas fa-edit"></i> Edit</a>
                            <a href="user_management.php?action=delete&user_id=<?php echo htmlspecialchars($user['user_id']); ?>" class="btn delete" onclick="return confirm('Are you sure you want to delete this user? This action is irreversible.');"><i class="fas fa-trash-alt"></i> Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeAddUserModal()">&times;</span>
            <h2>Add New User</h2>
            <form id="addUserForm" method="POST" action="user_management.php">
                <input type="hidden" name="add_user" value="1">
                
                <div class="form-group">
                    <label for="full_name">Full Name:</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone_number">Phone Number:</label>
                    <input type="text" id="phone_number" name="phone_number" required>
                </div>
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="HOM">Head of Marketing (HOM)</option>
                        <option value="NSM">National Sales Manager (NSM)</option>
                        <option value="DSM">Divisional Sales Manager (DSM)</option>
                        <option value="ASM">Area Sales Manager (ASM)</option>
                        <option value="TSM">Territory Sales Manager (TSM)</option>
                        <option value="SR">Sales Representative (SR)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="New">New</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="division">Division (Optional):</label>
                    <input type="text" id="division" name="division">
                </div>
                <div class="form-group">
                    <label for="district">District (Optional):</label>
                    <input type="text" id="district" name="district">
                </div>
                <div class="form-group">
                    <label for="upazila">Upazila (Optional):</label>
                    <input type="text" id="upazila" name="upazila">
                </div>
                <div class="form-group">
                    <label for="territory">Territory (Optional):</label>
                    <input type="text" id="territory" name="territory">
                </div>
                <div class="form-group">
                    <label for="reports_to">Reports To (Full Name, Optional):</label>
                    <input type="text" id="reports_to" name="reports_to" placeholder="e.g., Karim Uddin">
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeAddUserModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Add User</button>
                </div>
            </form>
        </div>
    </div>
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeEditUserModal()">&times;</span>
            <h2>Edit User</h2>
            <form id="editUserForm" method="POST" action="user_management.php">
                <input type="hidden" name="update_user" value="1">
                <input type="hidden" id="edit_user_id" name="user_id">
                
                <div class="form-group">
                    <label for="edit_full_name">Full Name:</label>
                    <input type="text" id="edit_full_name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_email">Email:</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_phone_number">Phone Number:</label>
                    <input type="text" id="edit_phone_number" name="phone_number" required>
                </div>
                <div class="form-group">
                    <label for="edit_role">Role:</label>
                    <select id="edit_role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="HOM">Head of Marketing (HOM)</option>
                        <option value="NSM">National Sales Manager (NSM)</option>
                        <option value="DSM">Divisional Sales Manager (DSM)</option>
                        <option value="ASM">Area Sales Manager (ASM)</option>
                        <option value="TSM">Territory Sales Manager (TSM)</option>
                        <option value="SR">Sales Representative (SR)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_status">Status:</label>
                    <select id="edit_status" name="status" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="New">New</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_division">Division (Optional):</label>
                    <input type="text" id="edit_division" name="division">
                </div>
                <div class="form-group">
                    <label for="edit_district">District (Optional):</label>
                    <input type="text" id="edit_district" name="district">
                </div>
                <div class="form-group">
                    <label for="edit_upazila">Upazila (Optional):</label>
                    <input type="text" id="edit_upazila" name="upazila">
                </div>
                <div class="form-group">
                    <label for="edit_territory">Territory (Optional):</label>
                    <input type="text" id="edit_territory" name="territory">
                </div>
                <div class="form-group">
                    <label for="edit_reports_to">Reports To (Full Name, Optional):</label>
                    <input type="text" id="edit_reports_to" name="reports_to" placeholder="e.g., Karim Uddin">
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeEditUserModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Update User</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            const mainContent = document.getElementById('mainContent');
            const addUserBtn = document.getElementById('addUserBtn');
            const addUserModal = document.getElementById('addUserModal');
            const editUserModal = document.getElementById('editUserModal');
            const userList = <?php echo json_encode($user_list); ?>; // Pass PHP user list to JS
            const searchInput = document.querySelector('.user-controls .search-bar input');
            const statusFilter = document.querySelector('.user-controls .filter-select');
            
            // Sidebar toggle logic
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                if (window.innerWidth <= 768) {
                    mainContent.classList.toggle('overlay');
                }
            });
            // Handle clicking outside the menu to close it on mobile
            mainContent.addEventListener('click', (event) => {
                if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                    if (!sidebar.contains(event.target) && event.target !== menuToggle) {
                        sidebar.classList.remove('active');
                        mainContent.classList.remove('overlay');
                    }
                }
            });
            // Initialize sidebar state based on screen size
            function handleResize() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    mainContent.classList.remove('overlay');
                } else {
                    sidebar.classList.add('hidden');
                }
            }
            // Set initial state
            handleResize();
            window.addEventListener('resize', handleResize);
            
            // --- Add User Modal Functions ---
            window.openAddUserModal = function() {
                addUserModal.style.display = 'flex';
                document.getElementById('addUserForm').reset();
            }
            window.closeAddUserModal = function() {
                addUserModal.style.display = 'none';
            }
            // Event listener for "Add New User" button
            addUserBtn.addEventListener('click', (e) => {
                e.preventDefault();
                openAddUserModal();
            });

            // --- Edit User Modal Functions ---
            window.openEditUserModal = function(userId) {
                const user = userList.find(u => u.user_id == userId);
                if (user) {
                    document.getElementById('edit_user_id').value = user.user_id;
                    document.getElementById('edit_full_name').value = user.full_name;
                    document.getElementById('edit_email').value = user.email;
                    document.getElementById('edit_phone_number').value = user.phone_number;
                    document.getElementById('edit_role').value = user.role;
                    document.getElementById('edit_status').value = user.status;
                    document.getElementById('edit_division').value = user.division;
                    document.getElementById('edit_district').value = user.district;
                    document.getElementById('edit_upazila').value = user.upazila;
                    document.getElementById('edit_territory').value = user.territory;
                    document.getElementById('edit_reports_to').value = user.reports_to_name;
                    editUserModal.style.display = 'flex';
                }
            }
            window.closeEditUserModal = function() {
                editUserModal.style.display = 'none';
            }

            // --- Search and Filter Logic ---
            const filterUsers = () => {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedStatus = statusFilter.value;
                const userCards = document.querySelectorAll('.user-list-grid .user-card');

                userCards.forEach(card => {
                    const fullName = card.dataset.fullName.toLowerCase();
                    const role = card.dataset.role.toLowerCase();
                    const status = card.dataset.status;

                    const matchesSearch = fullName.includes(searchTerm) || role.includes(searchTerm);
                    const matchesStatus = (selectedStatus === 'All Status' || status === selectedStatus);

                    if (matchesSearch && matchesStatus) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            };

            searchInput.addEventListener('input', filterUsers);
            statusFilter.addEventListener('change', filterUsers);

            // Handle success/error messages from session
            <?php if (isset($_SESSION['user_message'])): ?>
                const message = "<?php echo htmlspecialchars($_SESSION['user_message']); ?>";
                const isError = <?php echo isset($_SESSION['user_is_error']) && $_SESSION['user_is_error'] ? 'true' : 'false'; ?>;
                const alertDiv = document.createElement('div');
                alertDiv.textContent = message;
                alertDiv.classList.add('alert-container');
                if (isError) {
                    alertDiv.classList.add('alert-error');
                } else {
                    alertDiv.classList.add('alert-success');
                }
                const header = document.querySelector('.header');
                header.insertAdjacentElement('afterend', alertDiv);
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
                <?php 
                unset($_SESSION['user_message']); 
                unset($_SESSION['user_is_error']);
                ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>