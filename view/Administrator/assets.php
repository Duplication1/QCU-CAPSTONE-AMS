<?php
session_start();

// Check if user is logged in and has administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
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
    
    .tab-button {
        padding: 0.75rem 1.5rem;
        border: none;
        background: transparent;
        cursor: pointer;
        font-weight: 600;
        color: #6b7280;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }
    .tab-button.active {
        color: #3b82f6;
        border-bottom-color: #3b82f6;
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
</style>

<!-- Main Content -->
<main class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">Asset Management System</h2>
        <p class="text-gray-600">Manage and track all assets across computer laboratories</p>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php 
                echo htmlspecialchars($_SESSION['success_message']); 
                unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php 
                echo htmlspecialchars($_SESSION['error_message']); 
                unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="bg-white rounded-xl shadow-lg mb-6">
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
        <div id="labs-tab" class="tab-content active p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">Computer Laboratories</h3>
                <button onclick="openAddAssetModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>Add New Asset
                </button>
            </div>

            <!-- Computer Labs Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($rooms as $room): ?>
                    <div class="lab-card bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl shadow-md p-6 border-2 border-blue-200" 
                         onclick="openLabModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['name']); ?>')">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-blue-900"><?php echo htmlspecialchars($room['name']); ?></h3>
                            <div class="bg-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center">
                                <i class="fas fa-desktop text-xl"></i>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700">Total Assets:</span>
                                <span class="font-bold text-blue-900"><?php echo $room['total_assets']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700">Available:</span>
                                <span class="font-semibold text-green-600"><?php echo $room['available']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700">In Use:</span>
                                <span class="font-semibold text-blue-600"><?php echo $room['in_use']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700">Maintenance:</span>
                                <span class="font-semibold text-yellow-600"><?php echo $room['maintenance']; ?></span>
                            </div>
                        </div>
                        
                        <button class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition">
                            View Assets <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($rooms)): ?>
                    <div class="col-span-3 text-center py-12 text-gray-500">
                        <i class="fas fa-inbox text-6xl mb-4"></i>
                        <p class="text-xl">No computer labs found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- All Assets Tab -->
        <div id="all-assets-tab" class="tab-content p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">All Assets</h3>
                <div class="flex space-x-3">
                    <input type="text" id="searchAssets" placeholder="Search assets..." 
                           class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <button onclick="openAddAssetModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-plus mr-2"></i>Add Asset
                    </button>
                    <button onclick="exportAssets()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-download mr-2"></i>Export CSV
                    </button>
                </div>
            </div>

            <!-- Assets Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-300 rounded-lg" id="assetsTable">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Asset Tag</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Asset Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Room</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Condition</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($assets as $asset): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($asset['asset_tag']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($asset['asset_type']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($asset['category'] ?? '-'); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($asset['room_name'] ?? '-'); ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $asset['status'])); ?>">
                                        <?php echo htmlspecialchars($asset['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($asset['condition']); ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <button onclick="viewAssetDetails(<?php echo $asset['id']; ?>)" class="text-blue-600 hover:text-blue-800 mr-3" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editAsset(<?php echo $asset['id']; ?>)" class="text-yellow-600 hover:text-yellow-800 mr-3" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="generateQRCode(<?php echo $asset['id']; ?>)" class="text-purple-600 hover:text-purple-800 mr-3" title="QR Code">
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
        <div id="import-tab" class="tab-content p-6">
            <div class="max-w-4xl mx-auto">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Import Assets</h3>
                
                <!-- CSV Import -->
                <div class="bg-gray-50 rounded-xl p-6 mb-6 border-2 border-dashed border-gray-300">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-100 rounded-full p-3 mr-4">
                            <i class="fas fa-file-csv text-blue-600 text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-800">CSV Import</h4>
                            <p class="text-gray-600 text-sm">Upload a CSV file to import multiple assets at once</p>
                        </div>
                    </div>
                    
                    <form id="csvImportForm" action="../../controller/import_assets.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Select CSV File</label>
                            <input type="file" name="csv_file" id="csvFile" accept=".csv" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-2">Maximum file size: 5MB</p>
                        </div>
                        
                        <div class="flex space-x-3">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
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
                <button onclick="closeLabModal()" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="p-6">
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
        <div class="bg-gray-800 text-white p-6 rounded-t-xl">
            <div class="flex justify-between items-center">
                <h3 class="text-2xl font-bold">Asset Details</h3>
                <button onclick="closeAssetDetailsModal()" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="p-6" id="assetDetailsContent">
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
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayLabAssets(data.assets);
                } else {
                    document.getElementById('labAssetsContent').innerHTML = 
                        '<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-circle text-4xl mb-4"></i><p>' + data.message + '</p></div>';
                }
            })
            .catch(error => {
                document.getElementById('labAssetsContent').innerHTML = 
                    '<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-circle text-4xl mb-4"></i><p>Error loading assets</p></div>';
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

        let html = '<table class="min-w-full bg-white border border-gray-300"><thead class="bg-gray-100"><tr>';
        html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Asset Tag</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Name</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Type</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Terminal</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Status</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Actions</th>';
        html += '</tr></thead><tbody class="divide-y divide-gray-200">';

        assets.forEach(asset => {
            const statusClass = 'status-' + asset.status.toLowerCase().replace(' ', '-');
            html += `<tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm font-medium">${asset.asset_tag}</td>
                <td class="px-4 py-3 text-sm">${asset.asset_name}</td>
                <td class="px-4 py-3 text-sm">${asset.asset_type}</td>
                <td class="px-4 py-3 text-sm">${asset.terminal_number || '-'}</td>
                <td class="px-4 py-3 text-sm"><span class="status-badge ${statusClass}">${asset.status}</span></td>
                <td class="px-4 py-3 text-sm">
                    <button onclick="viewAssetDetails(${asset.id})" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-eye"></i></button>
                    <button onclick="editAsset(${asset.id})" class="text-yellow-600 hover:text-yellow-800 mr-2"><i class="fas fa-edit"></i></button>
                </td>
            </tr>`;
        });

        html += '</tbody></table>';
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
                                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                                        <i class="fas fa-download mr-2"></i>Download
                                    </a>
                                    <button onclick="window.print()" 
                                            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
                                        <i class="fas fa-print mr-2"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(qrModal);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error generating QR code');
            });
    }

    // Delete Asset
    function deleteAsset(assetId) {
        if (confirm('Are you sure you want to delete this asset? This action cannot be undone.')) {
            fetch(`../../controller/delete_asset.php?id=${assetId}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Asset deleted successfully');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error deleting asset');
            });
        }
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
