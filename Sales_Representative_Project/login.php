<?php
session_start();

// Include the database connection file
require_once 'db_connect.php';

$message = '';
$is_error = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = trim($_POST['userId']);
    $password = $_POST['password'];

    // Basic server-side validation
    if (empty($userId) || empty($password)) {
        $message = 'Please enter both User ID/Email and Password.';
        $is_error = true;
    } else {
        // Determine if the user input is an email or a User ID
        $is_email = filter_var($userId, FILTER_VALIDATE_EMAIL);
        $login_field = $is_email ? 'email' : 'user_id';

        // Prepare the SQL query
        $stmt = $conn->prepare("SELECT user_id, full_name, password FROM users WHERE $login_field = ?");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, start a session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                
                header('Location: dashboard.php');
                exit();
            } else {
                $message = 'Incorrect password.';
                $is_error = true;
            }
        } else {
            $message = 'User not found.';
            $is_error = true;
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Navigator - Login</title>
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
            overflow-y: auto; /* Allow scrolling for smaller screens */
        }

        .container {
            background: var(--darker-bg);
            padding: 25px; /* Consistent padding with Register page */
            border-radius: 15px;
            width: 100%;
            max-width: 420px; /* Consistent max-width with Register page */
            box-shadow: 0 0 25px var(--container-shadow);
            text-align: center;
            border: 1px solid rgba(0, 255, 100, 0.1); /* Subtle green border */
            margin: 20px; /* Add margin for mobile responsiveness */
        }

        .icon {
            font-size: 50px; /* Consistent icon size */
            color: var(--highlight-color);
            margin-bottom: 10px; /* Consistent margin */
        }

        h2 {
            margin: 0;
            font-size: 24px; /* Slightly larger for login page main title */
            font-weight: 600;
            color: var(--highlight-color);
        }

        p {
            font-size: 13px;
            color: var(--subtle-text);
            margin-bottom: 25px; /* Consistent margin */
        }

        .form-content {
            padding: 0 20px; /* Consistent padding */
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 5px; /* Gap between form groups */
            text-align: left;
        }
        
        .form-group {
            position: relative;
            display: flex;
            flex-direction: column;
            /* Adjusted margin-bottom to ensure space for error messages */
            margin-bottom: 40px; /* Increased to provide ample space for error messages */
        }
        
        label {
            font-size: 13px;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        input { /* Removed select from here as it's no longer present */
            width: 100%;
            box-sizing: border-box;
            padding: 12px 12px 12px 40px; /* Padding for icon */
            background: var(--field-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-color);
            font-size: 13px;
            outline: none;
            transition: border 0.2s ease, box-shadow 0.2s ease;
        }
        
        input:focus { /* Removed select:focus from here */
            border-color: var(--highlight-color);
            box-shadow: 0 0 6px var(--highlight-shadow);
        }

        .form-group > svg {
            position: absolute;
            left: 12px;
            top: 36px; /* Adjusted to center vertically with input padding */
            width: 18px;
            height: 18px;
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
        
        button {
            width: 100%;
            background: linear-gradient(90deg, var(--highlight-color), #00cc55);
            border: none;
            padding: 12px;
            font-size: 15px;
            font-weight: bold;
            border-radius: 8px;
            color: #000;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        button:hover {
            background: linear-gradient(90deg, #00cc55, var(--highlight-color));
            box-shadow: 0 5px 15px var(--highlight-shadow);
        }

        .links-group {
            margin-top: 15px;
            font-size: 16px;
            color: var(--subtle-text);
            text-align: center;
        }

        .links-group a {
            color: var(--highlight-color);
            text-decoration: none;
            margin: 0 5px;
        }
        .links-group a:hover {
            text-decoration: underline;
        }

        .error-message1 {
            color: var(--error-color);
            font-size: 11px;
            display: none;
            position: absolute;
            bottom: -20px; /* Adjusted to ensure no overlap */
            left: 0;
            width: 100%;
            text-align: left;
        }
        .error-message2 {
            color: var(--error-color);
            font-size: 11px;
            display: none;
            position: absolute;
            bottom: -35px; /* Adjusted to ensure no overlap */
            left: 0;
            width: 100%;
            text-align: left;
        }

        .success-message {
            color: var(--highlight-green);
            font-size: 11px;
            display: none;
            position: absolute;
            bottom: -20px; /* Adjusted to ensure no overlap */
            left: 0;
            width: 100%;
            text-align: left;
        }

        .input-error {
            border-color: var(--error-color) !important;
            box-shadow: 0 0 6px rgba(255, 77, 77, 0.4) !important;
        }

        .alert-container {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 8px;
            font-size: 12px;
        }

        .alert-error {
            background-color: rgba(255, 77, 77, 0.2);
            color: var(--error-color);
            border: 1px solid var(--error-color);
            display: block;
        }

        @media(max-width: 600px) {
            .container {
                width: calc(100% - 40px);
                padding: 20px;
            }
            .form-content {
                padding: 0;
            }
            .form-group > svg, .password-eye i {
                top: 38px;
            }
            input { /* Removed select from here */
                padding: 10px 10px 10px 35px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📊</div>
        <h2>Sales Navigator</h2>
        <p>বাংলাদেশের #১ সেলস ম্যানেজমেন্ট সিস্টেম</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert-container alert-<?php echo $is_error ? 'error' : 'success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-content">
            <form id="loginForm" action="login.php" method="POST">
                <div class="form-group">
                    <label for="userId">Email</label>
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/></svg>
                    <input type="text" id="userId" name="userId" placeholder="Enter your Email">
                    <span class="error-message1" id="userIdError">Please enter a valid Email.</span>
                </div>
    
                <div class="form-group password-eye">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password">
                    <i class="fa-solid fa-eye-slash" onclick="togglePassword('password')"></i>
                    <span class="error-message2" id="passwordError">Password is required.</span>
                </div>
    
                <button type="submit">Login to Dashboard</button>
            </form>

            <div class="links-group">
                Don't have an account? <a href="registration.php">Register here</a>
                <br>
                Forgot password? Contact your Territory Manager
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye', isPassword);
            icon.classList.toggle('fa-eye-slash', !isPassword);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const loginForm = document.getElementById('loginForm');
            const userIdInput = document.getElementById('userId');
            const passwordInput = document.getElementById('password');
            
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

            // FIX: Add more robust validation functions for email and password
            const isValidEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            const isValidPassword = (password) => /^(?=.*\d)(?=.*[!@#$%^&*()_+={}\[\]|\\:;"'<>,.?/~`])[A-Za-z\d!@#$%^&*()_+={}\[\]|\\:;"'<>,.?/~`]{8,16}$/.test(password);

            // FIX: Update event listeners to use the new validation functions
            userIdInput.addEventListener('input', () => {
                validateField(userIdInput, isValidEmail, 'Please enter a valid Email address.');
            });
            
            passwordInput.addEventListener('input', () => {
                validateField(passwordInput, isValidPassword, 'Password must be between 8 and 16 characters and include a special character and a number.');
            });

            loginForm.addEventListener('submit', (event) => {
                event.preventDefault();
                
                let formIsValid = true;

                // FIX: Use new validation functions on form submission
                formIsValid = validateField(userIdInput, isValidEmail, 'Please enter a valid Email address.') && formIsValid;
                formIsValid = validateField(passwordInput, isValidPassword, 'Password must be between 8 and 16 characters and include a special character and a number.') && formIsValid;

                if (formIsValid) {
                    loginForm.submit();
                } else {
                    console.log('Form has validation errors.');
                }
            });

            // Handle the case where the server returns an error.
            // The PHP block above the form sets the `$message` variable on a failed login attempt.
            // This script checks for that message and displays it.
            <?php if (!empty($message)): ?>
                const loginErrorContainer = document.querySelector('.alert-container');
                if (loginErrorContainer) {
                    // Force display the alert container
                    loginErrorContainer.style.display = 'block';
                }
            <?php endif; ?>

        });
    </script>
</body>
</html>