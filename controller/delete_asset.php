<?php
session_start();
require_once '../config/config.php';
require_once '../model/Asset.php';

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$asset_id = $_POST['asset_id'] ?? null;

if (!$asset_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Asset ID is required']);
    exit();
}

try {
    $asset = new Asset();
    
    if ($asset->delete($asset_id)) {
        // Log activity
        require_once '../model/ActivityLog.php';
        ActivityLog::record(
            $_SESSION['user_id'],
            'archive',
            'asset',
            $asset_id,
            'Archived asset ID: ' . $asset_id
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Asset deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete asset'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error deleting asset: ' . $e->getMessage()
    ]);
}
?>
