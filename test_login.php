<?php
require_once 'config/database.php';

// Test database connection
echo "Testing database connection...\n";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connection successful!\n\n";

// Test admin table
echo "Testing admin table...\n";
$sql = "SELECT * FROM admin";
$result = $conn->query($sql);

if ($result) {
    echo "Admin table exists!\n";
    echo "Number of admin users: " . $result->num_rows . "\n\n";
    
    // Display admin users
    if ($result->num_rows > 0) {
        echo "Admin users in database:\n";
        while($row = $result->fetch_assoc()) {
            echo "Username: " . $row['username'] . "\n";
            echo "Email: " . $row['email'] . "\n";
            echo "Role: " . $row['role'] . "\n";
            echo "Status: " . $row['status'] . "\n";
            echo "Password Hash: " . $row['password'] . "\n";
            echo "-------------------\n";
        }
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

// Test password verification
echo "\nTesting password verification...\n";
$test_username = 'admin';
$test_password = 'admin123';

$sql = "SELECT * FROM admin WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $test_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    echo "Found admin user with hash: " . $admin['password'] . "\n";
    
    // Generate a new hash for comparison
    $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
    echo "New hash for 'admin123': " . $new_hash . "\n";
    
    if (password_verify($test_password, $admin['password'])) {
        echo "Password verification successful!\n";
    } else {
        echo "Password verification failed!\n";
        echo "Current password hash in database: " . $admin['password'] . "\n";
        
        // Try to update the password with a new hash
        echo "\nAttempting to update password...\n";
        $update_sql = "UPDATE admin SET password = ? WHERE username = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $new_hash, $test_username);
        
        if ($update_stmt->execute()) {
            echo "Password updated successfully!\n";
            echo "New hash stored: " . $new_hash . "\n";
        } else {
            echo "Failed to update password: " . $update_stmt->error . "\n";
        }
    }
} else {
    echo "Admin user not found!\n";
}

$conn->close();
?> 