<?php
require_once 'config/database.php';

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS sdc_rms";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db("sdc_rms");

// Create admin table
$sql = "CREATE TABLE IF NOT EXISTS `admin` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `last_login` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "Admin table created successfully<br>";
} else {
    echo "Error creating admin table: " . $conn->error . "<br>";
}

// Insert default admin if not exists
$default_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO admin (username, password, email, full_name, role) 
        VALUES ('admin', '$default_password', 'admin@example.com', 'System Administrator', 'super_admin')
        ON DUPLICATE KEY UPDATE id = id";

if ($conn->query($sql) === TRUE) {
    echo "Default admin user created or already exists<br>";
} else {
    echo "Error creating default admin: " . $conn->error . "<br>";
}

echo "<br>Setup completed. You can now <a href='admin/login.php'>login</a> with:<br>";
echo "Username: admin<br>";
echo "Password: admin123";
?> 