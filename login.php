<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'config.php';

// Debug: Check if session is working
error_log("=== LOGIN PAGE LOADED ===");
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    error_log("User already logged in, redirecting to admin.php");
    header("Location: admin.php");
    exit();
}

$login_error = '';
$success_message = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    error_log("=== LOGIN FORM SUBMITTED ===");
    
    // Get and sanitize inputs
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    error_log("Username submitted: " . $username);
    error_log("Password submitted: " . (empty($password) ? '[empty]' : '[provided]'));
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $login_error = 'Please enter both username and password';
        error_log("Validation error: Empty fields");
    } else {
        // Escape username for SQL
        $username_escaped = mysqli_real_escape_string($conn, $username);
        
        // Check credentials against admin_users table
        $sql = "SELECT * FROM admin_users WHERE username = '$username_escaped' LIMIT 1";
        error_log("SQL Query: " . $sql);
        
        $result = mysqli_query($conn, $sql);
        
        if ($result === false) {
            // SQL error
            $login_error = 'Database error. Please try again later.';
            error_log("SQL Error: " . mysqli_error($conn));
        } elseif (mysqli_num_rows($result) === 0) {
            // No user found
            $login_error = 'Invalid username or password';
            error_log("No user found with username: $username");
        } else {
            // User found
            $admin = mysqli_fetch_assoc($result);
            error_log("User found in DB: " . print_r($admin, true));
            
            // Check if password field exists
            if (!isset($admin['password'])) {
                $login_error = 'Database configuration error. Contact administrator.';
                error_log("ERROR: 'password' column not found in admin_users table");
            } else {
                // Debug password verification
                error_log("Stored password hash: " . $admin['password']);
                error_log("Password to verify: " . $password);
                
                // Try password verification
                $password_verified = false;
                
                // Method 1: Check if password is hashed with password_verify()
                if (password_verify($password, $admin['password'])) {
                    $password_verified = true;
                    error_log("Password verified using password_verify()");
                } 
                // Method 2: Check if password is stored in plain text (for testing only)
                elseif ($password === $admin['password']) {
                    $password_verified = true;
                    error_log("Password matched as plain text (NOT SECURE - FOR TESTING ONLY)");
                }
                
                if ($password_verified) {
                    // Set session variables
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_id'] = $admin['id'];
                    
                    // Check if role exists, if not use default
                    if (isset($admin['role'])) {
                        $_SESSION['admin_role'] = $admin['role'];
                    } else {
                        $_SESSION['admin_role'] = 'admin'; // Default role
                    }
                    
                    // Handle "Remember me"
                    if (isset($_POST['remember_me']) && $_POST['remember_me'] == 'on') {
                        // Extend session lifetime
                        $session_duration = 60 * 60 * 24 * 7; // 7 days
                        ini_set('session.gc_maxlifetime', $session_duration);
                        session_set_cookie_params($session_duration);
                        error_log("Remember me enabled - Session extended to 7 days");
                    }
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    error_log("Login successful! Redirecting to admin.php");
                    error_log("Session data after login: " . print_r($_SESSION, true));
                    
                    // Redirect to admin page
                    header("Location: admin.php");
                    exit();
                } else {
                    $login_error = 'Invalid password. Please try again.';
                    error_log("Password verification FAILED");
                    error_log("Hash in DB: " . $admin['password']);
                    
                    // For debugging: show what hash algorithm might be used
                    $hash_info = password_get_info($admin['password']);
                    error_log("Hash info: " . print_r($hash_info, true));
                }
            }
        }
    }
}

// Handle logout from other page
if (isset($_GET['logout'])) {
    error_log("Logout requested");
    session_destroy();
    $success_message = 'You have been successfully logged out.';
    
    // Clear any existing session
    session_start();
    session_regenerate_id(true);
}

// Handle session messages from other pages
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    if ($message === 'logout_success') {
        $success_message = 'You have been successfully logged out.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Zetumarket Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary-color: #FF6D00;
            --primary-dark: #E65100;
            --primary-light: #FF9E40;
            --secondary-color: #333333;
            --light-bg: #F5F5F5;
            --white: #FFFFFF;
            --border-color: #E0E0E0;
            --text-color: #333;
            --text-light: #666;
            --success-color: #4CAF50;
            --error-color: #F44336;
            --warning-color: #FF9800;
            --info-color: #2196F3;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background: linear-gradient(135deg, #FF6D00 0%, #FF9E40 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-box {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .login-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .login-header {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            padding: 40px 30px;
            text-align: center;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
            animation: shine 3s infinite linear;
        }

        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .login-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            position: relative;
        }

        .login-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
        }

        .login-body {
            padding: 40px;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-error {
            background-color: rgba(244, 67, 54, 0.1);
            border: 1px solid var(--error-color);
            color: var(--error-color);
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }

        .alert-warning {
            background-color: rgba(255, 152, 0, 0.1);
            border: 1px solid var(--warning-color);
            color: var(--warning-color);
        }

        .alert-info {
            background-color: rgba(33, 150, 243, 0.1);
            border: 1px solid var(--info-color);
            color: var(--info-color);
        }

        .alert i {
            font-size: 1.2rem;
        }

        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #666;
        }

        .debug-info h4 {
            margin-bottom: 10px;
            color: var(--primary-color);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            transition: var(--transition);
            z-index: 1;
        }

        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: #f8f9fa;
            position: relative;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            background-color: var(--white);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 109, 0, 0.1);
        }

        .form-control:focus + i {
            color: var(--primary-color);
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
        }

        .remember-me label {
            color: var(--text-light);
            font-size: 0.95rem;
            cursor: pointer;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::after {
            width: 300px;
            height: 300px;
        }

        .btn:hover {
            background: linear-gradient(to right, var(--primary-dark), #d84300);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 109, 0, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .back-home {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid var(--border-color);
        }

        .back-home a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            padding: 10px 20px;
            border-radius: 8px;
        }

        .back-home a:hover {
            color: var(--primary-dark);
            background-color: rgba(255, 109, 0, 0.1);
            transform: translateY(-2px);
        }

        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 5px;
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
                max-width: 400px;
            }
            
            .login-body {
                padding: 30px;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
            }
            
            .login-body {
                padding: 25px;
            }
            
            .login-header h1 {
                font-size: 1.8rem;
            }
            
            .form-control {
                padding: 12px 12px 12px 40px;
            }
        }

        /* Loading animation */
        .btn.loading {
            color: transparent;
        }

        .btn.loading::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 3px solid var(--white);
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
            z-index: 1;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Floating animation */
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .login-header i {
            animation: float 3s ease-in-out infinite;
        }

        .test-credentials {
            background-color: #f8f9fa;
            border: 1px dashed var(--primary-color);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
        }

        .test-credentials h4 {
            color: var(--primary-color);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .test-credentials ul {
            padding-left: 20px;
            color: var(--text-light);
        }

        .test-credentials li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1><i class="fas fa-store"></i> Zetumarket</h1>
                <p>Admin Dashboard Login</p>
            </div>
            
            <div class="login-body">
                <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($login_error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Login Failed</strong>
                        <p style="margin-top: 5px; font-size: 0.9rem;"><?php echo htmlspecialchars($login_error); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['debug'])): ?>
                <div class="debug-info">
                    <h4>Debug Information:</h4>
                    <p>Session ID: <?php echo session_id(); ?></p>
                    <p>PHP Version: <?php echo phpversion(); ?></p>
                    <p>Server Time: <?php echo date('Y-m-d H:i:s'); ?></p>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="form-control" 
                                   placeholder="Enter your username" 
                                   required
                                   autocomplete="username"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   placeholder="Enter your password" 
                                   required
                                   autocomplete="current-password">
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="remember-me">
                        <input type="checkbox" id="remember_me" name="remember_me">
                        <label for="remember_me">Remember me</label>
                    </div>
                    
                    <button type="submit" name="login" class="btn" id="loginBtn">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Login to Dashboard</span>
                    </button>
                </form>
                
                <?php if (isset($_GET['show_test'])): ?>
                <div class="test-credentials">
                    <h4>Test Credentials:</h4>
                    <ul>
                        <li><strong>Username:</strong> admin</li>
                        <li><strong>Password:</strong> admin123</li>
                    </ul>
                    <p style="margin-top: 10px; font-size: 12px; color: #666;">
                        <i class="fas fa-info-circle"></i> Make sure the admin user exists in the database.
                    </p>
                </div>
                <?php endif; ?>
                
                <div class="back-home">
                    <a href="upload.php">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Zetumarket Home</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Login page loaded');
            
            // Password toggle functionality
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const toggleIcon = togglePassword.querySelector('i');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                toggleIcon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
                console.log('Password visibility toggled:', type);
            });
            
            // Form submission animation
            const loginForm = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            
            loginForm.addEventListener('submit', function(e) {
                console.log('Form submitted');
                loginBtn.classList.add('loading');
                loginBtn.disabled = true;
                
                // Show loading for at least 500ms
                setTimeout(() => {
                    loginBtn.classList.remove('loading');
                    loginBtn.disabled = false;
                }, 500);
            });
            
            // Input focus effects
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.parentElement.querySelector('i').style.color = '#FF6D00';
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.parentElement.querySelector('i').style.color = '#666';
                    }
                });
            });
            
            // Auto-focus on username field
            document.getElementById('username').focus();
            
            // Handle Enter key press
            document.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !loginBtn.classList.contains('loading')) {
                    console.log('Enter key pressed');
                }
            });
            
            // Add some debug info to console
            console.log('Login form initialized');
            console.log('Session cookie:', document.cookie);
        });
    </script>
</body>
</html>