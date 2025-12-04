<?php
session_start();
require_once '../config/config.php';
require_once '../model/Asset.php';

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    header("Location: ../view/login.php");
    exit();
}

try {
    $asset = new Asset();
    $assets = $asset->getAll();
    
    // Log the export activity
    try {
        require_once '../model/ActivityLog.php';
        require_once '../model/Database.php';
        ActivityLog::record(
            $_SESSION['user_id'],
            'export',
            'asset',
            null,
            'Exported assets to CSV (' . count($assets) . ' records)'
        );
    } catch (Exception $logError) {
        error_log('Failed to log asset export: ' . $logError->getMessage());
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=assets_export_' . date('Y-m-d_His') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Write CSV header
    fputcsv($output, [
        'asset_tag',
        'asset_name',
        'asset_type',
        'category',
        'brand',
        'model',
        'serial_number',
        'specifications',
        'status',
        'condition',
        'is_borrowable',
        'purchase_date',
        'purchase_cost',
        'supplier',
        'warranty_expiry',
        'notes',
        'created_at'
    ]);
    
    // Write data rows
    foreach ($assets as $asset) {
        fputcsv($output, [
            $asset['asset_tag'],
            $asset['asset_name'],
            $asset['asset_type'],
            $asset['category'] ?? '',
            $asset['brand'] ?? '',
            $asset['model'] ?? '',
            $asset['serial_number'] ?? '',
            $asset['specifications'] ?? '',
            $asset['status'],
            $asset['condition'],
            $asset['is_borrowable'] ? 'Yes' : 'No',
            $asset['purchase_date'] ?? '',
            $asset['purchase_cost'] ?? '',
            $asset['supplier'] ?? '',
            $asset['warranty_expiry'] ?? '',
            $asset['notes'] ?? '',
            $asset['created_at']
        ]);
    }
    
    fclose($output);
    exit();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Export error: ' . $e->getMessage();
    header("Location: ../view/LaboratoryStaff/registry.php");
    exit();
}
?>
