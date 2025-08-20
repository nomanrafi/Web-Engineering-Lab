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

// --- Add Target Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_target'])) {
    $target_name = trim($_POST['target_name']);
    $target_type = trim($_POST['target_type']);
    $target_value = floatval(trim(str_replace(['৳', ','], '', $_POST['target_value'])));
    $achieved_value = floatval(trim(str_replace(['৳', ','], '', $_POST['achieved_value'])));
    $start_date = trim($_POST['start_date']);
    $end_date = trim($_POST['end_date']);
    $assigned_territory = trim($_POST['assigned_territory']);
    $status = trim($_POST['status']);

    // Basic server-side validation
    if (empty($target_name) || empty($target_type) || !is_numeric($target_value) || $target_value <= 0 || empty($start_date) || empty($end_date)) {
        $_SESSION['target_message'] = "All required fields must be filled and have valid numbers for values.";
        $_SESSION['target_is_error'] = true;
    } elseif ($achieved_value < 0) {
        $_SESSION['target_message'] = "Achieved Value cannot be negative.";
        $_SESSION['target_is_error'] = true;
    } else {
        $stmt_add_target = $conn->prepare("INSERT INTO targets (user_id, target_name, target_type, target_value, achieved_value, start_date, end_date, assigned_territory, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt_add_target === false) {
            $_SESSION['target_message'] = "Database error: Could not prepare statement.";
            $_SESSION['target_is_error'] = true;
        } else {
            $stmt_add_target->bind_param("isddsssss", $user_id, $target_name, $target_type, $target_value, $achieved_value, $start_date, $end_date, $assigned_territory, $status);
            
            if ($stmt_add_target->execute()) {
                $_SESSION['target_message'] = "Target added successfully!";
                $_SESSION['target_is_error'] = false;
            } else {
                $_SESSION['target_message'] = "Error adding target: " . $stmt_add_target->error;
                $_SESSION['target_is_error'] = true;
            }
            $stmt_add_target->close();
        }
    }
    header('Location: target.php');
    exit();
}

// --- Update Target Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_target'])) {
    $target_id = trim($_POST['target_id']);
    $target_name = trim($_POST['target_name']);
    $target_type = trim($_POST['target_type']);
    $target_value = floatval(trim(str_replace(['৳', ','], '', $_POST['target_value'])));
    $achieved_value = floatval(trim(str_replace(['৳', ','], '', $_POST['achieved_value'])));
    $start_date = trim($_POST['start_date']);
    $end_date = trim($_POST['end_date']);
    $assigned_territory = trim($_POST['assigned_territory']);
    $status = trim($_POST['status']);
    
    // Basic server-side validation
    if (empty($target_id) || empty($target_name) || empty($target_type) || !is_numeric($target_value) || $target_value <= 0 || empty($start_date) || empty($end_date) || empty($status)) {
        $_SESSION['target_message'] = "All required fields must be filled and have valid numbers for values.";
        $_SESSION['target_is_error'] = true;
    } elseif ($achieved_value < 0) {
        $_SESSION['target_message'] = "Achieved Value cannot be negative.";
        $_SESSION['target_is_error'] = true;
    } else {
        $stmt_update_target = $conn->prepare("UPDATE targets SET target_name = ?, target_type = ?, target_value = ?, achieved_value = ?, start_date = ?, end_date = ?, assigned_territory = ?, status = ? WHERE target_id = ? AND user_id = ?");
        
        if ($stmt_update_target === false) {
            $_SESSION['target_message'] = "Database error: Could not prepare update statement.";
            $_SESSION['target_is_error'] = true;
        } else {
            $stmt_update_target->bind_param("ssddssssii", $target_name, $target_type, $target_value, $achieved_value, $start_date, $end_date, $assigned_territory, $status, $target_id, $user_id);
            
            if ($stmt_update_target->execute()) {
                if ($stmt_update_target->affected_rows > 0) {
                    $_SESSION['target_message'] = "Target updated successfully!";
                    $_SESSION['target_is_error'] = false;
                } else {
                    $_SESSION['target_message'] = "No changes were made or target not found.";
                    $_SESSION['target_is_error'] = false;
                }
            } else {
                $_SESSION['target_message'] = "Error updating target: " . $stmt_update_target->error;
                $_SESSION['target_is_error'] = true;
            }
            $stmt_update_target->close();
        }
    }
    header('Location: target.php');
    exit();
}

// --- Delete Target Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['target_id'])) {
    $target_id_to_delete = filter_var($_GET['target_id'], FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];
    
    if ($target_id_to_delete) {
        $stmt_delete_target = $conn->prepare("DELETE FROM targets WHERE target_id = ? AND user_id = ?");
        
        if ($stmt_delete_target === false) {
            $_SESSION['target_message'] = "Database error: Could not prepare delete statement.";
            $_SESSION['target_is_error'] = true;
        } else {
            $stmt_delete_target->bind_param("ii", $target_id_to_delete, $user_id);
            
            if ($stmt_delete_target->execute()) {
                if ($stmt_delete_target->affected_rows > 0) {
                    $_SESSION['target_message'] = "Target deleted successfully!";
                    $_SESSION['target_is_error'] = false;
                } else {
                    $_SESSION['target_message'] = "Error: Target not found or you don't have permission to delete it.";
                    $_SESSION['target_is_error'] = true;
                }
            } else {
                $_SESSION['target_message'] = "Error deleting target: " . $stmt_delete_target->error;
                $_SESSION['target_is_error'] = true;
            }
            $stmt_delete_target->close();
        }
    } else {
        $_SESSION['target_message'] = "Invalid target ID.";
        $_SESSION['target_is_error'] = true;
    }
    header('Location: target.php');
    exit();
}


// --- Fetch Dynamic Data for Target Metrics ---
$total_targets = 0;
$on_track_targets = 0;
$at_risk_targets = 0;
$critical_targets = 0;
$overall_achievement_percentage = 0;

// Total Targets
$query_total_targets = "SELECT COUNT(*) as total FROM targets WHERE user_id = ?";
$stmt_total_targets = $conn->prepare($query_total_targets);
$stmt_total_targets->bind_param("i", $user_id);
$stmt_total_targets->execute();
$result_total_targets = $stmt_total_targets->get_result();
if ($result_total_targets && $result_total_targets->num_rows > 0) {
    $row_total_targets = $result_total_targets->fetch_assoc();
    $total_targets = $row_total_targets['total'] ?? 0;
}
$stmt_total_targets->close();

// On Track Targets
$query_on_track = "SELECT COUNT(*) as total FROM targets WHERE user_id = ? AND status = 'On Track'";
$stmt_on_track = $conn->prepare($query_on_track);
$stmt_on_track->bind_param("i", $user_id);
$stmt_on_track->execute();
$result_on_track = $stmt_on_track->get_result();
if ($result_on_track && $result_on_track->num_rows > 0) {
    $row_on_track = $result_on_track->fetch_assoc();
    $on_track_targets = $row_on_track['total'] ?? 0;
}
$stmt_on_track->close();

// At Risk Targets
$query_at_risk = "SELECT COUNT(*) as total FROM targets WHERE user_id = ? AND status = 'At Risk'";
$stmt_at_risk = $conn->prepare($query_at_risk);
$stmt_at_risk->bind_param("i", $user_id);
$stmt_at_risk->execute();
$result_at_risk = $stmt_at_risk->get_result();
if ($result_at_risk && $result_at_risk->num_rows > 0) {
    $row_at_risk = $result_at_risk->fetch_assoc();
    $at_risk_targets = $row_at_risk['total'] ?? 0;
}
$stmt_at_risk->close();

// Critical Targets
$query_critical = "SELECT COUNT(*) as total FROM targets WHERE user_id = ? AND status = 'Critical'";
$stmt_critical = $conn->prepare($query_critical);
$stmt_critical->bind_param("i", $user_id);
$stmt_critical->execute();
$result_critical = $stmt_critical->get_result();
if ($result_critical && $result_critical->num_rows > 0) {
    $row_critical = $result_critical->fetch_assoc();
    $critical_targets = $row_critical['total'] ?? 0;
}
$stmt_critical->close();

// Overall Achievement Percentage
$query_overall_achievement = "SELECT SUM(target_value) as total_target_value, SUM(achieved_value) as total_achieved_value FROM targets WHERE user_id = ?";
$stmt_overall_achievement = $conn->prepare($query_overall_achievement);
$stmt_overall_achievement->bind_param("i", $user_id);
$stmt_overall_achievement->execute();
$result_overall_achievement = $stmt_overall_achievement->get_result();
if ($result_overall_achievement && $result_overall_achievement->num_rows > 0) {
    $row_overall_achievement = $result_overall_achievement->fetch_assoc();
    $total_target_value = $row_overall_achievement['total_target_value'] ?? 0;
    $total_achieved_value = $row_overall_achievement['total_achieved_value'] ?? 0;
    if ($total_target_value > 0) {
        $overall_achievement_percentage = ($total_achieved_value / $total_target_value) * 100;
    }
}
$stmt_overall_achievement->close();

// --- Fetch Target List Data ---
$target_list = [];
$query_target_list = "SELECT target_id, target_name, target_type, target_value, achieved_value, start_date, end_date, assigned_territory, status FROM targets WHERE user_id = ? ORDER BY end_date DESC";
$stmt_list = $conn->prepare($query_target_list);
$stmt_list->bind_param("i", $user_id);
$stmt_list->execute();
$result_list = $stmt_list->get_result();
while ($row = $result_list->fetch_assoc()) {
    $target_list[] = $row;
}
$stmt_list->close();

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Navigator - Target Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" xintegrity="sha512-Fo3rlalHpgO702sN1l8/p5pT+c+W5U/6a7F6S3a5b0S3e5w1/b0R7+v5F5t5C5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
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

        /* Target Management Specific Styles */
        .target-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 20px;
            flex-wrap: nowrap;
        }

        .target-controls .search-bar {
            flex: 1;
            position: relative;
            min-width: 200px;
        }

        .target-controls .search-bar input {
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

        .target-controls .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--subtle-text);
            pointer-events: none;
        }

        .target-controls .status-filter {
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
            background-position: right 10px center;
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 18px;
            cursor: pointer;
        }

        .target-controls .add-target-btn {
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

        .target-controls .add-target-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 255, 100, 0.4);
        }

        .target-summary-cards {
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

        .target-list-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .target-card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
        }

        .target-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .target-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .target-type-badge {
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            background-color: rgba(0, 191, 255, 0.2);
            color: var(--highlight-blue);
        }

        .target-status-badge {
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: auto;
        }
        .target-status-badge.on-track { background-color: rgba(0, 255, 100, 0.2); color: var(--highlight-green); }
        .target-status-badge.at-risk { background-color: rgba(255, 215, 0, 0.2); color: var(--highlight-yellow); }
        .target-status-badge.critical { background-color: rgba(255, 77, 77, 0.2); color: var(--highlight-red); }
        .target-status-badge.complete { background-color: rgba(0, 191, 255, 0.2); color: var(--highlight-blue); }

        .target-details-info {
            font-size: 0.9rem;
            color: var(--text-color);
            margin-bottom: 20px;
        }

        .target-details-info .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .target-details-info .detail-row span:first-child {
            color: var(--subtle-text);
            font-size: 0.85rem;
        }

        .target-progress-bar-container {
            background-color: #333;
            border-radius: 5px;
            height: 10px;
            overflow: hidden;
            margin-top: 10px;
        }
        .target-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            border-radius: 5px;
            width: 0%;
            transition: width 0.5s ease-out;
        }
        .target-progress-bar.red { background: var(--highlight-red); }
        .target-progress-bar.yellow { background: var(--highlight-yellow); }
        .target-progress-bar.green { background: var(--highlight-green); }


        .target-actions {
            display: flex;
            justify-content: space-around;
            gap: 10px;
            margin-top: auto;
        }

        .target-actions .btn {
            flex-grow: 1;
            padding: 10px 15px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .target-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 255, 100, 0.4);
        }

        .target-actions .btn.view {
            background-color: rgba(0, 191, 255, 0.2);
            color: var(--highlight-blue);
        }
        .target-actions .btn.view:hover {
            box-shadow: 0 4px 10px rgba(0, 191, 255, 0.4);
        }

        .target-actions .btn.edit {
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            color: #1a1a1a;
        }
        .target-actions .btn.edit:hover {
            box-shadow: 0 4px 10px rgba(0, 255, 100, 0.4);
        }
        
        .target-actions .btn.delete {
            background-color: rgba(255, 77, 77, 0.2);
            color: var(--highlight-red);
        }
        .target-actions .btn.delete:hover {
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

        /* Add target form specific styles */
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
        .modal-content input[type="number"],
        .modal-content input[type="date"],
        .modal-content select,
        .modal-content textarea {
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
        .modal-content input[type="number"]:focus,
        .modal-content input[type="date"]:focus,
        .modal-content select:focus,
        .modal-content textarea:focus {
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
            .target-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .target-controls .filter-select,
            .target-controls .add-target-btn {
                width: 100%;
            }
            .target-summary-cards {
                grid-template-columns: 1fr;
            }
            .target-list-grid {
                grid-template-columns: 1fr;
            }
            .target-details-info {
                grid-template-columns: 1fr;
                gap: 5px;
            }
            .target-actions {
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
            .target-list-grid {
                grid-template-columns: 1fr 1fr;
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
                <li><a href="#" class="active"><i class="fas fa-bullseye"></i> Target Management</a></li>
                <li><a href="sales.php"><i class="fas fa-chart-bar"></i> Sales Analytics</a></li>
                <li><a href="leaderboard.php"><i class="fas fa-trophy"></i> Leaderboard</a></li>
                <li><a href="user_management.php"><i class="fas fa-user-cog"></i> User Management</a></li>
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
                <span class="page-title">Target Management</span>
                <span style="font-size: 0.9rem; color: var(--subtle-text); margin-left: 10px;">Manage your sales targets and track progress</span>
            </div>
            <div class="header-right">
                <a href="#" class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                    <span class="user-role"><?php echo htmlspecialchars($user_role); ?></span>
                </a>
            </div>
        </header>

        <div class="target-controls">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search targets by name or type...">
            </div>
            <select class="status-filter">
                <option value="All">All Status</option>
                <option value="On Track">On Track</option>
                <option value="At Risk">At Risk</option>
                <option value="Critical">Critical</option>
                <option value="Complete">Complete</option>
            </select>
            <a href="#" class="add-target-btn" id="addTargetBtn">
                <i class="fas fa-plus"></i> Set New Target
            </a>
        </div>

        <?php 
        // Display session messages from the Add Target operation
        if (isset($_SESSION['target_message'])): ?>
            <div class="alert-container <?php echo $_SESSION['target_is_error'] ? 'alert-error' : 'alert-success'; ?>">
                <?php echo htmlspecialchars($_SESSION['target_message']); ?>
            </div>
            <?php 
            unset($_SESSION['target_message']); 
            unset($_SESSION['target_is_error']);
            ?>
        <?php endif; ?>

        <div class="target-summary-cards">
            <div class="summary-card blue">
                <i class="fas fa-bullseye icon"></i>
                <div class="value"><?php echo htmlspecialchars($total_targets); ?></div>
                <div class="label">Total Targets</div>
            </div>
            <div class="summary-card green">
                <i class="fas fa-check-circle icon"></i>
                <div class="value"><?php echo htmlspecialchars($on_track_targets); ?></div>
                <div class="label">On Track</div>
            </div>
            <div class="summary-card yellow">
                <i class="fas fa-exclamation-triangle icon"></i>
                <div class="value"><?php echo htmlspecialchars($at_risk_targets); ?></div>
                <div class="label">At Risk</div>
            </div>
            <div class="summary-card red">
                <i class="fas fa-times-circle icon"></i>
                <div class="value"><?php echo htmlspecialchars($critical_targets); ?></div>
                <div class="label">Critical</div>
            </div>
        </div>

        <div class="target-list-grid">
            <?php if (empty($target_list)): ?>
                <p>No targets found.</p>
            <?php else: ?>
                <?php foreach ($target_list as $target): 
                    $achievement_percentage = ($target['target_value'] > 0) ? ($target['achieved_value'] / $target['target_value']) * 100 : 0;
                    $progress_bar_class = '';
                    if ($achievement_percentage >= 100) {
                        $progress_bar_class = 'green';
                    } elseif ($achievement_percentage >= 75) {
                        $progress_bar_class = 'blue';
                    } elseif ($achievement_percentage >= 50) {
                        $progress_bar_class = 'yellow';
                    } else {
                        $progress_bar_class = 'red';
                    }
                ?>
                    <div class="target-card">
                        <div class="target-card-header">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="target-title"><?php echo htmlspecialchars($target['target_name']); ?></span>
                                <span class="target-type-badge"><?php echo htmlspecialchars($target['target_type']); ?></span>
                            </div>
                            <span class="target-status-badge <?php echo strtolower(str_replace(' ', '-', $target['status'])); ?>">
                                <?php echo htmlspecialchars($target['status']); ?>
                            </span>
                        </div>
                        <div class="target-details-info">
                            <div class="detail-row">
                                <span>Target Value:</span> <span>৳<?php echo htmlspecialchars(number_format($target['target_value'], 2)); ?></span>
                            </div>
                            <div class="detail-row">
                                <span>Achieved:</span> <span>৳<?php echo htmlspecialchars(number_format($target['achieved_value'], 2)); ?></span>
                            </div>
                            <div class="detail-row">
                                <span>Achievement:</span> <span><?php echo round($achievement_percentage, 1); ?>%</span>
                            </div>
                            <div class="detail-row">
                                <span>Duration:</span> <span><?php echo htmlspecialchars($target['start_date']); ?> to <?php echo htmlspecialchars($target['end_date']); ?></span>
                            </div>
                            <div class="detail-row">
                                <?php
                                    $today = new DateTime();
                                    $end_date_obj = new DateTime($target['end_date']);
                                    $interval = $today->diff($end_date_obj);
                                    $remaining_text = '';
                                    if ($today > $end_date_obj) {
                                        $remaining_text = 'Expired';
                                    } else {
                                        $remaining_days = $interval->days;
                                        $remaining_text = $remaining_days . ' days remaining';
                                    }
                                ?>
                                <span>Time Remaining:</span> <span><?php echo htmlspecialchars($remaining_text); ?></span>
                            </div>
                            <?php if (!empty($target['assigned_territory'])): ?>
                            <div class="detail-row">
                                <span>Territory:</span> <span><?php echo htmlspecialchars($target['assigned_territory']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="target-progress-bar-container">
                                <div class="target-progress-bar <?php echo $progress_bar_class; ?>" style="width: <?php echo min(100, round($achievement_percentage)); ?>%;"></div>
                            </div>
                        </div>
                        <div class="target-actions">
                            <a href="#" class="btn view"><i class="fas fa-eye"></i> View</a>
                            <a href="#" class="btn edit" onclick="openEditTargetModal(<?php echo htmlspecialchars($target['target_id']); ?>)"><i class="fas fa-edit"></i> Edit</a>
                            <a href="target.php?action=delete&target_id=<?php echo htmlspecialchars($target['target_id']); ?>" class="btn delete" onclick="return confirm('Are you sure you want to delete this target?');">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="addTargetModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeAddTargetModal()">&times;</span>
            <h2>Set New Target</h2>
            <form id="addTargetForm" method="POST" action="target.php">
                <input type="hidden" name="add_target" value="1">
                
                <div class="form-group">
                    <label for="add_target_name">Target Name:</label>
                    <input type="text" id="add_target_name" name="target_name" required>
                </div>
                
                <div class="form-group">
                    <label for="add_target_type">Target Type:</label>
                    <select id="add_target_type" name="target_type" required>
                        <option value="">Select Type</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Quarterly">Quarterly</option>
                        <option value="Yearly">Yearly</option>
                        <option value="Product Launch">Product Launch</option>
                        <option value="Team Performance">Team Performance</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="add_target_value">Target Value (৳):</label>
                    <input type="number" id="add_target_value" name="target_value" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="add_achieved_value">Achieved Value (৳):</label>
                    <input type="number" id="add_achieved_value" name="achieved_value" step="0.01" min="0" value="0" required>
                </div>

                <div class="form-group">
                    <label for="add_start_date">Start Date:</label>
                    <input type="date" id="add_start_date" name="start_date" required>
                </div>

                <div class="form-group">
                    <label for="add_end_date">End Date:</label>
                    <input type="date" id="add_end_date" name="end_date" required>
                </div>

                <div class="form-group">
                    <label for="add_assigned_territory">Assigned Territory (Optional):</label>
                    <input type="text" id="add_assigned_territory" name="assigned_territory" value="<?php echo htmlspecialchars($user_territory); ?>">
                </div>

                <div class="form-group">
                    <label for="add_status">Status:</label>
                    <select id="add_status" name="status" required>
                        <option value="On Track">On Track</option>
                        <option value="At Risk">At Risk</option>
                        <option value="Critical">Critical</option>
                        <option value="Complete">Complete</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeAddTargetModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Set Target</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editTargetModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeEditTargetModal()">&times;</span>
            <h2 id="editModalTitle">Edit Target</h2>
            <form id="editTargetForm" method="POST" action="target.php">
                <input type="hidden" name="update_target" value="1">
                <input type="hidden" id="edit_target_id" name="target_id">

                <div class="form-group">
                    <label for="edit_target_name">Target Name:</label>
                    <input type="text" id="edit_target_name" name="target_name" required>
                </div>

                <div class="form-group">
                    <label for="edit_target_type">Target Type:</label>
                    <select id="edit_target_type" name="target_type" required>
                        <option value="Monthly">Monthly</option>
                        <option value="Quarterly">Quarterly</option>
                        <option value="Yearly">Yearly</option>
                        <option value="Product Launch">Product Launch</option>
                        <option value="Team Performance">Team Performance</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_target_value">Target Value (৳):</label>
                    <input type="number" id="edit_target_value" name="target_value" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="edit_achieved_value">Achieved Value (৳):</label>
                    <input type="number" id="edit_achieved_value" name="achieved_value" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="edit_start_date">Start Date:</label>
                    <input type="date" id="edit_start_date" name="start_date" required>
                </div>

                <div class="form-group">
                    <label for="edit_end_date">End Date:</label>
                    <input type="date" id="edit_end_date" name="end_date" required>
                </div>

                <div class="form-group">
                    <label for="edit_assigned_territory">Assigned Territory (Optional):</label>
                    <input type="text" id="edit_assigned_territory" name="assigned_territory">
                </div>
                
                <div class="form-group">
                    <label for="edit_status">Status:</label>
                    <select id="edit_status" name="status" required>
                        <option value="On Track">On Track</option>
                        <option value="At Risk">At Risk</option>
                        <option value="Critical">Critical</option>
                        <option value="Complete">Complete</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeEditTargetModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Update Target</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            const mainContent = document.getElementById('mainContent');
            const addTargetBtn = document.getElementById('addTargetBtn');
            const addTargetModal = document.getElementById('addTargetModal');
            const editTargetModal = document.getElementById('editTargetModal');
            const targetList = <?php echo json_encode($target_list); ?>;
            const filterSelect = document.querySelector('.status-filter');
            const searchInput = document.querySelector('.search-bar input');

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
            
            // --- Add Target Modal Functions ---
            window.openAddTargetModal = function() {
                addTargetModal.style.display = 'flex';
                // Optional: reset form fields when opening
                document.getElementById('addTargetForm').reset();
            }

            window.closeAddTargetModal = function() {
                addTargetModal.style.display = 'none';
            }

            // Event listener for "Set New Target" button
            addTargetBtn.addEventListener('click', (e) => {
                e.preventDefault();
                openAddTargetModal();
            });

            // --- Edit Target Modal Functions ---
            window.openEditTargetModal = function(targetId) {
                const target = targetList.find(t => t.target_id == targetId);

                if (target) {
                    document.getElementById('editModalTitle').textContent = 'Edit Target: ' + target.target_name;
                    document.getElementById('edit_target_id').value = target.target_id;
                    document.getElementById('edit_target_name').value = target.target_name;
                    document.getElementById('edit_target_type').value = target.target_type;
                    document.getElementById('edit_target_value').value = target.target_value;
                    document.getElementById('edit_achieved_value').value = target.achieved_value;
                    document.getElementById('edit_start_date').value = target.start_date;
                    document.getElementById('edit_end_date').value = target.end_date;
                    document.getElementById('edit_assigned_territory').value = target.assigned_territory;
                    document.getElementById('edit_status').value = target.status;

                    editTargetModal.style.display = 'flex';
                }
            }
            
            window.closeEditTargetModal = function() {
                editTargetModal.style.display = 'none';
            }

            // --- Search and Filter Logic ---
            const filterTargets = () => {
                const selectedStatus = filterSelect.value;
                const searchTerm = searchInput.value.toLowerCase();
                const targetCards = document.querySelectorAll('.target-card');

                targetCards.forEach(card => {
                    const statusBadge = card.querySelector('.target-status-badge');
                    const status = statusBadge ? statusBadge.textContent.trim() : '';
                    const targetTitle = card.querySelector('.target-title').textContent.toLowerCase();
                    
                    const matchesStatus = (selectedStatus === 'All Status' || status.toLowerCase() === selectedStatus.toLowerCase());
                    const matchesSearch = (targetTitle.includes(searchTerm) || card.textContent.toLowerCase().includes(searchTerm));

                    if (matchesStatus && matchesSearch) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            };

            filterSelect.addEventListener('change', filterTargets);
            searchInput.addEventListener('input', filterTargets);

            // Handle success/error messages from session
            <?php if (isset($_SESSION['target_message'])): ?>
                const message = "<?php echo htmlspecialchars($_SESSION['target_message']); ?>";
                const isError = <?php echo isset($_SESSION['target_is_error']) && $_SESSION['target_is_error'] ? 'true' : 'false'; ?>;

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
                unset($_SESSION['target_message']); 
                unset($_SESSION['target_is_error']);
                ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>