<?php
require_once "config/database.php";

// First, clear the admins table
$sql = "TRUNCATE TABLE admins";
mysqli_query($conn, $sql);

// Create new admin with properly hashed password
$username = "admin";
$password = "admin123";
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO admins (username, password) VALUES (?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $username, $hashed_password);

if(mysqli_stmt_execute($stmt)) {
    echo "Admin user created successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
} else {
    echo "Error creating admin user: " . mysqli_error($conn);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?> 