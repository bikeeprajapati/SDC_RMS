<?php
require_once '../config/database.php';
require_once 'includes/session.php';
requireLogin();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validate required parameters
if (!isset($_GET['course_id']) || !isset($_GET['term_id']) || !isset($_GET['exam_type_id']) ||
    empty($_GET['course_id']) || empty($_GET['term_id']) || empty($_GET['exam_type_id'])) {
    header('Location: bulk_result_upload.php?error=invalid_request');
    exit();
}

$course_id = (int)$_GET['course_id'];
$term_id = (int)$_GET['term_id'];
$exam_type_id = (int)$_GET['exam_type_id'];

try {
    // Get course details
    $stmt = $conn->prepare("SELECT course_name, course_code FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();

    if (!$course) {
        throw new Exception("Course not found");
    }

    // Get term details
    $stmt = $conn->prepare("SELECT term_name FROM academic_terms WHERE id = ?");
    $stmt->bind_param("i", $term_id);
    $stmt->execute();
    $term = $stmt->get_result()->fetch_assoc();

    if (!$term) {
        throw new Exception("Term not found");
    }

    // Get exam type details
    $stmt = $conn->prepare("SELECT type_name FROM exam_types WHERE id = ?");
    $stmt->bind_param("i", $exam_type_id);
    $stmt->execute();
    $exam_type = $stmt->get_result()->fetch_assoc();

    if (!$exam_type) {
        throw new Exception("Exam type not found");
    }

    // Get subjects for this course and term
    $stmt = $conn->prepare("
        SELECT id, subject_code, subject_name, max_marks 
        FROM subjects 
        WHERE course_id = ? AND term_id = ?
        ORDER BY subject_code
    ");
    $stmt->bind_param("ii", $course_id, $term_id);
    $stmt->execute();
    $subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($subjects)) {
        throw new Exception("No subjects found for this course and term");
    }

    // Get students enrolled in this course
    $stmt = $conn->prepare("
        SELECT s.id, s.roll_number, s.full_name 
        FROM students s
        WHERE s.course_id = ? AND s.current_term_id = ?
        ORDER BY s.roll_number
    ");
    $stmt->bind_param("ii", $course_id, $term_id);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($students)) {
        throw new Exception("No students found for this course and term");
    }

    // Check for existing results
    $stmt = $conn->prepare("
        SELECT DISTINCT student_id, subject_id 
        FROM results 
        WHERE term_id = ? AND exam_type_id = ?
    ");
    $stmt->bind_param("ii", $term_id, $exam_type_id);
    $stmt->execute();
    $existing_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Create lookup for existing results
    $result_lookup = [];
    foreach ($existing_results as $result) {
        $key = $result['student_id'] . '_' . $result['subject_id'];
        $result_lookup[$key] = true;
    }

    // Generate CSV content
    $filename = sprintf(
        'result_template_%s_%s_%s_%s.csv',
        $course['course_code'],
        preg_replace('/[^a-zA-Z0-9]/', '_', $term['term_name']),
        preg_replace('/[^a-zA-Z0-9]/', '_', $exam_type['type_name']),
        date('Y-m-d')
    );

    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Create CSV file
    $output = fopen('php://output', 'w');
    if ($output === false) {
        throw new Exception("Failed to create output stream");
    }

    // Write metadata
    fputcsv($output, ['Course:', $course['course_name'] . ' (' . $course['course_code'] . ')']);
    fputcsv($output, ['Term:', $term['term_name']]);
    fputcsv($output, ['Exam Type:', $exam_type['type_name']]);
    fputcsv($output, []); // Empty line

    // Write headers
    $headers = ['Roll Number', 'Student Name'];
    foreach ($subjects as $subject) {
        $headers[] = $subject['subject_code'] . ' (Max: ' . $subject['max_marks'] . ')';
    }
    fputcsv($output, $headers);

    // Write student rows
    foreach ($students as $student) {
        $row = [$student['roll_number'], $student['full_name']];
        // Add empty cells for marks or 'EXISTS' if result already exists
        foreach ($subjects as $subject) {
            $key = $student['id'] . '_' . $subject['id'];
            $row[] = isset($result_lookup[$key]) ? 'EXISTS' : '';
        }
        fputcsv($output, $row);
    }

    // Write hidden metadata for processing
    fputcsv($output, []); // Empty line
    fputcsv($output, ['DO NOT MODIFY BELOW THIS LINE']);
    fputcsv($output, ['metadata_course_id', $course_id]);
    fputcsv($output, ['metadata_term_id', $term_id]);
    fputcsv($output, ['metadata_exam_type_id', $exam_type_id]);
    foreach ($subjects as $subject) {
        fputcsv($output, ['metadata_subject', $subject['id'], $subject['subject_code'], $subject['max_marks']]);
    }

    fclose($output);
    exit();

} catch (Exception $e) {
    error_log("Error generating template: " . $e->getMessage());
    header('Location: bulk_result_upload.php?error=template_generation_failed');
    exit();
} 