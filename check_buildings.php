<?php
// Check and populate buildings table
$conn = new mysqli('localhost', 'root', '', 'ams_database');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "<h2>Buildings Status Check</h2>";

// Check if buildings table exists
$result = $conn->query("SHOW TABLES LIKE 'buildings'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ Buildings table does NOT exist!</p>";
    echo "<p>Please import the database: database/ams_database (1).sql</p>";
    exit;
}

echo "<p style='color: green;'>✓ Buildings table exists</p>";

// Check if buildings have data
$result = $conn->query("SELECT * FROM buildings ORDER BY name ASC");
$count = $result->num_rows;

echo "<p>Total buildings in database: <strong>$count</strong></p>";

if ($count == 0) {
    echo "<p style='color: orange;'>⚠ No buildings found. Adding sample buildings...</p>";
    
    $buildings = [
        ['IK', 'Main IT Building'],
        ['IL', 'Laboratory Building'],
        ['IC', 'Computer Science Building'],
        ['KORPHIL', 'Multipurpose Building']
    ];
    
    $stmt = $conn->prepare("INSERT INTO buildings (name, created_at, updated_at) VALUES (?, NOW(), NOW())");
    
    foreach ($buildings as $building) {
        $stmt->bind_param("s", $building[0]);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Added building: {$building[0]}</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add: {$building[0]}</p>";
        }
    }
    
    $stmt->close();
    
    echo "<br><p style='color: green; font-weight: bold;'>✓ Sample buildings added successfully!</p>";
    echo "<p><a href='view/Technician/maintenance.php'>Go to Maintenance Page</a></p>";
} else {
    echo "<h3>Existing Buildings:</h3>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>ID: {$row['id']} - {$row['name']} (Created: {$row['created_at']})</li>";
    }
    echo "</ul>";
    echo "<p><a href='view/Technician/maintenance.php'>Go to Maintenance Page</a></p>";
}

// Check rooms count
$result = $conn->query("SELECT COUNT(*) as count FROM rooms");
$rooms = $result->fetch_assoc();
echo "<p>Total rooms in database: <strong>{$rooms['count']}</strong></p>";

$conn->close();
?>
