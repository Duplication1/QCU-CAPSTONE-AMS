<?php
session_start();
require_once '../config/config.php';
require_once '../model/Asset.php';

// Check if user is logged in and has administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$asset_id = $_GET['id'] ?? null;

if (!$asset_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Asset ID is required']);
    exit();
}

try {
    $asset = new Asset();
    $assetData = $asset->getById($asset_id);
    
    if ($assetData) {
        echo json_encode([
            'success' => true,
            'asset' => $assetData
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Asset not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch asset details: ' . $e->getMessage()
    ]);
}
?>
