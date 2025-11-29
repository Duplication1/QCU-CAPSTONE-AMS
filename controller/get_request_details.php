<?php
session_start();
require_once '../config/config.php';
require_once '../model/AssetBorrowing.php';

// Check if user is logged in and has student or faculty role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || !in_array($_SESSION['role'], ['Student', 'Faculty'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$request_id = $_GET['id'] ?? null;

if (!$request_id || $request_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Valid request ID is required']);
    exit();
}

try {
    $borrowing = new AssetBorrowing();
    $request = $borrowing->getById($request_id);
    
    if ($request) {
        // Verify the request belongs to the logged-in user
        if ($request['borrower_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized access to this request']);
            exit();
        }
        
        echo json_encode([
            'success' => true,
            'request' => $request
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Request not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch request details: ' . $e->getMessage()
    ]);
}
?>
