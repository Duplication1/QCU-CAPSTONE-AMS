<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

header('Content-Type: application/json');

try {
    $id = intval($_POST['id'] ?? 0);
    $room_id = intval($_POST['room_id'] ?? 0);

    if ($id <= 0 || $room_id <= 0) {
        throw new Exception('Invalid asset ID or room ID');
    }

    // Create database connection
    $conn = new mysqli('localhost', 'root', '', 'ams_database');
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Check if asset exists and is archived
    $check_stmt = $conn->prepare("SELECT id, asset_tag FROM assets WHERE id = ? AND room_id = ? AND (status = 'Archive' OR status = 'Archived')");
    $check_stmt->bind_param('ii', $id, $room_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        throw new Exception('Asset not found or not archived');
    }

    $asset = $check_result->fetch_assoc();
    $check_stmt->close();

    // Update asset status to Available
    $update_stmt = $conn->prepare("UPDATE assets SET status = 'Available', updated_at = NOW() WHERE id = ?");
    $update_stmt->bind_param('i', $id);

    if (!$update_stmt->execute()) {
        throw new Exception('Failed to restore asset');
    }

    $update_stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Asset restored successfully',
        'asset' => $asset
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>