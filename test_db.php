<?php
require_once 'config/database.php';

echo "Testing Database Connection...\n";

// Test database connection
if ($conn->ping()) {
    echo "✓ Database connection successful\n";
} else {
    die("✗ Database connection failed: " . $conn->error . "\n");
}

// Test if database exists
$result = $conn->query("SELECT DATABASE()");
if ($result) {
    $dbname = $result->fetch_row()[0];
    echo "✓ Connected to database: $dbname\n";
} else {
    die("✗ Error getting database name: " . $conn->error . "\n");
}

// Test if tables exist
$tables = ['semesters', 'years', 'sections'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✓ Table '$table' exists\n";
        
        // Check table structure
        $result = $conn->query("DESCRIBE $table");
        if ($result) {
            echo "  Table structure:\n";
            while ($row = $result->fetch_assoc()) {
                echo "  - {$row['Field']}: {$row['Type']}\n";
            }
        } else {
            echo "  ✗ Error getting table structure: " . $conn->error . "\n";
        }
        
        // Check if table has data
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "  ✓ Table has $count records\n";
        } else {
            echo "  ✗ Error counting records: " . $conn->error . "\n";
        }
    } else {
        echo "✗ Table '$table' does not exist\n";
    }
}

// Test specific queries
echo "\nTesting Specific Queries...\n";

// Test semesters query
$sql = "SELECT * FROM semesters WHERE status = 'active' ORDER BY name";
$stmt = $conn->prepare($sql);
if ($stmt) {
    echo "✓ Semesters query prepared successfully\n";
    if ($stmt->execute()) {
        echo "✓ Semesters query executed successfully\n";
        $result = $stmt->get_result();
        echo "  Found " . $result->num_rows . " active semesters\n";
    } else {
        echo "✗ Error executing semesters query: " . $stmt->error . "\n";
    }
} else {
    echo "✗ Error preparing semesters query: " . $conn->error . "\n";
}

// Test years query
$sql = "SELECT * FROM years WHERE status = 'active' ORDER BY name";
$stmt = $conn->prepare($sql);
if ($stmt) {
    echo "✓ Years query prepared successfully\n";
    if ($stmt->execute()) {
        echo "✓ Years query executed successfully\n";
        $result = $stmt->get_result();
        echo "  Found " . $result->num_rows . " active years\n";
    } else {
        echo "✗ Error executing years query: " . $stmt->error . "\n";
    }
} else {
    echo "✗ Error preparing years query: " . $conn->error . "\n";
}

// Test sections query
$sql = "SELECT * FROM sections WHERE status = 'active' ORDER BY name";
$stmt = $conn->prepare($sql);
if ($stmt) {
    echo "✓ Sections query prepared successfully\n";
    if ($stmt->execute()) {
        echo "✓ Sections query executed successfully\n";
        $result = $stmt->get_result();
        echo "  Found " . $result->num_rows . " active sections\n";
    } else {
        echo "✗ Error executing sections query: " . $stmt->error . "\n";
    }
} else {
    echo "✗ Error preparing sections query: " . $conn->error . "\n";
}

echo "\nTest completed!\n";
?> 