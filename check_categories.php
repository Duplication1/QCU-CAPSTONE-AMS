<?php
require 'config/config.php';
$dbConfig = Config::database();
$conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

$result = $conn->query('SELECT name, is_pc_category FROM asset_categories');
echo "Asset Categories and PC status:\n";
while ($row = $result->fetch_assoc()) {
    echo $row['name'] . ' - PC Category: ' . ($row['is_pc_category'] ? 'Yes' : 'No') . "\n";
}
$conn->close();
?>