<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access!";
    header("Location: ../subjects.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];

    // Validate input
    if (empty($subject_id)) {
        $_SESSION['error'] = "Invalid subject ID!";
        header("Location: ../subjects.php");
        exit();
    }

    // Check if subject exists
    $check_sql = "SELECT id, subject_name FROM subjects WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $subject_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Subject not found!";
        header("Location: ../subjects.php");
        exit();
    }

    $subject = $result->fetch_assoc();

    // Check if subject has any grades
    $check_grades_sql = "SELECT id FROM grades WHERE subject_id = ?";
    $check_grades_stmt = $conn->prepare($check_grades_sql);
    $check_grades_stmt->bind_param("i", $subject_id);
    $check_grades_stmt->execute();
    $grades_result = $check_grades_stmt->get_result();

    if ($grades_result->num_rows > 0) {
        $_SESSION['error'] = "Cannot delete subject: It has associated grades!";
        header("Location: ../subjects.php");
        exit();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete subject
        $delete_sql = "DELETE FROM subjects WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $subject_id);
        $delete_stmt->execute();

        // Log the action
        $admin_id = $_SESSION['user_id'];
        $action = "Deleted subject: " . $subject['subject_name'];
        $log_sql = "INSERT INTO audit_logs (user_id, user_type, action, table_name, record_id) VALUES (?, 'admin', ?, 'subjects', ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("isi", $admin_id, $action, $subject_id);
        $log_stmt->execute();

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Subject deleted successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error deleting subject: " . $e->getMessage();
    }

    header("Location: ../subjects.php");
    exit();
} else {
    header("Location: ../subjects.php");
    exit();
} 