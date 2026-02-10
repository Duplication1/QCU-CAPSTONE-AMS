<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config/config.php';
require_once '../model/Database.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Mark all unread notifications as read for this user
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    
    $affected_rows = $stmt->rowCount();
    
    echo json_encode([
        'success' => true, 
        'message' => 'All notifications marked as read',
        'affected_rows' => $affected_rows
    ]);
} catch (PDOException $e) {
    error_log("Error marking all notifications as read: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
}
?>
