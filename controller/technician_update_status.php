<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
}

// ensure DB connection
if (!isset($conn) || !$conn) {
    $conn = new mysqli('127.0.0.1', 'root', '', 'ams_database', 3306);
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'DB connection failed']);
        exit;
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Method not allowed');

    if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || ($_SESSION['role'] ?? '') !== 'Technician') {
        throw new Exception('Unauthorized');
    }

    $ticketId = intval($_POST['ticket_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');

    $allowed = ['Open','In Progress','Resolved','Closed'];
    if ($ticketId <= 0 || !in_array($newStatus, $allowed)) throw new Exception('Invalid input');

    // get technician name
    $technicianName = $_SESSION['full_name'] ?? null;
    if (!$technicianName) {
        // try to load name from users table
        if (isset($_SESSION['user_id'])) {
            $s = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
            $s->bind_param('i', $_SESSION['user_id']);
            $s->execute();
            $r = $s->get_result()->fetch_assoc();
            $technicianName = $r['full_name'] ?? null;
            $s->close();
        }
    }

    // verify ticket exists and assignment — require exact match to assigned technician
    $s = $conn->prepare("SELECT assigned_group, user_id, title FROM issues WHERE id = ?");
    $s->bind_param('i', $ticketId);
    $s->execute();
    $res = $s->get_result()->fetch_assoc();
    $s->close();

    if (!$res) throw new Exception('Ticket not found');

    $assigned = $res['assigned_group'] ?? '';
    $issueUserId = $res['user_id'] ?? null;
    $issueTitle = $res['title'] ?? 'Your ticket';

    // require that the ticket is assigned to this technician
    if ($assigned !== $technicianName) {
        throw new Exception('You are not assigned to this ticket');
    }

    $u = $conn->prepare("UPDATE issues SET status = ?, updated_at = NOW() WHERE id = ?");
    if (!$u) throw new Exception('Prepare failed: ' . $conn->error);
    $u->bind_param('si', $newStatus, $ticketId);
    $u->execute();
    $affected = $u->affected_rows;
    $u->close();

    // Create notification for the user who submitted the ticket
    if ($affected > 0 && $issueUserId) {
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
            
            // Determine notification type and message based on status
            $notifType = 'info';
            $notifMessage = '';
            
            switch ($newStatus) {
                case 'In Progress':
                    $notifType = 'info';
                    $notifMessage = "Your ticket is now being worked on by {$technicianName}.";
                    break;
                case 'Resolved':
                    $notifType = 'success';
                    $notifMessage = "Your ticket has been resolved by {$technicianName}.";
                    break;
                case 'Closed':
                    $notifType = 'info';
                    $notifMessage = "Your ticket has been closed.";
                    break;
                case 'Open':
                    $notifType = 'warning';
                    $notifMessage = "Your ticket status has been changed to Open.";
                    break;
            }
            
            $notifTitle = "Ticket #{$ticketId} - Status Updated";
            
            $notifStmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_type, related_id) 
                VALUES (?, ?, ?, ?, 'issue', ?)
            ");
            $notifStmt->bind_param('isssi', $issueUserId, $notifTitle, $notifMessage, $notifType, $ticketId);
            $notifStmt->execute();
            $notifStmt->close();
        } catch (Exception $notifError) {
            // Log but don't fail the main operation
            error_log("Failed to create notification: " . $notifError->getMessage());
        }
    }

    echo json_encode(['success' => true, 'ticket_id' => $ticketId, 'status' => $newStatus, 'message' => $affected ? 'Status updated' : 'No change']);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
?>