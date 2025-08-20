<?php
session_start();
// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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


// --- Fetch Dynamic Data for Dashboard Metrics ---
// Total Sales
$total_sales = 0;
$query_sales = "SELECT SUM(order_total) as total FROM orders WHERE sales_rep_id = ?";
$stmt_sales = $conn->prepare($query_sales);
$stmt_sales->bind_param("i", $user_id);
$stmt_sales->execute();
$result_sales = $stmt_sales->get_result();
if ($result_sales->num_rows > 0) {
    $row_sales = $result_sales->fetch_assoc();
    $total_sales = $row_sales['total'] ? number_format($row_sales['total'] / 100000, 2) . 'L' : '0';
}
$stmt_sales->close();

// Total Customers
$total_customers = 0;
$query_customers = "SELECT COUNT(DISTINCT customer_id) as total FROM customers WHERE added_by_user_id = ?";
$stmt_customers = $conn->prepare($query_customers);
$stmt_customers->bind_param("i", $user_id);
$stmt_customers->execute();
$result_customers = $stmt_customers->get_result();
if ($result_customers->num_rows > 0) {
    $row_customers = $result_customers->fetch_assoc();
    $total_customers = $row_customers['total'] ?? 0;
}
$stmt_customers->close();

// Total Orders
$total_orders = 0;
$query_orders = "SELECT COUNT(*) as total FROM orders WHERE sales_rep_id = ?";
$stmt_orders = $conn->prepare($query_orders);
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();
if ($result_orders->num_rows > 0) {
    $row_orders = $result_orders->fetch_assoc();
    $total_orders = $row_orders['total'] ?? 0;
}
$stmt_orders->close();

// Target Achievement (simplified for this example)
$target_achieved = 1200000;
$target_total = 1500000;
$achievement_percentage = ($target_total > 0) ? ($target_achieved / $target_total) * 100 : 0;
$remaining_percentage = 100 - $achievement_percentage;

// Sales Leaderboard Data (dummy for now)
$leaderboard_data = [
    ['name' => 'Karim Uddin', 'role' => 'TSM - Dhaka A', 'sales' => '৳156,000'],
    ['name' => 'Rashida Begum', 'role' => 'Sales Representative - Dhaka A', 'sales' => '৳135,000'],
    ['name' => $user_name, 'role' => $user_role, 'sales' => '৳125,000'],
];

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Navigator - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" xintegrity="sha512-Fo3rlalHpgO702sN1l8/p5pT+c+W5U/6a7F6S3a5b0S3e5w1/b0R7+v5F5t5C5Q5Q5Q5Q5Q5Q5Q5Q5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            --shadow-color: rgba(0, 255, 100, 0.15);
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            background-color: var(--main-bg);
            color: var(--text-color);
            min-height: 100vh;
            overflow-x: hidden; /* Prevent horizontal scroll */
        }
        
        .wrapper {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 250px;
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
            color: #1a1a1a; /* Dark text on green background */
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 255, 100, 0.4);
        }

        /* Main Content Styling */
        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 290px; /* 250px sidebar width + 20px padding on both sides */
            width: calc(100% - 290px);
            min-width: 0; /* Prevent content from overflowing */
        }

        /* Header Styling */
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
            margin-right: 15px;
            display: none; /* Hidden by default */
        }

        .header-left .location-info {
            font-size: 0.9rem;
            color: var(--subtle-text);
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

        /* Dashboard Content Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        /* Card Styling */
        .card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-header .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .card-header .card-icon {
            font-size: 1.2rem;
            color: var(--highlight-green);
        }

        .card-body {
            flex-grow: 1;
        }

        .card-footer {
            margin-top: 15px;
            font-size: 0.85rem;
            color: var(--subtle-text);
        }

        /* Specific Card Styles */
        .metric-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .metric-card {
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

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
        }

        .metric-card.green::before {
            background-color: var(--highlight-green);
        }

        .metric-card.blue::before {
            background-color: var(--highlight-blue);
        }

        .metric-card.yellow::before {
            background-color: var(--highlight-yellow);
        }

        .metric-card.red::before {
            background-color: var(--highlight-red);
        }

        .metric-card .icon {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .metric-card .value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .metric-card .label {
            font-size: 0.8rem;
            color: var(--subtle-text);
        }

        .metric-card.green .value, .metric-card.green .icon { color: var(--highlight-green); }
        .metric-card.blue .value, .metric-card.blue .icon { color: var(--highlight-blue); }
        .metric-card.yellow .value, .metric-card.yellow .icon { color: var(--highlight-yellow); }
        .metric-card.red .value, .metric-card.red .icon { color: var(--highlight-red); }

        /* Chart-specific styling */
        .chart-container {
            position: relative;
            height: 200px; /* Fixed height for charts */
            width: 100%;
        }

        /* Two-column layout for top sections */
        .top-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        /* Sales Leaderboard Styling */
        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .leaderboard-item:last-child {
            border-bottom: none;
        }

        .leaderboard-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--highlight-green);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 0.9rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-right: 10px;
        }
        .leaderboard-info {
            flex-grow: 1;
        }
        .leaderboard-name {
            font-weight: 500;
            font-size: 0.95rem;
        }
        .leaderboard-role {
            font-size: 0.75rem;
            color: var(--subtle-text);
        }
        .leaderboard-value {
            font-weight: 600;
            color: var(--highlight-green);
        }

        /* Monthly Progress */
        .progress-item {
            margin-bottom: 15px;
        }
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        .progress-bar-container {
            background-color: #333;
            border-radius: 5px;
            height: 10px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            border-radius: 5px;
            width: 0%; /* Controlled by JS */
            transition: width 0.5s ease-out;
        }

        /* Live Location & Recent Alerts */
        .location-item, .alert-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .location-item:last-child, .alert-item:last-child {
            border-bottom: none;
        }
        .location-icon, .alert-icon {
            font-size: 1rem;
            margin-right: 10px;
            color: var(--highlight-green);
        }
        .location-text, .alert-text {
            font-size: 0.9rem;
        }
        .status-badge {
            margin-left: auto;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-badge.green { background-color: rgba(0, 255, 100, 0.2); color: var(--highlight-green); }
        .status-badge.yellow { background-color: rgba(255, 215, 0, 0.2); color: var(--highlight-yellow); }
        .status-badge.red { background-color: rgba(255, 77, 77, 0.2); color: var(--highlight-red); }
        .status-badge.blue { background-color: rgba(0, 191, 255, 0.2); color: var(--highlight-blue); }


        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .wrapper {
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
                display: block; /* Show toggle button on small screens */
            }
            .header-right .user-profile .user-name {
                display: none; /* Hide user name on small screens */
            }
            .dashboard-grid {
                grid-template-columns: 1fr; /* Stack cards on small screens */
            }
            .metric-cards {
                grid-template-columns: 1fr 1fr; /* Two columns for metric cards */
            }
            .top-section {
                grid-template-columns: 1fr; /* Stack top sections */
            }
        }

        @media (max-width: 480px) {
            .metric-cards {
                grid-template-columns: 1fr; /* Stack metric cards on very small screens */
            }
        }

        @media (min-width: 769px) {
            .sidebar {
                position: fixed;
                left: 0;
            }
            .header-left .menu-toggle {
                display: none; /* Hide toggle button on desktop */
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-chart-line logo-icon"></i>
                <span class="app-name">Sales Navigator</span>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="customer.php"><i class="fas fa-users"></i> Customer Management</a></li>
                <li><a href="order.php"><i class="fas fa-shopping-cart"></i> Order Management</a></li>
                <li><a href="product.php"><i class="fas fa-box"></i> Product Management</a></li>
                <li><a href="target.php"><i class="fas fa-bullseye"></i> Target Management</a></li>
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
                    <h1 class="location-info"><?php echo htmlspecialchars($header_location); ?></h1>
                </div>
                <div class="header-right">
                    <select class="time-filter" style="background-color: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 8px; margin-right: 15px;">
                        <option>This Month</option>
                        <option>Last Month</option>
                        <option>This Quarter</option>
                    </select>
                    <a href="#" class="user-profile">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                        <span class="user-role"><?php echo htmlspecialchars($user_role); ?></span>
                    </a>
                </div>
            </header>

            <div class="metric-cards">
                <div class="metric-card green">
                    <i class="fas fa-wallet icon"></i>
                    <div class="value">৳<?php echo htmlspecialchars($total_sales); ?></div>
                    <div class="label">Total Sales</div>
                </div>
                <div class="metric-card blue">
                    <i class="fas fa-bullseye icon"></i>
                    <div class="value"><?php echo round($achievement_percentage, 1); ?>%</div>
                    <div class="label">Achievement</div>
                </div>
                <div class="metric-card yellow">
                    <i class="fas fa-shopping-cart icon"></i>
                    <div class="value"><?php echo htmlspecialchars($total_orders); ?></div>
                    <div class="label">Total Orders</div>
                </div>
                <div class="metric-card red">
                    <i class="fas fa-users icon"></i>
                    <div class="value"><?php echo htmlspecialchars($total_customers); ?></div>
                    <div class="label">Customers</div>
                </div>
            </div>

            <div class="top-section">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Sales Performance</span>
                        <i class="fas fa-chart-line card-icon"></i>
                    </div>
                    <div class="card-body">
                        <canvas id="salesPerformanceChart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Target Achievement</span>
                        <i class="fas fa-bullseye card-icon"></i>
                    </div>
                    <div class="card-body">
                        <canvas id="targetAchievementChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="top-section">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Territory Performance</span>
                        <i class="fas fa-chart-bar card-icon"></i>
                    </div>
                    <div class="card-body">
                        <canvas id="territoryPerformanceChart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Sales Leaderboard</span>
                        <i class="fas fa-trophy card-icon"></i>
                    </div>
                    <div class="card-body">
                        <?php foreach ($leaderboard_data as $leader): ?>
                        <div class="leaderboard-item">
                            <div class="leaderboard-avatar" style="background-color: #00bfff;"><?php echo htmlspecialchars(strtoupper(substr($leader['name'], 0, 1) . substr(strstr($leader['name'], ' '), 1, 1))); ?></div>
                            <div class="leaderboard-info">
                                <div class="leaderboard-name"><?php echo htmlspecialchars($leader['name']); ?></div>
                                <div class="leaderboard-role"><?php echo htmlspecialchars($leader['role']); ?></div>
                            </div>
                            <div class="leaderboard-value"><?php echo htmlspecialchars($leader['sales']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="top-section">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Monthly Progress</span>
                        <i class="fas fa-calendar-alt card-icon"></i>
                    </div>
                    <div class="card-body">
                        <div class="progress-item">
                            <div class="progress-label">
                                <span>January</span>
                                <span>৳120,000 / ৳150,000</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: 80%;"></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="progress-label">
                                <span>February</span>
                                <span>৳125,000 / ৳150,000</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: 83.3%;"></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="progress-label">
                                <span>March</span>
                                <span>৳140,000 / ৳160,000</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: 87.5%;"></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="progress-label">
                                <span>April</span>
                                <span>৳125,000 / ৳150,000</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: 83.3%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Live Location</span>
                        <i class="fas fa-map-marker-alt card-icon"></i>
                    </div>
                    <div class="card-body">
                        <div class="location-item">
                            <i class="fas fa-user-circle location-icon"></i>
                            <span class="location-text">Customer A</span>
                            <span class="status-badge green">Completed</span>
                        </div>
                        <div class="location-item">
                            <i class="fas fa-user-circle location-icon"></i>
                            <span class="location-text">Customer B</span>
                            <span class="status-badge yellow">Pending</span>
                        </div>
                    </div>
                    <div class="card-header" style="margin-top: 20px;">
                        <span class="card-title">Recent Alerts</span>
                        <i class="fas fa-bell card-icon"></i>
                    </div>
                    <div class="card-body">
                        <div class="alert-item">
                            <i class="fas fa-exclamation-triangle alert-icon" style="color: var(--highlight-red);"></i>
                            <span class="alert-text">Target Behind</span>
                            <span class="status-badge red">Urgent</span>
                        </div>
                        <div class="alert-item">
                            <i class="fas fa-check-circle alert-icon" style="color: var(--highlight-green);"></i>
                            <span class="alert-text">Order Completed</span>
                            <span class="status-badge green">Success</span>
                        </div>
                        <div class="alert-item">
                            <i class="fas fa-calendar-times alert-icon" style="color: var(--highlight-blue);"></i>
                            <span class="alert-text">Customer Visit Due</span>
                            <span class="status-badge blue">Upcoming</span>
                        </div>
                        <div class="alert-item">
                            <i class="fas fa-money-bill-wave alert-icon" style="color: var(--highlight-yellow);"></i>
                            <span class="alert-text">Payment Overdue</span>
                            <span class="status-badge yellow">Warning</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            const mainContent = document.getElementById('mainContent');

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
            
            const salesPerformanceData = {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [
                    {
                        label: 'Actual Sales',
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
                        label: 'Target',
                        data: [105, 115, 120, 135, 145, 160],
                        borderColor: 'rgb(255, 215, 0)',
                        backgroundColor: 'rgba(255, 215, 0, 0.2)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'rgb(255, 215, 0)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgb(255, 215, 0)',
                    }
                ]
            };
            
            const targetAchievementData = {
                labels: ['Achieved', 'Remaining'],
                datasets: [{
                    data: [<?php echo $achievement_percentage; ?>, <?php echo $remaining_percentage; ?>],
                    backgroundColor: ['rgb(0, 255, 102)', 'rgb(50, 50, 50)'],
                    hoverOffset: 4
                }]
            };

            const territoryPerformanceData = {
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

            const commonChartOptions = {
                responsive: true,
                maintainAspectRatio: false,
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

            new Chart(document.getElementById('salesPerformanceChart'), {
                type: 'line',
                data: salesPerformanceData,
                options: commonChartOptions
            });

            new Chart(document.getElementById('targetAchievementChart'), {
                type: 'doughnut',
                data: targetAchievementData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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

            new Chart(document.getElementById('territoryPerformanceChart'), {
                type: 'bar',
                data: territoryPerformanceData,
                options: commonChartOptions
            });
        });
    </script>
</body>
</html>