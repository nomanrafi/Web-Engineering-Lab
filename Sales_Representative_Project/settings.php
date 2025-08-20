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
header('Content-Type: text/html; charset=utf-8');

// Include the database connection file
require_once 'db_connect.php';

// Fetch User-specific data for the header
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

$user_info_query = "SELECT full_name, email, phone_number, role, division, territory, password FROM users WHERE user_id = ?";
$stmt_user_info = $conn->prepare($user_info_query);
$stmt_user_info->bind_param("i", $user_id);
$stmt_user_info->execute();
$user_result = $stmt_user_info->get_result();
$user_data = $user_result->fetch_assoc();
$stmt_user_info->close();

$user_full_name = $user_data['full_name'] ?? 'N/A';
$user_email = $user_data['email'] ?? 'N/A';
$user_phone_number = $user_data['phone_number'] ?? 'N/A';
$user_role = $user_data['role'] ?? 'Unknown Role';
$user_division = $user_data['division'] ?? 'Headquarters';
$user_territory = $user_data['territory'] ?? 'Global';

$initials = strtoupper(substr($user_name, 0, 1) . substr(strstr($user_name, ' '), 1, 1));
$header_location = $user_division . ' • ' . $user_territory;

// --- Fetch User Settings from user_settings table ---
$settings = [];
$settings_query = "SELECT * FROM user_settings WHERE user_id = ?";
$stmt_settings = $conn->prepare($settings_query);
$stmt_settings->bind_param("i", $user_id);
$stmt_settings->execute();
$settings_result = $stmt_settings->get_result();

if ($settings_result->num_rows > 0) {
    $settings = $settings_result->fetch_assoc();
} else {
    // If no settings exist, use defaults
    $settings = [
        'email_notifications' => 1,
        'push_notifications' => 0,
        'sms_notifications' => 1,
        'order_alerts' => 1,
        'target_achievement_alerts' => 0,
        'system_maintenance_alerts' => 1,
        'location_tracking_enabled' => 1,
        'share_location_with_team' => 0,
        'tracking_frequency' => 'Normal (Every 2 minutes)',
        'track_only_working_hours' => 1,
        'language' => 'English',
        'currency' => 'BDT',
        'date_format' => 'DD/MM/YYYY',
        'auto_logout_minutes' => 60,
    ];
}
$stmt_settings->close();


// --- Handle POST Requests for Settings Updates ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    // Using a try-catch block for database errors
    try {
        if (isset($_POST['save_profile'])) {
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone_number = trim($_POST['phone_number']);

            if (empty($full_name) || empty($email) || empty($phone_number)) {
                $response['message'] = "Full Name, Email, and Phone Number are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = "Invalid email format.";
            } else {
                if ($email !== $user_data['email']) {
                    $check_email_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                    $check_email_stmt->bind_param("si", $email, $user_id);
                    $check_email_stmt->execute();
                    $check_email_stmt->store_result();
                    if ($check_email_stmt->num_rows > 0) {
                        $response['message'] = "This email is already taken by another user.";
                        echo json_encode($response);
                        exit();
                    }
                    $check_email_stmt->close();
                }

                $stmt_update_profile = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone_number = ? WHERE user_id = ?");
                $stmt_update_profile->bind_param("sssi", $full_name, $email, $phone_number, $user_id);
                if ($stmt_update_profile->execute()) {
                    $_SESSION['full_name'] = $full_name;
                    $response['success'] = true;
                    $response['message'] = "Profile updated successfully!";
                } else {
                    $response['message'] = "Error updating profile: " . $stmt_update_profile->error;
                }
                $stmt_update_profile->close();
            }
        } elseif (isset($_POST['change_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_new_password = $_POST['confirm_new_password'];

            if (!password_verify($current_password, $user_data['password'])) {
                $response['message'] = "Current password is incorrect.";
            } elseif (empty($new_password) || strlen($new_password) < 8 || !preg_match("/[A-Z]/", $new_password) || !preg_match("/[a-z]/", $new_password) || !preg_match("/\d/", $new_password) || !preg_match("/[^a-zA-Z\d]/", $new_password)) {
                 $response['message'] = "New password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
            } elseif ($new_password !== $confirm_new_password) {
                $response['message'] = "New passwords do not match.";
            } else {
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_update_password = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt_update_password->bind_param("si", $hashed_new_password, $user_id);
                if ($stmt_update_password->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Password changed successfully!";
                } else {
                    $response['message'] = "Error changing password: " . $stmt_update_password->error;
                }
                $stmt_update_password->close();
            }
        } elseif (isset($_POST['save_notifications']) || isset($_POST['save_location']) || isset($_POST['save_system'])) {
            $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
            $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;
            $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
            $order_alerts = isset($_POST['order_alerts']) ? 1 : 0;
            $target_achievement_alerts = isset($_POST['target_achievement_alerts']) ? 1 : 0;
            $system_maintenance_alerts = isset($_POST['system_maintenance_alerts']) ? 1 : 0;
            
            $location_tracking_enabled = isset($_POST['location_tracking_enabled']) ? 1 : 0;
            $share_location_with_team = isset($_POST['share_location_with_team']) ? 1 : 0;
            $tracking_frequency = $_POST['tracking_frequency'] ?? 'Normal (Every 2 minutes)';
            $track_only_working_hours = isset($_POST['track_only_working_hours']) ? 1 : 0;

            $language = $_POST['language'] ?? 'English';
            $currency = $_POST['currency'] ?? 'BDT';
            $date_format = $_POST['date_format'] ?? 'DD/MM/YYYY';
            $auto_logout_minutes = intval($_POST['auto_logout_minutes'] ?? 60);

            $check_settings_stmt = $conn->prepare("SELECT setting_id FROM user_settings WHERE user_id = ?");
            $check_settings_stmt->bind_param("i", $user_id);
            $check_settings_stmt->execute();
            $check_settings_stmt->store_result();

            if ($check_settings_stmt->num_rows > 0) {
                $update_stmt = $conn->prepare("UPDATE user_settings SET email_notifications=?, push_notifications=?, sms_notifications=?, order_alerts=?, target_achievement_alerts=?, system_maintenance_alerts=?, location_tracking_enabled=?, share_location_with_team=?, tracking_frequency=?, track_only_working_hours=?, language=?, currency=?, date_format=?, auto_logout_minutes=? WHERE user_id=?");
                $update_stmt->bind_param("iiiiiiiisisiisi", $email_notifications, $push_notifications, $sms_notifications, $order_alerts, $target_achievement_alerts, $system_maintenance_alerts, $location_tracking_enabled, $share_location_with_team, $tracking_frequency, $track_only_working_hours, $language, $currency, $date_format, $auto_logout_minutes, $user_id);
                
                if ($update_stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Settings updated successfully!";
                } else {
                    $response['message'] = "Error updating settings: " . $update_stmt->error;
                }
                $update_stmt->close();
            } else {
                $insert_stmt = $conn->prepare("INSERT INTO user_settings (user_id, email_notifications, push_notifications, sms_notifications, order_alerts, target_achievement_alerts, system_maintenance_alerts, location_tracking_enabled, share_location_with_team, tracking_frequency, track_only_working_hours, language, currency, date_format, auto_logout_minutes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("iiiiiiiisisiisi", $user_id, $email_notifications, $push_notifications, $sms_notifications, $order_alerts, $target_achievement_alerts, $system_maintenance_alerts, $location_tracking_enabled, $share_location_with_team, $tracking_frequency, $track_only_working_hours, $language, $currency, $date_format, $auto_logout_minutes);
                
                if ($insert_stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Settings saved successfully!";
                } else {
                    $response['message'] = "Error saving settings: " . $insert_stmt->error;
                }
                $insert_stmt->close();
            }
            $check_settings_stmt->close();
        }

    } catch (mysqli_sql_exception $e) {
        $response['message'] = "Database error: " . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Navigator - Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" xintegrity="sha512-Fo3rlalHpgO702sN1l8/p5pT+c+W5U/6a7F6S3a5b0S3e5w1/b0R7+v5F5t5C5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            background-color: var(--sidebar-bg);
            padding: 20px;
            box-shadow: 2px 0 10px var(--shadow-color);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
            left: 0;
            top: 0;
            z-index: 1001;
            transition: transform 0.3s ease-in-out;
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

        /* Main Content Styling (Consistent with other pages) */
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

        /* Header Styling (Consistent with other pages) */
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

        /* Settings Specific Styles */
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .settings-section-card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
        }

        .settings-section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .settings-section-header i {
            font-size: 1.5rem;
            color: var(--highlight-green);
            margin-right: 10px;
        }

        .settings-section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .settings-group {
            margin-bottom: 20px;
        }

        .settings-group-title {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
            align-items: center;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        .form-group label {
            font-size: 0.85rem;
            color: var(--subtle-text);
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            background-color: #2a2a2a;
            border: 1px solid #444;
            border-radius: 8px;
            color: var(--text-color);
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--highlight-green);
            box-shadow: 0 0 0 2px rgba(0, 255, 100, 0.3);
        }
        .form-group select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23a0a0a0"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 18px;
        }

        .toggle-switch-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .toggle-switch-container:last-of-type {
            border-bottom: none;
        }

        .toggle-label {
            font-size: 0.95rem;
            color: var(--text-color);
        }

        /* Custom Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 20px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #333;
            transition: .4s;
            border-radius: 20px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 2px;
            bottom: 2px;
            background-color: #aaa;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--highlight-green);
        }

        input:focus + .slider {
            box-shadow: 0 0 1px var(--highlight-green);
        }

        input:checked + .slider:before {
            transform: translateX(20px);
            background-color: #fff;
        }

        .save-btn {
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            color: #1a1a1a;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            cursor: pointer;
            margin-top: 20px;
            width: fit-content;
            align-self: flex-end;
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 255, 100, 0.4);
        }
        
        .alert-container {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            display: none;
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
            .form-row {
                grid-template-columns: 1fr;
            }
            .save-btn {
                width: 100%;
                align-self: center;
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
            .form-row {
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
                <li><a href="target.php"><i class="fas fa-bullseye"></i> Target Management</a></li>
                <li><a href="sales.php"><i class="fas fa-chart-bar"></i> Sales Analytics</a></li>
                <li><a href="leaderboard.php"><i class="fas fa-trophy"></i> Leaderboard</a></li>
                <li><a href="user_management.php"><i class="fas fa-user-cog"></i> User Management</a></li>
                <li><a href="#"><i class="fas fa-map-marker-alt"></i> Location Tracking</a></li>
                <li><a href="#" class="active"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>

    <div class="main-content" id="mainContent">
        <header class="header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                <span class="page-title">Settings</span>
                <span style="font-size: 0.9rem; color: var(--subtle-text); margin-left: 10px;">Manage your account preferences and system settings</span>
            </div>
            <div class="header-right">
                <a href="#" class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                    <span class="user-role"><?php echo htmlspecialchars($user_role); ?></span>
                </a>
            </div>
        </header>

        <div id="alertContainer" class="alert-container" style="display:none;"></div>

        <div class="settings-grid">
            <div class="settings-section-card">
                <div class="settings-section-header">
                    <i class="fas fa-user-circle"></i>
                    <span class="settings-section-title">Profile Settings</span>
                </div>
                <form id="profileSettingsForm">
                    <input type="hidden" name="save_profile" value="1">
                    <div class="settings-group">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fullName">Full Name</label>
                                <input type="text" id="fullName" name="full_name" value="<?php echo htmlspecialchars($user_full_name); ?>">
                            </div>
                            <div class="form-group">
                                <label for="emailAddress">Email Address</label>
                                <input type="email" id="emailAddress" name="email" value="<?php echo htmlspecialchars($user_email); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phoneNumber">Phone Number</label>
                                <input type="tel" id="phoneNumber" name="phone_number" value="<?php echo htmlspecialchars($user_phone_number); ?>">
                            </div>
                            <div class="form-group">
                                <label for="territory">Territory</label>
                                <input type="text" id="territory" name="territory" value="<?php echo htmlspecialchars($user_territory); ?>" readonly>
                            </div>
                        </div>
                        <button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Profile</button>
                    </div>
                </form>
            </div>

            <div class="settings-section-card">
                <div class="settings-section-header">
                    <i class="fas fa-lock"></i>
                    <span class="settings-section-title">Security Settings</span>
                </div>
                <form id="securitySettingsForm">
                    <input type="hidden" name="change_password" value="1">
                    <div class="settings-group">
                        <div class="form-group">
                            <label for="currentPassword">Current Password</label>
                            <input type="password" id="currentPassword" name="current_password" placeholder="Enter current password">
                        </div>
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" name="new_password" placeholder="Enter new password">
                        </div>
                        <div class="form-group">
                            <label for="confirmNewPassword">Confirm New Password</label>
                            <input type="password" id="confirmNewPassword" name="confirm_new_password" placeholder="Confirm new password">
                        </div>
                        <button type="submit" class="save-btn"><i class="fas fa-key"></i> Change Password</button>
                    </div>
                </form>
            </div>

            <div class="settings-section-card">
                <div class="settings-section-header">
                    <i class="fas fa-bell"></i>
                    <span class="settings-section-title">Notification Settings</span>
                </div>
                <form id="notificationSettingsForm">
                    <input type="hidden" name="save_notifications" value="1">
                    <div class="settings-group">
                        <div class="toggle-switch-container">
                            <span class="toggle-label">Email Notifications</span>
                            <label class="switch">
                                <input type="checkbox" name="email_notifications" <?php echo ($settings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="toggle-switch-container">
                            <span class="toggle-label">Push Notifications</span>
                            <label class="switch">
                                <input type="checkbox" name="push_notifications" <?php echo ($settings['push_notifications'] ?? 0) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="toggle-switch-container">
                            <span class="toggle-label">SMS Notifications</span>
                            <label class="switch">
                                <input type="checkbox" name="sms_notifications" <?php echo ($settings['sms_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="toggle-switch-container">
                            <span class="toggle-label">Order Alerts</span>
                            <label class="switch">
                                <input type="checkbox" name="order_alerts" <?php echo ($settings['order_alerts'] ?? 1) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="toggle-switch-container">
                            <span class="toggle-label">Target Achievement Alerts</span>
                            <label class="switch">
                                <input type="checkbox" name="target_achievement_alerts" <?php echo ($settings['target_achievement_alerts'] ?? 0) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="toggle-switch-container">
                            <span class="toggle-label">System Maintenance Alerts</span>
                            <label class="switch">
                                <input type="checkbox" name="system_maintenance_alerts" <?php echo ($settings['system_maintenance_alerts'] ?? 1) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Notifications</button>
                    </div>
                </form>
            </div>

            <div class="settings-section-card">
                <div class="settings-section-header">
                    <i class="fas fa-map-marker-alt"></i>
                    <span class="settings-section-title">Location Settings</span>
                </div>
                <form id="locationSettingsForm">
                    <input type="hidden" name="save_location" value="1">
                    <div class="settings-group">
                        <div class="toggle-switch-container">
                            <span class="toggle-label">Enable Location Tracking</span>
                            <label class="switch">
                                <input type="checkbox" name="location_tracking_enabled" <?php echo ($settings['location_tracking_enabled'] ?? 1) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="toggle-switch-container">
                            <span class="toggle-label">Share Location with Team</span>
                            <label class="switch">
                                <input type="checkbox" name="share_location_with_team" <?php echo ($settings['share_location_with_team'] ?? 0) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="trackingFrequency">Tracking Frequency</label>
                            <select id="trackingFrequency" name="tracking_frequency">
                                <option value="Normal (Every 2 minutes)" <?php echo (($settings['tracking_frequency'] ?? '') == 'Normal (Every 2 minutes)') ? 'selected' : ''; ?>>Normal (Every 2 minutes)</option>
                                <option value="High (Every 30 seconds)" <?php echo (($settings['tracking_frequency'] ?? '') == 'High (Every 30 seconds)') ? 'selected' : ''; ?>>High (Every 30 seconds)</option>
                                <option value="Low (Every 5 minutes)" <?php echo (($settings['tracking_frequency'] ?? '') == 'Low (Every 5 minutes)') ? 'selected' : ''; ?>>Low (Every 5 minutes)</option>
                            </select>
                        </div>
                        <div class="toggle-switch-container">
                            <span class="toggle-label">Track Only During Working Hours</span>
                            <label class="switch">
                                <input type="checkbox" name="track_only_working_hours" <?php echo ($settings['track_only_working_hours'] ?? 1) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Location Settings</button>
                    </div>
                </form>
            </div>

            <div class="settings-section-card">
                <div class="settings-section-header">
                    <i class="fas fa-cogs"></i>
                    <span class="settings-section-title">System Preferences</span>
                </div>
                <form id="systemPreferencesForm">
                    <input type="hidden" name="save_system" value="1">
                    <div class="settings-group">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="language">Language</label>
                                <select id="language" name="language">
                                    <option value="English" <?php echo (($settings['language'] ?? '') == 'English') ? 'selected' : ''; ?>>English</option>
                                    <option value="Bengali" <?php echo (($settings['language'] ?? '') == 'Bengali') ? 'selected' : ''; ?>>Bengali</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="currency">Currency</label>
                                <select id="currency" name="currency">
                                    <option value="BDT" <?php echo (($settings['currency'] ?? '') == 'BDT') ? 'selected' : ''; ?>>Bangladeshi Taka (BDT)</option>
                                    <option value="USD" <?php echo (($settings['currency'] ?? '') == 'USD') ? 'selected' : ''; ?>>USD</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="dateFormat">Date Format</label>
                                <select id="dateFormat" name="date_format">
                                    <option value="DD/MM/YYYY" <?php echo (($settings['date_format'] ?? '') == 'DD/MM/YYYY') ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                    <option value="MM/DD/YYYY" <?php echo (($settings['date_format'] ?? '') == 'MM/DD/YYYY') ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                    <option value="YYYY-MM-DD" <?php echo (($settings['date_format'] ?? '') == 'YYYY-MM-DD') ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="autoLogout">Auto Logout</label>
                                <select id="autoLogout" name="auto_logout_minutes">
                                    <option value="60" <?php echo (($settings['auto_logout_minutes'] ?? 60) == 60) ? 'selected' : ''; ?>>60 minutes</option>
                                    <option value="30" <?php echo (($settings['auto_logout_minutes'] ?? 60) == 30) ? 'selected' : ''; ?>>30 minutes</option>
                                    <option value="0" <?php echo (($settings['auto_logout_minutes'] ?? 60) == 0) ? 'selected' : ''; ?>>Never</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="save-btn"><i class="fas fa-save"></i> Save System Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            const mainContent = document.getElementById('mainContent');
            const alertContainer = document.getElementById('alertContainer');

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

            // --- Handle Form Submissions with Fetch API (AJAX-like) ---
            const profileSettingsForm = document.getElementById('profileSettingsForm');
            const securitySettingsForm = document.getElementById('securitySettingsForm');
            const notificationSettingsForm = document.getElementById('notificationSettingsForm');
            const locationSettingsForm = document.getElementById('locationSettingsForm');
            const systemPreferencesForm = document.getElementById('systemPreferencesForm');

            const handleFormSubmission = async (event, formId) => {
                event.preventDefault();
                const form = document.getElementById(formId);
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());

                // Manually add unchecked checkboxes with value 0
                if (formId === 'notificationSettingsForm' || formId === 'locationSettingsForm') {
                    form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                        if (!formData.has(checkbox.name)) {
                            data[checkbox.name] = 0;
                        } else {
                            data[checkbox.name] = 1;
                        }
                    });
                }
                
                try {
                    const response = await fetch('settings.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams(data).toString(),
                    });
                    const result = await response.json();

                    alertContainer.textContent = result.message;
                    alertContainer.classList.remove('alert-success', 'alert-error');
                    if (result.success) {
                        alertContainer.classList.add('alert-success');
                    } else {
                        alertContainer.classList.add('alert-error');
                    }
                    alertContainer.style.display = 'block';
                    setTimeout(() => {
                        alertContainer.style.display = 'none';
                    }, 5000);

                    if (result.success && formId === 'profileSettingsForm') {
                        const userNameSpan = document.querySelector('.user-profile .user-name');
                        if (userNameSpan) {
                            userNameSpan.textContent = data.full_name;
                        }
                        const userAvatarDiv = document.querySelector('.user-profile .user-avatar');
                        if (userAvatarDiv) {
                            userAvatarDiv.textContent = data.full_name.substring(0,1).toUpperCase() + (data.full_name.indexOf(' ') > -1 ? data.full_name.split(' ')[1].substring(0,1).toUpperCase() : '');
                        }
                    }

                } catch (error) {
                    console.error('Error:', error);
                    alertContainer.textContent = "An unexpected error occurred.";
                    alertContainer.classList.remove('alert-success');
                    alertContainer.classList.add('alert-error');
                    alertContainer.style.display = 'block';
                    setTimeout(() => {
                        alertContainer.style.display = 'none';
                    }, 5000);
                }
            };

            profileSettingsForm.addEventListener('submit', (e) => handleFormSubmission(e, 'profileSettingsForm'));
            securitySettingsForm.addEventListener('submit', (e) => handleFormSubmission(e, 'securitySettingsForm'));
            notificationSettingsForm.addEventListener('submit', (e) => handleFormSubmission(e, 'notificationSettingsForm'));
            locationSettingsForm.addEventListener('submit', (e) => handleFormSubmission(e, 'locationSettingsForm'));
            systemPreferencesForm.addEventListener('submit', (e) => handleFormSubmission(e, 'systemPreferencesForm'));
        });
    </script>
</body>
</html>