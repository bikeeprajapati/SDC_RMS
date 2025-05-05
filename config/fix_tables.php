<?php
require_once 'database.php';

// Function to create a table if it doesn't exist
function createTableIfNotExists($conn, $tableName, $createQuery) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($result->num_rows == 0) {
        echo "Creating $tableName table...\n";
        if ($conn->query($createQuery) === TRUE) {
            echo "$tableName table created successfully\n";
            return true;
        } else {
            echo "Error creating $tableName table: " . $conn->error . "\n";
            return false;
        }
    } else {
        echo "$tableName table already exists\n";
        return true;
    }
}

// Drop and recreate students table to ensure correct structure
$conn->query("DROP TABLE IF EXISTS students");

// Create students table with correct structure
$students_table = "CREATE TABLE IF NOT EXISTS students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    roll_number VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    class_id INT,
    semester_id INT,
    year_id INT,
    section_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (semester_id) REFERENCES semesters(id),
    FOREIGN KEY (year_id) REFERENCES years(id),
    FOREIGN KEY (section_id) REFERENCES sections(id)
)";
createTableIfNotExists($conn, 'students', $students_table);

// Create semesters table
$semesters_table = "CREATE TABLE IF NOT EXISTS semesters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
createTableIfNotExists($conn, 'semesters', $semesters_table);

// Create years table
$years_table = "CREATE TABLE IF NOT EXISTS years (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
createTableIfNotExists($conn, 'years', $years_table);

// Create sections table
$sections_table = "CREATE TABLE IF NOT EXISTS sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
createTableIfNotExists($conn, 'sections', $sections_table);

// Create classes table
$classes_table = "CREATE TABLE IF NOT EXISTS classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    semester_id INT,
    year_id INT,
    section_id INT,
    capacity INT DEFAULT 40,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (semester_id) REFERENCES semesters(id),
    FOREIGN KEY (year_id) REFERENCES years(id),
    FOREIGN KEY (section_id) REFERENCES sections(id)
)";
createTableIfNotExists($conn, 'classes', $classes_table);

// Create courses table
$courses_table = "CREATE TABLE IF NOT EXISTS courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    credits INT NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
createTableIfNotExists($conn, 'courses', $courses_table);

// Create subjects table
$subjects_table = "CREATE TABLE IF NOT EXISTS subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    credits INT NOT NULL,
    theory_marks INT DEFAULT 60,
    practical_marks INT DEFAULT 40,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id)
)";
createTableIfNotExists($conn, 'subjects', $subjects_table);

// Create enrollments table
$enrollments_table = "CREATE TABLE IF NOT EXISTS enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    subject_id INT,
    semester_id INT,
    year_id INT,
    enrollment_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (semester_id) REFERENCES semesters(id),
    FOREIGN KEY (year_id) REFERENCES years(id)
)";
createTableIfNotExists($conn, 'enrollments', $enrollments_table);

// Create grades table
$grades_table = "CREATE TABLE IF NOT EXISTS grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT,
    midterm_marks DECIMAL(5,2),
    final_marks DECIMAL(5,2),
    assignment_marks DECIMAL(5,2),
    practical_marks DECIMAL(5,2),
    total_marks DECIMAL(5,2),
    grade_point DECIMAL(3,2),
    letter_grade VARCHAR(2),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    verified_by INT,
    verified_at TIMESTAMP NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id),
    FOREIGN KEY (verified_by) REFERENCES admins(id)
)";
createTableIfNotExists($conn, 'grades', $grades_table);

// Create grade_points table
$grade_points_table = "CREATE TABLE IF NOT EXISTS grade_points (
    id INT PRIMARY KEY AUTO_INCREMENT,
    min_marks DECIMAL(5,2),
    max_marks DECIMAL(5,2),
    grade_point DECIMAL(3,2),
    letter_grade VARCHAR(2),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
createTableIfNotExists($conn, 'grade_points', $grade_points_table);

// Create notices table
$notices_table = "CREATE TABLE IF NOT EXISTS notices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    type ENUM('general', 'exam', 'result', 'important') DEFAULT 'general',
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id)
)";
createTableIfNotExists($conn, 'notices', $notices_table);

// Create audit_logs table
$audit_logs_table = "CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    user_type ENUM('admin', 'student') NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
createTableIfNotExists($conn, 'audit_logs', $audit_logs_table);

// Insert default data for all tables
echo "\nInserting default data...\n";

// Insert default semester
$result = $conn->query("SELECT id FROM semesters LIMIT 1");
if ($result->num_rows == 0) {
    $sql = "INSERT INTO semesters (name, start_date, end_date, status) 
            VALUES ('Fall 2023', '2023-09-01', '2023-12-31', 'active')";
    if ($conn->query($sql) === TRUE) {
        echo "Default semester created successfully\n";
    } else {
        echo "Error creating default semester: " . $conn->error . "\n";
    }
}

// Insert default year
$result = $conn->query("SELECT id FROM years LIMIT 1");
if ($result->num_rows == 0) {
    $sql = "INSERT INTO years (name, status) VALUES ('2023', 'active')";
    if ($conn->query($sql) === TRUE) {
        echo "Default year created successfully\n";
    } else {
        echo "Error creating default year: " . $conn->error . "\n";
    }
}

// Insert default section
$result = $conn->query("SELECT id FROM sections LIMIT 1");
if ($result->num_rows == 0) {
    $sql = "INSERT INTO sections (name, status) VALUES ('A', 'active')";
    if ($conn->query($sql) === TRUE) {
        echo "Default section created successfully\n";
    } else {
        echo "Error creating default section: " . $conn->error . "\n";
    }
}

// Insert default course
$result = $conn->query("SELECT id FROM courses LIMIT 1");
if ($result->num_rows == 0) {
    $sql = "INSERT INTO courses (code, course_name, credits, description, status) 
            VALUES ('BIM', 'Bachelor of Information Management', 120, 'Bachelor of Information Management Program', 'active')";
    if ($conn->query($sql) === TRUE) {
        echo "Default course created successfully\n";
    } else {
        echo "Error creating default course: " . $conn->error . "\n";
    }
}

// Insert default subject
$result = $conn->query("SELECT id FROM subjects LIMIT 1");
if ($result->num_rows == 0) {
    $sql = "INSERT INTO subjects (course_id, subject_code, subject_name, credits, theory_marks, practical_marks, status) 
            VALUES (1, 'BIM101', 'Introduction to Information Management', 3, 60, 40, 'active')";
    if ($conn->query($sql) === TRUE) {
        echo "Default subject created successfully\n";
    } else {
        echo "Error creating default subject: " . $conn->error . "\n";
    }
}

// Insert default student
$result = $conn->query("SELECT id FROM students LIMIT 1");
if ($result->num_rows == 0) {
    $sql = "INSERT INTO students (roll_number, first_name, last_name, email, phone, gender, class_id, semester_id, year_id, section_id, status) 
            VALUES ('2023001', 'John', 'Doe', 'john.doe@example.com', '1234567890', 'male', 1, 1, 1, 1, 'active')";
    if ($conn->query($sql) === TRUE) {
        echo "Default student created successfully\n";
    } else {
        echo "Error creating default student: " . $conn->error . "\n";
    }
}

// Insert default grade points
$result = $conn->query("SELECT id FROM grade_points LIMIT 1");
if ($result->num_rows == 0) {
    $grade_points = [
        ['min' => 90, 'max' => 100, 'point' => 4.0, 'grade' => 'A+'],
        ['min' => 80, 'max' => 89, 'point' => 3.7, 'grade' => 'A'],
        ['min' => 70, 'max' => 79, 'point' => 3.3, 'grade' => 'B+'],
        ['min' => 60, 'max' => 69, 'point' => 3.0, 'grade' => 'B'],
        ['min' => 50, 'max' => 59, 'point' => 2.7, 'grade' => 'C+'],
        ['min' => 40, 'max' => 49, 'point' => 2.0, 'grade' => 'C'],
        ['min' => 0, 'max' => 39, 'point' => 0.0, 'grade' => 'F']
    ];
    
    foreach ($grade_points as $gp) {
        $sql = "INSERT INTO grade_points (min_marks, max_marks, grade_point, letter_grade, status) 
                VALUES ({$gp['min']}, {$gp['max']}, {$gp['point']}, '{$gp['grade']}', 'active')";
        if ($conn->query($sql) === TRUE) {
            echo "Grade point {$gp['grade']} created successfully\n";
        } else {
            echo "Error creating grade point {$gp['grade']}: " . $conn->error . "\n";
        }
    }
}

// Insert default notice
$result = $conn->query("SELECT id FROM notices LIMIT 1");
if ($result->num_rows == 0) {
    $sql = "INSERT INTO notices (title, content, type, start_date, end_date, status, created_by) 
            VALUES ('Welcome to RMS', 'Welcome to the Result Management System', 'general', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active', 1)";
    if ($conn->query($sql) === TRUE) {
        echo "Default notice created successfully\n";
    } else {
        echo "Error creating default notice: " . $conn->error . "\n";
    }
}

// Insert default audit log
$result = $conn->query("SELECT id FROM audit_logs LIMIT 1");
if ($result->num_rows == 0) {
    $sql = "INSERT INTO audit_logs (user_id, user_type, action, details, ip_address) 
            VALUES (1, 'admin', 'System Initialization', 'System initialized with default data', '127.0.0.1')";
    if ($conn->query($sql) === TRUE) {
        echo "Default audit log created successfully\n";
    } else {
        echo "Error creating default audit log: " . $conn->error . "\n";
    }
}

$conn->close();
echo "\nTable verification, creation, and default data insertion completed!\n";
?> 