<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
require_once '../../model/Asset.php';
require_once '../../model/Room.php';

$assetModel = new Asset();
$roomModel = new Room();

// Get all rooms with asset counts
$rooms = $roomModel->getRoomAssetCounts();

// Get all assets
$assets = $assetModel->getAll();

include '../components/layout_header.php';
?>

<style>
    .lab-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .lab-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.3s;
    }
    .modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modal-content {
        background-color: #fefefe;
        padding: 0;
        border-radius: 12px;
        width: 90%;
        max-width: 1200px;
        max-height: 90vh;
        overflow-y: auto;
        animation: slideIn 0.3s;
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideIn {
        from { transform: translateY(-50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-available { background-color: #d1fae5; color: #065f46; }
    .status-in-use { background-color: #dbeafe; color: #1e40af; }
    .status-maintenance { background-color: #fef3c7; color: #92400e; }
    .status-retired { background-color: #f3f4f6; color: #374151; }
    .status-damaged { background-color: #fee2e2; color: #991b1b; }
    
    .pc-terminal-card {
        transition: all 0.3s ease;
    }
    .pc-terminal-card:hover {
        transform: translateY(-3px);
    }
    .pc-terminal-card:active {
        transform: translateY(0);
    }
    
    .tab-button {
        padding: 0.5rem 1rem;
        border: none;
        background: transparent;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.75rem;
        color: #6b7280;
        border-bottom: 2px solid transparent;
        transition: all 0.3s;
    }
    .tab-button.active {
        color: #1E3A8A;
        border-bottom-color: #1E3A8A;
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
</style>

<!-- Main Content -->
<main class="p-3">
    <!-- Session Messages -->
    <?php include '../components/session_messages.php'; ?>

    <!-- Tab Navigation -->
    <div class="bg-white rounded-lg shadow mb-3">
        <div class="border-b border-gray-200 flex">
            <button class="tab-button active" data-tab="labs">
                <i class="fas fa-building mr-2"></i>Computer Labs
            </button>
            <button class="tab-button" data-tab="all-assets">
                <i class="fas fa-list mr-2"></i>All Assets
            </button>
            <button class="tab-button" data-tab="import">
                <i class="fas fa-upload mr-2"></i>Import Assets
            </button>
        </div>

        <!-- Computer Labs Tab -->
        <div id="labs-tab" class="tab-content active p-3">
            <div class="flex flex-wrap justify-between items-center mb-3 gap-2">
                <input type="text" id="searchLabs" placeholder="Search labs..." 
                       class="px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent"
                       onkeyup="filterLabs()">
                <button onclick="openAddAssetModal()" class="bg-[#1E3A8A] hover:bg-blue-900 text-white px-3 py-1.5 rounded text-xs transition">
                    <i class="fas fa-plus mr-1"></i>Add Asset
                </button>
            </div>

            <!-- Computer Labs Grid -->
            <div id="labsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
                <?php foreach ($rooms as $room): ?>
                    <div class="lab-card bg-white rounded-lg shadow p-3 border-l-4 border-[#1E3A8A]" 
                         data-lab-name="<?php echo strtolower(htmlspecialchars($room['name'])); ?>"
                         onclick="openLabModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['name']); ?>')">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-bold text-[#1E3A8A]"><?php echo htmlspecialchars($room['name']); ?></h3>
                            <div class="bg-blue-100 text-[#1E3A8A] rounded p-1.5">
                                <i class="fas fa-desktop text-sm"></i>
                            </div>
                        </div>
                        
                        <div class="space-y-1">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] text-gray-600">Total:</span>
                                <span class="text-xs font-bold text-gray-800"><?php echo $room['total_assets']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] text-gray-600">Available:</span>
                                <span class="text-xs font-semibold text-green-600"><?php echo $room['available']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] text-gray-600">In Use:</span>
                                <span class="text-xs font-semibold text-blue-600"><?php echo $room['in_use']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] text-gray-600">Maintenance:</span>
                                <span class="text-xs font-semibold text-yellow-600"><?php echo $room['maintenance']; ?></span>
                            </div>
                        </div>
                        
                        <button class="mt-2 w-full bg-[#1E3A8A] hover:bg-blue-900 text-white py-1.5 rounded text-xs transition">
                            View <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($rooms)): ?>
                    <div class="col-span-4 text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-5xl mb-3"></i>
                        <p class="text-xs">No computer labs found</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- No Results Message for Filtering -->
            <div id="noLabsFound" class="hidden text-center py-8">
                <i class="fas fa-search text-5xl text-gray-300 mb-3"></i>
                <p class="text-xs text-gray-600">No labs match your search</p>
            </div>
        </div>

        <!-- All Assets Tab -->
        <div id="all-assets-tab" class="tab-content p-3">
            <div class="flex flex-wrap items-center mb-3 gap-2">
                <input type="text" id="searchAssets" placeholder="Search assets..." 
                       class="px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent"
                       onkeyup="filterAssets()">
                
                <select id="filterStatus" class="px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent" onchange="filterAssets()">
                    <option value="">All Status</option>
                    <option value="available">Available</option>
                    <option value="in use">In Use</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="retired">Retired</option>
                    <option value="damaged">Damaged</option>
                </select>
                
                <select id="filterRoom" class="px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent" onchange="filterAssets()">
                    <option value="">All Rooms</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?php echo htmlspecialchars($room['name']); ?>"><?php echo htmlspecialchars($room['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select id="filterType" class="px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent" onchange="filterAssets()">
                    <option value="">All Types</option>
                    <option value="Desktop">Desktop</option>
                    <option value="Laptop">Laptop</option>
                    <option value="Monitor">Monitor</option>
                    <option value="Printer">Printer</option>
                    <option value="Projector">Projector</option>
                    <option value="Other">Other</option>
                </select>
                
                <button onclick="clearAssetFilters()" class="px-3 py-1.5 text-xs border border-gray-300 rounded hover:bg-gray-100 transition">
                    <i class="fas fa-times mr-1"></i>Clear
                </button>
                
                <div class="flex-1"></div>
                
                <div class="flex gap-2">
                    <button onclick="openAddAssetModal()" class="bg-[#1E3A8A] hover:bg-blue-900 text-white px-3 py-1.5 rounded text-xs transition">
                        <i class="fas fa-plus mr-1"></i>Add
                    </button>
                    <button onclick="exportAssets()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-xs transition">
                        <i class="fas fa-download mr-1"></i>Export
                    </button>
                </div>
            </div>

            <!-- Assets Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-300 rounded-lg" id="assetsTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-700 uppercase tracking-wider">Asset Tag</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-700 uppercase tracking-wider">Asset Name</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-700 uppercase tracking-wider">Type</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-700 uppercase tracking-wider">Category</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-700 uppercase tracking-wider">Room</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-700 uppercase tracking-wider">Condition</th>
                            <th class="px-3 py-2 text-center text-[10px] font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($assets as $asset): ?>
                            <tr class="hover:bg-gray-50 transition asset-row" 
                                data-asset-tag="<?php echo strtolower(htmlspecialchars($asset['asset_tag'])); ?>"
                                data-asset-name="<?php echo strtolower(htmlspecialchars($asset['asset_name'])); ?>"
                                data-asset-type="<?php echo strtolower(htmlspecialchars($asset['asset_type'])); ?>"
                                data-asset-category="<?php echo strtolower(htmlspecialchars($asset['category'] ?? '')); ?>"
                                data-asset-room="<?php echo strtolower(htmlspecialchars($asset['room_name'] ?? '')); ?>"
                                data-asset-status="<?php echo strtolower(htmlspecialchars($asset['status'])); ?>">
                                <td class="px-3 py-2 text-xs font-medium text-gray-900"><?php echo htmlspecialchars($asset['asset_tag']); ?></td>
                                <td class="px-3 py-2 text-xs text-gray-700"><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                                <td class="px-3 py-2 text-xs text-gray-700"><?php echo htmlspecialchars($asset['asset_type']); ?></td>
                                <td class="px-3 py-2 text-xs text-gray-700"><?php echo htmlspecialchars($asset['category'] ?? '-'); ?></td>
                                <td class="px-3 py-2 text-xs text-gray-700"><?php echo htmlspecialchars($asset['room_name'] ?? '-'); ?></td>
                                <td class="px-3 py-2 text-xs">
                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded status-<?php echo strtolower(str_replace(' ', '-', $asset['status'])); ?>">
                                        <?php echo htmlspecialchars($asset['status']); ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-700"><?php echo htmlspecialchars($asset['condition']); ?></td>
                                <td class="px-3 py-2 text-center">
                                    <button onclick="viewAssetDetails(<?php echo $asset['id']; ?>)" class="text-[#1E3A8A] hover:text-blue-700 mr-2" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editAsset(<?php echo $asset['id']; ?>)" class="text-yellow-600 hover:text-yellow-800 mr-2" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="generateQRCode(<?php echo $asset['id']; ?>)" class="text-purple-600 hover:text-purple-800 mr-2" title="QR Code">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                    <button onclick="deleteAsset(<?php echo $asset['id']; ?>)" class="text-red-600 hover:text-red-800" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($assets)): ?>
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-6xl mb-4"></i>
                                    <p class="text-xl">No assets found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Import Assets Tab -->
        <div id="import-tab" class="tab-content p-3">
            <div class="max-w-4xl mx-auto">
                <!-- CSV Import -->
                <div class="bg-gray-50 rounded-lg p-3 mb-3 border-2 border-dashed border-gray-300">
                    <div class="flex items-center mb-3">
                        <div class="bg-blue-100 rounded p-2 mr-3">
                            <i class="fas fa-file-csv text-[#1E3A8A] text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800">CSV Import</h4>
                            <p class="text-[10px] text-gray-600">Upload a CSV file to import multiple assets</p>
                        </div>
                    </div>
                    
                    <form id="csvImportForm" action="../../controller/import_assets.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Select CSV File</label>
                            <input type="file" name="csv_file" id="csvFile" accept=".csv" required
                                   class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                            <p class="text-[10px] text-gray-500 mt-1">Maximum file size: 5MB</p>
                        </div>
                        
                        <div class="flex gap-2">
                            <button type="submit" class="bg-[#1E3A8A] hover:bg-blue-900 text-white px-3 py-1.5 rounded text-xs transition">
                                <i class="fas fa-upload mr-2"></i>Upload and Import
                            </button>
                            <a href="../../controller/download_csv_template.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition">
                                <i class="fas fa-download mr-2"></i>Download Template
                            </a>
                        </div>
                    </form>
                </div>

                <!-- QR Code Scanning -->
                <div class="bg-gray-50 rounded-xl p-6 border-2 border-dashed border-gray-300">
                    <div class="flex items-center mb-4">
                        <div class="bg-purple-100 rounded-full p-3 mr-4">
                            <i class="fas fa-qrcode text-purple-600 text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-800">QR Code Scanner</h4>
                            <p class="text-gray-600 text-sm">Scan QR codes to quickly add or update asset information</p>
                        </div>
                    </div>
                    
                    <button onclick="openQRScanner()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition">
                        <i class="fas fa-camera mr-2"></i>Open QR Scanner
                    </button>
                </div>

                <!-- Import Instructions -->
                <div class="mt-6 bg-blue-50 border-l-4 border-blue-600 p-4 rounded">
                    <h5 class="font-bold text-blue-900 mb-2">Import Instructions:</h5>
                    <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                        <li>CSV file must include columns: asset_tag, asset_name, asset_type</li>
                        <li>Optional columns: category, brand, model, serial_number, room_id, status, condition</li>
                        <li>Download the template to see the correct format</li>
                        <li>QR codes can be scanned to auto-fill asset information</li>
                        <li>Make sure asset tags are unique</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Lab Assets Modal -->
<div id="labModal" class="modal">
    <div class="modal-content">
        <div class="bg-blue-600 text-white p-6 rounded-t-xl">
            <div class="flex justify-between items-center">
                <h3 class="text-2xl font-bold" id="labModalTitle">Lab Assets</h3>
                <button onclick="closeLabModal()" class="text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="p-3">
            <div id="labAssetsContent" class="overflow-x-auto">
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
                    <p class="text-gray-600 mt-4">Loading assets...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Asset Details Modal -->
<div id="assetDetailsModal" class="modal">
    <div class="modal-content max-w-3xl">
        <div class="bg-[#1E3A8A] text-white p-3 rounded-t-lg">
            <div class="flex justify-between items-center">
                <h3 class="text-sm font-bold">Asset Details</h3>
                <button onclick="closeAssetDetailsModal()" class="text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="p-3" id="assetDetailsContent">
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
                <p class="text-gray-600 mt-4">Loading details...</p>
            </div>
        </div>
    </div>
</div>

<!-- QR Scanner Modal -->
<div id="qrScannerModal" class="modal">
    <div class="modal-content max-w-2xl">
        <div class="bg-purple-600 text-white p-6 rounded-t-xl">
            <div class="flex justify-between items-center">
                <h3 class="text-2xl font-bold">QR Code Scanner</h3>
                <button onclick="closeQRScanner()" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="p-6">
            <div id="qrReader" class="mb-4"></div>
            <div id="qrResult" class="bg-gray-50 p-4 rounded-lg hidden">
                <h4 class="font-bold text-gray-800 mb-2">Scanned Data:</h4>
                <p id="qrResultText" class="text-gray-700"></p>
            </div>
        </div>
    </div>
</div>

<?php include 'add_edit_asset_modal.php'; ?>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    // Tab Switching
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
            const tabName = button.getAttribute('data-tab');
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab
            button.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        });
    });

    // Search Assets
    document.getElementById('searchAssets')?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#assetsTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Open Lab Modal
    function openLabModal(roomId, roomName) {
        document.getElementById('labModalTitle').textContent = roomName + ' - Assets';
        document.getElementById('labModal').classList.add('show');
        
        // Fetch room assets
        fetch(`../../controller/get_room_assets.php?room_id=${roomId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('API Response:', data); // Debug log
                if (data.success) {
                    // Store assets globally for navigation
                    window.currentRoomAssets = data.assets;
                    displayLabAssets(data.assets);
                } else {
                    document.getElementById('labAssetsContent').innerHTML = 
                        `<div class="text-center py-8 text-red-600">
                            <i class="fas fa-exclamation-circle text-4xl mb-4"></i>
                            <p class="font-semibold mb-2">Error Loading Assets</p>
                            <p class="text-sm">${data.message || 'Unknown error'}</p>
                        </div>`;
                }
            })
            .catch(error => {
                console.error('Fetch error:', error); // Debug log
                document.getElementById('labAssetsContent').innerHTML = 
                    `<div class="text-center py-8 text-red-600">
                        <i class="fas fa-exclamation-circle text-4xl mb-4"></i>
                        <p class="font-semibold mb-2">Error Loading Assets</p>
                        <p class="text-sm">${error.message}</p>
                        <p class="text-xs text-gray-500 mt-2">Check browser console for details</p>
                    </div>`;
            });
    }

    function closeLabModal() {
        document.getElementById('labModal').classList.remove('show');
    }

    function displayLabAssets(assets) {
        if (assets.length === 0) {
            document.getElementById('labAssetsContent').innerHTML = 
                '<div class="text-center py-8 text-gray-500"><i class="fas fa-inbox text-6xl mb-4"></i><p class="text-xl">No assets in this lab</p></div>';
            return;
        }

        // Separate PC assets (with terminal numbers) from other assets
        const pcAssets = {};
        const otherAssets = [];
        
        assets.forEach(asset => {
            if (asset.terminal_number && asset.terminal_number.trim() !== '') {
                const terminal = asset.terminal_number.trim();
                if (!pcAssets[terminal]) {
                    pcAssets[terminal] = [];
                }
                pcAssets[terminal].push(asset);
            } else {
                otherAssets.push(asset);
            }
        });

        let html = '<div class="space-y-6">';
        
        // PC Units Section
        if (Object.keys(pcAssets).length > 0) {
            html += '<div>';
            html += '<h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center">';
            html += '<i class="fas fa-desktop text-blue-600 mr-2"></i>PC Units';
            html += '</h4>';
            html += '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">';
            
            // Sort terminal numbers naturally (th-1, th-2, th-10, etc.)
            const sortedTerminals = Object.keys(pcAssets).sort((a, b) => {
                const numA = parseInt(a.replace(/\D/g, '')) || 0;
                const numB = parseInt(b.replace(/\D/g, '')) || 0;
                return numA - numB;
            });
            
            sortedTerminals.forEach(terminal => {
                const terminalAssets = pcAssets[terminal];
                const componentCount = terminalAssets.length;
                html += `
                    <div class="pc-terminal-card bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 cursor-pointer hover:shadow-lg transition border-2 border-blue-200 hover:border-blue-400"
                         onclick="viewTerminalAssets('${terminal}', ${JSON.stringify(terminalAssets).replace(/"/g, '&quot;')})">
                        <div class="text-center">
                            <div class="bg-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-computer text-xl"></i>
                            </div>
                            <div class="font-bold text-gray-800 text-sm">${terminal}</div>
                            <div class="text-xs text-gray-600 mt-1">${componentCount} asset${componentCount !== 1 ? 's' : ''}</div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div></div>';
        }
        
        // Other Assets Section
        if (otherAssets.length > 0) {
            html += '<div>';
            html += '<h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center">';
            html += '<i class="fas fa-boxes text-green-600 mr-2"></i>Other Assets';
            html += '</h4>';
            html += '<div class="overflow-x-auto">';
            html += '<table class="min-w-full bg-white border border-gray-300"><thead class="bg-gray-100"><tr>';
            html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Asset Tag</th>';
            html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Name</th>';
            html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Type</th>';
            html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Status</th>';
            html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Actions</th>';
            html += '</tr></thead><tbody class="divide-y divide-gray-200">';

            otherAssets.forEach(asset => {
                const statusClass = 'status-' + asset.status.toLowerCase().replace(' ', '-');
                html += `<tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-medium">${asset.asset_tag}</td>
                    <td class="px-4 py-3 text-sm">${asset.asset_name}</td>
                    <td class="px-4 py-3 text-sm">${asset.asset_type}</td>
                    <td class="px-4 py-3 text-sm"><span class="status-badge ${statusClass}">${asset.status}</span></td>
                    <td class="px-4 py-3 text-sm">
                        <button onclick="viewAssetDetails(${asset.id})" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-eye"></i></button>
                        <button onclick="editAsset(${asset.id})" class="text-yellow-600 hover:text-yellow-800 mr-2"><i class="fas fa-edit"></i></button>
                    </td>
                </tr>`;
            });

            html += '</tbody></table></div></div>';
        }
        
        html += '</div>';
        document.getElementById('labAssetsContent').innerHTML = html;
    }

    // View Terminal Assets (when clicking on a PC terminal number)
    function viewTerminalAssets(terminal, assets) {
        // Prevent event propagation
        event.stopPropagation();
        
        let html = '<div class="mb-4">';
        html += `<button onclick="displayLabAssets(window.currentRoomAssets)" class="text-blue-600 hover:text-blue-800 flex items-center mb-3">`;
        html += '<i class="fas fa-arrow-left mr-2"></i>Back to Room View';
        html += '</button>';
        html += `<h4 class="text-xl font-bold text-gray-800 mb-2">${terminal} - Components</h4>`;
        html += '</div>';
        
        html += '<div class="overflow-x-auto">';
        html += '<table class="min-w-full bg-white border border-gray-300"><thead class="bg-gray-100"><tr>';
        html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Asset Tag</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Name</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Type</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Category</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Brand</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Status</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Actions</th>';
        html += '</tr></thead><tbody class="divide-y divide-gray-200">';

        assets.forEach(asset => {
            const statusClass = 'status-' + asset.status.toLowerCase().replace(' ', '-');
            html += `<tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm font-medium">${asset.asset_tag}</td>
                <td class="px-4 py-3 text-sm">${asset.asset_name}</td>
                <td class="px-4 py-3 text-sm">${asset.asset_type}</td>
                <td class="px-4 py-3 text-sm">${asset.category || '-'}</td>
                <td class="px-4 py-3 text-sm">${asset.brand || '-'}</td>
                <td class="px-4 py-3 text-sm"><span class="status-badge ${statusClass}">${asset.status}</span></td>
                <td class="px-4 py-3 text-sm">
                    <button onclick="viewAssetDetails(${asset.id})" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-eye"></i></button>
                    <button onclick="editAsset(${asset.id})" class="text-yellow-600 hover:text-yellow-800 mr-2"><i class="fas fa-edit"></i></button>
                </td>
            </tr>`;
        });

        html += '</tbody></table></div>';
        document.getElementById('labAssetsContent').innerHTML = html;
    }

    // View Asset Details
    function viewAssetDetails(assetId) {
        document.getElementById('assetDetailsModal').classList.add('show');
        
        fetch(`../../controller/get_asset_details.php?id=${assetId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayAssetDetails(data.asset);
                } else {
                    document.getElementById('assetDetailsContent').innerHTML = 
                        '<div class="text-center py-8 text-red-600"><p>' + data.message + '</p></div>';
                }
            })
            .catch(error => {
                document.getElementById('assetDetailsContent').innerHTML = 
                    '<div class="text-center py-8 text-red-600"><p>Error loading details</p></div>';
            });
    }

    function closeAssetDetailsModal() {
        document.getElementById('assetDetailsModal').classList.remove('show');
    }

    function displayAssetDetails(asset) {
        let html = `
            <div class="grid grid-cols-2 gap-4">
                <div><strong>Asset Tag:</strong> ${asset.asset_tag}</div>
                <div><strong>Asset Name:</strong> ${asset.asset_name}</div>
                <div><strong>Type:</strong> ${asset.asset_type}</div>
                <div><strong>Category:</strong> ${asset.category || '-'}</div>
                <div><strong>Brand:</strong> ${asset.brand || '-'}</div>
                <div><strong>Model:</strong> ${asset.model || '-'}</div>
                <div><strong>Serial Number:</strong> ${asset.serial_number || '-'}</div>
                <div><strong>Room:</strong> ${asset.room_name || '-'}</div>
                <div><strong>Status:</strong> <span class="status-badge status-${asset.status.toLowerCase().replace(' ', '-')}">${asset.status}</span></div>
                <div><strong>Condition:</strong> ${asset.condition}</div>
                <div><strong>Purchase Date:</strong> ${asset.purchase_date || '-'}</div>
                <div><strong>Purchase Cost:</strong> ${asset.purchase_cost ? '₱' + asset.purchase_cost : '-'}</div>
                <div class="col-span-2"><strong>Specifications:</strong> ${asset.specifications || '-'}</div>
                <div class="col-span-2"><strong>Notes:</strong> ${asset.notes || '-'}</div>
            </div>
        `;
        document.getElementById('assetDetailsContent').innerHTML = html;
    }

    // Export Assets
    function exportAssets() {
        window.location.href = '../../controller/export_assets.php';
    }

    // Generate QR Code
    function generateQRCode(assetId) {
        fetch(`../../controller/generate_qr_code.php?id=${assetId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Create a modal to display the QR code
                    const qrModal = document.createElement('div');
                    qrModal.className = 'modal show';
                    qrModal.innerHTML = `
                        <div class="modal-content max-w-md">
                            <div class="bg-purple-600 text-white p-6 rounded-t-xl">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-2xl font-bold">QR Code: ${data.asset.asset_tag}</h3>
                                    <button onclick="this.closest('.modal').remove()" class="text-white hover:text-gray-200 text-2xl">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="p-6 text-center">
                                <img src="${data.qr_code_url}" alt="QR Code" class="mx-auto mb-4 border-4 border-gray-200 rounded-lg">
                                <h4 class="font-bold text-lg mb-2">${data.asset.asset_name}</h4>
                                <p class="text-gray-600 mb-4">${data.asset.asset_type}</p>
                                <div class="flex space-x-3 justify-center">
                                    <a href="${data.qr_code_url}" download="qr_${data.asset.asset_tag}.png" 
                                       class="bg-[#1E3A8A] hover:bg-blue-900 text-white px-3 py-1.5 rounded text-xs transition">
                                        <i class="fas fa-download mr-1"></i>Download
                                    </a>
                                    <button onclick="window.print()" 
                                            class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1.5 rounded text-xs transition">
                                        <i class="fas fa-print mr-2"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(qrModal);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error generating QR code', 'error');
            });
    }

    // Delete Asset
    async function deleteAsset(assetId) {
        const confirmed = await showConfirmModal({
            title: 'Delete Asset',
            message: 'Are you sure you want to delete this asset? This action cannot be undone.',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            confirmColor: 'bg-red-600 hover:bg-red-700',
            type: 'danger'
        });
        
        if (!confirmed) return;
        
        fetch(`../../controller/delete_asset.php?id=${assetId}`, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Asset deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error deleting asset', 'error');
        });
    }

    // Note: openAddAssetModal and editAsset functions are in add_edit_asset_modal.php

    // QR Scanner
    let html5QrCode;

    function openQRScanner() {
        document.getElementById('qrScannerModal').classList.add('show');
        
        html5QrCode = new Html5Qrcode("qrReader");
        
        html5QrCode.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: 250 },
            onScanSuccess,
            onScanError
        );
    }

    function closeQRScanner() {
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                document.getElementById('qrScannerModal').classList.remove('show');
            });
        }
    }

    function onScanSuccess(decodedText, decodedResult) {
        document.getElementById('qrResult').classList.remove('hidden');
        document.getElementById('qrResultText').textContent = decodedText;
        
        // Process scanned data
        console.log('Scanned:', decodedText);
        
        // Stop scanning after successful scan
        html5QrCode.stop();
    }

    function onScanError(errorMessage) {
        // Handle scan error
    }

    // Filter Labs
    function filterLabs() {
        const searchInput = document.getElementById('searchLabs').value.toLowerCase();
        const labCards = document.querySelectorAll('.lab-card');
        const labsGrid = document.getElementById('labsGrid');
        const noResultsMsg = document.getElementById('noLabsFound');
        let visibleCount = 0;

        labCards.forEach(card => {
            const labName = card.getAttribute('data-lab-name');
            
            if (labName.includes(searchInput)) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Show/hide no results message
        if (visibleCount === 0 && searchInput !== '') {
            labsGrid.style.display = 'none';
            noResultsMsg.classList.remove('hidden');
        } else {
            labsGrid.style.display = 'grid';
            noResultsMsg.classList.add('hidden');
        }
    }

    // Filter Assets
    function filterAssets() {
        const searchInput = document.getElementById('searchAssets').value.toLowerCase();
        const statusFilter = document.getElementById('filterStatus').value.toLowerCase();
        const roomFilter = document.getElementById('filterRoom').value.toLowerCase();
        const typeFilter = document.getElementById('filterType').value.toLowerCase();
        const assetRows = document.querySelectorAll('.asset-row');
        let visibleCount = 0;

        assetRows.forEach(row => {
            const assetTag = row.getAttribute('data-asset-tag');
            const assetName = row.getAttribute('data-asset-name');
            const assetType = row.getAttribute('data-asset-type');
            const assetCategory = row.getAttribute('data-asset-category');
            const assetRoom = row.getAttribute('data-asset-room');
            const assetStatus = row.getAttribute('data-asset-status');
            
            const matchesSearch = searchInput === '' || 
                                assetTag.includes(searchInput) || 
                                assetName.includes(searchInput) ||
                                assetType.includes(searchInput) ||
                                assetCategory.includes(searchInput);
            
            const matchesStatus = statusFilter === '' || assetStatus === statusFilter;
            const matchesRoom = roomFilter === '' || assetRoom === roomFilter.toLowerCase();
            const matchesType = typeFilter === '' || assetType === typeFilter.toLowerCase();
            
            if (matchesSearch && matchesStatus && matchesRoom && matchesType) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update visible count or show no results message
        console.log('Visible assets:', visibleCount);
    }

    // Clear Asset Filters
    function clearAssetFilters() {
        document.getElementById('searchAssets').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterRoom').value = '';
        document.getElementById('filterType').value = '';
        filterAssets();
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('show');
            if (html5QrCode) {
                html5QrCode.stop();
            }
        }
    }
</script>

<?php include '../components/layout_footer.php'; ?>
