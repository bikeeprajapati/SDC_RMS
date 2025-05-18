<?php
require_once 'includes/session.php';
requireLogin();

// Redirect if not POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: students.php');
    exit();
}

// Validate required fields
$required_fields = ['roll_number', 'full_name', 'course_id', 'current_term_id'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        header('Location: student_form.php?error=missing_fields');
        exit();
    }
}

// Sanitize input
$student = [
    'roll_number' => trim($_POST['roll_number']),
    'full_name' => trim($_POST['full_name']),
    'course_id' => (int)$_POST['course_id'],
    'current_term_id' => (int)$_POST['current_term_id'],
    'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
    'phone' => !empty($_POST['phone']) ? trim($_POST['phone']) : null
];

try {
    // Check if roll number already exists
    $stmt = $conn->prepare("SELECT id FROM students WHERE roll_number = ? AND id != ?");
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $stmt->bind_param("si", $student['roll_number'], $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        header('Location: student_form.php?error=duplicate_roll');
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    if (isset($_POST['id'])) {
        // Update existing student
        $stmt = $conn->prepare("
            UPDATE students 
            SET roll_number = ?, 
                full_name = ?, 
                course_id = ?, 
                current_term_id = ?, 
                email = ?, 
                phone = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssiissi",
            $student['roll_number'],
            $student['full_name'],
            $student['course_id'],
            $student['current_term_id'],
            $student['email'],
            $student['phone'],
            $id
        );
    } else {
        // Insert new student
        $stmt = $conn->prepare("
            INSERT INTO students 
            (roll_number, full_name, course_id, current_term_id, email, phone)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssiiss",
            $student['roll_number'],
            $student['full_name'],
            $student['course_id'],
            $student['current_term_id'],
            $student['email'],
            $student['phone']
        );
    }

    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    header('Location: students.php?success=' . (isset($_POST['id']) ? 'updated' : 'created'));
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Error processing student: " . $e->getMessage());
    header('Location: student_form.php?error=db_error');
    exit();
} 