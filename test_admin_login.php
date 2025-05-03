<?php
require_once 'config/database.php';

echo "Testing Admin Login System\n";
echo "========================\n\n";

// Test database connection
echo "1. Testing Database Connection...\n";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "✓ Database connection successful\n\n";

// Test admin table
echo "2. Testing Admin Table...\n";
$sql = "SHOW TABLES LIKE 'admin'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "✓ Admin table exists\n";
    
    // Check admin table structure
    $sql = "DESCRIBE admin";
    $result = $conn->query($sql);
    echo "\nAdmin table structure:\n";
    while($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // Check for admin users
    $sql = "SELECT id, username, email, role, status FROM admin";
    $result = $conn->query($sql);
    
    echo "\nAdmin users in database:\n";
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "- Username: " . $row['username'] . "\n";
            echo "  Email: " . $row['email'] . "\n";
            echo "  Role: " . $row['role'] . "\n";
            echo "  Status: " . $row['status'] . "\n";
            echo "  --------------------\n";
        }
    } else {
        echo "No admin users found!\n";
    }
} else {
    echo "✗ Admin table does not exist!\n";
}

// Test login functionality
echo "\n3. Testing Login Functionality...\n";
$test_username = 'admin';
$test_password = 'admin123';

$sql = "SELECT * FROM admin WHERE username = ? AND status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $test_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    echo "✓ Admin user found\n";
    
    if (password_verify($test_password, $admin['password'])) {
        echo "✓ Password verification successful\n";
    } else {
        echo "✗ Password verification failed\n";
        echo "Current hash in database: " . $admin['password'] . "\n";
        
        // Generate new hash
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        echo "\nNew hash for 'admin123': " . $new_hash . "\n";
        
        // Update password
        $update_sql = "UPDATE admin SET password = ? WHERE username = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $new_hash, $test_username);
        
        if ($update_stmt->execute()) {
            echo "✓ Password updated successfully\n";
        } else {
            echo "✗ Failed to update password: " . $update_stmt->error . "\n";
        }
    }
} else {
    echo "✗ Admin user not found!\n";
}

$conn->close();
?> 