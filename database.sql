-- Create database
CREATE DATABASE IF NOT EXISTS rms_db;
USE rms_db;

-- Create students table
CREATE TABLE IF NOT EXISTS students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    roll_number VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create results table
CREATE TABLE IF NOT EXISTS results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    roll_number VARCHAR(20) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    marks INT NOT NULL,
    grade VARCHAR(2) NOT NULL,
    semester INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (roll_number) REFERENCES students(roll_number)
);

-- Create admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample admin
INSERT INTO admins (username, password) VALUES 
('admin', '$2y$10$8K1p/a0dR1U5bN1QzK1Y3O1QzK1Y3O1QzK1Y3O1QzK1Y3O1QzK1Y3');

-- Insert sample students
INSERT INTO students (roll_number, name) VALUES
('2024001', 'John Doe'),
('2024002', 'Jane Smith'),
('2024003', 'Mike Johnson');

-- Insert sample results
INSERT INTO results (roll_number, subject, marks, grade, semester) VALUES
('2024001', 'Mathematics', 85, 'A', 1),
('2024001', 'Physics', 78, 'B', 1),
('2024002', 'Mathematics', 92, 'A+', 1),
('2024002', 'Physics', 88, 'A', 1),
('2024003', 'Mathematics', 75, 'B', 1),
('2024003', 'Physics', 82, 'A', 1); 