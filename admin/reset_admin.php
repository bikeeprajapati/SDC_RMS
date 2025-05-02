<?php
require_once "../config/database.php";

// Clear existing admin users
$sql = "TRUNCATE TABLE admins";
if(mysqli_query($conn, $sql)) {
    echo "Cleared existing admin users.<br>";
} else {
    echo "Error clearing admin users: " . mysqli_error($conn) . "<br>";
}

// Create new admin user with proper password hash
$username = "admin";
$password = "admin123";
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO admins (username, password) VALUES (?, ?)";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ss", $username, $hashed_password);
    
    if(mysqli_stmt_execute($stmt)) {
        echo "New admin user created successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
        echo "Stored Hash: " . $hashed_password . "<br>";
        
        // Verify the password
        if(password_verify($password, $hashed_password)) {
            echo "<br>Password verification successful! You can now log in.";
        } else {
            echo "<br>Error: Password verification failed!";
        }
    } else {
        echo "Error creating admin user: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "Error preparing statement: " . mysqli_error($conn);
}

mysqli_close($conn);
?> 