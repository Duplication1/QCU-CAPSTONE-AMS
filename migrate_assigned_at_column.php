<?php
// Migration script to add assigned_at column to issues table if it doesn't exist

require_once __DIR__ . '/config/config.php';

echo "<h2>Migrating assigned_at column for issues table</h2>";

// Establish mysqli database connection
$dbConfig = Config::database();
try {
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die("<p style='color: red;'>Database connection error: " . $e->getMessage() . "</p>");
}

// Check if column exists
$checkResult = $conn->query("SHOW COLUMNS FROM issues LIKE 'assigned_at'");

if ($checkResult->num_rows == 0) {
    echo "<p>Column 'assigned_at' does not exist. Adding it now...</p>";
    
    // Add the column
    $sql = "ALTER TABLE issues ADD COLUMN assigned_at TIMESTAMP NULL DEFAULT NULL AFTER assigned_technician";
    
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Successfully added 'assigned_at' column to issues table</p>";
        
        // Add index for better performance
        $indexSql = "ALTER TABLE issues ADD INDEX idx_assigned_at (assigned_at)";
        if ($conn->query($indexSql)) {
            echo "<p style='color: green;'>✓ Successfully added index on 'assigned_at' column</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Index might already exist or couldn't be created: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Error adding column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ Column 'assigned_at' already exists in issues table</p>";
}

// Verify the column now exists
echo "<h3>Current issues table structure:</h3>";
$result = $conn->query("DESCRIBE issues");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    $highlight = ($row['Field'] === 'assigned_at') ? " style='background-color: #90EE90;'" : "";
    echo "<tr{$highlight}>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();
echo "<h3>Migration completed!</h3>";
echo "<p><a href='view/Administrator/'>Go back to Administrator Dashboard</a></p>";
?>
