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

// Calculate disposal-eligible assets (end of life passed or poor/non-functional condition)
$current_date = date('Y-m-d');

// Query for assets eligible for disposal
$disposal_query = "
    SELECT 
        a.*,
        r.name as room_name,
        r.building_id,
        b.name as building_name,
        CASE 
            WHEN a.end_of_life IS NOT NULL AND a.end_of_life < ? THEN 'End of Life Reached'
            WHEN a.`condition` IN ('Poor', 'Non-Functional') THEN 'Poor Condition'
            ELSE 'Other'
        END as disposal_reason,
        DATEDIFF(?, a.end_of_life) as days_past_eol
    FROM assets a
    LEFT JOIN rooms r ON a.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    WHERE 
        a.status NOT IN ('Disposed', 'Archive', 'Archived')
        AND (
            (a.end_of_life IS NOT NULL AND a.end_of_life < ?)
            OR a.`condition` IN ('Poor', 'Non-Functional')
        )
    ORDER BY 
        CASE 
            WHEN a.end_of_life < ? THEN 1
            WHEN a.`condition` = 'Non-Functional' THEN 2
            WHEN a.`condition` = 'Poor' THEN 3
            ELSE 4
        END,
        a.end_of_life ASC,
        a.asset_name ASC
";

$stmt = $conn->prepare($disposal_query);
$stmt->bind_param('ssss', $current_date, $current_date, $current_date, $current_date);
$stmt->execute();
$disposal_result = $stmt->get_result();

// Fetch asset categories
$categories = [];
$category_query = "SELECT id, name FROM asset_categories ORDER BY name ASC";
$category_result = $conn->query($category_query);
if ($category_result && $category_result->num_rows > 0) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get counts by disposal reason
$count_query = "
    SELECT 
        SUM(CASE WHEN end_of_life IS NOT NULL AND end_of_life < ? THEN 1 ELSE 0 END) as eol_count,
        SUM(CASE WHEN `condition` = 'Poor' THEN 1 ELSE 0 END) as poor_count,
        SUM(CASE WHEN `condition` = 'Non-Functional' THEN 1 ELSE 0 END) as non_functional_count,
        COUNT(*) as total_count
    FROM assets
    WHERE status NOT IN ('Disposed', 'Archive', 'Archived')
        AND (
            (end_of_life IS NOT NULL AND end_of_life < ?)
            OR `condition` IN ('Poor', 'Non-Functional')
        )
";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param('ss', $current_date, $current_date);
$count_stmt->execute();
$counts = $count_stmt->get_result()->fetch_assoc();
?>


<?php include '../components/layout_header.php'; ?>

<style>
main {
    padding: 0.5rem;
    background-color: #f9fafb;
    min-height: 100%;
}
</style>

<main>
    <div class="flex-1 flex flex-col">
        
        <!-- Header with Statistics -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 px-4 py-3">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Asset Disposal Management</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Total Eligible: <?php echo number_format($counts['total_count'] ?? 0); ?> asset(s)</p>
                </div>
                <button onclick="exportDisposalList()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-download"></i>
                    <span>Export List</span>
                </button>
            </div>
          
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-red-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">Total Eligible</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($counts['total_count'] ?? 0); ?></p>
                        </div>
                        <div class="bg-red-100 rounded-full p-3">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">End of Life</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($counts['eol_count'] ?? 0); ?></p>
                        </div>
                        <div class="bg-orange-100 rounded-full p-3">
                            <i class="fas fa-calendar-times text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">Poor Condition</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($counts['poor_count'] ?? 0); ?></p>
                        </div>
                        <div class="bg-yellow-100 rounded-full p-3">
                            <i class="fas fa-tools text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-gray-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">Non-Functional</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($counts['non_functional_count'] ?? 0); ?></p>
                        </div>
                        <div class="bg-gray-100 rounded-full p-3">
                            <i class="fas fa-times-circle text-gray-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions Bar -->
        <div id="bulkActionsBar" class="hidden bg-white rounded shadow-sm border border-gray-200 mb-3 px-4 py-3">
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-600">
                    <span id="selectedCount">0</span> selected
                </span>
                <button onclick="openBulkDisposalModal()" 
                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded hover:bg-red-700 transition-colors">
                    <i class="fa-solid fa-trash mr-2"></i>Dispose Selected
                </button>
                <button onclick="printSelectedQRCodes()" 
                        class="px-4 py-2 bg-[#1E3A8A] text-white text-sm font-medium rounded hover:bg-[#153570] transition-colors">
                    <i class="fa-solid fa-qrcode mr-2"></i>Print QR Codes
                </button>
                <button onclick="clearSelection()" 
                        class="px-3 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded hover:bg-gray-300 transition-colors">
                    Clear
                </button>
            </div>
        </div>

        <!-- Show Entries Control -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">Show:</label>
                    <select id="entriesPerPage" onchange="updateEntriesPerPage()" class="px-3 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="all">All</option>
                    </select>
                    <span class="text-sm text-gray-600">entries</span>
                </div>
                <div class="text-sm text-gray-600">
                    Showing <span id="showingFrom">1</span> to <span id="showingTo">25</span> of <span id="totalEntries"><?php echo $counts['total_count'] ?? 0; ?></span> assets
                </div>
            </div>
        </div>

        <!-- Assets Table -->
        <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#1E3A8A] text-white sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-2 text-center text-xs font-medium uppercase tracking-wider">
                            <input type="checkbox" id="selectAll" onclick="toggleSelectAll()" class="rounded cursor-pointer" title="Select all">
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">#</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Asset Tag</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Asset Name</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Type</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Location</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Condition</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">End of Life</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Disposal Reason</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                        <th class="px-3 py-2 text-center text-xs font-medium uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="disposalTableBody" class="bg-white divide-y divide-gray-200">
                    <?php 
                    if ($disposal_result && $disposal_result->num_rows > 0):
                        $index = 0;
                        while ($asset = $disposal_result->fetch_assoc()): 
                            $index++;
                            $location = $asset['building_name'] ? $asset['building_name'] . ' - ' . $asset['room_name'] : 'Standby';
                            
                            // Condition badge
                            $condition_class = match($asset['condition']) {
                                'Excellent' => 'bg-green-100 text-green-700 border-green-300',
                                'Good' => 'bg-blue-100 text-blue-700 border-blue-300',
                                'Fair' => 'bg-yellow-100 text-yellow-700 border-yellow-300',
                                'Poor' => 'bg-orange-100 text-orange-700 border-orange-300',
                                'Non-Functional' => 'bg-red-100 text-red-700 border-red-300',
                                default => 'bg-gray-100 text-gray-700 border-gray-300'
                            };
                            
                            // Disposal reason badge
                            $reason_class = match($asset['disposal_reason']) {
                                'End of Life Reached' => 'bg-orange-100 text-orange-700',
                                'Poor Condition' => 'bg-yellow-100 text-yellow-700',
                                default => 'bg-gray-100 text-gray-700'
                            };
                    ?>
                    <tr class="hover:bg-blue-50 transition-colors asset-row" 
                        data-asset-id="<?php echo $asset['id']; ?>"
                        data-asset-tag="<?php echo htmlspecialchars($asset['asset_tag']); ?>"
                        data-asset-name="<?php echo htmlspecialchars($asset['asset_name']); ?>">
                        <td class="px-3 py-2 text-center">
                            <input type="checkbox" class="asset-checkbox rounded cursor-pointer" value="<?php echo $asset['id']; ?>" onchange="updateSelection()">
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                            <?php echo $index; ?>
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
                            <div class="max-w-xs truncate"><?php echo htmlspecialchars($location); ?></div>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs">
                            <span class="px-2 py-1 text-xs font-medium rounded border <?php echo $condition_class; ?>">
                                <?php echo htmlspecialchars($asset['condition']); ?>
                            </span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-700">
                            <?php 
                            if ($asset['end_of_life']) {
                                echo date('M d, Y', strtotime($asset['end_of_life']));
                                if ($asset['days_past_eol'] > 0) {
                                    echo '<br><span class="text-xs text-red-600">(' . $asset['days_past_eol'] . ' days overdue)</span>';
                                }
                            } else {
                                echo 'Not Set';
                            }
                            ?>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs">
                            <span class="px-2 py-1 text-xs font-medium rounded <?php echo $reason_class; ?>">
                                <?php echo htmlspecialchars($asset['disposal_reason']); ?>
                            </span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs">
                            <span class="px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-700">
                                <?php echo htmlspecialchars($asset['status']); ?>
                            </span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                            <div class="flex gap-1 justify-center">
                                <button onclick="viewAssetDetails(<?php echo $asset['id']; ?>)" 
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors" 
                                        title="View Details">
                                    <i class="fas fa-eye text-xs"></i>
                                </button>
                                <button onclick="markForDisposal(<?php echo $asset['id']; ?>, '<?php echo addslashes($asset['asset_name']); ?>')" 
                                        class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors" 
                                        title="Mark for Disposal">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="11" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-check-circle text-5xl mb-3 opacity-30"></i>
                            <p class="text-lg font-semibold">No assets currently eligible for disposal</p>
                            <p class="text-sm">Assets will appear here when they reach end-of-life or are in poor condition</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

    <!-- Asset Details Modal -->
    <div id="assetDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-gray-800">Asset Details</h3>
                <button onclick="closeAssetDetails()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="assetDetailsContent" class="p-6">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- Disposal Confirmation Modal -->
    <div id="disposalModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-800">Confirm Disposal</h3>
            </div>
            <div class="p-6">
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl mr-3 mt-1"></i>
                        <div>
                            <p class="font-semibold text-red-800 mb-1">Warning: This action cannot be undone</p>
                            <p class="text-sm text-red-700">The asset will be marked as disposed and archived.</p>
                        </div>
                    </div>
                </div>
                <p class="text-gray-700 mb-4">Are you sure you want to mark the following asset for disposal?</p>
                <p class="font-semibold text-gray-900 mb-4" id="disposalAssetName"></p>
                
                <label class="block text-sm font-medium text-gray-700 mb-2">Disposal Notes (Optional)</label>
                <textarea id="disposalNotes" rows="3" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Enter any additional notes about the disposal..."></textarea>
            </div>
            <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                <button onclick="closeDisposalModal()" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition-colors">
                    Cancel
                </button>
                <button onclick="confirmDisposal()" 
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-trash mr-2"></i>Confirm Disposal
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Disposal Confirmation Modal -->
    <div id="bulkDisposalModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-800">Confirm Bulk Disposal</h3>
            </div>
            <div class="p-6">
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl mr-3 mt-1"></i>
                        <div>
                            <p class="font-semibold text-red-800 mb-1">Warning: This action cannot be undone</p>
                            <p class="text-sm text-red-700">All selected assets will be marked as disposed and archived.</p>
                        </div>
                    </div>
                </div>
                <p class="text-gray-700 mb-2">You are about to mark <span id="bulkDisposalCount" class="font-bold text-red-600">0</span> asset(s) for disposal.</p>
                <p class="text-sm text-gray-600 mb-4">This action is permanent and cannot be reversed.</p>
                
                <label class="block text-sm font-medium text-gray-700 mb-2">Disposal Notes (Optional)</label>
                <textarea id="bulkDisposalNotes" rows="3" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Enter notes that will apply to all selected assets..."></textarea>
            </div>
            <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                <button onclick="closeBulkDisposalModal()" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition-colors">
                    Cancel
                </button>
                <button id="bulkDisposalBtn" onclick="confirmBulkDisposal()" 
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-trash mr-2"></i>Dispose All Selected
                </button>
            </div>
        </div>
    </div>

    <!-- QR Code Print Modal -->
    <div id="qrPrintModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-gray-800">Print QR Codes</h3>
                <button onclick="closeQRPrintModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <div id="qrPrintContent" class="grid grid-cols-3 gap-4">
                    <!-- QR codes will be dynamically loaded here -->
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                <button onclick="closeQRPrintModal()" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition-colors">
                    Cancel
                </button>
                <button onclick="window.print()" 
                        class="px-4 py-2 bg-[#1E3A8A] hover:bg-[#153570] text-white rounded-lg transition-colors">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
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
                position: fixed;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background: white;
            }
            #qrPrintContent {
                page-break-inside: avoid;
            }
            .qr-item {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>

    <script>
        let currentDisposalAssetId = null;
        let selectedAssets = new Set();
        let entriesPerPage = 25;
        let allRows = [];

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Store all table rows
            allRows = Array.from(document.querySelectorAll('.asset-row'));
            updatePagination();
        });

        // Update entries per page
        function updateEntriesPerPage() {
            const select = document.getElementById('entriesPerPage');
            const value = select.value;
            
            if (value === 'all') {
                entriesPerPage = allRows.length;
            } else {
                entriesPerPage = parseInt(value);
            }
            
            updatePagination();
        }

        // Update pagination display
        function updatePagination() {
            const totalEntries = allRows.length;
            let visibleCount = 0;
            
            allRows.forEach((row, index) => {
                if (entriesPerPage === allRows.length || index < entriesPerPage) {
                    row.style.display = '';
                    visibleCount++;
                    // Update row number
                    const numberCell = row.querySelector('td:nth-child(2)');
                    if (numberCell) {
                        numberCell.textContent = visibleCount;
                    }
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update showing text
            document.getElementById('showingFrom').textContent = totalEntries > 0 ? '1' : '0';
            document.getElementById('showingTo').textContent = Math.min(entriesPerPage, totalEntries);
            document.getElementById('totalEntries').textContent = totalEntries;
        }

        // Selection Management Functions
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.asset-checkbox');
            
            checkboxes.forEach(checkbox => {
                // Only toggle visible checkboxes
                const row = checkbox.closest('tr');
                if (row && row.style.display !== 'none') {
                    checkbox.checked = selectAllCheckbox.checked;
                }
            });
            
            updateSelection();
        }

        function updateSelection() {
            selectedAssets.clear();
            const checkboxes = document.querySelectorAll('.asset-checkbox:checked');
            
            checkboxes.forEach(checkbox => {
                selectedAssets.add(checkbox.value);
            });
            
            // Update UI
            const count = selectedAssets.size;
            document.getElementById('selectedCount').textContent = count;
            
            const bulkActionsBar = document.getElementById('bulkActionsBar');
            if (count > 0) {
                bulkActionsBar.classList.remove('hidden');
            } else {
                bulkActionsBar.classList.add('hidden');
            }
            
            // Update select all checkbox state
            const selectAllCheckbox = document.getElementById('selectAll');
            const visibleCheckboxes = Array.from(document.querySelectorAll('.asset-checkbox')).filter(cb => {
                const row = cb.closest('tr');
                return row && row.style.display !== 'none';
            });
            const checkedVisibleCheckboxes = visibleCheckboxes.filter(cb => cb.checked);
            
            if (checkedVisibleCheckboxes.length === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedVisibleCheckboxes.length === visibleCheckboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }

        // Add change event to all checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.asset-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateSelection);
            });
        });

        function clearSelection() {
            selectedAssets.clear();
            document.querySelectorAll('.asset-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('selectAll').checked = false;
            document.getElementById('selectAll').indeterminate = false;
            updateSelection();
        }

        function openBulkDisposalModal() {
            if (selectedAssets.size === 0) {
                alert('Please select at least one asset to dispose.');
                return;
            }
            
            document.getElementById('bulkDisposalCount').textContent = selectedAssets.size;
            document.getElementById('bulkDisposalNotes').value = '';
            document.getElementById('bulkDisposalModal').classList.remove('hidden');
        }

        function closeBulkDisposalModal() {
            document.getElementById('bulkDisposalModal').classList.add('hidden');
        }

        function confirmBulkDisposal() {
            if (selectedAssets.size === 0) return;
            
            const notes = document.getElementById('bulkDisposalNotes').value;
            const button = document.getElementById('bulkDisposalBtn');
            
            // Disable button and show loading state
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            
            // Prepare form data
            const formData = new URLSearchParams();
            formData.append('ajax', '1');
            formData.append('action', 'bulk_dispose');
            formData.append('notes', notes);
            selectedAssets.forEach(id => {
                formData.append('ids[]', id);
            });
            
            fetch('../../controller/dispose_asset.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Successfully disposed ${data.count || selectedAssets.size} asset(s)`);
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to dispose assets'));
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-trash mr-2"></i>Dispose All Selected';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: Failed to process bulk disposal request');
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-trash mr-2"></i>Dispose All Selected';
            });
        }

        // Original Single Asset Functions
        function viewAssetDetails(assetId) {
            document.getElementById('assetDetailsModal').classList.remove('hidden');
            document.getElementById('assetDetailsContent').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i><p class="mt-2 text-gray-600">Loading...</p></div>';
            
            // Load asset details via fetch
            fetch('../../controller/get_asset_details.php?id=' + assetId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('assetDetailsContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('assetDetailsContent').innerHTML = '<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-circle text-3xl mb-2"></i><p>Failed to load asset details</p></div>';
                });
        }

        function closeAssetDetails() {
            document.getElementById('assetDetailsModal').classList.add('hidden');
        }

        function markForDisposal(assetId, assetName) {
            currentDisposalAssetId = assetId;
            document.getElementById('disposalAssetName').textContent = assetName;
            document.getElementById('disposalNotes').value = '';
            document.getElementById('disposalModal').classList.remove('hidden');
        }

        function closeDisposalModal() {
            document.getElementById('disposalModal').classList.add('hidden');
            currentDisposalAssetId = null;
        }

        function confirmDisposal() {
            if (!currentDisposalAssetId) return;
            
            const notes = document.getElementById('disposalNotes').value;
            const formData = new URLSearchParams();
            formData.append('asset_id', currentDisposalAssetId);
            formData.append('notes', notes);
            
            fetch('../../controller/dispose_asset.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Asset marked for disposal successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to dispose asset'));
                }
            })
            .catch(error => {
                alert('Error: Failed to process disposal request');
            });
        }

        function exportDisposalList() {
            window.location.href = '../../controller/export_disposal_list.php';
        }

        // Print selected QR codes
        async function printSelectedQRCodes() {
            if (selectedAssets.size === 0) {
                alert('Please select at least one asset to print QR codes.');
                return;
            }
            
            const assetIds = Array.from(selectedAssets);
            
            // Show loading
            document.getElementById('qrPrintModal').classList.remove('hidden');
            document.getElementById('qrPrintContent').innerHTML = '<div class="col-span-3 text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i><p class="mt-2 text-gray-600">Generating QR codes...</p></div>';
            
            try {
                const formData = new URLSearchParams();
                formData.append('ajax', '1');
                formData.append('action', 'get_asset_qrcodes');
                assetIds.forEach(id => {
                    formData.append('asset_ids[]', id);
                });
                
                const response = await fetch('../../controller/get_asset_details.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData.toString()
                });
                
                const result = await response.json();
                
                if (result.success && result.qr_codes) {
                    // Display QR codes
                    let qrHtml = '';
                    result.qr_codes.forEach(asset => {
                        qrHtml += `
                            <div class="qr-item border border-gray-300 rounded-lg p-4 text-center">
                                <img src="${asset.qr_code}" alt="QR Code" class="mx-auto mb-2" style="width: 150px; height: 150px;">
                                <p class="text-sm font-semibold text-gray-800">${asset.asset_tag}</p>
                                <p class="text-xs text-gray-600 truncate">${asset.asset_name}</p>
                            </div>
                        `;
                    });
                    document.getElementById('qrPrintContent').innerHTML = qrHtml;
                } else {
                    document.getElementById('qrPrintContent').innerHTML = '<div class="col-span-3 text-center py-8 text-red-600"><i class="fas fa-exclamation-circle text-3xl mb-2"></i><p>Failed to generate QR codes</p></div>';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('qrPrintContent').innerHTML = '<div class="col-span-3 text-center py-8 text-red-600"><i class="fas fa-exclamation-circle text-3xl mb-2"></i><p>Error loading QR codes</p></div>';
            }
        }

        function closeQRPrintModal() {
            document.getElementById('qrPrintModal').classList.add('hidden');
        }

        // Close modals on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAssetDetails();
                closeDisposalModal();
                closeBulkDisposalModal();
                closeQRPrintModal();
            }
        });

        // Close modals when clicking outside
        document.getElementById('assetDetailsModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeAssetDetails();
        });
        document.getElementById('disposalModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeDisposalModal();
        });
        document.getElementById('bulkDisposalModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeBulkDisposalModal();
        });
        document.getElementById('qrPrintModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeQRPrintModal();
        });
    </script>

    <?php include '../components/layout_footer.php'; ?>
</body>
</html>
