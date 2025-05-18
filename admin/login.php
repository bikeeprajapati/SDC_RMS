<?php
require_once '../config/database.php';
session_start();

// Redirect if already logged in
if(isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT id, username, password, full_name FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($admin = $result->fetch_assoc()) {
            if(password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                
                header('Location: dashboard.php');
                exit();
            }
        }
        $error = 'Invalid username or password';
    } catch(Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $error = 'System error. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">Admin Login</h4>
                </div>
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <a href="../index.php" class="text-decoration-none">&larr; Back to Home</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 