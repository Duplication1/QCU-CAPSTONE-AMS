<?php
session_start();
require_once '../config/config.php';
require_once '../model/Database.php';

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$borrower_id = $_GET['borrower_id'] ?? null;
$lab_staff_id = $_GET['lab_staff_id'] ?? null;

if (!$borrower_id || !$lab_staff_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Borrower ID and Lab Staff ID are required']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get borrower's signature
    $stmt = $conn->prepare("SELECT e_signature FROM users WHERE id = ?");
    $stmt->execute([$borrower_id]);
    $borrower_signature = $stmt->fetchColumn();
    
    // Get lab staff's signature
    $stmt = $conn->prepare("SELECT e_signature FROM users WHERE id = ?");
    $stmt->execute([$lab_staff_id]);
    $lab_staff_signature = $stmt->fetchColumn();
    
    // Verify files exist
    $borrower_sig_exists = $borrower_signature && file_exists('../uploads/signatures/' . $borrower_signature);
    $lab_staff_sig_exists = $lab_staff_signature && file_exists('../uploads/signatures/' . $lab_staff_signature);
    
    echo json_encode([
        'success' => true,
        'borrower_signature' => $borrower_sig_exists ? $borrower_signature : null,
        'lab_staff_signature' => $lab_staff_sig_exists ? $lab_staff_signature : null
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
