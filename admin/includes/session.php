<?php
session_start();

// Include database connection
require_once __DIR__ . '/../../config/database.php';
global $conn;

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Get current admin info
function getCurrentAdmin() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT id, username, full_name FROM admin WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['admin_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error fetching admin info: " . $e->getMessage());
        return null;
    }
}

// Logout function
function logout() {
    session_start();
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}
?> 