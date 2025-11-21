<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has technician role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Technician') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
require_once '../../model/Asset.php';
require_once '../../model/Database.php';

// Handle AJAX request for updating asset condition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_condition') {
    header('Content-Type: application/json');
    
    $asset_id = intval($_POST['asset_id'] ?? 0);
    $condition = $_POST['condition'] ?? '';
    
    if ($asset_id <= 0 || empty($condition)) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("UPDATE assets SET `condition` = ?, updated_at = NOW() WHERE id = ?");
        $success = $stmt->execute([$condition, $asset_id]);
        
        if ($success) {
            // Log the activity
            if (class_exists('ActivityLog')) {
                require_once '../../model/ActivityLog.php';
                ActivityLog::record(
                    $_SESSION['user_id'],
                    'update',
                    'asset',
                    $asset_id,
                    "Changed asset condition to: {$condition}"
                );
            }
            
            echo json_encode(['success' => true, 'message' => 'Condition updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update condition']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

$assetModel = new Asset();
$assets = $assetModel->getAll();

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
        <div class="flex items-center justify-between px-3 py-2 bg-white rounded shadow-sm border border-gray-200 mb-2">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">Asset Registry</h3>
                <p class="text-[10px] text-gray-500 mt-0.5">View and update asset conditions</p>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Search -->
                <div class="relative">
                    <input id="searchAssets" type="search" placeholder="Search assets..." 
                        class="w-48 pl-8 pr-3 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
                    <i class="fas fa-search absolute left-2.5 top-2 text-gray-400 text-xs"></i>
                </div>
                
                <!-- Filter Button -->
                <div class="relative">
                    <button id="filterBtn" onclick="toggleFilterMenu()" 
                        class="px-2 py-1.5 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
                        <i class="fas fa-filter text-gray-600 text-xs"></i>
                    </button>
                    
                    <!-- Filter Dropdown -->
                    <div id="filterMenu" class="hidden absolute right-0 mt-1 w-48 bg-white border border-gray-200 rounded shadow-lg z-50">
                        <div class="p-2">
                            <h4 class="text-xs font-semibold text-gray-700 mb-2">Filter Assets</h4>
                            
                            <div class="mb-2">
                                <label class="block text-[10px] text-gray-600 mb-1">Status</label>
                                <select id="statusFilter" onchange="applyFilters()" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                                    <option value="">All Status</option>
                                    <option value="Available">Available</option>
                                    <option value="In Use">In Use</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Retired">Retired</option>
                                    <option value="Damaged">Damaged</option>
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <label class="block text-[10px] text-gray-600 mb-1">Condition</label>
                                <select id="conditionFilter" onchange="applyFilters()" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                                    <option value="">All Conditions</option>
                                    <option value="Excellent">Excellent</option>
                                    <option value="Good">Good</option>
                                    <option value="Fair">Fair</option>
                                    <option value="Poor">Poor</option>
                                    <option value="Broken">Broken</option>
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <label class="block text-[10px] text-gray-600 mb-1">Room</label>
                                <input type="text" id="roomFilter" onkeyup="applyFilters()" placeholder="Filter by room..." 
                                    class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            
                            <button onclick="clearFilters()" class="w-full px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded">
                                Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assets Table -->
        <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#1E3A8A] text-white sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Asset Tag</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Asset Name</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Type</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Room</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Status</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Condition</th>
                        <th class="px-3 py-2 text-center text-[10px] font-medium uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if (empty($assets)): ?>
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-xs text-gray-500">
                                <i class="fas fa-inbox text-2xl text-gray-300 mb-2"></i>
                                <p>No assets found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assets as $asset): ?>
                            <tr class="asset-row hover:bg-blue-50 transition" 
                                data-asset-id="<?php echo $asset['id']; ?>"
                                data-status="<?php echo htmlspecialchars($asset['status']); ?>"
                                data-condition="<?php echo htmlspecialchars($asset['condition']); ?>"
                                data-room="<?php echo htmlspecialchars($asset['room_name'] ?? ''); ?>">
                                <td class="px-3 py-2 text-xs font-medium text-gray-900">
                                    <?php echo htmlspecialchars($asset['asset_tag']); ?>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-700">
                                    <div class="font-medium"><?php echo htmlspecialchars($asset['asset_name']); ?></div>
                                    <?php if (!empty($asset['brand']) || !empty($asset['model'])): ?>
                                        <div class="text-[10px] text-gray-500">
                                            <?php echo htmlspecialchars(trim(($asset['brand'] ?? '') . ' ' . ($asset['model'] ?? ''))); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-700">
                                    <?php echo htmlspecialchars($asset['asset_type']); ?>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-700">
                                    <?php echo htmlspecialchars($asset['room_name'] ?? '-'); ?>
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    <?php
                                    $status = $asset['status'];
                                    $statusColors = [
                                        'Available' => 'bg-green-100 text-green-700',
                                        'In Use' => 'bg-blue-100 text-blue-700',
                                        'Maintenance' => 'bg-yellow-100 text-yellow-700',
                                        'Retired' => 'bg-gray-100 text-gray-700',
                                        'Damaged' => 'bg-red-100 text-red-700'
                                    ];
                                    $statusClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="inline-flex px-1.5 py-0.5 rounded-full text-[10px] font-medium <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    <span class="condition-badge inline-flex px-1.5 py-0.5 rounded-full text-[10px] font-medium <?php
                                        $condition = $asset['condition'];
                                        $conditionColors = [
                                            'Excellent' => 'bg-green-100 text-green-800',
                                            'Good' => 'bg-blue-100 text-blue-800',
                                            'Fair' => 'bg-yellow-100 text-yellow-800',
                                            'Poor' => 'bg-orange-100 text-orange-800',
                                            'Broken' => 'bg-red-100 text-red-800'
                                        ];
                                        echo $conditionColors[$condition] ?? 'bg-gray-100 text-gray-700';
                                    ?>">
                                        <?php echo htmlspecialchars($condition); ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center text-xs">
                                    <button onclick="openConditionModal(<?php echo $asset['id']; ?>, '<?php echo htmlspecialchars($asset['asset_tag']); ?>', '<?php echo htmlspecialchars($asset['condition']); ?>')" 
                                        class="px-2 py-1 bg-[#1E3A8A] text-white rounded hover:bg-blue-700 transition text-[10px]"
                                        title="Update Condition">
                                        <i class="fas fa-edit mr-1"></i>Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Update Condition Modal -->
<div id="conditionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="bg-[#1E3A8A] text-white p-4 rounded-t-lg">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold flex items-center gap-2">
                    <i class="fas fa-wrench"></i>
                    Update Asset Condition
                </h3>
                <button onclick="closeConditionModal()" class="text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="p-4">
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-700 mb-1">Asset Tag</label>
                <input type="text" id="modalAssetTag" readonly 
                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded bg-gray-50">
            </div>
            
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-700 mb-1">Current Condition</label>
                <input type="text" id="modalCurrentCondition" readonly 
                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded bg-gray-50">
            </div>
            
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-700 mb-1">New Condition <span class="text-red-500">*</span></label>
                <select id="modalNewCondition" 
                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
                    <option value="">Select condition...</option>
                    <option value="Excellent">Excellent</option>
                    <option value="Good">Good</option>
                    <option value="Fair">Fair</option>
                    <option value="Poor">Poor</option>
                    <option value="Broken">Broken</option>
                </select>
            </div>
            
            <div class="flex gap-2">
                <button onclick="closeConditionModal()" 
                    class="flex-1 px-3 py-2 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded transition">
                    Cancel
                </button>
                <button onclick="updateCondition()" 
                    class="flex-1 px-3 py-2 text-xs bg-[#1E3A8A] hover:bg-blue-700 text-white rounded transition">
                    <i class="fas fa-check mr-1"></i>Update
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentAssetId = null;
let allAssetRows = [];

// Filter menu toggle
function toggleFilterMenu() {
    const menu = document.getElementById('filterMenu');
    menu.classList.toggle('hidden');
}

// Close filter menu when clicking outside
document.addEventListener('click', function(e) {
    const filterBtn = document.getElementById('filterBtn');
    const filterMenu = document.getElementById('filterMenu');
    
    if (filterBtn && filterMenu && !filterBtn.contains(e.target) && !filterMenu.contains(e.target)) {
        filterMenu.classList.add('hidden');
    }
});

function applyFilters() {
    const searchQuery = document.getElementById('searchAssets').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const conditionFilter = document.getElementById('conditionFilter').value;
    const roomFilter = document.getElementById('roomFilter').value.toLowerCase();
    
    allAssetRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const status = row.dataset.status || '';
        const condition = row.dataset.condition || '';
        const room = row.dataset.room || '';
        
        const matchesSearch = !searchQuery || text.includes(searchQuery);
        const matchesStatus = !statusFilter || status === statusFilter;
        const matchesCondition = !conditionFilter || condition === conditionFilter;
        const matchesRoom = !roomFilter || room.toLowerCase().includes(roomFilter);
        
        if (matchesSearch && matchesStatus && matchesCondition && matchesRoom) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function clearFilters() {
    document.getElementById('searchAssets').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('conditionFilter').value = '';
    document.getElementById('roomFilter').value = '';
    applyFilters();
}

// Search functionality
document.getElementById('searchAssets')?.addEventListener('input', applyFilters);

function openConditionModal(assetId, assetTag, currentCondition) {
    currentAssetId = assetId;
    document.getElementById('modalAssetTag').value = assetTag;
    document.getElementById('modalCurrentCondition').value = currentCondition;
    document.getElementById('modalNewCondition').value = '';
    document.getElementById('conditionModal').classList.remove('hidden');
}

function closeConditionModal() {
    document.getElementById('conditionModal').classList.add('hidden');
    currentAssetId = null;
}

// Close modal when clicking outside
document.getElementById('conditionModal')?.addEventListener('click', function(e) {
    if (e.target.id === 'conditionModal') {
        closeConditionModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('conditionModal');
        if (modal && !modal.classList.contains('hidden')) {
            closeConditionModal();
        }
    }
});

async function updateCondition() {
    const newCondition = document.getElementById('modalNewCondition').value;
    
    if (!newCondition) {
        showToast('Please select a condition', false);
        return;
    }
    
    if (!currentAssetId) {
        showToast('Invalid asset ID', false);
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'update_condition');
        formData.append('asset_id', currentAssetId);
        formData.append('condition', newCondition);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, true);
            
            // Update the UI
            const row = document.querySelector(`tr[data-asset-id="${currentAssetId}"]`);
            if (row) {
                row.dataset.condition = newCondition;
                const badge = row.querySelector('.condition-badge');
                if (badge) {
                    const conditionColors = {
                        'Excellent': 'bg-green-100 text-green-800',
                        'Good': 'bg-blue-100 text-blue-800',
                        'Fair': 'bg-yellow-100 text-yellow-800',
                        'Poor': 'bg-orange-100 text-orange-800',
                        'Broken': 'bg-red-100 text-red-800'
                    };
                    badge.className = 'condition-badge inline-flex px-1.5 py-0.5 rounded-full text-[10px] font-medium ' + 
                        (conditionColors[newCondition] || 'bg-gray-100 text-gray-700');
                    badge.textContent = newCondition;
                }
            }
            
            closeConditionModal();
        } else {
            showToast(result.message || 'Failed to update condition', false);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', false);
    }
}

function showToast(message, success = true) {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-4 py-2 rounded shadow-lg z-50 text-xs ${success ? 'bg-green-600' : 'bg-red-600'} text-white`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    allAssetRows = Array.from(document.querySelectorAll('.asset-row'));
});
</script>

<?php include '../components/layout_footer.php'; ?>
