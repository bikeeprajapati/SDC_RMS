<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access!";
    header("Location: ../classes.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'];

    // Validate input
    if (empty($class_id)) {
        $_SESSION['error'] = "Invalid class ID!";
        header("Location: ../classes.php");
        exit();
    }

    // Check if class exists and get its details for audit log
    $check_sql = "SELECT * FROM classes WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $class_id);
    $check_stmt->execute();
    $class = $check_stmt->get_result()->fetch_assoc();

    if (!$class) {
        $_SESSION['error'] = "Class not found!";
        header("Location: ../classes.php");
        exit();
    }

    // Check if class has any students
    $check_students_sql = "SELECT COUNT(*) as student_count FROM students WHERE class_id = ?";
    $check_students_stmt = $conn->prepare($check_students_sql);
    $check_students_stmt->bind_param("i", $class_id);
    $check_students_stmt->execute();
    $student_count = $check_students_stmt->get_result()->fetch_assoc()['student_count'];

    if ($student_count > 0) {
        $_SESSION['error'] = "Cannot delete class: There are students enrolled in this class!";
        header("Location: ../classes.php");
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Delete class
        $delete_sql = "DELETE FROM classes WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $class_id);
        $delete_stmt->execute();

        // Log the action
        $admin_id = $_SESSION['user_id'];
        $action = "Deleted class: " . $class['name'];
        $log_sql = "INSERT INTO audit_logs (user_id, user_type, action, table_name, record_id, old_values) 
                    VALUES (?, 'admin', ?, 'classes', ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        $old_values_json = json_encode($class);
        $log_stmt->bind_param("isis", $admin_id, $action, $class_id, $old_values_json);
        $log_stmt->execute();

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Class deleted successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error deleting class: " . $e->getMessage();
    }

    header("Location: ../classes.php");
    exit();
} else {
    header("Location: ../classes.php");
    exit();
} 