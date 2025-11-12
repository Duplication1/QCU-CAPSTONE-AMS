<?php
session_start();
require_once '../config/config.php';
require_once '../model/Asset.php';

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$asset_id = $_GET['id'] ?? null;

if (!$asset_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Asset ID is required']);
    exit();
}

try {
    $asset = new Asset();
    $assetData = $asset->getById($asset_id);
    
    if (!$assetData) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Asset not found']);
        exit();
    }
    
    // Generate QR code data (JSON format with asset information)
    $qrData = json_encode([
        'asset_id' => $assetData['id'],
        'asset_tag' => $assetData['asset_tag'],
        'asset_name' => $assetData['asset_name'],
        'asset_type' => $assetData['asset_type'],
        'serial_number' => $assetData['serial_number'],
        'room' => $assetData['room_name'],
        'status' => $assetData['status']
    ]);
    
    // Using a third-party QR code API (you can also use a PHP library like endroid/qr-code)
    $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrData);
    
    echo json_encode([
        'success' => true,
        'qr_code_url' => $qrCodeUrl,
        'qr_data' => $qrData,
        'asset' => $assetData
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate QR code: ' . $e->getMessage()
    ]);
}
?>
