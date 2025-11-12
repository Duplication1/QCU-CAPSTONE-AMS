<?php
session_start();

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    header("Location: ../view/login.php");
    exit();
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=asset_import_template.csv');

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
    'notes'
]);

// Write sample data row
fputcsv($output, [
    'AST-2024-001',
    'Dell Latitude 5420 Laptop',
    'Hardware',
    'Laptop',
    'Dell',
    'Latitude 5420',
    'SN123456789',
    'Intel Core i5-1135G7, 8GB RAM, 256GB SSD, 14" FHD Display',
    'Available',
    'Good',
    'Yes',
    '2024-01-15',
    '45000.00',
    'Dell Philippines',
    '2027-01-15',
    'Purchased for laboratory use'
]);

// Write another sample
fputcsv($output, [
    'AST-2024-002',
    'HP LaserJet Pro MFP M428fdw',
    'Peripheral',
    'Printer',
    'HP',
    'LaserJet Pro MFP M428fdw',
    'VNBR123456',
    'Mono Laser, Print/Scan/Copy/Fax, 38ppm, Duplex, WiFi',
    'Available',
    'Excellent',
    'No',
    '2024-02-01',
    '25000.00',
    'HP Store Manila',
    '2025-02-01',
    'Office printer for faculty room'
]);

fclose($output);
exit();
?>
