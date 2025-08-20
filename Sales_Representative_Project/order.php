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

// --- Fetch Customers for Order Forms (for dropdowns) ---
$customers_for_dropdown = [];
$query_customers_for_dropdown = "SELECT customer_id, customer_name, contact_person FROM customers WHERE added_by_user_id = ? ORDER BY customer_name ASC";
$stmt_customers_for_dropdown = $conn->prepare($query_customers_for_dropdown);
$stmt_customers_for_dropdown->bind_param("i", $user_id);
$stmt_customers_for_dropdown->execute();
$result_customers_for_dropdown = $stmt_customers_for_dropdown->get_result();
while ($row = $result_customers_for_dropdown->fetch_assoc()) {
    $customers_for_dropdown[] = $row;
}
$stmt_customers_for_dropdown->close();

// --- Add Order Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_order'])) {
    $customer_id = trim($_POST['customer_id']);
    $order_total = trim($_POST['order_total']);
    $product_details = trim($_POST['product_details']);
    $quantity = trim($_POST['quantity']);
    $order_date = trim($_POST['order_date']);
    $delivery_date = trim($_POST['delivery_date']);
    $status = 'Pending'; // Default status for new orders

    if (empty($customer_id) || empty($order_total) || empty($product_details) || empty($quantity) || empty($order_date)) {
        $_SESSION['order_message'] = "All fields except Delivery Date are required.";
        $_SESSION['order_is_error'] = true;
    } elseif (!is_numeric($order_total) || $order_total <= 0) {
        $_SESSION['order_message'] = "Order total must be a positive number.";
        $_SESSION['order_is_error'] = true;
    } elseif (!is_numeric($quantity) || $quantity <= 0) {
        $_SESSION['order_message'] = "Quantity must be a positive integer.";
        $_SESSION['order_is_error'] = true;
    } else {
        $stmt_add_order = $conn->prepare("INSERT INTO orders (customer_id, sales_rep_id, order_total, product_details, quantity, order_date, delivery_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_add_order === false) {
            $_SESSION['order_message'] = "Database error: Could not prepare statement.";
            $_SESSION['order_is_error'] = true;
        } else {
            $stmt_add_order->bind_param("iidsisss", $customer_id, $user_id, $order_total, $product_details, $quantity, $order_date, $delivery_date, $status);
            if ($stmt_add_order->execute()) {
                $_SESSION['order_message'] = "Order created successfully!";
                $_SESSION['order_is_error'] = false;
            } else {
                $_SESSION['order_message'] = "Error creating order: " . $stmt_add_order->error;
                $_SESSION['order_is_error'] = true;
            }
            $stmt_add_order->close();
        }
    }
    header('Location: order.php');
    exit();
}

// --- Update Order Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $order_id = trim($_POST['order_id']);
    $customer_id = trim($_POST['customer_id']);
    $order_total = trim($_POST['order_total']);
    $product_details = trim($_POST['product_details']);
    $quantity = trim($_POST['quantity']);
    $order_date = trim($_POST['order_date']);
    $delivery_date = trim($_POST['delivery_date']);
    $status = trim($_POST['status']);
    $user_id = $_SESSION['user_id'];

    if (empty($order_id) || empty($customer_id) || empty($order_total) || empty($product_details) || empty($quantity) || empty($order_date) || empty($status)) {
        $_SESSION['order_message'] = "All fields except Delivery Date are required.";
        $_SESSION['order_is_error'] = true;
    } elseif (!is_numeric($order_total) || $order_total <= 0) {
        $_SESSION['order_message'] = "Order total must be a positive number.";
        $_SESSION['order_is_error'] = true;
    } elseif (!is_numeric($quantity) || $quantity <= 0) {
        $_SESSION['order_message'] = "Quantity must be a positive integer.";
        $_SESSION['order_is_error'] = true;
    } else {
        $stmt_update_order = $conn->prepare("UPDATE orders SET customer_id = ?, order_total = ?, product_details = ?, quantity = ?, order_date = ?, delivery_date = ?, status = ? WHERE order_id = ? AND sales_rep_id = ?");
        if ($stmt_update_order === false) {
            $_SESSION['order_message'] = "Database error: Could not prepare update statement.";
            $_SESSION['order_is_error'] = true;
        } else {
            // CORRECTED bind_param call
            $stmt_update_order->bind_param("isissisii", $customer_id, $order_total, $product_details, $quantity, $order_date, $delivery_date, $status, $order_id, $user_id);
            if ($stmt_update_order->execute()) {
                if ($stmt_update_order->affected_rows > 0) {
                    $_SESSION['order_message'] = "Order updated successfully!";
                    $_SESSION['order_is_error'] = false;
                } else {
                    $_SESSION['order_message'] = "Error: Order not found or you don't have permission to update it.";
                    $_SESSION['order_is_error'] = true;
                }
            } else {
                $_SESSION['order_message'] = "Error updating order: " . $stmt_update_order->error;
                $_SESSION['order_is_error'] = true;
            }
            $stmt_update_order->close();
        }
    }
    header('Location: order.php');
    exit();
}

// --- Delete Order Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['order_id'])) {
    $order_id_to_delete = filter_var($_GET['order_id'], FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];

    if ($order_id_to_delete) {
        $stmt_delete_order = $conn->prepare("DELETE FROM orders WHERE order_id = ? AND sales_rep_id = ?");
        if ($stmt_delete_order === false) {
            $_SESSION['order_message'] = "Database error: Could not prepare delete statement.";
            $_SESSION['order_is_error'] = true;
        } else {
            $stmt_delete_order->bind_param("ii", $order_id_to_delete, $user_id);
            if ($stmt_delete_order->execute()) {
                if ($stmt_delete_order->affected_rows > 0) {
                    $_SESSION['order_message'] = "Order deleted successfully!";
                    $_SESSION['order_is_error'] = false;
                } else {
                    $_SESSION['order_message'] = "Error: Order not found or you don't have permission to delete it.";
                    $_SESSION['order_is_error'] = true;
                }
            } else {
                $_SESSION['order_message'] = "Error deleting order: " . $stmt_delete_order->error;
                $_SESSION['order_is_error'] = true;
            }
            $stmt_delete_order->close();
        }
    } else {
        $_SESSION['order_message'] = "Invalid order ID.";
        $_SESSION['order_is_error'] = true;
    }
    header('Location: order.php');
    exit();
}

// --- Fetch Dynamic Data for Order Metrics ---
$total_orders = 0;
$pending_orders = 0;
$delivered_orders = 0;
$total_order_value = 0;

// Total Orders
$query_total_orders = "SELECT COUNT(*) as total FROM orders WHERE sales_rep_id = ?";
$stmt_total_orders = $conn->prepare($query_total_orders);
$stmt_total_orders->bind_param("i", $user_id);
$stmt_total_orders->execute();
$result_total_orders = $stmt_total_orders->get_result();
if ($result_total_orders->num_rows > 0) {
    $row_total_orders = $result_total_orders->fetch_assoc();
    $total_orders = $row_total_orders['total'] ?? 0;
}
$stmt_total_orders->close();

// Pending Orders
$query_pending_orders = "SELECT COUNT(*) as total FROM orders WHERE sales_rep_id = ? AND status = 'Pending'";
$stmt_pending_orders = $conn->prepare($query_pending_orders);
$stmt_pending_orders->bind_param("i", $user_id);
$stmt_pending_orders->execute();
$result_pending_orders = $stmt_pending_orders->get_result();
if ($result_pending_orders->num_rows > 0) {
    $row_pending_orders = $result_pending_orders->fetch_assoc();
    $pending_orders = $row_pending_orders['total'] ?? 0;
}
$stmt_pending_orders->close();

// Delivered Orders
$query_delivered_orders = "SELECT COUNT(*) as total FROM orders WHERE sales_rep_id = ? AND status = 'Delivered'";
$stmt_delivered_orders = $conn->prepare($query_delivered_orders);
$stmt_delivered_orders->bind_param("i", $user_id);
$stmt_delivered_orders->execute();
$result_delivered_orders = $stmt_delivered_orders->get_result();
if ($result_delivered_orders->num_rows > 0) {
    $row_delivered_orders = $result_delivered_orders->fetch_assoc();
    $delivered_orders = $row_delivered_orders['total'] ?? 0;
}
$stmt_delivered_orders->close();

// Total Order Value
$query_total_order_value = "SELECT SUM(order_total) as total FROM orders WHERE sales_rep_id = ?";
$stmt_total_order_value = $conn->prepare($query_total_order_value);
$stmt_total_order_value->bind_param("i", $user_id);
$stmt_total_order_value->execute();
$result_total_order_value = $stmt_total_order_value->get_result();
if ($result_total_order_value->num_rows > 0) {
    $row_total_order_value = $result_total_order_value->fetch_assoc();
    $total_order_value = $row_total_order_value['total'] ? number_format($row_total_order_value['total'] / 1000, 0) . 'K' : '0';
}
$stmt_total_order_value->close();

// --- Fetch All Orders List Data ---
$order_list = [];
$query_order_list = "SELECT o.order_id, c.customer_id, c.customer_name, c.contact_person, o.order_total, o.product_details, o.quantity, o.order_date, o.delivery_date, o.status FROM orders o JOIN customers c ON o.customer_id = c.customer_id WHERE o.sales_rep_id = ? ORDER BY o.order_date DESC";
$stmt_list = $conn->prepare($query_order_list);
$stmt_list->bind_param("i", $user_id);
$stmt_list->execute();
$result_list = $stmt_list->get_result();
while ($row = $result_list->fetch_assoc()) {
    $order_list[] = $row;
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
    <title>Sales Navigator - Order Management</title>
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

        /* Order Management Specific Styles */
        .order-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 20px;
            flex-wrap: nowrap;
        }

        .order-controls .search-bar {
            flex: 1;
            position: relative;
            min-width: 200px;
        }

        .order-controls .search-bar input {
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

        .order-controls .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--subtle-text);
            pointer-events: none;
        }

        .order-controls .status-filter {
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

        .order-controls .add-order-btn {
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

        .order-controls .add-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 255, 100, 0.4);
        }

        .order-summary-cards {
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

        .order-list-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .order-card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
        }

        .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .order-id {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .order-status-badge {
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .order-status-badge.pending { background-color: rgba(0, 191, 255, 0.2); color: var(--highlight-blue); }
        .order-status-badge.shipped { background-color: rgba(255, 215, 0, 0.2); color: var(--highlight-yellow); }
        .order-status-badge.delivered { background-color: rgba(0, 255, 100, 0.2); color: var(--highlight-green); }
        .order-status-badge.refunded { background-color: rgba(255, 77, 77, 0.2); color: var(--highlight-red); }
        .order-status-badge.paid { background-color: rgba(0, 255, 100, 0.2); color: var(--highlight-green); }
        .order-status-badge.cancelled { background-color: rgba(255, 77, 77, 0.2); color: var(--highlight-red); }

        .order-details-info {
            font-size: 0.9rem;
            color: var(--text-color);
            margin-bottom: 20px;
        }

        .order-details-info .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .order-details-info .detail-row span:first-child {
            color: var(--subtle-text);
            font-size: 0.85rem;
        }

        .order-actions {
            display: flex;
            justify-content: space-around;
            gap: 10px;
            margin-top: auto;
        }

        .order-actions .btn {
            flex-grow: 1;
            padding: 10px 15px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .order-actions .btn:hover {
            transform: translateY(-2px);
        }

        .order-actions .btn.view {
            background-color: rgba(0, 191, 255, 0.2);
            color: var(--highlight-blue);
        }
        .order-actions .btn.view:hover {
            box-shadow: 0 4px 10px rgba(0, 191, 255, 0.4);
        }

        .order-actions .btn.edit {
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            color: #1a1a1a;
        }
        .order-actions .btn.edit:hover {
            box-shadow: 0 4px 10px rgba(0, 255, 100, 0.4);
        }
        
        .order-actions .btn.delete {
            background-color: rgba(255, 77, 77, 0.2);
            color: var(--highlight-red);
        }
        .order-actions .btn.delete:hover {
            box-shadow: 0 4px 10px rgba(255, 77, 77, 0.4);
        }
        
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
        .modal-content select,
        .modal-content input[type="number"],
        .modal-content input[type="date"] {
            width: calc(100% - 20px);
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--field-bg);
            color: var(--text-color);
            font-size: 0.9rem;
            box-sizing: border-box;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s, box-shadow 0.2s;
        }

        .modal-actions .cancel-btn {
            background-color: var(--subtle-text);
            color: var(--main-bg);
        }

        .modal-actions .submit-btn {
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            color: #1a1a1a;
        }
        
        .close-button {
            color: var(--subtle-text);
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            right: 20px;
            top: 10px;
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
            .order-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 10px; /* Reduced gap for mobile */
            }
            .order-controls .search-bar,
            .order-controls .status-filter,
            .order-controls .add-order-btn {
                width: 100%;
            }
            .order-summary-cards {
                grid-template-columns: 1fr;
            }
            .order-list-grid {
                grid-template-columns: 1fr;
            }
            .order-details-info {
                display: block; /* Stack details vertically */
                gap: 5px;
            }
            .order-actions {
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
                <li><a href="customer.php"><i class="fas fa-users"></i> Customer Management</a></li>
                <li><a href="#" class="active"><i class="fas fa-shopping-cart"></i> Order Management</a></li>
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
                    <span class="page-title">Order Management</span>
                    <span style="font-size: 0.9rem; color: var(--subtle-text); margin-left: 10px;">Track and manage your customer orders effortlessly</span>
                </div>
                <div class="header-right">
                    <a href="#" class="user-profile">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                        <span class="user-role"><?php echo htmlspecialchars($user_role); ?></span>
                    </a>
                </div>
            </header>

            <div class="order-controls">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search orders by ID, customer, or contact...">
                </div>
                <select class="status-filter">
                    <option>All Status</option>
                    <option>Pending</option>
                    <option>Processing</option>
                    <option>Shipped</option>
                    <option>Delivered</option>
                    <option>Cancelled</option>
                    <option>Refunded</option>
                </select>
                <a href="#" class="add-order-btn" id="addOrderBtn">
                    <i class="fas fa-plus"></i> Create New Order
                </a>
            </div>

            <?php
            // Display session messages from order operations
            if (isset($_SESSION['order_message'])): ?>
                <div class="alert-container <?php echo $_SESSION['order_is_error'] ? 'alert-error' : 'alert-success'; ?>">
                    <?php echo htmlspecialchars($_SESSION['order_message']); ?>
                </div>
                <?php
                unset($_SESSION['order_message']);
                unset($_SESSION['order_is_error']);
                ?>
            <?php endif; ?>

            <div class="order-summary-cards">
                <div class="summary-card blue">
                    <i class="fas fa-shopping-cart icon"></i>
                    <div class="value"><?php echo htmlspecialchars($total_orders); ?></div>
                    <div class="label">Total Orders</div>
                </div>
                <div class="summary-card yellow">
                    <i class="fas fa-hourglass-half icon"></i>
                    <div class="value"><?php echo htmlspecialchars($pending_orders); ?></div>
                    <div class="label">Pending Orders</div>
                </div>
                <div class="summary-card green">
                    <i class="fas fa-truck icon"></i>
                    <div class="value"><?php echo htmlspecialchars($delivered_orders); ?></div>
                    <div class="label">Delivered Orders</div>
                </div>
                <div class="summary-card red">
                    <i class="fas fa-wallet icon"></i>
                    <div class="value">৳<?php echo htmlspecialchars($total_order_value); ?></div>
                    <div class="label">Total Value</div>
                </div>
            </div>

            <div class="order-list-grid">
                <?php if (empty($order_list)): ?>
                    <p>No orders found for your account.</p>
                <?php else: ?>
                    <?php foreach ($order_list as $order): ?>
                        <div class="order-card">
                            <div class="order-card-header">
                                <span class="order-id">ORD<?php echo htmlspecialchars($order['order_id']); ?></span>
                                <div class="order-status-badges">
                                    <span class="order-status-badge <?php echo strtolower($order['status']); ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                    <?php if ($order['status'] == 'Delivered'): ?>
                                    <span class="order-status-badge paid">Paid</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="order-details-info">
                                <div class="detail-row">
                                    <span>Customer:</span>
                                    <span><?php echo htmlspecialchars($order['customer_name']); ?> (<?php echo htmlspecialchars($order['contact_person']); ?>)</span>
                                </div>
                                <div class="detail-row">
                                    <span>Amount:</span>
                                    <span>৳<?php echo htmlspecialchars(number_format($order['order_total'])); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span>Products:</span>
                                    <span><?php echo htmlspecialchars($order['product_details']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span>Quantity:</span>
                                    <span><?php echo htmlspecialchars($order['quantity']); ?> units</span>
                                </div>
                                <div class="detail-row">
                                    <span>Ordered:</span>
                                    <span><?php echo htmlspecialchars($order['order_date']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span>Delivery:</span>
                                    <span><?php echo htmlspecialchars($order['delivery_date']); ?></span>
                                </div>
                            </div>
                            <div class="order-actions">
                                <a href="#" class="btn view"><i class="fas fa-eye"></i> View Details</a>
                                <a href="#" class="btn edit" onclick="openEditOrderModal(<?php echo htmlspecialchars($order['order_id']); ?>);"><i class="fas fa-edit"></i> Edit Order</a>
                                <a href="order.php?action=delete&order_id=<?php echo htmlspecialchars($order['order_id']); ?>" class="btn delete" onclick="return confirm('Are you sure you want to delete this order?');">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div id="addOrderModal" class="modal">
            <div class="modal-content">
                <span class="close-button" onclick="closeAddOrderModal()">&times;</span>
                <h2>Create New Order</h2>
                <form id="addOrderForm" method="POST" action="order.php">
                    <input type="hidden" name="add_order" value="1">
                    
                    <div class="form-group">
                        <label for="add_customer_id">Customer:</label>
                        <select id="add_customer_id" name="customer_id" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers_for_dropdown as $customer): ?>
                                <option value="<?php echo htmlspecialchars($customer['customer_id']); ?>">
                                    <?php echo htmlspecialchars($customer['customer_name']); ?> (<?php echo htmlspecialchars($customer['contact_person']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="add_order_total">Order Total (৳):</label>
                        <input type="number" id="add_order_total" name="order_total" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="add_product_details">Product Details:</label>
                        <textarea id="add_product_details" name="product_details" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="add_quantity">Quantity:</label>
                        <input type="number" id="add_quantity" name="quantity" required>
                    </div>

                    <div class="form-group">
                        <label for="add_order_date">Order Date:</label>
                        <input type="date" id="add_order_date" name="order_date" required>
                    </div>

                    <div class="form-group">
                        <label for="add_delivery_date">Delivery Date:</label>
                        <input type="date" id="add_delivery_date" name="delivery_date">
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="cancel-btn" onclick="closeAddOrderModal()">Cancel</button>
                        <button type="submit" class="submit-btn">Create Order</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="editOrderModal" class="modal">
            <div class="modal-content">
                <span class="close-button" onclick="closeEditOrderModal()">&times;</span>
                <h2 id="editModalTitle">Edit Order</h2>
                <form id="editOrderForm" method="POST" action="order.php">
                    <input type="hidden" name="update_order" value="1">
                    <input type="hidden" id="edit_order_id" name="order_id">
                    
                    <div class="form-group">
                        <label for="edit_customer_id">Customer:</label>
                        <select id="edit_customer_id" name="customer_id" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers_for_dropdown as $customer): ?>
                                <option value="<?php echo htmlspecialchars($customer['customer_id']); ?>">
                                    <?php echo htmlspecialchars($customer['customer_name']); ?> (<?php echo htmlspecialchars($customer['contact_person']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_order_total">Order Total (৳):</label>
                        <input type="number" id="edit_order_total" name="order_total" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_product_details">Product Details:</label>
                        <textarea id="edit_product_details" name="product_details" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="edit_quantity">Quantity:</label>
                        <input type="number" id="edit_quantity" name="quantity" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_order_date">Order Date:</label>
                        <input type="date" id="edit_order_date" name="order_date" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_delivery_date">Delivery Date:</label>
                        <input type="date" id="edit_delivery_date" name="delivery_date">
                    </div>

                    <div class="form-group">
                        <label for="edit_status">Status:</label>
                        <select id="edit_status" name="status" required>
                            <option value="Pending">Pending</option>
                            <option value="Processing">Processing</option>
                            <option value="Shipped">Shipped</option>
                            <option value="Delivered">Delivered</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Refunded">Refunded</option>
                        </select>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="cancel-btn" onclick="closeEditOrderModal()">Cancel</button>
                        <button type="submit" class="submit-btn">Update Order</button>
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
            const addOrderBtn = document.getElementById('addOrderBtn');
            const addOrderModal = document.getElementById('addOrderModal');
            const editOrderModal = document.getElementById('editOrderModal');
            const orderList = <?php echo json_encode($order_list); ?>;
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

            // --- Add Order Modal Functions ---
            window.openAddOrderModal = function() {
                addOrderModal.style.display = 'flex';
                // Reset form fields when opening
                document.getElementById('add_customer_id').value = '';
                document.getElementById('add_order_total').value = '';
                document.getElementById('add_product_details').value = '';
                document.getElementById('add_quantity').value = '';
                document.getElementById('add_order_date').value = '';
                document.getElementById('add_delivery_date').value = '';
            }

            window.closeAddOrderModal = function() {
                addOrderModal.style.display = 'none';
            }

            addOrderBtn.addEventListener('click', (e) => {
                e.preventDefault();
                openAddOrderModal();
            });

            // --- Edit Order Modal Functions ---
            window.openEditOrderModal = function(orderId) {
                const order = orderList.find(o => o.order_id == orderId);
                
                if (order) {
                    document.getElementById('editModalTitle').textContent = 'Edit Order: ORD' + order.order_id;
                    document.getElementById('edit_order_id').value = order.order_id;
                    document.getElementById('edit_customer_id').value = order.customer_id;
                    document.getElementById('edit_order_total').value = parseFloat(order.order_total).toFixed(2);
                    document.getElementById('edit_product_details').value = order.product_details;
                    document.getElementById('edit_quantity').value = order.quantity;
                    document.getElementById('edit_order_date').value = order.order_date;
                    document.getElementById('edit_delivery_date').value = order.delivery_date;
                    document.getElementById('edit_status').value = order.status;

                    editOrderModal.style.display = 'flex';
                }
            }

            window.closeEditOrderModal = function() {
                editOrderModal.style.display = 'none';
            }

            // --- Status Filtering Logic ---
            filterSelect.addEventListener('change', (e) => {
                const selectedStatus = e.target.value;
                const orderCards = document.querySelectorAll('.order-card');

                orderCards.forEach(card => {
                    const statusBadge = card.querySelector('.order-status-badge');
                    const status = statusBadge ? statusBadge.textContent.trim() : '';

                    if (selectedStatus === 'All Status' || status === selectedStatus) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });

            // --- Session Message Display ---
            <?php if (isset($_SESSION['order_message'])): ?>
                const message = "<?php echo htmlspecialchars($_SESSION['order_message']); ?>";
                const isError = <?php echo isset($_SESSION['order_is_error']) && $_SESSION['order_is_error'] ? 'true' : 'false'; ?>;
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
                unset($_SESSION['order_message']);
                unset($_SESSION['order_is_error']);
                ?>
            <?php endif; ?>
        });
    </script>
    </div>
</body>
</html>