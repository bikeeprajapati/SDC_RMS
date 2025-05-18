<?php
session_start();
require_once '../config/database.php';
require_once 'includes/session.php';
requireLogin();

// Validate request method and ID
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Location: students.php?error=invalid_request');
    exit();
}

$student_id = (int)$_POST['id'];

try {
    // Begin transaction
    $conn->begin_transaction();

    // Check if student exists and get their roll number for logging
    $stmt = $conn->prepare("SELECT roll_number FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Student not found");
    }
    
    $student = $result->fetch_assoc();
    
    // Check if student has any results
    $stmt = $conn->prepare("SELECT COUNT(*) as result_count FROM results WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result_count = $stmt->get_result()->fetch_assoc()['result_count'];
    
    if ($result_count > 0) {
        header('Location: students.php?error=has_results');
        exit();
    }
    
    // Delete the student
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to delete student");
    }
    
    // Log the deletion
    error_log("Student deleted - ID: {$student_id}, Roll Number: {$student['roll_number']}");
    
    // Commit transaction
    $conn->commit();
    
    header('Location: students.php?success=deleted');
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Error deleting student: " . $e->getMessage());
    header('Location: students.php?error=delete_failed');
    exit();
} 