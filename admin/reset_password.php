<?php
require_once '../config/database.php';

try {
    // Generate hash for 'admin123'
    $password_hash = password_hash('admin123', PASSWORD_BCRYPT);
    
    // Update admin password
    $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE username = 'admin'");
    $stmt->bind_param("s", $password_hash);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo "Password reset successful! You can now login with:<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
        echo "<br><a href='login.php'>Go to Login Page</a>";
    } else {
        echo "No admin user found. Please create an admin user first.";
    }
} catch(Exception $e) {
    error_log("Error resetting password: " . $e->getMessage());
    echo "Error resetting password. Please try again later.";
}
?> 