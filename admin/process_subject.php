<?php
require_once 'includes/session.php';
requireLogin();

// Redirect if not POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: subjects.php');
    exit();
}

// Validate required fields
$required_fields = ['subject_code', 'subject_name', 'course_id', 'term_id', 'subject_type', 'max_marks'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        header('Location: subject_form.php?error=missing_fields');
        exit();
    }
}

// Sanitize input
$subject = [
    'subject_code' => trim($_POST['subject_code']),
    'subject_name' => trim($_POST['subject_name']),
    'course_id' => (int)$_POST['course_id'],
    'term_id' => (int)$_POST['term_id'],
    'subject_type' => trim($_POST['subject_type']),
    'max_marks' => (int)$_POST['max_marks']
];

// Validate max_marks range
if ($subject['max_marks'] < 1 || $subject['max_marks'] > 1000) {
    header('Location: subject_form.php?error=invalid_marks');
    exit();
}

// Validate subject_type enum
if (!in_array($subject['subject_type'], ['IT', 'Management'])) {
    header('Location: subject_form.php?error=invalid_type');
    exit();
}

try {
    // Check if subject code already exists
    $stmt = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ? AND id != ?");
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $stmt->bind_param("si", $subject['subject_code'], $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        header('Location: subject_form.php?error=duplicate_code');
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    if (isset($_POST['id'])) {
        // Update existing subject
        $stmt = $conn->prepare("
            UPDATE subjects 
            SET subject_code = ?, 
                subject_name = ?, 
                course_id = ?, 
                term_id = ?, 
                subject_type = ?, 
                max_marks = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssiisii",
            $subject['subject_code'],
            $subject['subject_name'],
            $subject['course_id'],
            $subject['term_id'],
            $subject['subject_type'],
            $subject['max_marks'],
            $id
        );
    } else {
        // Insert new subject
        $stmt = $conn->prepare("
            INSERT INTO subjects 
            (subject_code, subject_name, course_id, term_id, subject_type, max_marks)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssiisi",
            $subject['subject_code'],
            $subject['subject_name'],
            $subject['course_id'],
            $subject['term_id'],
            $subject['subject_type'],
            $subject['max_marks']
        );
    }

    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    header('Location: subjects.php?success=' . (isset($_POST['id']) ? 'updated' : 'created'));
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Error processing subject: " . $e->getMessage());
    header('Location: subject_form.php?error=db_error');
    exit();
} 