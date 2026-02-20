<?php
// Migration script to add assigned_by column to issues table if it doesn't exist

require_once __DIR__ . '/config/config.php';

echo "<h2>Migrating assigned_by column for issues table</h2>";

// Establish mysqli database connection
$dbConfig = Config::database();
try {
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die("<p style='color: red;'>Database connection error: " . $e->getMessage() . "</p>");
}

// Check if column exists
$checkResult = $conn->query("SHOW COLUMNS FROM issues LIKE 'assigned_by'");

if ($checkResult->num_rows == 0) {
    echo "<p>Column 'assigned_by' does not exist. Adding it now...</p>";
    
    // Add the column
    $sql = "ALTER TABLE issues ADD COLUMN assigned_by INT(11) NULL DEFAULT NULL AFTER assigned_at";
    
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Successfully added 'assigned_by' column to issues table</p>";
        
        // Add index for better performance
        $indexSql = "ALTER TABLE issues ADD INDEX idx_assigned_by (assigned_by)";
        if ($conn->query($indexSql)) {
            echo "<p style='color: green;'>✓ Successfully added index on 'assigned_by' column</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Index might already exist or couldn't be created: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Error adding column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ Column 'assigned_by' already exists in issues table</p>";
}

// Verify the column now exists
echo "<h3>Relevant columns in issues table:</h3>";
$result = $conn->query("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
                        FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_NAME = 'issues' 
                        AND TABLE_SCHEMA = '{$dbConfig['name']}'
                        AND COLUMN_NAME IN ('assigned_technician', 'assigned_at', 'assigned_by')
                        ORDER BY ORDINAL_POSITION");
                        
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['COLUMN_NAME']}</td>";
    echo "<td>{$row['COLUMN_TYPE']}</td>";
    echo "<td>{$row['IS_NULLABLE']}</td>";
    echo "<td>" . ($row['COLUMN_DEFAULT'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();
echo "<h3>Migration completed!</h3>";
echo "<p><a href='view/Administrator/'>Go back to Administrator Dashboard</a></p>";
?>
