<?php
/**
 * Get PC Health Data Controller
 * Retrieves PC health status from Firebase for dashboard display
 */

session_start();
require_once '../config/config.php';
require_once '../config/firebase_config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// This endpoint returns PC unit information from database
// The actual health data will be fetched from Firebase in real-time via JavaScript

require_once '../model/PCUnit.php';
require_once '../model/Room.php';

try {
    $pcUnit = new PCUnit();
    $room = new Room();
    
    $action = $_GET['action'] ?? 'getAll';
    
    switch ($action) {
        case 'getAll':
            $pcUnits = $pcUnit->getAll();
            echo json_encode([
                'success' => true,
                'data' => $pcUnits
            ]);
            break;
            
        case 'getByRoom':
            $roomId = $_GET['room_id'] ?? null;
            if (!$roomId) {
                throw new Exception('Room ID is required');
            }
            $pcUnits = $pcUnit->getByRoom($roomId);
            echo json_encode([
                'success' => true,
                'data' => $pcUnits
            ]);
            break;
            
        case 'getRooms':
            $rooms = $room->getAll();
            echo json_encode([
                'success' => true,
                'data' => $rooms
            ]);
            break;
            
        case 'getFirebaseConfig':
            // Return Firebase config for client-side connection
            echo json_encode([
                'success' => true,
                'config' => FIREBASE_CONFIG
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
