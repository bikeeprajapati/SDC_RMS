<?php
require_once 'includes/session.php';
require_once 'includes/GradeCalculator.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['marks']) || !isset($_GET['max_marks']) || 
    !GradeCalculator::validateMarks($_GET['marks'], $_GET['max_marks'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid marks or max marks'
    ]);
    exit();
}

$marks = floatval($_GET['marks']);
$max_marks = floatval($_GET['max_marks']);
$result = GradeCalculator::calculate($marks, $max_marks);
$result['success'] = true;
$result['remarks'] = GradeCalculator::getRemarks($result['grade']);

echo json_encode($result); 