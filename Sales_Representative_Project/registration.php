<?php
session_start();

// Include the database connection file
require_once 'db_connect.php';

$message = '';
$is_error = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $phoneNumber = trim($_POST['phoneNumber']);
    $role = $_POST['role'];
    
    // Check if geographical fields are set before accessing them
    $division = isset($_POST['division']) && !empty($_POST['division']) ? trim($_POST['division']) : null;
    $district = isset($_POST['district']) && !empty($_POST['district']) ? trim($_POST['district']) : null;
    $upazila = isset($_POST['upazila']) && !empty($_POST['upazila']) ? trim($_POST['upazila']) : null;
    $territory = isset($_POST['territory']) && !empty($_POST['territory']) ? trim($_POST['territory']) : null;
    
    $password = $_POST['password'];

    // Basic server-side validation
    if (empty($fullName) || empty($email) || empty($phoneNumber) || empty($role) || empty($password)) {
        $_SESSION['registration_error'] = 'All fields marked with * are required.';
        $_SESSION['is_error'] = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['registration_error'] = 'Invalid email format.';
        $_SESSION['is_error'] = true;
    } else {
        // Step 1: Check if the email already exists in the database
        $check_stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $_SESSION['registration_error'] = "This email is already registered. Please use a different one.";
            $_SESSION['is_error'] = true;
            $check_stmt->close();
        } else {
            // Step 2: Proceed with registration if email is not found
            $check_stmt->close();

            // Hash the password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            try {
                // Prepare and execute the SQL query to insert data
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone_number, role, division, district, upazila, territory, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssss", $fullName, $email, $phoneNumber, $role, $division, $district, $upazila, $territory, $hashed_password);

                if ($stmt->execute()) {
                    $_SESSION['registration_success'] = true;
                } else {
                    $_SESSION['registration_error'] = "Registration failed: " . $stmt->error;
                    $_SESSION['is_error'] = true;
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                $_SESSION['registration_error'] = "Database error: " . $e->getMessage();
                $_SESSION['is_error'] = true;
            }
        }
    }
    header('Location: registration.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --dark-bg: #0c0e0c;
            --darker-bg: #0e0f0f;
            --field-bg: #111;
            --border-color: #2c2c2c;
            --text-color: #fff;
            --subtle-text: #aaa;
            --highlight-color: #00ff66; /* Bright green for accents */
            --highlight-shadow: rgba(0, 255, 100, 0.4);
            --container-shadow: rgba(0, 255, 100, 0.15);
            --error-color: #ff4d4d; /* Red for error messages */
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--dark-bg), #141716);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text-color);
            overflow-y: auto;
        }

        .container {
            background: var(--darker-bg);
            padding: 17px 22px; /* Increased padding by 2px */
            border-radius: 15px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 0 25px var(--container-shadow);
            text-align: center;
            border: 1px solid rgba(0, 255, 100, 0.1);
            margin: 20px;
        }

        .icon {
            font-size: 47px; /* Increased font size by 2px */
            color: var(--highlight-color);
            margin-bottom: 7px; /* Increased margin by 2px */
        }

        h2 {
            margin: 0;
            font-size: 22px; /* Increased font size by 2px */
            font-weight: 600;
            color: var(--highlight-color);
        }

        p {
            font-size: 13px; /* Increased font size by 2px */
            color: var(--subtle-text);
            margin-bottom: 20px; /* Increased margin by 2px */
        }

        .form-content {
            padding: 0 12px; /* Increased padding by 2px */
        }

        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 11px; /* Increased gap by 2px */
            text-align: left;
        }
        
        .form-group {
            position: relative;
            display: flex;
            flex-direction: column;
            margin-bottom: 27px; /* Increased margin by 2px */
        }
        
        .full-width {
            grid-column: 1 / -1;
        }

        label {
            font-size: 14px; /* Increased font size by 2px */
            margin-bottom: 6px; /* Increased margin by 2px */
            color: var(--text-color);
        }

        input, select {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 12px 12px 37px; /* Increased padding by 2px */
            background: var(--field-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-color);
            font-size: 14px; /* Increased font size by 2px */
            outline: none;
            transition: border 0.2s ease, box-shadow 0.2s ease;
        }
        
        select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23aaa"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center; /* Increased position by 2px */
            background-size: 20px; /* Increased size by 2px */
            cursor: pointer;
        }

        input:focus, select:focus {
            border-color: var(--highlight-color);
            box-shadow: 0 0 6px var(--highlight-shadow);
        }

        .form-group > svg {
            position: absolute;
            left: 10px; /* Increased position by 2px */
            top: 35px; /* Increased position by 2px */
            width: 18px; /* Increased size by 2px */
            height: 18px; /* Increased size by 2px */
            fill: #777;
        }
        
        .password-eye i {
            position: absolute;
            right: 15px; /* Aligned to the right */
            top: 70%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #777;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .password-eye i:hover {
            color: var(--text-color);
        }
        
        .phone-input {
            padding-left: 75px; /* Increased padding by 2px */
        }
        
        .phone-prefix {
            position: absolute;
            left: 37px; /* Increased position by 2px */
            top: 35px; /* Increased position by 2px */
            font-size: 14px; /* Increased font size by 2px */
            color: var(--subtle-text);
        }
        
        button {
            width: 100%;
            background: linear-gradient(90deg, var(--highlight-color), #00cc55);
            border: none;
            padding: 12px; /* Increased padding by 2px */
            font-size: 18px; /* Increased font size by 2px */
            font-weight: bold;
            border-radius: 8px;
            color: #000;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 7px; /* Increased margin by 2px */
        }

        button:hover {
            background: linear-gradient(90deg, #00cc55, var(--highlight-color));
            box-shadow: 0 5px 15px var(--highlight-shadow);
        }

        .login-link {
            margin-top: 10px; /* Increased margin by 2px */
            font-size: 17px; /* Increased font size by 2px */
            color: var(--subtle-text);
            text-align: center;
            grid-column: 1 / -1;
        }

        .login-link a {
            color: var(--highlight-color);
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }

        .error-message1{
            color: var(--error-color);
            font-size: 10px; /* Reduced error message font size */
            display: none;
            position: absolute;
            bottom: -20px; /* Adjusted to ensure no overlap with new form-group margin */
            left: 0;
            width: 100%;
            text-align: left;
        }

        .error-message2{
            color: var(--error-color);
            font-size: 10px; /* Reduced error message font size */
            display: none;
            position: absolute;
            bottom: -20px; /* Adjusted to ensure no overlap with new form-group margin */
            left: 0;
            width: 100%;
            text-align: left;
        }

        .error-message3{
            color: var(--error-color);
            font-size: 10px; /* Reduced error message font size */
            display: none;
            position: absolute;
            bottom: -30px; /* Adjusted to ensure no overlap with new form-group margin */
            left: 0;
            width: 100%;
            text-align: left;
        }

        .error-message4{
            color: var(--error-color);
            font-size: 10px; /* Reduced error message font size */
            display: none;
            position: absolute;
            bottom: -20px; /* Adjusted to ensure no overlap with new form-group margin */
            left: 0;
            width: 100%;
            text-align: left;
        }

        .error-message5{
            color: var(--error-color);
            font-size: 10px; /* Reduced error message font size */
            display: none;
            position: absolute;
            bottom: -20px; /* Adjusted to ensure no overlap with new form-group margin */
            left: 0;
            width: 100%;
            text-align: left;
        }

        .error-message6{
            color: var(--error-color);
            font-size: 10px; /* Reduced error message font size */
            display: none;
            position: absolute;
            bottom: -20px; /* Adjusted to ensure no overlap with new form-group margin */
            left: 0;
            width: 100%;
            text-align: left;
        }

        .error-message7{
            color: var(--error-color);
            font-size: 10px; /* Reduced error message font size */
            display: none;
            position: absolute;
            bottom: -20px; /* Adjusted to ensure no overlap with new form-group margin */
            left: 0;
            width: 100%;
            text-align: left;
        }

        .error-message8{
            color: var(--error-color);
            font-size: 10px; /* Reduced error message font size */
            display: none;
            position: absolute;
            bottom: -20px; /* Adjusted to ensure no overlap with new form-group margin */
            left: 0;
            width: 100%;
            text-align: left;
        }

        .error-message9{
            color: var(--error-color);
            font-size: 10px; /* Reduced error message font size */
            display: none;
            position: absolute;
            bottom: -20px; /* Adjusted to ensure no overlap with new form-group margin */
            left: 0;
            width: 100%;
            text-align: left;
        }

        .error-message10{
            color: var(--error-color);
            font-size: 10px; /* Reduced error message font size */
            display: none;
            position: absolute;
            bottom: -20px; /* Adjusted to ensure no overlap with new form-group margin */
            left: 0;
            width: 100%;
            text-align: left;
        }

        
        .success-message {
            color: var(--highlight-green);
            font-size: 14px; /* Increased font size by 2px */
            margin-top: -10px;
            margin-bottom: 10px;
            display: block;
        }
        
        .input-error {
            border-color: var(--error-color) !important;
            box-shadow: 0 0 6px rgba(255, 77, 77, 0.4) !important;
        }

        .hidden-group {
            display: none !important;
        }

        /* POP-UP MODAL STYLES */
        .popup-modal {
            display: none;
            position: fixed;
            z-index: 1000;
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
            animation: fadeIn 0.3s forwards;
        }

        .popup-content {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 255, 100, 0.3);
            border: 1px solid rgba(0, 255, 100, 0.2);
            transform: scale(0.8);
            animation: scaleIn 0.3s forwards;
        }
        
        .popup-content.error {
            box-shadow: 0 10px 30px rgba(255, 77, 77, 0.3);
            border: 1px solid rgba(255, 77, 77, 0.2);
        }

        .popup-content h3 {
            color: var(--highlight-green);
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .popup-content.error h3 {
            color: var(--error-color);
        }

        .popup-content p {
            font-size: 16px;
            color: var(--text-color);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes scaleIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        /* Responsive Design */
        @media(max-width: 600px) {
            form {
                grid-template-columns: 1fr;
            }
            .container {
                width: calc(100% - 40px);
                padding: 17px;
            }
            .form-content {
                padding: 0;
            }
            .form-group > svg, .password-eye i, .phone-prefix {
                top: 37px; /* Increased by 2px */
            }
            input, select {
                padding: 12px 12px 12px 37px;
            }
            .phone-input {
                padding-left: 67px;
            }
            .form-group {
                margin-bottom: 32px; /* Increased by 2px */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🛡️</div> <h2>Create Account</h2>
        <p>Join Bangladesh's leading sales management platform</p>
        
        <div class="form-content">
            <form id="registerForm" action="registration.php" method="POST">
                <div class="form-group">
                    <label for="fullName">Full Name *</label>
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/></svg>
                    <input type="text" id="fullName" name="fullName" placeholder="Enter your full name">
                    <span class="error-message1" id="fullNameError"></span>
                </div>
    
                <div class="form-group">
                    <label for="email">Email *</label>
                    <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    <input type="email" id="email" name="email" placeholder="Enter your email">
                    <span class="error-message2" id="emailError"></span>
                </div>
    
                <div class="form-group">
                    <label for="phoneNumber">Phone Number *</label>
                    <svg viewBox="0 0 24 24"><path d="M6.6 10.8c1.1 2.3 3.1 4.3 5.4 5.4l1.8-1.8c.2-.2.5-.3.8-.2 1 .3 2 .5 3 .5.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C9.9 21 3 14.1 3 6c0-.6.4-1 1-1h3.3c.6 0 1 .4 1 1 0 1 .2 2 .5 3 .1.3 0 .6-.2.8l-1.9 1.8z"/></svg>
                    <span class="phone-prefix">+880</span>
                    <input type="text" id="phoneNumber" name="phoneNumber" class="phone-input" placeholder="1XXXXXXXXX">
                    <span class="error-message3" id="phoneNumberError"></span>
                </div>
    
                <div class="form-group">
                    <label for="role">Role *</label>
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/></svg>
                    <select id="role" name="role">
                        <option value="">Select your role</option>
                        <option value="HOM">Head of Marketing (HOM)</option>
                        <option value="NSM">National Sales Manager (NSM)</option>
                        <option value="DSM">Divisional Sales Manager (DSM)</option>
                        <option value="ASM">Area Sales Manager (ASM)</option>
                        <option value="TSM">Territory Sales Manager (TSM)</option>
                        <option value="SR">Sales Representative (SR)</option>
                    </select>
                    <span class="error-message4" id="roleError"></span>
                </div>
    
                <div class="form-group hidden-group" id="divisionGroup">
                    <label for="division">Division *</label>
                    <svg viewBox="0 0 24 24"><path d="M12 2C8.1 2 5 5.1 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5S10.6 6.5 12 6.5s2.5 1.1 2.5 2.5S13.4 11.5 12 11.5z"/></svg>
                    <select id="division" name="division">
                        <option value="">Select division</option>
                    </select>
                    <span class="error-message5" id="divisionError"></span>
                </div>

                <div class="form-group hidden-group" id="districtGroup">
                    <label for="district">District *</label>
                    <svg viewBox="0 0 24 24"><path d="M12 2C8.1 2 5 5.1 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5S10.6 6.5 12 6.5s2.5 1.1 2.5 2.5S13.4 11.5 12 11.5z"/></svg>
                    <select id="district" name="district">
                        <option value="">Select district</option>
                    </select>
                    <span class="error-message6" id="districtError"></span>
                </div>

                <div class="form-group hidden-group" id="upazilaGroup">
                    <label for="upazila">Upazila *</label>
                    <svg viewBox="0 0 24 24"><path d="M12 2C8.1 2 5 5.1 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5S10.6 6.5 12 6.5s2.5 1.1 2.5 2.5S13.4 11.5 12 11.5z"/></svg>
                    <select id="upazila" name="upazila">
                        <option value="">Select upazila</option>
                    </select>
                    <span class="error-message7" id="upazilaError"></span>
                </div>

                <div class="form-group hidden-group" id="territoryGroup">
                    <label for="territory">Territory *</label>
                    <svg viewBox="0 0 24 24"><path d="M12 2C8.1 2 5 5.1 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5S10.6 6.5 12 6.5s2.5 1.1 2.5 2.5S13.4 11.5 12 11.5z"/></svg>
                    <select id="territory" name="territory">
                        <option value="">Select territory</option>
                    </select>
                    <span class="error-message8" id="territoryError"></span>
                </div>
    
                <div class="form-group password-eye full-width">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" placeholder="Create a strong password">
                    <i class="fa-solid fa-eye-slash" onclick="togglePassword('password')"></i>
                    <span class="error-message9" id="passwordError"></span>
                </div>
    
                <div class="form-group password-eye full-width">
                    <label for="confirmPassword">Confirm Password *</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password">
                    <i class="fa-solid fa-eye-slash" onclick="togglePassword('confirmPassword')"></i>
                    <span class="error-message10" id="confirmPasswordError"></span>
                </div>
    
                <div class="full-width">
                    <button type="submit">Create Account</button>
                </div>
    
                <div class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
        </div>
    </div>
    
    <div id="successPopup" class="popup-modal">
        <div class="popup-content">
            <h3>Success!</h3>
            <p>Your account has been created successfully. Redirecting to login...</p>
        </div>
    </div>
    
    <div id="errorPopup" class="popup-modal">
        <div class="popup-content error">
            <h3>Error!</h3>
            <p id="errorPopupMessage"></p>
        </div>
    </div>
    
    <script>
        // Simulated geographical data for demonstration
        const bdLocations = {
            "Dhaka": {
                "Dhaka District": {
                    "Dhamrai": ["Dhamrai East Territory", "Dhamrai West Territory"],
                    "Savar": ["Savar North Territory", "Savar South Territory"],
                    "Ashulia": ["Ashulia Ind. Zone", "Ashulia EPZ"]
                },
                "Gazipur District": {
                    "Gazipur Sadar": ["Gazipur Sadar North", "Gazipur Sadar South"]
                }
            },
            "Chittagong": {
                "Chattogram District": {
                    "Anwara": ["Anwara Coast Territory", "Anwara Inland Territory"],
                    "Hathazari": ["Hathazari North Territory", "Hathazari South Territory"]
                }
            },
            "Rajshahi": {
                "Rajshahi District": {
                    "Godagari": ["Godagari East", "Godagari West"]
                    }
                },
                "Khulna": {
                    "Khulna District": {
                        "Dumuria": ["Dumuria North", "Dumuria South"]
                    }
                },
                "Barishal": {
                    "Barishal District": {
                        "Bakerganj": ["Bakerganj East", "Bakerganj West"]
                    }
                },
                "Sylhet": {
                    "Sylhet District": {
                        "Balaganj": ["Balaganj North", "Balaganj South"]
                    }
                },
                "Rangpur": {
                    "Rangpur District": {
                        "Gangachara": ["Gangachara East", "Gangachara West"]
                    }
                },
                "Mymensingh": {
                    "Mymensingh District": {
                        "Bhaluka": ["Bhaluka North", "Bhaluka South"]
                    }
                }
            };

            function togglePassword(id) {
                const input = document.getElementById(id);
                const icon = input.nextElementSibling;
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.classList.toggle('fa-eye', isPassword);
                icon.classList.toggle('fa-eye-slash', !isPassword);
            }

            document.addEventListener('DOMContentLoaded', () => {
                const registerForm = document.getElementById('registerForm');
                const fullNameInput = document.getElementById('fullName');
                const emailInput = document.getElementById('email');
                const phoneNumberInput = document.getElementById('phoneNumber');
                const roleSelect = document.getElementById('role');
                const divisionSelect = document.getElementById('division');
                const districtSelect = document.getElementById('district');
                const upazilaSelect = document.getElementById('upazila');
                const territorySelect = document.getElementById('territory');
                const passwordInput = document.getElementById('password');
                const confirmPasswordInput = document.getElementById('confirmPassword');
                
                const divisionGroup = document.getElementById('divisionGroup');
                const districtGroup = document.getElementById('districtGroup');
                const upazilaGroup = document.getElementById('upazilaGroup');
                const territoryGroup = document.getElementById('territoryGroup');
                
                const showError = (element, message) => {
                    const errorSpan = document.getElementById(`${element.id}Error`);
                    if (errorSpan) {
                        errorSpan.textContent = message;
                        errorSpan.style.display = 'block';
                        element.classList.add('input-error');
                    }
                };
                
                const hideError = (element) => {
                    const errorSpan = document.getElementById(`${element.id}Error`);
                    if (errorSpan) {
                        errorSpan.textContent = '';
                        errorSpan.style.display = 'none';
                        element.classList.remove('input-error');
                    }
                };

                const validateField = (element, validationFn, errorMessage) => {
                    const value = element.value.trim();
                    if (!validationFn(value)) {
                        showError(element, errorMessage);
                        return false;
                    }
                    hideError(element);
                    return true;
                };

                const isValidFullName = (name) => /^[A-Za-z\s.'-]{1,50}$/.test(name.trim());
                const isValidEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                const isValidPhoneNumber = (phone) => /^1\d{9}$/.test(phone);
                const isValidRole = (role) => role !== '';
                const isValidDivision = (division) => division !== '';
                const isValidDistrict = (district) => district !== '';
                const isValidUpazila = (upazila) => upazila !== '';
                const isValidTerritory = (territory) => territory !== '';
                const isValidPassword = (password) => /^(?=.*\d)(?=.*[!@#$%^&*()_+={}\[\]|\\:;"'<>,.?/~`])[A-Za-z\d!@#$%^&*()_+={}\[\]|\\:;"'<>,.?/~`]{8,16}$/.test(password);

                const clearAndHideDropdown = (selectElement, groupElement) => {
                    selectElement.innerHTML = `<option value="">Select ${selectElement.id}</option>`;
                    groupElement.classList.add('hidden-group');
                    hideError(selectElement);
                };

                const populateDropdown = (selectElement, data, placeholderText) => {
                    selectElement.innerHTML = `<option value="">Select ${placeholderText}</option>`;
                    if (data) {
                        const sortedKeys = Array.isArray(data) ? data.sort() : Object.keys(data).sort();
                        sortedKeys.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item;
                            option.textContent = item;
                            selectElement.appendChild(option);
                        });
                    }
                    selectElement.parentElement.classList.remove('hidden-group');
                };

                // Add real-time validation event listeners for each input/select
                fullNameInput.addEventListener('input', () => {
                    validateField(fullNameInput, isValidFullName, 'Full name is required.');
                });
                
                emailInput.addEventListener('input', () => {
                    validateField(emailInput, isValidEmail, 'Please enter a valid email address.');
                });

                phoneNumberInput.addEventListener('input', () => {
                    validateField(phoneNumberInput, isValidPhoneNumber, 'Please enter a valid 10-digit phone number (after +880).');
                });

                roleSelect.addEventListener('change', () => {
                    const selectedRole = roleSelect.value;
                    clearAndHideDropdown(divisionSelect, divisionGroup);
                    clearAndHideDropdown(districtSelect, districtGroup);
                    clearAndHideDropdown(upazilaSelect, upazilaGroup);
                    clearAndHideDropdown(territorySelect, territoryGroup);
                    validateField(roleSelect, isValidRole, 'Please select a role.');
                    if (selectedRole === 'DSM' || selectedRole === 'ASM' || selectedRole === 'TSM' || selectedRole === 'SR') {
                        populateDropdown(divisionSelect, bdLocations, 'division');
                    }
                });

                divisionSelect.addEventListener('change', () => {
                    const selectedRole = roleSelect.value;
                    const selectedDivision = divisionSelect.value;
                    clearAndHideDropdown(districtSelect, districtGroup);
                    clearAndHideDropdown(upazilaSelect, upazilaGroup);
                    clearAndHideDropdown(territorySelect, territoryGroup);
                    validateField(divisionSelect, isValidDivision, 'Please select a division.');
                    if (selectedDivision && ['ASM', 'TSM', 'SR'].includes(selectedRole)) {
                        populateDropdown(districtSelect, bdLocations[selectedDivision], 'district');
                    }
                });

                districtSelect.addEventListener('change', () => {
                    const selectedRole = roleSelect.value;
                    const selectedDivision = divisionSelect.value;
                    const selectedDistrict = districtSelect.value;
                    clearAndHideDropdown(upazilaSelect, upazilaGroup);
                    clearAndHideDropdown(territorySelect, territoryGroup);
                    validateField(districtSelect, isValidDistrict, 'Please select a district.');
                    if (selectedDivision && selectedDistrict && ['TSM', 'SR'].includes(selectedRole)) {
                        populateDropdown(upazilaSelect, bdLocations[selectedDivision][selectedDistrict], 'upazila');
                    }
                });

                upazilaSelect.addEventListener('change', () => {
                    const selectedRole = roleSelect.value;
                    const selectedDivision = divisionSelect.value;
                    const selectedDistrict = districtSelect.value;
                    const selectedUpazila = upazilaSelect.value;
                    clearAndHideDropdown(territorySelect, territoryGroup);
                    validateField(upazilaSelect, isValidUpazila, 'Please select an upazila.');
                    if (selectedDivision && selectedDistrict && selectedUpazila && selectedRole === 'SR') {
                        populateDropdown(territorySelect, bdLocations[selectedDivision][selectedDistrict][selectedUpazila], 'territory');
                    }
                });
                
                territorySelect.addEventListener('change', () => {
                    validateField(territorySelect, isValidTerritory, 'Please select a territory.');
                });

                passwordInput.addEventListener('input', () => {
                    validateField(passwordInput, isValidPassword, 'Password must be at least 8-16 characters, include a number and a special character.');
                });

                confirmPasswordInput.addEventListener('input', () => {
                    if (confirmPasswordInput.value !== passwordInput.value || confirmPasswordInput.value === '') {
                        showError(confirmPasswordInput, 'Passwords do not match.');
                    } else {
                        hideError(confirmPasswordInput);
                    }
                });

                // Event listener for form submission
                registerForm.addEventListener('submit', (event) => {
                    // Prevent default form submission to handle validation first
                    event.preventDefault();
                    
                    let formIsValid = true;

                    // Trigger validation for all fields on submit
                    formIsValid = validateField(fullNameInput, isValidFullName, 'Full name is required.') && formIsValid;
                    formIsValid = validateField(emailInput, isValidEmail, 'Please enter a valid email address.') && formIsValid;
                    formIsValid = validateField(phoneNumberInput, isValidPhoneNumber, 'Please enter a valid 10-digit phone number (after +880).') && formIsValid;
                    formIsValid = validateField(roleSelect, isValidRole, 'Please select a role.') && formIsValid;
                    
                    // Conditionally validate geographical fields
                    const selectedRole = roleSelect.value;
                    if (['DSM', 'ASM', 'TSM', 'SR'].includes(selectedRole)) {
                        formIsValid = validateField(divisionSelect, isValidDivision, 'Please select a division.') && formIsValid;
                        if (['ASM', 'TSM', 'SR'].includes(selectedRole)) {
                            formIsValid = validateField(districtSelect, isValidDistrict, 'Please select a district.') && formIsValid;
                            if (['TSM', 'SR'].includes(selectedRole)) {
                                formIsValid = validateField(upazilaSelect, isValidUpazila, 'Please select an upazila.') && formIsValid;
                                if (selectedRole === 'SR') {
                                    formIsValid = validateField(territorySelect, isValidTerritory, 'Please select a territory.') && formIsValid;
                                }
                            }
                        }
                    }

                    formIsValid = validateField(passwordInput, isValidPassword, 'Password must be at least 8-16 characters, include a number and a special character.') && formIsValid;
                    
                    // Confirm password validation
                    if (confirmPasswordInput.value !== passwordInput.value || confirmPasswordInput.value === '') {
                        showError(confirmPasswordInput, 'Passwords do not match.');
                        formIsValid = false;
                    } else {
                        hideError(confirmPasswordInput);
                    }
                    
                    // If all validations pass, submit the form to the server
                    if (formIsValid) {
                        registerForm.submit();
                    } else {
                        console.log('Form has validation errors.');
                    }
                });
                
                // Check for success or error popups using session variables
                <?php if (isset($_SESSION['registration_success'])): ?>
                    function showSuccessPopup() {
                        const popup = document.getElementById('successPopup');
                        popup.style.display = 'flex';
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 3000); // Redirect to login page after 3 seconds
                    }
                    showSuccessPopup();
                    <?php unset($_SESSION['registration_success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['registration_error'])): ?>
                    function showErrorPopup() {
                        const popup = document.getElementById('errorPopup');
                        const messageElement = document.getElementById('errorPopupMessage');
                        messageElement.textContent = "<?php echo $_SESSION['registration_error']; ?>";
                        popup.style.display = 'flex';
                        setTimeout(() => {
                            popup.style.display = 'none';
                        }, 5000); // Hide after 5 seconds
                    }
                    showErrorPopup();
                    <?php unset($_SESSION['registration_error']); ?>
                <?php endif; ?>
            });
    </script>
</body>
</html>