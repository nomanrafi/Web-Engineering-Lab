<?php
session_start();
// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include the database connection file
require_once 'db_connect.php';

// --- Add Customer Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_customer'])) {
    // Sanitize and trim all incoming form data to prevent SQL injection and XSS.
    $customer_name = trim($_POST['customer_name']);
    $contact_person = trim($_POST['contact_person']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $territory = trim($_POST['territory']);
    $status = 'New'; // Default status for a newly added customer.
    $last_contact_date = date('Y-m-d'); // Set current date as last contact date.
    $user_id = $_SESSION['user_id'];

    // Basic server-side validation to ensure required fields are not empty.
    if (empty($customer_name) || empty($contact_person) || empty($email) || empty($address) || empty($territory)) {
        $_SESSION['customer_message'] = "All fields are required.";
        $_SESSION['customer_is_error'] = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['customer_message'] = "Invalid email format.";
        $_SESSION['customer_is_error'] = true;
    } else {
        // Prepare a SQL statement to check for an existing customer with the same email.
        $check_stmt = $conn->prepare("SELECT email FROM customers WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $_SESSION['customer_message'] = "Error: A customer with this email already exists.";
            $_SESSION['customer_is_error'] = true;
            $check_stmt->close();
        } else {
            // Proceed with registration if email is not found.
            $check_stmt->close();

            // Prepare a new SQL statement to insert the new customer data.
            $stmt_add_customer = $conn->prepare("INSERT INTO customers (customer_name, contact_person, email, address, territory, total_orders, total_revenue, status, last_contact_date, added_by_user_id) VALUES (?, ?, ?, ?, ?, 0, 0.00, ?, ?, ?)");

            if ($stmt_add_customer === false) {
                $_SESSION['customer_message'] = "Database error: Could not prepare statement.";
                $_SESSION['customer_is_error'] = true;
            } else {
                $stmt_add_customer->bind_param("sssssssi", $customer_name, $contact_person, $email, $address, $territory, $status, $last_contact_date, $user_id);

                if ($stmt_add_customer->execute()) {
                    $_SESSION['customer_message'] = "Customer added successfully!";
                    $_SESSION['customer_is_error'] = false;
                } else {
                    $_SESSION['customer_message'] = "Error adding customer: " . $stmt_add_customer->error;
                    $_SESSION['customer_is_error'] = true;
                }
                $stmt_add_customer->close();
            }
        }
    }
    // Redirect to prevent form resubmission on page refresh.
    header('Location: customer.php');
    exit();
}

// --- Delete Customer Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['customer_id'])) {
    $customer_id_to_delete = filter_var($_GET['customer_id'], FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];

    if ($customer_id_to_delete) {
        // Prepare a SQL statement to delete the customer.
        // The check on added_by_user_id prevents one user from deleting another's customers.
        $stmt_delete_customer = $conn->prepare("DELETE FROM customers WHERE customer_id = ? AND added_by_user_id = ?");

        if ($stmt_delete_customer === false) {
            $_SESSION['customer_message'] = "Database error: Could not prepare delete statement.";
            $_SESSION['customer_is_error'] = true;
        } else {
            $stmt_delete_customer->bind_param("ii", $customer_id_to_delete, $user_id);

            if ($stmt_delete_customer->execute()) {
                if ($stmt_delete_customer->affected_rows > 0) {
                    $_SESSION['customer_message'] = "Customer deleted successfully!";
                    $_SESSION['customer_is_error'] = false;
                } else {
                    $_SESSION['customer_message'] = "Error: Customer not found or you don't have permission to delete it.";
                    $_SESSION['customer_is_error'] = true;
                }
            } else {
                $_SESSION['customer_message'] = "Error deleting customer: " . $stmt_delete_customer->error;
                $_SESSION['customer_is_error'] = true;
            }
            $stmt_delete_customer->close();
        }
    } else {
        $_SESSION['customer_message'] = "Invalid customer ID.";
        $_SESSION['customer_is_error'] = true;
    }

    // Redirect to prevent re-deletion on refresh.
    header('Location: customer.php');
    exit();
}

// --- Update Customer Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_customer'])) {
    $customer_id = trim($_POST['customer_id']);
    $customer_name = trim($_POST['customer_name']);
    $contact_person = trim($_POST['contact_person']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $status = trim($_POST['status']);
    $territory = trim($_POST['territory']);
    $user_id = $_SESSION['user_id'];

    // Basic validation
    if (empty($customer_name) || empty($contact_person) || empty($email) || empty($address) || empty($territory) || empty($status)) {
        $_SESSION['customer_message'] = "All fields are required.";
        $_SESSION['customer_is_error'] = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['customer_message'] = "Invalid email format.";
        $_SESSION['customer_is_error'] = true;
    } else {
        // Prepare an update statement
        $stmt = $conn->prepare("UPDATE customers SET customer_name = ?, contact_person = ?, email = ?, address = ?, status = ?, territory = ? WHERE customer_id = ? AND added_by_user_id = ?");
        $stmt->bind_param("ssssssii", $customer_name, $contact_person, $email, $address, $status, $territory, $customer_id, $user_id);

        if ($stmt->execute()) {
            $_SESSION['customer_message'] = "Customer updated successfully!";
            $_SESSION['customer_is_error'] = false;
        } else {
            $_SESSION['customer_message'] = "Error updating customer: " . $stmt->error;
            $_SESSION['customer_is_error'] = true;
        }
        $stmt->close();
    }
    header('Location: customer.php');
    exit();
}

// Fetch User-specific data for the header (this block remains unchanged)
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

// --- Fetch Dynamic Data for Customer Metrics and List ---
$total_customers = 0;
$active_customers = 0;
$premium_customers = 0;
$total_revenue = 0;

// Total Customers
$query_total_customers = "SELECT COUNT(*) as total FROM customers WHERE added_by_user_id = ?";
$stmt_total = $conn->prepare($query_total_customers);
$stmt_total->bind_param("i", $user_id);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
if ($result_total->num_rows > 0) {
    $row_total = $result_total->fetch_assoc();
    $total_customers = $row_total['total'] ?? 0;
}
$stmt_total->close();

// Active Customers
$query_active_customers = "SELECT COUNT(*) as total FROM customers WHERE added_by_user_id = ? AND status = 'Active'";
$stmt_active = $conn->prepare($query_active_customers);
$stmt_active->bind_param("i", $user_id);
$stmt_active->execute();
$result_active = $stmt_active->get_result();
if ($result_active->num_rows > 0) {
    $row_active = $result_active->fetch_assoc();
    $active_customers = $row_active['total'] ?? 0;
}
$stmt_active->close();

// Premium Customers
$query_premium_customers = "SELECT COUNT(*) as total FROM customers WHERE added_by_user_id = ? AND status = 'Premium'";
$stmt_premium = $conn->prepare($query_premium_customers);
$stmt_premium->bind_param("i", $user_id);
$stmt_premium->execute();
$result_premium = $stmt_premium->get_result();
if ($result_premium->num_rows > 0) {
    $row_premium = $result_premium->fetch_assoc();
    $premium_customers = $row_premium['total'] ?? 0;
}
$stmt_premium->close();

// Total Revenue from Customers
$query_total_revenue = "SELECT SUM(o.order_total) as total_rev FROM orders o JOIN customers c ON o.customer_id = c.customer_id WHERE c.added_by_user_id = ?";
$stmt_revenue = $conn->prepare($query_total_revenue);
$stmt_revenue->bind_param("i", $user_id);
$stmt_revenue->execute();
$result_revenue = $stmt_revenue->get_result();
if ($result_revenue->num_rows > 0) {
    $row_revenue = $result_revenue->fetch_assoc();
    $total_revenue = $row_revenue['total_rev'] ? number_format($row_revenue['total_rev'] / 1000, 0) . 'K' : '0';
}
$stmt_revenue->close();


// Fetch Customer List Data with real-time order data
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
    ORDER BY c.created_at DESC
";

$stmt_list = $conn->prepare($query_customer_list);
$stmt_list->bind_param("i", $user_id);
$stmt_list->execute();
$result_list = $stmt_list->get_result();

while ($row = $result_list->fetch_assoc()) {
    // Format the revenue
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

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <title>Sales Navigator - Customer Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" xintegrity="sha512-Fo3rlalHpgO702sN1l8/p5pT+c+W5U/6a7F6S3a5b0S3e5w1/b0R7+v5F5t5C5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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

        /* Customer Management Specific Styles */
        .customer-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 20px; /* Increased gap for better spacing */
            flex-wrap: nowrap; /* Prevent wrapping by default */
        }

        .customer-controls .search-bar {
            flex: 1; /* Take available space */
            position: relative;
            min-width: 200px; /* Minimum width to prevent squishing */
        }

        .customer-controls .search-bar input {
            width: 100%;
            padding: 12px 15px 12px 40px; /* Slightly increased padding */
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-color);
            font-size: 0.9rem;
            outline: none;
            box-sizing: border-box; /* Ensure padding doesn't affect width */
        }

        .customer-controls .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--subtle-text);
            pointer-events: none; /* Ensure icon doesn't interfere with input */
        }

        .customer-controls .status-filter {
            width: 150px; /* Fixed width for consistency */
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            padding: 12px 35px 12px 15px; /* Adjusted padding to match input height */
            border-radius: 10px;
            font-size: 0.9rem;
            appearance: none;
            -webkit-appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23a0a0a0"><path d="M7 10l5 5 5-5z"/></svg>');
            background-position: right 10px center; /* Position the dropdown arrow */
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 18px;
            cursor: pointer;
        }

        .customer-controls .add-customer-btn {
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

        .customer-controls .add-customer-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 255, 100, 0.4);
        }

        .customer-summary-cards {
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

        .customer-list-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .customer-card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
        }

        .customer-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .customer-avatar-lg {
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

        .customer-info-main {
            flex-grow: 1;
            text-align: left;
        }

        .customer-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .customer-contact-info {
            font-size: 0.85rem;
            color: var(--subtle-text);
            margin-top: 5px;
        }

        .customer-status-badge {
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: auto; /* Pushes badge to the right */
        }
        .customer-status-badge.active { background-color: rgba(0, 255, 100, 0.2); color: var(--highlight-green); }
        .customer-status-badge.new { background-color: rgba(0, 191, 255, 0.2); color: var(--highlight-blue); }
        .customer-status-badge.inactive { background-color: rgba(255, 77, 77, 0.2); color: var(--highlight-red); }
        .customer-status-badge.premium { background-color: rgba(255, 215, 0, 0.2); color: var(--highlight-yellow); }

        .customer-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 0.9rem;
            color: var(--text-color);
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-item i {
            color: var(--subtle-text);
        }

        .customer-actions {
            display: flex;
            justify-content: space-around;
            gap: 10px;
        }

        .customer-actions .btn {
            flex-grow: 1;
            padding: 10px 15px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .customer-actions .btn.delete {
            background-color: rgba(255, 77, 77, 0.2);
            color: var(--highlight-red);
        }

        .customer-actions .btn:hover {
            transform: translateY(-2px);
        }
        .customer-actions .btn.delete:hover {
            box-shadow: 0 4px 10px rgba(255, 77, 77, 0.4);
        }

        .customer-actions .btn.view {
            background-color: rgba(0, 191, 255, 0.2);
            color: var(--highlight-blue);
        }
        .customer-actions .btn.view:hover {
            box-shadow: 0 4px 10px rgba(0, 191, 255, 0.4);
        }

        .customer-actions .btn.edit {
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            color: #1a1a1a;
        }
        .customer-actions .btn.edit:hover {
            box-shadow: 0 4px 10px rgba(0, 255, 100, 0.4);
        }
        
        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1002; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.8); /* Black w/ opacity */
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

        /* Add customer form specific styles */
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
        .modal-content textarea,
        .modal-content select {
            width: calc(100% - 20px); /* Adjust for padding */
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
        .modal-content textarea:focus,
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

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            body {
                display: block; /* Stack elements vertically on mobile */
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
            .customer-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 10px; /* Reduced gap for mobile */
            }
            .customer-controls .search-bar {
                width: 100%;
                order: 1; /* Show search bar first on mobile */
            }
            .customer-controls .status-filter {
                width: 100%;
                order: 2; /* Show status filter second */
            }
            .customer-controls .add-customer-btn {
                width: 100%;
                order: 3; /* Show add button last */
            }
            .customer-summary-cards {
                grid-template-columns: 1fr;
            }
            .customer-list-grid {
                grid-template-columns: 1fr;
            }
            .customer-details {
                grid-template-columns: 1fr;
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
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="#" class="active"><i class="fas fa-users"></i> Customer Management</a></li>
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
                <span class="page-title">Customer Management</span>
                <span style="font-size: 0.9rem; color: var(--subtle-text); margin-left: 10px;">Manage your customer accounts</span>
            </div>
            <div class="header-right">
                <a href="#" class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                    <span class="user-role"><?php echo htmlspecialchars($user_role); ?></span>
                </a>
            </div>
        </header>

        <div class="customer-controls">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search customers by name, contact, or ID...">
            </div>
            <select class="status-filter">
                <option>All Status</option>
                <option>Active</option>
                <option>New</option>
                <option>Inactive</option>
                <option>Premium</option>
            </select>
            <a href="#" class="add-customer-btn" id="addCustomerBtn">
                <i class="fas fa-plus"></i> Add New Customer
            </a>
        </div>

        <?php
        // Display session messages from the Add Customer operation
        if (isset($_SESSION['customer_message'])): ?>
            <div class="alert-container <?php echo $_SESSION['customer_is_error'] ? 'alert-error' : 'alert-success'; ?>">
                <?php echo htmlspecialchars($_SESSION['customer_message']); ?>
            </div>
            <?php
            unset($_SESSION['customer_message']);
            unset($_SESSION['customer_is_error']);
            ?>
        <?php endif; ?>

        <div class="customer-summary-cards">
            <div class="summary-card green">
                <i class="fas fa-user-check icon"></i>
                <div class="value"><?php echo htmlspecialchars($active_customers); ?></div>
                <div class="label">Active Customers</div>
            </div>
            <div class="summary-card blue">
                <i class="fas fa-users icon"></i>
                <div class="value"><?php echo htmlspecialchars($total_customers); ?></div>
                <div class="label">Total Customers</div>
            </div>
            <div class="summary-card yellow">
                <i class="fas fa-wallet icon"></i>
                <div class="value">৳<?php echo htmlspecialchars($total_revenue); ?></div>
                <div class="label">Total Revenue</div>
            </div>
            <div class="summary-card red">
                <i class="fas fa-crown icon"></i>
                <div class="value"><?php echo htmlspecialchars($premium_customers); ?></div>
                <div class="label">Premium Customers</div>
            </div>

        </div>

        <div class="customer-list-grid">
            <?php if (empty($customer_list)): ?>
                <p>No customers found for your account.</p>
            <?php else: ?>
                <?php foreach ($customer_list as $customer): ?>
                    <div class="customer-card">
                        <div class="customer-card-header">
                            <div class="customer-avatar-lg" style="background-color: var(--highlight-green);">
                                <?php echo htmlspecialchars(strtoupper(substr($customer['customer_name'], 0, 1))); ?>
                            </div>
                            <div class="customer-info-main">
                                <div class="customer-name"><?php echo htmlspecialchars($customer['customer_name']); ?></div>
                                <div class="customer-contact-info">
                                    <i class="fas fa-user"></i> Contact: <?php echo htmlspecialchars($customer['contact_person'] ?? 'N/A'); ?>
                                    <br>
                                    <i class="fas fa-envelope"></i> Email: <?php echo htmlspecialchars($customer['email'] ?? 'N/A'); ?>
                                    <br>
                                    <i class="fas fa-map-marker-alt"></i> Address: <?php echo htmlspecialchars($customer['address'] ?? 'N/A'); ?>
                                    <br>
                                    <i class="fas fa-map-marked-alt"></i> Territory: <?php echo htmlspecialchars($customer['territory'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <span class="customer-status-badge <?php echo strtolower($customer['status']); ?>">
                                <?php echo htmlspecialchars($customer['status']); ?>
                            </span>
                        </div>
                        <div class="customer-details">
                            <div class="detail-item">
                                <i class="fas fa-shopping-cart"></i> <?php echo htmlspecialchars($customer['total_orders']); ?> Orders
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-money-bill-wave"></i> ৳<?php echo htmlspecialchars(number_format($customer['total_revenue'] / 1000, 0)); ?>K
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($customer['last_contact_date'] ?? 'N/A'); ?>
                            </div>
                        </div>
                        <div class="customer-actions">
                            <a href="#" class="btn view"><i class="fas fa-eye"></i> View</a>
                            <a href="#" class="btn edit" onclick="openEditModal(<?php echo htmlspecialchars($customer['customer_id']); ?>);"><i class="fas fa-edit"></i> Edit</a>
                            <a href="customer.php?action=delete&customer_id=<?php echo htmlspecialchars($customer['customer_id']); ?>" class="btn delete" onclick="return confirm('Are you sure you want to delete this customer?');">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="addCustomerModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeAddCustomerModal()">&times;</span>
            <h2>Add New Customer</h2>
            <form id="addCustomerForm" method="POST" action="customer.php">
                <input type="hidden" name="add_customer" value="1">
                
                <div class="form-group">
                    <label for="customer_name">Customer Name:</label>
                    <input type="text" id="add_customer_name" name="customer_name" required>
                </div>
                
                <div class="form-group">
                    <label for="contact_person">Contact Person:</label>
                    <input type="text" id="add_contact_person" name="contact_person" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="add_email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea id="add_address" name="address" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="territory">Territory:</label>
                    <input type="text" id="add_territory" name="territory" value="<?php echo htmlspecialchars($user_territory); ?>" readonly>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeAddCustomerModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Add Customer</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editCustomerModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeEditCustomerModal()">&times;</span>
            <h2 id="modalTitle">Edit Customer</h2>
            <form id="editCustomerForm" method="POST" action="customer.php">
                <input type="hidden" name="update_customer" value="1">
                <input type="hidden" id="edit_customer_id" name="customer_id">
                
                <div class="form-group">
                    <label for="edit_customer_name">Customer Name:</label>
                    <input type="text" id="edit_customer_name" name="customer_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_contact_person">Contact Person:</label>
                    <input type="text" id="edit_contact_person" name="contact_person" required>
                </div>

                <div class="form-group">
                    <label for="edit_email">Email:</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="edit_address">Address:</label>
                    <textarea id="edit_address" name="address" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_territory">Territory:</label>
                    <input type="text" id="edit_territory" name="territory" required>
                </div>

                <div class="form-group">
                    <label for="edit_status">Status:</label>
                    <select id="edit_status" name="status" required>
                        <option value="New">New</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Premium">Premium</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeEditCustomerModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Update Customer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            const mainContent = document.getElementById('mainContent');
            const addCustomerBtn = document.getElementById('addCustomerBtn');
            const addCustomerModal = document.getElementById('addCustomerModal');
            const editCustomerModal = document.getElementById('editCustomerModal');
            const customerList = <?php echo json_encode($customer_list); ?>;
            const filterSelect = document.querySelector('.status-filter');

            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                if (window.innerWidth <= 768) {
                    mainContent.classList.toggle('overlay');
                }
            });

            mainContent.addEventListener('click', (event) => {
                if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                    if (!sidebar.contains(event.target) && event.target !== menuToggle) {
                        sidebar.classList.remove('active');
                        mainContent.classList.remove('overlay');
                    }
                }
            });

            function handleResize() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    mainContent.classList.remove('overlay');
                } else {
                    sidebar.classList.add('hidden');
                }
            }

            handleResize();
            window.addEventListener('resize', handleResize);

            window.openAddCustomerModal = function() {
                addCustomerModal.style.display = 'flex';
            }

            window.closeAddCustomerModal = function() {
                addCustomerModal.style.display = 'none';
            }
            
            window.openEditModal = function(customerId) {
                // Find the customer data from the PHP array
                const customer = customerList.find(c => c.customer_id == customerId);
                
                if (customer) {
                    // Populate the form fields with the customer data
                    document.getElementById('modalTitle').textContent = 'Edit Customer: ' + customer.customer_name;
                    document.getElementById('edit_customer_id').value = customer.customer_id;
                    document.getElementById('edit_customer_name').value = customer.customer_name;
                    document.getElementById('edit_contact_person').value = customer.contact_person;
                    document.getElementById('edit_email').value = customer.email;
                    document.getElementById('edit_address').value = customer.address;
                    document.getElementById('edit_territory').value = customer.territory;
                    
                    // Set the status dropdown correctly
                    document.getElementById('edit_status').value = customer.status;

                    // Show the modal
                    editCustomerModal.style.display = 'flex';
                }
            }
            
            window.closeEditCustomerModal = function() {
                editCustomerModal.style.display = 'none';
            }

            addCustomerBtn.addEventListener('click', (e) => {
                e.preventDefault();
                openAddCustomerModal();
            });

            // Update status filtering logic
            filterSelect.addEventListener('change', (e) => {
                const selectedStatus = e.target.value;
                const customerCards = document.querySelectorAll('.customer-card');

                customerCards.forEach(card => {
                    const statusBadge = card.querySelector('.customer-status-badge');
                    const status = statusBadge ? statusBadge.textContent.trim() : '';

                    if (selectedStatus === 'All Status' || status === selectedStatus) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });


            <?php if (isset($_SESSION['customer_message'])): ?>
                const message = "<?php echo htmlspecialchars($_SESSION['customer_message']); ?>";
                const isError = <?php echo isset($_SESSION['customer_is_error']) && $_SESSION['customer_is_error'] ? 'true' : 'false'; ?>;
                const alertDiv = document.createElement('div');
                alertDiv.textContent = message;
                alertDiv.style.padding = '10px';
                alertDiv.style.borderRadius = '8px';
                alertDiv.style.marginBottom = '20px';
                alertDiv.style.textAlign = 'center';
                alertDiv.style.fontWeight = 'bold';
                alertDiv.style.color = isError ? 'var(--highlight-red)' : 'var(--highlight-green)';
                alertDiv.style.backgroundColor = isError ? 'rgba(255, 77, 77, 0.2)' : 'rgba(0, 255, 100, 0.2)';
                alertDiv.style.border = isError ? '1px solid var(--highlight-red)' : '1px solid var(--highlight-green)';
                const header = document.querySelector('.header');
                header.insertAdjacentElement('afterend', alertDiv);
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);

                <?php
                unset($_SESSION['customer_message']);
                unset($_SESSION['customer_is_error']);
                ?>
            <?php endif; ?>
        });
    </script>
    </div>
</body>
</html>