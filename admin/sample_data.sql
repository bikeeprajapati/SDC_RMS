-- Insert sample sections
INSERT INTO `sections` (`name`, `status`) VALUES 
('Section A', 'active'),
('Section B', 'active'),
('Section C', 'active');

-- Insert sample subjects
INSERT INTO `subjects` (`subject_code`, `subject_name`, `description`, `units`, `course_id`, `year_level`, `semester`, `status`) VALUES 
('MATH101', 'Mathematics 1', 'Basic mathematics', 3, 1, '1st Year', '1st Semester', 'active'),
('ENG101', 'English 1', 'Basic English', 3, 1, '1st Year', '1st Semester', 'active'),
('SCI101', 'Science 1', 'Basic science', 3, 1, '1st Year', '1st Semester', 'active');

-- Insert sample students
INSERT INTO `students` (`student_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `course`, `year_level`, `semester_id`, `year_id`, `section_id`, `status`) VALUES 
('STU001', 'John', 'Doe', 'john@example.com', '1234567890', '123 Main St', 'BSIT', '1st Year', 1, 1, 1, 'active'),
('STU002', 'Jane', 'Smith', 'jane@example.com', '0987654321', '456 Oak Ave', 'BSIT', '1st Year', 1, 1, 2, 'active'),
('STU003', 'Bob', 'Johnson', 'bob@example.com', '1122334455', '789 Pine St', 'BSIT', '1st Year', 1, 1, 3, 'active');
