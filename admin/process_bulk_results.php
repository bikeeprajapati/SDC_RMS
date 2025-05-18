<?php
require_once '../config/database.php';
require_once 'includes/session.php';
require_once 'includes/GradeCalculator.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bulk_result_upload.php');
    exit();
}

// Validate file upload
if (!isset($_FILES['result_file']) || $_FILES['result_file']['error'] !== UPLOAD_ERR_OK) {
    header('Location: bulk_result_upload.php?error=no_file');
    exit();
}

// Validate file type
$file_info = pathinfo($_FILES['result_file']['name']);
if ($file_info['extension'] !== 'csv') {
    header('Location: bulk_result_upload.php?error=invalid_format');
    exit();
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Read CSV file
    $file = fopen($_FILES['result_file']['tmp_name'], 'r');
    if (!$file) {
        throw new Exception("Failed to open file");
    }

    // Read and validate course info
    $course_row = fgetcsv($file);
    if (!$course_row || count($course_row) < 2 || $course_row[0] !== 'Course:') {
        throw new Exception("Invalid CSV format: Missing course information");
    }

    // Read and validate term info
    $term_row = fgetcsv($file);
    if (!$term_row || count($term_row) < 2 || $term_row[0] !== 'Term:') {
        throw new Exception("Invalid CSV format: Missing term information");
    }

    // Read and validate exam type info
    $exam_row = fgetcsv($file);
    if (!$exam_row || count($exam_row) < 2 || $exam_row[0] !== 'Exam Type:') {
        throw new Exception("Invalid CSV format: Missing exam type information");
    }

    // Skip empty line
    fgetcsv($file);

    // Get headers
    $headers = fgetcsv($file);
    if (!$headers || count($headers) < 3 || $headers[0] !== 'Roll Number' || $headers[1] !== 'Student Name') {
        throw new Exception("Invalid CSV format: Invalid or missing headers");
    }

    // Process student rows until empty line
    $results = [];
    $row_number = 6; // Start counting from actual data rows
    while (($row = fgetcsv($file)) !== false) {
        // Check if we've reached the metadata section
        if (empty($row[0]) || $row[0] === 'DO NOT MODIFY BELOW THIS LINE') {
            break;
        }

        if (count($row) !== count($headers)) {
            throw new Exception("Row {$row_number}: Column count mismatch. Expected " . count($headers) . " columns, got " . count($row));
        }

        // Validate roll number format
        if (empty($row[0])) {
            throw new Exception("Row {$row_number}: Empty roll number");
        }

        $results[] = $row;
        $row_number++;
    }

    if (empty($results)) {
        throw new Exception("No valid result data found in CSV");
    }

    // Read metadata
    $metadata = [];
    $found_metadata_marker = false;
    while (($row = fgetcsv($file)) !== false) {
        if (empty($row[0])) continue;
        
        if ($row[0] === 'DO NOT MODIFY BELOW THIS LINE') {
            $found_metadata_marker = true;
            continue;
        }

        if (!$found_metadata_marker) {
            continue;
        }
        
        switch ($row[0]) {
            case 'metadata_course_id':
                $metadata['course_id'] = (int)$row[1];
                break;
            case 'metadata_term_id':
                $metadata['term_id'] = (int)$row[1];
                break;
            case 'metadata_exam_type_id':
                $metadata['exam_type_id'] = (int)$row[1];
                break;
            case 'metadata_subject':
                if (!isset($metadata['subjects'])) {
                    $metadata['subjects'] = [];
                }
                if (count($row) < 4) {
                    throw new Exception("Invalid subject metadata format");
                }
                $metadata['subjects'][] = [
                    'id' => (int)$row[1],
                    'code' => $row[2],
                    'max_marks' => (float)$row[3]
                ];
                break;
        }
    }
    fclose($file);

    // Validate metadata
    if (!isset($metadata['course_id']) || $metadata['course_id'] <= 0) {
        throw new Exception("Invalid or missing course ID in metadata");
    }
    if (!isset($metadata['term_id']) || $metadata['term_id'] <= 0) {
        throw new Exception("Invalid or missing term ID in metadata");
    }
    if (!isset($metadata['exam_type_id']) || $metadata['exam_type_id'] <= 0) {
        throw new Exception("Invalid or missing exam type ID in metadata");
    }
    if (empty($metadata['subjects'])) {
        throw new Exception("No subject metadata found");
    }

    // Get student IDs by roll numbers
    $roll_numbers = array_column($results, 0);
    $placeholders = str_repeat('?,', count($roll_numbers) - 1) . '?';
    $stmt_students = $conn->prepare("
        SELECT id, roll_number 
        FROM students 
        WHERE roll_number IN ($placeholders)
        AND course_id = ? AND current_term_id = ?
    ");

    $params = array_merge($roll_numbers, [$metadata['course_id'], $metadata['term_id']]);
    $types = str_repeat('s', count($roll_numbers)) . 'ii';
    $stmt_students->bind_param($types, ...$params);
    $stmt_students->execute();
    $student_result = $stmt_students->get_result();
    
    $students = [];
    while ($row = $student_result->fetch_assoc()) {
        $students[$row['roll_number']] = $row['id'];
    }

    // Validate all students exist before processing
    foreach ($results as $row_index => $row) {
        $roll_number = $row[0];
        if (!isset($students[$roll_number])) {
            throw new Exception("Row " . ($row_index + 6) . ": Student with roll number {$roll_number} not found in the selected course and term");
        }
    }

    // Prepare insert statement
    $stmt = $conn->prepare("
        INSERT INTO results (
            student_id, subject_id, exam_type_id, term_id, 
            marks, grade, grade_point, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            marks = VALUES(marks),
            grade = VALUES(grade),
            grade_point = VALUES(grade_point)
    ");

    // Process each row
    foreach ($results as $row_index => $row) {
        $roll_number = $row[0];
        $student_id = $students[$roll_number];
        $row_num = $row_index + 6; // Actual row number in CSV

        // Process each subject (starting from index 2, after roll number and name)
        for ($i = 0; $i < count($metadata['subjects']); $i++) {
            $subject = $metadata['subjects'][$i];
            $marks_str = $row[$i + 2];

            // Skip if marks field is empty or contains 'EXISTS'
            if (empty($marks_str) || strtoupper($marks_str) === 'EXISTS') {
                continue;
            }

            // Validate marks
            if (!is_numeric($marks_str)) {
                throw new Exception("Row {$row_num}: Invalid marks format for subject {$subject['code']}. Must be a number, got '{$marks_str}'");
            }

            $marks = (float)$marks_str;
            if (!GradeCalculator::validateMarks($marks, $subject['max_marks'])) {
                throw new Exception("Row {$row_num}: Invalid marks for subject {$subject['code']}. Must be between 0 and {$subject['max_marks']}, got {$marks}");
            }

            // Calculate grade
            $grade_info = GradeCalculator::calculate($marks, $subject['max_marks']);

            // Insert or update result
            $stmt->bind_param(
                "iiiiisdi",
                $student_id,
                $subject['id'],
                $metadata['exam_type_id'],
                $metadata['term_id'],
                $marks,
                $grade_info['grade'],
                $grade_info['grade_point'],
                $_SESSION['admin_id']
            );
            $stmt->execute();
        }
    }

    // Commit transaction
    $conn->commit();
    header('Location: bulk_result_upload.php?success=1');
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    // Log the detailed error
    error_log("Error processing bulk results: " . $e->getMessage());
    
    // Send a more specific error message back to the user
    $error_message = urlencode($e->getMessage());
    header("Location: bulk_result_upload.php?error=invalid_data&message={$error_message}");
    exit();
} 