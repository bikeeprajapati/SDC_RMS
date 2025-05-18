<?php
require_once 'config/database.php';

header('Content-Type: application/json');

// Validate input
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid course ID']);
    exit;
}

$course_id = (int)$_GET['course_id'];

try {
    // Fetch academic terms for the course
    $stmt = $conn->prepare("
        SELECT id, term_name 
        FROM academic_terms 
        WHERE course_id = ? AND is_active = 1 
        ORDER BY term_number
    ");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $terms = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($terms);
} catch(Exception $e) {
    error_log("Error in get_academic_terms.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 