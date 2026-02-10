<?php
// Quick test to check if assigned_at columns exist

$conn = new mysqli('127.0.0.1', 'root', '', 'ams_database');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Checking assigned_at columns...</h2>";

// Check issues table
echo "<h3>Issues Table:</h3>";
$result = $conn->query("DESCRIBE issues");
$has_assigned_at = false;
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Default']}</td></tr>";
    if ($row['Field'] === 'assigned_at') {
        $has_assigned_at = true;
    }
}
echo "</table>";
echo $has_assigned_at ? "<p style='color:green'>✓ assigned_at column EXISTS in issues table</p>" : "<p style='color:red'>✗ assigned_at column MISSING in issues table</p>";

// Check maintenance_schedules table
echo "<h3>Maintenance Schedules Table:</h3>";
$result = $conn->query("DESCRIBE maintenance_schedules");
$has_assigned_at = false;
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Default']}</td></tr>";
    if ($row['Field'] === 'assigned_at') {
        $has_assigned_at = true;
    }
}
echo "</table>";
echo $has_assigned_at ? "<p style='color:green'>✓ assigned_at column EXISTS in maintenance_schedules table</p>" : "<p style='color:red'>✗ assigned_at column MISSING in maintenance_schedules table</p>";

$conn->close();
?>
