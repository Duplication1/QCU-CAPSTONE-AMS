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

// Pagination for archived assets
$archived_assets_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$archived_assets_limit = 5;
$archived_assets_offset = ($archived_assets_page - 1) * $archived_assets_limit;

// Count total archived assets
$archived_assets_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM assets WHERE room_id = ? AND (status = 'Archive' OR status = 'Archived')");
$archived_assets_count_stmt->bind_param('i', $room_id);
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
    $archived_assets_query = $conn->prepare("SELECT * FROM assets WHERE room_id = ? AND (status = 'Archive' OR status = 'Archived') ORDER BY asset_name ASC LIMIT ? OFFSET ?");
    $archived_assets_query->bind_param('iii', $room_id, $archived_assets_limit, $archived_assets_offset);
    $archived_assets_query->execute();
    $archived_assets_result = $archived_assets_query->get_result();

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
    $archived_assets_query->close();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'get_asset_qrcodes') {
        try {
            $asset_ids = json_decode($_POST['asset_ids'] ?? '[]', true);
            
            if (empty($asset_ids) || !is_array($asset_ids)) {
                echo json_encode(['success' => false, 'message' => 'Invalid asset IDs']);
                exit;
            }
            
            $stmt = $conn->prepare("SELECT id, asset_tag, asset_name, asset_type, qr_code FROM assets WHERE id IN (" . str_repeat('?,', count($asset_ids) - 1) . "?)");
            $stmt->bind_param(str_repeat('i', count($asset_ids)), ...$asset_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            $assets_data = [];
            
            while ($row = $result->fetch_assoc()) {
                $assets_data[] = [
                    'id' => $row['id'],
                    'asset_tag' => $row['asset_tag'],
                    'asset_name' => $row['asset_name'],
                    'asset_type' => $row['asset_type'],
                    'qr_code' => $row['qr_code']
                ];
            }
            $stmt->close();
            
            echo json_encode(['success' => true, 'assets' => $assets_data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
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
}
</style>

<main class="flex-1 overflow-auto">
    <div id="app-container" class="h-full flex flex-col">
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
        <div class="bg-white rounded shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 bg-gradient-to-r from-red-50 to-red-100 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-box-archive text-red-600"></i>
                    <h4 class="text-sm font-semibold text-gray-800">Archived Assets</h4>
                    <span class="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 rounded-full">
                        <?php echo $total_archived_assets; ?> Total
                    </span>
                </div>
                <div id="archived-assets-bulk-actions" class="hidden flex items-center gap-2">
                    <button onclick="bulkPrintQRArchivedAssets()" class="px-3 py-1.5 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded transition-colors">
                        <i class="fa-solid fa-qrcode mr-1"></i>Print QR Codes
                    </button>
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
                                                    <button onclick="restoreAsset(<?php echo $asset['id']; ?>, '<?php echo htmlspecialchars($asset['asset_tag'], ENT_QUOTES); ?>')" 
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
                    <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $archived_assets_page - 1; ?>" 
                       class="px-3 py-1 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <i class="fa-solid fa-chevron-left mr-1"></i>Previous
                    </a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $archived_assets_page - 2);
                    $end_page = min($total_archived_assets_pages, $archived_assets_page + 2);
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $i; ?>" 
                       class="px-3 py-1 text-sm font-medium <?php echo $i === $archived_assets_page ? 'text-blue-600 bg-blue-50 border-blue-500' : 'text-gray-500 bg-white border-gray-300'; ?> border rounded-md hover:bg-gray-50">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($archived_assets_page < $total_archived_assets_pages): ?>
                    <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $archived_assets_page + 1; ?>" 
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
// Asset Kebab Menu Functions
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

// Bulk actions for Archived Assets
function bulkPrintQRArchivedAssets() {
    const selectedIds = Array.from(document.querySelectorAll('.archived-asset-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) return;
    
    openQRPrintModalForAssets(selectedIds);
}

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

    if (!confirm(`Are you sure you want to restore ${selectedIds.length} asset(s)?\n\nAssets: ${assetTags.join(', ')}\n\nRestored assets will be available again.`)) {
        return;
    }

    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Restoring...';
    button.disabled = true;

    fetch('../../controller/restore_assets.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            ids: selectedIds,
            room_id: <?php echo $room_id; ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Successfully restored ${selectedIds.length} asset(s)`);
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to restore assets'));
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while restoring assets');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Individual restore function
function restoreAsset(id, assetTag) {
    if (!confirm(`Are you sure you want to restore asset "${assetTag}"?\n\nThe asset will be available again.`)) {
        return;
    }

    fetch('../../controller/restore_asset.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: id,
            room_id: <?php echo $room_id; ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Asset restored successfully');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to restore asset'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while restoring the asset');
    });
}

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
                qrItem.className = 'qr-item';
                qrItem.innerHTML = `
                    <div class="qr-code mb-2">${asset.qr_code}</div>
                    <div class="asset-info text-xs">
                        <div class="font-semibold">${asset.asset_tag}</div>
                        <div class="text-gray-600">${asset.asset_name}</div>
                        <div class="text-gray-500">${asset.asset_type}</div>
                    </div>
                `;
                qrContent.appendChild(qrItem);
            });
            
            document.getElementById('qrPrintModal').classList.remove('hidden');
        } else {
            alert(result.message || 'Failed to load QR codes');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while loading QR codes');
    }
}

function closeQRPrintModal() {
    document.getElementById('qrPrintModal').classList.add('hidden');
    // Reload page after closing print modal
    window.location.reload();
}
</script>

<?php include '../components/layout_footer.php'; ?>