<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// load config
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
}

// ensure $conn
if (!isset($conn) || !$conn) {
    $conn = new mysqli('127.0.0.1','root','','ams_database');
}
if (!$conn || $conn->connect_error) {
    error_log('assign_ticket: DB connect error: ' . ($conn->connect_error ?? 'unknown'));
    echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . ($conn->connect_error ?? 'unknown')]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST allowed');
    }

    if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
        throw new Exception('Not logged in');
    }

    // Log POST data for debugging
    error_log('assign_ticket POST data: ' . print_r($_POST, true));

    $ticketId = intval($_POST['ticket_id'] ?? 0);
    $technicianId = intval($_POST['technician_id'] ?? 0);

    error_log("assign_ticket: ticketId=$ticketId, technicianId=$technicianId");

    if ($ticketId <= 0) throw new Exception('Invalid ticket id (received: ' . ($ticketId ?? 'null') . ')');
    if ($technicianId <= 0) throw new Exception('Please select a technician');

    // Get ticket details for notification
    $ticketStmt = $conn->prepare("SELECT user_id, title, category FROM issues WHERE id = ?");
    $ticketStmt->bind_param('i', $ticketId);
    $ticketStmt->execute();
    $ticketResult = $ticketStmt->get_result();
    $ticketData = $ticketResult->fetch_assoc();
    $ticketStmt->close();

    if (!$ticketData) {
        throw new Exception('Ticket not found');
    }

    // Get technician's full_name and verify they exist
    $techStmt = $conn->prepare("SELECT id, full_name FROM users WHERE id = ? AND role = 'Technician'");
    $techStmt->bind_param('i', $technicianId);
    $techStmt->execute();
    $techResult = $techStmt->get_result();
    $techData = $techResult->fetch_assoc();
    $techStmt->close();
    
    if (!$techData) {
        throw new Exception('Technician not found');
    }
    
    $technicianUserId = $techData['id'];
    $technicianName = $techData['full_name'];
    error_log("Technician lookup - ID: $technicianId, Name: $technicianName");

    // update assigned_technician with the technician's full name
    $stmt = $conn->prepare("UPDATE issues SET assigned_technician = ?, updated_at = NOW() WHERE id = ?");
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
    $stmt->bind_param('si', $technicianName, $ticketId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    // Log activity for Laboratory Staff
    if ($affected > 0 && isset($_SESSION['role']) && $_SESSION['role'] === 'Laboratory Staff') {
        try {
            $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent) VALUES (?, 'assign', 'ticket', ?, ?, ?, ?)");
            $description = 'Assigned ticket #' . $ticketId . ' to ' . $technicianName;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $logStmt->bind_param('iisss', $_SESSION['user_id'], $ticketId, $description, $ip_address, $user_agent);
            $logStmt->execute();
            $logStmt->close();
        } catch (Exception $logError) {
            error_log('Failed to log ticket assignment: ' . $logError->getMessage());
        }
    }

    // Create notification for the student
    if ($affected > 0) {
        try {
            // Create notifications table if it doesn't exist
            $conn->query("CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
                related_type ENUM('issue', 'borrowing', 'asset', 'system') DEFAULT 'issue',
                related_id INT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_is_read (is_read)
            )");

            // Notification for the student who submitted the ticket
            $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_type, related_id) VALUES (?, ?, ?, 'info', 'issue', ?)");
            $notifTitle = "Ticket #{$ticketId} Assigned";
            $notifMessage = "Your ticket has been assigned to {$technicianName}. They will be working on your issue soon.";
            $notifStmt->bind_param('issi', $ticketData['user_id'], $notifTitle, $notifMessage, $ticketId);
            $notifStmt->execute();
            $notifStmt->close();
            
            // Notification for the technician who is assigned to the ticket
            if ($technicianUserId) {
                $techNotifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_type, related_id) VALUES (?, ?, ?, 'info', 'issue', ?)");
                $techNotifTitle = "New Ticket Assigned #" . $ticketId;
                $ticketCategory = $ticketData['category'] ?? 'Technical';
                $ticketTitle = $ticketData['title'] ?? 'Untitled';
                $techNotifMessage = "You have been assigned to a {$ticketCategory} ticket: \"{$ticketTitle}\". Please review and take action.";
                $techNotifStmt->bind_param('issi', $technicianUserId, $techNotifTitle, $techNotifMessage, $ticketId);
                $techNotifStmt->execute();
                $techNotifStmt->close();
                error_log("Technician notification created for user_id: $technicianUserId, ticket: $ticketId");
            } else {
                error_log("WARNING: Could not create technician notification - technician user_id not found for ID: $technicianId");
            }
        } catch (Exception $notifError) {
            error_log('Failed to create notification: ' . $notifError->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'ticket_id' => $ticketId,
        'assigned_technician' => $technicianName,
        // user-visible message
        'message' => $affected > 0 ? 'Successfully Technician Assigned!' : 'No change'
    ]);
    exit;
} catch (Exception $e) {
    error_log('assign_ticket error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
?>