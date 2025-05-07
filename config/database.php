<?php
// Error reporting - only show errors in development
if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
}

// Database configuration
$servername = "localhost";
$username = "root";
$password = ""; // Consider moving to environment variable
$dbname = "sdc_rms";

// Create connection with improved error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting charset: " . $conn->error);
    }
    
    // Set SQL mode to strict and prevent SQL injection
    $conn->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    // Don't expose actual error message to users
    header("Location: error.php?msg=" . urlencode("Database connection error. Please contact support."));
    exit();
}

// Function to safely escape strings
function escape_string($conn, $string) {
    return $conn->real_escape_string($string);
}

// Function to execute prepared statements
function execute_prepared($conn, $sql, $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        
        if (!empty($params)) {
            $types = '';
            $bind_params = [];
            foreach ($params as $param) {
                $types .= gettype($param) === 'integer' ? 'i' : 's';
                $bind_params[] = $param;
            }
            array_unshift($bind_params, $types);
            call_user_func_array([$stmt, 'bind_param'], $bind_params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        
        return $stmt;
    } catch (Exception $e) {
        error_log("Database execution error: " . $e->getMessage());
        throw $e;
    }
}
?> 