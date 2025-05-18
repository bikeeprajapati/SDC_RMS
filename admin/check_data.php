<?php
// Prevent any output before our JSON response
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    require_once '../config/database.php';

    // Validate input parameters
    $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    $term_id = isset($_GET['term_id']) ? (int)$_GET['term_id'] : 0;

    if ($course_id <= 0 || $term_id <= 0) {
        throw new Exception("Invalid course_id or term_id");
    }

    // Check students
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM students 
        WHERE course_id = ? AND current_term_id = ?
    ");
    $stmt->bind_param("ii", $course_id, $term_id);
    $stmt->execute();
    $student_count = $stmt->get_result()->fetch_assoc()['count'];

    // Check subjects
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM subjects 
        WHERE course_id = ? AND term_id = ?
    ");
    $stmt->bind_param("ii", $course_id, $term_id);
    $stmt->execute();
    $subject_count = $stmt->get_result()->fetch_assoc()['count'];

    // Output results
    echo json_encode([
        'success' => true,
        'student_count' => (int)$student_count,
        'subject_count' => (int)$subject_count,
        'course_id' => $course_id,
        'term_id' => $term_id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'student_count' => 0,
        'subject_count' => 0,
        'course_id' => $course_id ?? 0,
        'term_id' => $term_id ?? 0
    ]);
} 