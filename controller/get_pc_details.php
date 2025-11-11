<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../model/PCUnit.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) && !isset($_GET['room_id'])) {
    echo json_encode(['success' => false, 'message' => 'PC unit ID or room ID required']);
    exit();
}

try {
    $pcUnitModel = new PCUnit();
    
    if (isset($_GET['id'])) {
        // Get specific PC by ID
        $pcUnit = $pcUnitModel->getByIdWithComponents($_GET['id']);
        
        if ($pcUnit) {
            echo json_encode([
                'success' => true,
                'data' => $pcUnit
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'PC unit not found'
            ]);
        }
    } elseif (isset($_GET['room_id']) && isset($_GET['terminal'])) {
        // Get PC by room and terminal
        $pcUnit = $pcUnitModel->getByRoomAndTerminal($_GET['room_id'], $_GET['terminal']);
        
        if ($pcUnit) {
            echo json_encode([
                'success' => true,
                'data' => $pcUnit
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'PC unit not found'
            ]);
        }
    } elseif (isset($_GET['room_id'])) {
        // Get all PCs in a room
        $pcUnits = $pcUnitModel->getByRoom($_GET['room_id']);
        echo json_encode([
            'success' => true,
            'data' => $pcUnits
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
