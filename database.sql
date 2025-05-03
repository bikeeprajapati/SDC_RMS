-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS sdc_rms;

-- Use the database
USE sdc_rms;

-- Admin table
CREATE TABLE IF NOT EXISTS `admin` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `last_login` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password resets table
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `admin_id` INT NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `expiry` DATETIME NOT NULL,
    `used` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `admin`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students table
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `student_id` VARCHAR(20) NOT NULL UNIQUE,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `course` VARCHAR(100) NOT NULL,
    `year_level` ENUM('1st Year', '2nd Year', '3rd Year', '4th Year') NOT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Courses table
CREATE TABLE IF NOT EXISTS `courses` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `course_code` VARCHAR(20) NOT NULL UNIQUE,
    `course_name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `units` INT NOT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subjects table
CREATE TABLE IF NOT EXISTS `subjects` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `subject_code` VARCHAR(20) NOT NULL UNIQUE,
    `subject_name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `units` INT NOT NULL,
    `course_id` INT NOT NULL,
    `year_level` ENUM('1st Year', '2nd Year', '3rd Year', '4th Year') NOT NULL,
    `semester` ENUM('1st Semester', '2nd Semester') NOT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enrollments table
CREATE TABLE IF NOT EXISTS `enrollments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `student_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `academic_year` VARCHAR(20) NOT NULL,
    `semester` ENUM('1st Semester', '2nd Semester') NOT NULL,
    `status` ENUM('enrolled', 'dropped', 'completed') NOT NULL DEFAULT 'enrolled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grades table
CREATE TABLE IF NOT EXISTS `grades` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `enrollment_id` INT NOT NULL,
    `midterm_grade` DECIMAL(5,2) DEFAULT NULL,
    `final_grade` DECIMAL(5,2) DEFAULT NULL,
    `remarks` ENUM('Passed', 'Failed', 'Incomplete') DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs table
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `admin_id` INT NOT NULL,
    `action` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `admin`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create semesters table
CREATE TABLE IF NOT EXISTS `semesters` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create years table
CREATE TABLE IF NOT EXISTS `years` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create sections table
CREATE TABLE IF NOT EXISTS `sections` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add semester_id, year_id, and section_id to students table
ALTER TABLE `students` 
ADD COLUMN `semester_id` INT,
ADD COLUMN `year_id` INT,
ADD COLUMN `section_id` INT,
ADD FOREIGN KEY (`semester_id`) REFERENCES `semesters`(`id`),
ADD FOREIGN KEY (`year_id`) REFERENCES `years`(`id`),
ADD FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`);

-- Insert default admin user (password: admin123)
INSERT INTO `admin` (`username`, `password`, `email`, `full_name`, `role`) 
VALUES ('admin', '$2y$10$YourNewHashHere', 'admin@example.com', 'System Administrator', 'super_admin')
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Insert sample course
INSERT INTO `courses` (`course_code`, `course_name`, `description`, `units`) 
VALUES ('BSIT', 'Bachelor of Science in Information Technology', 'A four-year degree program focusing on IT', 144)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Insert sample subject
INSERT INTO `subjects` (`subject_code`, `subject_name`, `description`, `units`, `course_id`, `year_level`, `semester`) 
VALUES ('IT101', 'Introduction to Programming', 'Basic programming concepts', 3, 1, '1st Year', '1st Semester')
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Insert default data
INSERT INTO `semesters` (`name`) VALUES 
('First Semester'),
('Second Semester'),
('Third Semester'),
('Fourth Semester'),
('Fifth Semester'),
('Sixth Semester'),
('Seventh Semester'),
('Eighth Semester');

INSERT INTO `years` (`name`) VALUES 
('First Year'),
('Second Year'),
('Third Year'),
('Fourth Year');

INSERT INTO `sections` (`name`) VALUES 
('Section A'),
('Section B'),
('Section C'),
('Section D'); 