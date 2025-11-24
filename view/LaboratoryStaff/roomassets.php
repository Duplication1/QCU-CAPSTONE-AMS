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

// Fetch PC Units from database with pagination
$pc_page = isset($_GET['pc_page']) ? max(1, intval($_GET['pc_page'])) : 1;
$pc_limit = 6;
$pc_offset = ($pc_page - 1) * $pc_limit;

// Count total PC units
$pc_count_query = $conn->prepare("SELECT COUNT(*) as total FROM pc_units WHERE room_id = ?");
$pc_count_query->bind_param('i', $room_id);
$pc_count_query->execute();
$pc_count_result = $pc_count_query->get_result();
$total_pc_units = $pc_count_result->fetch_assoc()['total'];
$pc_count_query->close();

$total_pc_pages = ceil($total_pc_units / $pc_limit);

// Fetch PC units with pagination
$pc_units = [];
$pc_query = $conn->prepare("SELECT * FROM pc_units WHERE room_id = ? ORDER BY terminal_number ASC LIMIT ? OFFSET ?");
$pc_query->bind_param('iii', $room_id, $pc_limit, $pc_offset);
$pc_query->execute();
$pc_result = $pc_query->get_result();
while ($pc_row = $pc_result->fetch_assoc()) {
    // Parse notes field to extract specs and health status
    $notes = $pc_row['notes'] ?? '';
    
    // Extract CPU, RAM, Storage from notes (format: "Description - CPU, RAM, Storage - Status")
    $cpu = 'N/A';
    $ram = 'N/A';
    $storage = 'N/A';
    $health_status = 'Healthy';
    
    if (preg_match('/Intel Core [^\,]+/', $notes, $cpu_match)) {
        $cpu = $cpu_match[0];
    }
    if (preg_match('/\d+GB DDR\d/', $notes, $ram_match)) {
        $ram = $ram_match[0];
    }
    if (preg_match('/\d+GB SSD/', $notes, $storage_match)) {
        $storage = $storage_match[0];
    }
    if (stripos($notes, 'Critical') !== false) {
        $health_status = 'Critical';
    } elseif (stripos($notes, 'Warning') !== false) {
        $health_status = 'Warning';
    }
    
    $pc_units[] = [
        'id' => $pc_row['id'],
        'terminal_number' => $pc_row['terminal_number'],
        'pc_name' => 'WORKSTATION-' . str_pad(substr($pc_row['terminal_number'], -2), 2, '0', STR_PAD_LEFT),
        'asset_tag' => 'COMP-' . $room['name'] . '-' . str_pad($pc_row['id'], 3, '0', STR_PAD_LEFT),
        'status' => $pc_row['status'],
        'cpu' => $cpu,
        'ram' => $ram,
        'storage' => $storage,
        'last_online' => $pc_row['updated_at'],
        'health_status' => $health_status,
        'notes' => $notes
    ];
}
$pc_query->close();

// Handle GET AJAX requests for PC components
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];
    
    if ($action === 'get_pc_components') {
        $pc_unit_id = intval($_GET['pc_unit_id'] ?? 0);
        
        if ($pc_unit_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid PC unit ID']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("SELECT * FROM assets WHERE pc_unit_id = ? ORDER BY asset_type, asset_name");
            $stmt->bind_param('i', $pc_unit_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $components = [];
            
            while ($row = $result->fetch_assoc()) {
                $components[] = $row;
            }
            $stmt->close();
            
            echo json_encode(['success' => true, 'components' => $components]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'get_available_assets') {
        try {
            $stmt = $conn->prepare("SELECT id, asset_tag, asset_name, asset_type, brand, model FROM assets WHERE room_id = ? AND (pc_unit_id IS NULL OR pc_unit_id = 0) ORDER BY asset_tag");
            $stmt->bind_param('i', $room_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $assets = [];
            
            while ($row = $result->fetch_assoc()) {
                $assets[] = $row;
            }
            $stmt->close();
            
            echo json_encode(['success' => true, 'assets' => $assets]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Handle AJAX requests for assets
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_component_to_pc') {
        $pc_unit_id = intval($_POST['pc_unit_id'] ?? 0);
        $asset_id = intval($_POST['asset_id'] ?? 0);
        
        if ($pc_unit_id <= 0 || $asset_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE assets SET pc_unit_id = ? WHERE id = ? AND room_id = ? AND (pc_unit_id IS NULL OR pc_unit_id = 0)");
            $stmt->bind_param('iii', $pc_unit_id, $asset_id, $room_id);
            $success = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            if ($success && $affected > 0) {
                echo json_encode(['success' => true, 'message' => 'Component added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add component. It may already be assigned.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'remove_component_from_pc') {
        $asset_id = intval($_POST['asset_id'] ?? 0);
        
        if ($asset_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid asset ID']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE assets SET pc_unit_id = NULL WHERE id = ? AND room_id = ?");
            $stmt->bind_param('ii', $asset_id, $room_id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Component removed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove component']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'create_pc_unit') {
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
                    } else {
                        $failed[] = $terminal;
                    }
                    $stmt->close();
                }
                
                $message = "Created $created_count PC units successfully";
                if (!empty($failed)) {
                    $message .= ". Failed: " . implode(', ', $failed);
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
                    echo json_encode(['success' => true, 'message' => 'PC Unit created successfully', 'id' => $new_id]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create PC unit']);
                }
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'update_pc_unit') {
        $id = intval($_POST['id'] ?? 0);
        $terminal_number = trim($_POST['terminal_number'] ?? '');
        $status = trim($_POST['status'] ?? 'Active');
        $notes = trim($_POST['notes'] ?? '');
        
        if ($id <= 0 || empty($terminal_number)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE pc_units SET terminal_number = ?, status = ?, notes = ? WHERE id = ? AND room_id = ?");
            $stmt->bind_param('sssii', $terminal_number, $status, $notes, $id, $room_id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'PC Unit updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update PC unit']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'archive_pc_unit') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid PC unit ID']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE pc_units SET status = 'Archive' WHERE id = ? AND room_id = ?");
            $stmt->bind_param('ii', $id, $room_id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'PC Unit archived successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to archive PC unit']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'delete_pc_unit') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid PC unit ID']);
            exit;
        }
        
        try {
            // First, unassign all assets from this PC unit
            $stmt = $conn->prepare("UPDATE assets SET pc_unit_id = NULL WHERE pc_unit_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            
            // Then delete the PC unit
            $stmt = $conn->prepare("DELETE FROM pc_units WHERE id = ? AND room_id = ?");
            $stmt->bind_param('ii', $id, $room_id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'PC Unit deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete PC unit']);
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
            
            $stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, serial_number, room_id, status, `condition`, qr_code, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $created_by = $_SESSION['user_id'];
            $stmt->bind_param('ssssssisss', $asset_tag, $asset_name, $asset_type, $brand, $model, $serial_number, $room_id, $status, $condition, $qr_code_url, $created_by);
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
                $stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, room_id, status, `condition`, qr_code, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssssisssi', $asset_tag, $asset_name, $asset_type, $brand, $model, $room_id, $status, $condition, $qr_code_url, $created_by);
                
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
        
        if ($id <= 0 || empty($asset_tag) || empty($asset_name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE assets SET asset_tag = ?, asset_name = ?, asset_type = ?, brand = ?, model = ?, serial_number = ?, status = ?, `condition` = ?, updated_by = ? WHERE id = ? AND room_id = ?");
            $updated_by = $_SESSION['user_id'];
            $stmt->bind_param('ssssssssiis', $asset_tag, $asset_name, $asset_type, $brand, $model, $serial_number, $status, $condition, $updated_by, $id, $room_id);
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
            $stmt = $conn->prepare("UPDATE assets SET status = 'Archived', updated_by = ? WHERE id = ? AND room_id = ?");
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
    $count_query .= " AND status != 'Archived'";
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
    $query_sql .= " AND status != 'Archived'";
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
        
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white rounded shadow-sm border border-gray-200 mb-3">
            <div class="flex items-center gap-3">
                <!-- Breadcrumb Navigation -->
                <div class="flex items-center gap-2">
                    <a href="buildings.php" 
                       class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded transition-colors"
                       title="Back to Buildings">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Buildings</span>
                    </a>
                    <a href="rooms.php?building_id=<?php echo $room['building_id']; ?>" 
                       class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded transition-colors"
                       title="Back to <?php echo htmlspecialchars($room['building_name'] ?? 'Building'); ?> Rooms">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span><?php echo htmlspecialchars($room['building_name'] ?? 'Building'); ?></span>
                    </a>
                </div>
                <div class="h-8 w-px bg-gray-300 mx-2"></div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        <?php echo htmlspecialchars($room['name']); ?> Assets
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Total: <?php echo $total_assets; ?> asset(s)</p>
                </div>
            </div>
            
            <button onclick="openAddAssetModal()" 
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fa-solid fa-plus"></i>
                <span>Add Asset</span>
            </button>
        </div>

        <!-- Search Bar -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 px-4 py-3">
            <form method="GET" action="" class="flex gap-3">
                <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                <div class="flex-1">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by asset tag, name, brand, or model..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-search mr-2"></i>Search
                </button>
                <?php if (!empty($search)): ?>
                <a href="?room_id=<?php echo $room_id; ?>" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fa-solid fa-times mr-2"></i>Clear
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 overflow-hidden">
            <div class="flex border-b border-gray-200">
                <button onclick="switchTab('pc-units')" id="tab-pc-units" 
                        class="flex-1 px-6 py-3 text-sm font-medium transition-all duration-200 border-b-2 border-[#1E3A8A] text-[#1E3A8A] bg-blue-50">
                    <i class="fa-solid fa-desktop mr-2"></i>PC Units
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-[#1E3A8A] text-white rounded-full"><?php echo $total_pc_units; ?></span>
                </button>
                <button onclick="switchTab('all-assets')" id="tab-all-assets" 
                        class="flex-1 px-6 py-3 text-sm font-medium transition-all duration-200 border-b-2 border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50">
                    <i class="fa-solid fa-boxes-stacked mr-2"></i>All Assets
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-gray-500 text-white rounded-full"><?php echo $total_assets; ?></span>
                </button>
            </div>
        </div>

        <!-- PC Units Section -->
        <div id="content-pc-units" class="tab-content">
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 overflow-hidden">
            <div class="px-4 py-3 bg-gradient-to-r from-blue-50 to-blue-100 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-desktop text-[#1E3A8A]"></i>
                    <h4 class="text-sm font-semibold text-gray-800">PC Units</h4>
                    <span class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-[#1E3A8A] rounded-full">
                        <?php echo $total_pc_units; ?> Total
                    </span>
                </div>
                <button onclick="openAddPCUnitModal()" class="px-3 py-1.5 text-sm font-medium text-white bg-[#1E3A8A] hover:bg-[#153570] rounded transition-colors">
                    <i class="fa-solid fa-plus mr-1"></i>Add PC Unit
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terminal</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PC Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Tag</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPU</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RAM</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Storage</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Health</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Online</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($pc_units as $pc): ?>
                            <tr class="hover:bg-blue-50 transition-colors cursor-pointer" onclick="window.location.href='pcassets.php?pc_unit_id=<?php echo $pc['id']; ?>'">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-blue-600"><?php echo htmlspecialchars($pc['terminal_number']); ?></span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($pc['pc_name']); ?></span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm text-gray-600"><?php echo htmlspecialchars($pc['asset_tag']); ?></span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-xs text-gray-600"><?php echo htmlspecialchars($pc['cpu']); ?></span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-xs text-gray-600"><?php echo htmlspecialchars($pc['ram']); ?></span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-xs text-gray-600"><?php echo htmlspecialchars($pc['storage']); ?></span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <?php
                                    $pc_status_colors = [
                                        'Active' => 'bg-green-100 text-green-700',
                                        'Inactive' => 'bg-gray-100 text-gray-700',
                                        'Under Maintenance' => 'bg-yellow-100 text-yellow-700',
                                        'Maintenance' => 'bg-yellow-100 text-yellow-700',
                                        'Archive' => 'bg-purple-100 text-purple-700',
                                        'Disposed' => 'bg-red-100 text-red-700'
                                    ];
                                    $pc_status_class = $pc_status_colors[$pc['status']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $pc_status_class; ?>">
                                        <?php echo htmlspecialchars($pc['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <?php
                                    $health_colors = [
                                        'Healthy' => 'bg-green-100 text-green-700 border-green-200',
                                        'Warning' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                        'Critical' => 'bg-red-100 text-red-700 border-red-200'
                                    ];
                                    $health_icons = [
                                        'Healthy' => 'fa-circle-check',
                                        'Warning' => 'fa-triangle-exclamation',
                                        'Critical' => 'fa-circle-xmark'
                                    ];
                                    $health_class = $health_colors[$pc['health_status']] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                                    $health_icon = $health_icons[$pc['health_status']] ?? 'fa-circle-question';
                                    ?>
                                    <span class="px-2 py-1 inline-flex items-center gap-1 text-xs leading-5 font-semibold rounded-full border <?php echo $health_class; ?>">
                                        <i class="fa-solid <?php echo $health_icon; ?>"></i>
                                        <?php echo htmlspecialchars($pc['health_status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-xs text-gray-500"><?php echo date('M d, H:i', strtotime($pc['last_online'])); ?></span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center" onclick="event.stopPropagation()">
                                    <div class="relative">
                                        <button onclick="togglePCMenu(<?php echo $pc['id']; ?>)" 
                                                class="p-2 hover:bg-gray-100 rounded-full transition-colors" 
                                                title="Actions">
                                            <i class="fa-solid fa-ellipsis-vertical text-gray-600"></i>
                                        </button>
                                        <div id="pc-menu-<?php echo $pc['id']; ?>" 
                                             class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                                            <div class="py-1">
                                                <a href="pcassets.php?pc_unit_id=<?php echo $pc['id']; ?>" 
                                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                    <i class="fa-solid fa-microchip text-[#1E3A8A]"></i> View Components
                                                </a>
                                                <button onclick="editPCUnit(<?php echo $pc['id']; ?>, '<?php echo htmlspecialchars($pc['terminal_number'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($pc['status'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($pc['notes'], ENT_QUOTES); ?>')" 
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                    <i class="fa-solid fa-edit text-blue-600"></i> Edit
                                                </button>
                                                <button onclick="archivePCUnit(<?php echo $pc['id']; ?>, '<?php echo htmlspecialchars($pc['terminal_number'], ENT_QUOTES); ?>')" 
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                    <i class="fa-solid fa-box-archive text-purple-600"></i> Archive
                                                </button>
                                                <button onclick="deletePCUnit(<?php echo $pc['id']; ?>)" 
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- PC Units Pagination -->
            <?php if ($total_pc_pages > 1): ?>
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Showing <?php echo count($pc_units); ?> of <?php echo $total_pc_units; ?> PC units
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if ($pc_page > 1): ?>
                            <a href="?room_id=<?php echo $room_id; ?>&pc_page=<?php echo $pc_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['page']) ? '&page=' . $_GET['page'] : ''; ?>" 
                               class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                                <i class="fa-solid fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $pc_page - 2);
                        $end_page = min($total_pc_pages, $pc_page + 2);
                        
                        if ($start_page > 1): ?>
                            <a href="?room_id=<?php echo $room_id; ?>&pc_page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['page']) ? '&page=' . $_GET['page'] : ''; ?>" 
                               class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">1</a>
                            <?php if ($start_page > 2): ?>
                                <span class="px-2 text-gray-500">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?room_id=<?php echo $room_id; ?>&pc_page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['page']) ? '&page=' . $_GET['page'] : ''; ?>" 
                           class="px-3 py-1 text-sm rounded <?php echo $i === $pc_page ? 'bg-[#1E3A8A] text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition-colors">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>                        <?php if ($end_page < $total_pc_pages): ?>
                            <?php if ($end_page < $total_pc_pages - 1): ?>
                                <span class="px-2 text-gray-500">...</span>
                            <?php endif; ?>
                            <a href="?room_id=<?php echo $room_id; ?>&pc_page=<?php echo $total_pc_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['page']) ? '&page=' . $_GET['page'] : ''; ?>" 
                               class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors"><?php echo $total_pc_pages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($pc_page < $total_pc_pages): ?>
                            <a href="?room_id=<?php echo $room_id; ?>&pc_page=<?php echo $pc_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['page']) ? '&page=' . $_GET['page'] : ''; ?>" 
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

        <!-- All Assets Section -->
        <div id="content-all-assets" class="tab-content hidden">
        <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
            <!-- Assets Table -->
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Tag</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand/Model</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To PC</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
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
                                    $status_colors = [
                                        'Active' => 'bg-green-100 text-green-700',
                                        'In Use' => 'bg-blue-100 text-blue-700',
                                        'Available' => 'bg-green-100 text-green-700',
                                        'Under Maintenance' => 'bg-yellow-100 text-yellow-700',
                                        'Retired' => 'bg-gray-100 text-gray-700',
                                        'Disposed' => 'bg-red-100 text-red-700',
                                        'Lost' => 'bg-red-100 text-red-700',
                                        'Damaged' => 'bg-orange-100 text-orange-700'
                                    ];
                                    $status_class = $status_colors[$asset['status']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($asset['status']); ?>
                                    </span>
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
                                                <?php if ($asset['status'] !== 'Archived'): ?>
                                                <button onclick="archiveAsset(<?php echo $asset['id']; ?>, '<?php echo htmlspecialchars($asset['asset_tag'], ENT_QUOTES); ?>')" 
                                                        class="w-full text-left px-4 py-2 text-sm text-orange-600 hover:bg-orange-50 flex items-center gap-2">
                                                    <i class="fa-solid fa-archive"></i> Archive
                                                </button>
                                                <?php endif; ?>
                                                <button onclick="deleteAsset(<?php echo $asset['id']; ?>, '<?php echo htmlspecialchars($asset['asset_tag'], ENT_QUOTES); ?>')" 
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </button>
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
                        <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['pc_page']) ? '&pc_page=' . $_GET['pc_page'] : ''; ?>" 
                           class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                            <i class="fa-solid fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="?room_id=<?php echo $room_id; ?>&page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['pc_page']) ? '&pc_page=' . $_GET['pc_page'] : ''; ?>" 
                           class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="px-2 text-gray-500">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['pc_page']) ? '&pc_page=' . $_GET['pc_page'] : ''; ?>" 
                           class="px-3 py-1 text-sm rounded <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition-colors">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="px-2 text-gray-500">...</span>
                        <?php endif; ?>
                        <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['pc_page']) ? '&pc_page=' . $_GET['pc_page'] : ''; ?>" 
                           class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo isset($_GET['pc_page']) ? '&pc_page=' . $_GET['pc_page'] : ''; ?>" 
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
                    <input type="text" id="assetTag" name="asset_tag"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="e.g., COMP-IK501-001">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Asset Name *</label>
                    <input type="text" id="assetName" name="asset_name"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="e.g., Desktop Computer">
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
                        <input type="text" id="bulkAssetName" name="bulk_asset_name" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., LAPTOP">
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
                               min="1" value="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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

<!-- Add PC Unit Modal -->
<div id="addPCUnitModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-[#1E3A8A] to-[#153570] px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Add New PC Unit(s)</h3>
        </div>
        <form id="pcUnitForm" class="p-6">
            <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
            
            <!-- Mode Selection -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Creation Mode</label>
                <div class="flex gap-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="bulk_mode" value="single" checked onchange="toggleBulkMode()" 
                               class="mr-2 text-[#1E3A8A] focus:ring-[#1E3A8A]">
                        <span class="text-sm">Single PC</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="bulk_mode" value="bulk" onchange="toggleBulkMode()" 
                               class="mr-2 text-[#1E3A8A] focus:ring-[#1E3A8A]">
                        <span class="text-sm">Bulk (Multiple PCs)</span>
                    </label>
                </div>
            </div>

            <!-- Single Mode Fields -->
            <div id="singleModeFields" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Terminal Number <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="terminal_number" id="singleTerminalNumber"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent"
                           placeholder="e.g., PC-001">
                </div>
            </div>

            <!-- Bulk Mode Fields -->
            <div id="bulkModeFields" class="space-y-4 hidden">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start gap-2">
                        <i class="fa-solid fa-info-circle text-[#1E3A8A] mt-1"></i>
                        <div class="text-sm text-blue-900">
                            <p class="font-medium mb-1">Bulk Creation</p>
                            <p>Create multiple PC units with sequential numbering. Example: PC-01 to PC-50</p>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Prefix <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="prefix" value="PC-"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent"
                           placeholder="e.g., PC-">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Start Number <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="range_start" value="1" min="1" max="999"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            End Number <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="range_end" value="50" min="1" max="999"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                    </div>
                </div>
                
                <div class="text-xs text-gray-500">
                    <i class="fa-solid fa-lightbulb mr-1"></i>
                    Maximum 100 units can be created at once. Numbers will be zero-padded (e.g., 01, 02, 03...)
                </div>
            </div>

            <!-- Common Fields -->
            <div class="space-y-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Archive">Archive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                    <textarea name="notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent"
                              placeholder="Additional information about this PC unit..."></textarea>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeAddPCUnitModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="submitPCUnit()"
                        class="px-4 py-2 bg-[#1E3A8A] text-white rounded-lg hover:bg-[#153570] transition-colors">
                    <i class="fa-solid fa-plus mr-2"></i><span id="submitButtonText">Add PC Unit</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit PC Unit Modal -->
<div id="editPCUnitModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-[#1E3A8A] to-[#153570] px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Edit PC Unit</h3>
        </div>
        <form id="editPCUnitForm" class="p-6">
            <input type="hidden" id="editPCUnitId" name="id">
            <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Terminal Number <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="editTerminalNumber" name="terminal_number" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="editPCStatus" name="status" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Archive">Archive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea id="editPCNotes" name="notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeEditPCUnitModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="submitEditPCUnit()"
                        class="px-4 py-2 bg-[#1E3A8A] text-white rounded-lg hover:bg-[#153570] transition-colors">
                    <i class="fa-solid fa-save mr-2"></i>Update PC Unit
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
</style>

<script>
// Modal functions
function openAddAssetModal() {
    document.getElementById('addAssetModal').classList.remove('hidden');
    document.getElementById('assetTag').focus();
}

function closeAddAssetModal() {
    document.getElementById('addAssetModal').classList.add('hidden');
    document.getElementById('addAssetForm').reset();
}

function closeEditAssetModal() {
    document.getElementById('editAssetModal').classList.add('hidden');
    document.getElementById('editAssetForm').reset();
}

// Add Asset Form Submit
document.getElementById('addAssetForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const bulkMode = document.querySelector('input[name="asset_bulk_mode"]:checked').value;
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
        updateAssetTagPreview();
    } else {
        singleFields.classList.remove('hidden');
        bulkFields.classList.add('hidden');
        submitBtn.textContent = 'Create Asset';
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
    document.getElementById('editAssetModal').classList.remove('hidden');
    document.getElementById('editAssetTag').focus();
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
    formData.append('status', document.getElementById('editStatus').value);
    formData.append('condition', document.getElementById('editCondition').value);
    
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

// Delete Asset
async function deleteAsset(id, assetTag) {
    closeAllMenus();
    
    if (!confirm(`Are you sure you want to delete asset "${assetTag}"?`)) return;
    
    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    formData.append('action', 'delete_asset');
    formData.append('id', id);
    
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
        showAlert('error', 'An error occurred while deleting the asset');
    }
}

// Archive Asset
async function archiveAsset(id, assetTag) {
    closeAllMenus();
    
    if (!confirm(`Are you sure you want to archive asset "${assetTag}"?`)) return;
    
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
        closeAddPCUnitModal();
        closeEditPCUnitModal();
    }
});

// PC Unit Management Functions
function openAddPCUnitModal() {
    document.getElementById('addPCUnitModal').classList.remove('hidden');
    document.getElementById('pcUnitForm').reset();
    toggleBulkMode(); // Reset to single mode
}

function closeAddPCUnitModal() {
    document.getElementById('addPCUnitModal').classList.add('hidden');
}

function toggleBulkMode() {
    const bulkMode = document.querySelector('input[name="bulk_mode"]:checked').value;
    const singleFields = document.getElementById('singleModeFields');
    const bulkFields = document.getElementById('bulkModeFields');
    const singleTerminal = document.getElementById('singleTerminalNumber');
    const submitButton = document.getElementById('submitButtonText');
    
    if (bulkMode === 'bulk') {
        singleFields.classList.add('hidden');
        bulkFields.classList.remove('hidden');
        singleTerminal.removeAttribute('required');
        submitButton.textContent = 'Create Multiple PCs';
    } else {
        singleFields.classList.remove('hidden');
        bulkFields.classList.add('hidden');
        singleTerminal.setAttribute('required', 'required');
        submitButton.textContent = 'Add PC Unit';
    }
}

function submitPCUnit() {
    const form = document.getElementById('pcUnitForm');
    const bulkMode = document.querySelector('input[name="bulk_mode"]:checked').value;
    
    // Validation
    if (bulkMode === 'single') {
        const terminalNumber = document.getElementById('singleTerminalNumber').value.trim();
        if (!terminalNumber) {
            showNotification('error', 'Terminal number is required');
            return;
        }
    } else {
        const prefix = form.querySelector('input[name="prefix"]').value.trim();
        const rangeStart = parseInt(form.querySelector('input[name="range_start"]').value);
        const rangeEnd = parseInt(form.querySelector('input[name="range_end"]').value);
        
        if (!prefix) {
            showNotification('error', 'Prefix is required for bulk creation');
            return;
        }
        
        if (rangeStart < 1 || rangeEnd < rangeStart) {
            showNotification('error', 'Invalid range. End number must be greater than start number');
            return;
        }
        
        if (rangeEnd - rangeStart > 100) {
            showNotification('error', 'Maximum 100 units can be created at once');
            return;
        }
        
        // Confirm bulk creation
        const count = rangeEnd - rangeStart + 1;
        if (!confirm(`Are you sure you want to create ${count} PC units?\n\nRange: ${prefix}${String(rangeStart).padStart(2, '0')} to ${prefix}${String(rangeEnd).padStart(2, '0')}`)) {
            return;
        }
    }
    
    const formData = new FormData(form);
    formData.append('ajax', '1');
    formData.append('action', 'create_pc_unit');
    
    // Disable submit button
    const submitBtn = event.target;
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Creating...';
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (bulkMode === 'bulk' && data.created) {
                showNotification('success', `Successfully created ${data.created} PC units!`);
            } else {
                showNotification('success', data.message);
            }
            closeAddPCUnitModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('error', data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'An error occurred while creating the PC unit(s)');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function editPCUnit(id, terminalNumber, status, notes) {
    document.getElementById('editPCUnitId').value = id;
    document.getElementById('editTerminalNumber').value = terminalNumber;
    document.getElementById('editPCStatus').value = status;
    document.getElementById('editPCNotes').value = notes || '';
    document.getElementById('editPCUnitModal').classList.remove('hidden');
}

function closeEditPCUnitModal() {
    document.getElementById('editPCUnitModal').classList.add('hidden');
}

function submitEditPCUnit() {
    const form = document.getElementById('editPCUnitForm');
    const formData = new FormData(form);
    formData.append('ajax', '1');
    formData.append('action', 'update_pc_unit');
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            closeEditPCUnitModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'An error occurred while updating the PC unit');
    });
}

function deletePCUnit(id) {
    if (!confirm('Are you sure you want to delete this PC unit? All assigned components will be unassigned.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'delete_pc_unit');
    formData.append('id', id);
    
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
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'An error occurred while deleting the PC unit');
    });
}

// Tab Switching Function
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active state from all tabs
    document.querySelectorAll('[id^="tab-"]').forEach(tab => {
        tab.classList.remove('border-[#1E3A8A]', 'text-[#1E3A8A]', 'bg-blue-50');
        tab.classList.add('border-transparent', 'text-gray-600');
        // Update badge colors
        const badge = tab.querySelector('span');
        if (badge) {
            badge.classList.remove('bg-[#1E3A8A]', 'text-white');
            badge.classList.add('bg-gray-500', 'text-white');
        }
    });
    
    // Show selected tab content
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    // Add active state to selected tab
    const activeTab = document.getElementById('tab-' + tabName);
    activeTab.classList.add('border-[#1E3A8A]', 'text-[#1E3A8A]', 'bg-blue-50');
    activeTab.classList.remove('border-transparent', 'text-gray-600');
    
    // Update badge color for active tab
    const activeBadge = activeTab.querySelector('span');
    if (activeBadge) {
        activeBadge.classList.add('bg-[#1E3A8A]', 'text-white');
        activeBadge.classList.remove('bg-gray-500');
    }
}

// PC Unit Kebab Menu Functions
function togglePCMenu(id) {
    event.stopPropagation();
    const menu = document.getElementById('pc-menu-' + id);
    const allMenus = document.querySelectorAll('[id^="pc-menu-"]');
    
    // Close all other menus
    allMenus.forEach(m => {
        if (m.id !== 'pc-menu-' + id) {
            m.classList.add('hidden');
        }
    });
    
    // Toggle current menu
    menu.classList.toggle('hidden');
}

function archivePCUnit(id, terminalNumber) {
    event.stopPropagation();
    
    if (!confirm(`Are you sure you want to archive PC Unit "${terminalNumber}"?\n\nArchived units can be restored later.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'archive_pc_unit');
    formData.append('id', id);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', 'PC Unit archived successfully');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('error', data.message || 'Failed to archive PC unit');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'An error occurred while archiving the PC unit');
    });
}

// Close PC menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('[id^="pc-menu-"]') && !e.target.closest('button')) {
        document.querySelectorAll('[id^="pc-menu-"]').forEach(menu => {
            menu.classList.add('hidden');
        });
    }
});

</script>

<?php include '../components/layout_footer.php'; ?>
