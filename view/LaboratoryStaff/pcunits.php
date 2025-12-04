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
$pc_search = isset($_GET['pc_search']) ? trim($_GET['pc_search']) : '';
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] === '1';
$pc_page = isset($_GET['pc_page']) ? max(1, intval($_GET['pc_page'])) : 1;
$pc_limit_param = isset($_GET['pc_limit']) ? $_GET['pc_limit'] : '10';
$pc_limit = ($pc_limit_param === 'all') ? PHP_INT_MAX : intval($pc_limit_param);
$pc_offset = ($pc_page - 1) * $pc_limit;

// Count total PC units
$pc_count_query = "SELECT COUNT(*) as total FROM pc_units WHERE room_id = ?";
$pc_params = [$room_id];
$pc_types = 'i';

if (!$show_archived) {
    $pc_count_query .= " AND status != 'Archive'";
}

if (!empty($pc_search)) {
    $pc_count_query .= " AND terminal_number LIKE ?";
    $pc_search_param = "%$pc_search%";
    $pc_params[] = $pc_search_param;
    $pc_types .= 's';
}

$pc_count_stmt = $conn->prepare($pc_count_query);
$pc_count_stmt->bind_param($pc_types, ...$pc_params);
$pc_count_stmt->execute();
$pc_count_result = $pc_count_stmt->get_result();
$total_pc_units = $pc_count_result->fetch_assoc()['total'];
$pc_count_stmt->close();

$total_pc_pages = ceil($total_pc_units / $pc_limit);

// Fetch PC units with pagination
$pc_units = [];
$pc_query_sql = "SELECT * FROM pc_units WHERE room_id = ?";
$pc_params = [$room_id];
$pc_types = 'i';

if (!$show_archived) {
    $pc_query_sql .= " AND status != 'Archive'";
}

if (!empty($pc_search)) {
    $pc_query_sql .= " AND terminal_number LIKE ?";
    $pc_search_param = "%$pc_search%";
    $pc_params = array_merge($pc_params, [$pc_search_param]);
    $pc_types .= 's';
}

$pc_query_sql .= " ORDER BY terminal_number ASC LIMIT ? OFFSET ?";
$pc_params[] = $pc_limit;
$pc_params[] = $pc_offset;
$pc_types .= 'ii';

$pc_query = $conn->prepare($pc_query_sql);
$pc_query->bind_param($pc_types, ...$pc_params);
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
        'condition' => 'Good',
        'notes' => $notes
    ];
}
$pc_query->close();

// Handle AJAX requests for PC units
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
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
                
                // Log activity
                if ($created_count > 0) {
                    require_once '../../model/ActivityLog.php';
                    ActivityLog::record(
                        $_SESSION['user_id'],
                        'create',
                        'pc_unit',
                        null,
                        'Bulk created ' . $created_count . ' PC unit(s) with prefix ' . $prefix
                    );
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
                    // Log activity
                    require_once '../../model/ActivityLog.php';
                    ActivityLog::record(
                        $_SESSION['user_id'],
                        'create',
                        'pc_unit',
                        $new_id,
                        'Created PC unit: ' . $terminal_number
                    );
                    
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
                // Log activity
                require_once '../../model/ActivityLog.php';
                ActivityLog::record(
                    $_SESSION['user_id'],
                    'update',
                    'pc_unit',
                    $id,
                    'Updated PC unit: ' . $terminal_number
                );
                
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
            // Get terminal number before archiving
            $get_stmt = $conn->prepare("SELECT terminal_number FROM pc_units WHERE id = ? AND room_id = ?");
            $get_stmt->bind_param('ii', $id, $room_id);
            $get_stmt->execute();
            $result = $get_stmt->get_result();
            $pc_data = $result->fetch_assoc();
            $get_stmt->close();
            
            $stmt = $conn->prepare("UPDATE pc_units SET status = 'Archive' WHERE id = ? AND room_id = ?");
            $stmt->bind_param('ii', $id, $room_id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                // Log activity
                require_once '../../model/ActivityLog.php';
                ActivityLog::record(
                    $_SESSION['user_id'],
                    'archive',
                    'pc_unit',
                    $id,
                    'Archived PC unit: ' . ($pc_data['terminal_number'] ?? 'ID ' . $id)
                );
                
                echo json_encode(['success' => true, 'message' => 'PC Unit archived successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to archive PC unit']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'restore_pc_unit') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid PC unit ID']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE pc_units SET status = 'Active' WHERE id = ? AND room_id = ?");
            $stmt->bind_param('ii', $id, $room_id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'PC Unit restored successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to restore PC unit']);
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
    
    if ($action === 'bulk_archive_pc_units') {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        
        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['success' => false, 'message' => 'Invalid PC unit IDs']);
            exit;
        }
        
        try {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $conn->prepare("UPDATE pc_units SET status = 'Archive' WHERE id IN ($placeholders) AND room_id = ?");
            $types = str_repeat('i', count($ids)) . 'i';
            $params = array_merge($ids, [$room_id]);
            $stmt->bind_param($types, ...$params);
            $success = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => "$affected PC unit(s) archived successfully"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to archive PC units']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'bulk_restore_pc_units') {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        
        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['success' => false, 'message' => 'Invalid PC unit IDs']);
            exit;
        }
        
        try {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $conn->prepare("UPDATE pc_units SET status = 'Active' WHERE id IN ($placeholders) AND room_id = ?");
            $types = str_repeat('i', count($ids)) . 'i';
            $params = array_merge($ids, [$room_id]);
            $stmt->bind_param($types, ...$params);
            $success = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => "$affected PC unit(s) restored successfully"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to restore PC units']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'bulk_edit_pc_units') {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        $status = trim($_POST['status'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['success' => false, 'message' => 'Invalid PC unit IDs']);
            exit;
        }
        
        if (empty($status) && empty($notes)) {
            echo json_encode(['success' => false, 'message' => 'Please provide at least one field to update']);
            exit;
        }
        
        try {
            $updates = [];
            $params = [];
            $types = '';
            
            if (!empty($status)) {
                $updates[] = 'status = ?';
                $params[] = $status;
                $types .= 's';
            }
            
            if (!empty($notes)) {
                $updates[] = 'notes = ?';
                $params[] = $notes;
                $types .= 's';
            }
            
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $sql = "UPDATE pc_units SET " . implode(', ', $updates) . " WHERE id IN ($placeholders) AND room_id = ?";
            
            $params = array_merge($params, $ids, [$room_id]);
            $types .= str_repeat('i', count($ids)) . 'i';
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $success = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            if ($success) {
                // Log activity
                require_once '../../model/ActivityLog.php';
                ActivityLog::record(
                    $_SESSION['user_id'],
                    'update',
                    'pc_unit',
                    null,
                    'Bulk updated ' . $affected . ' PC unit(s)'
                );
                
                echo json_encode(['success' => true, 'message' => "$affected PC unit(s) updated successfully"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update PC units']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Count archived PC units and assets for tabs
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

// Count total assets for All Assets tab
$assets_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM assets WHERE room_id = ? AND (status != 'Archive' AND status != 'Archived')");
$assets_count_stmt->bind_param('i', $room_id);
$assets_count_stmt->execute();
$assets_result = $assets_count_stmt->get_result();
$total_assets = $assets_result->fetch_assoc()['total'];
$assets_count_stmt->close();

include '../components/layout_header.php';
?>

<style>
html, body {
    height: 100vh;
    overflow: auto;
}
#app-container {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
main {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 0.5rem;
    background-color: #f9fafb;
}
</style>

<main>
    <div class="flex-1 flex flex-col">
        
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
                <button class="px-4 py-3 text-sm font-medium transition-all duration-200 border-b-2 border-[#1E3A8A] text-[#1E3A8A] bg-blue-50 flex items-center justify-center cursor-default">
                    <i class="fa-solid fa-desktop mr-2"></i>PC Units
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-[#1E3A8A] text-white rounded-full"><?php echo $total_pc_units; ?></span>
                </button>
                <a href="roomassets.php?room_id=<?php echo $room_id; ?>" class="px-4 py-3 text-sm font-medium transition-all duration-200 border-b-2 border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 flex items-center justify-center">
                    <i class="fa-solid fa-boxes-stacked mr-2"></i>All Assets
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-gray-500 text-white rounded-full"><?php echo $total_assets; ?></span>
                </a>
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

        <!-- PC Units Section -->
        <div id="content-pc-units">
        <!-- PC Units Search Bar -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 px-4 py-3">
            <div class="flex gap-3">
                <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 whitespace-nowrap">Show:</label>
                    <select id="pcLimitSelect" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="10" <?php echo $pc_limit_param === '10' ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $pc_limit_param === '25' ? 'selected' : ''; ?>>25</option>
                        <option value="100" <?php echo $pc_limit_param === '100' ? 'selected' : ''; ?>>100</option>
                        <option value="all" <?php echo $pc_limit_param === 'all' ? 'selected' : ''; ?>>All</option>
                    </select>
                </div>
                <div class="flex-1">
                    <input type="text" id="pcSearchInput" value="<?php echo htmlspecialchars($pc_search); ?>" 
                           placeholder="Search by terminal number..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <?php if (!empty($pc_search)): ?>
                <a href="?room_id=<?php echo $room_id; ?>" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fa-solid fa-times mr-2"></i>Clear
                </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 overflow-hidden">
            <div class="px-4 py-3 bg-gradient-to-r from-blue-50 to-blue-100 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-desktop text-[#1E3A8A]"></i>
                    <h4 class="text-sm font-semibold text-gray-800">PC Units</h4>
                    <span class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-[#1E3A8A] rounded-full">
                        <?php echo $total_pc_units; ?> Total
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <div id="pc-bulk-actions" class="hidden flex items-center gap-2">
                        <button onclick="openBulkEditModal()" class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded transition-colors">
                            <i class="fa-solid fa-edit mr-1"></i>Edit Selected
                        </button>
                        <button onclick="bulkArchivePCUnits()" class="px-3 py-1.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded transition-colors">
                            <i class="fa-solid fa-archive mr-1"></i>Archive Selected
                        </button>
                    </div>
                    <a href="addpc.php?room_id=<?php echo $room_id; ?>" class="px-3 py-1.5 text-sm font-medium text-white bg-[#1E3A8A] hover:bg-[#153570] rounded transition-colors">
                        <i class="fa-solid fa-plus mr-1"></i>Add PC Unit
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                <input type="checkbox" id="select-all-pc" class="rounded border-gray-300 text-[#1E3A8A] focus:ring-[#1E3A8A]">
                            </th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terminal</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PC Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Tag</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Online</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($pc_units)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fa-solid fa-desktop text-5xl mb-3 opacity-30"></i>
                                    <p class="text-lg">No PC units found</p>
                                    <?php if (!empty($pc_search)): ?>
                                        <p class="text-sm">Try adjusting your search</p>
                                    <?php else: ?>
                                        <p class="text-sm">Click "Add PC Unit" to create one</p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                        <?php foreach ($pc_units as $pc): ?>
                            <tr class="hover:bg-blue-50 transition-colors cursor-pointer" onclick="window.location.href='pcassets.php?pc_unit_id=<?php echo $pc['id']; ?>'">
                                <td class="px-4 py-3 whitespace-nowrap text-center" onclick="event.stopPropagation()">
                                    <input type="checkbox" class="pc-checkbox rounded border-gray-300 text-[#1E3A8A] focus:ring-[#1E3A8A]" value="<?php echo $pc['id']; ?>">
                                </td>
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
                                    <?php
                                    $status_colors = [
                                        'Active' => 'bg-green-100 text-green-700',
                                        'Inactive' => 'bg-gray-100 text-gray-700',
                                        'Maintenance' => 'bg-yellow-100 text-yellow-700',
                                        'Archive' => 'bg-red-100 text-red-700'
                                    ];
                                    $status_class = $status_colors[$pc['status']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($pc['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <?php
                                    $condition_colors = [
                                        'Excellent' => 'bg-green-100 text-green-700',
                                        'Good' => 'bg-blue-100 text-blue-700',
                                        'Fair' => 'bg-yellow-100 text-yellow-700',
                                        'Poor' => 'bg-orange-100 text-orange-700',
                                        'Non-Functional' => 'bg-red-100 text-red-700'
                                    ];
                                    $condition_class = $condition_colors[$pc['condition']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $condition_class; ?>">
                                        <?php echo htmlspecialchars($pc['condition']); ?>
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
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                    <i class="fa-solid fa-box-archive text-red-600"></i> Archive
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
            
            <!-- PC Units Pagination -->
            <?php if ($total_pc_pages > 1): ?>
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Showing <?php echo count($pc_units); ?> of <?php echo $total_pc_units; ?> PC units
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if ($pc_page > 1): ?>
                            <a href="?room_id=<?php echo $room_id; ?>&pc_page=<?php echo $pc_page - 1; ?><?php echo !empty($pc_search) ? '&pc_search=' . urlencode($pc_search) : ''; ?>" 
                               class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                                <i class="fa-solid fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $pc_page - 2);
                        $end_page = min($total_pc_pages, $pc_page + 2);
                        
                        if ($start_page > 1): ?>
                            <a href="?room_id=<?php echo $room_id; ?>&pc_page=1<?php echo !empty($pc_search) ? '&pc_search=' . urlencode($pc_search) : ''; ?>" 
                               class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">1</a>
                            <?php if ($start_page > 2): ?>
                                <span class="px-2 text-gray-500">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?room_id=<?php echo $room_id; ?>&pc_page=<?php echo $i; ?><?php echo !empty($pc_search) ? '&pc_search=' . urlencode($pc_search) : ''; ?>" 
                               class="px-3 py-1 text-sm rounded <?php echo $i === $pc_page ? 'bg-[#1E3A8A] text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition-colors">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pc_pages): ?>
                            <?php if ($end_page < $total_pc_pages - 1): ?>
                                <span class="px-2 text-gray-500">...</span>
                            <?php endif; ?>
                            <a href="?room_id=<?php echo $room_id; ?>&pc_page=<?php echo $total_pc_pages; ?><?php echo !empty($pc_search) ? '&pc_search=' . urlencode($pc_search) : ''; ?>" 
                               class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors"><?php echo $total_pc_pages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($pc_page < $total_pc_pages): ?>
                            <a href="?room_id=<?php echo $room_id; ?>&pc_page=<?php echo $pc_page + 1; ?><?php echo !empty($pc_search) ? '&pc_search=' . urlencode($pc_search) : ''; ?>" 
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

    </div>
</main>

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

<!-- Bulk Edit Modal -->
<div id="bulkEditModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Bulk Edit PC Units</h3>
        </div>
        <form id="bulkEditForm" class="p-6">
            <div class="mb-4">
                <div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                    <p class="text-sm text-gray-700">
                        <i class="fa-solid fa-info-circle text-blue-600 mr-1"></i>
                        Editing <strong id="bulkEditCount">0</strong> PC Unit(s)
                    </p>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Status <span class="text-gray-500 text-xs">(Leave unchanged if empty)</span>
                    </label>
                    <select id="bulkEditStatus" name="status" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- No Change --</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Notes <span class="text-gray-500 text-xs">(Leave empty to keep existing notes)</span>
                    </label>
                    <textarea id="bulkEditNotes" name="notes" rows="4"
                              placeholder="Enter notes to update all selected PC units..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeBulkEditModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="submitBulkEdit()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-save mr-2"></i>Update PC Units
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
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

function editPCUnit(id, terminalNumber, status, notes) {
    closeAllMenus();
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
    
    const content = `
        <p class="text-gray-700 mb-2">Are you sure you want to archive the following PC Unit?</p>
        <div class="bg-gray-50 p-3 rounded-lg border">
            <strong>Terminal Number:</strong> ${terminalNumber}
        </div>
    `;
    
    openArchiveModal(content, () => {
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

// Close all menus
function closeAllMenus() {
    const allMenus = document.querySelectorAll('[id^="pc-menu-"]');
    allMenus.forEach(m => m.classList.add('hidden'));
}

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditPCUnitModal();
        closeArchiveModal();
        closeBulkEditModal();
    }
});

// Initialize select all functionality
function initializeSelectAll() {
    // PC Units select all
    const selectAllPC = document.getElementById('select-all-pc');
    if (selectAllPC) {
        selectAllPC.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.pc-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            togglePCBulkActions();
        });
        
        // Individual PC checkboxes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('pc-checkbox')) {
                updateSelectAllPC();
                togglePCBulkActions();
            }
        });
    }
}

function updateSelectAllPC() {
    const selectAll = document.getElementById('select-all-pc');
    const checkboxes = document.querySelectorAll('.pc-checkbox');
    const checkedBoxes = document.querySelectorAll('.pc-checkbox:checked');
    selectAll.checked = checkboxes.length > 0 && checkedBoxes.length === checkboxes.length;
    selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
}

function togglePCBulkActions() {
    const bulkActions = document.getElementById('pc-bulk-actions');
    const checkedBoxes = document.querySelectorAll('.pc-checkbox:checked');
    if (checkedBoxes.length > 0) {
        bulkActions.classList.remove('hidden');
    } else {
        bulkActions.classList.add('hidden');
    }
}

// Bulk actions for PC Units
function bulkArchivePCUnits() {
    const selectedIds = Array.from(document.querySelectorAll('.pc-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) return;
    
    const terminalNumbers = selectedIds.map(id => {
        const row = document.querySelector(`.pc-checkbox[value="${id}"]`).closest('tr');
        return row.querySelector('td:nth-child(2) span').textContent.trim();
    });
    
    const content = `
        <p class="text-gray-700 mb-2">Are you sure you want to archive the following ${selectedIds.length} PC Unit(s)?</p>
        <div class="bg-gray-50 p-3 rounded-lg border max-h-32 overflow-y-auto">
            <strong>Terminal Numbers:</strong><br>
            ${terminalNumbers.map(num => `<span class="inline-block bg-white px-2 py-1 rounded border text-sm mr-1 mb-1">${num}</span>`).join('')}
        </div>
    `;
    
    openArchiveModal(content, () => {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'bulk_archive_pc_units');
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
                showNotification('error', data.message || 'Failed to archive PC units');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'An error occurred while archiving PC units');
        });
    });
}

// Automatic search with debouncing for PC Units
let pcSearchTimeout;
const pcSearchInput = document.getElementById('pcSearchInput');

if (pcSearchInput) {
    pcSearchInput.addEventListener('input', function() {
        clearTimeout(pcSearchTimeout);
        const query = this.value.trim();
        
        pcSearchTimeout = setTimeout(() => {
            // Update URL without page reload
            const url = new URL(window.location);
            if (query) {
                url.searchParams.set('pc_search', query);
            } else {
                url.searchParams.delete('pc_search');
            }
            // Reset to page 1 when searching
            url.searchParams.delete('pc_page');
            
            window.location.href = url.toString();
        }, 1000); // 1000ms debounce
    });
}

// Handle limit select change
const pcLimitSelect = document.getElementById('pcLimitSelect');
if (pcLimitSelect) {
    pcLimitSelect.addEventListener('change', function() {
        const url = new URL(window.location);
        url.searchParams.set('pc_limit', this.value);
        url.searchParams.delete('pc_page'); // Reset to page 1
        window.location.href = url.toString();
    });
}

// Bulk edit functions
function openBulkEditModal() {
    const selectedIds = Array.from(document.querySelectorAll('.pc-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) return;
    
    document.getElementById('bulkEditCount').textContent = selectedIds.length;
    document.getElementById('bulkEditStatus').value = '';
    document.getElementById('bulkEditNotes').value = '';
    document.getElementById('bulkEditModal').classList.remove('hidden');
}

function closeBulkEditModal() {
    document.getElementById('bulkEditModal').classList.add('hidden');
}

function submitBulkEdit() {
    const selectedIds = Array.from(document.querySelectorAll('.pc-checkbox:checked')).map(cb => cb.value);
    const status = document.getElementById('bulkEditStatus').value;
    const notes = document.getElementById('bulkEditNotes').value.trim();
    
    if (selectedIds.length === 0) {
        showNotification('error', 'No PC units selected');
        return;
    }
    
    if (!status && !notes) {
        showNotification('error', 'Please provide at least one field to update');
        return;
    }
    
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'bulk_edit_pc_units');
    formData.append('ids', JSON.stringify(selectedIds));
    if (status) formData.append('status', status);
    if (notes) formData.append('notes', notes);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            closeBulkEditModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'An error occurred while updating PC units');
    });
}

// Notification function
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white font-medium z-50 ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeSelectAll();
});
</script>

<?php include '../components/layout_footer.php'; ?>
