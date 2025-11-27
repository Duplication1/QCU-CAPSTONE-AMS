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
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['ids'] ?? [];
    $room_id = intval($input['room_id'] ?? 0);

    if (empty($ids) || $room_id <= 0) {
        throw new Exception('Invalid PC unit IDs or room ID');
    }

    // Validate all IDs are integers
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, function($id) { return $id > 0; });

    if (empty($ids)) {
        throw new Exception('No valid PC unit IDs provided');
    }

    // Create database connection
    $conn = new mysqli('localhost', 'root', '', 'ams_database');
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Check which PC units exist and are archived
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $types = str_repeat('i', count($ids) + 1);
    $params = array_merge($ids, [$room_id]);

    $check_query = "SELECT id, terminal_number FROM pc_units WHERE id IN ($placeholders) AND room_id = ? AND (status = 'Archive' OR status = 'Archived')";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param($types, ...$params);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    $valid_ids = [];
    $pc_units = [];
    while ($row = $check_result->fetch_assoc()) {
        $valid_ids[] = $row['id'];
        $pc_units[] = $row;
    }
    $check_stmt->close();

    if (empty($valid_ids)) {
        throw new Exception('No valid archived PC units found');
    }

    // Update PC units status to Active
    $update_placeholders = str_repeat('?,', count($valid_ids) - 1) . '?';
    $update_types = str_repeat('i', count($valid_ids));
    $update_query = "UPDATE pc_units SET status = 'Active', updated_at = NOW() WHERE id IN ($update_placeholders)";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param($update_types, ...$valid_ids);

    if (!$update_stmt->execute()) {
        throw new Exception('Failed to restore PC units');
    }

    $affected_rows = $update_stmt->affected_rows;
    $update_stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => "Successfully restored $affected_rows PC unit(s)",
        'restored_count' => $affected_rows,
        'pc_units' => $pc_units
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>