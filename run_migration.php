<?php
/**
 * Run Technician Schedule Migration
 * This script adds the allowed_login_days column to the users table
 */

require_once 'config/config.php';

try {
    $dbConfig = Config::database();
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
    
    echo "Connected to database successfully.\n\n";
    
    // Check if column already exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'allowed_login_days'");
    
    if ($checkColumn->num_rows > 0) {
        echo "✓ Column 'allowed_login_days' already exists. No migration needed.\n";
    } else {
        echo "Adding 'allowed_login_days' column to users table...\n";
        
        // Add the column
        $sql1 = "ALTER TABLE `users` 
                 ADD COLUMN `allowed_login_days` VARCHAR(255) DEFAULT NULL 
                 COMMENT 'Comma-separated list of allowed login days for Technicians (e.g., Monday,Wednesday,Friday)'";
        
        if ($conn->query($sql1)) {
            echo "✓ Column added successfully!\n\n";
            
            // Update existing technicians
            echo "Updating existing Technician accounts to allow all days...\n";
            $sql2 = "UPDATE `users` 
                     SET `allowed_login_days` = 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday' 
                     WHERE `role` = 'Technician'";
            
            if ($conn->query($sql2)) {
                $affected = $conn->affected_rows;
                echo "✓ Updated $affected Technician account(s).\n\n";
                echo "=================================\n";
                echo "Migration completed successfully!\n";
                echo "=================================\n";
            } else {
                echo "✗ Error updating technicians: " . $conn->error . "\n";
            }
        } else {
            echo "✗ Error adding column: " . $conn->error . "\n";
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
