<?php
session_start();
require_once '../config/config.php';
require_once '../model/Asset.php';

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    $_SESSION['error_message'] = 'Unauthorized access';
    header("Location: ../view/LaboratoryStaff/registry.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Invalid request method';
    header("Location: ../view/LaboratoryStaff/registry.php");
    exit();
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error_message'] = 'No file uploaded or upload error occurred';
    header("Location: ../view/LaboratoryStaff/registry.php");
    exit();
}

$file = $_FILES['csv_file'];

// Validate file type
if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
    $_SESSION['error_message'] = 'Invalid file type. Please upload a CSV file';
    header("Location: ../view/LaboratoryStaff/registry.php");
    exit();
}

// Validate file size (max 5MB)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    $_SESSION['error_message'] = 'File size exceeds 5MB limit';
    header("Location: ../view/LaboratoryStaff/registry.php");
    exit();
}

try {
    $handle = fopen($file['tmp_name'], 'r');
    
    if ($handle === false) {
        throw new Exception('Failed to open CSV file');
    }
    
    // Read header row
    $headers = fgetcsv($handle);
    
    if (!$headers) {
        throw new Exception('CSV file is empty');
    }
    
    // Normalize headers (trim and lowercase)
    $headers = array_map(function($h) {
        return strtolower(trim($h));
    }, $headers);
    
    // Check required columns
    $requiredColumns = ['asset_tag', 'asset_name', 'asset_type'];
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $headers)) {
            throw new Exception("Missing required column: $col");
        }
    }
    
    $asset = new Asset();
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    $lineNumber = 1;
    
    // Process each row
    while (($row = fgetcsv($handle)) !== false) {
        $lineNumber++;
        
        if (count($row) !== count($headers)) {
            $errorCount++;
            $errors[] = "Line $lineNumber: Column count mismatch";
            continue;
        }
        
        // Create associative array
        $data = array_combine($headers, $row);
        
        // Validate required fields
        if (empty($data['asset_tag']) || empty($data['asset_name']) || empty($data['asset_type'])) {
            $errorCount++;
            $errors[] = "Line $lineNumber: Missing required fields";
            continue;
        }
        
        try {
            // Set asset properties
            $asset->asset_tag = trim($data['asset_tag']);
            $asset->asset_name = trim($data['asset_name']);
            $asset->asset_type = trim($data['asset_type']);
            $asset->category = isset($data['category']) ? trim($data['category']) : null;
            $asset->brand = isset($data['brand']) ? trim($data['brand']) : null;
            $asset->model = isset($data['model']) ? trim($data['model']) : null;
            $asset->serial_number = isset($data['serial_number']) ? trim($data['serial_number']) : null;
            $asset->specifications = isset($data['specifications']) ? trim($data['specifications']) : null;
            $asset->status = isset($data['status']) ? trim($data['status']) : 'Available';
            $asset->condition = isset($data['condition']) ? trim($data['condition']) : 'Good';
            $asset->is_borrowable = isset($data['is_borrowable']) && (strtolower($data['is_borrowable']) === 'yes' || $data['is_borrowable'] === '1') ? 1 : 0;
            $asset->purchase_date = isset($data['purchase_date']) && !empty($data['purchase_date']) ? $data['purchase_date'] : null;
            $asset->purchase_cost = isset($data['purchase_cost']) && !empty($data['purchase_cost']) ? floatval($data['purchase_cost']) : null;
            $asset->supplier = isset($data['supplier']) ? trim($data['supplier']) : null;
            $asset->warranty_expiry = isset($data['warranty_expiry']) && !empty($data['warranty_expiry']) ? $data['warranty_expiry'] : null;
            $asset->notes = isset($data['notes']) ? trim($data['notes']) : null;
            $asset->created_by = $_SESSION['user_id'];
            $asset->updated_by = $_SESSION['user_id'];
            
            if ($asset->create()) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Line $lineNumber: Failed to create asset";
            }
        } catch (Exception $e) {
            $errorCount++;
            $errors[] = "Line $lineNumber: " . $e->getMessage();
        }
    }
    
    fclose($handle);
    
    // Log activity
    if ($successCount > 0) {
        require_once '../model/ActivityLog.php';
        ActivityLog::record(
            $_SESSION['user_id'],
            'import',
            'asset',
            null,
            'Imported ' . $successCount . ' asset(s) from CSV file'
        );
    }
    
    // Set success/error message
    if ($successCount > 0) {
        $_SESSION['success_message'] = "Successfully imported $successCount asset(s)";
        if ($errorCount > 0) {
            $_SESSION['success_message'] .= " with $errorCount error(s)";
        }
    } else {
        $_SESSION['error_message'] = "Failed to import any assets. $errorCount error(s) occurred";
    }
    
    // Store errors in session if any
    if (!empty($errors)) {
        $_SESSION['import_errors'] = array_slice($errors, 0, 10); // Limit to 10 errors
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Import error: ' . $e->getMessage();
}

header("Location: ../view/LaboratoryStaff/registry.php");
exit();
?>
