<?php
$servername = "localhost";
$username = "root";
$password = "";

// Create connection without database
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read and execute the SQL file
$sql = file_get_contents('../database.sql');

// Execute multi query
if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    
    echo "Database and tables created successfully!<br>";
    echo "You can now login with:<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
} else {
    echo "Error importing database: " . $conn->error;
}

$conn->close();
?> 