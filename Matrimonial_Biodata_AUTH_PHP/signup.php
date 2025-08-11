<?php
session_start();
require_once 'database_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $error = [];
    
    // Validation
    if ($password !== $confirm_password) {
        $error[] = "Passwords do not match";
    }
    
    if (strlen($password) < 6) {
        $error[] = "Password must be at least 6 characters long";
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $error[] = "Email already exists";
    }
    
    if (empty($error)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } else {
            $error[] = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Biodata Management System</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .container {
            position: relative;
            max-width: 400px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 30px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .container header {
            font-size: 30px;
            text-align: center;
            color: #333;
            font-weight: 600;
            margin-bottom: 35px;
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .input-field {
            position: relative;
            height: 50px;
            width: 100%;
        }

        .input-field input {
            position: absolute;
            height: 100%;
            width: 100%;
            padding: 0 35px;
            border: none;
            outline: none;
            font-size: 16px;
            border-bottom: 2px solid #ccc;
            border-top: 2px solid transparent;
            transition: all 0.2s ease;
            background: transparent;
        }

        .input-field i {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
            transition: all 0.2s ease;
        }

        .input-field i.icon {
            left: 0;
        }

        .input-field .toggle-password {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
            cursor: pointer;
            z-index: 2;
            transition: color 0.2s;
        }
        .input-field .toggle-password.active {
            color: #667eea;
        }

        .input-field input:is(:focus, :valid) {
            border-bottom-color: #667eea;
        }

        .input-field input:is(:focus, :valid) ~ i.icon {
            color: #667eea;
        }

        .button {
            margin-top: 35px;
        }

        .button input {
            border: none;
            color: #fff;
            font-size: 17px;
            font-weight: 500;
            letter-spacing: 1px;
            border-radius: 6px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            padding: 15px;
        }

        .button input:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .login-signup {
            margin-top: 20px;
            text-align: center;
        }

        .login-signup a {
            color: #667eea;
            text-decoration: none;
        }

        .login-signup a:hover {
            text-decoration: underline;
        }

        .error {
            color: #ff3333;
            margin-bottom: 20px;
            text-align: center;
        }

        .error ul {
            list-style-type: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>Sign Up</header>
        <?php if(!empty($error)): ?>
            <div class="error">
                <ul>
                    <?php foreach($error as $err): ?>
                        <li><?php echo $err; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form action="" method="POST">
            <div class="form-group">
                <div class="input-field">
                    <input type="text" name="name" placeholder="Enter your name" required>
                    <i class="fas fa-user icon"></i>
                </div>
            </div>
            <div class="form-group">
                <div class="input-field">
                    <input type="email" name="email" placeholder="Enter your email" required>
                    <i class="fas fa-envelope icon"></i>
                </div>
            </div>
            <div class="form-group">
                <div class="input-field">
                    <input type="password" name="password" id="signup-password" placeholder="Create password" required>
                    <i class="fas fa-lock icon"></i>
                    <i class="fas fa-eye-slash toggle-password" id="toggle-signup-password"></i>
                </div>
            </div>
            <div class="form-group">
                <div class="input-field">
                    <input type="password" name="confirm_password" id="signup-confirm-password" placeholder="Confirm password" required>
                    <i class="fas fa-lock icon"></i>
                    <i class="fas fa-eye-slash toggle-password" id="toggle-signup-confirm-password"></i>
                </div>
            </div>
            <div class="button">
                <input type="submit" value="Sign Up">
            </div>
        </form>
        <div class="login-signup">
            Already have an account? <a href="login.php">Login Now</a>
        </div>
    </div>
    <script>
        // Password toggle for signup
        document.getElementById('toggle-signup-password').addEventListener('click', function() {
            const pwd = document.getElementById('signup-password');
            this.classList.toggle('active');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                pwd.type = 'password';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });
        document.getElementById('toggle-signup-confirm-password').addEventListener('click', function() {
            const pwd = document.getElementById('signup-confirm-password');
            this.classList.toggle('active');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                pwd.type = 'password';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });
    </script>
</body>
</html>
