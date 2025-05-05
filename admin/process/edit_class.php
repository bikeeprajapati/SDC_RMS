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
    $name = trim($_POST['name']);
    $semester_id = $_POST['semester_id'];
    $year_id = $_POST['year_id'];
    $section_id = $_POST['section_id'];
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];

    // Validate inputs
    if (empty($class_id) || empty($name) || empty($semester_id) || empty($year_id) || empty($section_id) || empty($capacity)) {
        $_SESSION['error'] = "All fields are required!";
        header("Location: ../classes.php");
        exit();
    }

    // Check if class exists
    $check_sql = "SELECT id FROM classes WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $class_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Class not found!";
        header("Location: ../classes.php");
        exit();
    }

    // Check if another class with same details exists
    $check_duplicate_sql = "SELECT id FROM classes WHERE name = ? AND semester_id = ? AND year_id = ? AND section_id = ? AND id != ?";
    $check_duplicate_stmt = $conn->prepare($check_duplicate_sql);
    $check_duplicate_stmt->bind_param("siiii", $name, $semester_id, $year_id, $section_id, $class_id);
    $check_duplicate_stmt->execute();
    $duplicate_result = $check_duplicate_stmt->get_result();

    if ($duplicate_result->num_rows > 0) {
        $_SESSION['error'] = "Another class with these details already exists!";
        header("Location: ../classes.php");
        exit();
    }

    // Get old values for audit log
    $old_values_sql = "SELECT * FROM classes WHERE id = ?";
    $old_values_stmt = $conn->prepare($old_values_sql);
    $old_values_stmt->bind_param("i", $class_id);
    $old_values_stmt->execute();
    $old_values = $old_values_stmt->get_result()->fetch_assoc();

    // Update class
    $sql = "UPDATE classes SET name = ?, semester_id = ?, year_id = ?, section_id = ?, capacity = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siiissi", $name, $semester_id, $year_id, $section_id, $capacity, $status, $class_id);

    if ($stmt->execute()) {
        // Log the action
        $admin_id = $_SESSION['user_id'];
        $action = "Updated class: " . $name;
        $new_values = [
            'name' => $name,
            'semester_id' => $semester_id,
            'year_id' => $year_id,
            'section_id' => $section_id,
            'capacity' => $capacity,
            'status' => $status
        ];
        
        $log_sql = "INSERT INTO audit_logs (user_id, user_type, action, table_name, record_id, old_values, new_values) 
                    VALUES (?, 'admin', ?, 'classes', ?, ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        $old_values_json = json_encode($old_values);
        $new_values_json = json_encode($new_values);
        $log_stmt->bind_param("isisss", $admin_id, $action, $class_id, $old_values_json, $new_values_json);
        $log_stmt->execute();

        $_SESSION['success'] = "Class updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating class: " . $conn->error;
    }

    header("Location: ../classes.php");
    exit();
} else {
    header("Location: ../classes.php");
    exit();
} 