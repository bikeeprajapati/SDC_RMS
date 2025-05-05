<?php
require_once 'database.php';

// Check if admins table exists
$result = $conn->query("SHOW TABLES LIKE 'admins'");
if ($result->num_rows == 0) {
    echo "Creating admins table...\n";
    
    // Create admins table
    $sql = "CREATE TABLE admins (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        full_name VARCHAR(100),
        role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        remember_token VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Admins table created successfully\n";
        
        // Insert default admin
        $username = 'admin';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO admins (username, password, email, full_name, role) 
                VALUES (?, ?, 'admin@example.com', 'System Administrator', 'super_admin')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        
        if ($stmt->execute()) {
            echo "Default admin user created successfully\n";
        } else {
            echo "Error creating default admin: " . $stmt->error . "\n";
        }
    } else {
        echo "Error creating admins table: " . $conn->error . "\n";
    }
} else {
    echo "Admins table exists. Checking structure...\n";
    
    // Check if required columns exist
    $columns = [
        'id' => 'INT',
        'username' => 'VARCHAR(50)',
        'password' => 'VARCHAR(255)',
        'email' => 'VARCHAR(100)',
        'full_name' => 'VARCHAR(100)',
        'role' => "ENUM('super_admin', 'admin', 'moderator')",
        'status' => "ENUM('active', 'inactive')",
        'last_login' => 'TIMESTAMP',
        'remember_token' => 'VARCHAR(100)',
        'created_at' => 'TIMESTAMP',
        'updated_at' => 'TIMESTAMP'
    ];
    
    $result = $conn->query("SHOW COLUMNS FROM admins");
    $existing_columns = [];
    while ($row = $result->fetch_assoc()) {
        $existing_columns[$row['Field']] = $row['Type'];
    }
    
    foreach ($columns as $column => $type) {
        if (!isset($existing_columns[$column])) {
            echo "Adding missing column: $column\n";
            $sql = "ALTER TABLE admins ADD COLUMN $column $type";
            if ($conn->query($sql) === TRUE) {
                echo "Column $column added successfully\n";
            } else {
                echo "Error adding column $column: " . $conn->error . "\n";
            }
        }
    }
    
    // Check if default admin exists
    $result = $conn->query("SELECT id FROM admins WHERE username = 'admin'");
    if ($result->num_rows == 0) {
        echo "Creating default admin user...\n";
        $username = 'admin';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO admins (username, password, email, full_name, role) 
                VALUES (?, ?, 'admin@example.com', 'System Administrator', 'super_admin')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        
        if ($stmt->execute()) {
            echo "Default admin user created successfully\n";
        } else {
            echo "Error creating default admin: " . $stmt->error . "\n";
        }
    }
}

$conn->close();
echo "Table verification completed!\n";
?> 