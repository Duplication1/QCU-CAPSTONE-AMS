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

// Get room_id from URL
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

if ($room_id <= 0) {
    header("Location: buildings.php");
    exit();
}

// Get room and building details
$room_query = $conn->prepare("SELECT r.*, b.name as building_name FROM rooms r LEFT JOIN buildings b ON r.building_id = b.id WHERE r.id = ?");
$room_query->bind_param('i', $room_id);
$room_query->execute();
$room_result = $room_query->get_result();
$room = $room_result->fetch_assoc();
$room_query->close();

if (!$room) {
    header("Location: buildings.php");
    exit();
}

// Fetch assets with search, filter, and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] === '1';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Count total PC units (for tab badges)
$pc_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM pc_units WHERE room_id = ? AND status != 'Archive'");
$pc_count_stmt->bind_param('i', $room_id);
$pc_count_stmt->execute();
$pc_count_result = $pc_count_stmt->get_result();
$total_pc_units = $pc_count_result->fetch_assoc()['total'];
$pc_count_stmt->close();

// Handle AJAX requests for assets
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    
    if ($action === 'create_asset') {
        $terminal_number = trim($_POST['terminal_number'] ?? '');
        $status = trim($_POST['status'] ?? 'Active');
        $notes = trim($_POST['notes'] ?? '');
        $bulk_mode = $_POST['bulk_mode'] ?? 'single';
        $range_start = intval($_POST['range_start'] ?? 1);
        $range_end = intval($_POST['range_end'] ?? 1);
        $prefix = trim($_POST['prefix'] ?? 'PC-');
        
        if (empty($terminal_number) && $bulk_mode === 'single') {
            echo json_encode(['success' => false, 'message' => 'Terminal number is required']);
            exit;
        }
        
        if ($bulk_mode === 'bulk' && ($range_start < 1 || $range_end < $range_start || $range_end - $range_start > 100)) {
            echo json_encode(['success' => false, 'message' => 'Invalid range. Maximum 100 units at once.']);
            exit;
        }
        
        try {
            $building_id = $room['building_id'] ?? null;
            $created_count = 0;
            $failed = [];
            $created_pc_ids = [];
            
            if ($bulk_mode === 'bulk') {
                // Bulk creation
                for ($i = $range_start; $i <= $range_end; $i++) {
                    $terminal = $prefix . str_pad($i, 2, '0', STR_PAD_LEFT);
                    
                    // Check if terminal number already exists
                    $check = $conn->prepare("SELECT id FROM pc_units WHERE room_id = ? AND terminal_number = ?");
                    $check->bind_param('is', $room_id, $terminal);
                    $check->execute();
                    $check->store_result();
                    
                    if ($check->num_rows > 0) {
                        $failed[] = $terminal . ' (already exists)';
                        $check->close();
                        continue;
                    }
                    $check->close();
                    
                    $stmt = $conn->prepare("INSERT INTO pc_units (room_id, building_id, terminal_number, status, notes) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param('iisss', $room_id, $building_id, $terminal, $status, $notes);
                    if ($stmt->execute()) {
                        $created_count++;
                        $created_pc_ids[] = $conn->insert_id;
                    } else {
                        $failed[] = $terminal;
                    }
                    $stmt->close();
                }
                
                $message = "Created $created_count PC units successfully";
                if (!empty($failed)) {
                    $message .= ". Failed: " . implode(', ', $failed);
                }
                
                // Automatically create assets for selected PC components
                if ($created_count > 0 && !empty($created_pc_ids)) {
                    $selected_components = $_POST['selected_components'] ?? [];
                    
                    if (!empty($selected_components)) {
                        $assets_created = 0;
                        $asset_failed = [];
                        
                        foreach ($selected_components as $category_id) {
                            // Get category name
                            $cat_stmt = $conn->prepare("SELECT name FROM asset_categories WHERE id = ?");
                            $cat_stmt->bind_param('i', $category_id);
                            $cat_stmt->execute();
                            $cat_result = $cat_stmt->get_result();
                            $category = $cat_result->fetch_assoc();
                            $cat_stmt->close();
                            
                            if (!$category) continue;
                            
                            // Get specifications from form
                            $brand = trim($_POST["component_brand_{$category_id}"] ?? '');
                            $model = trim($_POST["component_model_{$category_id}"] ?? '');
                            $serial = trim($_POST["component_serial_{$category_id}"] ?? '');
                            $condition = trim($_POST["component_condition_{$category_id}"] ?? 'Good');
                            
                            foreach ($created_pc_ids as $pc_id) {
                                // Generate asset tag: PC-{PC_ID}-{CATEGORY}-001
                                $asset_tag = "PC-{$pc_id}-{$category['name']}-001";
                                $asset_name = $category['name'] . ' for PC-' . $pc_id;
                                
                                // Check if asset tag already exists
                                $check_asset = $conn->prepare("SELECT id FROM assets WHERE asset_tag = ?");
                                $check_asset->bind_param('s', $asset_tag);
                                $check_asset->execute();
                                $check_asset->store_result();
                                
                                if ($check_asset->num_rows > 0) {
                                    $check_asset->close();
                                    continue; // Skip if asset already exists
                                }
                                $check_asset->close();
                                
                                // Generate QR code data
                                $qr_data = json_encode([
                                    'asset_tag' => $asset_tag,
                                    'asset_name' => $asset_name,
                                    'asset_type' => 'Hardware',
                                    'room_id' => $room_id,
                                    'room_name' => $room['name'],
                                    'pc_unit_id' => $pc_id
                                ]);
                                $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr_data);
                                
                                // Insert asset
                                $asset_stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, serial_number, room_id, pc_unit_id, status, `condition`, qr_code, created_by, category) VALUES (?, ?, 'Hardware', ?, ?, ?, ?, ?, 'Available', ?, ?, ?, ?)");
                                $created_by = $_SESSION['user_id'];
                                $asset_stmt->bind_param('sssssiissi', $asset_tag, $asset_name, $brand, $model, $serial, $room_id, $pc_id, $condition, $qr_code_url, $created_by, $category_id);
                                
                                if ($asset_stmt->execute()) {
                                    $assets_created++;
                                } else {
                                    $asset_failed[] = $asset_tag;
                                }
                                $asset_stmt->close();
                            }
                        }
                        
                        if ($assets_created > 0) {
                            $message .= ". Automatically created $assets_created assets from selected components";
                        }
                        if (!empty($asset_failed)) {
                            $message .= ". Failed to create assets: " . implode(', ', $asset_failed);
                        }
                    }
                }
                
                echo json_encode(['success' => $created_count > 0, 'message' => $message, 'created' => $created_count]);
            } else {
                // Single creation
                $stmt = $conn->prepare("INSERT INTO pc_units (room_id, building_id, terminal_number, status, notes) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('iisss', $room_id, $building_id, $terminal_number, $status, $notes);
                $success = $stmt->execute();
                $new_id = $conn->insert_id;
                $stmt->close();
                
                if ($success) {
                    // Automatically create assets for selected PC components
                    $selected_components = $_POST['selected_components'] ?? [];
                    
                    if (!empty($selected_components)) {
                        $assets_created = 0;
                        $asset_failed = [];
                        
                        foreach ($selected_components as $category_id) {
                            // Get category name
                            $cat_stmt = $conn->prepare("SELECT name FROM asset_categories WHERE id = ?");
                            $cat_stmt->bind_param('i', $category_id);
                            $cat_stmt->execute();
                            $cat_result = $cat_stmt->get_result();
                            $category = $cat_result->fetch_assoc();
                            $cat_stmt->close();
                            
                            if (!$category) continue;
                            
                            // Get specifications from form
                            $brand = trim($_POST["component_brand_{$category_id}"] ?? '');
                            $model = trim($_POST["component_model_{$category_id}"] ?? '');
                            $serial = trim($_POST["component_serial_{$category_id}"] ?? '');
                            $condition = trim($_POST["component_condition_{$category_id}"] ?? 'Good');
                            
                            // Generate asset tag: PC-{PC_ID}-{CATEGORY}-001
                            $asset_tag = "PC-{$new_id}-{$category['name']}-001";
                            $asset_name = $category['name'] . ' for PC-' . $new_id;
                            
                            // Check if asset tag already exists
                            $check_asset = $conn->prepare("SELECT id FROM assets WHERE asset_tag = ?");
                            $check_asset->bind_param('s', $asset_tag);
                            $check_asset->execute();
                            $check_asset->store_result();
                            
                            if ($check_asset->num_rows > 0) {
                                $check_asset->close();
                                continue; // Skip if asset already exists
                            }
                            $check_asset->close();
                            
                            // Generate QR code data
                            $qr_data = json_encode([
                                'asset_tag' => $asset_tag,
                                'asset_name' => $asset_name,
                                'asset_type' => 'Hardware',
                                'room_id' => $room_id,
                                'room_name' => $room['name'],
                                'pc_unit_id' => $new_id
                            ]);
                            $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr_data);
                            
                            // Insert asset
                            $asset_stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, serial_number, room_id, pc_unit_id, status, `condition`, qr_code, created_by, category) VALUES (?, ?, 'Hardware', ?, ?, ?, ?, ?, 'Available', ?, ?, ?, ?)");
                            $created_by = $_SESSION['user_id'];
                            $asset_stmt->bind_param('sssssiissi', $asset_tag, $asset_name, $brand, $model, $serial, $room_id, $new_id, $condition, $qr_code_url, $created_by, $category_id);
                            
                            if ($asset_stmt->execute()) {
                                $assets_created++;
                            } else {
                                $asset_failed[] = $asset_tag;
                            }
                            $asset_stmt->close();
                        }
                        
                        $message = 'PC Unit created successfully';
                        if ($assets_created > 0) {
                            $message .= ". Automatically created $assets_created assets from selected components";
                        }
                        if (!empty($asset_failed)) {
                            $message .= ". Failed to create assets: " . implode(', ', $asset_failed);
                        }
                        
                        echo json_encode(['success' => true, 'message' => $message, 'id' => $new_id]);
                    } else {
                        echo json_encode(['success' => true, 'message' => 'PC Unit created successfully', 'id' => $new_id]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create PC unit']);
                }
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    
    if ($action === 'restore_asset') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid asset ID']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE assets SET status = 'Active' WHERE id = ? AND room_id = ?");
            $stmt->bind_param('ii', $id, $room_id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Asset restored successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to restore asset']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    
    if ($action === 'create_asset') {
        $asset_tag = trim($_POST['asset_tag'] ?? '');
        $asset_name = trim($_POST['asset_name'] ?? '');
        $asset_type = trim($_POST['asset_type'] ?? 'Hardware');
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        $status = trim($_POST['status'] ?? 'Available');
        $condition = trim($_POST['condition'] ?? 'Good');
        
        if (empty($asset_tag) || empty($asset_name)) {
            echo json_encode(['success' => false, 'message' => 'Asset tag and name are required']);
            exit;
        }
        
        // Lookup category ID
        $category_id = null;
        if (!empty($asset_name)) {
            $category_stmt = $conn->prepare("SELECT id FROM asset_categories WHERE name = ?");
            $category_stmt->bind_param('s', $asset_name);
            $category_stmt->execute();
            $category_result = $category_stmt->get_result();
            if ($category_result->num_rows > 0) {
                $category_id = $category_result->fetch_assoc()['id'];
            }
            $category_stmt->close();
        }
        
        try {
            // Generate QR code data
            $qr_data = json_encode([
                'asset_tag' => $asset_tag,
                'asset_name' => $asset_name,
                'asset_type' => $asset_type,
                'room_id' => $room_id,
                'room_name' => $room['name']
            ]);
            $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr_data);
            
            $stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, serial_number, room_id, status, `condition`, qr_code, created_by, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $created_by = $_SESSION['user_id'];
            $stmt->bind_param('ssssssisssi', $asset_tag, $asset_name, $asset_type, $brand, $model, $serial_number, $room_id, $status, $condition, $qr_code_url, $created_by, $category_id);
            $success = $stmt->execute();
            $new_id = $conn->insert_id;
            $stmt->close();
            
            if ($success) {
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
        $query = $conn->prepare("SELECT asset_tag FROM assets WHERE asset_tag LIKE ? AND room_id = ?");
        $query->bind_param('si', $pattern, $room_id);
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
        $is_borrowable = isset($_POST['is_borrowable']) ? 1 : 0;
        
        // Lookup category ID
        $category_id = null;
        if (!empty($asset_name_prefix)) {
            $category_stmt = $conn->prepare("SELECT id FROM asset_categories WHERE name = ?");
            $category_stmt->bind_param('s', $asset_name_prefix);
            $category_stmt->execute();
            $category_result = $category_stmt->get_result();
            if ($category_result->num_rows > 0) {
                $category_id = $category_result->fetch_assoc()['id'];
            }
            $category_stmt->close();
        }
        
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
                
                // Generate asset tag: 11-23-2025-LAPTOP-IK501-001
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
                
                // Generate unique QR code for this asset
                $qr_data = json_encode([
                    'asset_tag' => $asset_tag,
                    'asset_name' => $asset_name,
                    'asset_type' => $asset_type,
                    'room_id' => $room_id,
                    'room_name' => $room['name'],
                    'brand' => $brand,
                    'model' => $model
                ]);
                $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr_data);
                
                // Insert asset with QR code
                $stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, room_id, status, `condition`, qr_code, created_by, category, is_borrowable) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssssissssii', $asset_tag, $asset_name, $asset_type, $brand, $model, $room_id, $status, $condition, $qr_code_url, $created_by, $category_id, $is_borrowable);
                
                if ($stmt->execute()) {
                    $created_count++;
                    $created_asset_ids[] = $conn->insert_id;
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
        $is_borrowable = isset($_POST['is_borrowable']) ? 1 : 0;
        
        if ($id <= 0 || empty($asset_tag) || empty($asset_name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE assets SET asset_tag = ?, asset_name = ?, asset_type = ?, brand = ?, model = ?, serial_number = ?, status = ?, `condition` = ?, is_borrowable = ?, updated_by = ? WHERE id = ? AND room_id = ?");
            $updated_by = $_SESSION['user_id'];
            $stmt->bind_param('sssssssiisis', $asset_tag, $asset_name, $asset_type, $brand, $model, $serial_number, $status, $condition, $is_borrowable, $updated_by, $id, $room_id);
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
            $stmt = $conn->prepare("UPDATE assets SET status = 'Archive', updated_by = ? WHERE id = ? AND room_id = ?");
            $updated_by = $_SESSION['user_id'];
            $stmt->bind_param('iii', $updated_by, $id, $room_id);
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
            $stmt = $conn->prepare("SELECT id, asset_tag, asset_name, asset_type, brand, model, qr_code FROM assets WHERE id IN ($placeholders) AND room_id = ?");
            $types = str_repeat('i', count($asset_ids)) . 'i';
            $params = array_merge($asset_ids, [$room_id]);
            $stmt->bind_param($types, ...$params);
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
    
    if ($action === 'delete_asset') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid asset ID']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("DELETE FROM assets WHERE id = ? AND room_id = ?");
            $stmt->bind_param('ii', $id, $room_id);
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
    
    
    if ($action === 'bulk_archive_assets') {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        
        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['success' => false, 'message' => 'Invalid asset IDs']);
            exit;
        }
        
        try {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $conn->prepare("UPDATE assets SET status = 'Archive', updated_by = ? WHERE id IN ($placeholders) AND room_id = ?");
            $updated_by = $_SESSION['user_id'];
            $types = 'i' . str_repeat('i', count($ids)) . 'i';
            $params = array_merge([$updated_by], $ids, [$room_id]);
            $stmt->bind_param($types, ...$params);
            $success = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => "$affected asset(s) archived successfully"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to archive assets']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    
    if ($action === 'bulk_restore_assets') {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        
        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['success' => false, 'message' => 'Invalid asset IDs']);
            exit;
        }
        
        try {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $conn->prepare("UPDATE assets SET status = 'Active', updated_by = ? WHERE id IN ($placeholders) AND room_id = ?");
            $updated_by = $_SESSION['user_id'];
            $types = 'i' . str_repeat('i', count($ids)) . 'i';
            $params = array_merge([$updated_by], $ids, [$room_id]);
            $stmt->bind_param($types, ...$params);
            $success = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => "$affected asset(s) restored successfully"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to restore assets']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
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
    
    if ($action === 'get_pc_categories') {
        $pc_categories_data = [];
        $pc_cat_query = "SELECT id, name FROM asset_categories WHERE is_pc_category = 1 ORDER BY name ASC";
        $pc_cat_result = $conn->query($pc_cat_query);
        if ($pc_cat_result && $pc_cat_result->num_rows > 0) {
            while ($row = $pc_cat_result->fetch_assoc()) {
                $pc_categories_data[] = $row;
            }
        }
        echo json_encode(['success' => true, 'categories' => $pc_categories_data]);
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
}

// Fetch assets with search, filter, and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] === '1';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Count total assets
$count_query = "SELECT COUNT(*) as total FROM assets WHERE room_id = ?";
$params = [$room_id];
$types = 'i';

if (!$show_archived) {
    $count_query .= " AND (status != 'Archive' AND status != 'Archived')";
}

if (!empty($filter_status)) {
    $count_query .= " AND status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($search)) {
    $count_query .= " AND (asset_tag LIKE ? OR asset_name LIKE ? OR brand LIKE ? OR model LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= 'ssss';
}

$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_assets = $total_result->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_assets / $limit);

// Fetch assets
$assets = [];
$query_sql = "SELECT * FROM assets WHERE room_id = ?";
$params = [$room_id];
$types = 'i';

if (!$show_archived) {
    $query_sql .= " AND (status != 'Archive' AND status != 'Archived')";
}

if (!empty($filter_status)) {
    $query_sql .= " AND status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($search)) {
    $query_sql .= " AND (asset_tag LIKE ? OR asset_name LIKE ? OR brand LIKE ? OR model LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= 'ssss';
}

$query_sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

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



// Count archived PC units and assets for archives tab
$archived_pc_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM pc_units WHERE room_id = ? AND (status = 'Archive' OR status = 'Archived')");
$archived_pc_count_stmt->bind_param('i', $room_id);
$archived_pc_count_stmt->execute();
$archived_pc_result = $archived_pc_count_stmt->get_result();
$total_archived_pc = $archived_pc_result->fetch_assoc()['total'];
$archived_pc_count_stmt->close();

$archived_assets_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM assets WHERE room_id = ? AND (status = 'Archive' OR status = 'Archived')");
$archived_assets_count_stmt->bind_param('i', $room_id);
$archived_assets_count_stmt->execute();
$archived_assets_result = $archived_assets_count_stmt->get_result();
$total_archived_assets = $archived_assets_result->fetch_assoc()['total'];
$archived_assets_count_stmt->close();

$archived_pc_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM pc_units WHERE room_id = ? AND (status = 'Archive' OR status = 'Archived')");
$archived_pc_count_stmt->bind_param('i', $room_id);
$archived_pc_count_stmt->execute();
$archived_pc_result = $archived_pc_count_stmt->get_result();
$total_archived_pc = $archived_pc_result->fetch_assoc()['total'];
$archived_pc_count_stmt->close();

// Fetch all PC units in this room (for reference in assets table)
$pc_units = [];
$pc_units_stmt = $conn->prepare("SELECT id, terminal_number FROM pc_units WHERE room_id = ? ORDER BY terminal_number");
$pc_units_stmt->bind_param('i', $room_id);
$pc_units_stmt->execute();
$pc_units_result = $pc_units_stmt->get_result();
if ($pc_units_result && $pc_units_result->num_rows > 0) {
    while ($row = $pc_units_result->fetch_assoc()) {
        $pc_units[] = $row;
    }
}
$pc_units_stmt->close();

include '../components/layout_header.php';
?>

<style>
html, body {
    height: 100vh;
    overflow: hidden;
}
#app-container {
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
main {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    padding: 0.5rem;
    background-color: #f9fafb;
}
</style>

<main>
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Breadcrumb -->
        <div class="mb-4">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="buildings.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                            <i class="fa-solid fa-building mr-2"></i>
                            Buildings
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-gray-400 mx-2"></i>
                            <a href="rooms.php?building_id=<?php echo $room['building_id']; ?>" class="text-sm font-medium text-gray-700 hover:text-blue-600">
                                <?php echo htmlspecialchars($room['building_name']); ?>
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-sm font-medium text-gray-500"><?php echo htmlspecialchars($room['name']); ?></span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 overflow-hidden">
            <div class="grid grid-cols-4 border-b border-gray-200">
                <a href="pcunits.php?room_id=<?php echo $room_id; ?>" 
                   class="px-4 py-3 text-sm font-medium transition-all duration-200 border-b-2 border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 flex items-center justify-center">
                    <i class="fa-solid fa-desktop mr-2"></i>PC Units
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-gray-500 text-white rounded-full"><?php echo $total_pc_units; ?></span>
                </a>
                <button onclick="return false;" id="tab-all-assets" class="px-4 py-3 text-sm font-medium transition-all duration-200 border-b-2 border-blue-500 text-blue-600 bg-blue-50 flex items-center justify-center cursor-default">
                    <i class="fa-solid fa-boxes-stacked mr-2"></i>All Assets
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-[#1E3A8A] text-white rounded-full"><?php echo $total_assets; ?></span>
                </button>
                <a href="archived_pc_units.php?room_id=<?php echo $room_id; ?>" 
                   class="px-4 py-3 text-sm font-medium transition-all duration-200 border-b-2 border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 flex items-center justify-center">
                    <i class="fa-solid fa-archive mr-2"></i>Archived PC
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-red-500 text-white rounded-full"><?php echo $total_archived_pc; ?></span>
                </a>
                <a href="archived_assets.php?room_id=<?php echo $room_id; ?>" 
                   class="px-4 py-3 text-sm font-medium transition-all duration-200 border-b-2 border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 flex items-center justify-center">
                    <i class="fa-solid fa-archive mr-2"></i>Archived Assets
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-red-500 text-white rounded-full"><?php echo $total_archived_assets; ?></span>
                </a>
            </div>
        </div>


        <!-- All Assets Section -->
        <!-- Assets Search Bar -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 px-4 py-3">
            <div class="flex gap-3">
                <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                <div class="flex-1">
                    <input type="text" id="assetSearchInput" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by asset tag, name, brand, or model..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <?php if (!empty($search)): ?>
                <a href="?room_id=<?php echo $room_id; ?><?php echo !empty($pc_search) ? '&pc_search=' . urlencode($pc_search) : ''; ?>" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fa-solid fa-times mr-2"></i>Clear
                </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 overflow-hidden">
            <div class="px-4 py-3 bg-gradient-to-r from-green-50 to-green-100 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-boxes-stacked text-green-600"></i>
                    <h4 class="text-sm font-semibold text-gray-800">All Assets</h4>
                    <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded-full">
                        <?php echo $total_assets; ?> Total
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <div id="asset-bulk-actions" class="hidden flex items-center gap-2">
                        <button onclick="bulkPrintQRAssets()" class="px-3 py-1.5 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded transition-colors">
                            <i class="fa-solid fa-qrcode mr-1"></i>Print QR Codes
                        </button>
                        <button onclick="bulkArchiveAssets()" class="px-3 py-1.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded transition-colors">
                            <i class="fa-solid fa-archive mr-1"></i>Archive Selected
                        </button>
                    </div>
                    <button onclick="openImportRoomAssetsModal()" class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded transition-colors">
                        <i class="fa-solid fa-file-import mr-1"></i>Import Room Assets
                    </button>
                    <button onclick="openAddAssetModal()" class="px-3 py-1.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded transition-colors">
                        <i class="fa-solid fa-plus mr-1"></i>Add Asset
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
            <!-- Assets Table -->
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
                    <tr>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                            <input type="checkbox" id="select-all-assets" class="rounded border-gray-300 text-[#1E3A8A] focus:ring-[#1E3A8A]">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Tag</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand/Model</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To PC</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($assets)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <i class="fa-solid fa-box text-5xl mb-3 opacity-30"></i>
                                <p class="text-lg">No assets found</p>
                                <?php if (!empty($search)): ?>
                                    <p class="text-sm">Try adjusting your search</p>
                                <?php else: ?>
                                    <p class="text-sm">Click "Add Asset" to create one</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assets as $index => $asset): ?>
                            <tr class="hover:bg-gray-50 transition-colors" data-id="<?php echo $asset['id']; ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <input type="checkbox" class="asset-checkbox rounded border-gray-300 text-[#1E3A8A] focus:ring-[#1E3A8A]" value="<?php echo $asset['id']; ?>">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $offset + $index + 1; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-blue-600"><?php echo htmlspecialchars($asset['asset_tag']); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($asset['asset_name']); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($asset['asset_type']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php 
                                    $brand_model = array_filter([
                                        $asset['brand'] ?? '', 
                                        $asset['model'] ?? ''
                                    ]);
                                    echo htmlspecialchars(implode(' - ', $brand_model) ?: 'N/A'); 
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if (!empty($asset['pc_unit_id'])): ?>
                                        <?php
                                        // Find PC unit terminal number
                                        $pc_terminal = 'N/A';
                                        foreach ($pc_units as $pc) {
                                            if ($pc['id'] == $asset['pc_unit_id']) {
                                                $pc_terminal = $pc['terminal_number'];
                                                break;
                                            }
                                        }
                                        ?>
                                        <span class="px-2 py-1 inline-flex items-center gap-1 text-xs font-medium bg-indigo-100 text-indigo-700 rounded">
                                            <i class="fa-solid fa-desktop"></i>
                                            <?php echo htmlspecialchars($pc_terminal); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $condition_colors = [
                                        'Excellent' => 'bg-green-100 text-green-700',
                                        'Good' => 'bg-blue-100 text-blue-700',
                                        'Fair' => 'bg-yellow-100 text-yellow-700',
                                        'Poor' => 'bg-orange-100 text-orange-700',
                                        'Non-Functional' => 'bg-red-100 text-red-700'
                                    ];
                                    $condition_class = $condition_colors[$asset['condition']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $condition_class; ?>">
                                        <?php echo htmlspecialchars($asset['condition']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <div class="relative">
                                        <button onclick="toggleMenu(<?php echo $asset['id']; ?>)" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                            <i class="fa-solid fa-ellipsis-vertical text-xl"></i>
                                        </button>
                                        <div id="menu-<?php echo $asset['id']; ?>" class="hidden fixed bg-white rounded-lg shadow-lg border border-gray-200 z-50" style="min-width: 12rem;">
                                            <div class="py-1">
                                                <button onclick='printQRCode(<?php echo json_encode($asset); ?>)' 
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                    <i class="fa-solid fa-qrcode text-purple-600"></i> Print QR Code
                                                </button>
                                                <button onclick='editAsset(<?php echo json_encode($asset); ?>)' 
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                    <i class="fa-solid fa-pencil text-blue-600"></i> Edit
                                                </button>
                                                <?php if ($asset['status'] !== 'Archive' && $asset['status'] !== 'Archived'): ?>
                                                <button onclick="archiveAsset(<?php echo $asset['id']; ?>, '<?php echo htmlspecialchars($asset['asset_tag'], ENT_QUOTES); ?>')" 
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                    <i class="fa-solid fa-archive text-red-600"></i> Archive
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
            
            <!-- Assets Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="bg-white rounded shadow-sm border border-gray-200 mt-3 px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Showing <?php echo count($assets); ?> of <?php echo $total_assets; ?> assets
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['pc_page']) ? '&pc_page=' . $_GET['pc_page'] : ''; ?><?php echo !empty($pc_search) ? '&pc_search=' . urlencode($pc_search) : ''; ?>" 
                           class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                            <i class="fa-solid fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="?room_id=<?php echo $room_id; ?>&page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['pc_page']) ? '&pc_page=' . $_GET['pc_page'] : ''; ?><?php echo !empty($pc_search) ? '&pc_search=' . urlencode($pc_search) : ''; ?>" 
                           class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="px-2 text-gray-500">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['pc_page']) ? '&pc_page=' . $_GET['pc_page'] : ''; ?><?php echo !empty($pc_search) ? '&pc_search=' . urlencode($pc_search) : ''; ?>" 
                           class="px-3 py-1 text-sm rounded <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition-colors">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="px-2 text-gray-500">...</span>
                        <?php endif; ?>
                        <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['pc_page']) ? '&pc_page=' . $_GET['pc_page'] : ''; ?><?php echo !empty($pc_search) ? '&pc_search=' . urlencode($pc_search) : ''; ?>" 
                           class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['pc_page']) ? '&pc_page=' . $_GET['pc_page'] : ''; ?><?php echo !empty($pc_search) ? '&pc_search=' . urlencode($pc_search) : ''; ?>" 
                           class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                            Next <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        </div>
    </div>


</main>



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
                    <div class="searchable-dropdown relative">
                        <div class="dropdown-display flex items-center justify-between px-4 py-2 border border-gray-300 rounded-lg bg-white cursor-pointer hover:border-gray-400 transition-colors">
                            <span class="selected-text text-gray-500">Select Category</span>
                            <i class="fa-solid fa-chevron-down text-gray-400"></i>
                        </div>
                        <div class="dropdown-options absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 hidden max-h-60 overflow-y-auto">
                            <input type="text" class="dropdown-search w-full px-4 py-2 border-b border-gray-200 text-sm focus:outline-none focus:ring-0" placeholder="Search categories...">
                            <div class="dropdown-list max-h-48 overflow-y-auto">
                                <!-- Options will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                    <select id="assetName" name="asset_name" class="hidden">
                        <option value="">Select Category</option>
                        <option value="__add_new__">+ Add New Category</option>
                    </select>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" name="status" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="Active">Active</option>
                        <option value="In Use">In Use</option>
                        <option value="Available" selected>Available</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                        <option value="Retired">Retired</option>
                        <option value="Disposed">Disposed</option>
                        <option value="Lost">Lost</option>
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
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="isBorrowable" name="is_borrowable" value="1"
                               class="mr-2 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">Allow this asset to be borrowed</span>
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
                            <p class="mt-1 text-xs">Example: 11-23-2025-LAPTOP-IK501-001 through 11-23-2025-LAPTOP-IK501-020</p>
                        </div>
                    </div>
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
                        <div class="searchable-dropdown relative">
                            <div class="dropdown-display flex items-center justify-between px-4 py-2 border border-gray-300 rounded-lg bg-white cursor-pointer hover:border-gray-400 transition-colors">
                                <span class="selected-text text-gray-500">Select Category</span>
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                            <div class="dropdown-options absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 hidden max-h-60 overflow-y-auto">
                                <input type="text" class="dropdown-search w-full px-4 py-2 border-b border-gray-200 text-sm focus:outline-none focus:ring-0" placeholder="Search categories...">
                                <div class="dropdown-list max-h-48 overflow-y-auto">
                                    <!-- Options will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                        <select id="bulkAssetName" name="bulk_asset_name" class="hidden">
                            <option value="">Select Category</option>
                            <option value="__add_new__">+ Add New Category</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Room Number <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="bulkRoomNumber" name="bulk_room_number" 
                               value="<?php echo $room['name'] ?? ''; ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., IK501">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Quantity <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="bulkQuantity" name="bulk_quantity" 
                               min="1" max="100" value="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., 20">
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
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                        <input type="text" id="bulkBrand" name="bulk_brand"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., Dell, HP">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                        <input type="text" id="bulkModel" name="bulk_model"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., Latitude 5420">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="bulkStatus" name="bulk_status" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Active">Active</option>
                            <option value="In Use">In Use</option>
                            <option value="Available" selected>Available</option>
                            <option value="Under Maintenance">Under Maintenance</option>
                            <option value="Retired">Retired</option>
                            <option value="Disposed">Disposed</option>
                            <option value="Lost">Lost</option>
                            <option value="Damaged">Damaged</option>
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

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" id="bulkIsBorrowable" name="bulk_is_borrowable" value="1"
                                   class="mr-2 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Allow these assets to be borrowed</span>
                        </label>
                    </div>
                </div>

                <div class="text-xs text-gray-600 bg-yellow-50 border border-yellow-200 rounded p-3">
                    <i class="fa-solid fa-lightbulb mr-1"></i>
                    <strong>Preview:</strong> <span id="assetTagPreview">11-23-2025-LAPTOP-IK501-001, 11-23-2025-LAPTOP-IK501-002, ...</span>
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
                    <input type="text" id="editAssetName" name="asset_name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="editStatus" name="status" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="Active">Active</option>
                        <option value="In Use">In Use</option>
                        <option value="Available">Available</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                        <option value="Retired">Retired</option>
                        <option value="Disposed">Disposed</option>
                        <option value="Lost">Lost</option>
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
            </div>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="editIsBorrowable" name="edit_is_borrowable" value="1"
                               class="mr-2 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">Allow this asset to be borrowed</span>
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
        <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Confirm Archive</h3>
        </div>
        <div class="p-6">
            <div class="flex items-center mb-4">
                <i class="fa-solid fa-triangle-exclamation text-red-500 text-2xl mr-3"></i>
                <div>
                    <p class="text-gray-800 font-medium">Archive PC Unit(s)</p>
                </div>
            </div>
            <div id="archiveModalContent" class="mb-6">
                <!-- Content will be dynamically inserted -->
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeArchiveModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button type="button" id="confirmArchiveBtn" onclick="confirmArchive()"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fa-solid fa-archive mr-2"></i>Archive
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Category Name *</label>
                <input type="text" id="newCategoryName" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeAddCategoryModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="addNewCategory()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Add Category
                </button>
            </div>
        </div>
    </div>
</div>

<style>
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
// No tab switching needed - single page view

// Modal functions
function openAddAssetModal() {
    document.getElementById('addAssetModal').classList.remove('hidden');
    document.getElementById('assetName').focus();
}

function closeAddAssetModal() {
    document.getElementById('addAssetModal').classList.add('hidden');
    document.getElementById('addAssetForm').reset();
}

function closeEditAssetModal() {
    document.getElementById('editAssetModal').classList.add('hidden');
    document.getElementById('editAssetForm').reset();
}

function openArchiveModal(content, callback) {
    document.getElementById('archiveModalContent').innerHTML = content;
    document.getElementById('archiveModal').classList.remove('hidden');
    window.confirmArchiveCallback = callback;
}

function closeArchiveModal() {
    document.getElementById('archiveModal').classList.add('hidden');
    document.getElementById('archiveModalContent').innerHTML = '';
    window.confirmArchiveCallback = null;
}

function confirmArchive() {
    if (window.confirmArchiveCallback) {
        window.confirmArchiveCallback();
    }
    closeArchiveModal();
}

// Add Asset Form Submit - wrapped in DOMContentLoaded to ensure form exists
document.addEventListener('DOMContentLoaded', function() {
    const addAssetForm = document.getElementById('addAssetForm');
    if (!addAssetForm) return;
    
    addAssetForm.addEventListener('submit', async function(e) {
        e.preventDefault();
    
    const bulkMode = document.querySelector('input[name="asset_bulk_mode"]:checked').value;
    
    // Validate category selection
    if (bulkMode === 'bulk') {
        let bulkCategory = document.getElementById('bulkAssetName').value;
        if (!bulkCategory) {
            // Try to select the first valid category
            const dropdown = document.getElementById('bulkAssetName').parentElement.querySelector('.searchable-dropdown');
            const options = dropdown.querySelectorAll('.dropdown-option');
            for (let option of options) {
                const value = option.getAttribute('data-value');
                if (value && value !== '__add_new__') {
                    updateSearchableDropdownDisplay('bulkAssetName', value);
                    bulkCategory = value;
                    break;
                }
            }
        }
        if (!bulkCategory || bulkCategory === '__add_new__') {
            showAlert('error', 'Please select a valid category for bulk creation');
            return;
        }
    } else {
        let singleCategory = document.getElementById('assetName').value;
        if (!singleCategory) {
            // Try to select the first valid category
            const dropdown = document.getElementById('assetName').parentElement.querySelector('.searchable-dropdown');
            const options = dropdown.querySelectorAll('.dropdown-option');
            for (let option of options) {
                const value = option.getAttribute('data-value');
                if (value && value !== '__add_new__') {
                    updateSearchableDropdownDisplay('assetName', value);
                    singleCategory = value;
                    break;
                }
            }
        }
        if (!singleCategory || singleCategory === '__add_new__') {
            showAlert('error', 'Please select a valid category for asset creation');
            return;
        }
    }
    
    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    
    if (bulkMode === 'bulk') {
        // Bulk mode
        formData.append('action', 'bulk_create_assets');
        formData.append('acquisition_date', document.getElementById('bulkAcquisitionDate').value);
        formData.append('asset_name', document.getElementById('bulkAssetName').value);
        formData.append('room_number', document.getElementById('bulkRoomNumber').value);
        formData.append('quantity', document.getElementById('bulkQuantity').value);
        formData.append('start_number', document.getElementById('bulkStartNumber').value);
        formData.append('asset_type', document.getElementById('bulkAssetType').value);
        formData.append('brand', document.getElementById('bulkBrand').value);
        formData.append('model', document.getElementById('bulkModel').value);
        formData.append('status', document.getElementById('bulkStatus').value);
        formData.append('condition', document.getElementById('bulkCondition').value);
        formData.append('is_borrowable', document.getElementById('bulkIsBorrowable').checked ? '1' : '0');
    } else {
        // Single mode
        formData.append('action', 'create_asset');
        formData.append('asset_tag', document.getElementById('assetTag').value);
        formData.append('asset_name', document.getElementById('assetName').value);
        formData.append('asset_type', document.getElementById('assetType').value);
        formData.append('brand', document.getElementById('brand').value);
        formData.append('model', document.getElementById('model').value);
        formData.append('serial_number', document.getElementById('serialNumber').value);
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
            
            // If bulk mode and assets were created, show print QR modal
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
}); // End DOMContentLoaded for addAssetForm

// Update Starting Number for Bulk Assets
async function updateBulkStartNumber() {
    const assetName = document.getElementById('bulkAssetName')?.value?.trim();
    const roomNumber = document.getElementById('bulkRoomNumber')?.value?.trim();
    const startNumberField = document.getElementById('bulkStartNumber');
    
    if (!assetName || !roomNumber || !startNumberField) {
        return;
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
        
        // Sync category selection from single to bulk mode
        const singleCategory = document.getElementById('assetName').value;
        if (singleCategory && singleCategory !== '__add_new__') {
            updateSearchableDropdownDisplay('bulkAssetName', singleCategory);
            document.getElementById('bulkAssetName').dispatchEvent(new Event('change'));
        }
        
        updateBulkStartNumber();
        updateAssetTagPreview();
    } else {
        singleFields.classList.remove('hidden');
        bulkFields.classList.add('hidden');
        submitBtn.textContent = 'Create Asset';
        
        // Sync category selection from bulk to single mode
        const bulkCategory = document.getElementById('bulkAssetName').value;
        if (bulkCategory && bulkCategory !== '__add_new__') {
            updateSearchableDropdownDisplay('assetName', bulkCategory);
            document.getElementById('assetName').dispatchEvent(new Event('change'));
        }
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
    
    // Get room name from the current room
    const roomName = '<?php echo addslashes($room['name']); ?>';
    let roomNumber = 'NOROOM';
    if (roomName) {
        const roomMatch = roomName.match(/([A-Z0-9]+)/);
        if (roomMatch) {
            roomNumber = roomMatch[1];
        } else {
            roomNumber = roomName.replace(/\\s+/g, '').toUpperCase();
        }
    }
    
    try {
        // Get current date
        const today = new Date();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const year = today.getFullYear();
        const formattedDate = `${month}-${day}-${year}`;
        
        // Create asset name prefix (first few letters)
        const assetNamePrefix = assetName.substring(0, Math.min(10, assetName.length)).toUpperCase().replace(/\\s+/g, '');
        
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
        const assetNamePrefix = assetName.substring(0, Math.min(10, assetName.length)).toUpperCase().replace(/\\s+/g, '');
        assetTagField.value = `${formattedDate}-${assetNamePrefix}-${roomNumber}-001`;
    }
}

// Update Asset Tag Preview
function updateAssetTagPreview() {
    const date = document.getElementById('bulkAcquisitionDate')?.value || '2025-11-23';
    const assetName = document.getElementById('bulkAssetName')?.value || 'LAPTOP';
    const roomNumber = document.getElementById('bulkRoomNumber')?.value || 'IK501';
    const quantity = parseInt(document.getElementById('bulkQuantity')?.value || 1);
    const startNumber = parseInt(document.getElementById('bulkStartNumber')?.value || 1);
    
    const dateParts = date.split('-');
    const formattedDate = `${dateParts[1]}-${dateParts[2]}-${dateParts[0]}`;
    
    const preview = document.getElementById('assetTagPreview');
    if (preview) {
        if (quantity <= 3) {
            const tags = [];
            for (let i = 0; i < quantity; i++) {
                const num = String(startNumber + i).padStart(3, '0');
                tags.push(`${formattedDate}-${assetName}-${roomNumber}-${num}`);
            }
            preview.textContent = tags.join(', ');
        } else {
            const firstNum = String(startNumber).padStart(3, '0');
            const lastNum = String(startNumber + quantity - 1).padStart(3, '0');
            preview.textContent = `${formattedDate}-${assetName}-${roomNumber}-${firstNum} ... ${formattedDate}-${assetName}-${roomNumber}-${lastNum}`;
        }
    }
}

// Add event listeners for preview update
document.addEventListener('DOMContentLoaded', function() {
    const previewFields = ['bulkAcquisitionDate', 'bulkAssetName', 'bulkRoomNumber', 'bulkQuantity', 'bulkStartNumber'];
    previewFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', updateAssetTagPreview);
            // Also listen for change events for select elements
            if (field.tagName === 'SELECT') {
                field.addEventListener('change', updateAssetTagPreview);
            }
        }
    });
    
    // Add listeners for start number update
    const startNumberTriggerFields = ['bulkAssetName', 'bulkRoomNumber'];
    startNumberTriggerFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', updateBulkStartNumber);
            // Also listen for change events for select elements
            if (field.tagName === 'SELECT') {
                field.addEventListener('change', updateBulkStartNumber);
            }
        }
    });
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
    document.getElementById('editStatus').value = asset.status;
    document.getElementById('editCondition').value = asset.condition;
    document.getElementById('editIsBorrowable').checked = asset.is_borrowable == '1';
    document.getElementById('editAssetModal').classList.remove('hidden');
    document.getElementById('editAssetTag').focus();
}

// Edit Asset Form Submit - wrapped in DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    const editAssetForm = document.getElementById('editAssetForm');
    if (!editAssetForm) return;
    
    editAssetForm.addEventListener('submit', async function(e) {
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
            window.location.reload();
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while updating the asset');
    }
});
}); // End DOMContentLoaded for editAssetForm

// Delete Asset
// Archive Asset
async function archiveAsset(id, assetTag) {
    closeAllMenus();
    
    const content = `
        <p class="text-gray-700 mb-2">Are you sure you want to archive the following asset?</p>
        <div class="bg-gray-50 p-3 rounded-lg border">
            <strong>Asset Tag:</strong> ${assetTag}
        </div>
    `;
    
    openArchiveModal(content, async () => {
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
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showAlert('error', result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('error', 'An error occurred while archiving the asset');
        }
    });
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

function closeQRPrintModal() {
    document.getElementById('qrPrintModal').classList.add('hidden');
    // Reload page after closing print modal
    window.location.reload();
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

// Import: open/close modals
function openImportPcUnitsModal() { document.getElementById('importPcUnitsModal').classList.remove('hidden'); }
function closeImportPcUnitsModal() { document.getElementById('importPcUnitsModal').classList.add('hidden'); const f=document.getElementById('importPcUnitsFile'); if(f) f.value=''; }
function openImportRoomAssetsModal() { document.getElementById('importRoomAssetsModal').classList.remove('hidden'); }
function closeImportRoomAssetsModal() { document.getElementById('importRoomAssetsModal').classList.add('hidden'); const f=document.getElementById('importRoomAssetsFile'); if(f) f.value=''; }

// Ensure SheetJS is available
function ensureSheetJSLoaded() {
    return new Promise((resolve) => {
        if (window.XLSX) return resolve();
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
        script.onload = () => resolve();
        document.head.appendChild(script);
    });
}

// Parse file to headers + rows
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

// Import PC Units
async function processImportPcUnits() {
    const fileInput = document.getElementById('importPcUnitsFile');
    const file = fileInput && fileInput.files[0];
    if (!file) { showAlert('error', 'Please select a file'); return; }
    try {
        const { headers, rows } = await parseFileToRows(file);
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'import_pc_units_rows');
        formData.append('headers', JSON.stringify(headers));
        formData.append('rows', JSON.stringify(rows));
        const res = await fetch(location.href, { method: 'POST', body: formData });
        const result = await res.json();
        if (result.success) {
            showAlert('success', `Imported ${result.created.length} PC unit(s)`);
            closeImportPcUnitsModal();
            setTimeout(() => window.location.reload(), 800);
        } else {
            showAlert('error', result.message || 'Import failed');
        }
    } catch (e) {
        console.error(e);
        showAlert('error', 'Failed to parse or import file');
    }
}

// Import Room Assets
async function processImportRoomAssets() {
    const fileInput = document.getElementById('importRoomAssetsFile');
    const file = fileInput && fileInput.files[0];
    if (!file) { showAlert('error', 'Please select a file'); return; }
    try {
        const { headers, rows } = await parseFileToRows(file);
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'import_room_assets_rows');
        formData.append('headers', JSON.stringify(headers));
        formData.append('rows', JSON.stringify(rows));
        const res = await fetch(location.href, { method: 'POST', body: formData });
        const result = await res.json();
        if (result.success) {
            showAlert('success', `Imported ${result.created.length} asset(s)`);
            closeImportRoomAssetsModal();
            setTimeout(() => window.location.reload(), 800);
        } else {
            showAlert('error', result.message || 'Import failed');
        }
    } catch (e) {
        console.error(e);
        showAlert('error', 'Failed to parse or import file');
    }
}
// Toggle three-dot menu
function toggleMenu(id) {
    const button = event.target.closest('button');
    const menu = document.getElementById('menu-' + id);
    const allMenus = document.querySelectorAll('[id^="menu-"]');
    
    // Close all other menus
    allMenus.forEach(m => {
        if (m.id !== 'menu-' + id) {
            m.classList.add('hidden');
        }
    });
    
    // Toggle current menu
    if (!menu.classList.contains('hidden')) {
        menu.classList.add('hidden');
    } else {
        menu.classList.remove('hidden');
        
        // Position the menu near the button
        const buttonRect = button.getBoundingClientRect();
        menu.style.top = (buttonRect.bottom + window.scrollY) + 'px';
        menu.style.left = (buttonRect.left + window.scrollX - menu.offsetWidth + button.offsetWidth) + 'px';
    }
}

// Close all menus
function closeAllMenus() {
    const allMenus = document.querySelectorAll('[id^="menu-"]');
    allMenus.forEach(m => m.classList.add('hidden'));
}

// Close menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('tr') && !e.target.closest('[id^="menu-"]')) {
        closeAllMenus();
    }
});

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddAssetModal();
        closeEditAssetModal();
        closeEditPCUnitModal();
    }
});

// Load PC Components for selection
async function loadPCComponents() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'get_pc_categories');
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success && result.categories) {
            const container = document.getElementById('pcComponentsContainer');
            container.innerHTML = '';
            
            result.categories.forEach(category => {
                const componentDiv = document.createElement('div');
                componentDiv.className = 'component-item border border-gray-200 rounded-lg p-3';
                componentDiv.innerHTML = `
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="component_${category.id}" 
                                   name="selected_components[]" 
                                   value="${category.id}"
                                   onchange="toggleComponentSpecs(${category.id})"
                                   class="mr-3 text-[#1E3A8A] focus:ring-[#1E3A8A]">
                            <label for="component_${category.id}" class="text-sm font-medium text-gray-700 cursor-pointer">
                                ${category.name}
                            </label>
                        </div>
                    </div>
                    <div id="specs_${category.id}" class="component-specs hidden grid grid-cols-2 gap-3 mt-3 pl-6 border-l-2 border-gray-200">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Brand</label>
                            <input type="text" 
                                   name="component_brand_${category.id}" 
                                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-[#1E3A8A] focus:border-transparent"
                                   placeholder="e.g., Dell">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Model</label>
                            <input type="text" 
                                   name="component_model_${category.id}" 
                                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-[#1E3A8A] focus:border-transparent"
                                   placeholder="e.g., DDR4-8GB">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Serial Number</label>
                            <input type="text" 
                                   name="component_serial_${category.id}" 
                                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-[#1E3A8A] focus:border-transparent"
                                   placeholder="Optional">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Condition</label>
                            <select name="component_condition_${category.id}" 
                                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-[#1E3A8A] focus:border-transparent">
                                <option value="Good">Good</option>
                                <option value="Excellent">Excellent</option>
                                <option value="Fair">Fair</option>
                                <option value="Poor">Poor</option>
                                <option value="Non-Functional">Non-Functional</option>
                            </select>
                        </div>
                    </div>
                `;
                container.appendChild(componentDiv);
            });
        }
    } catch (error) {
        console.error('Error loading PC components:', error);
    }
}

// Toggle component specifications visibility
function toggleComponentSpecs(categoryId) {
    const checkbox = document.getElementById(`component_${categoryId}`);
    const specsDiv = document.getElementById(`specs_${categoryId}`);
    
    if (checkbox.checked) {
        specsDiv.classList.remove('hidden');
    } else {
        specsDiv.classList.add('hidden');
        // Clear the specification fields when unchecked
        const inputs = specsDiv.querySelectorAll('input, select');
        inputs.forEach(input => input.value = '');
    }
}

// Close menus when clicking outside
function bulkPrintQRAssets() {
    const selectedIds = Array.from(document.querySelectorAll('.asset-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) return;
    
    openQRPrintModalForAssets(selectedIds);
}

function bulkArchiveAssets() {
    const selectedIds = Array.from(document.querySelectorAll('.asset-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) return;
    
    const assetTags = selectedIds.map(id => {
        const row = document.querySelector(`.asset-checkbox[value="${id}"]`).closest('tr');
        return row.querySelector('td:nth-child(3) span').textContent.trim();
    });
    
    const content = `
        <p class="text-gray-700 mb-2">Are you sure you want to archive the following ${selectedIds.length} asset(s)?</p>
        <div class="bg-gray-50 p-3 rounded-lg border max-h-32 overflow-y-auto">
            <strong>Asset Tags:</strong><br>
            ${assetTags.map(tag => `<span class="inline-block bg-white px-2 py-1 rounded border text-sm mr-1 mb-1">${tag}</span>`).join('')}
        </div>
    `;
    
    openArchiveModal(content, () => {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'bulk_archive_assets');
        formData.append('ids', JSON.stringify(selectedIds));
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('error', data.message || 'Failed to archive assets');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'An error occurred while archiving assets');
        });
    });
}


// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize select all functionality for assets only
    initializeSelectAll();
    
    // Initialize searchable dropdowns
    initializeSearchableDropdowns();
    
    // Populate dropdowns with existing categories
    updateSearchableDropdownOptions('assetName');
    updateSearchableDropdownOptions('bulkAssetName');
    
    // Handle category dropdown changes for all asset name selects
    const categorySelects = ['assetName', 'bulkAssetName'];
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
    
    // Note: Room is fixed in this view, so no room change listener needed
    // Asset tag will be generated when category is selected
});

// Select All functionality
function initializeSelectAll() {
    // Assets select all checkbox
    const selectAllAssets = document.getElementById('select-all-assets');
    const assetCheckboxes = document.querySelectorAll('.asset-checkbox');
    
    if (selectAllAssets) {
        selectAllAssets.addEventListener('change', function() {
            assetCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            toggleAssetBulkActions();
        });
    }
    
    // Individual asset checkboxes
    assetCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateSelectAllAssets();
            toggleAssetBulkActions();
        });
    });
}

function updateSelectAllAssets() {
    const selectAllAssets = document.getElementById('select-all-assets');
    const assetCheckboxes = document.querySelectorAll('.asset-checkbox');
    const checkedCount = document.querySelectorAll('.asset-checkbox:checked').length;
    
    if (selectAllAssets) {
        selectAllAssets.checked = checkedCount === assetCheckboxes.length && assetCheckboxes.length > 0;
    }
}

function toggleAssetBulkActions() {
    const checkedCount = document.querySelectorAll('.asset-checkbox:checked').length;
    const bulkActions = document.getElementById('asset-bulk-actions');
    
    if (bulkActions) {
        if (checkedCount > 0) {
            bulkActions.classList.remove('hidden');
        } else {
            bulkActions.classList.add('hidden');
        }
    }
}

// Asset search with debouncing
let assetSearchTimeout;
const assetSearchInput = document.getElementById('assetSearchInput');

if (assetSearchInput) {
    assetSearchInput.addEventListener('input', function() {
        clearTimeout(assetSearchTimeout);
        const query = this.value.trim();
        
        assetSearchTimeout = setTimeout(() => {
            // Update URL without page reload
            const url = new URL(window.location);
            if (query) {
                url.searchParams.set('search', query);
            } else {
                url.searchParams.delete('search');
            }
            // Reset to page 1 when searching
            url.searchParams.delete('page');
            url.searchParams.delete('pc_page');
            url.searchParams.delete('pc_search');
            
            window.location.href = url.toString();
        }, 1000); // 1000ms debounce
    });
}

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
                if (searchInput) searchInput.focus();
                if (searchInput) searchInput.value = '';
                filterDropdownOptions(options, '');
            }
        });
        
        // Handle search input
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                filterDropdownOptions(options, this.value.toLowerCase());
            });
        }
        
        // Handle option selection
        const optionElements = options.querySelectorAll('.dropdown-option');
        optionElements.forEach(option => {
            option.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                const text = this.textContent.trim();
                
                if (value === '__add_new__') {
                    openAddCategoryModal(selectElement.id);
                    options.classList.add('hidden');
                    return;
                }
                
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
                if (searchInput) searchInput.value = '';
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
    const dropdown = select.parentElement.querySelector('.searchable-dropdown');
    const selectedText = dropdown.querySelector('.selected-text');
    
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
            
            // Clear existing options
            dropdownList.innerHTML = '';
            
            // Add "Add New Category" option first (at the top)
            const addNewOption = document.createElement('div');
            addNewOption.className = 'dropdown-option px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm border-b border-gray-200 mb-2 pb-3';
            addNewOption.setAttribute('data-value', '__add_new__');
            addNewOption.innerHTML = '<i class="fa-solid fa-plus mr-2 text-blue-600"></i>Add New Category';
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
                    if (searchInput) searchInput.value = '';
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
                
                // Trigger asset tag generation if this is the asset name field
                if (select.id === 'assetName') {
                    generateAssetTag();
                }
                
                const searchInput = dropdown.querySelector('.dropdown-search');
                if (searchInput) searchInput.value = '';
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
            
            // Dispatch change event to trigger preview updates
            select.dispatchEvent(new Event('change'));
            
            // Trigger asset tag generation if this is the asset name field
            if (window.currentCategorySource === 'assetName') {
                generateAssetTag();
            }
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
            const dropdownIds = ['assetName', 'bulkAssetName'].filter(id => id !== window.currentCategorySource);
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


</script>

<?php include '../components/layout_footer.php'; ?>


