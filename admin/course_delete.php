<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Validate course ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: courses.php');
    exit();
}

$course_id = (int)$_GET['id'];

try {
    // Check if course has any associated records
    $query = "
        SELECT 
            (SELECT COUNT(*) FROM students WHERE course_id = ?) as student_count,
            (SELECT COUNT(*) FROM subjects WHERE course_id = ?) as subject_count
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $course_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $counts = $result->fetch_assoc();

    if ($counts['student_count'] > 0 || $counts['subject_count'] > 0) {
        header('Location: courses.php?error=cannot_delete');
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Delete academic terms first
        $stmt = $conn->prepare("DELETE FROM academic_terms WHERE course_id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();

        // Delete the course
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        header('Location: courses.php?msg=deleted');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error deleting course: " . $e->getMessage());
        header('Location: courses.php?error=delete_failed');
        exit();
    }
    
} catch (Exception $e) {
    error_log("Error checking course dependencies: " . $e->getMessage());
    header('Location: courses.php?error=delete_failed');
    exit();
}

// If we somehow get here, redirect to courses page
header('Location: courses.php');
exit(); 