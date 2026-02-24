<?php
/**
 * Migration Runner: Add image support to tickets
 * Run this file once to add the image_path column to the issues table
 */

require_once 'config/config.php';

// Database connection
$dbConfig = Config::database();
try {
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Ticket Image Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Ticket Image Migration</h1>
";

// Check if column already exists
$checkQuery = "SHOW COLUMNS FROM issues LIKE 'image_path'";
$result = $conn->query($checkQuery);

if ($result->num_rows > 0) {
    echo "<div class='info'>✓ The 'image_path' column already exists in the 'issues' table. No migration needed.</div>";
} else {
    // Run migration
    $migrationSQL = "ALTER TABLE `issues` 
        ADD COLUMN `image_path` VARCHAR(500) DEFAULT NULL COMMENT 'Path to uploaded issue image' 
        AFTER `description`";
    
    if ($conn->query($migrationSQL)) {
        echo "<div class='success'>✓ Successfully added 'image_path' column to the 'issues' table!</div>";
    } else {
        echo "<div class='error'>✗ Error running migration: " . $conn->error . "</div>";
    }
}

// Create uploads directory if it doesn't exist
$uploadDir = __DIR__ . '/uploads/ticket_images/';
if (!file_exists($uploadDir)) {
    if (mkdir($uploadDir, 0755, true)) {
        echo "<div class='success'>✓ Created directory: uploads/ticket_images/</div>";
    } else {
        echo "<div class='error'>✗ Failed to create directory: uploads/ticket_images/</div>";
    }
} else {
    echo "<div class='info'>✓ Directory already exists: uploads/ticket_images/</div>";
}

// Create .htaccess for security
$htaccessPath = $uploadDir . '.htaccess';
$htaccessContent = "# Prevent PHP execution in upload directory\nphp_flag engine off\n\n# Allow only image files\n<FilesMatch \"\\.(jpg|jpeg|png|gif|webp)$\">\n    Order Allow,Deny\n    Allow from all\n</FilesMatch>";

if (!file_exists($htaccessPath)) {
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        echo "<div class='success'>✓ Created .htaccess file for upload security</div>";
    } else {
        echo "<div class='error'>✗ Failed to create .htaccess file</div>";
    }
} else {
    echo "<div class='info'>✓ .htaccess file already exists</div>";
}

$conn->close();

echo "
    <h2>Summary</h2>
    <p>The ticket image upload feature has been configured. Users can now:</p>
    <ul>
        <li>Upload images when submitting tickets (optional)</li>
        <li>Preview images before submission</li>
        <li>Supported formats: JPG, PNG, GIF, WEBP</li>
        <li>Maximum file size: 5MB</li>
    </ul>
    
    <p><strong>Note:</strong> You can safely delete this file (run_ticket_image_migration.php) after running it successfully.</p>
</body>
</html>
";
?>
