<?php
require_once 'config/database.php';

echo "Creating Tables and Inserting Data...\n";

// Create semesters table
$sql = "CREATE TABLE IF NOT EXISTS `semesters` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✓ Semesters table created successfully\n";
} else {
    die("Error creating semesters table: " . $conn->error . "\n");
}

// Create years table
$sql = "CREATE TABLE IF NOT EXISTS `years` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✓ Years table created successfully\n";
} else {
    die("Error creating years table: " . $conn->error . "\n");
}

// Create sections table
$sql = "CREATE TABLE IF NOT EXISTS `sections` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✓ Sections table created successfully\n";
} else {
    die("Error creating sections table: " . $conn->error . "\n");
}

// Insert default data into semesters
$semesters = [
    'First Semester',
    'Second Semester',
    'Third Semester',
    'Fourth Semester',
    'Fifth Semester',
    'Sixth Semester',
    'Seventh Semester',
    'Eighth Semester'
];

foreach ($semesters as $semester) {
    $sql = "INSERT INTO semesters (name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $semester);
    if ($stmt->execute()) {
        echo "✓ Added semester: $semester\n";
    } else {
        echo "Error adding semester $semester: " . $stmt->error . "\n";
    }
}

// Insert default data into years
$years = [
    'First Year',
    'Second Year',
    'Third Year',
    'Fourth Year'
];

foreach ($years as $year) {
    $sql = "INSERT INTO years (name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $year);
    if ($stmt->execute()) {
        echo "✓ Added year: $year\n";
    } else {
        echo "Error adding year $year: " . $stmt->error . "\n";
    }
}

// Insert default data into sections
$sections = [
    'Section A',
    'Section B',
    'Section C',
    'Section D'
];

foreach ($sections as $section) {
    $sql = "INSERT INTO sections (name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $section);
    if ($stmt->execute()) {
        echo "✓ Added section: $section\n";
    } else {
        echo "Error adding section $section: " . $stmt->error . "\n";
    }
}

echo "\nVerifying data...\n";

// Verify semesters
$sql = "SELECT COUNT(*) as count FROM semesters";
$result = $conn->query($sql);
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "✓ Semesters table has $count records\n";
}

// Verify years
$sql = "SELECT COUNT(*) as count FROM years";
$result = $conn->query($sql);
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "✓ Years table has $count records\n";
}

// Verify sections
$sql = "SELECT COUNT(*) as count FROM sections";
$result = $conn->query($sql);
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "✓ Sections table has $count records\n";
}

echo "\nSetup completed! You can now access the dashboard.\n";
?> 