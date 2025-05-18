<?php
require_once 'includes/session.php';
require_once 'includes/GradeCalculator.php';
requireLogin();

// Redirect if not POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: results.php');
    exit();
}

// Validate required fields
$required_fields = ['student_id', 'subject_id', 'exam_type_id', 'marks'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        header('Location: result_form.php?error=missing_fields');
        exit();
    }
}

// Get subject details (term_id and max_marks)
try {
    $stmt = $conn->prepare("SELECT term_id, max_marks FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $_POST['subject_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $subject = $result->fetch_assoc();
    
    if (!$subject) {
        header('Location: result_form.php?error=invalid_subject');
        exit();
    }
    
    $term_id = $subject['term_id'];
    $max_marks = $subject['max_marks'];
} catch(Exception $e) {
    error_log("Error fetching subject details: " . $e->getMessage());
    header('Location: result_form.php?error=db_error');
    exit();
}

// Validate marks against max_marks
if (!GradeCalculator::validateMarks($_POST['marks'], $max_marks)) {
    header('Location: result_form.php?error=invalid_marks');
    exit();
}

// Calculate grade and grade point
$marks = floatval($_POST['marks']);
$grade_info = GradeCalculator::calculate($marks, $max_marks);

try {
    // Begin transaction
    $conn->begin_transaction();

    if (isset($_POST['id'])) {
        // Update existing result
        $stmt = $conn->prepare("
            UPDATE results 
            SET student_id = ?,
                subject_id = ?,
                exam_type_id = ?,
                term_id = ?,
                marks = ?,
                grade = ?,
                grade_point = ?,
                remarks = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "iiiidsssi",
            $_POST['student_id'],
            $_POST['subject_id'],
            $_POST['exam_type_id'],
            $term_id,
            $marks,
            $grade_info['grade'],
            $grade_info['grade_point'],
            $_POST['remarks'],
            $_POST['id']
        );
    } else {
        // Check for duplicate result
        $stmt = $conn->prepare("
            SELECT id FROM results 
            WHERE student_id = ? AND subject_id = ? AND exam_type_id = ? AND term_id = ?
        ");
        $stmt->bind_param("iiii", $_POST['student_id'], $_POST['subject_id'], $_POST['exam_type_id'], $term_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            header('Location: result_form.php?error=duplicate');
            exit();
        }

        // Insert new result
        $stmt = $conn->prepare("
            INSERT INTO results (
                student_id, subject_id, exam_type_id, term_id,
                marks, grade, grade_point, remarks, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iiiidsssi",
            $_POST['student_id'],
            $_POST['subject_id'],
            $_POST['exam_type_id'],
            $term_id,
            $marks,
            $grade_info['grade'],
            $grade_info['grade_point'],
            $_POST['remarks'],
            $_SESSION['admin_id']
        );
    }

    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    header('Location: results.php?success=1');
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Error processing result: " . $e->getMessage());
    header('Location: result_form.php?error=db_error');
    exit();
} 