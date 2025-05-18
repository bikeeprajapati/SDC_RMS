<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Validate exam type ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: exam_types.php');
    exit();
}

$exam_type_id = (int)$_GET['id'];

try {
    // Check if exam type has any associated results
    $stmt = $conn->prepare("SELECT COUNT(*) as result_count FROM results WHERE exam_type_id = ?");
    $stmt->bind_param("i", $exam_type_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['result_count'];

    if ($count > 0) {
        header('Location: exam_types.php?error=cannot_delete');
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Delete the exam type
        $stmt = $conn->prepare("DELETE FROM exam_types WHERE id = ?");
        $stmt->bind_param("i", $exam_type_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        header('Location: exam_types.php?success=deleted');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error deleting exam type: " . $e->getMessage());
        header('Location: exam_types.php?error=delete_failed');
        exit();
    }
    
} catch (Exception $e) {
    error_log("Error checking exam type dependencies: " . $e->getMessage());
    header('Location: exam_types.php?error=delete_failed');
    exit();
} 