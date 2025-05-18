<?php
require_once '../config/database.php';
require_once 'includes/session.php';
requireLogin();

header('Content-Type: application/json');

// Validate input parameters
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id']) || 
    !isset($_GET['term_id']) || !is_numeric($_GET['term_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid course ID or term ID']);
    exit;
}

$course_id = (int)$_GET['course_id'];
$term_id = (int)$_GET['term_id'];

try {
    $stmt = $conn->prepare("
        SELECT id, subject_code, subject_name 
        FROM subjects 
        WHERE course_id = ? AND term_id = ?
        ORDER BY subject_code
    ");
    $stmt->bind_param("ii", $course_id, $term_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    
    echo json_encode($subjects);
    
} catch (Exception $e) {
    error_log("Error fetching subjects: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch subjects']);
} 