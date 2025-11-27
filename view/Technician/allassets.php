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
            // Update asset condition
            $stmt = $conn->prepare("UPDATE assets SET `condition` = ?, updated_by = ? WHERE id = ?");
            $updated_by = $_SESSION['user_id'];
            $stmt->bind_param('sii', $condition, $updated_by, $id);
            $success = $stmt->execute();
            $stmt->close();
            
            // Log the condition change in activity logs if needed
            if ($success && !empty($notes)) {
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?, 'update_condition', ?, NOW())");
                $description = "Updated asset ID {$id} condition to {$condition}. Notes: {$notes}";
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
$limit = 12;
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
            <div>
                <h1 class="text-lg font-bold text-gray-800">Asset Registry</h1>
                <p class="text-xs text-gray-500 mt-0.5">View and update asset conditions</p>
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
            </div>
        </div>

        <!-- Assets Grid -->
        <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200 p-3">
            <?php if (empty($assets)): ?>
                <div class="flex flex-col items-center justify-center h-full text-gray-400">
                    <i class="fa-solid fa-inbox text-6xl mb-4 opacity-30"></i>
                    <p class="text-lg font-medium text-gray-500">No assets found</p>
                    <p class="text-sm text-gray-400 mt-1">Try adjusting your filters</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                    <?php foreach ($assets as $asset): ?>
                        <div class="border border-gray-200 rounded-lg p-3 hover:shadow-md transition-shadow bg-white">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-semibold text-blue-600 truncate"><?php echo htmlspecialchars($asset['asset_tag']); ?></h3>
                                    <p class="text-xs text-gray-700 mt-0.5 truncate"><?php echo htmlspecialchars($asset['asset_name']); ?></p>
                                </div>
                                <span class="px-2 py-0.5 text-xs font-medium bg-purple-100 text-purple-700 rounded flex-shrink-0 ml-2">
                                    <?php echo htmlspecialchars($asset['asset_type']); ?>
                                </span>
                            </div>
                            
                            <div class="space-y-1 text-xs text-gray-600 mb-3">
                                <?php if (!empty($asset['brand']) || !empty($asset['model'])): ?>
                                    <p class="truncate">
                                        <i class="fa-solid fa-tag text-gray-400 w-4"></i>
                                        <?php 
                                        $brand_model = array_filter([$asset['brand'] ?? '', $asset['model'] ?? '']);
                                        echo htmlspecialchars(implode(' - ', $brand_model) ?: 'N/A'); 
                                        ?>
                                    </p>
                                <?php endif; ?>
                                
                                <p class="truncate">
                                    <i class="fa-solid fa-building text-gray-400 w-4"></i>
                                    <?php echo htmlspecialchars($asset['building_name'] ?? 'Standby'); ?>
                                    <?php if (!empty($asset['room_name'])): ?>
                                        - <?php echo htmlspecialchars($asset['room_name']); ?>
                                    <?php endif; ?>
                                </p>
                                
                                <div class="flex items-center gap-2">
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
                                    ?> px-2 py-0.5 text-xs font-medium rounded">
                                        <?php echo htmlspecialchars($asset['status']); ?>
                                    </span>
                                    
                                    <span class="<?php 
                                        $condition_colors = [
                                            'Excellent' => 'bg-green-100 text-green-700 border-green-300',
                                            'Good' => 'bg-blue-100 text-blue-700 border-blue-300',
                                            'Fair' => 'bg-yellow-100 text-yellow-700 border-yellow-300',
                                            'Poor' => 'bg-orange-100 text-orange-700 border-orange-300',
                                            'Damaged' => 'bg-red-100 text-red-700 border-red-300'
                                        ];
                                        echo $condition_colors[$asset['condition'] ?? 'Good'] ?? 'bg-gray-100 text-gray-700 border-gray-300';
                                    ?> px-2 py-0.5 text-xs font-medium rounded border">
                                        <?php echo htmlspecialchars($asset['condition'] ?? 'Good'); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <button onclick="openUpdateConditionModal(<?php echo htmlspecialchars(json_encode($asset)); ?>)" 
                                    class="w-full px-3 py-1.5 text-xs font-medium text-white bg-[#1E3A8A] rounded hover:bg-[#153570] transition-colors">
                                <i class="fa-solid fa-wrench"></i> Update Condition
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
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
    formData.append('id', document.getElementById('assetId').value);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: new URLSearchParams(formData)
        });
        
        const result = await response.json();
        
        closeUpdateConditionModal();
        
        if (result.success) {
            showAlert('success', 'Success', result.message, true);
        } else {
            showAlert('error', 'Error', result.message, false);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', 'An error occurred while updating the condition', false);
    }
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
}

// Close alert modal
function closeAlertModal() {
    document.getElementById('alertModal').classList.add('hidden');
    if (shouldReloadOnAlert) {
        location.reload();
    }
    shouldReloadOnAlert = false;
}

// Close modals on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeUpdateConditionModal();
        closeAlertModal();
    }
});
</script>

<?php include '../components/layout_footer.php'; ?>
