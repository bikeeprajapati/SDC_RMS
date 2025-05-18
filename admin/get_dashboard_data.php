<?php
require_once 'includes/session.php';
requireLogin();

header('Content-Type: application/json');

try {
    $data = [];
    
    // Get total students count
    $result = $conn->query("SELECT COUNT(*) as count FROM students WHERE is_active = 1");
    $data['total_students'] = $result->fetch_assoc()['count'];
    
    // Get total subjects count
    $result = $conn->query("SELECT COUNT(*) as count FROM subjects WHERE is_active = 1");
    $data['total_subjects'] = $result->fetch_assoc()['count'];
    
    // Get total results count
    $result = $conn->query("SELECT COUNT(*) as count FROM results");
    $data['total_results'] = $result->fetch_assoc()['count'];
    
    // Get results by grade distribution
    $result = $conn->query("
        SELECT grade, COUNT(*) as count 
        FROM results 
        GROUP BY grade 
        ORDER BY grade
    ");
    $data['grade_distribution'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Get recent results (last 5)
    $result = $conn->query("
        SELECT r.*, 
            s.roll_number,
            s.full_name as student_name,
            sub.subject_code,
            sub.subject_name,
            et.type_name as exam_type
        FROM results r
        JOIN students s ON r.student_id = s.id
        JOIN subjects sub ON r.subject_id = sub.id
        JOIN exam_types et ON r.exam_type_id = et.id
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $data['recent_results'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Get top performers (students with highest average grade points)
    $result = $conn->query("
        SELECT 
            s.roll_number,
            s.full_name as student_name,
            COUNT(*) as total_exams,
            ROUND(AVG(r.grade_point), 2) as avg_grade_point
        FROM results r
        JOIN students s ON r.student_id = s.id
        GROUP BY r.student_id
        HAVING total_exams >= 3
        ORDER BY avg_grade_point DESC
        LIMIT 5
    ");
    $data['top_performers'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Get subject-wise performance
    $result = $conn->query("
        SELECT 
            sub.subject_code,
            sub.subject_name,
            COUNT(*) as total_results,
            ROUND(AVG(r.marks/sub.max_marks * 100), 2) as avg_percentage,
            ROUND(AVG(r.grade_point), 2) as avg_grade_point
        FROM results r
        JOIN subjects sub ON r.subject_id = sub.id
        GROUP BY r.subject_id
        ORDER BY avg_grade_point DESC
    ");
    $data['subject_performance'] = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching dashboard data'
    ]);
} 