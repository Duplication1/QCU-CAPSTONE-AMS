<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
require_once '../../model/PCUnit.php';
require_once '../../model/Room.php';

$pcUnitModel = new PCUnit();
$roomModel = new Room();

// Get all rooms and PC units
$rooms = $roomModel->getAll();
$pcUnits = $pcUnitModel->getAll();

include '../components/layout_header.php';
?>

<style>
    .component-card {
        transition: all 0.3s ease;
    }
    .component-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
</style>

<!-- Main Content -->
<main class="p-4 sm:p-6 lg:p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">PC Components Inventory</h1>
        <p class="text-gray-600">View detailed component information for each PC</p>
    </div>

    <!-- Filter by Room -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Filter by Room:</label>
                <select id="roomFilter" class="w-full sm:w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Rooms</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button onclick="openAddPCModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>Add PC Unit
                </button>
            </div>
        </div>
    </div>

    <!-- PC Units Grid -->
    <div id="pcUnitsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($pcUnits as $pcUnit): ?>
            <div class="pc-unit-card bg-white rounded-lg shadow-md hover:shadow-xl transition cursor-pointer border-2 border-gray-200 hover:border-blue-400" 
                 data-room-id="<?php echo $pcUnit['room_id']; ?>"
                 onclick="viewPCDetails(<?php echo $pcUnit['id']; ?>)">
                <div class="p-6">
                    <!-- PC Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($pcUnit['terminal_number']); ?></h3>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($pcUnit['room_name']); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-desktop text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    
                    <!-- PC Name -->
                    <?php if ($pcUnit['pc_name']): ?>
                        <p class="text-sm font-medium text-gray-700 mb-2"><?php echo htmlspecialchars($pcUnit['pc_name']); ?></p>
                    <?php endif; ?>
                    
                    <!-- Status Badge -->
                    <div class="mb-3">
                        <?php
                        $statusColors = [
                            'Active' => 'bg-green-100 text-green-800',
                            'Inactive' => 'bg-gray-100 text-gray-800',
                            'Under Maintenance' => 'bg-yellow-100 text-yellow-800',
                            'Disposed' => 'bg-red-100 text-red-800'
                        ];
                        $statusClass = $statusColors[$pcUnit['status']] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($pcUnit['status']); ?>
                        </span>
                    </div>
                    
                    <!-- Asset Tag -->
                    <?php if ($pcUnit['asset_tag']): ?>
                        <p class="text-xs text-gray-500 mb-3">
                            <i class="fas fa-tag mr-1"></i><?php echo htmlspecialchars($pcUnit['asset_tag']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <!-- View Details Button -->
                    <button class="w-full bg-blue-50 hover:bg-blue-100 text-blue-700 py-2 rounded-lg transition text-sm font-medium">
                        <i class="fas fa-info-circle mr-2"></i>View Components
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($pcUnits)): ?>
        <div class="text-center py-12 bg-white rounded-lg shadow-md">
            <i class="fas fa-desktop text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-600 mb-4">No PC units found</p>
            <button onclick="openAddPCModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition">
                <i class="fas fa-plus mr-2"></i>Add Your First PC Unit
            </button>
        </div>
    <?php endif; ?>
</main>

<!-- PC Details Modal -->
<div id="pcDetailsModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden mx-4">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold" id="modalPCName">PC Details</h2>
                    <p class="text-blue-100 text-sm" id="modalPCLocation"></p>
                </div>
                <button onclick="closePCDetailsModal()" class="text-white hover:text-gray-200 text-3xl">&times;</button>
            </div>
        </div>
        
        <!-- Modal Body -->
        <div class="overflow-y-auto" style="max-height: calc(90vh - 180px);">
            <div class="p-6">
                <!-- PC Info -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="font-semibold text-gray-700">Terminal:</span>
                            <span id="modalTerminal" class="text-gray-900 ml-2"></span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-700">Status:</span>
                            <span id="modalStatus" class="ml-2"></span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-700">Asset Tag:</span>
                            <span id="modalAssetTag" class="text-gray-900 ml-2"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Components List -->
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Components</h3>
                    <button onclick="openAddComponentModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition">
                        <i class="fas fa-plus mr-2"></i>Add Component
                    </button>
                </div>
                
                <div id="componentsList" class="space-y-3">
                    <!-- Components will be loaded here -->
                </div>
            </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="bg-gray-50 p-4 flex justify-end gap-3 border-t">
            <button onclick="closePCDetailsModal()" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">
                Close
            </button>
        </div>
    </div>
</div>

<script>
let currentPCId = null;

// Filter PCs by room
document.getElementById('roomFilter').addEventListener('change', function() {
    const roomId = this.value;
    const pcCards = document.querySelectorAll('.pc-unit-card');
    
    pcCards.forEach(card => {
        if (roomId === '' || card.dataset.roomId === roomId) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});

// View PC details with components
function viewPCDetails(pcId) {
    currentPCId = pcId;
    
    fetch(`../../controller/get_pc_details.php?id=${pcId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const pc = data.data;
                
                // Update modal header
                document.getElementById('modalPCName').textContent = pc.pc_name || pc.terminal_number;
                document.getElementById('modalPCLocation').textContent = `${pc.room_name} - ${pc.terminal_number}`;
                document.getElementById('modalTerminal').textContent = pc.terminal_number;
                document.getElementById('modalAssetTag').textContent = pc.asset_tag || 'N/A';
                
                // Update status with color
                const statusColors = {
                    'Active': 'bg-green-100 text-green-800',
                    'Inactive': 'bg-gray-100 text-gray-800',
                    'Under Maintenance': 'bg-yellow-100 text-yellow-800',
                    'Disposed': 'bg-red-100 text-red-800'
                };
                const statusClass = statusColors[pc.status] || 'bg-gray-100 text-gray-800';
                document.getElementById('modalStatus').innerHTML = `<span class="inline-block px-3 py-1 text-xs font-semibold rounded-full ${statusClass}">${pc.status}</span>`;
                
                // Display components
                const componentsList = document.getElementById('componentsList');
                if (pc.components && pc.components.length > 0) {
                    componentsList.innerHTML = pc.components.map(component => `
                        <div class="component-card bg-white border-2 border-gray-200 rounded-lg p-4 hover:border-blue-400 transition">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-semibold">
                                            ${component.component_type}
                                        </span>
                                        <span class="status-${component.status.toLowerCase()} px-2 py-1 rounded text-xs font-medium">
                                            ${component.status}
                                        </span>
                                    </div>
                                    <h4 class="font-bold text-gray-800 text-lg mb-1">${component.component_name}</h4>
                                    <p class="text-sm text-gray-600 mb-2">${component.brand || ''} ${component.model || ''}</p>
                                    ${component.specifications ? `<p class="text-sm text-gray-700 mb-2">${component.specifications}</p>` : ''}
                                    ${component.serial_number ? `<p class="text-xs text-gray-500"><i class="fas fa-barcode mr-1"></i>${component.serial_number}</p>` : ''}
                                </div>
                                <div class="text-right">
                                    ${component.purchase_cost ? `<p class="text-sm font-semibold text-gray-800">₱${parseFloat(component.purchase_cost).toLocaleString()}</p>` : ''}
                                    ${component.condition ? `<span class="text-xs text-gray-600">${component.condition}</span>` : ''}
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    componentsList.innerHTML = '<p class="text-center text-gray-500 py-8">No components found for this PC</p>';
                }
                
                // Show modal
                document.getElementById('pcDetailsModal').classList.remove('hidden');
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to load PC details', 'error');
        });
}

function closePCDetailsModal() {
    document.getElementById('pcDetailsModal').classList.add('hidden');
    currentPCId = null;
}

function openAddPCModal() {
    showNotification('Add PC functionality - To be implemented', 'info');
}

function openAddComponentModal() {
    showNotification('Add Component functionality - To be implemented', 'info');
}

// Close modal when clicking outside
document.getElementById('pcDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePCDetailsModal();
    }
});
</script>

<style>
.status-working { background-color: #d1fae5; color: #065f46; }
.status-faulty { background-color: #fee2e2; color: #991b1b; }
.status-replaced { background-color: #fef3c7; color: #92400e; }
.status-disposed { background-color: #e5e7eb; color: #374151; }
</style>

<?php include '../components/layout_footer.php'; ?>
