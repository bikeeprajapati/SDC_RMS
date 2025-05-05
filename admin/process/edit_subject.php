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
    $course_id = $_POST['course_id'];
    $subject_code = trim($_POST['subject_code']);
    $subject_name = trim($_POST['subject_name']);
    $credits = $_POST['credits'];
    $theory_marks = $_POST['theory_marks'];
    $practical_marks = $_POST['practical_marks'];
    $description = trim($_POST['description']);
    $status = $_POST['status'];

    // Validate inputs
    if (empty($subject_id) || empty($course_id) || empty($subject_code) || empty($subject_name) || empty($credits)) {
        $_SESSION['error'] = "Required fields cannot be empty!";
        header("Location: ../subjects.php");
        exit();
    }

    // Validate marks
    if ($theory_marks < 0 || $practical_marks < 0) {
        $_SESSION['error'] = "Marks cannot be negative!";
        header("Location: ../subjects.php");
        exit();
    }

    // Check if subject exists
    $check_sql = "SELECT id FROM subjects WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $subject_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Subject not found!";
        header("Location: ../subjects.php");
        exit();
    }

    // Check if subject code already exists for another subject
    $check_code_sql = "SELECT id FROM subjects WHERE subject_code = ? AND id != ?";
    $check_code_stmt = $conn->prepare($check_code_sql);
    $check_code_stmt->bind_param("si", $subject_code, $subject_id);
    $check_code_stmt->execute();
    $code_result = $check_code_stmt->get_result();

    if ($code_result->num_rows > 0) {
        $_SESSION['error'] = "Subject code already exists!";
        header("Location: ../subjects.php");
        exit();
    }

    // Get old values for audit log
    $old_values_sql = "SELECT * FROM subjects WHERE id = ?";
    $old_values_stmt = $conn->prepare($old_values_sql);
    $old_values_stmt->bind_param("i", $subject_id);
    $old_values_stmt->execute();
    $old_values = $old_values_stmt->get_result()->fetch_assoc();

    // Update subject
    $sql = "UPDATE subjects SET course_id = ?, subject_code = ?, subject_name = ?, credits = ?, 
            theory_marks = ?, practical_marks = ?, description = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issiiissi", $course_id, $subject_code, $subject_name, $credits, 
                      $theory_marks, $practical_marks, $description, $status, $subject_id);

    if ($stmt->execute()) {
        // Log the action
        $admin_id = $_SESSION['user_id'];
        $action = "Updated subject: " . $subject_name;
        $new_values = [
            'course_id' => $course_id,
            'subject_code' => $subject_code,
            'subject_name' => $subject_name,
            'credits' => $credits,
            'theory_marks' => $theory_marks,
            'practical_marks' => $practical_marks,
            'description' => $description,
            'status' => $status
        ];
        
        $log_sql = "INSERT INTO audit_logs (user_id, user_type, action, table_name, record_id, old_values, new_values) 
                    VALUES (?, 'admin', ?, 'subjects', ?, ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        $old_values_json = json_encode($old_values);
        $new_values_json = json_encode($new_values);
        $log_stmt->bind_param("isisss", $admin_id, $action, $subject_id, $old_values_json, $new_values_json);
        $log_stmt->execute();

        $_SESSION['success'] = "Subject updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating subject: " . $conn->error;
    }

    header("Location: ../subjects.php");
    exit();
} else {
    header("Location: ../subjects.php");
    exit();
} 