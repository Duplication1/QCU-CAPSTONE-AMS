<?php
session_start();
require_once '../config/config.php';
require_once '../model/Database.php';

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Allow Laboratory Staff, Student, and Faculty roles
$allowed_roles = ['Laboratory Staff', 'Student', 'Faculty'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$borrower_id = $_GET['borrower_id'] ?? null;
$lab_staff_id = $_GET['lab_staff_id'] ?? null;

// If no parameters provided, return current user's signature
if (!$borrower_id && !$lab_staff_id) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT e_signature FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_signature = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'borrower_signature' => $user_signature
        ]);
        exit();
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
        exit();
    }
}

if (!$borrower_id || !$lab_staff_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Borrower ID and Lab Staff ID are required']);
    exit();
}

// If user is Student or Faculty, only allow them to access their own signature
if (in_array($_SESSION['role'], ['Student', 'Faculty']) && $_SESSION['user_id'] != $borrower_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You can only access your own signature']);
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
    
    // Signatures are stored as Base64 data URIs
    echo json_encode([
        'success' => true,
        'borrower_signature' => $borrower_signature,
        'lab_staff_signature' => $lab_staff_signature
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
