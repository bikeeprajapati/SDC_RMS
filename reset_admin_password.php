<?php
require_once 'config/database.php';

$username = 'admin';
$password = 'admin123';
$new_hash = password_hash($password, PASSWORD_DEFAULT);

echo "Resetting admin password...\n";
echo "Username: " . $username . "\n";
echo "New password: " . $password . "\n";
echo "New hash: " . $new_hash . "\n\n";

// First check if admin exists
$check_sql = "SELECT id FROM admin WHERE username = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $username);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 1) {
    // Update existing admin
    $update_sql = "UPDATE admin SET password = ? WHERE username = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ss", $new_hash, $username);
    
    if ($update_stmt->execute()) {
        echo "Password updated successfully!\n";
    } else {
        echo "Error updating password: " . $update_stmt->error . "\n";
    }
} else {
    // Create new admin
    $insert_sql = "INSERT INTO admin (username, password, email, full_name, role) VALUES (?, ?, 'admin@example.com', 'System Administrator', 'super_admin')";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ss", $username, $new_hash);
    
    if ($insert_stmt->execute()) {
        echo "New admin user created successfully!\n";
    } else {
        echo "Error creating admin user: " . $insert_stmt->error . "\n";
    }
}

$conn->close();
?> 