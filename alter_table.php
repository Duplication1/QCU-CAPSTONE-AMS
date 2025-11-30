<?php
$conn = new mysqli('localhost', 'root', '', 'ams_database');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$sql = 'ALTER TABLE asset_categories ADD COLUMN is_pc_category TINYINT(1) NOT NULL DEFAULT 0';
if ($conn->query($sql) === TRUE) {
    echo 'Column is_pc_category added successfully';
} else {
    echo 'Error adding column: ' . $conn->error;
}
$conn->close();
?>