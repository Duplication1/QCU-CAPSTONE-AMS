<?php
require 'config/config.php';
$dbConfig = Config::database();
$conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

// Set some categories as PC categories for testing
$pc_categories = ['RAM', 'MONITOR', 'KEYBOARD', 'MOUSE'];
foreach ($pc_categories as $category) {
    $conn->query("UPDATE asset_categories SET is_pc_category = 1 WHERE name = '$category'");
    echo "Set $category as PC category\n";
}
$conn->close();
echo "Done setting PC categories\n";
?>