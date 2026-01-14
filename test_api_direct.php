<?php
// Simple test to check API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Predictive Analytics API</h2>";

// Start session
session_start();

// Check session
echo "<p><strong>Session Check:</strong></p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test database connection
echo "<p><strong>Database Connection Test:</strong></p>";
require_once 'config/config.php';

try {
    $dbConfig = Config::database();
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
    echo "<p style='color: green;'>✓ Database connected successfully</p>";
    
    // Test a simple query
    $result = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status NOT IN ('Disposed', 'Archive')");
    $row = $result->fetch_assoc();
    echo "<p>Active assets count: {$row['count']}</p>";
    
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

// Test API call
echo "<p><strong>API Test:</strong></p>";
echo "<p><a href='controller/get_predictive_analytics.php' target='_blank'>Click here to test API directly</a></p>";

echo "<hr>";
echo "<p>If the API link shows an error, copy it here:</p>";
echo "<textarea style='width: 100%; height: 200px;' placeholder='Paste any error message here'></textarea>";
?>
