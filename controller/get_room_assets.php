<?php
session_start();

// Check if user is logged in and has administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
require_once '../../model/Database.php';
require_once '../../model/Room.php';

if (!isset($_GET['room_id'])) {
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit();
}

$room_id = $_GET['room_id'];
$room = new Room();

try {
    $roomInfo = $room->getById($room_id);
    $assets = $room->getAssetsByRoom($room_id);
    
    echo json_encode([
        'success' => true,
        'room' => $roomInfo,
        'assets' => $assets
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
