<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
}

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
    $action   = trim($_POST['action'] ?? ''); // 'confirm' or 'refuse'
    $refusalReason = trim($_POST['refusal_reason'] ?? '');

    if ($ticketId <= 0) throw new Exception('Invalid ticket ID');
    if (!in_array($action, ['confirm', 'refuse'])) throw new Exception('Invalid action');
    if ($action === 'refuse' && empty($refusalReason)) throw new Exception('Refusal reason is required');

    // Get technician name from session or DB
    $technicianName = $_SESSION['full_name'] ?? null;
    if (!$technicianName && isset($_SESSION['user_id'])) {
        $s = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
        $s->bind_param('i', $_SESSION['user_id']);
        $s->execute();
        $r = $s->get_result()->fetch_assoc();
        $technicianName = $r['full_name'] ?? null;
        $s->close();
    }

    if (!$technicianName) throw new Exception('Could not determine technician identity');

    // Ensure assignment_status column exists
    $colCheck = $conn->query("SHOW COLUMNS FROM issues LIKE 'assignment_status'");
    if ($colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE issues ADD COLUMN assignment_status ENUM('Pending','Confirmed','Refused') NULL DEFAULT NULL");
    }
    
    // Ensure refusal_reason column exists
    $refusalReasonCheck = $conn->query("SHOW COLUMNS FROM issues LIKE 'refusal_reason'");
    if ($refusalReasonCheck->num_rows === 0) {
        $conn->query("ALTER TABLE issues ADD COLUMN refusal_reason TEXT NULL DEFAULT NULL AFTER assignment_status");
    }
    
    // Create refusal history table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS ticket_refusal_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        technician_name VARCHAR(255) NOT NULL,
        technician_id INT NOT NULL,
        refusal_reason TEXT NOT NULL,
        refused_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ticket_id (ticket_id)
    )");

    // Verify the ticket is assigned to this technician and is pending
    $s = $conn->prepare("SELECT id, assigned_technician, assignment_status, user_id, title, assigned_by FROM issues WHERE id = ?");
    $s->bind_param('i', $ticketId);
    $s->execute();
    $ticket = $s->get_result()->fetch_assoc();
    $s->close();

    if (!$ticket) throw new Exception('Ticket not found');
    if ($ticket['assigned_technician'] !== $technicianName) throw new Exception('You are not assigned to this ticket');
    if ($ticket['assignment_status'] !== 'Pending') throw new Exception('This ticket is not pending a response');

    if ($action === 'confirm') {
        // Technician confirms: mark as Confirmed and set status to In Progress
        $stmt = $conn->prepare("UPDATE issues SET assignment_status = 'Confirmed', status = 'In Progress', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $ticketId);
        $stmt->execute();
        $stmt->close();

        // Notify lab staff / assigned_by user
        try {
            $conn->query("CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type ENUM('info','success','warning','error') DEFAULT 'info',
                related_type ENUM('issue','borrowing','asset','system') DEFAULT 'issue',
                related_id INT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_is_read (is_read)
            )");

            // Notify reporter
            if (!empty($ticket['user_id'])) {
                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_type, related_id) VALUES (?, ?, ?, 'success', 'issue', ?)");
                $notifTitle   = "Ticket #{$ticketId} Confirmed";
                $notifMessage = "{$technicianName} has confirmed and will be working on your ticket: \"{$ticket['title']}\".";
                $notifStmt->bind_param('issi', $ticket['user_id'], $notifTitle, $notifMessage, $ticketId);
                $notifStmt->execute();
                $notifStmt->close();
            }
        } catch (Exception $notifError) {
            error_log('Notification error: ' . $notifError->getMessage());
        }

        echo json_encode(['success' => true, 'action' => 'confirm', 'message' => 'You have confirmed this ticket assignment.']);
    } else {
        // Technician refuses: reset ticket to Open and unassign, save refusal reason
        $stmt = $conn->prepare("UPDATE issues SET assignment_status = 'Refused', refusal_reason = ?, assigned_technician = NULL, assigned_at = NULL, status = 'Open', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $refusalReason, $ticketId);
        $stmt->execute();
        $stmt->close();
        
        // Save refusal to history table
        $historyStmt = $conn->prepare("INSERT INTO ticket_refusal_history (ticket_id, technician_name, technician_id, refusal_reason) VALUES (?, ?, ?, ?)");
        $technicianId = $_SESSION['user_id'];
        $historyStmt->bind_param('isis', $ticketId, $technicianName, $technicianId, $refusalReason);
        $historyStmt->execute();
        $historyStmt->close();

        // Notify reporter and lab staff about refusal
        try {
            $conn->query("CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type ENUM('info','success','warning','error') DEFAULT 'info',
                related_type ENUM('issue','borrowing','asset','system') DEFAULT 'issue',
                related_id INT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_is_read (is_read)
            )");

            $notifTitle   = "Ticket #{$ticketId} — Technician Refused";
            $notifMessage = "{$technicianName} has refused the assignment for ticket: \"{$ticket['title']}\".\n\nReason: {$refusalReason}\n\nA new technician needs to be assigned.";

            // Notify the reporter (student/faculty)
            if (!empty($ticket['user_id'])) {
                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_type, related_id) VALUES (?, ?, ?, 'warning', 'issue', ?)");
                $notifStmt->bind_param('issi', $ticket['user_id'], $notifTitle, $notifMessage, $ticketId);
                $notifStmt->execute();
                $notifStmt->close();
            }

            // Notify the lab staff who assigned the ticket
            if (!empty($ticket['assigned_by'])) {
                $labNotifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_type, related_id) VALUES (?, ?, ?, 'warning', 'issue', ?)");
                $labNotifTitle   = "Ticket #{$ticketId} — Assignment Refused";
                $labNotifMessage = "{$technicianName} has refused the assignment for ticket \"{$ticket['title']}\".\n\nReason: {$refusalReason}\n\nPlease assign a different technician.";
                $labNotifStmt->bind_param('issi', $ticket['assigned_by'], $labNotifTitle, $labNotifMessage, $ticketId);
                $labNotifStmt->execute();
                $labNotifStmt->close();
            }
        } catch (Exception $notifError) {
            error_log('Notification error: ' . $notifError->getMessage());
        }

        echo json_encode(['success' => true, 'action' => 'refuse', 'message' => 'You have refused this ticket. It has been returned to the queue.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
