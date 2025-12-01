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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Disposal Management - AMS</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../node_modules/@fortawesome/fontawesome-free/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link href="../../assets/css/poppins.css" rel="stylesheet">
    <link rel="stylesheet" href="../../node_modules/datatables.net-dt/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="../../assets/css/output.css">
    
    <script src="../../node_modules/jquery/dist/jquery.min.js"></script>
    <script src="../../node_modules/datatables.net/js/dataTables.min.js"></script>
</head>
<body class="min-h-screen bg-gray-100">
    
    <?php include '../components/sidebar.php'; ?>
    
    <div id="main-wrapper" class="ml-0 lg:ml-20 lg:peer-hover:ml-[220px] pt-[90px] transition-all duration-300 ease-in-out">
        
        <?php include '../components/header.php'; ?>
        
        <div id="main-content-container" class="p-6">
          
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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

            <!-- Main Content Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Assets Eligible for Disposal</h2>
                    <button onclick="exportDisposalList()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-download mr-2"></i>Export List
                    </button>
                </div>

                <!-- Assets Table -->
                <div class="overflow-x-auto">
                    <table id="disposalTable" class="w-full display stripe hover" style="width:100%">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Tag</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End of Life</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disposal Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            if ($disposal_result && $disposal_result->num_rows > 0):
                                while ($asset = $disposal_result->fetch_assoc()): 
                                    $location = $asset['building_name'] ? $asset['building_name'] . ' - ' . $asset['room_name'] : 'No Location';
                                    
                                    // Condition badge
                                    $condition_class = match($asset['condition']) {
                                        'Excellent' => 'bg-green-100 text-green-800',
                                        'Good' => 'bg-blue-100 text-blue-800',
                                        'Fair' => 'bg-yellow-100 text-yellow-800',
                                        'Poor' => 'bg-orange-100 text-orange-800',
                                        'Non-Functional' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                    
                                    // Disposal reason badge
                                    $reason_class = match($asset['disposal_reason']) {
                                        'End of Life Reached' => 'bg-orange-100 text-orange-800',
                                        'Poor Condition' => 'bg-yellow-100 text-yellow-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($asset['asset_tag']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($asset['asset_type']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($location); ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $condition_class; ?>">
                                        <?php echo htmlspecialchars($asset['condition']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
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
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $reason_class; ?>">
                                        <?php echo htmlspecialchars($asset['disposal_reason']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                        <?php echo htmlspecialchars($asset['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex gap-2">
                                        <button onclick="viewAssetDetails(<?php echo $asset['id']; ?>)" 
                                                class="text-blue-600 hover:text-blue-800 transition-colors" 
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="markForDisposal(<?php echo $asset['id']; ?>, '<?php echo addslashes($asset['asset_name']); ?>')" 
                                                class="text-red-600 hover:text-red-800 transition-colors" 
                                                title="Mark for Disposal">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            endif;
                            
                            // Don't show empty message in tbody - handle it with DataTables
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

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

    <script>
        let currentDisposalAssetId = null;

        $(document).ready(function() {
            // Initialize DataTable
            $('#disposalTable').DataTable({
                pageLength: 25,
                order: [[6, 'asc']], // Order by disposal reason
                columnDefs: [
                    { orderable: false, targets: 8 } // Disable sorting on Actions column
                ],
                language: {
                    search: "Search assets:",
                    lengthMenu: "Show _MENU_ assets per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ assets",
                    emptyTable: '<div class="text-center py-8"><i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i><p class="text-lg font-semibold text-gray-700 mt-3">No assets currently eligible for disposal</p><p class="text-sm text-gray-500">Assets will appear here when they reach end-of-life or are in poor condition</p></div>',
                    zeroRecords: "No matching assets found"
                }
            });
        });

        function viewAssetDetails(assetId) {
            $('#assetDetailsModal').removeClass('hidden');
            $('#assetDetailsContent').html('<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i><p class="mt-2 text-gray-600">Loading...</p></div>');
            
            // Load asset details via AJAX
            $.ajax({
                url: '../../controller/get_asset_details.php',
                method: 'GET',
                data: { id: assetId },
                success: function(response) {
                    $('#assetDetailsContent').html(response);
                },
                error: function() {
                    $('#assetDetailsContent').html('<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-circle text-3xl mb-2"></i><p>Failed to load asset details</p></div>');
                }
            });
        }

        function closeAssetDetails() {
            $('#assetDetailsModal').addClass('hidden');
        }

        function markForDisposal(assetId, assetName) {
            currentDisposalAssetId = assetId;
            $('#disposalAssetName').text(assetName);
            $('#disposalNotes').val('');
            $('#disposalModal').removeClass('hidden');
        }

        function closeDisposalModal() {
            $('#disposalModal').addClass('hidden');
            currentDisposalAssetId = null;
        }

        function confirmDisposal() {
            if (!currentDisposalAssetId) return;
            
            const notes = $('#disposalNotes').val();
            
            $.ajax({
                url: '../../controller/dispose_asset.php',
                method: 'POST',
                data: {
                    asset_id: currentDisposalAssetId,
                    notes: notes
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Asset marked for disposal successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Failed to dispose asset'));
                    }
                },
                error: function() {
                    alert('Error: Failed to process disposal request');
                }
            });
        }

        function exportDisposalList() {
            window.location.href = '../../controller/export_disposal_list.php';
        }

        // Close modals on escape key
        $(document).keydown(function(e) {
            if (e.key === 'Escape') {
                closeAssetDetails();
                closeDisposalModal();
            }
        });
    </script>

    <?php include '../components/layout_footer.php'; ?>
</body>
</html>
