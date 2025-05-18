<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'rms_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create connection
$conn = null;
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Function to get database connection
function getConnection() {
    global $conn;
    return $conn;
}
?> 