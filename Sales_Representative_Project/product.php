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

// --- Add Product Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $product_name = trim($_POST['product_name']);
    $price = floatval(trim($_POST['price']));
    $stock_quantity = intval(trim($_POST['stock_quantity']));
    $description = trim($_POST['description']);
    
    // Basic server-side validation
    if (empty($product_name) || !is_numeric($price) || $price <= 0 || !is_numeric($stock_quantity) || $stock_quantity < 0) {
        $_SESSION['product_message'] = "Product Name, a positive Price, and a non-negative Stock Quantity are required.";
        $_SESSION['product_is_error'] = true;
    } else {
        $stmt_add_product = $conn->prepare("INSERT INTO products (product_name, price, stock_quantity, description) VALUES (?, ?, ?, ?)");
        
        if ($stmt_add_product === false) {
            $_SESSION['product_message'] = "Database error: Could not prepare statement.";
            $_SESSION['product_is_error'] = true;
        } else {
            $stmt_add_product->bind_param("sdis", $product_name, $price, $stock_quantity, $description);
            
            if ($stmt_add_product->execute()) {
                $_SESSION['product_message'] = "Product added successfully!";
                $_SESSION['product_is_error'] = false;
            } else {
                $_SESSION['product_message'] = "Error adding product: " . $stmt_add_product->error;
                $_SESSION['product_is_error'] = true;
            }
            $stmt_add_product->close();
        }
    }
    header('Location: product.php');
    exit();
}

// --- Update Product Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $product_id = trim($_POST['product_id']);
    $product_name = trim($_POST['product_name']);
    $price = floatval(trim($_POST['price']));
    $stock_quantity = intval(trim($_POST['stock_quantity']));
    $description = trim($_POST['description']);
    
    if (empty($product_id) || empty($product_name) || !is_numeric($price) || $price <= 0 || !is_numeric($stock_quantity) || $stock_quantity < 0) {
        $_SESSION['product_message'] = "Product ID, Name, a positive Price, and a non-negative Stock Quantity are required.";
        $_SESSION['product_is_error'] = true;
    } else {
        $stmt_update_product = $conn->prepare("UPDATE products SET product_name = ?, price = ?, stock_quantity = ?, description = ? WHERE product_id = ?");
        
        if ($stmt_update_product === false) {
            $_SESSION['product_message'] = "Database error: Could not prepare update statement.";
            $_SESSION['product_is_error'] = true;
        } else {
            $stmt_update_product->bind_param("sdisi", $product_name, $price, $stock_quantity, $description, $product_id);
            
            if ($stmt_update_product->execute()) {
                if ($stmt_update_product->affected_rows > 0) {
                    $_SESSION['product_message'] = "Product updated successfully!";
                    $_SESSION['product_is_error'] = false;
                } else {
                    $_SESSION['product_message'] = "No changes were made or product not found.";
                    $_SESSION['product_is_error'] = false;
                }
            } else {
                $_SESSION['product_message'] = "Error updating product: " . $stmt_update_product->error;
                $_SESSION['product_is_error'] = true;
            }
            $stmt_update_product->close();
        }
    }
    header('Location: product.php');
    exit();
}

// --- Delete Product Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['product_id'])) {
    $product_id_to_delete = filter_var($_GET['product_id'], FILTER_SANITIZE_NUMBER_INT);
    
    if ($product_id_to_delete) {
        $stmt_delete_product = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        
        if ($stmt_delete_product === false) {
            $_SESSION['product_message'] = "Database error: Could not prepare delete statement.";
            $_SESSION['product_is_error'] = true;
        } else {
            $stmt_delete_product->bind_param("i", $product_id_to_delete);
            
            if ($stmt_delete_product->execute()) {
                if ($stmt_delete_product->affected_rows > 0) {
                    $_SESSION['product_message'] = "Product deleted successfully!";
                    $_SESSION['product_is_error'] = false;
                } else {
                    $_SESSION['product_message'] = "Error: Product not found.";
                    $_SESSION['product_is_error'] = true;
                }
            } else {
                $_SESSION['product_message'] = "Error deleting product: " . $stmt_delete_product->error;
                $_SESSION['product_is_error'] = true;
            }
            $stmt_delete_product->close();
        }
    } else {
        $_SESSION['product_message'] = "Invalid product ID.";
        $_SESSION['product_is_error'] = true;
    }
    header('Location: product.php');
    exit();
}

// --- Import Products from CSV Operation (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_products'])) {
    if (isset($_FILES['product_file']) && $_FILES['product_file']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['product_file']['tmp_name'];
        $file_mime_type = mime_content_type($file_tmp_path);

        // Check if the uploaded file is a CSV type
        if ($file_mime_type == 'text/csv' || $file_mime_type == 'application/vnd.ms-excel' || strpos($_FILES['product_file']['name'], '.csv') !== false) {
            $handle = fopen($file_tmp_path, "r");
            if ($handle !== FALSE) {
                // Prepare the SQL statement for insertion
                $stmt_import = $conn->prepare("INSERT INTO products (product_name, price, stock_quantity, description) VALUES (?, ?, ?, ?)");
                
                $row_count = 0;
                $success_count = 0;
                
                // Read each row from the CSV file
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if ($row_count == 0) {
                        // Skip the header row
                        $row_count++;
                        continue;
                    }
                    
                    // Assuming CSV columns are: Product Name, Price, Stock Quantity, Description
                    $product_name = trim($data[0]);
                    $price = floatval(trim($data[1]));
                    $stock_quantity = intval(trim($data[2]));
                    $description = trim($data[3]);

                    // Validate data before binding
                    if (!empty($product_name) && is_numeric($price) && $price > 0 && is_numeric($stock_quantity) && $stock_quantity >= 0) {
                        if ($stmt_import->bind_param("sdis", $product_name, $price, $stock_quantity, $description) && $stmt_import->execute()) {
                            $success_count++;
                        }
                    }
                    $row_count++;
                }
                
                fclose($handle);
                $stmt_import->close();

                if ($success_count > 0) {
                    $_SESSION['product_message'] = "$success_count products imported successfully!";
                    $_SESSION['product_is_error'] = false;
                } else {
                    $_SESSION['product_message'] = "No valid products were found in the file to import.";
                    $_SESSION['product_is_error'] = true;
                }
            } else {
                $_SESSION['product_message'] = "Error opening the uploaded file.";
                $_SESSION['product_is_error'] = true;
            }
        } else {
            $_SESSION['product_message'] = "Invalid file type. Please upload a CSV file.";
            $_SESSION['product_is_error'] = true;
        }
    } else {
        $_SESSION['product_message'] = "File upload failed or no file was selected.";
        $_SESSION['product_is_error'] = true;
    }
    header('Location: product.php');
    exit();
}


// --- Fetch Dynamic Data for Product Metrics ---
$total_products = 0;
$active_products = 0;
$low_stock_products = 0;
$out_of_stock_products = 0;
$total_product_revenue = 0;

// Total Products
$query_total_products = "SELECT COUNT(*) as total FROM products";
$result_total_products = $conn->query($query_total_products);
if ($result_total_products && $result_total_products->num_rows > 0) {
    $row_total_products = $result_total_products->fetch_assoc();
    $total_products = $row_total_products['total'] ?? 0;
}

// Active Products (stock_quantity > 0)
$query_active_products = "SELECT COUNT(*) as total FROM products WHERE stock_quantity > 0";
$result_active_products = $conn->query($query_active_products);
if ($result_active_products && $result_active_products->num_rows > 0) {
    $row_active_products = $result_active_products->fetch_assoc();
    $active_products = $row_active_products['total'] ?? 0;
}

// Low Stock Products (stock_quantity > 0 AND < 10)
$query_low_stock = "SELECT COUNT(*) as total FROM products WHERE stock_quantity > 0 AND stock_quantity < 10";
$result_low_stock = $conn->query($query_low_stock);
if ($result_low_stock && $result_low_stock->num_rows > 0) {
    $row_low_stock = $result_low_stock->fetch_assoc();
    $low_stock_products = $row_low_stock['total'] ?? 0;
}

// Out of Stock Products (stock_quantity = 0)
$query_out_of_stock = "SELECT COUNT(*) as total FROM products WHERE stock_quantity = 0";
$result_out_of_stock = $conn->query($query_out_of_stock);
if ($result_out_of_stock && $result_out_of_stock->num_rows > 0) {
    $row_out_of_stock = $result_out_of_stock->fetch_assoc();
    $out_of_stock_products = $row_out_of_stock['total'] ?? 0;
}

// Total Value of Current Stock
$query_total_product_value = "SELECT SUM(price * stock_quantity) as total_val FROM products";
$result_total_product_value = $conn->query($query_total_product_value);
if ($result_total_product_value && $result_total_product_value->num_rows > 0) {
    $row_total_product_value = $result_total_product_value->fetch_assoc();
    $total_product_revenue = $row_total_product_value['total_val'] ? number_format($row_total_product_value['total_val'] / 1000, 1) . 'K' : '0';
}


// --- Fetch Product List Data ---
$product_list = [];
$query_product_list = "SELECT product_id, product_name, price, stock_quantity, description FROM products ORDER BY product_name ASC";
$result_product_list = $conn->query($query_product_list);
if ($result_product_list && $result_product_list->num_rows > 0) {
    while ($row = $result_product_list->fetch_assoc()) {
        $product_list[] = $row;
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
    <title>Sales Navigator - Product Management</title>
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
            overflow-x: hidden;
            display: flex;
        }

        /* Sidebar Styling */
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

        /* Product Management Specific Styles */
        .product-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 20px;
            flex-wrap: nowrap;
        }

        .product-controls .search-bar {
            flex: 1;
            position: relative;
            min-width: 200px;
        }

        .product-controls .search-bar input {
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

        .product-controls .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--subtle-text);
            pointer-events: none;
        }

        .product-controls .status-filter {
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
        
        .action-buttons-group {
            display: flex;
            gap: 10px;
        }

        .product-controls .add-product-btn,
        .product-controls .import-product-btn {
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

        .product-controls .add-product-btn:hover,
        .product-controls .import-product-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 255, 100, 0.4);
        }

        .product-summary-cards {
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

        .product-list-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .product-card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
        }

        .product-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .product-status-badge {
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: auto;
        }
        .product-status-badge.active { background-color: rgba(0, 255, 100, 0.2); color: var(--highlight-green); }
        .product-status-badge.low-stock { background-color: rgba(255, 215, 0, 0.2); color: var(--highlight-yellow); }
        .product-status-badge.out-of-stock { background-color: rgba(255, 77, 77, 0.2); color: var(--highlight-red); }
        .product-status-badge.new { background-color: rgba(0, 191, 255, 0.2); color: var(--highlight-blue); }

        .product-details-info {
            font-size: 0.9rem;
            color: var(--text-color);
            margin-bottom: 20px;
        }

        .product-details-info .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .product-details-info .detail-row span:first-child {
            color: var(--subtle-text);
            font-size: 0.85rem;
        }

        .product-actions {
            display: flex;
            justify-content: space-around;
            gap: 10px;
            margin-top: auto;
        }

        .product-actions .btn {
            flex-grow: 1;
            padding: 10px 15px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .product-actions .btn:hover {
            transform: translateY(-2px);
        }

        .product-actions .btn.view {
            background-color: rgba(0, 191, 255, 0.2);
            color: var(--highlight-blue);
        }
        .product-actions .btn.view:hover {
            box-shadow: 0 4px 10px rgba(0, 191, 255, 0.4);
        }

        .product-actions .btn.edit {
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            color: #1a1a1a;
        }
        .product-actions .btn.edit:hover {
            box-shadow: 0 4px 10px rgba(0, 255, 100, 0.4);
        }
        
        .product-actions .btn.restock {
            background-color: rgba(255, 215, 0, 0.2);
            color: var(--highlight-yellow);
        }
        .product-actions .btn.restock:hover {
            box-shadow: 0 4px 10px rgba(255, 215, 0, 0.4);
        }
        
        .product-actions .btn.delete {
            background-color: rgba(255, 77, 77, 0.2);
            color: var(--highlight-red);
        }
        .product-actions .btn.delete:hover {
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
        .modal-content input[type="file"],
        .modal-content input[type="number"],
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
            .product-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .product-controls .search-bar {
                width: 100%;
                order: 1;
            }
            .product-controls .status-filter {
                width: 100%;
                order: 2;
            }
            .product-controls .add-product-btn {
                width: 100%;
                order: 3;
            }
            .product-summary-cards {
                grid-template-columns: 1fr;
            }
            .product-list-grid {
                grid-template-columns: 1fr;
            }
            .product-details-info {
                grid-template-columns: 1fr;
                gap: 5px;
            }
            .product-actions {
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
            .product-list-grid {
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
                <li><a href="#" class="active"><i class="fas fa-box"></i> Product Management</a></li>
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
                <span class="page-title">Product Management</span>
                <span style="font-size: 0.9rem; color: var(--subtle-text); margin-left: 10px;">Manage your inventory and product catalog</span>
            </div>
            <div class="header-right">
                <a href="#" class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                    <span class="user-role"><?php echo htmlspecialchars($user_role); ?></span>
                </a>
            </div>
        </header>

        <div class="product-controls">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search products by name, ID, or category...">
            </div>
            <select class="status-filter">
                <option value="All">All Status</option>
                <option value="Active">Active Products</option>
                <option value="Low">Low Stock</option>
                <option value="Out">Out of Stock</option>
            </select>
            <div class="action-buttons-group">
                <a href="#" class="add-product-btn" id="addProductBtn">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
                <a href="#" class="import-product-btn" id="importProductsBtn">
                    <i class="fas fa-file-upload"></i> Import CSV
                </a>
            </div>
        </div>

        <?php 
        // Display session messages from the Add Product operation
        if (isset($_SESSION['product_message'])): ?>
            <div class="alert-container <?php echo $_SESSION['product_is_error'] ? 'alert-error' : 'alert-success'; ?>">
                <?php echo htmlspecialchars($_SESSION['product_message']); ?>
            </div>
            <?php 
            unset($_SESSION['product_message']); 
            unset($_SESSION['product_is_error']);
            ?>
        <?php endif; ?>

        <div class="product-summary-cards">
            <div class="summary-card blue">
                <i class="fas fa-box icon"></i>
                <div class="value"><?php echo htmlspecialchars($total_products); ?></div>
                <div class="label">Total Products</div>
            </div>
            <div class="summary-card green">
                <i class="fas fa-check-circle icon"></i>
                <div class="value"><?php echo htmlspecialchars($active_products); ?></div>
                <div class="label">Active Products</div>
            </div>
            <div class="summary-card yellow">
                <i class="fas fa-exclamation-triangle icon"></i>
                <div class="value"><?php echo htmlspecialchars($low_stock_products); ?></div>
                <div class="label">Low Stock</div>
            </div>
            <div class="summary-card red">
                <i class="fas fa-times-circle icon"></i>
                <div class="value"><?php echo htmlspecialchars($out_of_stock_products); ?></div>
                <div class="label">Out of Stock</div>
            </div>
        </div>

        <div class="product-list-grid">
            <?php if (empty($product_list)): ?>
                <p>No products found.</p>
            <?php else: ?>
                <?php foreach ($product_list as $product): 
                    $stock_level_class = '';
                    if ($product['stock_quantity'] == 0) {
                        $stock_level_class = 'out-of-stock';
                    } elseif ($product['stock_quantity'] < 10) {
                        $stock_level_class = 'low-stock';
                    } else {
                        $stock_level_class = 'active';
                    }
                    $stock_level_text = '';
                    if ($product['stock_quantity'] == 0) {
                        $stock_level_text = 'Out of Stock';
                    } elseif ($product['stock_quantity'] < 10) {
                        $stock_level_text = 'Low Stock';
                    } else {
                        $stock_level_text = 'Active';
                    }
                ?>
                    <div class="product-card">
                        <div class="product-card-header">
                            <span class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></span>
                            <span class="product-status-badge <?php echo $stock_level_class; ?>">
                                <?php echo htmlspecialchars($stock_level_text); ?>
                            </span>
                        </div>
                        <div class="product-details-info">
                            <div class="detail-row">
                                <span>Price:</span> <span>৳<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></span>
                            </div>
                            <div class="detail-row">
                                <span>Stock:</span> <span><?php echo htmlspecialchars($product['stock_quantity']); ?> units</span>
                            </div>
                            <div class="detail-row">
                                <span>Description:</span> <span><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . (strlen($product['description']) > 50 ? '...' : ''); ?></span>
                            </div>
                        </div>
                        <div class="product-actions">
                            <a href="#" class="btn view"><i class="fas fa-eye"></i> View</a>
                            <a href="#" class="btn edit" onclick="openEditProductModal(<?php echo htmlspecialchars($product['product_id']); ?>)"><i class="fas fa-edit"></i> Edit</a>
                            <a href="product.php?action=delete&product_id=<?php echo htmlspecialchars($product['product_id']); ?>" class="btn delete" onclick="return confirm('Are you sure you want to delete this product?');">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeAddProductModal()">&times;</span>
            <h2>Add New Product</h2>
            <form id="addProductForm" method="POST" action="product.php">
                <input type="hidden" name="add_product" value="1">
                
                <div class="form-group">
                    <label for="add_product_name">Product Name:</label>
                    <input type="text" id="add_product_name" name="product_name" required>
                </div>
                
                <div class="form-group">
                    <label for="add_price">Price (৳):</label>
                    <input type="number" id="add_price" name="price" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="add_stock_quantity">Stock Quantity:</label>
                    <input type="number" id="add_stock_quantity" name="stock_quantity" min="0" required>
                </div>

                <div class="form-group">
                    <label for="add_description">Description:</label>
                    <textarea id="add_description" name="description" rows="3"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeAddProductModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Add Product</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeEditProductModal()">&times;</span>
            <h2 id="editModalTitle">Edit Product</h2>
            <form id="editProductForm" method="POST" action="product.php">
                <input type="hidden" name="update_product" value="1">
                <input type="hidden" id="edit_product_id" name="product_id">
                
                <div class="form-group">
                    <label for="edit_product_name">Product Name:</label>
                    <input type="text" id="edit_product_name" name="product_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_price">Price (৳):</label>
                    <input type="number" id="edit_price" name="price" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="edit_stock_quantity">Stock Quantity:</label>
                    <input type="number" id="edit_stock_quantity" name="stock_quantity" min="0" required>
                </div>

                <div class="form-group">
                    <label for="edit_description">Description:</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeEditProductModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Update Product</button>
                </div>
            </form>
        </div>
    </div>

    <div id="importProductsModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeImportProductsModal()">&times;</span>
            <h2>Import Products from CSV</h2>
            <p style="color: var(--subtle-text); font-size: 0.9em;">Ensure your CSV file has the following columns: **product_name, price, stock_quantity, description**.</p>
            <form id="importProductsForm" method="POST" action="product.php" enctype="multipart/form-data">
                <input type="hidden" name="import_products" value="1">
                <div class="form-group">
                    <label for="product_file">Choose CSV File:</label>
                    <input type="file" id="product_file" name="product_file" accept=".csv" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeImportProductsModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Import Products</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            const mainContent = document.getElementById('mainContent');
            const addProductBtn = document.getElementById('addProductBtn');
            const addProductModal = document.getElementById('addProductModal');
            const editProductModal = document.getElementById('editProductModal');
            const importProductsBtn = document.getElementById('importProductsBtn');
            const importProductsModal = document.getElementById('importProductsModal');
            const productList = <?php echo json_encode($product_list); ?>;
            const filterSelect = document.querySelector('.status-filter');

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
            
            // --- Add Product Modal Functions ---
            window.openAddProductModal = function() {
                addProductModal.style.display = 'flex';
            }

            window.closeAddProductModal = function() {
                addProductModal.style.display = 'none';
            }

            // Event listener for "Add New Product" button
            addProductBtn.addEventListener('click', (e) => {
                e.preventDefault();
                openAddProductModal();
            });

            // --- Edit Product Modal Functions ---
            window.openEditProductModal = function(productId) {
                const product = productList.find(p => p.product_id == productId);

                if (product) {
                    document.getElementById('editModalTitle').textContent = 'Edit Product: ' + product.product_name;
                    document.getElementById('edit_product_id').value = product.product_id;
                    document.getElementById('edit_product_name').value = product.product_name;
                    document.getElementById('edit_price').value = product.price;
                    document.getElementById('edit_stock_quantity').value = product.stock_quantity;
                    document.getElementById('edit_description').value = product.description;

                    editProductModal.style.display = 'flex';
                }
            }
            
            window.closeEditProductModal = function() {
                editProductModal.style.display = 'none';
            }

            // --- Import Products Modal Functions ---
            window.openImportProductsModal = function() {
                importProductsModal.style.display = 'flex';
            }

            window.closeImportProductsModal = function() {
                importProductsModal.style.display = 'none';
            }
            
            // Event listener for "Import CSV" button
            importProductsBtn.addEventListener('click', (e) => {
                e.preventDefault();
                openImportProductsModal();
            });
            
            // --- Status Filtering Logic ---
            filterSelect.addEventListener('change', (e) => {
                const selectedStatus = e.target.value;
                const productCards = document.querySelectorAll('.product-card');

                productCards.forEach(card => {
                    const statusBadge = card.querySelector('.product-status-badge');
                    const status = statusBadge ? statusBadge.textContent.trim() : '';

                    if (selectedStatus === 'All' || status.includes(selectedStatus)) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });

            // Handle success/error messages from session
            <?php if (isset($_SESSION['product_message'])): ?>
                const message = "<?php echo htmlspecialchars($_SESSION['product_message']); ?>";
                const isError = <?php echo isset($_SESSION['product_is_error']) && $_SESSION['product_is_error'] ? 'true' : 'false'; ?>;

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
                unset($_SESSION['product_message']); 
                unset($_SESSION['product_is_error']);
                ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>