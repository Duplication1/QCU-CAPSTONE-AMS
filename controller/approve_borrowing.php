<?php
session_start();
require_once '../config/config.php';
require_once '../model/AssetBorrowing.php';

// Lab Staff are NOT permitted to approve asset requests
// Only Administrators can approve borrowing requests
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Restrict approval to Administrators only
if ($_SESSION['role'] === 'Laboratory Staff') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Lab Staff are not permitted to approve asset requests. Only Administrators can approve borrowing requests.']);
    exit();
}

if ($_SESSION['role'] !== 'Administrator') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only Administrators can approve borrowing requests']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$borrowing_id = $_POST['borrowing_id'] ?? null;

if (!$borrowing_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Borrowing ID is required']);
    exit();
}

try {
    $borrowing = new AssetBorrowing();
    $approved_by = $_SESSION['user_id'];
    
    if ($borrowing->approve($borrowing_id, $approved_by)) {
        echo json_encode([
            'success' => true,
            'message' => 'Borrowing request approved successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to approve borrowing request'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error approving request: ' . $e->getMessage()
    ]);
}
?>
