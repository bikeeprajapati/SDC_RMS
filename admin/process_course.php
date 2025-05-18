<?php
require_once 'includes/session.php';
requireLogin();

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $required_fields = ['course_code', 'course_name', 'total_years', 'terms_per_year'];
    $is_valid = true;
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $is_valid = false;
            $response['message'] = "All required fields must be filled out";
            break;
        }
    }

    if ($is_valid) {
        try {
            $is_edit = isset($_POST['course_id']) && !empty($_POST['course_id']);
            
            if ($is_edit) {
                $stmt = $conn->prepare("
                    UPDATE courses 
                    SET course_code = ?, course_name = ?, is_semester_system = ?, 
                        total_years = ?, terms_per_year = ?, description = ?, is_active = ?
                    WHERE id = ?
                ");
                
                $is_semester = isset($_POST['is_semester_system']) ? 1 : 0;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $total_years = (int)$_POST['total_years'];
                $terms_per_year = (int)$_POST['terms_per_year'];
                $description = isset($_POST['description']) ? trim($_POST['description']) : '';
                
                $stmt->bind_param("ssiiisii", 
                    $_POST['course_code'],
                    $_POST['course_name'],
                    $is_semester,
                    $total_years,
                    $terms_per_year,
                    $description,
                    $is_active,
                    $_POST['course_id']
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update course: " . $stmt->error);
                }

                // Update academic terms if system type changed
                if ($_POST['original_is_semester'] != $is_semester ||
                    $_POST['original_total_years'] != $total_years ||
                    $_POST['original_terms_per_year'] != $terms_per_year) {
                    
                    // First, deactivate all existing terms
                    $stmt = $conn->prepare("UPDATE academic_terms SET is_active = 0 WHERE course_id = ?");
                    $stmt->bind_param("i", $_POST['course_id']);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to deactivate existing terms: " . $stmt->error);
                    }
                    
                    // Then, create new terms
                    $total_terms = $total_years * $terms_per_year;
                    
                    $stmt = $conn->prepare("
                        INSERT INTO academic_terms (course_id, term_number, term_name, is_active)
                        VALUES (?, ?, ?, 1)
                    ");
                    
                    for ($i = 1; $i <= $total_terms; $i++) {
                        $term_name = $is_semester ? "Semester $i" : "Year " . ceil($i/$terms_per_year);
                        $stmt->bind_param("iis", $_POST['course_id'], $i, $term_name);
                        if (!$stmt->execute()) {
                            throw new Exception("Failed to create term $i: " . $stmt->error);
                        }
                    }
                }

                $response['success'] = true;
                $response['message'] = 'Course updated successfully';
                $response['redirect'] = 'courses.php?msg=updated';
            } else {
                // Insert new course
                $stmt = $conn->prepare("
                    INSERT INTO courses (course_code, course_name, is_semester_system, 
                                       total_years, terms_per_year, description, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $is_semester = isset($_POST['is_semester_system']) ? 1 : 0;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $total_years = (int)$_POST['total_years'];
                $terms_per_year = (int)$_POST['terms_per_year'];
                $description = isset($_POST['description']) ? trim($_POST['description']) : '';
                
                $stmt->bind_param("ssiiisi", 
                    $_POST['course_code'],
                    $_POST['course_name'],
                    $is_semester,
                    $total_years,
                    $terms_per_year,
                    $description,
                    $is_active
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert course: " . $stmt->error);
                }

                // Get the new course ID
                $course_id = $conn->insert_id;
                if (!$course_id) {
                    throw new Exception("Failed to get new course ID");
                }

                // Create academic terms
                $total_terms = $total_years * $terms_per_year;
                
                $stmt = $conn->prepare("
                    INSERT INTO academic_terms (course_id, term_number, term_name, is_active)
                    VALUES (?, ?, ?, 1)
                ");
                
                for ($i = 1; $i <= $total_terms; $i++) {
                    $term_name = $is_semester ? "Semester $i" : "Year " . ceil($i/$terms_per_year);
                    $stmt->bind_param("iis", $course_id, $i, $term_name);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to create term $i: " . $stmt->error);
                    }
                }

                $response['success'] = true;
                $response['message'] = 'Course added successfully';
                $response['redirect'] = 'courses.php?msg=added';
            }
        } catch(Exception $e) {
            error_log("Error saving course: " . $e->getMessage());
            $response['message'] = $is_edit ? 
                "Error updating course: " . $e->getMessage() : 
                "Error adding course: " . $e->getMessage();
        }
    }
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit(); 