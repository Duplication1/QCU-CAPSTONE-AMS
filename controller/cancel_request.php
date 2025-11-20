<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../config/config.php';
require_once '../model/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;

if (!$request_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verify that the request belongs to the current user and is pending
    $stmt = $conn->prepare("
        SELECT id, borrower_id, status, asset_id
        FROM asset_borrowing 
        WHERE id = ? AND borrower_id = ?
    ");
    $stmt->execute([$request_id, $_SESSION['user_id']]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found or access denied']);
        exit();
    }
    
    // Check if request is pending
    if ($request['status'] !== 'Pending') {
        echo json_encode(['success' => false, 'message' => 'Only pending requests can be cancelled']);
        exit();
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Delete the request completely from database
        $stmt = $conn->prepare("DELETE FROM asset_borrowing WHERE id = ?");
        $result = $stmt->execute([$request_id]);
        
        if (!$result) {
            throw new Exception('Failed to delete request');
        }
        
        // Update asset status if the asset was reserved
        // Check what column name exists in assets table (status, availability, etc.)
        if ($request['asset_id']) {
            $stmt = $conn->prepare("
                UPDATE assets 
                SET status = 'Available' 
                WHERE id = ? AND status = 'Reserved'
            ");
            $stmt->execute([$request['asset_id']]);
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Request cancelled and removed successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Cancel request error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Cancel request error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>