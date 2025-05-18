<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Validate subject ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: subjects.php');
    exit();
}

$subject_id = (int)$_GET['id'];

try {
    // Check if subject has any associated results
    $stmt = $conn->prepare("SELECT COUNT(*) as result_count FROM results WHERE subject_id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['result_count'];

    if ($count > 0) {
        header('Location: subjects.php?error=cannot_delete');
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Delete the subject
        $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        header('Location: subjects.php?msg=deleted');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error deleting subject: " . $e->getMessage());
        header('Location: subjects.php?error=delete_failed');
        exit();
    }
    
} catch (Exception $e) {
    error_log("Error checking subject dependencies: " . $e->getMessage());
    header('Location: subjects.php?error=delete_failed');
    exit();
} 