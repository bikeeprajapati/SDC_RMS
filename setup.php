<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to check if database exists
function databaseExists($conn, $dbname) {
    $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    return $result->num_rows > 0;
}

// Function to check if all required tables exist
function checkRequiredTables($conn) {
    $required_tables = [
        'admins', 'semesters', 'years', 'sections', 'classes',
        'courses', 'subjects', 'students', 'enrollments', 'grades',
        'grade_points', 'notices', 'audit_logs', 'student_attendance'
    ];
    
    $existing_tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $existing_tables[] = $row[0];
    }
    
    $missing_tables = array_diff($required_tables, $existing_tables);
    return empty($missing_tables);
}

// Function to verify table structure
function verifyTableStructure($conn, $table_name) {
    $result = $conn->query("SHOW COLUMNS FROM $table_name");
    return $result->num_rows > 0;
}

try {
    // Initial connection without database
    $conn = new mysqli("localhost", "root", "");
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Check if database exists
    if (!databaseExists($conn, "rms_db")) {
        echo "Database does not exist. Creating...<br>";
        require_once 'config/init_database.php';
    } else {
        // Connect to existing database
        $conn->select_db("rms_db");
        
        // Check if all required tables exist
        if (!checkRequiredTables($conn)) {
            echo "Some required tables are missing. Reinitializing...<br>";
            require_once 'config/init_database.php';
        } else {
            // Verify table structures
            $tables = ['admins', 'semesters', 'years', 'sections', 'classes', 
                      'courses', 'subjects', 'students', 'enrollments', 'grades'];
            
            $structure_issues = false;
            foreach ($tables as $table) {
                if (!verifyTableStructure($conn, $table)) {
                    $structure_issues = true;
                    break;
                }
            }
            
            if ($structure_issues) {
                echo "Table structure issues detected. Reinitializing...<br>";
                require_once 'config/init_database.php';
            } else {
                echo "Database is properly initialized and verified!<br>";
            }
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    error_log("Setup error: " . $e->getMessage());
    die("Setup failed: " . $e->getMessage());
}
?> 