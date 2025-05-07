<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Include database configuration
require_once '../config/database.php';

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            throw new Exception("Username and password are required");
        }

        $username = $conn->real_escape_string($username);
        $password = $password;

        $sql = "SELECT id, username, password, role FROM admin WHERE username = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row["password"])) {
                $_SESSION["admin_id"] = $row["id"];
                $_SESSION["role"] = $row["role"];
                $_SESSION["username"] = $row["username"];
                
                // Update last login
                $update_sql = "UPDATE admin SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $row["id"]);
                $update_stmt->execute();
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                throw new Exception("Invalid username or password");
            }
        } else {
            throw new Exception("Invalid username or password");
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $error = $e->getMessage();
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
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        /* Base Styles */
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

        /* Container Styles */
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 500px;
            padding: 2rem;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }

        /* Header Styles */
        /* Header Styles */
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

        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a2a6c;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Form Styles */
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        /* Form Styles */
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
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

        /* Button Styles */
        /* Button Styles */
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

        /* Link Styles */
        /* Link Styles */
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

        /* Alert Styles */
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

    /* Loading Animation Styles */
    .loading {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .loading.active {
        display: flex;
    }
            border: 3px solid #f3f3f3;
            border-top: 3px solid #0d6efd;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Success Checkmark Styles */
        /* Success Checkmark Animation */
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

        /* Animations */
        /* Animations */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

        /* Input Field Animations */
        .input-group.focused .input-group-text {
            background: #0d6efd;
            color: white;
        }

        .input-group.focused .form-control {
            border-color: #0d6efd;
        }

        /* Icon Hover Effects */
        .input-group-text i {
            transition: all 0.3s ease;
        }

        .input-group-text i:hover {
            transform: scale(1.1);
            color: #0d6efd;
        }

        /* Input Field Animations */
        .input-group.focused .input-group-text {
            background: #0d6efd;
            color: white;
        }

        .input-group.focused .form-control {
            border-color: #0d6efd;
        }

        /* Icon Hover Effects */
        .input-group-text i {
            transition: all 0.3s ease;
        }

        .input-group-text i:hover {
            transform: scale(1.1);
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card" data-aos="fade-up" data-aos-delay="200">
            <div class="login-header" data-aos="fade-down" data-aos-delay="100">
                <i class="fas fa-user-shield"></i>
                <h1>Admin Login</h1>
                <p class="text-muted mb-0">Welcome back! Please login to continue.</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" data-aos="fade-up" data-aos-delay="300">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="mb-4" data-aos="fade-up" data-aos-delay="400">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="invalid-feedback">Please enter your username</div>
                </div>
                <div class="mb-4" data-aos="fade-up" data-aos-delay="500">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group password-field">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">Please enter your password</div>
                </div>
                <div class="d-grid mb-4" data-aos="fade-up" data-aos-delay="600">
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </div>
                <div class="text-center">
                    <a href="../index.php" class="back-to-home">
                        <i class="fas fa-arrow-left"></i> Back to Home
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
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });

        // Password Toggle Function
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.password-toggle i');
            const passwordField = document.querySelector('.password-field');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.classList.remove('fa-eye');
                toggleButton.classList.add('fa-eye-slash');
                passwordField.classList.add('password-visible');
            } else {
                passwordInput.type = 'password';
                toggleButton.classList.remove('fa-eye-slash');
                toggleButton.classList.add('fa-eye');
                passwordField.classList.remove('password-visible');
            }
        }

        // Form Validation
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();

        // Form Submission Animation
        const loginForm = document.querySelector('form');
        const submitButton = loginForm.querySelector('button[type="submit"]');
        const loadingAnimation = document.createElement('div');
        loadingAnimation.className = 'loading-animation';
        
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading animation
            submitButton.appendChild(loadingAnimation);
            loadingAnimation.classList.add('active');
            
            // Disable submit button
            submitButton.disabled = true;
            submitButton.style.cursor = 'not-allowed';
            
            // Simulate form submission delay
            setTimeout(() => {
                // Remove loading animation
                loadingAnimation.classList.remove('active');
                setTimeout(() => {
                    loadingAnimation.remove();
                }, 300);
                
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.style.cursor = 'pointer';
                
                // Submit form
                this.submit();
            }, 1000);
        });

        // Add smooth scrolling for back to home link
        document.querySelector('.back-to-home').addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
            setTimeout(() => {
                window.location.href = this.getAttribute('href');
            }, 1000);
        });

        // Add focus animations to input fields
        const inputFields = document.querySelectorAll('.form-control');
        inputFields.forEach(field => {
            field.addEventListener('focus', () => {
                field.parentElement.classList.add('focused');
            });
            
            field.addEventListener('blur', () => {
                if (!field.value) {
                    field.parentElement.classList.remove('focused');
                }
            });
        });

        // Add hover effects to input icons
        const inputIcons = document.querySelectorAll('.input-group-text i');
        inputIcons.forEach(icon => {
            icon.addEventListener('mouseenter', () => {
                icon.style.transform = 'scale(1.2)';
            });
            
            icon.addEventListener('mouseleave', () => {
                icon.style.transform = 'scale(1)';
            });
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