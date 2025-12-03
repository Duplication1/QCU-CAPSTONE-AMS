<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has Technician role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Technician') {
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

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_condition') {
        $id = intval($_POST['id'] ?? 0);
        $condition = trim($_POST['condition'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid asset ID']);
            exit();
        }
        
        if (empty($condition)) {
            echo json_encode(['success' => false, 'message' => 'Condition is required']);
            exit();
        }
        
        try {
            // Get current condition before update
            $old_condition_stmt = $conn->prepare("SELECT asset_tag, asset_name, `condition` FROM assets WHERE id = ?");
            $old_condition_stmt->bind_param('i', $id);
            $old_condition_stmt->execute();
            $old_data = $old_condition_stmt->get_result()->fetch_assoc();
            $old_condition_stmt->close();
            
            $old_condition = $old_data['condition'] ?? 'Unknown';
            $asset_tag = $old_data['asset_tag'] ?? 'Unknown';
            $asset_name = $old_data['asset_name'] ?? 'Unknown';
            
            // Update asset condition
            $stmt = $conn->prepare("UPDATE assets SET `condition` = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
            $updated_by = $_SESSION['user_id'];
            $stmt->bind_param('sii', $condition, $updated_by, $id);
            $success = $stmt->execute();
            $stmt->close();
            
            // Log the condition change in activity logs
            if ($success) {
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?, 'update_condition', ?, NOW())");
                
                if (!empty($notes)) {
                    $description = "Updated asset condition: {$asset_tag} ({$asset_name}) from '{$old_condition}' to '{$condition}'. Notes: {$notes}";
                } else {
                    $description = "Updated asset condition: {$asset_tag} ({$asset_name}) from '{$old_condition}' to '{$condition}'";
                }
                
                $log_stmt->bind_param('is', $_SESSION['user_id'], $description);
                $log_stmt->execute();
                $log_stmt->close();
            }
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Asset condition updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update asset condition']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit();
    }
    
    // Bulk update condition
    if ($action === 'bulk_update_condition') {
        $ids = $_POST['ids'] ?? [];
        $condition = trim($_POST['condition'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['success' => false, 'message' => 'No assets selected']);
            exit();
        }
        
        if (empty($condition)) {
            echo json_encode(['success' => false, 'message' => 'Condition is required']);
            exit();
        }
        
        try {
            $successCount = 0;
            $errorCount = 0;
            $updated_assets = [];
            
            foreach ($ids as $id) {
                $id = intval($id);
                if ($id <= 0) continue;
                
                // Get current condition before update
                $old_condition_stmt = $conn->prepare("SELECT asset_tag, asset_name, `condition` FROM assets WHERE id = ?");
                $old_condition_stmt->bind_param('i', $id);
                $old_condition_stmt->execute();
                $old_data = $old_condition_stmt->get_result()->fetch_assoc();
                $old_condition_stmt->close();
                
                if (!$old_data) {
                    $errorCount++;
                    continue;
                }
                
                $old_condition = $old_data['condition'] ?? 'Unknown';
                $asset_tag = $old_data['asset_tag'] ?? 'Unknown';
                $asset_name = $old_data['asset_name'] ?? 'Unknown';
                
                // Update asset condition
                $stmt = $conn->prepare("UPDATE assets SET `condition` = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
                $updated_by = $_SESSION['user_id'];
                $stmt->bind_param('sii', $condition, $updated_by, $id);
                $success = $stmt->execute();
                $stmt->close();
                
                if ($success) {
                    $successCount++;
                    $updated_assets[] = "{$asset_tag} ({$asset_name})";
                } else {
                    $errorCount++;
                }
            }
            
            // Log bulk update in activity logs
            if ($successCount > 0) {
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?, 'bulk_update_condition', ?, NOW())");
                
                $asset_list = implode(', ', array_slice($updated_assets, 0, 5));
                if (count($updated_assets) > 5) {
                    $asset_list .= ' and ' . (count($updated_assets) - 5) . ' more';
                }
                
                if (!empty($notes)) {
                    $description = "Bulk updated {$successCount} asset(s) condition to '{$condition}': {$asset_list}. Notes: {$notes}";
                } else {
                    $description = "Bulk updated {$successCount} asset(s) condition to '{$condition}': {$asset_list}";
                }
                
                $log_stmt->bind_param('is', $_SESSION['user_id'], $description);
                $log_stmt->execute();
                $log_stmt->close();
            }
            
            if ($successCount > 0 && $errorCount === 0) {
                echo json_encode(['success' => true, 'message' => "Successfully updated {$successCount} asset(s)"]);
            } elseif ($successCount > 0 && $errorCount > 0) {
                echo json_encode(['success' => true, 'message' => "Updated {$successCount} asset(s), {$errorCount} failed"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update assets']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit();
    }
}

// Fetch assets with search, filter, and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
$filter_type = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : '';
$filter_condition = isset($_GET['filter_condition']) ? trim($_GET['filter_condition']) : '';
$filter_building = isset($_GET['filter_building']) ? trim($_GET['filter_building']) : '';
$filter_room = isset($_GET['filter_room']) ? trim($_GET['filter_room']) : '';
$show_standby = isset($_GET['show_standby']) && $_GET['show_standby'] === '1';
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] === '1';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 12;
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

// Count total assets
$count_query = "SELECT COUNT(*) as total FROM assets WHERE 1=1";
$params = [];
$types = '';

if (!$show_archived) {
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

if (!empty($filter_condition)) {
    $count_query .= " AND `condition` = ?";
    $params[] = $filter_condition;
    $types .= 's';
}

if ($show_standby) {
    $count_query .= " AND (room_id IS NULL OR room_id = 0)";
}

if (!empty($filter_building)) {
    $count_query .= " AND room_id IN (SELECT id FROM rooms WHERE building_id = ?)";
    $params[] = intval($filter_building);
    $types .= 'i';
}

if (!empty($filter_room)) {
    $count_query .= " AND room_id = ?";
    $params[] = intval($filter_room);
    $types .= 'i';
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

// Fetch assets
$assets = [];
$query_sql = "SELECT a.*, r.name as room_name, b.name as building_name FROM assets a 
              LEFT JOIN rooms r ON a.room_id = r.id 
              LEFT JOIN buildings b ON r.building_id = b.id
              WHERE 1=1";
$params = [];
$types = '';

if (!$show_archived) {
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

if (!empty($filter_condition)) {
    $query_sql .= " AND a.`condition` = ?";
    $params[] = $filter_condition;
    $types .= 's';
}

if ($show_standby) {
    $query_sql .= " AND (a.room_id IS NULL OR a.room_id = 0)";
}

if (!empty($filter_building)) {
    $query_sql .= " AND a.room_id IN (SELECT id FROM rooms WHERE building_id = ?)";
    $params[] = intval($filter_building);
    $types .= 'i';
}

if (!empty($filter_room)) {
    $query_sql .= " AND a.room_id = ?";
    $params[] = intval($filter_room);
    $types .= 'i';
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
</style>

<main>
    <div class="flex-1 flex flex-col">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white rounded shadow-sm border border-gray-200 mb-3">
            <div>
                <h1 class="text-lg font-bold text-gray-800">Asset Registry</h1>
                <p class="text-xs text-gray-500 mt-0.5">View and update asset conditions</p>
            </div>
            
            <!-- Bulk Actions -->
            <div id="bulkActionsBar" class="hidden flex items-center gap-3">
                <span class="text-sm text-gray-600">
                    <span id="selectedCount">0</span> selected
                </span>
                <button onclick="openBulkUpdateModal()" class="px-4 py-2 bg-[#1E3A8A] text-white text-sm font-medium rounded hover:bg-[#153570] transition-colors">
                    <i class="fa-solid fa-wrench mr-2"></i>Update Condition
                </button>
                <button onclick="clearSelection()" class="px-3 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded hover:bg-gray-300 transition-colors">
                    Clear
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-3 mb-3">
            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-2">
                <div>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search assets..." 
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                </div>
                
                <div>
                    <select name="filter_type" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A]">
                        <option value="">All Types</option>
                        <option value="Hardware" <?php echo $filter_type === 'Hardware' ? 'selected' : ''; ?>>Hardware</option>
                        <option value="Software" <?php echo $filter_type === 'Software' ? 'selected' : ''; ?>>Software</option>
                        <option value="Peripheral" <?php echo $filter_type === 'Peripheral' ? 'selected' : ''; ?>>Peripheral</option>
                        <option value="Furniture" <?php echo $filter_type === 'Furniture' ? 'selected' : ''; ?>>Furniture</option>
                    </select>
                </div>
                
                <div>
                    <select name="filter_status" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A]">
                        <option value="">All Status</option>
                        <option value="Available" <?php echo $filter_status === 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="In Use" <?php echo $filter_status === 'In Use' ? 'selected' : ''; ?>>In Use</option>
                        <option value="Under Maintenance" <?php echo $filter_status === 'Under Maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                        <option value="Damaged" <?php echo $filter_status === 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
                        <option value="Retired" <?php echo $filter_status === 'Retired' ? 'selected' : ''; ?>>Retired</option>
                    </select>
                </div>

                <div>
                    <select name="filter_condition" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A]">
                        <option value="">All Conditions</option>
                        <option value="Excellent" <?php echo $filter_condition === 'Excellent' ? 'selected' : ''; ?>>Excellent</option>
                        <option value="Good" <?php echo $filter_condition === 'Good' ? 'selected' : ''; ?>>Good</option>
                        <option value="Fair" <?php echo $filter_condition === 'Fair' ? 'selected' : ''; ?>>Fair</option>
                        <option value="Poor" <?php echo $filter_condition === 'Poor' ? 'selected' : ''; ?>>Poor</option>
                        <option value="Damaged" <?php echo $filter_condition === 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
                    </select>
                </div>
                
                <div>
                    <select name="filter_building" id="buildingFilter" onchange="updateRoomFilter()" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A]">
                        <option value="">All Buildings</option>
                        <?php foreach ($buildings as $building): ?>
                            <option value="<?php echo $building['id']; ?>" <?php echo $filter_building == $building['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($building['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <select name="filter_room" id="roomFilter" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A]">
                        <option value="">All Rooms</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>" <?php echo $filter_room == $room['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($room['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-3 py-1.5 text-sm bg-[#1E3A8A] text-white rounded hover:bg-[#153570] transition-colors">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <a href="?" class="px-3 py-1.5 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">
                        <i class="fa-solid fa-times"></i>
                    </a>
                </div>
            </form>
            
            <div class="flex items-center gap-3 mt-2 text-xs">
                <label class="flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" name="show_standby" value="1" <?php echo $show_standby ? 'checked' : ''; ?> onchange="this.form.submit()" class="rounded">
                    <span class="text-gray-700">Standby Assets</span>
                </label>
                <label class="flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" name="show_archived" value="1" <?php echo $show_archived ? 'checked' : ''; ?> onchange="this.form.submit()" class="rounded">
                    <span class="text-gray-700">Archived Assets</span>
                </label>
                <div class="ml-auto flex items-center gap-2">
                    <label class="text-gray-700">Show:</label>
                    <select name="per_page" onchange="this.form.submit()" class="px-3 py-1 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                        <option value="10" <?php echo ($per_page == 10) ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo ($per_page == 25) ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo ($per_page == 50) ? 'selected' : ''; ?>>50</option>
                        <option value="0" <?php echo ($per_page == 999999) ? 'selected' : ''; ?>>All</option>
                    </select>
                    <span class="text-gray-700">entries</span>
                </div>
            </div>
        </div>

        <!-- Assets Table -->
        <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
            <?php if (empty($assets)): ?>
                <div class="flex flex-col items-center justify-center h-full text-gray-400 p-8">
                    <i class="fa-solid fa-inbox text-6xl mb-4 opacity-30"></i>
                    <p class="text-lg font-medium text-gray-500">No assets found</p>
                    <p class="text-sm text-gray-400 mt-1">Try adjusting your filters</p>
                </div>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-[#1E3A8A] text-white sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-2 text-center text-xs font-medium uppercase tracking-wider">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" class="rounded cursor-pointer" title="Select all">
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Asset Tag</th>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Asset Name</th>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Type</th>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Brand/Model</th>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Location</th>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Condition</th>
                            <th class="px-3 py-2 text-center text-xs font-medium uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($assets as $asset): ?>
                            <tr class="hover:bg-blue-50 transition-colors" data-asset-id="<?php echo $asset['id']; ?>">
                                <td class="px-3 py-2 text-center">
                                    <input type="checkbox" class="asset-checkbox rounded cursor-pointer" value="<?php echo $asset['id']; ?>" onchange="updateSelection()">
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
                                        $brand_model = array_filter([$asset['brand'] ?? '', $asset['model'] ?? '']);
                                        echo htmlspecialchars(implode(' - ', $brand_model) ?: 'N/A'); 
                                        ?>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-700">
                                    <div class="max-w-xs truncate">
                                        <?php echo htmlspecialchars($asset['building_name'] ?? 'Standby'); ?>
                                        <?php if (!empty($asset['room_name'])): ?>
                                            - <?php echo htmlspecialchars($asset['room_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs">
                                    <span class="<?php 
                                        $status_colors = [
                                            'Available' => 'bg-green-100 text-green-700',
                                            'In Use' => 'bg-blue-100 text-blue-700',
                                            'Under Maintenance' => 'bg-yellow-100 text-yellow-700',
                                            'Damaged' => 'bg-orange-100 text-orange-700',
                                            'Retired' => 'bg-gray-100 text-gray-700',
                                            'Archived' => 'bg-purple-100 text-purple-700'
                                        ];
                                        echo $status_colors[$asset['status']] ?? 'bg-gray-100 text-gray-700';
                                    ?> px-2 py-1 text-xs font-medium rounded">
                                        <?php echo htmlspecialchars($asset['status']); ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs">
                                    <span class="condition-badge <?php 
                                        $condition_colors = [
                                            'Excellent' => 'bg-green-100 text-green-700 border-green-300',
                                            'Good' => 'bg-blue-100 text-blue-700 border-blue-300',
                                            'Fair' => 'bg-yellow-100 text-yellow-700 border-yellow-300',
                                            'Poor' => 'bg-orange-100 text-orange-700 border-orange-300',
                                            'Damaged' => 'bg-red-100 text-red-700 border-red-300'
                                        ];
                                        echo $condition_colors[$asset['condition'] ?? 'Good'] ?? 'bg-gray-100 text-gray-700 border-gray-300';
                                    ?> px-2 py-1 text-xs font-medium rounded border">
                                        <?php echo htmlspecialchars($asset['condition'] ?? 'Good'); ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                                    <button onclick="openUpdateConditionModal(<?php echo htmlspecialchars(json_encode($asset)); ?>)" 
                                            class="px-3 py-1 text-xs font-medium text-white bg-[#1E3A8A] rounded hover:bg-[#153570] transition-colors">
                                        <i class="fa-solid fa-wrench"></i> Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="bg-white rounded shadow-sm border border-gray-200 mt-3 px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Showing <?php echo count($assets); ?> of <?php echo $total_assets; ?> assets (Page <?php echo $page; ?> of <?php echo $total_pages; ?>)
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_status) ? '&filter_status=' . urlencode($filter_status) : ''; ?><?php echo !empty($filter_type) ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo !empty($filter_condition) ? '&filter_condition=' . urlencode($filter_condition) : ''; ?><?php echo !empty($filter_building) ? '&filter_building=' . urlencode($filter_building) : ''; ?><?php echo !empty($filter_room) ? '&filter_room=' . urlencode($filter_room) : ''; ?><?php echo $show_standby ? '&show_standby=1' : ''; ?><?php echo $show_archived ? '&show_archived=1' : ''; ?>" 
                           class="px-3 py-1 text-sm rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                            <i class="fa-solid fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                        $active_class = ($i == $page) ? 'bg-[#1E3A8A] text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300';
                    ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_status) ? '&filter_status=' . urlencode($filter_status) : ''; ?><?php echo !empty($filter_type) ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo !empty($filter_condition) ? '&filter_condition=' . urlencode($filter_condition) : ''; ?><?php echo !empty($filter_building) ? '&filter_building=' . urlencode($filter_building) : ''; ?><?php echo !empty($filter_room) ? '&filter_room=' . urlencode($filter_room) : ''; ?><?php echo $show_standby ? '&show_standby=1' : ''; ?><?php echo $show_archived ? '&show_archived=1' : ''; ?>" 
                           class="px-3 py-1 text-sm rounded <?php echo $active_class; ?> transition-colors">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_status) ? '&filter_status=' . urlencode($filter_status) : ''; ?><?php echo !empty($filter_type) ? '&filter_type=' . urlencode($filter_type) : ''; ?><?php echo !empty($filter_condition) ? '&filter_condition=' . urlencode($filter_condition) : ''; ?><?php echo !empty($filter_building) ? '&filter_building=' . urlencode($filter_building) : ''; ?><?php echo !empty($filter_room) ? '&filter_room=' . urlencode($filter_room) : ''; ?><?php echo $show_standby ? '&show_standby=1' : ''; ?><?php echo $show_archived ? '&show_archived=1' : ''; ?>" 
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

<!-- Update Condition Modal -->
<div id="updateConditionModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="closeUpdateConditionModal()"></div>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="updateConditionForm" onsubmit="updateCondition(event)">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-start mb-4">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fa-solid fa-wrench text-xl text-yellow-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Update Asset Condition
                            </h3>
                            <p class="text-sm text-gray-500 mt-1" id="modalAssetInfo"></p>
                        </div>
                    </div>
                    
                    <input type="hidden" id="assetId" name="asset_id">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Current Condition: <span id="currentCondition" class="font-semibold"></span>
                            </label>
                        </div>
                        
                        <div>
                            <label for="newCondition" class="block text-sm font-medium text-gray-700 mb-1">
                                New Condition <span class="text-red-500">*</span>
                            </label>
                            <select id="newCondition" name="condition" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]">
                                <option value="">Select condition...</option>
                                <option value="Excellent">Excellent</option>
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Poor">Poor</option>
                                <option value="Damaged">Damaged</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="conditionNotes" class="block text-sm font-medium text-gray-700 mb-1">
                                Notes/Observations
                            </label>
                            <textarea id="conditionNotes" name="notes" rows="3" 
                                      placeholder="Describe the reason for condition change, any issues found, repairs needed, etc."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="submit" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#1E3A8A] text-base font-medium text-white hover:bg-[#153570] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1E3A8A] sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fa-solid fa-save mr-2"></i> Update Condition
                    </button>
                    <button type="button" onclick="closeUpdateConditionModal()" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Update Modal -->
<div id="bulkUpdateModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="closeBulkUpdateModal()"></div>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="bulkUpdateForm" onsubmit="bulkUpdateCondition(event)">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-start mb-4">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fa-solid fa-layer-group text-xl text-purple-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Bulk Update Asset Conditions
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Update condition for <span id="bulkSelectedCount" class="font-semibold">0</span> selected assets
                            </p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="bulkNewCondition" class="block text-sm font-medium text-gray-700 mb-1">
                                New Condition <span class="text-red-500">*</span>
                            </label>
                            <select id="bulkNewCondition" name="condition" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]">
                                <option value="">Select condition...</option>
                                <option value="Excellent">Excellent</option>
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Poor">Poor</option>
                                <option value="Damaged">Damaged</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="bulkConditionNotes" class="block text-sm font-medium text-gray-700 mb-1">
                                Notes/Observations
                            </label>
                            <textarea id="bulkConditionNotes" name="notes" rows="3" 
                                      placeholder="Describe the reason for condition change, any issues found, repairs needed, etc."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]"></textarea>
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
                            <p class="text-xs text-blue-800">
                                <i class="fa-solid fa-info-circle mr-1"></i>
                                This will update all selected assets to the same condition.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="submit" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#1E3A8A] text-base font-medium text-white hover:bg-[#153570] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1E3A8A] sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fa-solid fa-save mr-2"></i> Update All Selected
                    </button>
                    <button type="button" onclick="closeBulkUpdateModal()" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Alert Modal -->
<div id="alertModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="closeAlertModal()"></div>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div id="alertIcon" class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fa-solid fa-check-circle text-2xl"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="alertTitle">Notice</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="alertMessage"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeAlertModal()" 
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#1E3A8A] text-base font-medium text-white hover:bg-[#153570] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1E3A8A] sm:w-auto sm:text-sm">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// All rooms data for cascading filter
const allRoomsData = <?php echo json_encode($all_rooms_for_js); ?>;

// Selection management
let selectedAssets = new Set();

// Update room filter based on building selection
function updateRoomFilter() {
    const buildingSelect = document.getElementById('buildingFilter');
    const roomSelect = document.getElementById('roomFilter');
    const selectedBuilding = buildingSelect.value;
    
    // Clear room dropdown
    roomSelect.innerHTML = '<option value="">All Rooms</option>';
    
    // Filter rooms by selected building
    const filteredRooms = selectedBuilding 
        ? allRoomsData.filter(room => room.building_id == selectedBuilding)
        : allRoomsData;
    
    // Populate room dropdown
    filteredRooms.forEach(room => {
        const option = document.createElement('option');
        option.value = room.id;
        option.textContent = room.name;
        roomSelect.appendChild(option);
    });
}

// Toggle select all checkboxes
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const assetCheckboxes = document.querySelectorAll('.asset-checkbox');
    
    assetCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
        if (selectAllCheckbox.checked) {
            selectedAssets.add(checkbox.value);
        } else {
            selectedAssets.delete(checkbox.value);
        }
    });
    
    updateSelection();
}

// Update selection state
function updateSelection() {
    selectedAssets.clear();
    const assetCheckboxes = document.querySelectorAll('.asset-checkbox:checked');
    
    assetCheckboxes.forEach(checkbox => {
        selectedAssets.add(checkbox.value);
    });
    
    const count = selectedAssets.size;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('bulkSelectedCount').textContent = count;
    
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    if (count > 0) {
        bulkActionsBar.classList.remove('hidden');
        bulkActionsBar.classList.add('flex');
    } else {
        bulkActionsBar.classList.add('hidden');
        bulkActionsBar.classList.remove('flex');
    }
    
    // Update select all checkbox state
    const selectAllCheckbox = document.getElementById('selectAll');
    const totalCheckboxes = document.querySelectorAll('.asset-checkbox').length;
    selectAllCheckbox.checked = count === totalCheckboxes && totalCheckboxes > 0;
    selectAllCheckbox.indeterminate = count > 0 && count < totalCheckboxes;
}

// Clear selection
function clearSelection() {
    selectedAssets.clear();
    document.querySelectorAll('.asset-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('selectAll').checked = false;
    updateSelection();
}

// Open bulk update modal
function openBulkUpdateModal() {
    if (selectedAssets.size === 0) {
        showAlert('warning', 'No Selection', 'Please select at least one asset to update.', false);
        return;
    }
    
    document.getElementById('bulkNewCondition').value = '';
    document.getElementById('bulkConditionNotes').value = '';
    document.getElementById('bulkUpdateModal').classList.remove('hidden');
}

// Close bulk update modal
function closeBulkUpdateModal() {
    document.getElementById('bulkUpdateModal').classList.add('hidden');
}

// Bulk update condition
async function bulkUpdateCondition(event) {
    event.preventDefault();
    
    if (selectedAssets.size === 0) {
        showAlert('error', 'Error', 'No assets selected', false);
        return;
    }
    
    const formData = new FormData(event.target);
    const newCondition = formData.get('condition');
    const notes = formData.get('notes');
    
    // Disable submit button
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Updating...';
    
    try {
        // Prepare bulk update request
        const updateFormData = new URLSearchParams();
        updateFormData.append('ajax', '1');
        updateFormData.append('action', 'bulk_update_condition');
        updateFormData.append('condition', newCondition);
        updateFormData.append('notes', notes);
        
        // Add all selected asset IDs
        selectedAssets.forEach(assetId => {
            updateFormData.append('ids[]', assetId);
        });
        
        const response = await fetch('', {
            method: 'POST',
            body: updateFormData,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        let result;
        
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }
        
        // Re-enable button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        
        // Close modal
        closeBulkUpdateModal();
        
        if (result.success) {
            // Update all condition badges
            selectedAssets.forEach(assetId => {
                updateConditionBadge(assetId, newCondition);
            });
            
            showAlert('success', 'Success', result.message, false);
        } else {
            showAlert('error', 'Error', result.message || 'Failed to update assets', false);
        }
        
        // Clear selection
        clearSelection();
        
    } catch (error) {
        console.error('Error:', error);
        
        // Re-enable button on error
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        
        closeBulkUpdateModal();
        showAlert('error', 'Error', error.message || 'An error occurred while updating assets. Please try again.', false);
    }
}

// Open update condition modal
function openUpdateConditionModal(asset) {
    const modal = document.getElementById('updateConditionModal');
    document.getElementById('assetId').value = asset.id;
    document.getElementById('modalAssetInfo').textContent = `${asset.asset_tag} - ${asset.asset_name}`;
    document.getElementById('currentCondition').textContent = asset.condition || 'Good';
    document.getElementById('newCondition').value = '';
    document.getElementById('conditionNotes').value = '';
    modal.classList.remove('hidden');
}

// Close update condition modal
function closeUpdateConditionModal() {
    document.getElementById('updateConditionModal').classList.add('hidden');
}

// Update asset condition
async function updateCondition(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('ajax', '1');
    formData.append('action', 'update_condition');
    const assetId = document.getElementById('assetId').value;
    formData.append('id', assetId);
    
    // Disable submit button to prevent double submission
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Updating...';
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: new URLSearchParams(formData),
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }
        
        // Re-enable button first
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        
        // Then close modal
        closeUpdateConditionModal();
        
        if (result.success) {
            // Update the condition badge in the table without reloading
            const newCondition = formData.get('condition');
            updateConditionBadge(assetId, newCondition);
            
            showAlert('success', 'Success', result.message, false);
        } else {
            showAlert('error', 'Error', result.message || 'Failed to update condition', false);
        }
    } catch (error) {
        console.error('Error:', error);
        // Re-enable button on error
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        closeUpdateConditionModal();
        showAlert('error', 'Error', error.message || 'An error occurred while updating the condition. Please try again.', false);
    }
}

// Update condition badge in table row
function updateConditionBadge(assetId, newCondition) {
    const row = document.querySelector(`tr[data-asset-id="${assetId}"]`);
    if (!row) return;
    
    const conditionBadge = row.querySelector('.condition-badge');
    if (!conditionBadge) return;
    
    // Update badge text
    conditionBadge.textContent = newCondition;
    
    // Update badge classes
    const conditionColors = {
        'Excellent': 'bg-green-100 text-green-700 border-green-300',
        'Good': 'bg-blue-100 text-blue-700 border-blue-300',
        'Fair': 'bg-yellow-100 text-yellow-700 border-yellow-300',
        'Poor': 'bg-orange-100 text-orange-700 border-orange-300',
        'Damaged': 'bg-red-100 text-red-700 border-red-300'
    };
    
    conditionBadge.className = 'condition-badge px-2 py-1 text-xs font-medium rounded border ' + (conditionColors[newCondition] || 'bg-gray-100 text-gray-700 border-gray-300');
    
    // Add a brief highlight animation
    row.classList.add('bg-green-100');
    setTimeout(() => {
        row.classList.remove('bg-green-100');
    }, 2000);
}

// Show alert modal
let shouldReloadOnAlert = false;

function showAlert(type, title, message, reload = false) {
    const modal = document.getElementById('alertModal');
    const iconDiv = document.getElementById('alertIcon');
    const titleEl = document.getElementById('alertTitle');
    const messageEl = document.getElementById('alertMessage');
    
    titleEl.textContent = title;
    messageEl.textContent = message;
    shouldReloadOnAlert = reload;
    
    // Set icon based on type
    if (type === 'success') {
        iconDiv.className = 'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10';
        iconDiv.innerHTML = '<i class="fa-solid fa-check-circle text-2xl text-green-600"></i>';
    } else if (type === 'error') {
        iconDiv.className = 'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10';
        iconDiv.innerHTML = '<i class="fa-solid fa-times-circle text-2xl text-red-600"></i>';
    } else if (type === 'warning') {
        iconDiv.className = 'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10';
        iconDiv.innerHTML = '<i class="fa-solid fa-exclamation-triangle text-2xl text-yellow-600"></i>';
    }
    
    modal.classList.remove('hidden');
    
    // Auto-close success alerts after 2 seconds
    if (type === 'success') {
        setTimeout(() => {
            closeAlertModal();
        }, 2000);
    }
}

// Close alert modal
function closeAlertModal() {
    document.getElementById('alertModal').classList.add('hidden');
    shouldReloadOnAlert = false;
}

// Close modals on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeUpdateConditionModal();
        closeBulkUpdateModal();
        closeAlertModal();
    }
});
</script>

<?php include '../components/layout_footer.php'; ?>
