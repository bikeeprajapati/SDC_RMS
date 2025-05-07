<?php
class DashboardController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getDashboardStats() {
        $stats = [
            'total_students' => $this->getTotalStudents(),
            'total_classes' => $this->getTotalClasses(),
            'total_subjects' => $this->getTotalSubjects(),
            'recent_activities' => $this->getRecentActivities()
        ];
        return $stats;
    }

    private function getTotalStudents() {
        $sql = "SELECT COUNT(*) as total FROM students WHERE status = 'active'";
        $result = $this->conn->query($sql);
        return $result->fetch_assoc()['total'];
    }

    private function getTotalClasses() {
        $sql = "SELECT COUNT(*) as total FROM sections WHERE status = 'active'";
        $result = $this->conn->query($sql);
        return $result->fetch_assoc()['total'];
    }

    private function getTotalSubjects() {
        $sql = "SELECT COUNT(*) as total FROM subjects WHERE status = 'active'";
        $result = $this->conn->query($sql);
        return $result->fetch_assoc()['total'];
    }

    private function getRecentActivities() {
        $sql = "SELECT a.*, u.full_name 
                FROM activity_logs a 
                LEFT JOIN admin u ON a.admin_id = u.id 
                ORDER BY a.created_at DESC 
                LIMIT 5";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // CSV Import functionality
    public function importResults($file) {
        $errors = [];
        $success = 0;
        
        if (($handle = fopen($file, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                try {
                    $this->processCSVRow($data);
                    $success++;
                } catch (Exception $e) {
                    $errors[] = "Error processing row: " . $e->getMessage();
                }
            }
            fclose($handle);
        }
        
        return [
            'success' => $success,
            'errors' => $errors
        ];
    }

    private function processCSVRow($data) {
        // Assuming CSV format: student_id, subject_code, grade
        $student_id = $data[0];
        $subject_code = $data[1];
        $grade = $data[2];
        
        // Get student and subject IDs
        $student_sql = "SELECT id FROM students WHERE student_id = ?";
        $subject_sql = "SELECT id FROM subjects WHERE subject_code = ?";
        
        $stmt = $this->conn->prepare($student_sql);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $student_result = $stmt->get_result();
        $student = $student_result->fetch_assoc();
        
        $stmt = $this->conn->prepare($subject_sql);
        $stmt->bind_param("s", $subject_code);
        $stmt->execute();
        $subject_result = $stmt->get_result();
        $subject = $subject_result->fetch_assoc();
        
        if (!$student || !$subject) {
            throw new Exception("Invalid student or subject");
        }
        
        // Get or create enrollment
        $enrollment_sql = "INSERT INTO enrollments (student_id, subject_id, academic_year, semester) 
                          SELECT ?, ?, YEAR(CURDATE()), CASE 
                              WHEN MONTH(CURDATE()) <= 6 THEN '1st Semester' 
                              ELSE '2nd Semester' 
                          END 
                          WHERE NOT EXISTS (
                              SELECT 1 FROM enrollments 
                              WHERE student_id = ? 
                              AND subject_id = ? 
                              AND academic_year = YEAR(CURDATE())
                          )";
        
        $stmt = $this->conn->prepare($enrollment_sql);
        $stmt->bind_param("iii", $student['id'], $subject['id'], $student['id'], $subject['id']);
        $stmt->execute();
        
        // Get enrollment ID
        $enrollment_id = $stmt->insert_id;
        
        // Insert grade
        $grade_sql = "INSERT INTO grades (enrollment_id, final_grade) VALUES (?, ?)";
        $stmt = $this->conn->prepare($grade_sql);
        $stmt->bind_param("id", $enrollment_id, $grade);
        $stmt->execute();
    }
}
