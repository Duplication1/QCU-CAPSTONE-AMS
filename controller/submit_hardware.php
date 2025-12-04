<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli('localhost', 'root', '', 'ams_database');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['error_message'] = 'You must be logged in.';
    header('Location: ../view/StudentFaculty/tickets.php');
    exit;
}

// Check POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Invalid request method.';
    header('Location: ../view/StudentFaculty/tickets.php');
    exit;
}

// Get user info
$userId = $_SESSION['user_id'] ?? null;
$requesterName = $_SESSION['full_name'] ?? 'Unknown';

// Get form data
$room = trim($_POST['room'] ?? '');
$terminal = trim($_POST['terminal'] ?? '');
$hardwareComponent = trim($_POST['hardware_component'] ?? '');
$hardwareComponentOther = trim($_POST['hardware_component_other'] ?? '');
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$priority = trim($_POST['priority'] ?? 'Medium');

// If "Others" is selected, use the custom input
if ($hardwareComponent === 'Others' && !empty($hardwareComponentOther)) {
    $hardwareComponent = $hardwareComponentOther;
}

// Validate
if (empty($room) || empty($terminal) || empty($hardwareComponent) || empty($title)) {
    $_SESSION['error_message'] = 'Please fill in all required fields.';
    header('Location: ../view/StudentFaculty/tickets.php');
    exit;
}

// Validate priority
if (!in_array($priority, ['Low', 'Medium', 'High'])) {
    $priority = 'Medium';
}

$issueType = 'Hardware';
$status = 'Open';

// Add hardware component to title
$titleWithComponent = $hardwareComponent . ' - ' . $title;

// Insert query
$sql = "INSERT INTO hardware_issues (user_id, requester_name, room, terminal, title, description, priority, issue_type, submitted_by, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log('Prepare failed: ' . $conn->error);
    $_SESSION['error_message'] = 'Database error: ' . $conn->error;
    header('Location: ../view/StudentFaculty/tickets.php');
    exit;
}

$stmt->bind_param('isssssssss', $userId, $requesterName, $room, $terminal, $titleWithComponent, $description, $priority, $issueType, $requesterName, $status);

if ($stmt->execute()) {
    $ticketId = $stmt->insert_id;
    $_SESSION['success_message'] = 'Hardware issue submitted successfully!';
    error_log('Hardware issue submitted: ID=' . $ticketId);
    
    // Log the issue submission
    try {
        require_once '../model/ActivityLog.php';
        require_once '../model/Database.php';
        ActivityLog::record(
            $userId,
            'create',
            'ticket',
            $ticketId,
            "Submitted hardware ticket: {$titleWithComponent}"
        );
    } catch (Exception $logError) {
        error_log('Failed to log hardware issue submission: ' . $logError->getMessage());
    }
    
    // Notify all Laboratory Staff
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
        
        // Get all Laboratory Staff
        $labStaffQuery = "SELECT id FROM users WHERE role = 'Laboratory Staff'";
        $labStaffResult = $conn->query($labStaffQuery);
        
        if ($labStaffResult && $labStaffResult->num_rows > 0) {
            $staffNotifTitle = "New Hardware Ticket Submitted";
            $staffNotifMessage = "{$requesterName} submitted a hardware ticket: {$titleWithComponent}";
            $staffNotifType = 'info';
            
            $staffNotifStmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_type, related_id) 
                VALUES (?, ?, ?, ?, 'issue', ?)
            ");
            
            while ($staff = $labStaffResult->fetch_assoc()) {
                $staffId = $staff['id'];
                $staffNotifStmt->bind_param('isssi', $staffId, $staffNotifTitle, $staffNotifMessage, $staffNotifType, $ticketId);
                $staffNotifStmt->execute();
            }
            $staffNotifStmt->close();
        }
    } catch (Exception $notifError) {
        error_log("Failed to create notification: " . $notifError->getMessage());
    }
} else {
    error_log('Execute failed: ' . $stmt->error);
    $_SESSION['error_message'] = 'Failed to submit: ' . $stmt->error;
}

$stmt->close();
$conn->close();

header('Location: ../view/StudentFaculty/tickets.php');
exit;
?>