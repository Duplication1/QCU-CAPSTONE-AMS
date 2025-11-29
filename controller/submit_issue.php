<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php'; // adjust path

// Database connection
$conn = new mysqli('localhost', 'root', '', 'ams_database');
if ($conn->connect_error) {
    echo json_encode(['success'=>false,'message'=>'Database connection failed']); 
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success'=>false,'message'=>'Only POST allowed']); exit;
}
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
  echo json_encode(['success'=>false,'message'=>'Not logged in']); exit;
}

// validate inputs (example)
$category = $_POST['category'] ?? '';
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$building_id = $_POST['building_id'] ?? null;
$room_id = $_POST['room_id'] ?? null;
$pc_id = $_POST['pc_id'] ?? null;

if ($category==='' || $title==='') {
  echo json_encode(['success'=>false,'message'=>'Missing required fields']); exit;
}

// insert into issues
$stmt = $conn->prepare("INSERT INTO issues (category, title, description, building_id, room_id, pc_id, priority, status, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'Open', ?, NOW())");
if (!$stmt) { echo json_encode(['success'=>false,'message'=>'DB prepare failed']); exit; }
$user_id = $_SESSION['user_id'] ?? null;
$priority = $_POST['priority'] ?? 'Medium';
$stmt->bind_param('sssssiss', $category, $title, $description, $building_id, $room_id, $pc_id, $priority, $user_id);
$stmt->execute();
$id = $stmt->insert_id;
$stmt->close();

// Create notification for successful submission
if ($id > 0) {
    try {
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
        $conn->query($createTableQuery);
        
        // Notify the submitter
        $notifTitle = "Ticket #{$id} Submitted";
        $notifMessage = "Your ticket has been submitted successfully and is pending assignment.";
        $notifType = 'success';
        
        $notifStmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_type, related_id) 
            VALUES (?, ?, ?, ?, 'issue', ?)
        ");
        $notifStmt->bind_param('isssi', $user_id, $notifTitle, $notifMessage, $notifType, $id);
        $notifStmt->execute();
        $notifStmt->close();
        
        // Get submitter name
        $submitterName = $_SESSION['full_name'] ?? 'A user';
        
        // Notify all Laboratory Staff
        $labStaffQuery = "SELECT id FROM users WHERE role = 'Laboratory Staff'";
        $labStaffResult = $conn->query($labStaffQuery);
        
        if ($labStaffResult && $labStaffResult->num_rows > 0) {
            $staffNotifTitle = "New Ticket Submitted";
            $staffNotifMessage = "{$submitterName} submitted a new {$category} ticket: {$title}";
            $staffNotifType = 'info';
            
            $staffNotifStmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_type, related_id) 
                VALUES (?, ?, ?, ?, 'issue', ?)
            ");
            
            while ($staff = $labStaffResult->fetch_assoc()) {
                $staffId = $staff['id'];
                $staffNotifStmt->bind_param('isssi', $staffId, $staffNotifTitle, $staffNotifMessage, $staffNotifType, $id);
                $staffNotifStmt->execute();
            }
            $staffNotifStmt->close();
        }
    } catch (Exception $notifError) {
        error_log("Failed to create notification: " . $notifError->getMessage());
    }
}

$conn->close();

echo json_encode(['success'=>true,'ticket_id'=>$id,'message'=>'Issue submitted']);
exit;
?>