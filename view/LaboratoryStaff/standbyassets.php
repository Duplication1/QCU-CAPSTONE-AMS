<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has Laboratory Staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';

// Create database connection
if (!isset($conn)) {
    $conn = new mysqli('localhost', 'root', '', 'ams_database');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }
}

// Fetch asset categories
$categories = [];
$category_query = "SELECT id, name FROM asset_categories ORDER BY name ASC";
$category_result = $conn->query($category_query);
if ($category_result && $category_result->num_rows > 0) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_asset') {
        $asset_tag = trim($_POST['asset_tag'] ?? '');
        $asset_name = trim($_POST['asset_name'] ?? '');
        $asset_type = trim($_POST['asset_type'] ?? 'Hardware');
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        $end_of_life = !empty($_POST['end_of_life']) ? trim($_POST['end_of_life']) : null;
        $status = trim($_POST['status'] ?? 'Available');
        $condition = trim($_POST['condition'] ?? 'Good');
        $room_id = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
        
        if (empty($asset_tag) || empty($asset_name)) {
            echo json_encode(['success' => false, 'message' => 'Asset tag and name are required']);
            exit;
        }
        
        try {
            // Insert asset first to get ID
            $stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, serial_number, end_of_life, room_id, status, `condition`, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $created_by = $_SESSION['user_id'];
            $stmt->bind_param('sssssssissi', $asset_tag, $asset_name, $asset_type, $brand, $model, $serial_number, $end_of_life, $room_id, $status, $condition, $created_by);
            $success = $stmt->execute();
            $new_id = $conn->insert_id;
            $stmt->close();
            
            if ($success) {
                // Generate QR code with scan URL
                $base_url = 'http://192.168.100.15/QCU-CAPSTONE-AMS';
                $scan_url = $base_url . '/view/public/scan_asset.php?id=' . $new_id;
                $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($scan_url);
                
                // Update asset with QR code
                $update_qr = $conn->prepare("UPDATE assets SET qr_code = ? WHERE id = ?");
                $update_qr->bind_param('si', $qr_code_url, $new_id);
                $update_qr->execute();
                $update_qr->close();
                
                echo json_encode(['success' => true, 'message' => 'Asset created successfully', 'id' => $new_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create asset']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'get_next_asset_number') {
        $asset_name = trim($_POST['asset_name'] ?? '');
        
        if (empty($asset_name)) {
            echo json_encode(['success' => false, 'message' => 'Asset name is required']);
            exit;
        }
        
        // Create asset name prefix
        $asset_name_prefix = substr($asset_name, 0, min(10, strlen($asset_name)));
        $asset_name_prefix = strtoupper(str_replace(' ', '', $asset_name_prefix));
        
        // Find the highest existing number for this asset name (regardless of room)
        $pattern = "%-{$asset_name_prefix}-%";
        $query = $conn->prepare("SELECT asset_tag FROM assets WHERE asset_tag LIKE ?");
        $query->bind_param('s', $pattern);
        $query->execute();
        $result = $query->get_result();
        
        $max_number = 0;
        while ($row = $result->fetch_assoc()) {
            $asset_tag = $row['asset_tag'];
            // Extract the number part: last segment after last dash
            $parts = explode('-', $asset_tag);
            if (count($parts) >= 4) {
                $number_part = end($parts);
                if (is_numeric($number_part)) {
                    $number = intval($number_part);
                    if ($number > $max_number) {
                        $max_number = $number;
                    }
                }
            }
        }
        $query->close();
        
        $next_number = $max_number + 1;
        echo json_encode(['success' => true, 'next_number' => $next_number]);
        exit;
    }
    
    if ($action === 'get_next_start_number') {
        $asset_name_prefix = trim($_POST['asset_name'] ?? '');
        $room_number = trim($_POST['room_number'] ?? '');
        
        if (empty($asset_name_prefix) || empty($room_number)) {
            echo json_encode(['success' => false, 'message' => 'Asset name and room number are required']);
            exit;
        }
        
        // Find the highest existing number for this asset prefix and room
        $pattern = "%-{$asset_name_prefix}-{$room_number}-%";
        $query = $conn->prepare("SELECT asset_tag FROM assets WHERE asset_tag LIKE ?");
        $query->bind_param('s', $pattern);
        $query->execute();
        $result = $query->get_result();
        
        $max_number = 0;
        while ($row = $result->fetch_assoc()) {
            $asset_tag = $row['asset_tag'];
            // Extract the number part: last 3 characters before any extension
            $parts = explode('-', $asset_tag);
            if (count($parts) >= 4) {
                $number_part = end($parts);
                if (is_numeric($number_part)) {
                    $number = intval($number_part);
                    if ($number > $max_number) {
                        $max_number = $number;
                    }
                }
            }
        }
        $query->close();
        
        $next_number = $max_number + 1;
        echo json_encode(['success' => true, 'next_number' => $next_number]);
        exit;
    }
    
    if ($action === 'bulk_create_assets') {
        $acquisition_date = trim($_POST['acquisition_date'] ?? '');
        $asset_name_prefix = trim($_POST['asset_name'] ?? '');
        $room_number = trim($_POST['room_number'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $start_number = intval($_POST['start_number'] ?? 1);
        $asset_type = trim($_POST['asset_type'] ?? 'Hardware');
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $end_of_life = !empty($_POST['end_of_life']) ? trim($_POST['end_of_life']) : null;
        $status = trim($_POST['status'] ?? 'Available');
        $condition = trim($_POST['condition'] ?? 'Good');
        $room_id = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
        $is_borrowable = isset($_POST['is_borrowable']) ? intval($_POST['is_borrowable']) : 0;
        
        if (empty($acquisition_date) || empty($asset_name_prefix) || empty($room_number) || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
            exit;
        }
        
        if ($quantity > 100) {
            echo json_encode(['success' => false, 'message' => 'Maximum 100 assets can be created at once']);
            exit;
        }
        
        try {
            // Format date as MM-DD-YYYY
            $date_parts = explode('-', $acquisition_date);
            $formatted_date = $date_parts[1] . '-' . $date_parts[2] . '-' . $date_parts[0];
            
            $created_count = 0;
            $failed = [];
            $created_asset_ids = [];
            $created_by = $_SESSION['user_id'];
            
            for ($i = 0; $i < $quantity; $i++) {
                $current_number = $start_number + $i;
                $padded_number = str_pad($current_number, 3, '0', STR_PAD_LEFT);
                
                $asset_tag = "{$formatted_date}-{$asset_name_prefix}-{$room_number}-{$padded_number}";
                $asset_name = "{$asset_name_prefix} #{$current_number}";
                
                // Check if asset tag already exists
                $check = $conn->prepare("SELECT id FROM assets WHERE asset_tag = ?");
                $check->bind_param('s', $asset_tag);
                $check->execute();
                $check->store_result();
                
                if ($check->num_rows > 0) {
                    $failed[] = $asset_tag . ' (already exists)';
                    $check->close();
                    continue;
                }
                $check->close();
                
                // Insert asset first to get ID
                $stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, end_of_life, room_id, status, `condition`, is_borrowable, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssssssii', $asset_tag, $asset_name, $asset_type, $brand, $model, $end_of_life, $room_id, $status, $condition, $is_borrowable, $created_by);
                
                if ($stmt->execute()) {
                    $new_id = $conn->insert_id;
                    $created_count++;
                    $created_asset_ids[] = $new_id;
                    
                    // Generate QR code with scan URL
                    $base_url = 'http://192.168.100.15/QCU-CAPSTONE-AMS';
                    $scan_url = $base_url . '/view/public/scan_asset.php?id=' . $new_id;
                    $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($scan_url);
                    
                    // Update asset with QR code
                    $update_qr = $conn->prepare("UPDATE assets SET qr_code = ? WHERE id = ?");
                    $update_qr->bind_param('si', $qr_code_url, $new_id);
                    $update_qr->execute();
                    $update_qr->close();
                } else {
                    $failed[] = $asset_tag . ' (insert failed)';
                }
                $stmt->close();
            }
            
            $message = "Successfully created {$created_count} asset(s) with unique QR codes";
            if (!empty($failed)) {
                $message .= ". Failed: " . implode(', ', $failed);
            }
            
            echo json_encode([
                'success' => $created_count > 0, 
                'message' => $message, 
                'created' => $created_count,
                'failed_count' => count($failed),
                'asset_ids' => $created_asset_ids
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'update_asset') {
        $id = intval($_POST['id'] ?? 0);
        $asset_tag = trim($_POST['asset_tag'] ?? '');
        $asset_name = trim($_POST['asset_name'] ?? '');
        $asset_type = trim($_POST['asset_type'] ?? 'Hardware');
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        $status = trim($_POST['status'] ?? 'Available');
        $condition = trim($_POST['condition'] ?? 'Good');
        $room_id = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
        $is_borrowable = isset($_POST['is_borrowable']) ? intval($_POST['is_borrowable']) : 0;
        
        if ($id <= 0 || empty($asset_tag) || empty($asset_name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE assets SET asset_tag = ?, asset_name = ?, asset_type = ?, brand = ?, model = ?, serial_number = ?, room_id = ?, status = ?, `condition` = ?, is_borrowable = ?, updated_by = ? WHERE id = ?");
            $updated_by = $_SESSION['user_id'];
            $stmt->bind_param('ssssssissiis', $asset_tag, $asset_name, $asset_type, $brand, $model, $serial_number, $room_id, $status, $condition, $is_borrowable, $updated_by, $id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Asset updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update asset']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'archive_asset') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid asset ID']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE assets SET status = 'Archive', updated_by = ? WHERE id = ?");
            $updated_by = $_SESSION['user_id'];
            $stmt->bind_param('ii', $updated_by, $id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Asset archived successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to archive asset']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'get_asset_qrcodes') {
        $asset_ids = json_decode($_POST['asset_ids'] ?? '[]', true);
        
        if (empty($asset_ids) || !is_array($asset_ids)) {
            echo json_encode(['success' => false, 'message' => 'Invalid asset IDs']);
            exit;
        }
        
        try {
            $placeholders = str_repeat('?,', count($asset_ids) - 1) . '?';
            $stmt = $conn->prepare("SELECT id, asset_tag, asset_name, asset_type, brand, model, qr_code FROM assets WHERE id IN ($placeholders)");
            $types = str_repeat('i', count($asset_ids));
            $stmt->bind_param($types, ...$asset_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $assets_data = [];
            while ($row = $result->fetch_assoc()) {
                $assets_data[] = $row;
            }
            $stmt->close();
            
            echo json_encode(['success' => true, 'assets' => $assets_data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'add_category') {
        $category_name = trim($_POST['category_name'] ?? '');
        
        if (empty($category_name)) {
            echo json_encode(['success' => false, 'message' => 'Category name is required']);
            exit;
        }
        
        // Check if category already exists
        $check_stmt = $conn->prepare("SELECT id FROM asset_categories WHERE name = ?");
        $check_stmt->bind_param('s', $category_name);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Category already exists']);
            $check_stmt->close();
            exit;
        }
        $check_stmt->close();
        
        // Add new category
        $insert_stmt = $conn->prepare("INSERT INTO asset_categories (name) VALUES (?)");
        $insert_stmt->bind_param('s', $category_name);
        $success = $insert_stmt->execute();
        $new_id = $conn->insert_id;
        $insert_stmt->close();
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Category added successfully', 'id' => $new_id, 'name' => $category_name]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add category']);
        }
        exit;
    }
    
    if ($action === 'bulk_archive_assets') {
        $asset_ids = json_decode($_POST['asset_ids'] ?? '[]', true);
        
        if (empty($asset_ids) || !is_array($asset_ids)) {
            echo json_encode(['success' => false, 'message' => 'Invalid asset IDs']);
            exit;
        }
        
        try {
            $placeholders = str_repeat('?,', count($asset_ids) - 1) . '?';
            $stmt = $conn->prepare("UPDATE assets SET status = 'Archive', updated_by = ? WHERE id IN ($placeholders)");
            $params = array_merge([$_SESSION['user_id']], $asset_ids);
            $stmt->bind_param(str_repeat('i', count($params)), ...$params);
            $success = $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => "Successfully archived {$affected_rows} asset(s)"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to archive assets']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'import_excel_rows') {
        $headers = json_decode($_POST['headers'] ?? '[]', true);
        $rows = json_decode($_POST['rows'] ?? '[]', true);
        $created = [];
        $errors = [];
        
        try {
            foreach ($rows as $idx => $row) {
                $asset_tag = trim($row['Asset Tag'] ?? $row['asset tag'] ?? '');
                $asset_name = trim($row['Asset Name'] ?? $row['asset name'] ?? '');
                $asset_type = trim($row['Type'] ?? $row['type'] ?? 'Hardware');
                $brandModel = trim($row['Brand/Model'] ?? $row['brand/model'] ?? '');
                $serial_number = trim($row['Serial Number'] ?? $row['serial number'] ?? '');
                $room_name = trim($row['Room'] ?? $row['room'] ?? '');
                $status = trim($row['Status'] ?? $row['status'] ?? 'Available');
                $condition = trim($row['Condition'] ?? $row['condition'] ?? 'Good');
                
                if ($asset_tag === '' || $asset_name === '') {
                    $errors[] = "Row ".($idx+1).": Missing Asset Tag or Name";
                    continue;
                }
                
                // Check for duplicate asset tag
                $chk = $conn->prepare("SELECT id FROM assets WHERE asset_tag = ?");
                $chk->bind_param('s', $asset_tag);
                $chk->execute();
                $chk->store_result();
                if ($chk->num_rows > 0) {
                    $chk->close();
                    $errors[] = "Row ".($idx+1).": Duplicate asset tag";
                    continue;
                }
                $chk->close();
                
                // Split brand/model
                $brand = '';
                $model = '';
                if ($brandModel !== '') {
                    $parts = explode('/', $brandModel);
                    $brand = trim($parts[0]);
                    if (count($parts) > 1) { $model = trim($parts[1]); }
                }
                
                // Normalize status
                $status_map = [
                    'active' => 'Active',
                    'available' => 'Available',
                    'in use' => 'In Use',
                    'maintenance' => 'Under Maintenance',
                    'under maintenance' => 'Under Maintenance',
                    'retired' => 'Retired',
                    'disposed' => 'Disposed',
                    'lost' => 'Lost',
                    'damaged' => 'Damaged'
                ];
                $status_lower = strtolower($status);
                $status = $status_map[$status_lower] ?? 'Available';
                
                // Normalize condition
                $condition_map = [
                    'excellent' => 'Excellent',
                    'good' => 'Good',
                    'fair' => 'Fair',
                    'poor' => 'Poor',
                    'non-functional' => 'Non-Functional',
                    'nonfunctional' => 'Non-Functional'
                ];
                $condition_lower = strtolower($condition);
                $condition = $condition_map[$condition_lower] ?? 'Good';
                
                // Lookup room by name
                $room_id = null;
                if ($room_name !== '') {
                    $rm = $conn->prepare("SELECT id FROM rooms WHERE name = ?");
                    $rm->bind_param('s', $room_name);
                    $rm->execute();
                    $res = $rm->get_result();
                    if ($res && $res->num_rows > 0) {
                        $room_id = $res->fetch_assoc()['id'];
                    }
                    $rm->close();
                }
                
                // Generate QR code
                $qr_data = json_encode([
                    'asset_tag' => $asset_tag,
                    'asset_name' => $asset_name,
                    'asset_type' => $asset_type,
                    'room_id' => $room_id
                ]);
                $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr_data);
                
                $created_by = $_SESSION['user_id'];
                $ins = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, serial_number, room_id, status, `condition`, qr_code, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $ins->bind_param('ssssssisssi', $asset_tag, $asset_name, $asset_type, $brand, $model, $serial_number, $room_id, $status, $condition, $qr_code_url, $created_by);
                
                if ($ins->execute()) {
                    $created[] = [
                        'id' => $conn->insert_id,
                        'asset_tag' => $asset_tag,
                        'asset_name' => $asset_name,
                        'asset_type' => $asset_type,
                        'brand' => $brand,
                        'model' => $model,
                        'serial_number' => $serial_number,
                        'room_id' => $room_id,
                        'status' => $status,
                        'condition' => $condition
                    ];
                } else {
                    $errors[] = "Row ".($idx+1).": Insert failed";
                }
                $ins->close();
            }
            
            echo json_encode(['success' => true, 'created' => $created, 'errors' => $errors]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'import_excel_rows') {
        $headers = json_decode($_POST['headers'] ?? '[]', true);
        $rows = json_decode($_POST['rows'] ?? '[]', true);
        $created = [];
        $errors = [];
        
        try {
            foreach ($rows as $idx => $row) {
                $asset_tag = trim($row['Asset Tag'] ?? $row['asset tag'] ?? '');
                $asset_name = trim($row['Asset Name'] ?? $row['asset name'] ?? '');
                $asset_type = trim($row['Type'] ?? $row['type'] ?? 'Hardware');
                $brandModel = trim($row['Brand/Model'] ?? $row['brand/model'] ?? '');
                $serial_number = trim($row['Serial Number'] ?? $row['serial number'] ?? '');
                $room_name = trim($row['Room'] ?? $row['room'] ?? '');
                $status = trim($row['Status'] ?? $row['status'] ?? 'Available');
                $condition = trim($row['Condition'] ?? $row['condition'] ?? 'Good');
                
                if ($asset_tag === '' || $asset_name === '') {
                    $errors[] = "Row ".($idx+1).": Missing Asset Tag or Name";
                    continue;
                }
                
                $chk = $conn->prepare("SELECT id FROM assets WHERE asset_tag = ?");
                $chk->bind_param('s', $asset_tag);
                $chk->execute();
                $chk->store_result();
                if ($chk->num_rows > 0) {
                    $chk->close();
                    $errors[] = "Row ".($idx+1).": Duplicate asset tag";
                    continue;
                }
                $chk->close();
                
                $brand = '';
                $model = '';
                if ($brandModel !== '') {
                    $parts = explode('/', $brandModel);
                    $brand = trim($parts[0]);
                    if (count($parts) > 1) { $model = trim($parts[1]); }
                }
                
                $status_map = [
                    'active' => 'Active',
                    'available' => 'Available',
                    'in use' => 'In Use',
                    'maintenance' => 'Under Maintenance',
                    'under maintenance' => 'Under Maintenance',
                    'retired' => 'Retired',
                    'disposed' => 'Disposed',
                    'lost' => 'Lost',
                    'damaged' => 'Damaged'
                ];
                $status_lower = strtolower($status);
                $status = $status_map[$status_lower] ?? 'Available';
                
                $condition_map = [
                    'excellent' => 'Excellent',
                    'good' => 'Good',
                    'fair' => 'Fair',
                    'poor' => 'Poor',
                    'non-functional' => 'Non-Functional',
                    'nonfunctional' => 'Non-Functional'
                ];
                $condition_lower = strtolower($condition);
                $condition = $condition_map[$condition_lower] ?? 'Good';
                
                $room_id = null;
                if ($room_name !== '') {
                    $rm = $conn->prepare("SELECT id FROM rooms WHERE name = ?");
                    $rm->bind_param('s', $room_name);
                    $rm->execute();
                    $res = $rm->get_result();
                    if ($res && $res->num_rows > 0) {
                        $room_id = $res->fetch_assoc()['id'];
                    }
                    $rm->close();
                }
                
                $qr_data = json_encode([
                    'asset_tag' => $asset_tag,
                    'asset_name' => $asset_name,
                    'asset_type' => $asset_type,
                    'room_id' => $room_id
                ]);
                $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr_data);
                
                $created_by = $_SESSION['user_id'];
                $ins = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, serial_number, room_id, status, `condition`, qr_code, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $ins->bind_param('ssssssisssi', $asset_tag, $asset_name, $asset_type, $brand, $model, $serial_number, $room_id, $status, $condition, $qr_code_url, $created_by);
                
                if ($ins->execute()) {
                    $created[] = [
                        'id' => $conn->insert_id,
                        'asset_tag' => $asset_tag,
                        'asset_name' => $asset_name,
                        'asset_type' => $asset_type,
                        'brand' => $brand,
                        'model' => $model,
                        'serial_number' => $serial_number,
                        'room_id' => $room_id,
                        'status' => $status,
                        'condition' => $condition
                    ];
                } else {
                    $errors[] = "Row ".($idx+1).": Insert failed";
                }
                $ins->close();
            }
            
            echo json_encode(['success' => true, 'created' => $created, 'errors' => $errors]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Fetch assets with search, filter, and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
$filter_type = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : '';
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] === '1';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 9;
// Handle "all" entries
if ($per_page <= 0) $per_page = 999999; // Show all
$limit = $per_page;
$offset = ($page - 1) * $limit;

// Count total standby assets (not assigned to any room) - always include archived for client-side filtering
$count_query = "SELECT COUNT(*) as total FROM assets WHERE (room_id IS NULL OR room_id = 0)";
$params = [];
$types = '';

if ($show_archived) {
    $count_query .= " AND status IN ('Archive', 'Archived')";
} else {
    $count_query .= " AND status NOT IN ('Archive', 'Archived')";
}

if (!empty($filter_status)) {
    $count_query .= " AND status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($filter_type)) {
    $count_query .= " AND asset_type = ?";
    $params[] = $filter_type;
    $types .= 's';
}

if (!empty($search)) {
    $count_query .= " AND (asset_tag LIKE ? OR asset_name LIKE ? OR brand LIKE ? OR model LIKE ? OR serial_number LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $types .= 'sssss';
}

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_result = $count_stmt->get_result();
    $total_assets = $total_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_result = $conn->query($count_query);
    $total_assets = $total_result->fetch_assoc()['total'];
}

$total_pages = ceil($total_assets / $limit);

// Fetch standby assets (not assigned to any room) - always include all assets for client-side filtering
$assets = [];
$query_sql = "SELECT a.*, r.name as room_name FROM assets a LEFT JOIN rooms r ON a.room_id = r.id WHERE (a.room_id IS NULL OR a.room_id = 0)";
$params = [];
$types = '';

if ($show_archived) {
    $query_sql .= " AND a.status IN ('Archive', 'Archived')";
} else {
    $query_sql .= " AND a.status NOT IN ('Archive', 'Archived')";
}

if (!empty($filter_status)) {
    $query_sql .= " AND a.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($filter_type)) {
    $query_sql .= " AND a.asset_type = ?";
    $params[] = $filter_type;
    $types .= 's';
}

if (!empty($search)) {
    $query_sql .= " AND (a.asset_tag LIKE ? OR a.asset_name LIKE ? OR a.brand LIKE ? OR a.model LIKE ? OR a.serial_number LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $types .= 'sssss';
}

$query_sql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

if (!empty($types)) {
    $query = $conn->prepare($query_sql);
    $query->bind_param($types, ...$params);
    $query->execute();
    $result = $query->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $assets[] = $row;
        }
    }
    $query->close();
}

include '../components/layout_header.php';
?>

<style>
main {
    padding: 0.5rem;
    background-color: #f9fafb;
    min-height: 100%;
}
@media print {
    body * {
        visibility: hidden;
    }
    #qrPrintContent, #qrPrintContent * {
        visibility: visible;
    }
    #qrPrintContent {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        padding: 20px;
    }
    .qr-item {
        page-break-inside: avoid;
        border: 1px solid #000;
        padding: 10px;
        text-align: center;
    }
}
</style>

<main>
    <div class="flex-1 flex flex-col">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white rounded shadow-sm border border-gray-200 mb-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Standby Assets</h3>
                <p class="text-xs text-gray-500 mt-0.5">Assets not assigned to any room • Total: <?php echo $total_assets; ?> asset(s)</p>
            </div>
            
            <div class="flex gap-2">
                <button onclick="openImportExcelModal()" 
                        class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                    <i class="fa-solid fa-file-import"></i>
                    <span>Import Excel</span>
                </button>
                <button onclick="openAddAssetModal()" 
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add Asset</span>
                </button>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 px-4 py-3">
            <form method="GET" action="" class="flex flex-wrap gap-3" id="filterForm">
                <div class="flex-1 min-w-[250px]">
                    <input type="text" name="search" id="searchInput" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by tag, name, brand, model, serial..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <select name="filter_status" id="filter_status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="Active" <?php echo $filter_status === 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Available" <?php echo $filter_status === 'Available' ? 'selected' : ''; ?>>Available</option>
                    <option value="In Use" <?php echo $filter_status === 'In Use' ? 'selected' : ''; ?>>In Use</option>
                    <option value="Under Maintenance" <?php echo $filter_status === 'Under Maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                    <option value="Damaged" <?php echo $filter_status === 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
                    <option value="Retired" <?php echo $filter_status === 'Retired' ? 'selected' : ''; ?>>Retired</option>
                </select>
                <select name="filter_type" id="filter_type" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Types</option>
                    <option value="Hardware" <?php echo $filter_type === 'Hardware' ? 'selected' : ''; ?>>Hardware</option>
                    <option value="Software" <?php echo $filter_type === 'Software' ? 'selected' : ''; ?>>Software</option>
                    <option value="Furniture" <?php echo $filter_type === 'Furniture' ? 'selected' : ''; ?>>Furniture</option>
                    <option value="Equipment" <?php echo $filter_type === 'Equipment' ? 'selected' : ''; ?>>Equipment</option>
                    <option value="Peripheral" <?php echo $filter_type === 'Peripheral' ? 'selected' : ''; ?>>Peripheral</option>
                    <option value="Network Device" <?php echo $filter_type === 'Network Device' ? 'selected' : ''; ?>>Network Device</option>
                </select>
                <label class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" name="show_archived" value="1" <?php echo $show_archived ? 'checked' : ''; ?> class="rounded">
                    <span class="text-sm text-gray-700">Show Only Archived Assets</span>
                </label>
                <select name="per_page" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="10" <?php echo ($per_page == 10) ? 'selected' : ''; ?>>Show 10</option>
                    <option value="25" <?php echo ($per_page == 25) ? 'selected' : ''; ?>>Show 25</option>
                    <option value="50" <?php echo ($per_page == 50) ? 'selected' : ''; ?>>Show 50</option>
                    <option value="0" <?php echo ($per_page == 999999) ? 'selected' : ''; ?>>Show All</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-search"></i>
                </button>
                <?php if (!empty($search) || !empty($filter_status) || !empty($filter_type) || $show_archived): ?>
                    <a href="standbyassets.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        <i class="fa-solid fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Bulk Actions -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 px-4 py-3 hidden" id="bulkActions">
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-600">
                    <span id="selectedCount">0</span> selected
                </span>
                <button onclick="openBulkEditModal()" 
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-edit mr-2"></i>Edit Selected
                </button>
                <button onclick="editSelectedAssetTags()" 
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700 transition-colors">
                    <i class="fa-solid fa-tag mr-2"></i>Edit Asset Tags
                </button>
                <button onclick="printSelectedQRCodes()" 
                        class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded hover:bg-purple-700 transition-colors">
                    <i class="fa-solid fa-qrcode mr-2"></i>Print QR Codes
                </button>
                <button onclick="bulkArchive()" 
                        class="px-4 py-2 bg-[#1E3A8A] text-white text-sm font-medium rounded hover:bg-[#153570] transition-colors">
                    <i class="fa-solid fa-archive mr-2"></i>Archive Selected
                </button>
                <button onclick="clearSelection()" 
                        class="px-3 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded hover:bg-gray-300 transition-colors">
                    Clear
                </button>
            </div>
        </div>

        <!-- Assets Table -->
        <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
            <table class="w-full">
                <thead class="bg-[#1E3A8A] text-white sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-2 text-center w-12">
                            <input type="checkbox" id="selectAll" class="rounded cursor-pointer">
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">#</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Asset Tag</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Asset Name</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Type</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Brand/Model</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Serial Number</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Room</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Condition</th>
                        <th class="px-3 py-2 text-center text-xs font-medium uppercase tracking-wider w-20">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100" id="assetsTableBody">
                    <?php if (empty($assets)): ?>
                        <tr id="noResultsRow">
                            <td colspan="11" class="px-6 py-12 text-center text-gray-500">
                                <i class="fa-solid fa-box text-5xl mb-3 opacity-30"></i>
                                <p class="text-lg">No standby assets found</p>
                                <?php if (!empty($search) || !empty($filter_status) || !empty($filter_type)): ?>
                                    <p class="text-sm">Try adjusting your filters</p>
                                <?php else: ?>
                                    <p class="text-sm">All assets are assigned to rooms or click "Add Asset" to create one</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assets as $index => $asset): ?>
                            <tr class="hover:bg-blue-50 transition-colors asset-row" 
                                data-status="<?php echo htmlspecialchars($asset['status']); ?>"
                                data-type="<?php echo htmlspecialchars($asset['asset_type']); ?>"
                                data-tag="<?php echo htmlspecialchars($asset['asset_tag']); ?>"
                                data-name="<?php echo htmlspecialchars($asset['asset_name']); ?>"
                                data-brand="<?php echo htmlspecialchars($asset['brand'] ?? ''); ?>"
                                data-model="<?php echo htmlspecialchars($asset['model'] ?? ''); ?>"
                                data-serial="<?php echo htmlspecialchars($asset['serial_number'] ?? ''); ?>">
                                <td class="px-3 py-2 text-center">
                                    <input type="checkbox" class="asset-checkbox rounded cursor-pointer" value="<?php echo $asset['id']; ?>" onchange="updateSelectAllState(); updateBulkActions();">
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-500">
                                    <?php echo $offset + $index + 1; ?>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-xs font-medium text-blue-600"><?php echo htmlspecialchars($asset['asset_tag']); ?></span>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="max-w-xs truncate">
                                        <span class="text-xs font-medium text-gray-900"><?php echo htmlspecialchars($asset['asset_name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-700 rounded">
                                        <?php echo htmlspecialchars($asset['asset_type']); ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-700">
                                    <div class="max-w-xs truncate">
                                        <?php 
                                        $brand_model = array_filter([
                                            $asset['brand'] ?? '', 
                                            $asset['model'] ?? ''
                                        ]);
                                        echo htmlspecialchars(implode(' - ', $brand_model) ?: 'N/A'); 
                                        ?>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-700">
                                    <div class="max-w-xs truncate"><?php echo htmlspecialchars($asset['serial_number'] ?: 'N/A'); ?></div>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-700">
                                    <div class="max-w-xs truncate"><?php echo htmlspecialchars($asset['room_name'] ?: 'Standby'); ?></div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <?php
                                    $status_colors = [
                                        'Active' => 'bg-green-100 text-green-700',
                                        'In Use' => 'bg-blue-100 text-blue-700',
                                        'Available' => 'bg-green-100 text-green-700',
                                        'Under Maintenance' => 'bg-yellow-100 text-yellow-700',
                                        'Retired' => 'bg-gray-100 text-gray-700',
                                        'Disposed' => 'bg-red-100 text-red-700',
                                        'Lost' => 'bg-red-100 text-red-700',
                                        'Damaged' => 'bg-orange-100 text-orange-700',
                                        'Archive' => 'bg-purple-100 text-purple-700',
                                        'Archived' => 'bg-purple-100 text-purple-700'
                                    ];
                                    $status_class = $status_colors[$asset['status']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-medium rounded <?php echo $status_class; ?>" data-status-badge>
                                        <?php echo htmlspecialchars($asset['status']); ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <?php
                                    $condition_colors = [
                                        'Excellent' => 'bg-green-50 text-green-700 border border-green-300',
                                        'Good' => 'bg-blue-50 text-blue-700 border border-blue-300',
                                        'Fair' => 'bg-yellow-50 text-yellow-700 border border-yellow-300',
                                        'Poor' => 'bg-orange-50 text-orange-700 border border-orange-300',
                                        'Non-Functional' => 'bg-red-50 text-red-700 border border-red-300'
                                    ];
                                    $condition_class = $condition_colors[$asset['condition']] ?? 'bg-gray-50 text-gray-700 border border-gray-300';
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-medium rounded <?php echo $condition_class; ?>">
                                        <?php echo htmlspecialchars($asset['condition']); ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                                    <div class="relative inline-block">
                                        <button onclick="toggleMenu(<?php echo $asset['id']; ?>)" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-blue-50 rounded-full focus:outline-none transition-colors">
                                            <i class="fa-solid fa-ellipsis-vertical text-base"></i>
                                        </button>
                                        <div id="menu-<?php echo $asset['id']; ?>" class="hidden absolute right-0 mt-2 bg-white rounded-lg shadow-lg border border-gray-200 z-50" style="min-width: 11rem;">
                                            <div class="py-1">
                                                <button onclick='printQRCode(<?php echo json_encode($asset); ?>)' 
                                                        class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-blue-50 flex items-center gap-2">
                                                    <i class="fa-solid fa-qrcode text-purple-600"></i> Print QR Code
                                                </button>
                                                <button onclick='editAsset(<?php echo json_encode($asset); ?>)' 
                                                        class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-blue-50 flex items-center gap-2">
                                                    <i class="fa-solid fa-pencil text-blue-600"></i> Edit
                                                </button>
                                                <?php if ($asset['status'] !== 'Archived'): ?>
                                                <button onclick="archiveAsset(<?php echo $asset['id']; ?>, '<?php echo htmlspecialchars($asset['asset_tag'], ENT_QUOTES); ?>')" 
                                                        class="w-full text-left px-3 py-2 text-xs text-orange-600 hover:bg-orange-50 flex items-center gap-2">
                                                    <i class="fa-solid fa-archive"></i> Archive
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="bg-white rounded shadow-sm border border-gray-200 mt-3 px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Showing <?php echo count($assets); ?> of <?php echo $total_assets; ?> assets
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_status) ? '&filter_status=' . urlencode($filter_status) : ''; ?><?php echo !empty($filter_type) ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo !empty($filter_room) ? '&filter_room=' . urlencode($filter_room) : ''; ?><?php echo $show_archived ? '&show_archived=1' : ''; ?>" 
                           class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                            <i class="fa-solid fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_status) ? '&filter_status=' . urlencode($filter_status) : ''; ?><?php echo !empty($filter_type) ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo !empty($filter_room) ? '&filter_room=' . urlencode($filter_room) : ''; ?><?php echo $show_archived ? '&show_archived=1' : ''; ?>" 
                           class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="px-2 text-gray-500">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_status) ? '&filter_status=' . urlencode($filter_status) : ''; ?><?php echo !empty($filter_type) ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo !empty($filter_room) ? '&filter_room=' . urlencode($filter_room) : ''; ?><?php echo $show_archived ? '&show_archived=1' : ''; ?>" 
                           class="px-3 py-1 text-sm rounded <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition-colors">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="px-2 text-gray-500">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_status) ? '&filter_status=' . urlencode($filter_status) : ''; ?><?php echo !empty($filter_type) ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo !empty($filter_room) ? '&filter_room=' . urlencode($filter_room) : ''; ?><?php echo $show_archived ? '&show_archived=1' : ''; ?>" 
                           class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_status) ? '&filter_status=' . urlencode($filter_status) : ''; ?><?php echo !empty($filter_type) ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo !empty($filter_room) ? '&filter_room=' . urlencode($filter_room) : ''; ?><?php echo $show_archived ? '&show_archived=1' : ''; ?>" 
                           class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                            Next <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Import Excel Modal -->
<div id="importExcelModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Import Assets from Excel</h3>
        </div>
        <div class="p-6 space-y-4">
            <div class="text-sm text-gray-700">
                <p class="mb-2 font-medium">Expected columns:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Asset Tag</li>
                    <li>Asset Name</li>
                    <li>Type</li>
                    <li>Brand/Model</li>
                    <li>Serial Number</li>
                    <li>Room (name)</li>
                    <li>Status</li>
                    <li>Condition</li>
                </ul>
                <p class="mt-3 text-xs text-gray-600">
                    <i class="fa-solid fa-info-circle mr-1"></i>
                    Leave Room empty for standby assets. Brand/Model can be split with "/".
                </p>
            </div>
            <input type="file" id="importExcelFile" accept=".xlsx,.xls,.csv" class="w-full border border-gray-300 rounded px-3 py-2">
            <div class="flex gap-3 pt-2">
                <button onclick="closeImportExcelModal()" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                <button onclick="processImportExcel()" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md">Import</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Asset Modal -->
<div id="addAssetModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Add New Asset</h3>
        </div>
        <form id="addAssetForm" class="p-6">
            <!-- Mode Selection -->
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-2">Creation Mode</label>
                <div class="flex gap-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="asset_bulk_mode" value="single" checked onchange="toggleAssetBulkMode()" 
                               class="mr-2 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm">Single Asset</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="asset_bulk_mode" value="bulk" onchange="toggleAssetBulkMode()" 
                               class="mr-2 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm">Bulk (Multiple Assets)</span>
                    </label>
                </div>
            </div>

            <!-- Single Mode Fields -->
            <div id="singleAssetModeFields" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Asset Tag *</label>
                    <input type="text" id="assetTag" name="asset_tag" readonly
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Auto-generated">
                    <p class="text-xs text-gray-500 mt-1">Automatically generated based on asset name and room</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Asset Name *</label>
                    <div class="relative">
                        <select id="assetName" name="asset_name" class="hidden">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                            <option value="__add_new__">+ Add New Category</option>
                        </select>
                        <div class="searchable-dropdown">
                            <div class="dropdown-display w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white cursor-pointer flex items-center justify-between">
                                <span class="selected-text text-gray-500">Select Category</span>
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                            <div class="dropdown-options absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 hidden max-h-60 overflow-y-auto">
                                <div class="p-2 border-b border-gray-200">
                                    <input type="text" class="dropdown-search w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Search categories...">
                                </div>
                                <div class="dropdown-option px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm text-blue-600 font-medium border-b border-gray-200" data-value="__add_new__">
                                    <i class="fa-solid fa-plus mr-2"></i>Add New Category
                                </div>
                                <div class="dropdown-list">
                                    <?php foreach ($categories as $category): ?>
                                        <div class="dropdown-option px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm" data-value="<?php echo htmlspecialchars($category['name']); ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Asset Type</label>
                    <select id="assetType" name="asset_type" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="Hardware">Hardware</option>
                        <option value="Software">Software</option>
                        <option value="Furniture">Furniture</option>
                        <option value="Equipment">Equipment</option>
                        <option value="Peripheral">Peripheral</option>
                        <option value="Network Device">Network Device</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                    <input type="text" id="brand" name="brand"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="e.g., Dell, HP">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                    <input type="text" id="model" name="model"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="e.g., OptiPlex 7090">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Serial Number</label>
                    <input type="text" id="serialNumber" name="serial_number"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="e.g., SN123456789">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End of Life</label>
                    <input type="date" id="endOfLife" name="end_of_life"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Room</label>
                    <input type="text" value="NOROOM (Standby)" disabled
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed">
                    <input type="hidden" id="roomId" name="room_id" value="">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" name="status" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="Active">Active</option>
                        <option value="Available" selected>Available</option>
                        <option value="In Use">In Use</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                        <option value="Retired">Retired</option>
                        <option value="Damaged">Damaged</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Condition</label>
                    <select id="condition" name="condition" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="Excellent">Excellent</option>
                        <option value="Good" selected>Good</option>
                        <option value="Fair">Fair</option>
                        <option value="Poor">Poor</option>
                        <option value="Non-Functional">Non-Functional</option>
                    </select>
                </div>
                <div>
                    <label class="flex items-center cursor-pointer mt-6">
                        <input type="checkbox" id="isBorrowable" name="is_borrowable" value="1"
                               class="mr-2 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Allow this asset to be borrowed</span>
                    </label>
                </div>
            </div>

            <!-- Bulk Mode Fields -->
            <div id="bulkAssetModeFields" class="hidden space-y-4">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-start gap-2">
                        <i class="fa-solid fa-info-circle text-green-700 mt-1"></i>
                        <div class="text-sm text-green-900">
                            <p class="font-medium mb-1">Bulk Asset Creation</p>
                            <p>Create multiple assets with sequential numbering and unique QR codes.</p>
                            <p class="mt-1 text-xs">Example: 11-23-2025-LAPTOP-NOROOM-001 through 11-23-2025-LAPTOP-NOROOM-020</p>
                        </div>
                    </div>
                </div>

                <!-- Asset Tag Preview at Top -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fa-solid fa-tag mr-1 text-blue-500"></i>Asset Tag Preview
                    </label>
                    <input type="text" id="bulkAssetTagPreview" readonly
                           class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm font-mono text-gray-700"
                           placeholder="Asset tags will appear here...">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Acquisition Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="bulkAcquisitionDate" name="bulk_acquisition_date" 
                               value="<?php echo date('Y-m-d'); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Asset Name <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select id="bulkAssetName" name="bulk_asset_name" class="hidden">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                                <option value="__add_new__">+ Add New Category</option>
                            </select>
                            <div class="searchable-dropdown">
                                <div class="dropdown-display w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white cursor-pointer flex items-center justify-between">
                                    <span class="selected-text text-gray-500">Select Category</span>
                                    <i class="fa-solid fa-chevron-down text-gray-400"></i>
                                </div>
                                <div class="dropdown-options absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 hidden max-h-60 overflow-y-auto">
                                    <div class="p-2 border-b border-gray-200">
                                        <input type="text" class="dropdown-search w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Search categories...">
                                    </div>
                                    <div class="dropdown-option px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm text-blue-600 font-medium border-b border-gray-200" data-value="__add_new__">
                                        <i class="fa-solid fa-plus mr-2"></i>Add New Category
                                    </div>
                                    <div class="dropdown-list">
                                        <?php foreach ($categories as $category): ?>
                                            <div class="dropdown-option px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm" data-value="<?php echo htmlspecialchars($category['name']); ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Quantity <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="bulkQuantity" name="bulk_quantity" 
                               min="1" max="100" value="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Starting Number <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="bulkStartNumber" name="bulk_start_number" 
                               min="1" value="1" readonly
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Automatically calculated based on existing assets</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Asset Type</label>
                        <select id="bulkAssetType" name="bulk_asset_type" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Hardware">Hardware</option>
                            <option value="Software">Software</option>
                            <option value="Furniture">Furniture</option>
                            <option value="Equipment">Equipment</option>
                            <option value="Peripheral">Peripheral</option>
                            <option value="Network Device">Network Device</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                        <input type="text" id="bulkBrand" name="bulk_brand"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                        <input type="text" id="bulkModel" name="bulk_model"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End of Life</label>
                        <input type="date" id="bulkEndOfLife" name="bulk_end_of_life"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Condition</label>
                        <select id="bulkCondition" name="bulk_condition" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Excellent">Excellent</option>
                            <option value="Good" selected>Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                            <option value="Non-Functional">Non-Functional</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Room</label>
                        <input type="text" value="NOROOM (Standby)" disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed">
                        <input type="hidden" id="bulkRoomId" name="bulk_room_id" value="">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="bulkStatus" name="bulk_status" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Active">Active</option>
                            <option value="Available" selected>Available</option>
                            <option value="In Use">In Use</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="bulkIsBorrowable" name="bulk_is_borrowable" value="1"
                               class="mr-2 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Allow these assets to be borrowed</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-3 justify-end mt-6">
                <button type="button" onclick="closeAddAssetModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button type="submit" id="createAssetBtn"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-plus mr-2"></i><span id="createAssetBtnText">Create Asset</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Asset Modal -->
<div id="editAssetModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Edit Asset</h3>
        </div>
        <form id="editAssetForm" class="p-6">
            <input type="hidden" id="editAssetId" name="id">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Asset Tag *</label>
                    <input type="text" id="editAssetTag" name="asset_tag" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Asset Name *</label>
                    <div class="relative">
                        <select id="editAssetName" name="asset_name" required class="hidden">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                            <option value="__add_new__">+ Add New Category</option>
                        </select>
                        <div class="searchable-dropdown">
                            <div class="dropdown-display w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white cursor-pointer flex items-center justify-between">
                                <span class="selected-text text-gray-500">Select Category</span>
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                            <div class="dropdown-options absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 hidden max-h-60 overflow-y-auto">
                                <div class="p-2 border-b border-gray-200">
                                    <input type="text" class="dropdown-search w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Search categories...">
                                </div>
                                <div class="dropdown-option px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm text-blue-600 font-medium border-b border-gray-200" data-value="__add_new__">
                                    <i class="fa-solid fa-plus mr-2"></i>Add New Category
                                </div>
                                <div class="dropdown-list">
                                    <?php foreach ($categories as $category): ?>
                                        <div class="dropdown-option px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm" data-value="<?php echo htmlspecialchars($category['name']); ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Asset Type</label>
                    <select id="editAssetType" name="asset_type" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="Hardware">Hardware</option>
                        <option value="Software">Software</option>
                        <option value="Furniture">Furniture</option>
                        <option value="Equipment">Equipment</option>
                        <option value="Peripheral">Peripheral</option>
                        <option value="Network Device">Network Device</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                    <input type="text" id="editBrand" name="brand"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                    <input type="text" id="editModel" name="model"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Serial Number</label>
                    <input type="text" id="editSerialNumber" name="serial_number"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Room</label>
                    <select id="editRoomId" name="room_id" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">No Room</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="editStatus" name="status" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="Active">Active</option>
                        <option value="Available">Available</option>
                        <option value="In Use">In Use</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                        <option value="Retired">Retired</option>
                        <option value="Damaged">Damaged</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Condition</label>
                    <select id="editCondition" name="condition" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="Excellent">Excellent</option>
                        <option value="Good">Good</option>
                        <option value="Fair">Fair</option>
                        <option value="Poor">Poor</option>
                        <option value="Non-Functional">Non-Functional</option>
                    </select>
                </div>
                <div>
                    <label class="flex items-center cursor-pointer mt-6">
                        <input type="checkbox" id="editIsBorrowable" name="edit_is_borrowable" value="1"
                               class="mr-2 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Allow this asset to be borrowed</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-3 justify-end mt-6">
                <button type="button" onclick="closeEditAssetModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-save mr-2"></i>Update Asset
                </button>
            </div>
        </form>
    </div>
</div>

<!-- QR Code Print Modal -->
<div id="qrPrintModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-white">Print QR Codes</h3>
            <button onclick="closeQRPrintModal()" class="text-white hover:text-gray-200">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div id="qrPrintContent" class="grid grid-cols-3 gap-4">
                <!-- QR codes will be dynamically inserted here -->
            </div>
            <div class="flex gap-3 justify-end mt-6 print:hidden">
                <button onclick="closeQRPrintModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button onclick="window.print()" 
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fa-solid fa-print mr-2"></i>Print QR Codes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Archive Confirmation Modal -->
<div id="archiveModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-orange-600 to-orange-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Archive Asset</h3>
        </div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-6">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center">
                    <i class="fa-solid fa-archive text-orange-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-800 font-medium mb-2">Are you sure you want to archive this asset?</p>
                    <p class="text-sm text-gray-600 mb-1">Asset Tag: <span id="archiveAssetTag" class="font-semibold text-gray-800"></span></p>
                    <p class="text-xs text-gray-500 mt-2">Archived assets will be hidden from the default view but can be restored later.</p>
                </div>
            </div>
            <div class="flex gap-3 justify-end">
                <button onclick="closeArchiveModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button onclick="confirmArchiveAsset()" 
                        class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                    <i class="fa-solid fa-archive mr-2"></i>Archive Asset
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Import Excel Modal -->
<div id="importExcelModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Import Assets from Excel</h3>
        </div>
        <div class="p-6 space-y-4">
            <div class="text-sm text-gray-700">
                <p class="mb-2 font-medium">Expected columns:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Asset Tag</li>
                    <li>Asset Name</li>
                    <li>Type</li>
                    <li>Brand/Model</li>
                    <li>Serial Number</li>
                    <li>Room (name)</li>
                    <li>Status</li>
                    <li>Condition</li>
                </ul>
                <p class="mt-3 text-xs text-gray-600">
                    <i class="fa-solid fa-info-circle mr-1"></i>
                    Leave Room empty for standby assets. Brand/Model can be split with "/".
                </p>
            </div>
            <input type="file" id="importExcelFile" accept=".xlsx,.xls,.csv" class="w-full border border-gray-300 rounded px-3 py-2">
            <div class="flex gap-3 pt-2">
                <button onclick="closeImportExcelModal()" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                <button onclick="processImportExcel()" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md">Import</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Add New Category</h3>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Category Name *</label>
                <input type="text" id="newCategoryName" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="e.g., MONITOR, KEYBOARD">
                <p class="text-xs text-gray-500 mt-1">Enter a new category name to add to the list</p>
            </div>
            <div class="flex gap-3 justify-end">
                <button onclick="closeAddCategoryModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button onclick="addNewCategory()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-plus mr-2"></i>Add Category
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Edit Modal -->
<div id="bulkEditModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white flex items-center justify-between">
                <span><i class="fa-solid fa-edit mr-2"></i>Bulk Edit Standby Assets</span>
                <button onclick="closeBulkEditModal()" class="text-white hover:text-gray-200">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </h3>
        </div>
        <form id="bulkEditForm" class="p-6">
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    <i class="fa-solid fa-info-circle mr-2"></i>
                    <span id="bulkEditCount">0</span> standby asset(s) selected. Only fill in the fields you want to update. Empty fields will remain unchanged.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Asset Name (Category) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Asset Name (Category)
                    </label>
                    <div class="searchable-dropdown relative">
                        <div class="dropdown-display w-full px-4 py-2 border border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 transition-colors flex items-center justify-between bg-white">
                            <span class="selected-text text-gray-400">Keep current values</span>
                            <i class="fa-solid fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                        <select id="bulkEditAssetName" name="asset_name" class="hidden">
                            <option value="">Keep current values</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="dropdown-options hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            <div class="sticky top-0 bg-white border-b border-gray-200 p-2">
                                <input type="text" class="dropdown-search w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Search categories...">
                            </div>
                            <div class="dropdown-option px-4 py-2 cursor-pointer text-blue-600 font-medium" data-value="__add_new__">
                                <i class="fa-solid fa-plus mr-2"></i>Add New Category
                            </div>
                            <div class="dropdown-option px-4 py-2 cursor-pointer hover:bg-gray-50" data-value="">
                                <em class="text-gray-400">Keep current values</em>
                            </div>
                            <?php foreach ($categories as $cat): ?>
                                <div class="dropdown-option px-4 py-2 cursor-pointer hover:bg-gray-50" data-value="<?php echo htmlspecialchars($cat['name']); ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Asset Type -->
                <div>
                    <label for="bulkEditAssetType" class="block text-sm font-medium text-gray-700 mb-2">
                        Asset Type
                    </label>
                    <select id="bulkEditAssetType" name="asset_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Keep current values</option>
                        <option value="Hardware">Hardware</option>
                        <option value="Software">Software</option>
                        <option value="Furniture">Furniture</option>
                        <option value="Equipment">Equipment</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label for="bulkEditStatus" class="block text-sm font-medium text-gray-700 mb-2">
                        Status
                    </label>
                    <select id="bulkEditStatus" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Keep current values</option>
                        <option value="Available">Available</option>
                        <option value="In Use">In Use</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                        <option value="Damaged">Damaged</option>
                        <option value="For Repair">For Repair</option>
                    </select>
                </div>

                <!-- Condition -->
                <div>
                    <label for="bulkEditCondition" class="block text-sm font-medium text-gray-700 mb-2">
                        Condition
                    </label>
                    <select id="bulkEditCondition" name="condition" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Keep current values</option>
                        <option value="Excellent">Excellent</option>
                        <option value="Good">Good</option>
                        <option value="Fair">Fair</option>
                        <option value="Poor">Poor</option>
                    </select>
                </div>

                <!-- Borrowable -->
                <div>
                    <label for="bulkEditIsBorrowable" class="block text-sm font-medium text-gray-700 mb-2">
                        Borrowable Status
                    </label>
                    <select id="bulkEditIsBorrowable" name="is_borrowable" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Keep current values</option>
                        <option value="1">Borrowable</option>
                        <option value="0">Not Borrowable</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeBulkEditModal()" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" id="bulkEditBtn" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-save mr-2"></i>Update Selected Assets
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #qrPrintModal, #qrPrintModal * {
        visibility: visible;
    }
    #qrPrintModal {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: white;
    }
    #qrPrintContent {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 1rem !important;
    }
    .qr-item {
        page-break-inside: avoid;
    }
}

/* Searchable Dropdown Styles */
.searchable-dropdown {
    position: relative;
}

.dropdown-display {
    transition: all 0.2s ease;
}

.dropdown-display:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.dropdown-options {
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.dropdown-option {
    transition: background-color 0.15s ease;
}

.dropdown-option:hover {
    background-color: #eff6ff;
}

.dropdown-option.selected {
    background-color: #dbeafe;
    color: #1e40af;
    font-weight: 500;
}

.dropdown-search:focus {
    outline: none;
}

/* Special styling for Add New Category option */
.dropdown-option[data-value="__add_new__"] {
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 4px;
    padding-bottom: 8px;
}
</style>

<script>
// Initialize bulk selection
function initializeBulkSelection() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const assetCheckboxes = document.querySelectorAll('.asset-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    // Select all checkbox
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        assetCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        updateBulkActions();
    });
    
    // Individual checkboxes
    assetCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedBoxes = document.querySelectorAll('.asset-checkbox:checked');
            selectAllCheckbox.checked = checkedBoxes.length === assetCheckboxes.length;
            selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < assetCheckboxes.length;
            updateBulkActions();
        });
    });
    
    function updateBulkActions() {
        const checkedBoxes = document.querySelectorAll('.asset-checkbox:checked');
        const count = checkedBoxes.length;
        
        if (count > 0) {
            bulkActions.classList.remove('hidden');
            selectedCount.textContent = count;
        } else {
            bulkActions.classList.add('hidden');
        }
    }
}

// Clear selection
function clearSelection() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const assetCheckboxes = document.querySelectorAll('.asset-checkbox');
    
    selectAllCheckbox.checked = false;
    selectAllCheckbox.indeterminate = false;
    
    assetCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    document.getElementById('bulkActions').classList.add('hidden');
}

// Bulk Archive
async function bulkArchive() {
    const selectedAssets = document.querySelectorAll('.asset-checkbox:checked');
    if (selectedAssets.length === 0) {
        showAlert('error', 'No assets selected');
        return;
    }
    
    const assetIds = Array.from(selectedAssets).map(cb => parseInt(cb.value));
    
    if (!confirm(`Are you sure you want to archive ${assetIds.length} asset(s)?`)) {
        return;
    }
    
    try {
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'bulk_archive_assets');
        formData.append('asset_ids', JSON.stringify(assetIds));
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while archiving assets');
    }
}

// Bulk Dispose
async function bulkDispose() {
    const selectedAssets = document.querySelectorAll('.asset-checkbox:checked');
    if (selectedAssets.length === 0) {
        showAlert('error', 'No assets selected');
        return;
    }
    
    const assetIds = Array.from(selectedAssets).map(cb => parseInt(cb.value));
    
    if (!confirm(`Are you sure you want to dispose ${assetIds.length} asset(s)? This will mark them as disposed.`)) {
        return;
    }
    
    try {
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'bulk_dispose_assets');
        formData.append('asset_ids', JSON.stringify(assetIds));
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while disposing assets');
    }
}

// Debounce function for search input
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Apply filters to the table
function applyFilters() {
    const filterStatus = document.getElementById('filter_status').value.toLowerCase();
    const filterType = document.getElementById('filter_type').value.toLowerCase();
    const searchQuery = document.getElementById('searchInput').value.toLowerCase();
    const showArchived = document.getElementById('show_archived').checked;
    
    const rows = document.querySelectorAll('.asset-row');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const status = row.getAttribute('data-status').toLowerCase();
        const type = row.getAttribute('data-type').toLowerCase();
        const tag = row.getAttribute('data-tag').toLowerCase();
        const name = row.getAttribute('data-name').toLowerCase();
        const brand = row.getAttribute('data-brand').toLowerCase();
        const model = row.getAttribute('data-model').toLowerCase();
        const serial = row.getAttribute('data-serial').toLowerCase();
        
        // Status filter
        let statusMatch = !filterStatus || status === filterStatus;
        
        // Type filter
        let typeMatch = !filterType || type === filterType;
        
        // Search filter
        let searchMatch = !searchQuery || 
            tag.includes(searchQuery) || 
            name.includes(searchQuery) || 
            brand.includes(searchQuery) || 
            model.includes(searchQuery) || 
            serial.includes(searchQuery);
        
        // Archived filter - hide archived assets unless "Show Archived" is checked
        let archivedMatch = showArchived || status !== 'archived';
        
        // Show/hide row based on all filters
        if (statusMatch && typeMatch && searchMatch && archivedMatch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show/hide "no results" message
    const noResultsRow = document.getElementById('noResultsRow');
    if (noResultsRow) {
        if (visibleCount === 0 && rows.length > 0) {
            noResultsRow.style.display = '';
        } else {
            noResultsRow.style.display = 'none';
        }
    } else if (visibleCount === 0 && rows.length > 0) {
        // Create and insert no results row if it doesn't exist
        const tbody = document.getElementById('assetsTableBody');
        const newNoResultsRow = document.createElement('tr');
        newNoResultsRow.id = 'noResultsRow';
        newNoResultsRow.innerHTML = `
            <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                <i class="fa-solid fa-box text-5xl mb-3 opacity-30"></i>
                <p class="text-lg">No assets match your filters</p>
                <p class="text-sm">Try adjusting your search or filter criteria</p>
            </td>
        `;
        tbody.appendChild(newNoResultsRow);
    }
}


// Modal functions
function openAddAssetModal() {
    document.getElementById('addAssetModal').classList.remove('hidden');
    document.getElementById('assetName').focus();
}

function closeAddAssetModal() {
    document.getElementById('addAssetModal').classList.add('hidden');
    document.getElementById('addAssetForm').reset();
    
    // Reset searchable dropdown displays
    updateSearchableDropdownDisplay('assetName', '');
    updateSearchableDropdownDisplay('bulkAssetName', '');
}

function closeEditAssetModal() {
    document.getElementById('editAssetModal').classList.add('hidden');
    document.getElementById('editAssetForm').reset();
    
    // Reset searchable dropdown display
    updateSearchableDropdownDisplay('editAssetName', '');
}

function closeQRPrintModal() {
    document.getElementById('qrPrintModal').classList.add('hidden');
    window.location.reload();
}

// Update Starting Number for Bulk Assets
async function updateBulkStartNumber() {
    const assetName = document.getElementById('bulkAssetName')?.value?.trim();
    const startNumberField = document.getElementById('bulkStartNumber');
    
    if (!assetName || !startNumberField) {
        return;
    }
    
    // For standby assets, always use NOROOM
    const roomNumber = 'NOROOM';
    
    try {
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'get_next_start_number');
        formData.append('asset_name', assetName);
        formData.append('room_number', roomNumber);
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            startNumberField.value = result.next_number;
            updateAssetTagPreview();
        }
    } catch (error) {
        console.error('Error updating start number:', error);
    }
}

// Toggle Asset Bulk Mode
function toggleAssetBulkMode() {
    const bulkMode = document.querySelector('input[name="asset_bulk_mode"]:checked').value;
    const singleFields = document.getElementById('singleAssetModeFields');
    const bulkFields = document.getElementById('bulkAssetModeFields');
    const submitBtn = document.getElementById('createAssetBtnText');
    
    if (bulkMode === 'bulk') {
        singleFields.classList.add('hidden');
        bulkFields.classList.remove('hidden');
        submitBtn.textContent = 'Create Assets';
        updateBulkStartNumber();
        updateAssetTagPreview();
    } else {
        singleFields.classList.remove('hidden');
        bulkFields.classList.add('hidden');
        submitBtn.textContent = 'Create Asset';
    }
}

// Auto-generate asset tag for single asset mode
async function generateAssetTag() {
    const assetName = document.getElementById('assetName')?.value?.trim();
    const assetTagField = document.getElementById('assetTag');
    
    if (!assetName || !assetTagField) {
        if (assetTagField) assetTagField.value = '';
        return;
    }
    
    // For standby assets, always use NOROOM
    const roomNumber = 'NOROOM';
    
    try {
        // Get current date
        const today = new Date();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const year = today.getFullYear();
        const formattedDate = `${month}-${day}-${year}`;
        
        // Create asset name prefix (first few letters)
        const assetNamePrefix = assetName.substring(0, Math.min(10, assetName.length)).toUpperCase().replace(/\s+/g, '');
        
        // Get next sequential number for this asset name only
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'get_next_asset_number');
        formData.append('asset_name', assetName);
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const nextNumber = String(result.next_number).padStart(3, '0');
            const assetTag = `${formattedDate}-${assetNamePrefix}-${roomNumber}-${nextNumber}`;
            assetTagField.value = assetTag;
        } else {
            // Fallback to basic format if server request fails
            const assetTag = `${formattedDate}-${assetNamePrefix}-${roomNumber}-001`;
            assetTagField.value = assetTag;
        }
    } catch (error) {
        console.error('Error generating asset tag:', error);
        // Fallback generation
        const today = new Date();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const year = today.getFullYear();
        const formattedDate = `${month}-${day}-${year}`;
        const assetNamePrefix = assetName.substring(0, Math.min(10, assetName.length)).toUpperCase().replace(/\s+/g, '');
        assetTagField.value = `${formattedDate}-${assetNamePrefix}-${roomNumber}-001`;
    }
}

// Update Asset Tag Preview
function updateAssetTagPreview() {
    const date = document.getElementById('bulkAcquisitionDate')?.value || '<?php echo date('Y-m-d'); ?>';
    const assetName = document.getElementById('bulkAssetName')?.value || 'LAPTOP';
    const quantity = parseInt(document.getElementById('bulkQuantity')?.value || 1);
    const startNumber = parseInt(document.getElementById('bulkStartNumber')?.value || 1);
    
    // For standby assets, always use NOROOM
    const roomNumber = 'NOROOM';
    
    const dateParts = date.split('-');
    const formattedDate = `${dateParts[1]}-${dateParts[2]}-${dateParts[0]}`;
    const assetNamePrefix = assetName.substring(0, Math.min(10, assetName.length)).toUpperCase().replace(/\s+/g, '');
    
    const preview = document.getElementById('bulkAssetTagPreview');
    if (preview) {
        if (!assetName) {
            preview.value = 'Please select asset name...';
            return;
        }
        if (quantity <= 3) {
            const tags = [];
            for (let i = 0; i < quantity; i++) {
                const num = String(startNumber + i).padStart(3, '0');
                tags.push(`${formattedDate}-${assetNamePrefix}-${roomNumber}-${num}`);
            }
            preview.value = tags.join(', ');
        } else {
            const firstNum = String(startNumber).padStart(3, '0');
            const lastNum = String(startNumber + quantity - 1).padStart(3, '0');
            preview.value = `${formattedDate}-${assetNamePrefix}-${roomNumber}-${firstNum} ... ${formattedDate}-${assetNamePrefix}-${roomNumber}-${lastNum} (${quantity} assets)`;
        }
    }
}

// Add Asset Form Submit
document.getElementById('addAssetForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const bulkMode = document.querySelector('input[name="asset_bulk_mode"]:checked').value;
    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    
    if (bulkMode === 'bulk') {
        formData.append('action', 'bulk_create_assets');
        formData.append('acquisition_date', document.getElementById('bulkAcquisitionDate').value);
        formData.append('asset_name', document.getElementById('bulkAssetName').value);
        formData.append('room_number', 'NOROOM');
        formData.append('quantity', document.getElementById('bulkQuantity').value);
        formData.append('start_number', document.getElementById('bulkStartNumber').value);
        formData.append('asset_type', document.getElementById('bulkAssetType').value);
        formData.append('brand', document.getElementById('bulkBrand').value);
        formData.append('model', document.getElementById('bulkModel').value);
        formData.append('end_of_life', document.getElementById('bulkEndOfLife').value);
        formData.append('room_id', '');
        formData.append('status', document.getElementById('bulkStatus').value);
        formData.append('condition', document.getElementById('bulkCondition').value);
        formData.append('is_borrowable', document.getElementById('bulkIsBorrowable').checked ? '1' : '0');
    } else {
        formData.append('action', 'create_asset');
        formData.append('asset_tag', document.getElementById('assetTag').value);
        formData.append('asset_name', document.getElementById('assetName').value);
        formData.append('asset_type', document.getElementById('assetType').value);
        formData.append('brand', document.getElementById('brand').value);
        formData.append('model', document.getElementById('model').value);
        formData.append('serial_number', document.getElementById('serialNumber').value);
        formData.append('end_of_life', document.getElementById('endOfLife').value);
        formData.append('room_id', '');
        formData.append('status', document.getElementById('status').value);
        formData.append('condition', document.getElementById('condition').value);
        formData.append('is_borrowable', document.getElementById('isBorrowable').checked ? '1' : '0');
    }
    
    try {
        const submitBtn = document.getElementById('createAssetBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Creating...';
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            
            if (bulkMode === 'bulk' && result.asset_ids && result.asset_ids.length > 0) {
                setTimeout(() => {
                    openQRPrintModalForAssets(result.asset_ids);
                }, 1000);
            } else {
                setTimeout(() => window.location.reload(), 1500);
            }
        } else {
            showAlert('error', result.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-plus mr-2"></i><span id="createAssetBtnText">Create Asset</span>';
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while creating the asset');
        const submitBtn = document.getElementById('createAssetBtn');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-plus mr-2"></i><span id="createAssetBtnText">Create Asset</span>';
    }
});

// Edit Asset
function editAsset(asset) {
    closeAllMenus();
    document.getElementById('editAssetId').value = asset.id;
    document.getElementById('editAssetTag').value = asset.asset_tag;
    document.getElementById('editAssetName').value = asset.asset_name;
    document.getElementById('editAssetType').value = asset.asset_type;
    document.getElementById('editBrand').value = asset.brand || '';
    document.getElementById('editModel').value = asset.model || '';
    document.getElementById('editSerialNumber').value = asset.serial_number || '';
    document.getElementById('editRoomId').value = asset.room_id || '';
    document.getElementById('editStatus').value = asset.status;
    document.getElementById('editCondition').value = asset.condition;
    document.getElementById('editIsBorrowable').checked = asset.is_borrowable == '1';
    document.getElementById('editAssetModal').classList.remove('hidden');
    document.getElementById('editAssetTag').focus();
    
    // Update searchable dropdown display
    updateSearchableDropdownDisplay('editAssetName', asset.asset_name);
}

// Edit Asset Form Submit
document.getElementById('editAssetForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    formData.append('action', 'update_asset');
    formData.append('id', document.getElementById('editAssetId').value);
    formData.append('asset_tag', document.getElementById('editAssetTag').value);
    formData.append('asset_name', document.getElementById('editAssetName').value);
    formData.append('asset_type', document.getElementById('editAssetType').value);
    formData.append('brand', document.getElementById('editBrand').value);
    formData.append('model', document.getElementById('editModel').value);
    formData.append('serial_number', document.getElementById('editSerialNumber').value);
    formData.append('room_id', document.getElementById('editRoomId').value);
    formData.append('status', document.getElementById('editStatus').value);
    formData.append('condition', document.getElementById('editCondition').value);
    formData.append('is_borrowable', document.getElementById('editIsBorrowable').checked ? '1' : '0');
    
    try {
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while updating the asset');
    }
});

// Dispose Asset

// Archive Asset
let assetToArchive = { id: null, tag: null };

function archiveAsset(id, assetTag) {
    closeAllMenus();
    assetToArchive = { id, tag: assetTag };
    document.getElementById('archiveAssetTag').textContent = assetTag;
    document.getElementById('archiveModal').classList.remove('hidden');
}

function closeArchiveModal() {
    document.getElementById('archiveModal').classList.add('hidden');
    assetToArchive = { id: null, tag: null };
}

async function confirmArchiveAsset() {
    const { id, tag } = assetToArchive;
    
    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    formData.append('action', 'archive_asset');
    formData.append('id', id);
    
    try {
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            closeArchiveModal();
            
            // Update the row's status instead of reloading the page
            const row = document.querySelector(`.asset-row[data-tag="${tag}"]`);
            if (row) {
                row.setAttribute('data-status', 'Archived');
                const statusBadge = row.querySelector('[data-status-badge]');
                if (statusBadge) {
                    statusBadge.className = 'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-700';
                    statusBadge.textContent = 'Archived';
                }
                
                // Find and hide the archive button in the menu for this row
                const menuButton = row.querySelector('button[onclick*="archiveAsset"]');
                if (menuButton) {
                    menuButton.remove();
                }
                
                // Apply filters to hide the archived asset if "Show Archived" is not checked
                setTimeout(() => applyFilters(), 100);
            }
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while archiving the asset');
    }
}


// Print QR Code for single asset
function printQRCode(asset) {
    closeAllMenus();
    openQRPrintModalForAssets([asset.id]);
}

// Open QR Print Modal for multiple assets
async function openQRPrintModalForAssets(assetIds) {
    try {
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'get_asset_qrcodes');
        formData.append('asset_ids', JSON.stringify(assetIds));
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success && result.assets) {
            const qrContent = document.getElementById('qrPrintContent');
            qrContent.innerHTML = '';
            
            result.assets.forEach(asset => {
                // Generate QR code URL if not stored in database
                let qrCodeUrl = asset.qr_code;
                if (!qrCodeUrl || qrCodeUrl.trim() === '') {
                    const qrData = {
                        asset_tag: asset.asset_tag,
                        asset_name: asset.asset_name,
                        asset_type: asset.asset_type,
                        room_id: asset.room_id,
                        brand: asset.brand,
                        model: asset.model
                    };
                    qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(JSON.stringify(qrData));
                }
                
                const qrItem = document.createElement('div');
                qrItem.className = 'qr-item p-4 border border-gray-300 rounded-lg text-center';
                qrItem.innerHTML = `
                    <div class="mb-2">
                        <img src="${qrCodeUrl}" alt="QR Code" class="w-48 h-48 mx-auto" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5RVI8L3RleHQ+PC9zdmc+'">
                    </div>
                    <div class="text-sm font-semibold text-gray-900">${asset.asset_tag}</div>
                    <div class="text-xs text-gray-600">${asset.asset_name}</div>
                    ${asset.brand ? `<div class="text-xs text-gray-500">${asset.brand}${asset.model ? ' - ' + asset.model : ''}</div>` : ''}
                `;
                qrContent.appendChild(qrItem);
            });
            
            // Only close add asset modal if it's open
            if (!document.getElementById('addAssetModal').classList.contains('hidden')) {
                closeAddAssetModal();
            }
            document.getElementById('qrPrintModal').classList.remove('hidden');
        } else {
            showAlert('error', result.message || 'Failed to load QR codes');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while loading QR codes');
    }
}

// Print selected QR codes from bulk selection
async function printSelectedQRCodes() {
    const selectedCheckboxes = document.querySelectorAll('.asset-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        showAlert('error', 'Please select at least one asset to print QR codes');
        return;
    }
    
    const assetIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.value));
    await openQRPrintModalForAssets(assetIds);
}

// Edit selected asset tags
function editSelectedAssetTags() {
    const selectedCheckboxes = document.querySelectorAll('.asset-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        showAlert('error', 'Please select at least one asset to edit');
        return;
    }
    
    const assetIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.value));
    
    // Redirect to edit asset tags page with selected IDs
    const idsParam = assetIds.join(',');
    window.location.href = 'edit_asset_tag_standbyassets.php?asset_ids=' + idsParam;
}

// Close QR Print Modal
function closeQRPrintModal() {
    document.getElementById('qrPrintModal').classList.add('hidden');
}

// Toggle three-dot menu
function toggleMenu(id) {
    const menu = document.getElementById('menu-' + id);
    const allMenus = document.querySelectorAll('[id^="menu-"]');
    
    // Close all other menus
    allMenus.forEach(m => {
        if (m.id !== 'menu-' + id) {
            m.classList.add('hidden');
        }
    });
    
    // Toggle current menu
    menu.classList.toggle('hidden');
}

// Close all menus
function closeAllMenus() {
    document.querySelectorAll('[id^="menu-"]').forEach(menu => {
        menu.classList.add('hidden');
    });
}

// Alert function
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    } text-white font-medium`;
    alertDiv.textContent = message;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => alertDiv.remove(), 3000);
}

// Import Excel functions
function openImportExcelModal() { 
    document.getElementById('importExcelModal').classList.remove('hidden'); 
}

function closeImportExcelModal() { 
    document.getElementById('importExcelModal').classList.add('hidden'); 
    const f = document.getElementById('importExcelFile'); 
    if (f) f.value = ''; 
}

function ensureSheetJSLoaded() {
    return new Promise((resolve) => {
        if (window.XLSX) return resolve();
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
        script.onload = () => resolve();
        document.head.appendChild(script);
    });
}

async function parseFileToRows(file) {
    await ensureSheetJSLoaded();
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const data = new Uint8Array(e.target.result);
                const wb = XLSX.read(data, { type: 'array' });
                const ws = wb.Sheets[wb.SheetNames[0]];
                const json = XLSX.utils.sheet_to_json(ws, { defval: '' });
                const headers = Object.keys(json[0] || {});
                resolve({ headers, rows: json });
            } catch (err) { reject(err); }
        };
        reader.onerror = reject;
        reader.readAsArrayBuffer(file);
    });
}

async function processImportExcel() {
    const fileInput = document.getElementById('importExcelFile');
    const file = fileInput && fileInput.files[0];
    if (!file) { 
        showAlert('error', 'Please select a file'); 
        return; 
    }
    
    try {
        const { headers, rows } = await parseFileToRows(file);
        
        if (rows.length === 0) {
            showAlert('error', 'No data found in file');
            return;
        }
        
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'import_excel_rows');
        formData.append('headers', JSON.stringify(headers));
        formData.append('rows', JSON.stringify(rows));
        
        const res = await fetch(location.href, { method: 'POST', body: formData });
        const result = await res.json();
        
        if (result.success) {
            let message = `Successfully imported ${result.created.length} asset(s)`;
            if (result.errors && result.errors.length > 0) {
                message += `. Errors: ${result.errors.length}`;
                console.log('Import errors:', result.errors);
            }
            showAlert('success', message);
            closeImportExcelModal();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert('error', result.message || 'Import failed');
        }
    } catch (e) {
        console.error(e);
        showAlert('error', 'Failed to parse or import file');
    }
}

// Import Excel functions
function openImportExcelModal() { 
    document.getElementById('importExcelModal').classList.remove('hidden'); 
}

function closeImportExcelModal() { 
    document.getElementById('importExcelModal').classList.add('hidden'); 
    const f = document.getElementById('importExcelFile'); 
    if (f) f.value = ''; 
}

// Ensure SheetJS is loaded
function ensureSheetJSLoaded() {
    return new Promise((resolve) => {
        if (window.XLSX) return resolve();
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
        script.onload = () => resolve();
        document.head.appendChild(script);
    });
}

// Parse Excel file
async function parseFileToRows(file) {
    await ensureSheetJSLoaded();
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const data = new Uint8Array(e.target.result);
                const wb = XLSX.read(data, { type: 'array' });
                const ws = wb.Sheets[wb.SheetNames[0]];
                const json = XLSX.utils.sheet_to_json(ws, { defval: '' });
                const headers = Object.keys(json[0] || {});
                resolve({ headers, rows: json });
            } catch (err) { reject(err); }
        };
        reader.onerror = reject;
        reader.readAsArrayBuffer(file);
    });
}

// Process import
async function processImportExcel() {
    const fileInput = document.getElementById('importExcelFile');
    const file = fileInput && fileInput.files[0];
    if (!file) { 
        showAlert('error', 'Please select a file'); 
        return; 
    }
    
    try {
        const { headers, rows } = await parseFileToRows(file);
        
        if (rows.length === 0) {
            showAlert('error', 'No data found in file');
            return;
        }
        
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'import_excel_rows');
        formData.append('headers', JSON.stringify(headers));
        formData.append('rows', JSON.stringify(rows));
        
        const res = await fetch(location.href, { method: 'POST', body: formData });
        const result = await res.json();
        
        if (result.success) {
            let message = `Successfully imported ${result.created.length} asset(s)`;
            if (result.errors && result.errors.length > 0) {
                message += `. Errors: ${result.errors.length}`;
                console.log('Import errors:', result.errors);
            }
            showAlert('success', message);
            closeImportExcelModal();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert('error', result.message || 'Import failed');
        }
    } catch (e) {
        console.error(e);
        showAlert('error', 'Failed to parse or import file');
    }
}

// Close menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('[id^="menu-"]') && !e.target.closest('button')) {
        closeAllMenus();
    }
});

// Category dropdown handlers
document.addEventListener('DOMContentLoaded', function() {
    // Initialize bulk selection
    initializeBulkSelection();
    
    // Initialize searchable dropdowns
    initializeSearchableDropdowns();
    
    // Handle category dropdown changes for all asset name selects
    const categorySelects = ['assetName', 'bulkAssetName', 'editAssetName'];
    categorySelects.forEach(selectId => {
        const select = document.getElementById(selectId);
        if (select) {
            select.addEventListener('change', function() {
                if (this.value === '__add_new__') {
                    openAddCategoryModal(this.id);
                    // Reset the select to empty
                    this.value = '';
                }
            });
        }
    });
    
    // For bulk mode, update start number and preview when asset name changes
    const bulkAssetName = document.getElementById('bulkAssetName');
    if (bulkAssetName) {
        bulkAssetName.addEventListener('change', function() {
            if (this.value && this.value !== '__add_new__') {
                updateBulkStartNumber();
                updateAssetTagPreview();
            }
        });
    }
    
    // Update preview when other bulk fields change
    const bulkPreviewFields = ['bulkAcquisitionDate', 'bulkQuantity', 'bulkStartNumber'];
    bulkPreviewFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('change', updateAssetTagPreview);
            field.addEventListener('input', updateAssetTagPreview);
        }
    });
});

// Initialize searchable dropdowns
function initializeSearchableDropdowns() {
    const dropdowns = document.querySelectorAll('.searchable-dropdown');
    
    dropdowns.forEach(dropdown => {
        const display = dropdown.querySelector('.dropdown-display');
        const options = dropdown.querySelector('.dropdown-options');
        const searchInput = dropdown.querySelector('.dropdown-search');
        const selectElement = dropdown.parentElement.querySelector('select');
        const selectedText = dropdown.querySelector('.selected-text');
        
        // Toggle dropdown on display click
        display.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Close other dropdowns
            document.querySelectorAll('.dropdown-options').forEach(opt => {
                if (opt !== options) {
                    opt.classList.add('hidden');
                }
            });
            
            // Toggle current dropdown
            options.classList.toggle('hidden');
            
            if (!options.classList.contains('hidden')) {
                searchInput.focus();
                searchInput.value = '';
                filterDropdownOptions(options, '');
            }
        });
        
        // Handle search input
        searchInput.addEventListener('input', function() {
            filterDropdownOptions(options, this.value.toLowerCase());
        });
        
        // Handle option selection
        const optionElements = options.querySelectorAll('.dropdown-option');
        optionElements.forEach(option => {
            option.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                const text = this.textContent.trim();
                
                // Update hidden select
                selectElement.value = value;
                
                // Update display text
                selectedText.textContent = text;
                selectedText.className = 'selected-text text-gray-900';
                
                // Close dropdown
                options.classList.add('hidden');
                
                // Trigger change event for compatibility
                selectElement.dispatchEvent(new Event('change'));
                
                // Trigger asset tag generation if this is the asset name field in single mode
                if (selectElement.id === 'assetName') {
                    generateAssetTag();
                }
                
                // Clear search
                searchInput.value = '';
            });
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.searchable-dropdown')) {
            document.querySelectorAll('.dropdown-options').forEach(options => {
                options.classList.add('hidden');
            });
        }
    });
}

// Filter dropdown options based on search
function filterDropdownOptions(optionsContainer, searchTerm) {
    const options = optionsContainer.querySelectorAll('.dropdown-option');
    let hasVisibleOptions = false;
    
    options.forEach(option => {
        const text = option.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            option.style.display = '';
            hasVisibleOptions = true;
        } else {
            option.style.display = 'none';
        }
    });
    
    // Show/hide "no results" message
    let noResultsMsg = optionsContainer.querySelector('.no-results');
    if (!hasVisibleOptions && searchTerm) {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.className = 'no-results px-4 py-2 text-sm text-gray-500 text-center';
            noResultsMsg.textContent = 'No categories found';
            optionsContainer.querySelector('.dropdown-list').appendChild(noResultsMsg);
        }
        noResultsMsg.style.display = '';
    } else if (noResultsMsg) {
        noResultsMsg.style.display = 'none';
    }
}

// Update searchable dropdown display when categories are refreshed
function updateSearchableDropdownDisplay(selectId, selectedValue) {
    const dropdown = document.querySelector(`#${selectId}`).closest('.searchable-dropdown');
    if (dropdown) {
        const selectedText = dropdown.querySelector('.selected-text');
        if (selectedValue) {
            selectedText.textContent = selectedValue;
            selectedText.className = 'selected-text text-gray-900';
        } else {
            selectedText.textContent = 'Select Category';
            selectedText.className = 'selected-text text-gray-500';
        }
    }
}

// Category modal functions
function openAddCategoryModal(triggeredBy) {
    document.getElementById('addCategoryModal').classList.remove('hidden');
    document.getElementById('newCategoryName').focus();
    // Store which select triggered this modal
    document.getElementById('addCategoryModal').setAttribute('data-triggered-by', triggeredBy);
}

function closeAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.add('hidden');
    document.getElementById('newCategoryName').value = '';
}

async function addNewCategory() {
    const categoryName = document.getElementById('newCategoryName').value.trim().toUpperCase();
    
    if (!categoryName) {
        showAlert('error', 'Please enter a category name');
        return;
    }
    
    try {
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'add_category');
        formData.append('category_name', categoryName);
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            closeAddCategoryModal();
            
            // Refresh categories in all dropdowns
            await refreshCategoryDropdowns();
            
            // Set the newly added category as selected in the triggering dropdown
            const triggeredBy = document.getElementById('addCategoryModal').getAttribute('data-triggered-by');
            if (triggeredBy) {
                const select = document.getElementById(triggeredBy);
                if (select) {
                    select.value = categoryName;
                    
                    // Trigger asset tag generation if this is the asset name field
                    if (triggeredBy === 'assetName') {
                        generateAssetTag();
                    }
                }
            }
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while adding the category');
    }
}

async function refreshCategoryDropdowns() {
    try {
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'get_categories');
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const categorySelects = ['assetName', 'bulkAssetName', 'editAssetName'];
            categorySelects.forEach(selectId => {
                const select = document.getElementById(selectId);
                if (select) {
                    const currentValue = select.value;
                    
                    // Clear existing options except the first empty one and the "Add New" option
                    while (select.options.length > 2) {
                        select.remove(1); // Remove options between first and last
                    }
                    
                    // Add new categories
                    result.categories.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.name;
                        option.textContent = category.name;
                        select.insertBefore(option, select.options[select.options.length - 1]);
                    });
                    
                    // Restore previous selection if it still exists
                    if (currentValue && currentValue !== '__add_new__') {
                        select.value = currentValue;
                        updateSearchableDropdownDisplay(selectId, currentValue);
                    } else {
                        updateSearchableDropdownDisplay(selectId, '');
                    }
                    
                    // Update the searchable dropdown options
                    updateSearchableDropdownOptions(selectId, result.categories);
                }
            });
        }
    } catch (error) {
        console.error('Error refreshing categories:', error);
    }
}

// Update searchable dropdown options when categories are refreshed
// Update searchable dropdown options when categories are refreshed
function updateSearchableDropdownOptions(selectId, categories) {
    const dropdown = document.querySelector(`#${selectId}`).closest('.searchable-dropdown');
    if (dropdown) {
        const dropdownList = dropdown.querySelector('.dropdown-list');
        const existingOptions = dropdownList.querySelectorAll('.dropdown-option');
        
        // Remove existing category options (the "Add New" option is now outside dropdown-list)
        existingOptions.forEach(option => {
            option.remove();
        });
        
        // Add new category options to the dropdown-list
        categories.forEach(category => {
            const optionDiv = document.createElement('div');
            optionDiv.className = 'dropdown-option px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm';
            optionDiv.setAttribute('data-value', category.name);
            optionDiv.textContent = category.name;
            
            dropdownList.appendChild(optionDiv);
            
            // Add click event listener
            optionDiv.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                const text = this.textContent.trim();
                
                // Update hidden select
                const selectElement = dropdown.parentElement.querySelector('select');
                selectElement.value = value;
                
                // Update display text
                const selectedText = dropdown.querySelector('.selected-text');
                selectedText.textContent = text;
                selectedText.className = 'selected-text text-gray-900';
                
                // Close dropdown
                const options = dropdown.querySelector('.dropdown-options');
                options.classList.add('hidden');
                
                // Trigger change event for compatibility
                selectElement.dispatchEvent(new Event('change'));
            });
        });
    }
}

// Bulk Edit Functions
function openBulkEditModal() {
    const selectedCheckboxes = document.querySelectorAll('.asset-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        showAlert('error', 'Please select at least one asset to edit');
        return;
    }
    
    document.getElementById('bulkEditCount').textContent = selectedCheckboxes.length;
    document.getElementById('bulkEditModal').classList.remove('hidden');
    
    // Initialize bulk edit dropdowns
    initializeBulkEditDropdowns();
}

function closeBulkEditModal() {
    document.getElementById('bulkEditModal').classList.add('hidden');
    document.getElementById('bulkEditForm').reset();
}

// Initialize bulk edit dropdowns
function initializeBulkEditDropdowns() {
    const bulkEditAssetName = document.getElementById('bulkEditAssetName');
    if (bulkEditAssetName) {
        bulkEditAssetName.value = '';
        const dropdown = bulkEditAssetName.closest('.searchable-dropdown');
        if (dropdown) {
            const selectedText = dropdown.querySelector('.selected-text');
            if (selectedText) {
                selectedText.textContent = 'Keep current values';
                selectedText.className = 'selected-text text-gray-400';
            }
        }
    }
}

// Bulk Edit Form Submit
document.addEventListener('DOMContentLoaded', function() {
    const bulkEditForm = document.getElementById('bulkEditForm');
    if (bulkEditForm) {
        bulkEditForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const selectedCheckboxes = document.querySelectorAll('.asset-checkbox:checked');
            if (selectedCheckboxes.length === 0) {
                showAlert('error', 'No assets selected');
                return;
            }
            
            const assetIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.value));
            
            const formData = new URLSearchParams();
            formData.append('ajax', '1');
            formData.append('action', 'bulk_update');
            formData.append('asset_ids', JSON.stringify(assetIds));
            
            // Only append fields that have values (not empty)
            const assetName = document.getElementById('bulkEditAssetName').value;
            if (assetName) formData.append('asset_name', assetName);
            
            const assetType = document.getElementById('bulkEditAssetType').value;
            if (assetType) formData.append('asset_type', assetType);
            
            const status = document.getElementById('bulkEditStatus').value;
            if (status) formData.append('status', status);
            
            const condition = document.getElementById('bulkEditCondition').value;
            if (condition) formData.append('condition', condition);
            
            const isBorrowable = document.getElementById('bulkEditIsBorrowable').value;
            if (isBorrowable !== '') formData.append('is_borrowable', isBorrowable);
            
            // Check if at least one field is being updated
            if (!assetName && !assetType && !status && !condition && isBorrowable === '') {
                showAlert('error', 'Please select at least one field to update');
                return;
            }
            
            const submitBtn = document.getElementById('bulkEditBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Updating...';
            
            try {
                const response = await fetch(location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    closeBulkEditModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('error', result.message || 'Failed to update assets');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'An error occurred while updating assets');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    }
});
</script>

<?php include '../components/layout_footer.php'; ?>