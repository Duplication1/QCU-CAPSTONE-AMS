<?php
/**
 * Update QR codes to include scan URL
 * This script updates all existing asset QR codes to link to the scan page
 */

require_once __DIR__ . '/config/config.php';

// Get database connection
$conn = new mysqli('localhost', 'root', '', 'ams_database');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the base URL (adjust this to your actual domain)
<<<<<<< HEAD
$base_url = 'http://192.168.1.58/QCU-CAPSTONE-AMS';  // Using local network IP
=======
$base_url = 'http://172.20.10.2/QCU-CAPSTONE-AMS';  // Using local network IP
>>>>>>> 14b90a3cb03ab18ce465d310e5382ffa6df5d8cd
$scan_url_base = $base_url . '/view/public/scan_asset.php?id=';

echo "Starting QR code update...\n\n";

// Get all assets
$result = $conn->query("SELECT id, asset_tag, asset_name, asset_type, room_id FROM assets");

if ($result->num_rows > 0) {
    $updated = 0;
    $failed = 0;
    
    while ($asset = $result->fetch_assoc()) {
        // Create scan URL
        $scan_url = $scan_url_base . $asset['id'];
        
        // Generate QR code with the scan URL
        $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($scan_url);
        
        // Update the asset
        $update_stmt = $conn->prepare("UPDATE assets SET qr_code = ? WHERE id = ?");
        $update_stmt->bind_param('si', $qr_code_url, $asset['id']);
        
        if ($update_stmt->execute()) {
            $updated++;
            echo "✓ Updated: {$asset['asset_tag']} - {$asset['asset_name']}\n";
        } else {
            $failed++;
            echo "✗ Failed: {$asset['asset_tag']} - Error: " . $update_stmt->error . "\n";
        }
        
        $update_stmt->close();
    }
    
    echo "\n========================================\n";
    echo "Update completed!\n";
    echo "Successfully updated: $updated assets\n";
    echo "Failed: $failed assets\n";
    echo "========================================\n";
} else {
    echo "No assets found in database.\n";
}

$conn->close();
?>
