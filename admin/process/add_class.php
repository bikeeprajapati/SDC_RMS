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
    $name = trim($_POST['name']);
    $semester_id = $_POST['semester_id'];
    $year_id = $_POST['year_id'];
    $section_id = $_POST['section_id'];
    $capacity = $_POST['capacity'];

    // Validate inputs
    if (empty($name) || empty($semester_id) || empty($year_id) || empty($section_id) || empty($capacity)) {
        $_SESSION['error'] = "All fields are required!";
        header("Location: ../classes.php");
        exit();
    }

    // Check if class already exists
    $check_sql = "SELECT id FROM classes WHERE name = ? AND semester_id = ? AND year_id = ? AND section_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("siii", $name, $semester_id, $year_id, $section_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "A class with these details already exists!";
        header("Location: ../classes.php");
        exit();
    }

    // Insert new class
    $sql = "INSERT INTO classes (name, semester_id, year_id, section_id, capacity) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siiii", $name, $semester_id, $year_id, $section_id, $capacity);

    if ($stmt->execute()) {
        // Log the action
        $admin_id = $_SESSION['user_id'];
        $action = "Added new class: " . $name;
        $log_sql = "INSERT INTO audit_logs (user_id, user_type, action, table_name, record_id) VALUES (?, 'admin', ?, 'classes', ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("isi", $admin_id, $action, $stmt->insert_id);
        $log_stmt->execute();

        $_SESSION['success'] = "Class added successfully!";
    } else {
        $_SESSION['error'] = "Error adding class: " . $conn->error;
    }

    header("Location: ../classes.php");
    exit();
} else {
    header("Location: ../classes.php");
    exit();
} 