<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized', 'notifications' => [], 'unread_count' => 0]);
    exit();
}

require_once '../config/config.php';
require_once '../model/Database.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'Student';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Create notifications table if it doesn't exist
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
            related_type ENUM('issue', 'borrowing', 'asset', 'system') DEFAULT 'system',
            related_id INT DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_is_read (is_read),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $conn->exec($createTableQuery);
    
    // Get unread notifications for the user
    $stmt = $conn->prepare("
        SELECT 
            id,
            title,
            message,
            type,
            related_type,
            related_id,
            is_read,
            created_at,
            TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_ago
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unread count
    $countStmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
    $countStmt->execute([$user_id]);
    $unreadCount = $countStmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
    
    // Format time ago
    foreach ($notifications as &$notification) {
        $minutes = $notification['minutes_ago'];
        if ($minutes < 1) {
            $notification['time_ago'] = 'Just now';
        } elseif ($minutes < 60) {
            $notification['time_ago'] = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($minutes < 1440) {
            $hours = floor($minutes / 60);
            $notification['time_ago'] = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } else {
            $days = floor($minutes / 1440);
            $notification['time_ago'] = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]);
} catch (PDOException $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch notifications',
        'notifications' => [],
        'unread_count' => 0
    ]);
}
?>
