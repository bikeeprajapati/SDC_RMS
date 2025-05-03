<?php
session_start();
require_once '../config/database.php';

// Clear any existing session
session_unset();
session_destroy();
session_start();

// Check if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$remembered_username = '';

// Check for remembered username
if (isset($_COOKIE['remembered_username'])) {
    $remembered_username = $_COOKIE['remembered_username'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        try {
            // Debug information
            error_log("Login attempt - Username: " . $username);
            
            $sql = "SELECT * FROM admin WHERE username = ? AND status = 'active'";
            $stmt = $conn->prepare($sql);
            
            if ($stmt === false) {
                error_log("Error preparing statement: " . $conn->error);
                throw new Exception("Error preparing statement: " . $conn->error);
            }
            
            $stmt->bind_param("s", $username);
            
            if (!$stmt->execute()) {
                error_log("Error executing statement: " . $stmt->error);
                throw new Exception("Error executing statement: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            error_log("Number of rows found: " . $result->num_rows);

            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                error_log("Found admin user: " . json_encode($admin));
                
                // Debug password verification
                error_log("Attempting to verify password...");
                error_log("Stored hash: " . $admin['password']);
                
                if (password_verify($password, $admin['password'])) {
                    error_log("Password verification successful");
                    
                    // Update last login
                    $update_sql = "UPDATE admin SET last_login = NOW() WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("i", $admin['id']);
                    $update_stmt->execute();
                    
                    // Set remember me cookie if checked
                    if ($remember) {
                        setcookie('remembered_username', $username, time() + (86400 * 30), "/"); // 30 days
                    } else {
                        setcookie('remembered_username', '', time() - 3600, "/"); // Delete cookie
                    }

                    // Set session variables
                    $_SESSION['user_id'] = $admin['id'];
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['user_type'] = 'admin';
                    $_SESSION['last_activity'] = time();

                    header("Location: dashboard.php");
                    exit();
                } else {
                    error_log("Password verification failed");
                    error_log("Input password: " . $password);
                    $error = "Invalid password";
                }
            } else {
                error_log("No admin user found with username: " . $username);
                $error = "Invalid username";
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - SDC RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translateY(0);
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease-out;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(45deg, #1a2a6c, #b21f1f);
            border-radius: 3px;
        }

        .login-header i {
            font-size: 3.5rem;
            background: linear-gradient(45deg, #1a2a6c, #b21f1f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #1a2a6c;
            box-shadow: 0 0 0 0.25rem rgba(26, 42, 108, 0.15);
            animation: pulse 0.3s ease-in-out;
        }

        .input-group-text {
            border-radius: 10px 0 0 10px;
            border: 2px solid #e9ecef;
            border-right: none;
            background: #f8f9fa;
        }

        .input-group .form-control {
            border-radius: 0 10px 10px 0;
        }

        .btn-login {
            background: linear-gradient(45deg, #1a2a6c, #b21f1f);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #b21f1f, #1a2a6c);
            transition: all 0.3s ease;
        }

        .btn-login:hover::before {
            left: 0;
        }

        .btn-login span {
            position: relative;
            z-index: 1;
        }

        .back-to-home {
            color: #6c757d;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .back-to-home::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(45deg, #1a2a6c, #b21f1f);
            transition: all 0.3s ease;
        }

        .back-to-home:hover {
            color: #1a2a6c;
        }

        .back-to-home:hover::after {
            width: 100%;
        }

        .alert {
            border-radius: 10px;
            border: none;
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            padding: 1rem;
            margin-bottom: 1.5rem;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .floating-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            100% { transform: translateY(-100vh) rotate(360deg); }
        }

        /* Remember Me Checkbox Styles */
        .form-check {
            margin: 0;
            padding: 0;
            position: relative;
            display: flex;
            align-items: center;
            height: 24px;
        }

        .form-check-input {
            position: relative;
            margin: 0;
            margin-right: 8px;
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid #1a2a6c;
            border-radius: 0.25rem;
            transition: all 0.3s ease;
            cursor: pointer;
            flex-shrink: 0;
        }

        .form-check-input:checked {
            background-color: #1a2a6c;
            border-color: #1a2a6c;
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(26, 42, 108, 0.15);
        }

        .form-check-label {
            color: #6c757d;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0;
            padding: 0;
            line-height: 1.25rem;
            font-size: 0.9rem;
        }

        .form-check-label:hover {
            color: #1a2a6c;
        }

        /* Password Toggle Button */
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .password-toggle:hover {
            color: #1a2a6c;
        }

        .password-field {
            position: relative;
        }

        /* Loading Animation */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #1a2a6c;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Enhanced Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        /* Success Animation */
        .success-checkmark {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
        }

        .success-checkmark .checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: block;
            stroke-width: 2;
            stroke: #1a2a6c;
            stroke-miterlimit: 10;
            box-shadow: inset 0px 0px 0px #1a2a6c;
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }

        .success-checkmark .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: #1a2a6c;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .success-checkmark .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }

        @keyframes stroke {
            100% { stroke-dashoffset: 0; }
        }

        @keyframes scale {
            0%, 100% { transform: none; }
            50% { transform: scale3d(1.1, 1.1, 1); }
        }

        @keyframes fill {
            100% { box-shadow: inset 0px 0px 0px 30px #1a2a6c; }
        }

        /* Forgot Password Link */
        .forgot-password {
            color: #6c757d;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            line-height: 1.25rem;
            display: inline-flex;
            align-items: center;
            height: 24px;
        }

        .forgot-password i {
            font-size: 0.9rem;
        }

        .forgot-password:hover {
            color: #1a2a6c;
        }
    </style>
</head>
<body>
    <div class="floating-particles" id="particles"></div>
    <div class="login-container">
        <div class="login-card p-4">
            <div class="login-header">
                <i class="fas fa-user-shield"></i>
                <h2 class="h4 mb-0">Admin Login</h2>
                <p class="text-muted">Enter your credentials to access the dashboard</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="mb-4">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($remembered_username); ?>" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group password-field">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-6 d-flex align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember" <?php echo $remembered_username ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                    </div>
                    <div class="col-6 d-flex align-items-center justify-content-end">
                        <a href="forgot_password.php" class="forgot-password">
                            <i class="fas fa-key me-2"></i>Forgot Password?
                        </a>
                    </div>
                </div>

                <div class="d-grid mb-4">
                    <button type="submit" class="btn btn-primary btn-login">
                        <span><i class="fas fa-sign-in-alt me-2"></i>Login</span>
                    </button>
                </div>

                <div class="text-center">
                    <a href="../index.php" class="back-to-home">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Animation -->
    <div class="loading" id="loading">
        <div class="loading-spinner"></div>
    </div>

    <!-- Success Checkmark -->
    <div class="success-checkmark" id="successCheckmark">
        <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
            <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
            <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
        </svg>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password Toggle Function
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.classList.remove('fa-eye');
                toggleButton.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleButton.classList.remove('fa-eye-slash');
                toggleButton.classList.add('fa-eye');
            }
        }

        // Form Submission Animation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loading = document.getElementById('loading');
            loading.style.display = 'flex';
        });

        // Create floating particles
        function createParticles() {
            const container = document.getElementById('particles');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                const size = Math.random() * 15 + 5;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                particle.style.animationDuration = `${Math.random() * 10 + 10}s`;
                particle.style.animationDelay = `${Math.random() * 5}s`;
                
                container.appendChild(particle);
            }
        }

        // Initialize particles when the page loads
        window.addEventListener('load', createParticles);

        // Add input focus animations
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html> 