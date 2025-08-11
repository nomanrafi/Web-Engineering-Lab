<?php
session_start();
require_once 'database_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Fetch all biodata entries for the dashboard table
$sql = "SELECT id, photo_path, name FROM biodata";
$result_biodata = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Biodata Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #f4f7fe;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem 2rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar h1 {
            font-size: 1.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            padding: 0.5rem 1rem;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .section-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }

        .section-card:hover {
            transform: translateY(-5px);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .section-header i {
            font-size: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-title {
            font-size: 1.5rem;
            color: #333;
        }

        .button-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .action-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.8rem;
            border: none;
            border-radius: 8px;
            background: #f4f7fe;
            color: #333;
            cursor: pointer;
            transition: 0.3s;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .action-button:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .action-button i {
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .sections {
                grid-template-columns: 1fr;
            }
        }

        .welcome-message {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-text h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .welcome-text p {
            opacity: 0.9;
        }

        .welcome-icon i {
            font-size: 4rem;
            opacity: 0.2;
        }
        
        .section-header-text {
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        
        .section-header-text h3 {
            color: #667eea;
            text-align: center; /* Center the heading text */
        }

        .biodata-list-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
            /* Reverting width to fill the container and using flexbox to center content */
            width: 100%;
            margin: 2rem auto;
            display: flex; /* Use flexbox for centering */
            justify-content: center; /* Center the table horizontally */
        }
        
        .biodata-list-container .biodata-table {
            width: 80%; /* Set a specific width for the table */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .biodata-list-container th, 
        .biodata-list-container td {
            text-align: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .biodata-list-container th {
            background: #f8f9fa;
            color: #667eea;
            font-weight: 600;
        }
        
        .biodata-list-container tr:hover {
            background: #f8f9fa;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem;
            font-size: 0.875rem;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        .btn-primary{
            background: #16d947;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .empty-message {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>Biodata Management System</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($user_name); ?></span>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-message">
            <div class="welcome-text">
                <h2>Welcome Back!</h2>
                <p>Manage your users and biodata efficiently</p>
            </div>
            <div class="welcome-icon">
                <i class="fas fa-user-circle"></i>
            </div>
        </div>

        <div class="sections">
            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-users"></i>
                    <h2 class="section-title">Users Section</h2>
                </div>
                <div class="button-grid">
                    <a href="view_user.php" class="action-button">
                        <i class="fas fa-eye"></i>
                        View Users
                    </a>
                    <a href="add_user.php" class="action-button">
                        <i class="fas fa-user-plus"></i>
                        Add User
                    </a>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-address-card"></i>
                    <h2 class="section-title">Biodata Section</h2>
                </div>
                <div class="button-grid">
                    <a href="add_biodata.php" class="action-button">
                        <i class="fas fa-plus-circle"></i>
                        Add Biodata
                    </a>
                </div>
            </div>
        </div>
        
        <div class="section-header-text">
            <h3>All Biodata Entries</h3>
        </div>

        <div class="biodata-list-container">
            <?php if ($result_biodata->num_rows > 0): ?>
                <table class="biodata-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result_biodata->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td>
                                    <?php if (!empty($row['photo_path']) && file_exists($row['photo_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($row['photo_path']); ?>" alt="Photo" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle fa-2x"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view_biodata.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_biodata.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="generate_pdf.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <a href="delete_biodata.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this biodata?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-message">
                    <i class="fas fa-address-card fa-3x"></i>
                    <p>No biodata entries found. Add one to get started.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>