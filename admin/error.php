<?php
session_start();

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Validate session
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    header('Location: login.php?error=' . urlencode('Unauthorized access.'));
    exit();
}

// Get sanitized error message
$error_msg = isset($_GET['msg']) ? htmlspecialchars(urldecode($_GET['msg']), ENT_QUOTES, 'UTF-8') : 'An error occurred.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - SDC RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1>Oops! Something went wrong</h1>
            <p class="error-message"><?php echo $error_msg; ?></p>
            <div class="error-actions">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Return to Dashboard
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Go Back
                </a>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
