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

// --- Add Customer Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_customer'])) {
    // Reopen connection if needed
    if (!isset($conn) || $conn->connect_error) {
        require_once 'db_connect.php';
    }

    // Sanitize and trim all incoming form data to prevent SQL injection and XSS.
    $customer_name = trim($_POST['customer_name']);
    $contact_person = trim($_POST['contact_person']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $territory = trim($_POST['territory']);
    $status = 'New'; // Default status for a newly added customer.
    $last_contact_date = date('Y-m-d'); // Set current date as last contact date.

    // Basic server-side validation to ensure required fields are not empty.
    if (empty($customer_name) || empty($contact_person) || empty($email) || empty($address) || empty($territory)) {
        $_SESSION['customer_message'] = "All fields are required.";
        $_SESSION['customer_is_error'] = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['customer_message'] = "Invalid email format.";
        $_SESSION['customer_is_error'] = true;
    } else {
        // Prepare a SQL statement to insert the new customer data.
        $stmt_add_customer = $conn->prepare("INSERT INTO customers (customer_name, contact_person, email, address, territory, total_orders, total_revenue, status, last_contact_date, added_by_user_id) VALUES (?, ?, ?, ?, ?, 0, 0.00, ?, ?, ?)");
        
        if ($stmt_add_customer === false) {
            $_SESSION['customer_message'] = "Database error: Could not prepare statement.";
            $_SESSION['customer_is_error'] = true;
        } else {
            $stmt_add_customer->bind_param("sssssssi", $customer_name, $contact_person, $email, $address, $territory, $status, $last_contact_date, $user_id);
            
            if ($stmt_add_customer->execute()) {
                $_SESSION['customer_message'] = "Customer added successfully!";
                $_SESSION['customer_is_error'] = false;
                header("Location: customer.php?added=1");
                exit();
            } else {
                $_SESSION['customer_message'] = "Error adding customer: " . $stmt_add_customer->error;
                $_SESSION['customer_is_error'] = true;
            }
            $stmt_add_customer->close();
        }
    }
}

// --- Fetch Dynamic Data for Customer Metrics ---
$total_customers = 0;
$active_customers = 0;
$premium_customers = 0;
$total_revenue = 0;

// Fetch all metrics in a single query
$metrics_query = "SELECT 
    COUNT(*) as total_customers,
    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_customers,
    SUM(CASE WHEN status = 'Premium' THEN 1 ELSE 0 END) as premium_customers,
    SUM(total_revenue) as total_revenue
FROM customers 
WHERE added_by_user_id = ?";

$stmt_metrics = $conn->prepare($metrics_query);
$stmt_metrics->bind_param("i", $user_id);
$stmt_metrics->execute();
$metrics_result = $stmt_metrics->get_result();
if ($metrics_result->num_rows > 0) {
    $metrics = $metrics_result->fetch_assoc();
    $total_customers = $metrics['total_customers'];
    $active_customers = $metrics['active_customers'];
    $premium_customers = $metrics['premium_customers'];
    $total_revenue = $metrics['total_revenue'] ? number_format($metrics['total_revenue'] / 1000, 0) . 'K' : '0';
}
$stmt_metrics->close();

// Fetch Customer List Data
$customer_list = [];
$query_customer_list = "
    SELECT 
        c.customer_id,
        c.customer_name,
        c.contact_person,
        c.email,
        c.address,
        c.territory,
        c.status,
        c.last_contact_date,
        COALESCE(COUNT(o.order_id), 0) as total_orders,
        COALESCE(SUM(o.order_total), 0) as total_revenue
    FROM customers c
    LEFT JOIN orders o ON c.customer_id = o.customer_id
    WHERE c.added_by_user_id = ?
    GROUP BY 
        c.customer_id, 
        c.customer_name,
        c.contact_person,
        c.email,
        c.address,
        c.territory,
        c.status,
        c.last_contact_date
    ORDER BY c.created_at DESC";

$stmt_list = $conn->prepare($query_customer_list);
$stmt_list->bind_param("i", $user_id);
$stmt_list->execute();
$result_list = $stmt_list->get_result();
while ($row = $result_list->fetch_assoc()) {
    // Format revenue
    $revenue = $row['total_revenue'];
    if ($revenue >= 1000000) {
        $row['formatted_revenue'] = number_format($revenue / 1000000, 1) . 'M';
    } elseif ($revenue >= 1000) {
        $row['formatted_revenue'] = number_format($revenue / 1000, 1) . 'K';
    } else {
        $row['formatted_revenue'] = number_format($revenue);
    }
    $customer_list[] = $row;
}
$stmt_list->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Navigator - Customer Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Your existing CSS remains unchanged */
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-chart-line logo-icon"></i>
                <span class="app-name">Sales Navigator</span>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="#" class="active"><i class="fas fa-users"></i> Customer Management</a></li>
                    <li><a href="order.php"><i class="fas fa-shopping-cart"></i> Order Management</a></li>
                    <li><a href="#"><i class="fas fa-box"></i> Product Management</a></li>
                    <li><a href="#"><i class="fas fa-chart-bar"></i> Sales Analytics</a></li>
                    <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <header class="header">
                <div class="header-left">
                    <button id="menuToggle" class="menu-toggle"><i class="fas fa-bars"></i></button>
                    <span class="page-title">Customer Management</span>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                        <span class="user-role"><?php echo htmlspecialchars($user_role); ?></span>
                    </div>
                </div>
            </header>

            <?php if (isset($_SESSION['customer_message'])): ?>
            <div class="alert <?php echo $_SESSION['customer_is_error'] ? 'alert-error' : 'alert-success'; ?>">
                <?php 
                echo htmlspecialchars($_SESSION['customer_message']);
                unset($_SESSION['customer_message']);
                unset($_SESSION['customer_is_error']);
                ?>
            </div>
            <?php endif; ?>

            <div class="customer-controls">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="customerSearch" placeholder="Search customers...">
                </div>
                <button id="addCustomerBtn" class="add-customer-btn">
                    <i class="fas fa-plus"></i> Add New Customer
                </button>
            </div>

            <!-- Summary Cards -->
            <div class="customer-summary-cards">
                <div class="summary-card">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-content">
                        <h3>Total Customers</h3>
                        <p class="card-value"><?php echo $total_customers; ?></p>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="card-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="card-content">
                        <h3>Active Customers</h3>
                        <p class="card-value"><?php echo $active_customers; ?></p>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="card-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="card-content">
                        <h3>Premium Customers</h3>
                        <p class="card-value"><?php echo $premium_customers; ?></p>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="card-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="card-content">
                        <h3>Total Revenue</h3>
                        <p class="card-value">৳<?php echo $total_revenue; ?></p>
                    </div>
                </div>
            </div>

            <!-- Customer List -->
            <div class="customer-list">
                <?php if (empty($customer_list)): ?>
                <div class="no-customers">
                    <i class="fas fa-users"></i>
                    <p>No customers found. Add your first customer!</p>
                </div>
                <?php else: ?>
                <div class="customer-grid">
                    <?php foreach ($customer_list as $customer): ?>
                    <div class="customer-card">
                        <div class="customer-header">
                            <div class="customer-initials">
                                <?php 
                                $initials = strtoupper(substr($customer['customer_name'], 0, 1));
                                echo htmlspecialchars($initials);
                                ?>
                            </div>
                            <div class="customer-status <?php echo strtolower($customer['status']); ?>">
                                <?php echo htmlspecialchars($customer['status']); ?>
                            </div>
                        </div>
                        <div class="customer-info">
                            <h3><?php echo htmlspecialchars($customer['customer_name']); ?></h3>
                            <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($customer['contact_person']); ?></p>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($customer['address']); ?></p>
                            <p><i class="fas fa-map"></i> <?php echo htmlspecialchars($customer['territory']); ?></p>
                        </div>
                        <div class="customer-stats">
                            <div class="stat">
                                <span>Orders</span>
                                <strong><?php echo $customer['total_orders']; ?></strong>
                            </div>
                            <div class="stat">
                                <span>Revenue</span>
                                <strong>৳<?php echo $customer['formatted_revenue']; ?></strong>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div id="addCustomerModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Customer</h2>
            <form id="addCustomerForm" method="POST">
                <div class="form-group">
                    <label for="customer_name">Customer Name*</label>
                    <input type="text" id="customer_name" name="customer_name" required>
                </div>
                <div class="form-group">
                    <label for="contact_person">Contact Person*</label>
                    <input type="text" id="contact_person" name="contact_person" required>
                </div>
                <div class="form-group">
                    <label for="email">Email*</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="address">Address*</label>
                    <input type="text" id="address" name="address" required>
                </div>
                <div class="form-group">
                    <label for="territory">Territory*</label>
                    <input type="text" id="territory" name="territory" required>
                </div>
                <input type="hidden" name="add_customer" value="1">
                <button type="submit" class="submit-btn">Add Customer</button>
            </form>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.getElementById('mainContent').classList.toggle('sidebar-active');
        });

        // Modal functionality
        const modal = document.getElementById('addCustomerModal');
        const addBtn = document.getElementById('addCustomerBtn');
        const closeBtn = document.querySelector('.modal .close');
        const form = document.getElementById('addCustomerForm');

        addBtn.onclick = function() {
            modal.style.display = 'block';
        }

        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Customer search functionality
        document.getElementById('customerSearch').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            document.querySelectorAll('.customer-card').forEach(card => {
                const customerName = card.querySelector('h3').textContent.toLowerCase();
                const customerEmail = card.querySelector('.fa-envelope').parentNode.textContent.toLowerCase();
                if (customerName.includes(searchText) || customerEmail.includes(searchText)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Show success message if present in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('added')) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-success';
            alert.textContent = 'Customer added successfully!';
            document.querySelector('.customer-controls').before(alert);
            
            // Remove parameter from URL
            window.history.replaceState({}, document.title, window.location.pathname);
            
            // Remove alert after 3 seconds
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }
    </script>
</body>
</html>
