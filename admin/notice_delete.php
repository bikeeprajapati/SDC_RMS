<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Validate notice ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: notices.php');
    exit();
}

$notice_id = (int)$_GET['id'];

try {
    // Begin transaction
    $conn->begin_transaction();

    try {
        // Delete the notice
        $stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
        $stmt->bind_param("i", $notice_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        header('Location: notices.php?success=deleted');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error deleting notice: " . $e->getMessage());
        header('Location: notices.php?error=delete_failed');
        exit();
    }
    
} catch (Exception $e) {
    error_log("Error deleting notice: " . $e->getMessage());
    header('Location: notices.php?error=delete_failed');
    exit();
} 