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
    $technician = trim($_POST['technician_name'] ?? '');

    error_log("assign_ticket: ticketId=$ticketId, technician=$technician");

    if ($ticketId <= 0) throw new Exception('Invalid ticket id (received: ' . ($ticketId ?? 'null') . ')');
    if ($technician === '') throw new Exception('Please select a technician');

    // update
    $stmt = $conn->prepare("UPDATE issues SET assigned_group = ?, updated_at = NOW() WHERE id = ?");
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
    $stmt->bind_param('si', $technician, $ticketId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'ticket_id' => $ticketId,
        'assigned_group' => $technician,
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