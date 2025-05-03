<?php
require_once 'database.php';

// Create admins table
$sql = "CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Admins table created successfully<br>";
} else {
    echo "Error creating admins table: " . $conn->error . "<br>";
}

// Create students table
$sql = "CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol_no VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    program VARCHAR(50) NOT NULL,
    semester INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Students table created successfully<br>";
} else {
    echo "Error creating students table: " . $conn->error . "<br>";
}

// Create results table
$sql = "CREATE TABLE IF NOT EXISTS results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol_no VARCHAR(20) NOT NULL,
    semester INT NOT NULL,
    subject1 DECIMAL(5,2) NOT NULL,
    subject2 DECIMAL(5,2) NOT NULL,
    subject3 DECIMAL(5,2) NOT NULL,
    subject4 DECIMAL(5,2) NOT NULL,
    subject5 DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (symbol_no) REFERENCES students(symbol_no)
)";

if ($conn->query($sql) === TRUE) {
    echo "Results table created successfully<br>";
} else {
    echo "Error creating results table: " . $conn->error . "<br>";
}

// Add default admin user if not exists
$username = "admin";
$password = password_hash("admin123", PASSWORD_DEFAULT); // Default password: admin123
$email = "admin@sdc.edu.np";

// Check if admin user already exists
$sql = "SELECT id FROM admins WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Insert default admin user
    $sql = "INSERT INTO admins (username, password, email) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $password, $email);
    
    if ($stmt->execute()) {
        echo "Default admin user created successfully<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Error creating default admin user: " . $stmt->error . "<br>";
    }
} else {
    echo "Default admin user already exists<br>";
}

$conn->close();
?> 