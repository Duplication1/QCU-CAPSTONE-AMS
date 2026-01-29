<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection and Buildings Test</h2>";

// Test connection
$conn = new mysqli('localhost', 'root', '', 'ams_database');

if ($conn->connect_error) {
    die('<p style="color: red;">❌ Connection failed: ' . $conn->connect_error . '</p>');
}

echo '<p style="color: green;">✓ Database connected successfully</p>';

// Test if buildings table exists
$result = $conn->query("SHOW TABLES LIKE 'buildings'");
if ($result->num_rows == 0) {
    echo '<p style="color: red;">❌ Buildings table does NOT exist!</p>';
    echo '<p>Run this SQL to create and populate it:</p>';
    echo '<pre>';
    echo "CREATE TABLE buildings (
    id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO buildings (name) VALUES ('IK'), ('IL'), ('IC'), ('KORPHIL');";
    echo '</pre>';
    exit;
}

echo '<p style="color: green;">✓ Buildings table exists</p>';

// Simple count
$result = $conn->query("SELECT COUNT(*) as count FROM buildings");
$row = $result->fetch_assoc();
echo "<p>Buildings count: <strong>{$row['count']}</strong></p>";

// List all buildings
echo "<h3>All Buildings:</h3>";
$result = $conn->query("SELECT * FROM buildings ORDER BY name ASC");
if ($result->num_rows == 0) {
    echo '<p style="color: red;">❌ No buildings found in table!</p>';
    echo '<p>Inserting sample buildings...</p>';
    
    $insertQuery = "INSERT INTO buildings (name, created_at, updated_at) VALUES 
        ('IK', NOW(), NOW()),
        ('IL', NOW(), NOW()),
        ('IC', NOW(), NOW()),
        ('KORPHIL', NOW(), NOW())";
    
    if ($conn->query($insertQuery)) {
        echo '<p style="color: green;">✓ Sample buildings inserted!</p>';
        $result = $conn->query("SELECT * FROM buildings ORDER BY name ASC");
    } else {
        echo '<p style="color: red;">❌ Error inserting: ' . $conn->error . '</p>';
    }
}

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Created At</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['created_at']}</td></tr>";
}
echo "</table>";

// Test the exact query from maintenance.php
echo "<h3>Testing Maintenance Query:</h3>";
$query = "SELECT 
    b.id,
    b.name,
    b.created_at,
    COUNT(DISTINCT r.id) as total_rooms,
    COUNT(DISTINCT CASE WHEN r.next_maintenance_date IS NOT NULL AND r.next_maintenance_date < CURDATE() THEN r.id END) as overdue_maintenance,
    COUNT(DISTINCT CASE WHEN r.next_maintenance_date >= CURDATE() AND r.next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN r.id END) as upcoming_maintenance,
    MIN(r.next_maintenance_date) as earliest_maintenance
FROM buildings b
LEFT JOIN rooms r ON b.id = r.building_id
GROUP BY b.id, b.name, b.created_at
ORDER BY b.name ASC";

$result = $conn->query($query);
if (!$result) {
    echo '<p style="color: red;">❌ Query error: ' . $conn->error . '</p>';
} else {
    echo "<p>Query returned <strong>{$result->num_rows}</strong> rows</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Total Rooms</th><th>Overdue</th><th>Upcoming</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['total_rooms']}</td>";
        echo "<td>{$row['overdue_maintenance']}</td>";
        echo "<td>{$row['upcoming_maintenance']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();

echo '<br><p><a href="view/Technician/maintenance.php">Go to Maintenance Page</a></p>';
?>
