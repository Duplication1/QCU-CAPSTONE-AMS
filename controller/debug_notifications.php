<?php
session_start();
require_once '../config/config.php';
require_once '../model/Database.php';

if (!isset($_SESSION['user_id'])) {
    die('Not logged in');
}

$db = new Database();
$conn = $db->getConnection();

// Check if notifications table exists
try {
    $result = $conn->query("SHOW TABLES LIKE 'notifications'");
    echo "<h2>Notifications Table Exists: " . ($result->rowCount() > 0 ? 'YES' : 'NO') . "</h2>";
    
    if ($result->rowCount() > 0) {
        // Get all notifications for current user
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Your Notifications (Total: " . count($notifications) . "):</h3>";
        echo "<pre>";
        print_r($notifications);
        echo "</pre>";
        
        // Get all notifications (for debugging)
        $allStmt = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 20");
        $allNotifications = $allStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>All Recent Notifications (Total: " . count($allNotifications) . "):</h3>";
        echo "<pre>";
        print_r($allNotifications);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<h2>Error: " . $e->getMessage() . "</h2>";
}

echo "<hr>";
echo "<h3>Session Info:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>
