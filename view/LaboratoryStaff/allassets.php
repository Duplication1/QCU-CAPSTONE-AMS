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

/**
 * Build filter conditions and parameters for asset queries
 * @param array $filters Array of filter values (status, type, building, room, search, etc.)
 * @param bool $show_archived Whether to include archived assets
 * @param bool $show_standby Whether to show only standby (unassigned) assets
 * @return array ['where' => SQL WHERE conditions, 'params' => parameter values, 'types' => parameter types]
 */
function buildAssetFilters($filters, $show_archived = false, $show_standby = false) {
    $conditions = [];
    $params = [];
    $types = '';
    
    // Exclude archived assets by default
    if (!$show_archived) {
        $conditions[] = "status NOT IN ('Archive', 'Archived')";
    }
    
    // Filter by status
    if (!empty($filters['status'])) {
        $conditions[] = "status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    // Filter by asset type
    if (!empty($filters['type'])) {
        $conditions[] = "asset_type = ?";
        $params[] = $filters['type'];
        $types .= 's';
    }
    
    // Standby filter - assets not assigned to any room
    if ($show_standby) {
        $conditions[] = "(room_id IS NULL OR room_id = 0)";
    }
    
    // Filter by building (through room relationship)
    if (!empty($filters['building'])) {
        $conditions[] = "room_id IN (SELECT id FROM rooms WHERE building_id = ?)";
        $params[] = intval($filters['building']);
        $types .= 'i';
    }
    
    // Filter by room
    if (!empty($filters['room'])) {
        $conditions[] = "room_id = ?";
        $params[] = intval($filters['room']);
        $types .= 'i';
    }
    
    // Search across multiple fields
    if (!empty($filters['search'])) {
        $conditions[] = "(asset_tag LIKE ? OR asset_name LIKE ? OR brand LIKE ? OR model LIKE ? OR serial_number LIKE ?)";
        $search_param = "%" . $filters['search'] . "%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
        $types .= 'sssss';
    }
    
    $where = !empty($conditions) ? implode(' AND ', $conditions) : '1=1';
    
    return [
        'where' => $where,
        'params' => $params,
        'types' => $types
    ];
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
        $status = trim($_POST['status'] ?? 'Available');
        $condition = trim($_POST['condition'] ?? 'Good');
        $room_id = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
        
        if (empty($asset_tag) || empty($asset_name)) {
            echo json_encode(['success' => false, 'message' => 'Asset tag and name are required']);
            exit;
        }
        
        try {
            // Insert asset first to get ID
            $stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, serial_number, room_id, status, `condition`, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $created_by = $_SESSION['user_id'];
            $stmt->bind_param('ssssssissi', $asset_tag, $asset_name, $asset_type, $brand, $model, $serial_number, $room_id, $status, $condition, $created_by);
            $success = $stmt->execute();
            $new_id = $conn->insert_id;
            $stmt->close();
            
            if ($success) {
                // Generate QR code with scan URL
                $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
                $scan_url = $base_url . '/QCU-CAPSTONE-AMS/view/public/scan_asset.php?id=' . $new_id;
                $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($scan_url);
                
                // Update asset with QR code
                $update_qr = $conn->prepare("UPDATE assets SET qr_code = ? WHERE id = ?");
                $update_qr->bind_param('si', $qr_code_url, $new_id);
                $update_qr->execute();
                $update_qr->close();
                
                // Log asset creation history
                require_once '../../controller/AssetHistoryHelper.php';
                $historyHelper = AssetHistoryHelper::getInstance();
                $historyHelper->logAssetCreated($new_id, $asset_tag, $asset_name, $created_by);
                
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
        $status = trim($_POST['status'] ?? 'Available');
        $condition = trim($_POST['condition'] ?? 'Good');
        $room_id = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
        $is_borrowable = isset($_POST['is_borrowable']) && $_POST['is_borrowable'] == '1' ? 1 : 0;
        
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
                $stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, room_id, status, `condition`, is_borrowable, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssssissii', $asset_tag, $asset_name, $asset_type, $brand, $model, $room_id, $status, $condition, $is_borrowable, $created_by);
                
                if ($stmt->execute()) {
                    $new_id = $conn->insert_id;
                    $created_count++;
                    $created_asset_ids[] = $new_id;
                    
                    // Generate QR code with scan URL
                    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
                    $scan_url = $base_url . '/QCU-CAPSTONE-AMS/view/public/scan_asset.php?id=' . $new_id;
                    $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($scan_url);
                    
                    // Update asset with QR code
                    $update_qr = $conn->prepare("UPDATE assets SET qr_code = ? WHERE id = ?");
                    $update_qr->bind_param('si', $qr_code_url, $new_id);
                    $update_qr->execute();
                    $update_qr->close();
                    
                    // Log asset creation history
                    require_once '../../controller/AssetHistoryHelper.php';
                    $historyHelper = AssetHistoryHelper::getInstance();
                    $historyHelper->logAssetCreated($new_id, $asset_tag, $asset_name, $created_by);
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
        $is_borrowable = isset($_POST['is_borrowable']) && $_POST['is_borrowable'] == '1' ? 1 : 0;
        
        if ($id <= 0 || empty($asset_tag) || empty($asset_name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        try {
            // Check for duplicate asset_tag on a different asset
            $dup = $conn->prepare("SELECT id FROM assets WHERE asset_tag = ? AND id <> ? LIMIT 1");
            $dup->bind_param('si', $asset_tag, $id);
            $dup->execute();
            $dup->store_result();
            if ($dup->num_rows > 0) {
                $dup->close();
                echo json_encode(['success' => false, 'message' => 'Asset tag already exists on another asset']);
                exit;
            }
            $dup->close();

            $stmt = $conn->prepare("UPDATE assets SET asset_tag = ?, asset_name = ?, asset_type = ?, brand = ?, model = ?, serial_number = ?, room_id = ?, status = ?, `condition` = ?, is_borrowable = ?, updated_by = ? WHERE id = ?");
            $updated_by = $_SESSION['user_id'];
            $stmt->bind_param('ssssssissiii', $asset_tag, $asset_name, $asset_type, $brand, $model, $serial_number, $room_id, $status, $condition, $is_borrowable, $updated_by, $id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Asset updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update asset: ' . $conn->error]);
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
            $stmt = $conn->prepare("UPDATE assets SET status = 'Archived', updated_by = ? WHERE id = ?");
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
    
    if ($action === 'delete_asset') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid asset ID']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("DELETE FROM assets WHERE id = ?");
            $stmt->bind_param('i', $id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Asset deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete asset']);
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

    // Import Excel/CSV parsed rows (client sends JSON after parsing)
    if ($action === 'import_excel_rows') {
        $headers = json_decode($_POST['headers_json'] ?? '[]', true);
        $rows = json_decode($_POST['rows_json'] ?? '[]', true);
        if (empty($headers) || empty($rows)) {
            echo json_encode(['success' => false, 'message' => 'No data received for import']);
            exit;
        }
        // Normalize headers map
        $headerMap = [];
        foreach ($headers as $idx => $h) {
            $key = strtolower(trim($h));
            if ($key !== '') {
                $headerMap[$key] = $idx;
            }
        }

        // Helper to get value by candidate header names
        $getVal = function($row, $candidates, $default='') use ($headerMap) {
            foreach ($candidates as $c) {
                $k = strtolower($c);
                if (isset($headerMap[$k])) {
                    $v = $row[$headerMap[$k]] ?? '';
                    if (is_string($v)) { $v = trim($v); }
                    if ($v !== '') return $v;
                }
            }
            return $default;
        };

        // Normalizers
        $normalize_status = function($val){
            $v = trim(strtolower((string)$val));
            $v = str_replace(['-', '_', ' '], '', $v);
            $map = [
                'active' => 'Active',
                'available' => 'Available',
                'inuse' => 'In Use',
                'undermaintenance' => 'Under Maintenance',
                'retired' => 'Retired',
                'disposed' => 'Disposed',
                'lost' => 'Lost',
                'damaged' => 'Damaged',
                'archived' => 'Archived'
            ];
            return $map[$v] ?? 'Available';
        };
        $normalize_condition = function($val){
            $v = trim(strtolower((string)$val));
            $v = str_replace(['-', '_'], ' ', $v);
            $v = preg_replace('/\s+/', ' ', $v);
            $map = [
                'excellent' => 'Excellent',
                'good' => 'Good',
                'fair' => 'Fair',
                'poor' => 'Poor',
                'non functional' => 'Non-Functional',
                'non-functional' => 'Non-Functional',
            ];
            return $map[$v] ?? 'Good';
        };

        $inserted = 0;
        $skipped = [];
        $created_assets = [];
        $today = date('m-d-Y');
        $user_id = $_SESSION['user_id'];

        // Prepare duplicate check statement
        $checkStmt = $conn->prepare("SELECT id FROM assets WHERE asset_tag = ?");
        $insertStmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, serial_number, room_id, status, `condition`, qr_code, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($rows as $i => $row) {
            if (!is_array($row)) continue;
            // Extract columns
            $asset_tag = $getVal($row, ['asset tag','asset_tag','tag']);
            $asset_name = $getVal($row, ['asset name','asset_name','name'], 'Imported Asset');
            $asset_type = $getVal($row, ['type','asset type','asset_type'], 'Hardware');
            $brand = $getVal($row, ['brand'], '');
            $model = $getVal($row, ['model'], '');
            $serial_number = $getVal($row, ['serial number','serial_number','serial'], '');
            $room_identifier = $getVal($row, ['room','room name','room_name'], '');
            $status = $getVal($row, ['status','asset status','current status','equipment status'], 'Available');
            $condition = $getVal($row, ['condition','asset condition','state'], 'Good');
            $status = $normalize_status($status);
            $condition = $normalize_condition($condition);

            // Generate asset_tag if missing
            if ($asset_tag === '') {
                $prefix = strtoupper(preg_replace('/\s+/', '', substr($asset_name, 0, 10))); // first 10 chars no spaces
                $asset_tag = $today . '-' . $prefix . '-IMPORT-' . str_pad($i+1, 3, '0', STR_PAD_LEFT);
            }

            // Check duplicate
            $checkStmt->bind_param('s', $asset_tag);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                $skipped[] = $asset_tag . ' (duplicate)';
                continue;
            }
            $checkStmt->free_result();

            // Resolve room_id (lookup by name if provided)
            $room_id = null;
            if ($room_identifier !== '') {
                $roomLookup = $conn->prepare("SELECT id FROM rooms WHERE name = ? LIMIT 1");
                $roomLookup->bind_param('s', $room_identifier);
                $roomLookup->execute();
                $roomResult = $roomLookup->get_result();
                if ($roomResult && $roomResult->num_rows > 0) {
                    $room_id = $roomResult->fetch_assoc()['id'];
                }
                $roomLookup->close();
            }

            // QR code generation data
            $qr_data = json_encode([
                'asset_tag' => $asset_tag,
                'asset_name' => $asset_name,
                'asset_type' => $asset_type,
                'room_id' => $room_id,
                'brand' => $brand,
                'model' => $model
            ]);
            $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr_data);

            $insertStmt->bind_param(
                'ssssssisssi',
                $asset_tag,
                $asset_name,
                $asset_type,
                $brand,
                $model,
                $serial_number,
                $room_id,
                $status,
                $condition,
                $qr_code_url,
                $user_id
            );

            if ($insertStmt->execute()) {
                $inserted++;
                $created_assets[] = [
                    'id' => $conn->insert_id,
                    'asset_tag' => $asset_tag,
                    'asset_name' => $asset_name,
                    'asset_type' => $asset_type,
                    'brand' => $brand,
                    'model' => $model,
                    'serial_number' => $serial_number,
                    'room_id' => $room_id,
                    'room_name' => $room_identifier ?: null,
                    'status' => $status,
                    'condition' => $condition
                ];
            } else {
                $skipped[] = $asset_tag . ' (insert failed)';
            }
        }

        $checkStmt->close();
        $insertStmt->close();

        $message = "Imported $inserted asset(s)";
        if (!empty($skipped)) {
            $message .= '. Skipped: ' . implode(', ', $skipped);
        }
        echo json_encode([
            'success' => $inserted > 0,
            'message' => $message,
            'inserted' => $inserted,
            'skipped_count' => count($skipped),
            'assets' => $created_assets
        ]);
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
    
    if ($action === 'get_categories') {
        $categories_data = [];
        $cat_query = "SELECT id, name FROM asset_categories ORDER BY name ASC";
        $cat_result = $conn->query($cat_query);
        if ($cat_result && $cat_result->num_rows > 0) {
            while ($row = $cat_result->fetch_assoc()) {
                $categories_data[] = $row;
            }
        }
        echo json_encode(['success' => true, 'categories' => $categories_data]);
        exit;
    }
    
    if ($action === 'bulk_archive') {
        $asset_ids = json_decode($_POST['asset_ids'] ?? '[]', true);
        
        if (empty($asset_ids) || !is_array($asset_ids)) {
            echo json_encode(['success' => false, 'message' => 'Invalid asset IDs']);
            exit;
        }
        
        try {
            $placeholders = str_repeat('?,', count($asset_ids) - 1) . '?';
            $stmt = $conn->prepare("UPDATE assets SET status = 'Archived', updated_by = ? WHERE id IN ($placeholders)");
            $updated_by = $_SESSION['user_id'];
            $params = array_merge([$updated_by], $asset_ids);
            $types = 'i' . str_repeat('i', count($asset_ids));
            $stmt->bind_param($types, ...$params);
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
    
    if ($action === 'bulk_update') {
        error_log("=== BULK UPDATE STARTED ===");
        error_log("POST data: " . print_r($_POST, true));
        
        require_once '../../model/ActivityLog.php';
        
        $asset_ids = json_decode($_POST['asset_ids'] ?? '[]', true);
        error_log("Asset IDs: " . print_r($asset_ids, true));
        
        if (empty($asset_ids) || !is_array($asset_ids)) {
            error_log("ERROR: No assets selected or invalid format");
            echo json_encode(['success' => false, 'message' => 'No assets selected']);
            exit;
        }
        
        // Get fields to update (only if provided)
        $updates = [];
        $params = [];
        $types = '';
        $changed_fields = [];
        
        if (isset($_POST['asset_name']) && $_POST['asset_name'] !== '') {
            $updates[] = 'asset_name = ?';
            $params[] = trim($_POST['asset_name']);
            $types .= 's';
            $changed_fields['asset_name'] = trim($_POST['asset_name']);
        }
        
        if (isset($_POST['asset_type']) && $_POST['asset_type'] !== '') {
            $updates[] = 'asset_type = ?';
            $params[] = trim($_POST['asset_type']);
            $types .= 's';
            $changed_fields['asset_type'] = trim($_POST['asset_type']);
        }
        
        if (isset($_POST['room_id']) && $_POST['room_id'] !== '') {
            $updates[] = 'room_id = ?';
            $new_room_id = $_POST['room_id'] === '0' ? null : intval($_POST['room_id']);
            $params[] = $new_room_id;
            $types .= 'i';
            $changed_fields['room_id'] = $new_room_id;
        }
        
        if (isset($_POST['status']) && $_POST['status'] !== '') {
            $updates[] = 'status = ?';
            $params[] = trim($_POST['status']);
            $types .= 's';
            $changed_fields['status'] = trim($_POST['status']);
        }
        
        if (isset($_POST['condition']) && $_POST['condition'] !== '') {
            $updates[] = '`condition` = ?';
            $params[] = trim($_POST['condition']);
            $types .= 's';
            $changed_fields['condition'] = trim($_POST['condition']);
        }
        
        if (isset($_POST['is_borrowable']) && $_POST['is_borrowable'] !== '') {
            $updates[] = 'is_borrowable = ?';
            $params[] = intval($_POST['is_borrowable']);
            $types .= 'i';
            $changed_fields['is_borrowable'] = intval($_POST['is_borrowable']);
        }
        
        if (empty($updates)) {
            error_log("ERROR: No fields to update");
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            exit;
        }
        
        error_log("Updates to apply: " . print_r($updates, true));
        error_log("Parameters: " . print_r($params, true));
        error_log("Changed fields: " . print_r($changed_fields, true));
        
        try {
            error_log("Starting database transaction");
            $conn->begin_transaction();
            
            // Fetch old values for each asset before updating
            $placeholders_select = implode(',', array_fill(0, count($asset_ids), '?'));
            $select_sql = "SELECT id, asset_tag, asset_name, asset_type, room_id, status, `condition`, is_borrowable FROM assets WHERE id IN ($placeholders_select)";
            error_log("Select SQL: " . $select_sql);
            
            $select_stmt = $conn->prepare($select_sql);
            if (!$select_stmt) {
                throw new Exception("Prepare failed for SELECT: " . $conn->error);
            }
            
            $select_types = str_repeat('i', count($asset_ids));
            $select_stmt->bind_param($select_types, ...$asset_ids);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            $old_values = [];
            while ($row = $result->fetch_assoc()) {
                $old_values[$row['id']] = $row;
            }
            $select_stmt->close();
            error_log("Fetched old values for " . count($old_values) . " assets");
            
            // Update assets
            $placeholders = implode(',', array_fill(0, count($asset_ids), '?'));
            $sql = "UPDATE assets SET " . implode(', ', $updates) . " WHERE id IN ($placeholders)";
            error_log("Update SQL: " . $sql);
            
            // Add asset_ids to params
            foreach ($asset_ids as $id) {
                $params[] = intval($id);
                $types .= 'i';
            }
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed for UPDATE: " . $conn->error);
            }
            
            $stmt->bind_param($types, ...$params);
            $success = $stmt->execute();
            if (!$success) {
                throw new Exception("Execute failed for UPDATE: " . $stmt->error);
            }
            
            $affected = $stmt->affected_rows;
            $stmt->close();
            error_log("Updated $affected assets");
            
            // Regenerate asset tags if asset_name or room_id changed
            if (isset($changed_fields['asset_name']) || isset($changed_fields['room_id'])) {
                error_log("Asset name or room changed, regenerating asset tags");
                
                foreach ($old_values as $asset_id => $old_data) {
                    // Get the current values (after update)
                    $current_asset_name = isset($changed_fields['asset_name']) ? $changed_fields['asset_name'] : $old_data['asset_name'];
                    $current_room_id = isset($changed_fields['room_id']) ? $changed_fields['room_id'] : $old_data['room_id'];
                    
                    // Parse the old asset tag to extract date and number
                    $old_tag_parts = explode('-', $old_data['asset_tag']);
                    $acquisition_date = (count($old_tag_parts) >= 3) ? "{$old_tag_parts[0]}-{$old_tag_parts[1]}-{$old_tag_parts[2]}" : date('m-d-Y');
                    
                    // Extract the sequential number from the old tag (last part)
                    $sequential_number = '001';
                    if (count($old_tag_parts) > 0) {
                        $last_part = end($old_tag_parts);
                        if (preg_match('/(\d{3})$/', $last_part, $matches)) {
                            $sequential_number = $matches[1];
                        }
                    }
                    
                    // Get room number
                    $room_number = 'NOROOM';
                    if ($current_room_id) {
                        $room_stmt = $conn->prepare("SELECT r.name FROM rooms r WHERE r.id = ?");
                        $room_stmt->bind_param('i', $current_room_id);
                        $room_stmt->execute();
                        $room_result = $room_stmt->get_result();
                        if ($room_row = $room_result->fetch_assoc()) {
                            $room_name = $room_row['name'];
                            $room_match = preg_match('/([A-Z0-9]+)/', $room_name, $room_matches);
                            if ($room_match) {
                                $room_number = $room_matches[1];
                            } else {
                                $room_number = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $room_name), 0, 6));
                            }
                        }
                        $room_stmt->close();
                    }
                    
                    // Create asset name prefix
                    $asset_name_prefix = substr($current_asset_name, 0, min(10, strlen($current_asset_name)));
                    $asset_name_prefix = strtoupper(str_replace(' ', '', $asset_name_prefix));
                    
                    // Generate new asset tag
                    $new_asset_tag = "{$acquisition_date}-{$asset_name_prefix}-{$room_number}-{$sequential_number}";
                    
                    // Update the asset tag
                    $tag_update_stmt = $conn->prepare("UPDATE assets SET asset_tag = ? WHERE id = ?");
                    $tag_update_stmt->bind_param('si', $new_asset_tag, $asset_id);
                    $tag_update_stmt->execute();
                    $tag_update_stmt->close();
                    
                    error_log("Updated asset tag for asset $asset_id: {$old_data['asset_tag']} -> $new_asset_tag");
                }
            }
            
            if ($success && $affected > 0) {
                $user_id = $_SESSION['user_id'];
                error_log("Logging to activity_logs for user: " . $user_id);
                
                // Log to activity_logs
                try {
                    $fields_changed = implode(', ', array_keys($changed_fields));
                    ActivityLog::record(
                        $user_id,
                        'update',
                        'asset',
                        "Bulk updated {$affected} assets: {$fields_changed}"
                    );
                } catch (Exception $e) {
                    error_log("Failed to log bulk update to activity_logs: " . $e->getMessage());
                }
                
                // Log to asset_history for each asset (within the same transaction)
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                
                // Get user's full name
                $performed_by_name = null;
                $user_query = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
                $user_query->bind_param('i', $user_id);
                $user_query->execute();
                $user_result = $user_query->get_result();
                if ($user_row = $user_result->fetch_assoc()) {
                    $performed_by_name = $user_row['full_name'];
                }
                $user_query->close();
                
                foreach ($old_values as $asset_id => $old_data) {
                    try {
                        // Get room name if room changed
                        if (isset($changed_fields['room_id'])) {
                            $old_room_id = $old_data['room_id'];
                            $new_room_id = $changed_fields['room_id'];
                            
                            if ($old_room_id != $new_room_id) {
                                // Get room names
                                $old_room_name = 'No Room';
                                $new_room_name = 'No Room';
                                
                                if ($old_room_id) {
                                    $room_stmt = $conn->prepare("SELECT r.name, b.name as building_name FROM rooms r LEFT JOIN buildings b ON r.building_id = b.id WHERE r.id = ?");
                                    $room_stmt->bind_param('i', $old_room_id);
                                    $room_stmt->execute();
                                    $room_result = $room_stmt->get_result();
                                    if ($room_row = $room_result->fetch_assoc()) {
                                        $old_room_name = $room_row['name'] . ' (' . $room_row['building_name'] . ')';
                                    }
                                    $room_stmt->close();
                                }
                                
                                if ($new_room_id) {
                                    $room_stmt = $conn->prepare("SELECT r.name, b.name as building_name FROM rooms r LEFT JOIN buildings b ON r.building_id = b.id WHERE r.id = ?");
                                    $room_stmt->bind_param('i', $new_room_id);
                                    $room_stmt->execute();
                                    $room_result = $room_stmt->get_result();
                                    if ($room_row = $room_result->fetch_assoc()) {
                                        $new_room_name = $room_row['name'] . ' (' . $room_row['building_name'] . ')';
                                    }
                                    $room_stmt->close();
                                }
                                
                                // Insert room change history
                                $history_stmt = $conn->prepare("INSERT INTO asset_history (asset_id, action_type, field_changed, old_value, new_value, description, performed_by, performed_by_name, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                $action_type = 'Location Changed';
                                $field_changed = 'Room';
                                $description = "Room changed from {$old_room_name} to {$new_room_name}";
                                $history_stmt->bind_param('isssssssss', $asset_id, $action_type, $field_changed, $old_room_name, $new_room_name, $description, $user_id, $performed_by_name, $ip_address, $user_agent);
                                $history_stmt->execute();
                                $history_stmt->close();
                            }
                        }
                        
                        // Log status change
                        if (isset($changed_fields['status']) && $old_data['status'] != $changed_fields['status']) {
                            $old_status = $old_data['status'];
                            $new_status = $changed_fields['status'];
                            $history_stmt = $conn->prepare("INSERT INTO asset_history (asset_id, action_type, field_changed, old_value, new_value, description, performed_by, performed_by_name, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $action_type = 'Status Changed';
                            $field_changed = 'Status';
                            $description = "Status changed from {$old_status} to {$new_status}";
                            $history_stmt->bind_param('isssssssss', $asset_id, $action_type, $field_changed, $old_status, $new_status, $description, $user_id, $performed_by_name, $ip_address, $user_agent);
                            $history_stmt->execute();
                            $history_stmt->close();
                        }
                        
                        // Log condition change
                        if (isset($changed_fields['condition']) && $old_data['condition'] != $changed_fields['condition']) {
                            $old_condition = $old_data['condition'];
                            $new_condition = $changed_fields['condition'];
                            $history_stmt = $conn->prepare("INSERT INTO asset_history (asset_id, action_type, field_changed, old_value, new_value, description, performed_by, performed_by_name, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $action_type = 'Condition Changed';
                            $field_changed = 'Condition';
                            $description = "Condition changed from {$old_condition} to {$new_condition}";
                            $history_stmt->bind_param('isssssssss', $asset_id, $action_type, $field_changed, $old_condition, $new_condition, $description, $user_id, $performed_by_name, $ip_address, $user_agent);
                            $history_stmt->execute();
                            $history_stmt->close();
                        }
                        
                        // Log field updates (asset_name, asset_type, is_borrowable)
                        foreach (['asset_name', 'asset_type', 'is_borrowable'] as $field) {
                            if (isset($changed_fields[$field]) && $old_data[$field] != $changed_fields[$field]) {
                                $field_label = str_replace('_', ' ', ucwords(str_replace('_', ' ', $field)));
                                $old_display = $field === 'is_borrowable' ? ($old_data[$field] ? 'Yes' : 'No') : $old_data[$field];
                                $new_display = $field === 'is_borrowable' ? ($changed_fields[$field] ? 'Yes' : 'No') : $changed_fields[$field];
                                $history_stmt = $conn->prepare("INSERT INTO asset_history (asset_id, action_type, field_changed, old_value, new_value, description, performed_by, performed_by_name, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                $action_type = 'Updated';
                                $description = "{$field_label} changed from {$old_display} to {$new_display}";
                                $history_stmt->bind_param('isssssssss', $asset_id, $action_type, $field_label, $old_display, $new_display, $description, $user_id, $performed_by_name, $ip_address, $user_agent);
                                $history_stmt->execute();
                                $history_stmt->close();
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Failed to log asset history for asset {$asset_id}: " . $e->getMessage());
                    }
                }
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => "Successfully updated {$affected} asset(s)"]);
            } else {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'No assets were updated']);
            }
        } catch (Exception $e) {
            if ($conn) {
                $conn->rollback();
            }
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Fetch assets with search, filter, and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
$filter_type = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : '';
$filter_building = isset($_GET['filter_building']) ? trim($_GET['filter_building']) : '';
$filter_room = isset($_GET['filter_room']) ? trim($_GET['filter_room']) : '';
$show_standby = isset($_GET['show_standby']) && $_GET['show_standby'] === '1';
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] === '1';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 9;
// Handle "all" entries
if ($per_page <= 0) $per_page = 999999; // Show all
$limit = $per_page;
$offset = ($page - 1) * $limit;

// Get all buildings for filter
$buildings = [];
$buildings_query = $conn->query("SELECT id, name FROM buildings ORDER BY name");
if ($buildings_query) {
    while ($building_row = $buildings_query->fetch_assoc()) {
        $buildings[] = $building_row;
    }
}

// Get rooms for filter (filtered by building if selected)
$rooms = [];
if (!empty($filter_building)) {
    $rooms_query = $conn->prepare("SELECT id, name, building_id FROM rooms WHERE building_id = ? ORDER BY name");
    $rooms_query->bind_param('i', $filter_building);
    $rooms_query->execute();
    $rooms_result = $rooms_query->get_result();
    while ($room_row = $rooms_result->fetch_assoc()) {
        $rooms[] = $room_row;
    }
    $rooms_query->close();
} else {
    $rooms_query = $conn->query("SELECT id, name, building_id FROM rooms ORDER BY name");
    if ($rooms_query) {
        while ($room_row = $rooms_query->fetch_assoc()) {
            $rooms[] = $room_row;
        }
    }
}

// Get all rooms with building info for JavaScript
$all_rooms_for_js = [];
$all_rooms_query = $conn->query("SELECT r.id, r.name, r.building_id, b.name as building_name FROM rooms r LEFT JOIN buildings b ON r.building_id = b.id ORDER BY r.name");
if ($all_rooms_query) {
    while ($room_row = $all_rooms_query->fetch_assoc()) {
        $all_rooms_for_js[] = $room_row;
    }
}

// Prepare filter array for the buildAssetFilters function
$filters = [
    'status' => $filter_status,
    'type' => $filter_type,
    'building' => $filter_building,
    'room' => $filter_room,
    'search' => $search
];

// Build filter conditions using the function
$filter_result = buildAssetFilters($filters, $show_archived, $show_standby);
$where_conditions = $filter_result['where'];
$filter_params = $filter_result['params'];
$filter_types = $filter_result['types'];

// Count total assets
$count_query = "SELECT COUNT(*) as total FROM assets WHERE " . $where_conditions;

if (!empty($filter_params)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($filter_types, ...$filter_params);
    $count_stmt->execute();
    $total_result = $count_stmt->get_result();
    $total_assets = $total_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_result = $conn->query($count_query);
    $total_assets = $total_result->fetch_assoc()['total'];
}

$total_pages = ceil($total_assets / $limit);

// Fetch assets using the same filter function
$assets = [];
$query_sql = "SELECT a.*, r.name as room_name FROM assets a LEFT JOIN rooms r ON a.room_id = r.id WHERE " . $where_conditions;
$query_sql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";

// Add pagination parameters to existing filter parameters
$query_params = array_merge($filter_params, [$limit, $offset]);
$query_types = $filter_types . 'ii';

if (!empty($query_types)) {
    $query = $conn->prepare($query_sql);
    $query->bind_param($query_types, ...$query_params);
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
                <h3 class="text-lg font-semibold text-gray-800">All Assets</h3>
                <p class="text-xs text-gray-500 mt-0.5">Total: <?php echo $total_assets; ?> asset(s)</p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="openImportExcelModal()" 
                        class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fa-solid fa-file-excel"></i>
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
            <form method="GET" action="" id="filterForm" class="flex flex-wrap gap-3">
                <div class="flex-1 min-w-[250px]">
                    <input type="text" name="search" id="searchInput" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by tag, name, brand, model, serial..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <select name="filter_status" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="Active" <?php echo $filter_status === 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Available" <?php echo $filter_status === 'Available' ? 'selected' : ''; ?>>Available</option>
                    <option value="In Use" <?php echo $filter_status === 'In Use' ? 'selected' : ''; ?>>In Use</option>
                    <option value="Under Maintenance" <?php echo $filter_status === 'Under Maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                    <option value="Damaged" <?php echo $filter_status === 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
                    <option value="Retired" <?php echo $filter_status === 'Retired' ? 'selected' : ''; ?>>Retired</option>
                </select>
                <select name="filter_type" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Types</option>
                    <option value="Hardware" <?php echo $filter_type === 'Hardware' ? 'selected' : ''; ?>>Hardware</option>
                    <option value="Software" <?php echo $filter_type === 'Software' ? 'selected' : ''; ?>>Software</option>
                    <option value="Furniture" <?php echo $filter_type === 'Furniture' ? 'selected' : ''; ?>>Furniture</option>
                    <option value="Equipment" <?php echo $filter_type === 'Equipment' ? 'selected' : ''; ?>>Equipment</option>
                    <option value="Peripheral" <?php echo $filter_type === 'Peripheral' ? 'selected' : ''; ?>>Peripheral</option>
                    <option value="Network Device" <?php echo $filter_type === 'Network Device' ? 'selected' : ''; ?>>Network Device</option>
                </select>
                <select name="filter_building" id="filterBuilding" onchange="updateRoomFilter(); this.form.submit();" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Buildings</option>
                    <?php foreach ($buildings as $building): ?>
                        <option value="<?php echo $building['id']; ?>" <?php echo $filter_building == $building['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($building['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="filter_room" id="filterRoom" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Rooms</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?php echo $room['id']; ?>" <?php echo $filter_room == $room['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($room['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" name="show_standby" value="1" <?php echo $show_standby ? 'checked' : ''; ?> onchange="this.form.submit()" class="rounded">
                    <span class="text-sm text-gray-700">Standby Assets</span>
                </label>
                <label class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" name="show_archived" value="1" <?php echo $show_archived ? 'checked' : ''; ?> onchange="this.form.submit()" class="rounded">
                    <span class="text-sm text-gray-700">Show Archived</span>
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
                </button>
                <?php if (!empty($search) || !empty($filter_status) || !empty($filter_type) || !empty($filter_building) || !empty($filter_room) || $show_standby || $show_archived): ?>
                    <a href="allassets.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        <i class="fa-solid fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Bulk Actions Bar -->
        <div id="bulkActionsBar" class="hidden bg-white rounded shadow-sm border border-gray-200 mb-3 px-4 py-3">
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
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#1E3A8A] text-white sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-2 text-center text-xs font-medium uppercase tracking-wider">
                            <input type="checkbox" id="selectAll" class="rounded cursor-pointer" title="Select all">
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
                        <th class="px-3 py-2 text-center text-xs font-medium uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="assetsTableBody" class="bg-white divide-y divide-gray-200">
                    <?php if (empty($assets)): ?>
                        <tr>
                            <td colspan="11" class="px-6 py-12 text-center text-gray-500">
                                <i class="fa-solid fa-box text-5xl mb-3 opacity-30"></i>
                                <p class="text-lg">No assets found</p>
                                <?php if (!empty($search) || !empty($filter_status) || !empty($filter_type) || !empty($filter_room)): ?>
                                    <p class="text-sm">Try adjusting your filters</p>
                                <?php else: ?>
                                    <p class="text-sm">Click "Add Asset" to create one</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assets as $index => $asset): ?>
                            <tr class="hover:bg-blue-50 transition-colors">
                                <td class="px-3 py-2 text-center">
                                    <input type="checkbox" class="asset-checkbox rounded cursor-pointer" value="<?php echo $asset['id']; ?>" onchange="updateSelectAllState(); updateBulkActions();">
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                                    <?php echo $offset + $index + 1; ?>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs font-medium text-blue-600">
                                    <?php echo htmlspecialchars($asset['asset_tag']); ?>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-900">
                                    <div class="max-w-xs truncate"><?php echo htmlspecialchars($asset['asset_name']); ?></div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs">
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
                                        'Archived' => 'bg-purple-100 text-purple-700'
                                    ];
                                    $status_value = isset($asset['status']) && trim($asset['status']) !== '' ? $asset['status'] : 'Available';
                                    $status_class = $status_colors[$status_value] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-medium rounded <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($status_value); ?>
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
    <div id="importExcelModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
            <div class="flex items-center justify-between mb-4 pb-3 border-b">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fa-solid fa-file-excel text-green-600 mr-2"></i>
                    Import Excel / CSV
                </h3>
                <button onclick="closeImportExcelModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select file</label>
                    <input id="excelFileInput" type="file" accept=".xlsx,.xls,.csv" 
                           class="block w-full text-sm text-gray-700 border border-gray-300 rounded-md p-2" />
                    <p class="text-xs text-gray-500 mt-1">Accepted formats: .xlsx, .xls, .csv</p>
                </div>
                <div id="importPreviewContainer" class="hidden">
                    <h4 class="text-sm font-semibold text-gray-800 mb-2">Preview</h4>
                    <div class="overflow-auto max-h-[50vh] border rounded">
                        <table class="min-w-full">
                            <thead class="bg-gray-50" id="importPreviewHead"></thead>
                            <tbody id="importPreviewBody" class="divide-y divide-gray-200 bg-white"></tbody>
                        </table>
                    </div>
                    <div class="flex justify-end gap-2 mt-3">
                        <button onclick="importPreviewToDatabase()" 
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm" id="importAndDisplayBtn">
                            Import & Display
                        </button>
                        <button onclick="closeImportExcelModal()" 
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    
    </div>

<!-- Delete Confirmation Modal -->
<div id="deleteAssetModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                <i class="fa-solid fa-trash text-red-600 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Asset?</h3>
            <p class="text-sm text-gray-600 text-center mb-4">
                Are you sure you want to delete <strong id="deleteAssetName"></strong>?
            </p>
            <p class="text-xs text-gray-500 text-center mb-6">
                This action cannot be undone.
            </p>
            <div class="flex gap-3">
                <button onclick="closeDeleteAssetModal()" 
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button id="confirmDeleteAssetBtn" onclick="confirmDeleteAsset()" 
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors">
                    <i class="fa-solid fa-trash mr-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Archive Confirmation Modal -->
<div id="archiveAssetModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-orange-100 rounded-full mb-4">
                <i class="fa-solid fa-box-archive text-orange-600 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Archive Asset?</h3>
            <p class="text-sm text-gray-600 text-center mb-4">
                Are you sure you want to archive <strong id="archiveAssetName"></strong>?
            </p>
            <p class="text-xs text-gray-500 text-center mb-6">
                The asset will be moved to archived assets.
            </p>
            <div class="flex gap-3">
                <button onclick="closeArchiveAssetModal()" 
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button id="confirmArchiveAssetBtn" onclick="confirmArchiveAsset()" 
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-md transition-colors">
                    <i class="fa-solid fa-box-archive mr-1"></i>Archive
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Archive Confirmation Modal -->
<div id="bulkArchiveModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-orange-100 rounded-full mb-4">
                <i class="fa-solid fa-box-archive text-orange-600 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Archive Assets?</h3>
            <p class="text-sm text-gray-600 text-center mb-4">
                Are you sure you want to archive <strong id="bulkArchiveCount">0</strong> asset(s)?
            </p>
            <p class="text-xs text-gray-500 text-center mb-6">
                The assets will be moved to archived assets.
            </p>
            <div class="flex gap-3">
                <button onclick="closeBulkArchiveModal()" 
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button id="confirmBulkArchiveBtn" onclick="confirmBulkArchive()" 
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-md transition-colors">
                    <i class="fa-solid fa-box-archive mr-1"></i>Archive
                </button>
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
                        <span class="text-sm">Identical (Bulk Assets)</span>
                    </label>
                </div>
            </div>

            <!-- Single Mode Fields -->
            <div id="singleAssetModeFields" class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Asset Tag Preview</label>
                    <input type="text" id="assetTag" name="asset_tag" readonly
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed focus:outline-none"
                           placeholder="Auto-generated based on acquisition date, asset name and room">
                    <p class="text-xs text-gray-500 mt-1">This will be automatically generated when you create the asset</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Acquisition Date *</label>
                    <input type="date" id="acquisitionDate" name="acquisition_date" 
                           value="<?php echo date('Y-m-d'); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Room</label>
                    <select id="roomId" name="room_id" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">No Room</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End of Life</label>
                    <input type="date" id="endOfLife" name="end_of_life"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Expected end of life date">
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Is Borrowable</label>
                    <div class="flex items-center">
                        <input type="checkbox" id="isBorrowable" name="is_borrowable" value="1"
                               class="mr-2 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-600">Allow this asset to be borrowed</span>
                    </div>
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
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Asset Tag Preview</label>
                    <input type="text" id="bulkAssetTagPreview" readonly
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed focus:outline-none"
                           placeholder="Asset tags will be previewed here">
                    <p class="text-xs text-gray-500 mt-1">Preview of asset tags that will be generated</p>
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Starting Number <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="bulkStartNumber" name="bulk_start_number" 
                               min="1" value="1" readonly
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Automatically calculated based on existing assets</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Room</label>
                        <select id="bulkRoomId" name="bulk_room_id" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">No Room</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End of Life</label>
                        <input type="date" id="bulkEndOfLife" name="bulk_end_of_life"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Expected end of life date">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="bulkStatus" name="bulk_status" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Active">Active</option>
                            <option value="Available" selected>Available</option>
                            <option value="In Use">In Use</option>
                        </select>
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

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Is Borrowable</label>
                    <div class="flex items-center">
                        <input type="checkbox" id="bulkIsBorrowable" name="bulk_is_borrowable" value="1"
                               class="mr-2 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-600">Allow these assets to be borrowed</span>
                    </div>
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
                        <select id="editAssetName" name="asset_name" class="hidden">
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Is Borrowable</label>
                    <div class="flex items-center">
                        <input type="checkbox" id="editIsBorrowable" name="is_borrowable" value="1"
                               class="mr-2 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-600">Allow this asset to be borrowed</span>
                    </div>
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
                <span><i class="fa-solid fa-edit mr-2"></i>Bulk Edit Assets</span>
                <button onclick="closeBulkEditModal()" class="text-white hover:text-gray-200">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </h3>
        </div>
        <form id="bulkEditForm" class="p-6">
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    <i class="fa-solid fa-info-circle mr-2"></i>
                    <span id="bulkEditCount">0</span> asset(s) selected. Only fill in the fields you want to update. Empty fields will remain unchanged.
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

                <!-- Room -->
                <div>
                    <label for="bulkEditRoomId" class="block text-sm font-medium text-gray-700 mb-2">
                        Room
                    </label>
                    <select id="bulkEditRoomId" name="room_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Keep current values</option>
                        <option value="0">No Room</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['name']); ?></option>
                        <?php endforeach; ?>
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
// All rooms data for filtering
const allRoomsData = <?php echo json_encode($all_rooms_for_js); ?>;
const currentFilterRoom = '<?php echo $filter_room; ?>';

// Auto-submit search with debounce
let searchTimeout;
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            this.form.submit();
        }, 800); // Wait 800ms after user stops typing
    });
}

// Update room filter based on selected building
function updateRoomFilter() {
    const buildingSelect = document.getElementById('filterBuilding');
    const roomSelect = document.getElementById('filterRoom');
    const selectedBuilding = buildingSelect.value;
    
    // Clear current options
    roomSelect.innerHTML = '<option value="">All Rooms</option>';
    
    // Filter rooms by selected building
    const filteredRooms = selectedBuilding 
        ? allRoomsData.filter(room => room.building_id == selectedBuilding)
        : allRoomsData;
    
    // Add room options
    filteredRooms.forEach(room => {
        const option = document.createElement('option');
        option.value = room.id;
        option.textContent = room.name;
        if (room.id == currentFilterRoom) {
            option.selected = true;
        }
        roomSelect.appendChild(option);
    });
}

// Initialize bulk selection on page load
document.addEventListener('DOMContentLoaded', function() {
    updateRoomFilter();
    
    // Update preview for bulk mode
    const bulkPreviewFields = ['bulkAcquisitionDate', 'bulkAssetName', 'bulkRoomId', 'bulkQuantity', 'bulkStartNumber'];
    bulkPreviewFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', updateAssetTagPreview);
            field.addEventListener('change', updateAssetTagPreview);
        }
    });
    
    // Add listeners for start number update
    const startNumberTriggerFields = ['bulkAssetName', 'bulkRoomId'];
    startNumberTriggerFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('change', updateBulkStartNumber);
        }
    });
    
    // Add listeners for single asset mode
    const singleAssetFields = ['acquisitionDate', 'assetName', 'roomId'];
    singleAssetFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', generateAssetTag);
            field.addEventListener('change', generateAssetTag);
        }
    });
    
    // Initialize bulk selection
    initializeBulkSelection();
});

// Bulk Selection Functions
function initializeBulkSelection() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const assetCheckboxes = document.querySelectorAll('.asset-checkbox');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            assetCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActions();
        });
    }
    
    assetCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllState();
            updateBulkActions();
        });
    });
}

function updateSelectAllState() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const assetCheckboxes = document.querySelectorAll('.asset-checkbox');
    const checkedBoxes = document.querySelectorAll('.asset-checkbox:checked');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = assetCheckboxes.length > 0 && checkedBoxes.length === assetCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < assetCheckboxes.length;
    }
}

function updateBulkActions() {
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    const checkedBoxes = document.querySelectorAll('.asset-checkbox:checked');
    
    if (bulkActionsBar && selectedCount) {
        if (checkedBoxes.length > 0) {
            bulkActionsBar.classList.remove('hidden');
            selectedCount.textContent = checkedBoxes.length;
        } else {
            bulkActionsBar.classList.add('hidden');
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
    
    document.getElementById('bulkActionsBar').classList.add('hidden');
    document.getElementById('selectedCount').textContent = '0';
}

async function bulkArchive() {
    const selectedAssets = document.querySelectorAll('.asset-checkbox:checked');
    if (selectedAssets.length === 0) {
        showAlert('error', 'Please select assets to archive');
        return;
    }
    
    const assetIds = Array.from(selectedAssets).map(cb => parseInt(cb.value));
    
    // Open modal instead of confirm dialog
    document.getElementById('bulkArchiveCount').textContent = assetIds.length;
    document.getElementById('bulkArchiveModal').classList.remove('hidden');
    
    // Store asset IDs for later use
    window.bulkArchiveAssetIds = assetIds;
}

// Edit selected asset tags
function editSelectedAssetTags() {
    const selectedCheckboxes = document.querySelectorAll('.asset-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        showAlert('error', 'Please select at least one asset to edit asset tags');
        return;
    }
    
    const assetIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.value));
    
    // Redirect to edit asset tags page with selected IDs
    const idsParam = assetIds.join(',');
    window.location.href = 'edit_asset_tag_all_assets.php?asset_ids=' + idsParam;
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
    const roomId = document.getElementById('bulkRoomId')?.value;
    const startNumberField = document.getElementById('bulkStartNumber');
    
    if (!assetName || !roomId || !startNumberField) {
        return;
    }
    
    // Get room name from select
    const roomSelect = document.getElementById('bulkRoomId');
    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const roomName = selectedOption?.text || '';
    
    // Extract room number
    let roomNumber = 'NOROOM';
    if (roomId && roomName && roomName !== 'No Room') {
        const roomMatch = roomName.match(/([A-Z0-9]+)/);
        if (roomMatch) {
            roomNumber = roomMatch[1];
        } else {
            roomNumber = roomName.replace(/\s+/g, '').toUpperCase();
        }
    }
    
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
    const roomId = document.getElementById('roomId')?.value;
    const acquisitionDate = document.getElementById('acquisitionDate')?.value;
    const assetTagField = document.getElementById('assetTag');
    
    if (!assetName || !assetTagField) {
        if (assetTagField) assetTagField.value = '';
        return;
    }
    
    // Get room name from select option
    const roomSelect = document.getElementById('roomId');
    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const roomName = selectedOption?.text || 'No Room';
    
    // Extract room number or use 'NOROOM' if no room selected
    let roomNumber = 'NOROOM';
    if (roomId && roomName && roomName !== 'No Room') {
        const roomMatch = roomName.match(/([A-Z0-9]+)/);
        if (roomMatch) {
            roomNumber = roomMatch[1];
        } else {
            roomNumber = roomName.replace(/\s+/g, '').toUpperCase();
        }
    }
    
    try {
        // Get date from acquisition date field or use current date
        let formattedDate;
        if (acquisitionDate) {
            const dateParts = acquisitionDate.split('-');
            formattedDate = `${dateParts[1]}-${dateParts[2]}-${dateParts[0]}`;
        } else {
            const today = new Date();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            const year = today.getFullYear();
            formattedDate = `${month}-${day}-${year}`;
        }
        
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
        const roomNumber = roomId ? 'ROOM' : 'NOROOM';
        assetTagField.value = `${formattedDate}-${assetNamePrefix}-${roomNumber}-001`;
    }
}

// Update Asset Tag Preview for bulk mode
function updateAssetTagPreview() {
    const date = document.getElementById('bulkAcquisitionDate')?.value || '<?php echo date('Y-m-d'); ?>';
    const assetName = document.getElementById('bulkAssetName')?.value || '';
    const roomId = document.getElementById('bulkRoomId')?.value;
    const quantity = parseInt(document.getElementById('bulkQuantity')?.value || 1);
    const startNumber = parseInt(document.getElementById('bulkStartNumber')?.value || 1);
    
    const previewField = document.getElementById('bulkAssetTagPreview');
    if (!previewField) return;
    
    if (!assetName || !roomId) {
        previewField.value = 'Please select asset name and room';
        return;
    }
    
    // Get room name from select
    const roomSelect = document.getElementById('bulkRoomId');
    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const roomName = selectedOption?.text || '';
    
    // Extract room number
    let roomNumber = 'NOROOM';
    if (roomId && roomName && roomName !== 'No Room') {
        const roomMatch = roomName.match(/([A-Z0-9]+)/);
        if (roomMatch) {
            roomNumber = roomMatch[1];
        } else {
            roomNumber = roomName.replace(/\s+/g, '').toUpperCase();
        }
    }
    
    const dateParts = date.split('-');
    const formattedDate = `${dateParts[1]}-${dateParts[2]}-${dateParts[0]}`;
    const assetNamePrefix = assetName.substring(0, Math.min(10, assetName.length)).toUpperCase().replace(/\s+/g, '');
    
    if (quantity <= 3) {
        const tags = [];
        for (let i = 0; i < quantity; i++) {
            const num = String(startNumber + i).padStart(3, '0');
            tags.push(`${formattedDate}-${assetNamePrefix}-${roomNumber}-${num}`);
        }
        previewField.value = tags.join(', ');
    } else {
        const firstNum = String(startNumber).padStart(3, '0');
        const lastNum = String(startNumber + quantity - 1).padStart(3, '0');
        previewField.value = `${formattedDate}-${assetNamePrefix}-${roomNumber}-${firstNum} ... ${formattedDate}-${assetNamePrefix}-${roomNumber}-${lastNum}`;
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
        
        // Get room number from selected room
        const roomId = document.getElementById('bulkRoomId').value;
        const roomSelect = document.getElementById('bulkRoomId');
        const selectedOption = roomSelect.options[roomSelect.selectedIndex];
        const roomName = selectedOption?.text || '';
        let roomNumber = 'NOROOM';
        if (roomId && roomName && roomName !== 'No Room') {
            const roomMatch = roomName.match(/([A-Z0-9]+)/);
            if (roomMatch) {
                roomNumber = roomMatch[1];
            } else {
                roomNumber = roomName.replace(/\s+/g, '').toUpperCase();
            }
        }
        
        formData.append('room_number', roomNumber);
        formData.append('quantity', document.getElementById('bulkQuantity').value);
        formData.append('start_number', document.getElementById('bulkStartNumber').value);
        formData.append('asset_type', document.getElementById('bulkAssetType').value);
        formData.append('brand', document.getElementById('bulkBrand').value);
        formData.append('model', document.getElementById('bulkModel').value);
        formData.append('room_id', roomId);
        formData.append('status', document.getElementById('bulkStatus').value);
        formData.append('condition', document.getElementById('bulkCondition').value);
        formData.append('is_borrowable', document.getElementById('bulkIsBorrowable').checked ? '1' : '0');
        formData.append('end_of_life', document.getElementById('bulkEndOfLife').value);
    } else {
        formData.append('action', 'create_asset');
        formData.append('asset_tag', document.getElementById('assetTag').value);
        formData.append('asset_name', document.getElementById('assetName').value);
        formData.append('asset_type', document.getElementById('assetType').value);
        formData.append('brand', document.getElementById('brand').value);
        formData.append('model', document.getElementById('model').value);
        formData.append('serial_number', document.getElementById('serialNumber').value);
        formData.append('room_id', document.getElementById('roomId').value);
        formData.append('status', document.getElementById('status').value);
        formData.append('condition', document.getElementById('condition').value);
        formData.append('is_borrowable', document.getElementById('isBorrowable').checked ? '1' : '0');
        formData.append('acquisition_date', document.getElementById('acquisitionDate').value);
        formData.append('end_of_life', document.getElementById('endOfLife').value);
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
    // Client-side validation to avoid hidden required control errors
    const nameVal = document.getElementById('editAssetName').value.trim();
    if (!nameVal) {
        showAlert('error', 'Please select an Asset Name (Category).');
        // Try to focus the visible dropdown display
        const dropdownDisplay = document.querySelector('#editAssetForm .searchable-dropdown .dropdown-display');
        dropdownDisplay && dropdownDisplay.focus();
        return;
    }
    
    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    formData.append('action', 'update_asset');
    formData.append('id', document.getElementById('editAssetId').value);
    formData.append('asset_tag', document.getElementById('editAssetTag').value);
    formData.append('asset_name', nameVal);
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

// Delete Asset
// Delete Asset Modal Functions
let currentDeleteAssetId = null;

function openDeleteAssetModal(id, assetTag) {
    closeAllMenus();
    currentDeleteAssetId = id;
    const modal = document.getElementById('deleteAssetModal');
    const assetName = document.getElementById('deleteAssetName');
    
    assetName.textContent = assetTag;
    modal.classList.remove('hidden');
}

function closeDeleteAssetModal() {
    const modal = document.getElementById('deleteAssetModal');
    modal.classList.add('hidden');
    currentDeleteAssetId = null;
}

async function confirmDeleteAsset() {
    if (!currentDeleteAssetId) return;
    
    const button = document.getElementById('confirmDeleteAssetBtn');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Deleting...';
    button.disabled = true;
    
    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    formData.append('action', 'delete_asset');
    formData.append('id', currentDeleteAssetId);
    
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
            button.innerHTML = originalText;
            button.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while deleting the asset');
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

// Close delete modal when clicking outside
document.getElementById('deleteAssetModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteAssetModal();
    }
});

// Archive Asset Modal Functions
let currentArchiveAssetId = null;

function openArchiveAssetModal(id, assetTag) {
    closeAllMenus();
    currentArchiveAssetId = id;
    const modal = document.getElementById('archiveAssetModal');
    const assetName = document.getElementById('archiveAssetName');
    
    assetName.textContent = assetTag;
    modal.classList.remove('hidden');
}

function closeArchiveAssetModal() {
    const modal = document.getElementById('archiveAssetModal');
    modal.classList.add('hidden');
    currentArchiveAssetId = null;
}

async function confirmArchiveAsset() {
    if (!currentArchiveAssetId) return;
    
    const button = document.getElementById('confirmArchiveAssetBtn');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Archiving...';
    button.disabled = true;
    
    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    formData.append('action', 'archive_asset');
    formData.append('id', currentArchiveAssetId);
    
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
            button.innerHTML = originalText;
            button.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while archiving the asset');
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

// Close archive modal when clicking outside
document.getElementById('archiveAssetModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeArchiveAssetModal();
    }
});

// Bulk Archive Modal Functions
function closeBulkArchiveModal() {
    document.getElementById('bulkArchiveModal').classList.add('hidden');
    window.bulkArchiveAssetIds = null;
}

async function confirmBulkArchive() {
    if (!window.bulkArchiveAssetIds) return;
    
    const button = document.getElementById('confirmBulkArchiveBtn');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Archiving...';
    button.disabled = true;
    
    try {
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'bulk_archive');
        formData.append('asset_ids', JSON.stringify(window.bulkArchiveAssetIds));
        
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
            button.innerHTML = originalText;
            button.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while archiving assets');
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

// Close bulk archive modal when clicking outside
document.getElementById('bulkArchiveModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeBulkArchiveModal();
    }
});



// Delete Asset (now opens modal)
async function deleteAsset(id, assetTag) {
    openDeleteAssetModal(id, assetTag);
}

// Archive Asset (now opens modal)
async function archiveAsset(id, assetTag) {
    openArchiveAssetModal(id, assetTag);
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
                const qrItem = document.createElement('div');
                qrItem.className = 'qr-item p-4 border border-gray-300 rounded-lg text-center';
                qrItem.innerHTML = `
                    <div class="mb-2">
                        <img src="${asset.qr_code || 'placeholder.png'}" alt="QR Code" class="w-48 h-48 mx-auto">
                    </div>
                    <div class="text-sm font-semibold text-gray-900">${asset.asset_tag}</div>
                    <div class="text-xs text-gray-600">${asset.asset_name}</div>
                    ${asset.brand ? `<div class="text-xs text-gray-500">${asset.brand}${asset.model ? ' - ' + asset.model : ''}</div>` : ''}
                `;
                qrContent.appendChild(qrItem);
            });
            
            closeAddAssetModal();
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

// =====================
// Import Excel / CSV
// =====================
// Load SheetJS lazily
(function ensureSheetJS(){
    if(!window.XLSX){
        const s=document.createElement('script');
        s.src='https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
        s.defer=true;
        document.head.appendChild(s);
    }
})();

let parsedImportHeaders = [];
let parsedImportRows = [];
let originalTableHTML = null;

function openImportExcelModal(){
    const modal = document.getElementById('importExcelModal');
    const input = document.getElementById('excelFileInput');
    const head = document.getElementById('importPreviewHead');
    const body = document.getElementById('importPreviewBody');
    const container = document.getElementById('importPreviewContainer');
    if(modal){ modal.classList.remove('hidden'); }
    if(input){ input.value=''; }
    if(head){ head.innerHTML=''; }
    if(body){ body.innerHTML=''; }
    if(container){ container.classList.add('hidden'); }
    parsedImportHeaders = [];
    parsedImportRows = [];
}

function closeImportExcelModal(){
    const modal = document.getElementById('importExcelModal');
    if(modal){ modal.classList.add('hidden'); }
}

// Parse on file select
document.addEventListener('change', function(e){
    if(e.target && e.target.id === 'excelFileInput'){
        const file = e.target.files && e.target.files[0];
        if(!file){ return; }
        if(!window.XLSX){
            alert('Parser not loaded yet. Please try again in a moment.');
            return;
        }
        const reader = new FileReader();
        const name = file.name.toLowerCase();
        const isCSV = name.endsWith('.csv');

        reader.onload = function(evt){
            try{
                let workbook;
                if(isCSV){
                    const text = evt.target.result;
                    workbook = XLSX.read(text, { type: 'string' });
                } else {
                    const data = evt.target.result;
                    workbook = XLSX.read(data, { type: 'array' });
                }
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];
                const rows = XLSX.utils.sheet_to_json(worksheet, { header:1, defval:'' });
                if(!rows || rows.length === 0){
                    alert('The file appears to be empty.');
                    return;
                }
                parsedImportHeaders = rows[0].map(h => (h||'').toString());
                parsedImportRows = rows.slice(1).filter(r => r.some(cell => (cell||'').toString().trim() !== ''));
                renderImportPreview(parsedImportHeaders, parsedImportRows);
            }catch(err){
                console.error(err);
                alert('Failed to parse the file. Please check the format.');
            }
        };

        if(isCSV){
            reader.readAsText(file);
        } else {
            reader.readAsArrayBuffer(file);
        }
    }
});

function renderImportPreview(headers, rows){
    const headEl = document.getElementById('importPreviewHead');
    const bodyEl = document.getElementById('importPreviewBody');
    const container = document.getElementById('importPreviewContainer');
    if(!headEl || !bodyEl || !container){ return; }
    headEl.innerHTML = '';
    bodyEl.innerHTML = '';

    const tr = document.createElement('tr');
    headers.forEach(h => {
        const th = document.createElement('th');
        th.className = 'px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
        th.textContent = h;
        tr.appendChild(th);
    });
    headEl.appendChild(tr);

    const maxRows = Math.min(rows.length, 50);
    for(let i=0;i<maxRows;i++){
        const row = rows[i];
        const trb = document.createElement('tr');
        trb.className = 'hover:bg-gray-50';
        headers.forEach((_, idx) => {
            const td = document.createElement('td');
            td.className = 'px-4 py-2 text-sm text-gray-700';
            td.textContent = (row[idx] !== undefined && row[idx] !== null) ? row[idx] : '';
            trb.appendChild(td);
        });
        bodyEl.appendChild(trb);
    }
    container.classList.remove('hidden');
}

function applyImportPreviewToTable(){
    if(parsedImportHeaders.length === 0 || parsedImportRows.length === 0){
        alert('No data to display. Please select a valid file.');
        return;
    }
    const tbody = document.getElementById('assetsTableBody');
    if(!tbody){
        alert('Table body not found.');
        return;
    }

    if(originalTableHTML === null){
        originalTableHTML = tbody.innerHTML;
    }

    const headerMap = buildHeaderIndexMap(parsedImportHeaders);
    const fragment = document.createDocumentFragment();
    const limit = Math.min(parsedImportRows.length, 500);
    for(let i=0;i<limit;i++){
        const r = parsedImportRows[i];
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 transition-colors';

        // Checkbox
        const tdCheck = document.createElement('td');
        tdCheck.className = 'px-6 py-4 whitespace-nowrap';
        tdCheck.innerHTML = '<input type="checkbox" disabled class="asset-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 opacity-50 cursor-not-allowed">';
        tr.appendChild(tdCheck);

        // Index
        const tdIdx = document.createElement('td');
        tdIdx.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-500';
        tdIdx.textContent = i + 1;
        tr.appendChild(tdIdx);

        // Asset Tag
        tr.appendChild(buildCell(r, headerMap, ['asset tag','asset_tag','tag'], 'px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600'));
        // Asset Name
        tr.appendChild(buildCell(r, headerMap, ['asset name','asset_name','name'], 'px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'));
        // Type
        tr.appendChild(buildCell(r, headerMap, ['type','asset type','asset_type'], 'px-6 py-4 whitespace-nowrap text-sm text-gray-500'));
        // Brand/Model combined
        const brand = getValue(r, headerMap, ['brand']);
        const model = getValue(r, headerMap, ['model']);
        const tdBrandModel = document.createElement('td');
        tdBrandModel.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-500';
        const combined = [brand, model].filter(Boolean).join(' - ');
        tdBrandModel.textContent = combined || 'N/A';
        tr.appendChild(tdBrandModel);
        // Serial Number
        tr.appendChild(buildCell(r, headerMap, ['serial number','serial_number','serial'], 'px-6 py-4 whitespace-nowrap text-sm text-gray-500', 'N/A'));
        // Room
        tr.appendChild(buildCell(r, headerMap, ['room','room name','room_name'], 'px-6 py-4 whitespace-nowrap text-sm text-gray-500', 'N/A'));
        // Status
        tr.appendChild(buildCell(r, headerMap, ['status'], 'px-6 py-4 whitespace-nowrap', 'Available'));
        // Condition
        tr.appendChild(buildCell(r, headerMap, ['condition'], 'px-6 py-4 whitespace-nowrap', 'Good'));

        // Actions placeholder
        const tdActions = document.createElement('td');
        tdActions.className = 'px-6 py-4 whitespace-nowrap text-center text-sm';
        tdActions.innerHTML = '<span class="text-xs text-gray-400">Preview</span>';
        tr.appendChild(tdActions);

        fragment.appendChild(tr);
    }

    tbody.innerHTML = '';
    tbody.appendChild(fragment);
    closeImportExcelModal();
    showImportPreviewBanner();
}

// Send parsed preview rows to server for DB import then display
async function importPreviewToDatabase(){
    if(parsedImportHeaders.length === 0 || parsedImportRows.length === 0){
        alert('No parsed data to import.');
        return;
    }
    const btn = document.getElementById('importAndDisplayBtn');
    if(btn){
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Importing...';
    }
    try {
        // Limit rows sent for performance (you can adjust as needed)
        const limit = Math.min(parsedImportRows.length, 1000);
        const rowsToSend = parsedImportRows.slice(0, limit);
        const formData = new FormData();
        formData.append('ajax','1');
        formData.append('action','import_excel_rows');
        formData.append('headers_json', JSON.stringify(parsedImportHeaders));
        formData.append('rows_json', JSON.stringify(rowsToSend));
        const response = await fetch(location.href,{method:'POST', body:formData});
        const result = await response.json();
        if(result.success){
            showAlert('success', result.message);
            // Replace table with server-confirmed assets (result.assets) for accuracy
            const tbody = document.getElementById('assetsTableBody');
            if(tbody && Array.isArray(result.assets)){
                const fragment = document.createDocumentFragment();
                result.assets.forEach((asset, idx) => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-gray-50 transition-colors';
                    const statusClass = getStatusClass(asset.status);
                    const conditionClass = getConditionClass(asset.condition);
                    tr.innerHTML = `
                        <td class='px-6 py-4 whitespace-nowrap'><input type='checkbox' disabled class='asset-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 opacity-50 cursor-not-allowed'></td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>${idx+1}</td>
                        <td class='px-6 py-4 whitespace-nowrap'><span class='text-sm font-medium text-blue-600'>${escapeHtml(asset.asset_tag)}</span></td>
                        <td class='px-6 py-4 whitespace-nowrap'><span class='text-sm font-medium text-gray-900'>${escapeHtml(asset.asset_name)}</span></td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>${escapeHtml(asset.asset_type)}</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>${escapeHtml([asset.brand, asset.model].filter(Boolean).join(' - ') || 'N/A')}</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>${escapeHtml(asset.serial_number || 'N/A')}</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>${escapeHtml(asset.room_name || 'N/A')}</td>
                        <td class='px-6 py-4 whitespace-nowrap'><span class='px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}'>${escapeHtml(asset.status || 'Available')}</span></td>
                        <td class='px-6 py-4 whitespace-nowrap'><span class='px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${conditionClass}'>${escapeHtml(asset.condition || 'Good')}</span></td>
                        <td class='px-6 py-4 whitespace-nowrap text-center text-sm'><span class='text-xs text-gray-400'>Imported</span></td>`;
                    fragment.appendChild(tr);
                });
                if(originalTableHTML === null){ originalTableHTML = tbody.innerHTML; }
                tbody.innerHTML='';
                tbody.appendChild(fragment);
            }
            closeImportExcelModal();
            showImportPreviewBanner();
        } else {
            showAlert('error', result.message || 'Import failed');
        }
    } catch(err){
        console.error(err);
        showAlert('error','Unexpected error during import');
    } finally {
        if(btn){
            btn.disabled = false;
            btn.innerHTML = 'Import & Display';
        }
    }
}

function escapeHtml(str){
    if(str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
}

function getStatusClass(status){
    const s = String(status || '').toLowerCase();
    const map = {
        'active': 'bg-green-100 text-green-700',
        'available': 'bg-green-100 text-green-700',
        'in use': 'bg-blue-100 text-blue-700',
        'under maintenance': 'bg-yellow-100 text-yellow-700',
        'retired': 'bg-gray-100 text-gray-700',
        'disposed': 'bg-red-100 text-red-700',
        'lost': 'bg-red-100 text-red-700',
        'damaged': 'bg-orange-100 text-orange-700',
        'archived': 'bg-purple-100 text-purple-700'
    };
    return map[s] || 'bg-gray-100 text-gray-700';
}

function getConditionClass(cond){
    const c = String(cond || '').toLowerCase();
    const map = {
        'excellent': 'bg-green-100 text-green-700',
        'good': 'bg-blue-100 text-blue-700',
        'fair': 'bg-yellow-100 text-yellow-700',
        'poor': 'bg-orange-100 text-orange-700',
        'non-functional': 'bg-red-100 text-red-700',
        'non functional': 'bg-red-100 text-red-700'
    };
    return map[c] || 'bg-gray-100 text-gray-700';
}

function buildHeaderIndexMap(headers){
    const map = {};
    headers.forEach((h, idx) => {
        const key = (h||'').toString().trim().toLowerCase();
        map[key] = idx;
    });
    return map;
}

function getValue(row, map, candidates, fallback=''){
    for(const c of candidates){
        const key = c.toLowerCase();
        if(map[key] !== undefined){
            const v = row[map[key]];
            if(v !== undefined && v !== null && String(v).trim() !== ''){
                return v;
            }
        }
    }
    return fallback;
}

function buildCell(row, map, candidates, className, fallback=''){
    const td = document.createElement('td');
    td.className = className || 'px-6 py-4 whitespace-nowrap text-sm text-gray-700';
    td.textContent = getValue(row, map, candidates, fallback);
    return td;
}

function showImportPreviewBanner(){
    let banner = document.getElementById('importPreviewBanner');
    if(!banner){
        banner = document.createElement('div');
        banner.id = 'importPreviewBanner';
        banner.className = 'fixed bottom-4 right-4 bg-yellow-100 border border-yellow-300 text-yellow-800 px-4 py-2 rounded shadow z-50 flex items-center gap-3';
        banner.innerHTML = '<span class="text-sm">Imported and saved. Showing preview of imported rows.</span>'+
                           '<button id="clearImportPreviewBtn" class="text-sm px-2 py-1 bg-yellow-300 hover:bg-yellow-400 rounded">Restore Table</button>'+
                           '<button id="reloadPageBtn" class="text-sm px-2 py-1 bg-blue-600 text-white hover:bg-blue-700 rounded">Reload Page</button>';
        document.body.appendChild(banner);
        document.getElementById('clearImportPreviewBtn').addEventListener('click', restoreOriginalTableFromPreview);
        document.getElementById('reloadPageBtn').addEventListener('click', () => window.location.reload());
    }
}

function restoreOriginalTableFromPreview(){
    const tbody = document.getElementById('assetsTableBody');
    if(tbody && originalTableHTML !== null){
        tbody.innerHTML = originalTableHTML;
    }
    const banner = document.getElementById('importPreviewBanner');
    if(banner){ banner.remove(); }
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

// Close menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('[id^="menu-"]') && !e.target.closest('button')) {
        closeAllMenus();
    }
});

// Category dropdown handlers
document.addEventListener('DOMContentLoaded', function() {
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
    
    // Add event listener for room selection to trigger asset tag generation
    const roomSelect = document.getElementById('roomId');
    if (roomSelect) {
        roomSelect.addEventListener('change', function() {
            const assetName = document.getElementById('assetName')?.value;
            if (assetName) {
                generateAssetTag();
            }
        });
    }
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
    const optionElements = optionsContainer.querySelectorAll('.dropdown-option');
    
    optionElements.forEach(option => {
        const text = option.textContent.toLowerCase();
        const value = option.getAttribute('data-value');
        
        // Always show "Add New Category" option
        if (value === '__add_new__') {
            option.style.display = '';
            return;
        }
        
        // Filter other options based on search
        if (text.includes(searchTerm)) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
}

// Update searchable dropdown display
function updateSearchableDropdownDisplay(selectId, value) {
    const select = document.getElementById(selectId);
    if (!select) return;
    
    const dropdown = select.parentElement ? select.parentElement.querySelector('.searchable-dropdown') : null;
    if (!dropdown) return;
    
    const selectedText = dropdown.querySelector('.selected-text');
    if (!selectedText) return;
    
    if (value) {
        selectedText.textContent = value;
        selectedText.className = 'selected-text text-gray-900';
        select.value = value;
    } else {
        selectedText.textContent = 'Select Category';
        selectedText.className = 'selected-text text-gray-500';
        select.value = '';
    }
}

// Update searchable dropdown options
async function updateSearchableDropdownOptions(selectId) {
    try {
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'get_categories');
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success && result.categories) {
            const select = document.getElementById(selectId);
            const dropdown = select.parentElement.querySelector('.searchable-dropdown');
            const dropdownList = dropdown.querySelector('.dropdown-list');
            
            // Clear existing options except "Add New Category"
            const addNewOption = dropdownList.querySelector('[data-value="__add_new__]');
            dropdownList.innerHTML = '';
            dropdownList.appendChild(addNewOption);
            
            // Set click handler for Add New Category
            addNewOption.addEventListener('click', () => openAddCategoryModal(selectId));
            
            // Add new category options
            result.categories.forEach(category => {
                const option = document.createElement('div');
                option.className = 'dropdown-option px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm';
                option.setAttribute('data-value', category.name);
                option.textContent = category.name;
                dropdownList.appendChild(option);
                
                // Re-attach click event
                option.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    const text = this.textContent.trim();
                    
                    // Update hidden select
                    select.value = value;
                    
                    // Update display text
                    const selectedText = dropdown.querySelector('.selected-text');
                    selectedText.textContent = text;
                    selectedText.className = 'selected-text text-gray-900';
                    
                    // Close dropdown
                    const options = dropdown.querySelector('.dropdown-options');
                    options.classList.add('hidden');
                    
                    // Trigger change event for compatibility
                    select.dispatchEvent(new Event('change'));
                    
                    // Trigger asset tag generation if this is the asset name field in single mode
                    if (select.id === 'assetName') {
                        generateAssetTag();
                    }
                    
                    // Clear search
                    const searchInput = dropdown.querySelector('.dropdown-search');
                    searchInput.value = '';
                });
            });
            
            // Update the hidden select options too
            select.innerHTML = '<option value="">Select Category</option>';
            result.categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.name;
                option.textContent = category.name;
                select.appendChild(option);
            });
            const addNewSelectOption = document.createElement('option');
            addNewSelectOption.value = '__add_new__';
            addNewSelectOption.textContent = '+ Add New Category';
            select.appendChild(addNewSelectOption);
        }
    } catch (error) {
        console.error('Error updating dropdown options:', error);
    }
}

// Open Add Category Modal
function openAddCategoryModal(sourceSelectId) {
    // Store which select triggered the modal
    window.currentCategorySource = sourceSelectId;
    document.getElementById('addCategoryModal').classList.remove('hidden');
    document.getElementById('newCategoryName').focus();
}

// Close Add Category Modal
function closeAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.add('hidden');
    document.getElementById('newCategoryName').value = '';
    window.currentCategorySource = null;
}

// Add New Category
async function addNewCategory() {
    const categoryName = document.getElementById('newCategoryName').value.trim();
    
    if (!categoryName) {
        showAlert('error', 'Please enter a category name');
        return;
    }
    
    // Add the category locally first for instant feedback
    if (window.currentCategorySource) {
        const select = document.getElementById(window.currentCategorySource);
        const dropdown = select.parentElement.querySelector('.searchable-dropdown');
        const dropdownList = dropdown.querySelector('.dropdown-list');
        
        // Check if already exists
        const existing = dropdownList.querySelector(`[data-value="${categoryName}"]`);
        if (!existing) {
            // Add to dropdown options
            const option = document.createElement('div');
            option.className = 'dropdown-option px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm';
            option.setAttribute('data-value', categoryName);
            option.textContent = categoryName;
            
            // Add click handler
            option.addEventListener('click', function() {
                select.value = categoryName;
                const selectedText = dropdown.querySelector('.selected-text');
                selectedText.textContent = categoryName;
                selectedText.className = 'selected-text text-gray-900';
                const options = dropdown.querySelector('.dropdown-options');
                options.classList.add('hidden');
                select.dispatchEvent(new Event('change'));
                
                // Trigger asset tag generation if this is the asset name field in single mode
                if (select.id === 'assetName') {
                    generateAssetTag();
                }
                
                const searchInput = dropdown.querySelector('.dropdown-search');
                searchInput.value = '';
            });
            
            // Insert before "Add New Category"
            const addNew = dropdownList.querySelector('[data-value="__add_new__]');
            dropdownList.insertBefore(option, addNew);
            
            // Add to select options
            const selectOption = document.createElement('option');
            selectOption.value = categoryName;
            selectOption.textContent = categoryName;
            const addNewSelect = select.querySelector('[value="__add_new__]');
            select.insertBefore(selectOption, addNewSelect);
            
            // Set as selected
            updateSearchableDropdownDisplay(window.currentCategorySource, categoryName);
        }
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
            
            // Update all other dropdowns with the new category (excluding the current one since it's already updated)
            const dropdownIds = ['assetName', 'bulkAssetName', 'editAssetName'].filter(id => id !== window.currentCategorySource);
            await Promise.all(dropdownIds.map(id => updateSearchableDropdownOptions(id)));
        } else {
            showAlert('error', result.message);
            
            // Revert local changes if server failed
            if (window.currentCategorySource) {
                const select = document.getElementById(window.currentCategorySource);
                const dropdown = select.parentElement.querySelector('.searchable-dropdown');
                const dropdownList = dropdown.querySelector('.dropdown-list');
                
                // Remove from dropdown
                const option = dropdownList.querySelector(`[data-value="${categoryName}"]`);
                if (option) option.remove();
                
                // Remove from select
                const selectOption = select.querySelector(`option[value="${categoryName}"]`);
                if (selectOption) selectOption.remove();
                
                // Reset display if it was selected
                if (select.value === categoryName) {
                    updateSearchableDropdownDisplay(window.currentCategorySource, '');
                }
            }
        }
    } catch (error) {
        console.error('Error adding category:', error);
        showAlert('error', 'An error occurred while adding the category');
        
        // Revert local changes on error
        if (window.currentCategorySource) {
            const select = document.getElementById(window.currentCategorySource);
            const dropdown = select.parentElement.querySelector('.searchable-dropdown');
            const dropdownList = dropdown.querySelector('.dropdown-list');
            
            // Remove from dropdown
            const option = dropdownList.querySelector(`[data-value="${categoryName}"]`);
            if (option) option.remove();
            
            // Remove from select
            const selectOption = select.querySelector(`option[value="${categoryName}"]`);
            if (selectOption) selectOption.remove();
            
            // Reset display if it was selected
            if (select.value === categoryName) {
                updateSearchableDropdownDisplay(window.currentCategorySource, '');
            }
        }
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
    
    // Initialize bulk edit dropdowns if needed
    initializeBulkEditDropdowns();
}

function closeBulkEditModal() {
    document.getElementById('bulkEditModal').classList.add('hidden');
    document.getElementById('bulkEditForm').reset();
}

// Initialize bulk edit dropdowns
function initializeBulkEditDropdowns() {
    // Reset the asset name dropdown if it exists
    const bulkEditAssetName = document.getElementById('bulkEditAssetName');
    if (bulkEditAssetName) {
        bulkEditAssetName.value = '';
        const dropdown = bulkEditAssetName.parentElement ? bulkEditAssetName.parentElement.querySelector('.searchable-dropdown') : null;
        if (dropdown) {
            const selectedText = dropdown.querySelector('.selected-text');
            if (selectedText) {
                selectedText.textContent = 'Select Category';
                selectedText.className = 'selected-text text-gray-500';
            }
        }
    }
}

// Bulk Edit Form Submit
document.getElementById('bulkEditForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    console.log('=== BULK EDIT SUBMIT STARTED ===');
    
    const selectedCheckboxes = document.querySelectorAll('.asset-checkbox:checked');
    console.log('Selected checkboxes:', selectedCheckboxes.length);
    
    if (selectedCheckboxes.length === 0) {
        showAlert('error', 'No assets selected');
        return;
    }
    
    const assetIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.value));
    console.log('Asset IDs:', assetIds);
    
    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    formData.append('action', 'bulk_update');
    formData.append('asset_ids', JSON.stringify(assetIds));
    
    // Only append fields that have values (not empty)
    const assetName = document.getElementById('bulkEditAssetName').value;
    console.log('Asset Name:', assetName);
    if (assetName) formData.append('asset_name', assetName);
    
    const assetType = document.getElementById('bulkEditAssetType').value;
    console.log('Asset Type:', assetType);
    if (assetType) formData.append('asset_type', assetType);
    
    const roomId = document.getElementById('bulkEditRoomId').value;
    console.log('Room ID:', roomId);
    if (roomId !== '') formData.append('room_id', roomId);
    
    const status = document.getElementById('bulkEditStatus').value;
    console.log('Status:', status);
    if (status) formData.append('status', status);
    
    const condition = document.getElementById('bulkEditCondition').value;
    console.log('Condition:', condition);
    if (condition) formData.append('condition', condition);
    
    const isBorrowable = document.getElementById('bulkEditIsBorrowable').value;
    console.log('Is Borrowable:', isBorrowable);
    if (isBorrowable !== '') formData.append('is_borrowable', isBorrowable);
    
    console.log('FormData to send:', Object.fromEntries(formData));
    
    try {
        const submitBtn = document.getElementById('bulkEditBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Updating...';
        
        console.log('Sending request to:', location.href);
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Get the raw text first to see what we're getting
        const rawText = await response.text();
        console.log('Raw response text:', rawText);
        
        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(rawText);
            console.log('Parsed JSON result:', result);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Failed to parse response:', rawText.substring(0, 500));
            showAlert('error', 'Server returned invalid response. Check console for details.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-save mr-2"></i>Update Selected Assets';
            return;
        }
        
        if (result.success) {
            console.log('Success! Message:', result.message);
            showAlert('success', result.message);
            closeBulkEditModal();
            clearSelection();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            console.error('Update failed:', result.message);
            showAlert('error', result.message || 'Failed to update assets');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-save mr-2"></i>Update Selected Assets';
        }
    } catch (error) {
        console.error('=== BULK EDIT ERROR ===');
        console.error('Error type:', error.name);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        showAlert('error', 'An error occurred: ' + error.message);
        const submitBtn = document.getElementById('bulkEditBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-save mr-2"></i>Update Selected Assets';
        }
    }
});

// Close bulk edit modal when clicking outside
document.getElementById('bulkEditModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeBulkEditModal();
    }
});
</script>
<?php include '../components/layout_footer.php'; ?>