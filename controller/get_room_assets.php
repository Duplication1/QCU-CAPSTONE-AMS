<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/Database.php';
require_once __DIR__ . '/../model/Room.php';

if (!isset($_GET['room_id'])) {
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit();
}

$room_id = $_GET['room_id'];

try {
    $room = new Room();
    $roomInfo = $room->getById($room_id);
    $assets = $room->getAssetsByRoom($room_id);
    
    echo json_encode([
        'success' => true,
        'room' => $roomInfo,
        'assets' => $assets
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
