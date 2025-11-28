<?php
$conn = new mysqli('localhost', 'root', '', 'ams_database');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Create asset_categories table
$sql = "CREATE TABLE IF NOT EXISTS asset_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo 'asset_categories table created successfully\n';
} else {
    echo 'Error creating table: ' . $conn->error . '\n';
}

// Insert default categories
$categories = ['LAPTOP', 'PC', 'RAM', 'MONITOR', 'KEYBOARD', 'MOUSE', 'PRINTER', 'PROJECTOR', 'ROUTER', 'SWITCH', 'SERVER', 'STORAGE', 'CABLE', 'ADAPTER', 'OTHER'];
foreach ($categories as $category) {
    $stmt = $conn->prepare('INSERT IGNORE INTO asset_categories (name) VALUES (?)');
    $stmt->bind_param('s', $category);
    $stmt->execute();
    $stmt->close();
}
echo 'Default categories inserted\n';

$conn->close();
?>