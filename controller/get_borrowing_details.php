<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display to browser, we'll return JSON

require_once '../config/config.php';
require_once '../model/Database.php';
require_once '../model/AssetBorrowing.php';

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$request_id = $_GET['id'] ?? null;

if (!$request_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Request ID is required']);
    exit();
}

try {
    $borrowing = new AssetBorrowing();
    $request = $borrowing->getById($request_id);
    
    if ($request) {
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
