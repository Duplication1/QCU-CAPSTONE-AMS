<?php
/**
 * Add component_asset_id column to issues table
 * This allows tracking which specific hardware component asset the issue is related to
 */

require_once __DIR__ . '/config/config.php';

// Database connection
$dbConfig = Config::database();
$conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if column already exists
$checkQuery = "SHOW COLUMNS FROM `issues` LIKE 'component_asset_id'";
$result = $conn->query($checkQuery);

if ($result->num_rows > 0) {
    echo "Column 'component_asset_id' already exists in 'issues' table.\n";
} else {
    // Add the column
    $alterQuery = "ALTER TABLE `issues` ADD COLUMN `component_asset_id` INT(11) DEFAULT NULL COMMENT 'ID of the specific hardware component asset from assets table' AFTER `hardware_component`";
    
    if ($conn->query($alterQuery) === TRUE) {
        echo "Column 'component_asset_id' added successfully to 'issues' table.\n";
        
        // Add foreign key constraint (optional, but recommended)
        $fkQuery = "ALTER TABLE `issues` ADD CONSTRAINT `fk_issues_component_asset` 
                    FOREIGN KEY (`component_asset_id`) REFERENCES `assets`(`id`) 
                    ON DELETE SET NULL ON UPDATE CASCADE";
        
        if ($conn->query($fkQuery) === TRUE) {
            echo "Foreign key constraint added successfully.\n";
        } else {
            echo "Note: Foreign key constraint could not be added: " . $conn->error . "\n";
            echo "This is optional and the system will still work.\n";
        }
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
}

$conn->close();
echo "\nMigration completed.\n";
?>
