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

// Pagination for archived PC units
$archived_pc_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$archived_pc_limit = 5;
$archived_pc_offset = ($archived_pc_page - 1) * $archived_pc_limit;

// Count total archived PC units
$archived_pc_count_query = "SELECT COUNT(*) as total FROM pc_units WHERE room_id = ? AND (status = 'Archive' OR status = 'Archived')";
$archived_pc_count_params = [$room_id];
$archived_pc_count_types = 'i';

if (!empty($search)) {
    $archived_pc_count_query .= " AND terminal_number LIKE ?";
    $search_param = '%' . $search . '%';
    $archived_pc_count_params[] = $search_param;
    $archived_pc_count_types .= 's';
}

$archived_pc_count_stmt = $conn->prepare($archived_pc_count_query);
$archived_pc_count_stmt->bind_param($archived_pc_count_types, ...$archived_pc_count_params);
$archived_pc_count_stmt->execute();
$archived_pc_result = $archived_pc_count_stmt->get_result();
$total_archived_pc = $archived_pc_result->fetch_assoc()['total'];
$total_archived_pc_pages = ceil($total_archived_pc / $archived_pc_limit);
$archived_pc_count_stmt->close();

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

$archived_assets_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM assets WHERE room_id = ? AND (status = 'Archive' OR status = 'Archived')");
$archived_assets_count_stmt->bind_param('i', $room_id);
$archived_assets_count_stmt->execute();
$archived_assets_result = $archived_assets_count_stmt->get_result();
$total_archived_assets = $archived_assets_result->fetch_assoc()['total'];
$archived_assets_count_stmt->close();

// Fetch archived PC units with pagination
$archived_pc_units = [];
if ($total_archived_pc > 0) {
    $archived_pc_query = "SELECT * FROM pc_units WHERE room_id = ? AND (status = 'Archive' OR status = 'Archived')";
    $archived_pc_params = [$room_id];
    $archived_pc_types = 'i';

    if (!empty($search)) {
        $archived_pc_query .= " AND terminal_number LIKE ?";
        $search_param = '%' . $search . '%';
        $archived_pc_params[] = $search_param;
        $archived_pc_types .= 's';
    }

    $archived_pc_query .= " ORDER BY terminal_number ASC LIMIT ? OFFSET ?";
    $archived_pc_params = array_merge($archived_pc_params, [$archived_pc_limit, $archived_pc_offset]);
    $archived_pc_types .= 'ii';

    $archived_pc_stmt = $conn->prepare($archived_pc_query);
    $archived_pc_stmt->bind_param($archived_pc_types, ...$archived_pc_params);
    $archived_pc_stmt->execute();
    $archived_pc_result = $archived_pc_stmt->get_result();

    while ($pc_row = $archived_pc_result->fetch_assoc()) {
        $notes = $pc_row['notes'] ?? '';
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

        $archived_pc_units[] = [
            'id' => $pc_row['id'],
            'terminal_number' => $pc_row['terminal_number'],
            'pc_name' => 'WORKSTATION-' . str_pad(substr($pc_row['terminal_number'], -2), 2, '0', STR_PAD_LEFT),
            'asset_tag' => 'COMP-' . $room['name'] . '-' . str_pad($pc_row['id'], 3, '0', STR_PAD_LEFT),
            'condition' => isset($pc_row['condition']) ? $pc_row['condition'] : 'N/A',
            'cpu' => $cpu,
            'ram' => $ram,
            'storage' => $storage,
            'last_online' => $pc_row['updated_at'],
            'health_status' => $health_status,
            'notes' => $notes
        ];
    }
    $archived_pc_stmt->close();
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
                            <span class="text-sm font-medium text-gray-500">Archived PC Units</span>
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
                <button class="px-4 py-3 text-sm font-medium transition-all duration-200 border-b-2 border-red-500 text-red-600 bg-red-50 flex items-center justify-center cursor-default">
                    <i class="fa-solid fa-archive mr-2"></i>Archived PC
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-red-500 text-white rounded-full"><?php echo $total_archived_pc; ?></span>
                </button>
                <a href="archived_assets.php?room_id=<?php echo $room_id; ?>" 
                   class="px-4 py-3 text-sm font-medium transition-all duration-200 border-b-2 border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 flex items-center justify-center">
                    <i class="fa-solid fa-archive mr-2"></i>Archived Assets
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-red-500 text-white rounded-full"><?php echo $total_archived_assets; ?></span>
                </a>
            </div>
        </div>

        <!-- Archived PC Units Table -->
        <div class="bg-white rounded shadow-sm border border-gray-200">
            <!-- Search Bar -->
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <input type="text" id="search-input" placeholder="Search by terminal number..." 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    </div>
                    <?php if (!empty($search)): ?>
                    <a href="?room_id=<?php echo $room_id; ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <i class="fa-solid fa-times mr-1"></i>Clear
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="px-4 py-3 bg-gradient-to-r from-red-50 to-red-100 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-desktop text-red-600"></i>
                    <h4 class="text-sm font-semibold text-gray-800">Archived PC Units</h4>
                    <span class="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 rounded-full">
                        <?php echo $total_archived_pc; ?> Total
                    </span>
                </div>
                <div id="archived-pc-bulk-actions" class="hidden flex items-center gap-2">
                    <button onclick="bulkRestoreArchivedPCUnits()" class="px-3 py-1.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded transition-colors">
                        <i class="fa-solid fa-rotate-left mr-1"></i>Restore Selected
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                <input type="checkbox" id="select-all-archived-pc" class="rounded border-gray-300 text-red-600 focus:ring-red-600">
                            </th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terminal</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PC Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Tag</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Online</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($archived_pc_units)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fa-solid fa-archive text-5xl mb-3 opacity-30"></i>
                                    <p class="text-lg">No archived PC units</p>
                                    <p class="text-sm">Archived PC units will appear here</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($archived_pc_units as $pc): ?>
                                <tr class="hover:bg-red-50 transition-colors cursor-pointer" onclick="window.location.href='pcassets.php?pc_unit_id=<?php echo $pc['id']; ?>'">
                                    <td class="px-4 py-3 whitespace-nowrap text-center" onclick="event.stopPropagation()">
                                        <input type="checkbox" class="archived-pc-checkbox rounded border-gray-300 text-red-600 focus:ring-red-600" value="<?php echo $pc['id']; ?>">
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-red-600"><?php echo htmlspecialchars($pc['terminal_number']); ?></span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($pc['pc_name']); ?></span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-sm text-gray-600"><?php echo htmlspecialchars($pc['asset_tag']); ?></span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-700">
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
                                                    <button onclick="openComponentsModal(<?php echo $pc['id']; ?>, '<?php echo htmlspecialchars($pc['terminal_number'], ENT_QUOTES); ?>')" 
                                                       class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                        <i class="fa-solid fa-microchip text-red-600"></i> View Components
                                                    </button>
                                                    <button onclick="openRestoreModal(<?php echo $pc['id']; ?>, '<?php echo htmlspecialchars($pc['terminal_number'], ENT_QUOTES); ?>')" 
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
            <?php if ($total_archived_pc_pages > 1): ?>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <?php echo min(($archived_pc_page - 1) * $archived_pc_limit + 1, $total_archived_pc); ?> to <?php echo min($archived_pc_page * $archived_pc_limit, $total_archived_pc); ?> of <?php echo $total_archived_pc; ?> archived PC units
                </div>
                <div class="flex items-center space-x-1">
                    <?php if ($archived_pc_page > 1): ?>
                    <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $archived_pc_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-1 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <i class="fa-solid fa-chevron-left mr-1"></i>Previous
                    </a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $archived_pc_page - 2);
                    $end_page = min($total_archived_pc_pages, $archived_pc_page + 2);
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-1 text-sm font-medium <?php echo $i === $archived_pc_page ? 'text-blue-600 bg-blue-50 border-blue-500' : 'text-gray-500 bg-white border-gray-300'; ?> border rounded-md hover:bg-gray-50">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($archived_pc_page < $total_archived_pc_pages): ?>
                    <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $archived_pc_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
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

<!-- Components Modal -->
<div id="componentsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4 pb-3 border-b">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fa-solid fa-microchip text-red-600 mr-2"></i>
                PC Components - <span id="modalPCName"></span>
            </h3>
            <button onclick="closeComponentsModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>
        <div id="componentsContent" class="overflow-y-auto max-h-96">
            <div class="flex items-center justify-center py-8">
                <i class="fa-solid fa-spinner fa-spin text-3xl text-gray-400"></i>
            </div>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div id="restoreModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 rounded-full mb-4">
                <i class="fa-solid fa-rotate-left text-green-600 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Restore PC Unit?</h3>
            <p class="text-sm text-gray-600 text-center mb-4">
                Are you sure you want to restore <strong id="restorePCName"></strong>?
            </p>
            <p class="text-xs text-gray-500 text-center mb-6">
                The PC unit will be available again.
            </p>
            <div class="flex gap-3">
                <button onclick="closeRestoreModal()" 
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button id="confirmRestoreBtn" onclick="confirmRestore()" 
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md transition-colors">
                    <i class="fa-solid fa-rotate-left mr-1"></i>Restore
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// PC Unit Kebab Menu Functions
function togglePCMenu(id) {
    event.stopPropagation();
    const menu = document.getElementById('pc-menu-' + id);
    const allMenus = document.querySelectorAll('[id^="pc-menu-"]');
    allMenus.forEach(m => {
        if (m !== menu) m.classList.add('hidden');
    });
    menu.classList.toggle('hidden');
}

// Close menus when clicking outside
document.addEventListener('click', function(event) {
    const menus = document.querySelectorAll('[id^="pc-menu-"]');
    menus.forEach(menu => {
        if (!menu.contains(event.target) && !event.target.closest('[onclick*="togglePCMenu"]')) {
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
        window.location.href = url.toString();
    }, 1000);
});

// Bulk actions for Archived PC Units
function bulkRestoreArchivedPCUnits() {
    const selectedIds = Array.from(document.querySelectorAll('.archived-pc-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) {
        alert('Please select PC units to restore');
        return;
    }

    const pcTags = selectedIds.map(id => {
        const pc = <?php echo json_encode($archived_pc_units); ?>.find(p => p.id == id);
        return pc ? pc.terminal_number : id;
    });

    if (!confirm(`Are you sure you want to restore ${selectedIds.length} PC unit(s)?\n\nPC Units: ${pcTags.join(', ')}\n\nRestored PC units will be available again.`)) {
        return;
    }

    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Restoring...';
    button.disabled = true;

    fetch('../../controller/restore_pc_units.php', {
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
            showAlert('success', `Successfully restored ${selectedIds.length} PC unit(s)`);
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', data.message || 'Failed to restore PC units');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while restoring PC units');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Restore Modal Functions
let currentRestorePCId = null;

function openRestoreModal(id, terminalNumber) {
    currentRestorePCId = id;
    const modal = document.getElementById('restoreModal');
    const pcName = document.getElementById('restorePCName');
    
    pcName.textContent = terminalNumber;
    modal.classList.remove('hidden');
}

function closeRestoreModal() {
    const modal = document.getElementById('restoreModal');
    modal.classList.add('hidden');
    currentRestorePCId = null;
}

function confirmRestore() {
    if (!currentRestorePCId) return;
    
    const button = document.getElementById('confirmRestoreBtn');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Restoring...';
    button.disabled = true;
    
    fetch('../../controller/restore_pc_unit.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: currentRestorePCId,
            room_id: <?php echo $room_id; ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'PC unit restored successfully!');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', data.message || 'Failed to restore PC unit');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while restoring the PC unit');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Close modal when clicking outside
document.getElementById('restoreModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeRestoreModal();
    }
});

// Select all functionality
document.getElementById('select-all-archived-pc')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.archived-pc-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    toggleArchivedPCBulkActions();
});

// Individual checkbox change
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('archived-pc-checkbox')) {
        updateSelectAllArchivedPC();
        toggleArchivedPCBulkActions();
    }
});

function updateSelectAllArchivedPC() {
    const allCheckboxes = document.querySelectorAll('.archived-pc-checkbox');
    const checkedCheckboxes = document.querySelectorAll('.archived-pc-checkbox:checked');
    const selectAllCheckbox = document.getElementById('select-all-archived-pc');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
    }
}

function toggleArchivedPCBulkActions() {
    const checkedCheckboxes = document.querySelectorAll('.archived-pc-checkbox:checked');
    const bulkActions = document.getElementById('archived-pc-bulk-actions');
    
    if (bulkActions) {
        if (checkedCheckboxes.length > 0) {
            bulkActions.classList.remove('hidden');
        } else {
            bulkActions.classList.add('hidden');
        }
    }
}

// Components Modal Functions
function openComponentsModal(pcId, terminalNumber) {
    const modal = document.getElementById('componentsModal');
    const modalPCName = document.getElementById('modalPCName');
    const componentsContent = document.getElementById('componentsContent');
    
    modalPCName.textContent = terminalNumber;
    modal.classList.remove('hidden');
    
    // Show loading state
    componentsContent.innerHTML = `
        <div class="flex items-center justify-center py-8">
            <i class="fa-solid fa-spinner fa-spin text-3xl text-gray-400"></i>
        </div>
    `;
    
    // Fetch components data
    fetch('../../controller/get_pc_details.php?pc_unit_id=' + pcId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.components && data.components.length > 0) {
                let html = '<div class="space-y-3">';
                data.components.forEach(component => {
                    html += `
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-700">
                                            ${component.component_type || 'N/A'}
                                        </span>
                                        <span class="text-sm font-medium text-gray-900">
                                            ${component.brand || 'N/A'} ${component.model || ''}
                                        </span>
                                    </div>
                                    ${component.specifications ? `
                                        <p class="text-sm text-gray-600 mb-1">
                                            <i class="fa-solid fa-info-circle mr-1"></i>
                                            ${component.specifications}
                                        </p>
                                    ` : ''}
                                    ${component.serial_number ? `
                                        <p class="text-xs text-gray-500">
                                            <i class="fa-solid fa-barcode mr-1"></i>
                                            S/N: ${component.serial_number}
                                        </p>
                                    ` : ''}
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded ${
                                    component.condition === 'Excellent' ? 'bg-green-100 text-green-700' :
                                    component.condition === 'Good' ? 'bg-blue-100 text-blue-700' :
                                    component.condition === 'Fair' ? 'bg-yellow-100 text-yellow-700' :
                                    'bg-red-100 text-red-700'
                                }">
                                    ${component.condition || 'N/A'}
                                </span>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                componentsContent.innerHTML = html;
            } else {
                componentsContent.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fa-solid fa-microchip text-5xl mb-3 opacity-30"></i>
                        <p class="text-lg">No components found</p>
                        <p class="text-sm">This PC unit has no registered components</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            componentsContent.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <i class="fa-solid fa-exclamation-circle text-5xl mb-3 opacity-30"></i>
                    <p class="text-lg">Error loading components</p>
                    <p class="text-sm">Please try again later</p>
                </div>
            `;
        });
}

function closeComponentsModal() {
    const modal = document.getElementById('componentsModal');
    modal.classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('componentsModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeComponentsModal();
    }
});

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