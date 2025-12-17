<?php
session_start();
require_once '../config/config.php';
require_once '../model/Database.php';

// Check if user is administrator
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'Administrator') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit();
}

$id = intval($_GET['id']);

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT rr.*, u.full_name as reviewed_by_name 
        FROM registration_requests rr 
        LEFT JOIN users u ON rr.reviewed_by = u.id 
        WHERE rr.id = ?
    ");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit();
    }
    
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'request' => $request]);
    
} catch (Exception $e) {
    error_log("Error fetching registration details: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
