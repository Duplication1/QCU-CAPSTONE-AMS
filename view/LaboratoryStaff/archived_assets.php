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

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination for archived assets
$archived_assets_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
// Handle "all" entries
if ($per_page <= 0) $per_page = 999999; // Show all
$archived_assets_limit = $per_page;
$archived_assets_offset = ($archived_assets_page - 1) * $archived_assets_limit;

// Count total archived assets
$archived_assets_count_query = "SELECT COUNT(*) as total FROM assets WHERE room_id = ? AND (status = 'Archive' OR status = 'Archived')";
$archived_assets_count_params = [$room_id];
$archived_assets_count_types = 'i';

if (!empty($search)) {
    $archived_assets_count_query .= " AND (asset_name LIKE ? OR asset_tag LIKE ? OR brand LIKE ? OR model LIKE ?)";
    $search_param = '%' . $search . '%';
    $archived_assets_count_params = array_merge($archived_assets_count_params, [$search_param, $search_param, $search_param, $search_param]);
    $archived_assets_count_types .= 'ssss';
}

$archived_assets_count_stmt = $conn->prepare($archived_assets_count_query);
$archived_assets_count_stmt->bind_param($archived_assets_count_types, ...$archived_assets_count_params);
$archived_assets_count_stmt->execute();
$archived_assets_result = $archived_assets_count_stmt->get_result();
$total_archived_assets = $archived_assets_result->fetch_assoc()['total'];
$total_archived_assets_pages = ceil($total_archived_assets / $archived_assets_limit);
$archived_assets_count_stmt->close();

// Count total PC units and assets for tab navigation
$pc_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM pc_units WHERE room_id = ?");
$pc_count_stmt->bind_param('i', $room_id);
$pc_count_stmt->execute();
$pc_result = $pc_count_stmt->get_result();
$total_pc_units = $pc_result->fetch_assoc()['total'];
$pc_count_stmt->close();

$assets_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM assets WHERE room_id = ?");
$assets_count_stmt->bind_param('i', $room_id);
$assets_count_stmt->execute();
$assets_result = $assets_count_stmt->get_result();
$total_assets = $assets_result->fetch_assoc()['total'];
$assets_count_stmt->close();

$archived_pc_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM pc_units WHERE room_id = ? AND (status = 'Archive' OR status = 'Archived')");
$archived_pc_count_stmt->bind_param('i', $room_id);
$archived_pc_count_stmt->execute();
$archived_pc_result = $archived_pc_count_stmt->get_result();
$total_archived_pc = $archived_pc_result->fetch_assoc()['total'];
$archived_pc_count_stmt->close();

// Fetch archived assets with pagination
$archived_assets = [];
if ($total_archived_assets > 0) {
    $archived_assets_query = "SELECT * FROM assets WHERE room_id = ? AND (status = 'Archive' OR status = 'Archived')";
    $archived_assets_params = [$room_id];
    $archived_assets_types = 'i';

    if (!empty($search)) {
        $archived_assets_query .= " AND (asset_name LIKE ? OR asset_tag LIKE ? OR brand LIKE ? OR model LIKE ?)";
        $search_param = '%' . $search . '%';
        $archived_assets_params = array_merge($archived_assets_params, [$search_param, $search_param, $search_param, $search_param]);
        $archived_assets_types .= 'ssss';
    }

    $archived_assets_query .= " ORDER BY asset_name ASC LIMIT ? OFFSET ?";
    $archived_assets_params = array_merge($archived_assets_params, [$archived_assets_limit, $archived_assets_offset]);
    $archived_assets_types .= 'ii';

    $archived_assets_stmt = $conn->prepare($archived_assets_query);
    $archived_assets_stmt->bind_param($archived_assets_types, ...$archived_assets_params);
    $archived_assets_stmt->execute();
    $archived_assets_result = $archived_assets_stmt->get_result();

    while ($asset_row = $archived_assets_result->fetch_assoc()) {
        $archived_assets[] = [
            'id' => $asset_row['id'],
            'name' => $asset_row['asset_name'],
            'brand' => $asset_row['brand'],
            'model' => $asset_row['model'],
            'asset_tag' => $asset_row['asset_tag'],
            'condition' => isset($asset_row['condition']) ? $asset_row['condition'] : 'N/A',
            'created_at' => $asset_row['created_at'],
            'updated_at' => $asset_row['updated_at']
        ];
    }
    $archived_assets_stmt->close();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    // Add any future AJAX handlers here
}

include '../components/layout_header.php';
?>

<style>
main {
    padding: 0.5rem;
    background-color: #f9fafb;
    min-height: 100vh;
}
.overflow-x-auto {
    overflow: visible !important;
}
table {
    position: relative;
}
tbody tr {
    position: relative;
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
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-gray-400 mx-2"></i>
                            <a href="roomassets.php?room_id=<?php echo $room_id; ?>" class="text-sm font-medium text-gray-700 hover:text-blue-600">
                                <?php echo htmlspecialchars($room['name']); ?>
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-sm font-medium text-gray-500">Archived Assets</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 overflow-hidden">
            <div class="grid grid-cols-4 border-b border-gray-200">
                <a href="roomassets.php?room_id=<?php echo $room_id; ?>" 
                   class="px-4 py-3 text-sm font-medium transition-all duration-200 border-b-2 border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 flex items-center justify-center">
                    <i class="fa-solid fa-desktop mr-2"></i>PC Units
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-gray-500 text-white rounded-full"><?php echo $total_pc_units; ?></span>
                </a>
                <a href="roomassets.php?room_id=<?php echo $room_id; ?>" 
                   class="px-4 py-3 text-sm font-medium transition-all duration-200 border-b-2 border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 flex items-center justify-center">
                    <i class="fa-solid fa-boxes-stacked mr-2"></i>All Assets
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-gray-500 text-white rounded-full"><?php echo $total_assets; ?></span>
                </a>
                <a href="archived_pc_units.php?room_id=<?php echo $room_id; ?>" 
                   class="px-4 py-3 text-sm font-medium transition-all duration-200 border-b-2 border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 flex items-center justify-center">
                    <i class="fa-solid fa-archive mr-2"></i>Archived PC
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-red-500 text-white rounded-full"><?php echo $total_archived_pc; ?></span>
                </a>
                <button class="px-4 py-3 text-sm font-medium transition-all duration-200 border-b-2 border-red-500 text-red-600 bg-red-50 flex items-center justify-center cursor-default">
                    <i class="fa-solid fa-archive mr-2"></i>Archived Assets
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-red-500 text-white rounded-full"><?php echo $total_archived_assets; ?></span>
                </button>
            </div>
        </div>

        <!-- Archived Assets Table -->
        <div class="bg-white rounded shadow-sm border border-gray-200">
            <!-- Search Bar -->
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <form method="GET" action="" id="filterForm">
                    <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                    <div class="flex items-center gap-4">
                        <div class="flex-1">
                            <input type="text" name="search" id="search-input" placeholder="Search by asset name, tag, brand, or model..." 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700">Show:</label>
                            <select name="per_page" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                <option value="10" <?php echo ($per_page == 10) ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?php echo ($per_page == 25) ? 'selected' : ''; ?>>25</option>
                                <option value="100" <?php echo ($per_page == 100) ? 'selected' : ''; ?>>100</option>
                                <option value="0" <?php echo ($per_page == 999999) ? 'selected' : ''; ?>>All</option>
                            </select>
                        </div>
                        <?php if (!empty($search)): ?>
                        <a href="?room_id=<?php echo $room_id; ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            <i class="fa-solid fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="px-4 py-3 bg-gradient-to-r from-red-50 to-red-100 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-box-archive text-red-600"></i>
                    <h4 class="text-sm font-semibold text-gray-800">Archived Assets</h4>
                    <span class="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 rounded-full">
                        <?php echo $total_archived_assets; ?> Total
                    </span>
                </div>
                <div id="archived-assets-bulk-actions" class="hidden flex items-center gap-2">
                    <button onclick="bulkRestoreArchivedAssets()" class="px-3 py-1.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded transition-colors">
                        <i class="fa-solid fa-rotate-left mr-1"></i>Restore Selected
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                <input type="checkbox" id="select-all-archived-assets" class="rounded border-gray-300 text-red-600 focus:ring-red-600">
                            </th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Tag</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($archived_assets)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fa-solid fa-archive text-5xl mb-3 opacity-30"></i>
                                    <p class="text-lg">No archived assets</p>
                                    <p class="text-sm">Archived assets will appear here</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($archived_assets as $asset): ?>
                                <tr class="hover:bg-red-50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap text-center" onclick="event.stopPropagation()">
                                        <input type="checkbox" class="archived-asset-checkbox rounded border-gray-300 text-red-600 focus:ring-red-600" value="<?php echo $asset['id']; ?>">
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-red-600"><?php echo htmlspecialchars($asset['asset_tag']); ?></span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($asset['name']); ?></span>
                                            <span class="text-xs text-gray-500"><?php echo htmlspecialchars($asset['brand'] . ' ' . $asset['model']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-700">
                                            <?php echo htmlspecialchars($asset['condition']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-xs text-gray-500"><?php echo date('M d, H:i', strtotime($asset['updated_at'])); ?></span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center" onclick="event.stopPropagation()">
                                        <div class="relative">
                                            <button onclick="toggleAssetMenu(<?php echo $asset['id']; ?>)" 
                                                    class="p-2 hover:bg-gray-100 rounded-full transition-colors" 
                                                    title="Actions">
                                                <i class="fa-solid fa-ellipsis-vertical text-gray-600"></i>
                                            </button>
                                            <div id="asset-menu-<?php echo $asset['id']; ?>" 
                                                 class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                                                <div class="py-1">
                                                    <button onclick="openRestoreAssetModal(<?php echo $asset['id']; ?>, '<?php echo htmlspecialchars($asset['asset_tag'], ENT_QUOTES); ?>')" 
                                                            class="w-full text-left px-4 py-2 text-sm text-green-600 hover:bg-green-50 flex items-center gap-2">
                                                        <i class="fa-solid fa-rotate-left text-green-600"></i> Restore
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
            <?php if ($total_archived_assets_pages > 1): ?>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <?php echo min(($archived_assets_page - 1) * $archived_assets_limit + 1, $total_archived_assets); ?> to <?php echo min($archived_assets_page * $archived_assets_limit, $total_archived_assets); ?> of <?php echo $total_archived_assets; ?> archived assets
                </div>
                <div class="flex items-center space-x-1">
                    <?php if ($archived_assets_page > 1): ?>
                    <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $archived_assets_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>&per_page=<?php echo $_GET['per_page'] ?? 10; ?>" 
                       class="px-3 py-1 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <i class="fa-solid fa-chevron-left mr-1"></i>Previous
                    </a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $archived_assets_page - 2);
                    $end_page = min($total_archived_assets_pages, $archived_assets_page + 2);
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>&per_page=<?php echo $_GET['per_page'] ?? 10; ?>" 
                       class="px-3 py-1 text-sm font-medium <?php echo $i === $archived_assets_page ? 'text-blue-600 bg-blue-50 border-blue-500' : 'text-gray-500 bg-white border-gray-300'; ?> border rounded-md hover:bg-gray-50">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($archived_assets_page < $total_archived_assets_pages): ?>
                    <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $archived_assets_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>&per_page=<?php echo $_GET['per_page'] ?? 10; ?>" 
                       class="px-3 py-1 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Next<i class="fa-solid fa-chevron-right ml-1"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Restore Confirmation Modal -->
<div id="restoreAssetModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 rounded-full mb-4">
                <i class="fa-solid fa-rotate-left text-green-600 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Restore Asset?</h3>
            <p class="text-sm text-gray-600 text-center mb-4">
                Are you sure you want to restore <strong id="restoreAssetName"></strong>?
            </p>
            <p class="text-xs text-gray-500 text-center mb-6">
                The asset will be available again.
            </p>
            <div class="flex gap-3">
                <button onclick="closeRestoreAssetModal()" 
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button id="confirmRestoreAssetBtn" onclick="confirmRestoreAsset()" 
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md transition-colors">
                    <i class="fa-solid fa-rotate-left mr-1"></i>Restore
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Restore Confirmation Modal -->
<div id="bulkRestoreModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 rounded-full mb-4">
                <i class="fa-solid fa-rotate-left text-green-600 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Restore Multiple Assets?</h3>
            <p class="text-sm text-gray-600 text-center mb-4">
                Are you sure you want to restore <strong id="bulkRestoreCount">0</strong> asset(s)?
            </p>
            <div id="bulkRestoreList" class="bg-gray-50 p-3 rounded-lg border max-h-32 overflow-y-auto mb-4">
                <!-- Asset tags will be listed here -->
            </div>
            <p class="text-xs text-gray-500 text-center mb-6">
                The assets will be available again.
            </p>
            <div class="flex gap-3">
                <button onclick="closeBulkRestoreModal()" 
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button id="confirmBulkRestoreBtn" onclick="confirmBulkRestore()" 
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md transition-colors">
                    <i class="fa-solid fa-rotate-left mr-1"></i>Restore All
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Asset Menu Functions
function toggleAssetMenu(id) {
    event.stopPropagation();
    const menu = document.getElementById('asset-menu-' + id);
    const allMenus = document.querySelectorAll('[id^="asset-menu-"]');
    allMenus.forEach(m => {
        if (m !== menu) m.classList.add('hidden');
    });
    menu.classList.toggle('hidden');
}

// Close menus when clicking outside
document.addEventListener('click', function(event) {
    const menus = document.querySelectorAll('[id^="asset-menu-"]');
    menus.forEach(menu => {
        if (!menu.contains(event.target) && !event.target.closest('[onclick*="toggleAssetMenu"]')) {
            menu.classList.add('hidden');
        }
    });
});

// Debounced search
let searchTimeout;
document.getElementById('search-input').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const searchValue = this.value.trim();
        const url = new URL(window.location);
        if (searchValue) {
            url.searchParams.set('search', searchValue);
        } else {
            url.searchParams.delete('search');
        }
        url.searchParams.delete('page'); // Reset to page 1
        // Preserve per_page parameter
        const perPage = new URLSearchParams(window.location.search).get('per_page');
        if (perPage) {
            url.searchParams.set('per_page', perPage);
        }
        window.location.href = url.toString();
    }, 1000);
});

// Bulk actions for Archived Assets
function bulkRestoreArchivedAssets() {
    const selectedIds = Array.from(document.querySelectorAll('.archived-asset-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) {
        alert('Please select assets to restore');
        return;
    }

    const assetTags = selectedIds.map(id => {
        const asset = <?php echo json_encode($archived_assets); ?>.find(a => a.id == id);
        return asset ? asset.asset_tag : id;
    });

    // Open modal instead of confirm
    document.getElementById('bulkRestoreCount').textContent = selectedIds.length;
    const listHtml = assetTags.map(tag => `<span class="inline-block bg-white px-2 py-1 rounded border text-sm mr-1 mb-1">${tag}</span>`).join('');
    document.getElementById('bulkRestoreList').innerHTML = listHtml;
    document.getElementById('bulkRestoreModal').classList.remove('hidden');
    
    // Store IDs for later use
    window.bulkRestoreAssetIds = selectedIds;
}

function closeBulkRestoreModal() {
    document.getElementById('bulkRestoreModal').classList.add('hidden');
    window.bulkRestoreAssetIds = null;
}

function confirmBulkRestore() {
    if (!window.bulkRestoreAssetIds) return;
    
    const button = document.getElementById('confirmBulkRestoreBtn');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Restoring...';
    button.disabled = true;

    fetch('../../controller/restore_assets.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            ids: window.bulkRestoreAssetIds,
            room_id: <?php echo $room_id; ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', `Successfully restored ${window.bulkRestoreAssetIds.length} asset(s)`);
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', data.message || 'Failed to restore assets');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while restoring assets');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Restore Asset Modal Functions
let currentRestoreAssetId = null;

function openRestoreAssetModal(id, assetTag) {
    currentRestoreAssetId = id;
    const modal = document.getElementById('restoreAssetModal');
    const assetName = document.getElementById('restoreAssetName');
    
    assetName.textContent = assetTag;
    modal.classList.remove('hidden');
}

function closeRestoreAssetModal() {
    const modal = document.getElementById('restoreAssetModal');
    modal.classList.add('hidden');
    currentRestoreAssetId = null;
}

function confirmRestoreAsset() {
    if (!currentRestoreAssetId) return;
    
    const button = document.getElementById('confirmRestoreAssetBtn');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Restoring...';
    button.disabled = true;
    
    fetch('../../controller/restore_asset.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: currentRestoreAssetId,
            room_id: <?php echo $room_id; ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Asset restored successfully!');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', data.message || 'Failed to restore asset');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while restoring the asset');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Close modal when clicking outside
document.getElementById('restoreAssetModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeRestoreAssetModal();
    }
});

document.getElementById('bulkRestoreModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeBulkRestoreModal();
    }
});

// Select all functionality
document.getElementById('select-all-archived-assets')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.archived-asset-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    toggleArchivedAssetsBulkActions();
});

// Individual checkbox change
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('archived-asset-checkbox')) {
        updateSelectAllArchivedAssets();
        toggleArchivedAssetsBulkActions();
    }
});

function updateSelectAllArchivedAssets() {
    const allCheckboxes = document.querySelectorAll('.archived-asset-checkbox');
    const checkedCheckboxes = document.querySelectorAll('.archived-asset-checkbox:checked');
    const selectAllCheckbox = document.getElementById('select-all-archived-assets');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
    }
}

function toggleArchivedAssetsBulkActions() {
    const checkedCheckboxes = document.querySelectorAll('.archived-asset-checkbox:checked');
    const bulkActions = document.getElementById('archived-assets-bulk-actions');
    
    if (bulkActions) {
        if (checkedCheckboxes.length > 0) {
            bulkActions.classList.remove('hidden');
        } else {
            bulkActions.classList.add('hidden');
        }
    }
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
</script>

<?php include '../components/layout_footer.php'; ?>