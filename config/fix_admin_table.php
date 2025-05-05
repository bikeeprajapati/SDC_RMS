<?php
require_once 'database.php';

// Check if full_name column exists
$result = $conn->query("SHOW COLUMNS FROM admins LIKE 'full_name'");
if ($result->num_rows == 0) {
    echo "Adding full_name column to admins table...\n";
    
    // Add full_name column
    $sql = "ALTER TABLE admins ADD COLUMN full_name VARCHAR(100) AFTER email";
    if ($conn->query($sql) === TRUE) {
        echo "full_name column added successfully\n";
        
        // Update existing admin records with default full_name
        $sql = "UPDATE admins SET full_name = 'System Administrator' WHERE full_name IS NULL";
        if ($conn->query($sql) === TRUE) {
            echo "Updated existing admin records with default full_name\n";
        } else {
            echo "Error updating admin records: " . $conn->error . "\n";
        }
    } else {
        echo "Error adding full_name column: " . $conn->error . "\n";
    }
} else {
    echo "full_name column already exists\n";
}

// Check if role column exists
$result = $conn->query("SHOW COLUMNS FROM admins LIKE 'role'");
if ($result->num_rows == 0) {
    echo "Adding role column to admins table...\n";
    
    // Add role column
    $sql = "ALTER TABLE admins ADD COLUMN role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin' AFTER full_name";
    if ($conn->query($sql) === TRUE) {
        echo "role column added successfully\n";
        
        // Update existing admin records with default role
        $sql = "UPDATE admins SET role = 'super_admin' WHERE role IS NULL";
        if ($conn->query($sql) === TRUE) {
            echo "Updated existing admin records with default role\n";
        } else {
            echo "Error updating admin records: " . $conn->error . "\n";
        }
    } else {
        echo "Error adding role column: " . $conn->error . "\n";
    }
} else {
    echo "role column already exists\n";
}

$conn->close();
echo "Table fix completed!\n";
?> 