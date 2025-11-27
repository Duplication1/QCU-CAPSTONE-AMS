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
            // Generate QR code data
            $qr_data = json_encode([
                'asset_tag' => $asset_tag,
                'asset_name' => $asset_name,
                'asset_type' => $asset_type,
                'room_id' => $room_id
            ]);
            $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr_data);
            
            $stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, serial_number, room_id, status, `condition`, qr_code, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $created_by = $_SESSION['user_id'];
            $stmt->bind_param('ssssssisssi', $asset_tag, $asset_name, $asset_type, $brand, $model, $serial_number, $room_id, $status, $condition, $qr_code_url, $created_by);
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
        $room_id = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
        
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
                
                // Generate unique QR code
                $qr_data = json_encode([
                    'asset_tag' => $asset_tag,
                    'asset_name' => $asset_name,
                    'asset_type' => $asset_type,
                    'room_id' => $room_id,
                    'brand' => $brand,
                    'model' => $model
                ]);
                $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr_data);
                
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
        $room_id = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
        
        if ($id <= 0 || empty($asset_tag) || empty($asset_name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE assets SET asset_tag = ?, asset_name = ?, asset_type = ?, brand = ?, model = ?, serial_number = ?, room_id = ?, status = ?, `condition` = ?, updated_by = ? WHERE id = ?");
            $updated_by = $_SESSION['user_id'];
            $stmt->bind_param('ssssssissii', $asset_tag, $asset_name, $asset_type, $brand, $model, $serial_number, $room_id, $status, $condition, $updated_by, $id);
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
}

// Fetch assets with search, filter, and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
$filter_type = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : '';
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] === '1';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Count total standby assets (not assigned to any room) - always include archived for client-side filtering
$count_query = "SELECT COUNT(*) as total FROM assets WHERE (room_id IS NULL OR room_id = 0)";
$params = [];
$types = '';

// Don't filter archived on server side anymore - we'll handle it with JavaScript
// if (!$show_archived) {
//     $count_query .= " AND status != 'Archived'";
// }

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

// Don't filter archived on server side anymore - we'll handle it with JavaScript
// if (!$show_archived) {
//     $query_sql .= " AND a.status != 'Archived'";
// }

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
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white rounded shadow-sm border border-gray-200 mb-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Standby Assets</h3>
                <p class="text-xs text-gray-500 mt-0.5">Assets not assigned to any room • Total: <?php echo $total_assets; ?> asset(s)</p>
            </div>
            
            <button onclick="openAddAssetModal()" 
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fa-solid fa-plus"></i>
                <span>Add Asset</span>
            </button>
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
                    <input type="checkbox" name="show_archived" id="show_archived" value="1" <?php echo $show_archived ? 'checked' : ''; ?> class="rounded">
                    <span class="text-sm text-gray-700">Show Archived</span>
                </label>
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

        <!-- Assets Table -->
        <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Tag</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand/Model</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="assetsTableBody">
                    <?php if (empty($assets)): ?>
                        <tr id="noResultsRow">
                            <td colspan="10" class="px-6 py-12 text-center text-gray-500">
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
                            <tr class="hover:bg-gray-50 transition-colors asset-row" 
                                data-status="<?php echo htmlspecialchars($asset['status']); ?>"
                                data-type="<?php echo htmlspecialchars($asset['asset_type']); ?>"
                                data-tag="<?php echo htmlspecialchars($asset['asset_tag']); ?>"
                                data-name="<?php echo htmlspecialchars($asset['asset_name']); ?>"
                                data-brand="<?php echo htmlspecialchars($asset['brand'] ?? ''); ?>"
                                data-model="<?php echo htmlspecialchars($asset['model'] ?? ''); ?>"
                                data-serial="<?php echo htmlspecialchars($asset['serial_number'] ?? ''); ?>">
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($asset['serial_number'] ?: 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($asset['room_name'] ?: 'N/A'); ?>
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
                                        'Damaged' => 'bg-orange-100 text-orange-700',
                                        'Archived' => 'bg-purple-100 text-purple-700'
                                    ];
                                    $status_class = $status_colors[$asset['status']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>" data-status-badge>
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
                           placeholder="e.g., COMP-001">
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
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., IK501">
                    </div>
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="bulkStatus" name="bulk_status" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Active">Active</option>
                            <option value="Available" selected>Available</option>
                            <option value="In Use">In Use</option>
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

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Delete Asset</h3>
        </div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-6">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                    <i class="fa-solid fa-trash text-red-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-800 font-medium mb-2">Are you sure you want to delete this asset?</p>
                    <p class="text-sm text-gray-600 mb-1">Asset Tag: <span id="deleteAssetTag" class="font-semibold text-gray-800"></span></p>
                    <p class="text-xs text-red-600 mt-2 font-medium">This action cannot be undone!</p>
                </div>
            </div>
            <div class="flex gap-3 justify-end">
                <button onclick="closeDeleteModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button onclick="confirmDeleteAsset()" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fa-solid fa-trash mr-2"></i>Delete Asset
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
</style>

<script>
// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const previewFields = ['bulkAcquisitionDate', 'bulkAssetName', 'bulkRoomNumber', 'bulkQuantity', 'bulkStartNumber'];
    previewFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', updateAssetTagPreview);
        }
    });
    
    // Add event listeners for real-time filtering
    const filterStatus = document.getElementById('filter_status');
    const filterType = document.getElementById('filter_type');
    const searchInput = document.getElementById('searchInput');
    const showArchived = document.getElementById('show_archived');
    
    if (filterStatus) {
        filterStatus.addEventListener('change', applyFilters);
    }
    
    if (filterType) {
        filterType.addEventListener('change', applyFilters);
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(applyFilters, 300));
    }
    
    if (showArchived) {
        showArchived.addEventListener('change', applyFilters);
    }
    
    // Apply filters on page load to hide archived assets by default
    applyFilters();
});

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

function closeQRPrintModal() {
    document.getElementById('qrPrintModal').classList.add('hidden');
    window.location.reload();
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
        updateAssetTagPreview();
    } else {
        singleFields.classList.remove('hidden');
        bulkFields.classList.add('hidden');
        submitBtn.textContent = 'Create Asset';
    }
}

// Update Asset Tag Preview
function updateAssetTagPreview() {
    const date = document.getElementById('bulkAcquisitionDate')?.value || '<?php echo date('Y-m-d'); ?>';
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
        formData.append('room_number', document.getElementById('bulkRoomNumber').value);
        formData.append('quantity', document.getElementById('bulkQuantity').value);
        formData.append('start_number', document.getElementById('bulkStartNumber').value);
        formData.append('asset_type', document.getElementById('bulkAssetType').value);
        formData.append('brand', document.getElementById('bulkBrand').value);
        formData.append('model', document.getElementById('bulkModel').value);
        formData.append('room_id', document.getElementById('bulkRoomId').value);
        formData.append('status', document.getElementById('bulkStatus').value);
        formData.append('condition', 'Good');
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
    formData.append('room_id', document.getElementById('editRoomId').value);
    formData.append('status', document.getElementById('editStatus').value);
    formData.append('condition', document.getElementById('editCondition').value);
    
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
let assetToDelete = { id: null, tag: null };

function deleteAsset(id, assetTag) {
    closeAllMenus();
    assetToDelete = { id, tag: assetTag };
    document.getElementById('deleteAssetTag').textContent = assetTag;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    assetToDelete = { id: null, tag: null };
}

async function confirmDeleteAsset() {
    const { id } = assetToDelete;
    
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
            showAlert('success', result.message);
            closeDeleteModal();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while deleting the asset');
    }
}

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

// Close menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('[id^="menu-"]') && !e.target.closest('button')) {
        closeAllMenus();
    }
});
</script>

<?php include '../components/layout_footer.php'; ?>