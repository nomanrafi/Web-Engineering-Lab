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

// --- Fetch Leaderboard Data ---
$leaderboard_query = "
    SELECT
        u.user_id,
        u.full_name,
        u.role,
        u.division,
        u.territory,
        COALESCE(sales_data.total_sales, 0) AS total_sales,
        COALESCE(sales_data.total_orders, 0) AS total_orders,
        COALESCE(target_data.total_achieved_target, 0) AS total_achieved_target,
        COALESCE(target_data.total_set_target, 0) AS total_set_target,
        COALESCE(customer_data.total_customers, 0) AS total_customers
    FROM
        users u
    LEFT JOIN (
        SELECT
            sales_rep_id,
            SUM(order_total) AS total_sales,
            COUNT(order_id) AS total_orders
        FROM
            orders
        GROUP BY
            sales_rep_id
    ) AS sales_data ON u.user_id = sales_data.sales_rep_id
    LEFT JOIN (
        SELECT
            user_id,
            SUM(achieved_value) AS total_achieved_target,
            SUM(target_value) AS total_set_target
        FROM
            targets
        GROUP BY
            user_id
    ) AS target_data ON u.user_id = target_data.user_id
    LEFT JOIN (
        SELECT
            added_by_user_id,
            COUNT(customer_id) AS total_customers
        FROM
            customers
        GROUP BY
            added_by_user_id
    ) AS customer_data ON u.user_id = customer_data.added_by_user_id
    ORDER BY
        total_sales DESC, total_achieved_target DESC;
";

$leaderboard_data = [];
$result_leaderboard = $conn->query($leaderboard_query);

if ($result_leaderboard) {
    while ($row = $result_leaderboard->fetch_assoc()) {
        $achievement_percentage = 0;
        if ($row['total_set_target'] > 0) {
            $achievement_percentage = ($row['total_achieved_target'] / $row['total_set_target']) * 100;
        }
        
        // Calculate points based on a simple formula (e.g., 1 point per 1000 sales, 100 points per % achievement)
        $points = round(($row['total_sales'] / 1000) + ($achievement_percentage * 100));

        $leaderboard_data[] = [
            'user_id' => $row['user_id'],
            'name' => $row['full_name'],
            'role' => $row['role'] . ' - ' . ($row['territory'] ?? 'Global'),
            'sales' => $row['total_sales'],
            'achievement' => round($achievement_percentage, 1),
            'orders' => $row['total_orders'],
            'customers' => $row['total_customers'],
            'points' => $points
        ];
    }
}

// Sort data for top 3 display (if not already perfectly sorted by SQL, ensure consistency)
usort($leaderboard_data, function($a, $b) {
    if ($b['sales'] == $a['sales']) {
        return $b['achievement'] <=> $a['achievement'];
    }
    return $b['sales'] <=> $a['sales'];
});


$top_leaders = array_slice($leaderboard_data, 0, 3);

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Navigator - Leaderboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" xintegrity="sha512-Fo3rlalHpgO702sN1l8/p5pT+c+W5U/6a7F6S3a5b0S3e5w1/b0R7+v5F5t5C5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            --rank1-bg: rgba(0, 255, 100, 0.1);
            --rank2-bg: rgba(0, 191, 255, 0.1);
            --rank3-bg: rgba(255, 215, 0, 0.1);
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
            flex-wrap: wrap;
            gap: 10px;
        }

        .header-right .time-filter {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            padding: 8px 12px;
            border-radius: 8px;
            appearance: none;
            -webkit-appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23a0a0a0"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 18px;
            cursor: pointer;
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

        /* Leaderboard Specific Styles */
        .leaderboard-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }

        .leaderboard-controls .filter-select {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            padding: 8px 12px;
            border-radius: 8px;
            appearance: none;
            -webkit-appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23a0a0a0"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 18px;
            cursor: pointer;
        }

        .top-leader-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .top-leader-card {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px var(--shadow-color);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .top-leader-card.first-place {
            background: linear-gradient(135deg, #00ff66, #00cc55);
            color: #1a1a1a;
            box-shadow: 0 10px 20px rgba(0, 255, 100, 0.4);
        }
        .top-leader-card.second-place {
            background: linear-gradient(135deg, #00bfff, #0099cc);
            color: #1a1a1a;
            box-shadow: 0 10px 20px rgba(0, 191, 255, 0.4);
        }
        .top-leader-card.third-place {
            background: linear-gradient(135deg, #ffd700, #ccaa00);
            color: #1a1a1a;
            box-shadow: 0 10px 20px rgba(255, 215, 0, 0.4);
        }

        .top-leader-card .rank-icon {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 2rem;
            color: rgba(0,0,0,0.2);
        }
        .top-leader-card.first-place .rank-icon { color: rgba(24, 13, 13, 0.93); }
        .top-leader-card.second-place .rank-icon { color: rgba(24, 13, 13, 0.93); }
        .top-leader-card.third-place .rank-icon { color: rgba(24, 13, 13, 0.93); }


        .top-leader-card .leader-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 auto 15px;
            color: var(--text-color);
        }
        .top-leader-card.first-place .leader-avatar,
        .top-leader-card.second-place .leader-avatar,
        .top-leader-card.third-place .leader-avatar {
            color: #1a1a1a;
        }


        .top-leader-card .leader-name {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-color);
        }
        .top-leader-card.first-place .leader-name,
        .top-leader-card.second-place .leader-name,
        .top-leader-card.third-place .leader-name {
            color: #1a1a1a;
        }

        .top-leader-card .leader-role {
            font-size: 0.9rem;
            color: var(--subtle-text);
            margin-bottom: 15px;
        }
        .top-leader-card.first-place .leader-role,
        .top-leader-card.second-place .leader-role,
        .top-leader-card.third-place .leader-role {
            color: rgba(0,0,0,0.6);
        }

        .top-leader-card .leader-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--highlight-green);
        }
        .top-leader-card.first-place .leader-value,
        .top-leader-card.second-place .leader-value,
        .top-leader-card.third-place .leader-value {
            color: #1a1a1a;
        }

        .top-leader-card .leader-achievement {
            font-size: 0.9rem;
            color: var(--subtle-text);
        }
        .top-leader-card.first-place .leader-achievement,
        .top-leader-card.second-place .leader-achievement,
        .top-leader-card.third-place .leader-achievement {
            color: rgba(0,0,0,0.7);
        }


        .complete-rankings-card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px var(--shadow-color);
            margin-bottom: 30px;
        }

        .complete-rankings-header {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 15px;
        }

        .ranking-headers {
            display: grid;
            grid-template-columns: 40px 2.2fr repeat(5, 1fr);
            gap: 15px;
            align-items: center;
            padding: 10px 0;
            font-size: 0.8rem;
            color: var(--subtle-text);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .ranking-item {
            display: grid;
            grid-template-columns: 40px 1.5fr repeat(5, 1fr);
            gap: 15px;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .ranking-item:last-child {
            border-bottom: none;
        }
        
        .ranking-item.first-place { background-color: var(--rank1-bg); border-radius: 10px; }
        .ranking-item.second-place { background-color: var(--rank2-bg); border-radius: 10px; }
        .ranking-item.third-place { background-color: var(--rank3-bg); border-radius: 10px; }

        .ranking-place {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--subtle-text);
            text-align: center;
        }

        .ranking-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--highlight-green);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-right: 10px;
        }

        .ranking-info {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
        }

        .ranking-details {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .ranking-name {
            font-weight: 500;
            font-size: 0.95rem;
        }
        .ranking-role {
            font-size: 0.75rem;
            color: var(--subtle-text);
        }

        .ranking-sales { 
            color: var(--highlight-green); 
            text-align: center;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .ranking-achievement { 
            color: var(--highlight-blue); 
            text-align: center;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .ranking-orders { 
            color: var(--highlight-yellow); 
            text-align: center;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .ranking-customers { 
            color: var(--highlight-red); 
            text-align: center;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .ranking-points { 
            color: var(--text-color); 
            text-align: center;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .monthly-rewards-card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px var(--shadow-color);
        }

        .monthly-rewards-header {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 15px;
        }

        .reward-items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .reward-item {
            background-color: rgba(0,0,0,0.2);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .reward-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .reward-item.first-place .reward-icon { color: var(--highlight-green); }
        .reward-item.second-place .reward-icon { color: var(--highlight-blue); }
        .reward-item.third-place .reward-icon { color: var(--highlight-yellow); }

        .reward-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-color);
        }
        .reward-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--highlight-green);
            margin-top: 5px;
        }
        .reward-description {
            font-size: 0.8rem;
            color: var(--subtle-text);
            margin-top: 5px;
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
            .header-right {
                flex-direction: column;
                align-items: flex-end;
            }
            .header-right .time-filter {
                width: 100%;
                margin-bottom: 10px;
            }
            .header-right .user-profile .user-name {
                display: none;
            }
            .leaderboard-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .leaderboard-controls .filter-select {
                width: 100%;
            }
            .top-leader-cards {
                grid-template-columns: 1fr;
            }
            .ranking-headers {
                display: none;
            }
            .ranking-item {
                grid-template-columns: 1fr;
                gap: 10px;
                flex-direction: column;
                align-items: center;
                text-align: center;
                position: relative;
                padding-left: 20px;
                padding-right: 20px;
            }
            .ranking-place {
                position: absolute;
                top: 15px;
                left: 15px;
            }
            .ranking-info {
                text-align: center;
                flex-direction: column;
                align-items: center;
            }
            .ranking-info .ranking-avatar {
                margin-right: 0;
                margin-bottom: 10px;
            }
            .ranking-metrics {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                text-align: center;
                grid-column: span 2;
            }
            .ranking-metrics span {
                min-width: 80px;
            }
            .reward-items-grid {
                grid-template-columns: 1fr;
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
            .top-leader-cards {
                grid-template-columns: repeat(3, 1fr);
            }
            .ranking-headers {
                display: grid;
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
                <li><a href="#" class="active"><i class="fas fa-trophy"></i> Leaderboard</a></li>
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
                <span class="page-title">Sales Leaderboard</span>
                <span style="font-size: 0.9rem; color: var(--subtle-text); margin-left: 10px;">Compete with your team and earn rewards!</span>
            </div>
            <div class="header-right">
                <select class="time-filter">
                    <option>This Month</option>
                    <option>Last Month</option>
                    <option>This Quarter</option>
                    <option>This Year</option>
                </select>
                <a href="#" class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                    <span class="user-role"><?php echo htmlspecialchars($user_role); ?></span>
                </a>
            </div>
        </header>

        <div class="leaderboard-controls">
            <select class="filter-select">
                <option>Overall Ranking</option>
                <option>This Month</option>
                <option>This Quarter</option>
                <option>This Year</option>
            </select>
            <div style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem; color: var(--subtle-text);">
                <i class="fas fa-sync-alt"></i> Real-time updates
            </div>
        </div>

        <div class="top-leader-cards">
            <?php 
            $rank_colors = [
                1 => 'first-place',
                2 => 'second-place',
                3 => 'third-place'
            ];
            $rank_icons = [
                1 => 'fas fa-crown',
                2 => 'fas fa-medal',
                3 => 'fas fa-award'
            ];

            foreach ($top_leaders as $rank => $leader): 
                $current_rank = $rank + 1;
                $rank_class = $rank_colors[$current_rank] ?? '';
                $rank_icon = $rank_icons[$current_rank] ?? 'fas fa-trophy';
            ?>
                <div class="top-leader-card <?php echo $rank_class; ?>">
                    <i class="<?php echo $rank_icon; ?> rank-icon"></i>
                    <div class="leader-avatar"><?php echo htmlspecialchars(strtoupper(substr($leader['name'], 0, 1) . (strpos($leader['name'], ' ') !== false ? substr(strstr($leader['name'], ' '), 1, 1) : ''))); ?></div>
                    <div class="leader-name"><?php echo htmlspecialchars($leader['name']); ?></div>
                    <div class="leader-role"><?php echo htmlspecialchars($leader['role']); ?></div>
                    <div class="leader-value">৳<?php echo htmlspecialchars(number_format($leader['sales'], 0)); ?></div>
                    <div class="leader-achievement"><?php echo htmlspecialchars($leader['achievement']); ?>% Achievement</div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="complete-rankings-card">
            <div class="complete-rankings-header">Complete Rankings</div>
            <div class="ranking-headers">
                <span class="header-col">Rank</span>
                <span class="header-col">Sales Rep</span>
                <span class="header-col">Sales</span>
                <span class="header-col">Achvmnt</span>
                <span class="header-col">Orders</span>
                <span class="header-col">Customers</span>
                <span class="header-col">Points</span>
            </div>
            <div class="ranking-list">
                <?php foreach ($leaderboard_data as $rank => $leader): 
                    $current_rank = $rank + 1;
                    $row_class = '';
                    if ($current_rank <= 3) {
                        $row_class = $rank_colors[$current_rank] ?? '';
                    }
                ?>
                    <div class="ranking-item <?php echo $row_class; ?>">
                        <span class="ranking-place"><?php echo $current_rank; ?></span>
                        <div class="ranking-info">
                            <div class="ranking-avatar" style="background-color: <?php 
                                if ($current_rank == 1) echo 'var(--highlight-green)';
                                else if ($current_rank == 2) echo 'var(--highlight-blue)';
                                else if ($current_rank == 3) echo 'var(--highlight-yellow)';
                                else echo 'var(--subtle-text)';
                            ?>;">
                                <?php echo htmlspecialchars(strtoupper(substr($leader['name'], 0, 1) . (strpos($leader['name'], ' ') !== false ? substr(strstr($leader['name'], ' '), 1, 1) : ''))); ?>
                            </div>
                            <div class="ranking-details">
                                <div class="ranking-name"><?php echo htmlspecialchars($leader['name']); ?></div>
                                <div class="ranking-role"><?php echo htmlspecialchars($leader['role']); ?></div>
                            </div>
                        </div>
                        <span class="ranking-sales">৳<?php echo htmlspecialchars(number_format($leader['sales'], 0)); ?></span>
                        <span class="ranking-achievement"><?php echo htmlspecialchars($leader['achievement']); ?>%</span>
                        <span class="ranking-orders"><?php echo htmlspecialchars($leader['orders']); ?></span>
                        <span class="ranking-customers"><?php echo htmlspecialchars($leader['customers']); ?></span>
                        <span class="ranking-points"><?php echo htmlspecialchars(number_format($leader['points'], 0)); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="monthly-rewards-card">
            <div class="monthly-rewards-header">Monthly Rewards & Incentives</div>
            <div class="reward-items-grid">
                <?php 
                $rewards_incentives = [
                    ['place' => '1st Place', 'icon_class' => 'fas fa-medal', 'value' => '৳15,000', 'description' => 'Monthly Bonus + Company Recognition', 'class' => 'first-place'],
                    ['place' => '2nd Place', 'icon_class' => 'fas fa-award', 'value' => '৳12,000', 'description' => 'Monthly Bonus + Team Dinner', 'class' => 'second-place'],
                    ['place' => '3rd Place', 'icon_class' => 'fas fa-certificate', 'value' => '৳10,000', 'description' => 'Monthly Bonus + Badge / Appreciation', 'class' => 'third-place'],
                ];
                foreach ($rewards_incentives as $reward): ?>
                    <div class="reward-item <?php echo htmlspecialchars($reward['class']); ?>">
                        <i class="<?php echo htmlspecialchars($reward['icon_class']); ?> reward-icon"></i>
                        <div class="reward-title"><?php echo htmlspecialchars($reward['place']); ?></div>
                        <div class="reward-value"><?php echo htmlspecialchars($reward['value']); ?></div>
                        <div class="reward-description"><?php echo htmlspecialchars($reward['description']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            const mainContent = document.getElementById('mainContent');

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
        });
    </script>
</body>
</html>