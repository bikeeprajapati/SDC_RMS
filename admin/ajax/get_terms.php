<?php
require_once '../includes/session.php';
requireLogin();

// Set content type to JSON before any output
header('Content-Type: application/json');

// Validate course ID
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid course ID']);
    exit();
}

$course_id = (int)$_GET['course_id'];
$terms = [];

try {
    // Fetch active terms for the selected course
    $stmt = $conn->prepare("
        SELECT id, term_name, term_number
        FROM academic_terms
        WHERE course_id = ? AND is_active = 1
        ORDER BY term_number
    ");
    
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $terms[] = [
            'id' => $row['id'],
            'term_name' => $row['term_name'],
            'term_number' => $row['term_number']
        ];
    }
    
    echo json_encode(['terms' => $terms]);
    
} catch (Exception $e) {
    error_log("Error fetching terms: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
} 