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
    $course_id = $_POST['course_id'];
    $subject_code = trim($_POST['subject_code']);
    $subject_name = trim($_POST['subject_name']);
    $credits = $_POST['credits'];
    $theory_marks = $_POST['theory_marks'];
    $practical_marks = $_POST['practical_marks'];
    $description = trim($_POST['description']);

    // Validate inputs
    if (empty($course_id) || empty($subject_code) || empty($subject_name) || empty($credits)) {
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

    // Check if subject code already exists
    $check_sql = "SELECT id FROM subjects WHERE subject_code = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $subject_code);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Subject code already exists!";
        header("Location: ../subjects.php");
        exit();
    }

    // Insert new subject
    $sql = "INSERT INTO subjects (course_id, subject_code, subject_name, credits, theory_marks, practical_marks, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issiiis", $course_id, $subject_code, $subject_name, $credits, $theory_marks, $practical_marks, $description);

    if ($stmt->execute()) {
        // Log the action
        $admin_id = $_SESSION['user_id'];
        $action = "Added new subject: " . $subject_name;
        $log_sql = "INSERT INTO audit_logs (user_id, user_type, action, table_name, record_id) VALUES (?, 'admin', ?, 'subjects', ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("isi", $admin_id, $action, $stmt->insert_id);
        $log_stmt->execute();

        $_SESSION['success'] = "Subject added successfully!";
    } else {
        $_SESSION['error'] = "Error adding subject: " . $conn->error;
    }

    header("Location: ../subjects.php");
    exit();
} else {
    header("Location: ../subjects.php");
    exit();
} 