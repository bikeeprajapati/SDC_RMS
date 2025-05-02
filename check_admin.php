<?php
require_once "config/database.php";

// Check if admin exists
$sql = "SELECT * FROM admins WHERE username = 'admin'";
$result = mysqli_query($conn, $sql);

if($row = mysqli_fetch_assoc($result)) {
    echo "Admin found in database:<br>";
    echo "Username: " . $row['username'] . "<br>";
    echo "Stored Hash: " . $row['password'] . "<br>";
    
    // Test password verification
    $test_password = "admin123";
    if(password_verify($test_password, $row['password'])) {
        echo "<br>Password verification SUCCESSFUL!<br>";
    } else {
        echo "<br>Password verification FAILED!<br>";
    }
} else {
    echo "No admin found in database!";
}

mysqli_close($conn);
?> 