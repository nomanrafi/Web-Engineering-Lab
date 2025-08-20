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


// --- Fetch Dynamic Data for Sales Analytics Summary Cards ---
$total_sales = 0;
$total_orders = 0;
$new_customers = 0;
$achievement_rate = 0;

// Total Sales (from orders table for the current user)
$query_total_sales = "SELECT SUM(order_total) as total FROM orders WHERE sales_rep_id = ?";
$stmt_total_sales = $conn->prepare($query_total_sales);
$stmt_total_sales->bind_param("i", $user_id);
$stmt_total_sales->execute();
$result_total_sales = $stmt_total_sales->get_result();
if ($result_total_sales && $result_total_sales->num_rows > 0) {
    $row_total_sales = $result_total_sales->fetch_assoc();
    $total_sales = $row_total_sales['total'] ? number_format($row_total_sales['total'] / 100000, 1) . 'L' : '0';
}
$stmt_total_sales->close();

// Total Orders (from orders table for the current user)
$query_total_orders = "SELECT COUNT(*) as total FROM orders WHERE sales_rep_id = ?";
$stmt_total_orders = $conn->prepare($query_total_orders);
$stmt_total_orders->bind_param("i", $user_id);
$stmt_total_orders->execute();
$result_total_orders = $stmt_total_orders->get_result();
if ($result_total_orders && $result_total_orders->num_rows > 0) {
    $row_total_orders = $result_total_orders->fetch_assoc();
    $total_orders = $row_total_orders['total'] ?? 0;
}
$stmt_total_orders->close();

// New Customers (from customers table for the current user, assuming 'New' status)
$query_new_customers = "SELECT COUNT(*) as total FROM customers WHERE added_by_user_id = ? AND status = 'New'";
$stmt_new_customers = $conn->prepare($query_new_customers);
$stmt_new_customers->bind_param("i", $user_id);
$stmt_new_customers->execute();
$result_new_customers = $stmt_new_customers->get_result();
if ($result_new_customers && $result_new_customers->num_rows > 0) {
    $row_new_customers = $result_new_customers->fetch_assoc();
    $new_customers = $row_new_customers['total'] ?? 0;
}
$stmt_new_customers->close();

// Achievement Rate (from targets table for the current user)
$query_achievement_rate = "SELECT SUM(target_value) as total_target_value, SUM(achieved_value) as total_achieved_value FROM targets WHERE user_id = ?";
$stmt_achievement_rate = $conn->prepare($query_achievement_rate);
$stmt_achievement_rate->bind_param("i", $user_id);
$stmt_achievement_rate->execute();
$result_achievement_rate = $stmt_achievement_rate->get_result();
if ($result_achievement_rate && $result_achievement_rate->num_rows > 0) {
    $row_achievement_rate = $result_achievement_rate->fetch_assoc();
    $total_target_value = $row_achievement_rate['total_target_value'] ?? 0;
    $total_achieved_value = $row_achievement_rate['total_achieved_value'] ?? 0;
    if ($total_target_value > 0) {
        $achievement_rate = ($total_achieved_value / $total_target_value) * 100;
    }
}
$stmt_achievement_rate->close();

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Navigator - Sales Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" xintegrity="sha512-Fo3rlalHpgO702sN1l8/p5pT+c+W5U/6a7F6S3a5b0S3e5w1/b0R7+v5F5t5C5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Sales Analytics Specific Styles */
        .analytics-summary-cards {
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

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .analytics-card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .analytics-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .analytics-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .analytics-card-icon {
            font-size: 1.2rem;
            color: var(--highlight-green);
        }

        .analytics-card-body {
            flex-grow: 1;
        }
        
        .chart-container {
            position: relative;
            height: 200px;
            width: 100%;
        }

        .customer-segment-card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
        }

        .customer-segment-header {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 15px;
        }

        .segment-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .segment-item:last-child {
            border-bottom: none;
        }

        .segment-label {
            font-size: 0.9rem;
            color: var(--subtle-text);
        }
        .segment-value {
            font-weight: 600;
            color: var(--text-color);
        }
        .segment-sub-value {
            font-size: 0.8rem;
            color: var(--subtle-text);
        }

        .performance-summary-card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
        }

        .performance-summary-header {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 15px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-size: 0.9rem;
            color: var(--subtle-text);
        }
        .summary-value {
            font-weight: 600;
            color: var(--text-color);
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
            .analytics-summary-cards {
                grid-template-columns: 1fr;
            }
            .analytics-grid {
                grid-template-columns: 1fr;
            }
            .customer-segment-card, .performance-summary-card {
                grid-column: span 1;
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
            .analytics-grid {
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
                <li><a href="#" class="active"><i class="fas fa-chart-bar"></i> Sales Analytics</a></li>
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
                <span class="page-title">Sales Analytics</span>
                <span style="font-size: 0.9rem; color: var(--subtle-text); margin-left: 10px;">Comprehensive sales performance insights and trends</span>
            </div>
            <div class="header-right">
                <select class="time-filter analytics-controls-filter">
                    <option value="current_month">This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="this_quarter">This Quarter</option>
                    <option value="last_quarter">Last Quarter</option>
                </select>
                <a href="#" class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                    <span class="user-role"><?php echo htmlspecialchars($user_role); ?></span>
                </a>
            </div>
        </header>

        <div class="analytics-summary-cards">
            <div class="summary-card green">
                <i class="fas fa-dollar-sign icon"></i>
                <div class="value">৳<?php echo htmlspecialchars($total_sales); ?></div>
                <div class="label">Total Sales</div>
            </div>
            <div class="summary-card blue">
                <i class="fas fa-shopping-bag icon"></i>
                <div class="value"><?php echo htmlspecialchars($total_orders); ?></div>
                <div class="label">Total Orders</div>
            </div>
            <div class="summary-card yellow">
                <i class="fas fa-users icon"></i>
                <div class="value"><?php echo htmlspecialchars($new_customers); ?></div>
                <div class="label">New Customers</div>
            </div>
            <div class="summary-card red">
                <i class="fas fa-bullseye icon"></i>
                <div class="value"><?php echo round($achievement_rate, 1); ?>%</div>
                <div class="label">Achievement Rate</div>
            </div>
        </div>

        <div class="analytics-grid">
            <div class="analytics-card">
                <div class="analytics-card-header">
                    <span class="analytics-card-title">Sales Trend Analysis</span>
                    <i class="fas fa-chart-line analytics-card-icon"></i>
                </div>
                <div class="analytics-card-body">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>

            <div class="analytics-card">
                <div class="analytics-card-header">
                    <span class="analytics-card-title">Sales by Category</span>
                    <i class="fas fa-chart-pie analytics-card-icon"></i>
                </div>
                <div class="analytics-card-body">
                    <canvas id="salesByCategoryChart"></canvas>
                </div>
            </div>

            <div class="analytics-card">
                <div class="analytics-card-header">
                    <span class="analytics-card-title">Territory Performance</span>
                    <i class="fas fa-chart-bar analytics-card-icon"></i>
                </div>
                <div class="analytics-card-body">
                    <canvas id="territoryPerformanceAnalyticsChart"></canvas>
                </div>
            </div>

            <div class="analytics-card">
                <div class="analytics-card-header">
                    <span class="analytics-card-title">Monthly Target Achievement</span>
                    <i class="fas fa-calendar-alt analytics-card-icon"></i>
                </div>
                <div class="analytics-card-body">
                    <canvas id="monthlyTargetAchievementChart"></canvas>
                </div>
            </div>

            <div class="customer-segment-card">
                <div class="customer-segment-header">Customer Segment Analysis</div>
                <div class="analytics-card-body">
                    <?php
                    // Dummy data for Customer Segment Analysis - In a real app, query customer_segments_monthly table
                    $customer_segments = [
                        ['label' => 'Premium Customers', 'value' => 12, 'revenue' => 420000, 'avg_order' => 35000],
                        ['label' => 'Regular Customers', 'value' => 18, 'revenue' => 290000, 'avg_order' => 16000],
                        ['label' => 'New Customers', 'value' => 8, 'revenue' => 95000, 'avg_order' => 12000],
                    ];
                    foreach ($customer_segments as $segment): ?>
                        <div class="segment-item">
                            <span class="segment-label"><?php echo htmlspecialchars($segment['label']); ?></span>
                            <span class="segment-value"><?php echo htmlspecialchars($segment['value']); ?></span>
                        </div>
                        <div class="segment-item">
                            <span class="segment-label">Total Revenue</span>
                            <span class="segment-sub-value">৳<?php echo htmlspecialchars(number_format($segment['revenue'], 0)); ?></span>
                        </div>
                        <div class="segment-item">
                            <span class="segment-label">Avg Order Value</span>
                            <span class="segment-sub-value">৳<?php echo htmlspecialchars(number_format($segment['avg_order'], 0)); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="performance-summary-card">
                <div class="performance-summary-header">Performance Summary</div>
                <div class="analytics-card-body">
                    <?php
                    // Dummy data for Performance Summary - In a real app, query sales_analytics_summary table
                    $performance_summary = [
                        ['label' => 'Avg Order Value', 'value' => 6428, 'unit' => ''],
                        ['label' => 'Conversion Rate', 'value' => 67.2, 'unit' => '%'],
                        ['label' => 'Monthly Growth', 'value' => 12.5, 'unit' => '%'],
                        ['label' => 'Customer Growth', 'value' => 8.3, 'unit' => '%'],
                    ];
                    foreach ($performance_summary as $item): ?>
                        <div class="summary-row">
                            <span class="summary-label"><?php echo htmlspecialchars($item['label']); ?></span>
                            <span class="summary-value">৳<?php echo htmlspecialchars(number_format($item['value'], $item['unit'] == '%' ? 1 : 0)); ?><?php echo htmlspecialchars($item['unit'] ?? ''); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
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

            // --- Chart Data (Dummy Data for Demonstration - Replace with dynamic data from PHP/DB) ---
            // Sales Trend Data - In a real app, fetch from sales_trends_monthly table
            const salesTrendData = {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [
                    {
                        label: 'Sales',
                        data: [100, 120, 110, 140, 130, 150],
                        borderColor: 'rgb(0, 255, 102)',
                        backgroundColor: 'rgba(0, 255, 102, 0.2)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'rgb(0, 255, 102)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgb(0, 255, 102)',
                    },
                    {
                        label: 'Orders',
                        data: [80, 100, 90, 110, 100, 120],
                        borderColor: 'rgb(0, 191, 255)',
                        backgroundColor: 'rgba(0, 191, 255, 0.2)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'rgb(0, 191, 255)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgb(0, 191, 255)',
                    }
                ]
            };

            // Sales by Category Data - In a real app, fetch from sales_by_category_monthly table
            const salesByCategoryData = {
                labels: ['Electronics', 'Office Supplies', 'Industrial Tools', 'Services', 'Others'],
                datasets: [{
                    data: [30, 20, 15, 25, 10],
                    backgroundColor: [
                        'rgb(0, 255, 102)',
                        'rgb(0, 191, 255)',
                        'rgb(255, 215, 0)',
                        'rgb(255, 77, 77)',
                        'rgb(160, 160, 160)'
                    ],
                    hoverOffset: 4
                }]
            };

            // Territory Performance Data - In a real app, fetch from territory_sales_monthly table
            const territoryPerformanceAnalyticsData = {
                labels: ['Dhaka A', 'Dhaka B', 'Chittagong A', 'Khulna A'],
                datasets: [
                    {
                        label: 'Sales',
                        data: [120, 90, 110, 80],
                        backgroundColor: 'rgb(0, 255, 102)',
                        borderColor: 'rgb(0, 255, 102)',
                        borderWidth: 1
                    },
                    {
                        label: 'Target',
                        data: [130, 100, 105, 90],
                        backgroundColor: 'rgb(255, 215, 0)',
                        borderColor: 'rgb(255, 215, 0)',
                        borderWidth: 1
                    }
                ]
            };

            // Monthly Target Achievement Data - In a real app, fetch from targets table or sales_trends_monthly
            const monthlyTargetAchievementData = {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [
                    {
                        label: 'Achievement %',
                        data: [75, 80, 85, 90, 88, 92],
                        borderColor: 'rgb(0, 255, 102)',
                        backgroundColor: 'rgba(0, 255, 102, 0.2)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'rgb(0, 255, 102)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgb(0, 255, 102)',
                    }
                ]
            };

            // --- Chart Options (Common for dark theme) ---
            const commonChartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                color: 'var(--subtle-text)', // Sets global font color
                plugins: {
                    legend: {
                        labels: {
                            color: 'var(--subtle-text)'
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'var(--subtle-text)'
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'var(--subtle-text)'
                        }
                    }
                }
            };

            // --- Render Charts ---
            new Chart(document.getElementById('salesTrendChart'), {
                type: 'line',
                data: salesTrendData,
                options: commonChartOptions
            });

            new Chart(document.getElementById('salesByCategoryChart'), {
                type: 'doughnut',
                data: salesByCategoryData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    color: 'var(--subtle-text)',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: 'var(--subtle-text)'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed !== null) {
                                        label += context.parsed.toFixed(1) + '%';
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    cutout: '70%',
                }
            });

            new Chart(document.getElementById('territoryPerformanceAnalyticsChart'), {
                type: 'bar',
                data: territoryPerformanceAnalyticsData,
                options: commonChartOptions
            });

            new Chart(document.getElementById('monthlyTargetAchievementChart'), {
                type: 'line',
                data: monthlyTargetAchievementData,
                options: commonChartOptions
            });
        });
    </script>
</body>
</html>