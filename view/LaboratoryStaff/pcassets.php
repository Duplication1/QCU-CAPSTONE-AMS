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

// Get pc_unit_id from URL
$pc_unit_id = isset($_GET['pc_unit_id']) ? intval($_GET['pc_unit_id']) : 0;

if ($pc_unit_id <= 0) {
    header("Location: roomassets.php");
    exit();
}

// Get PC unit and room details
$pc_query = $conn->prepare("SELECT pu.*, r.name as room_name, r.id as room_id, b.name as building_name 
                             FROM pc_units pu 
                             LEFT JOIN rooms r ON pu.room_id = r.id 
                             LEFT JOIN buildings b ON r.building_id = b.id 
                             WHERE pu.id = ?");
$pc_query->bind_param('i', $pc_unit_id);
$pc_query->execute();
$pc_result = $pc_query->get_result();
$pc_unit = $pc_result->fetch_assoc();
$pc_query->close();

if (!$pc_unit) {
    header("Location: roomassets.php");
    exit();
}

$room_id = $pc_unit['room_id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_component') {
        $asset_id = intval($_POST['asset_id'] ?? 0);
        
        if ($asset_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid asset ID']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE assets SET pc_unit_id = ? WHERE id = ? AND room_id = ? AND (pc_unit_id IS NULL OR pc_unit_id = 0)");
            $stmt->bind_param('iii', $pc_unit_id, $asset_id, $room_id);
            $success = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            if ($success && $affected > 0) {
                echo json_encode(['success' => true, 'message' => 'Component added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add component. It may already be assigned.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'remove_component') {
        $asset_id = intval($_POST['asset_id'] ?? 0);
        
        if ($asset_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid asset ID']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE assets SET pc_unit_id = NULL WHERE id = ? AND pc_unit_id = ?");
            $stmt->bind_param('ii', $asset_id, $pc_unit_id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Component removed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove component']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Fetch components assigned to this PC unit
$components = [];
$comp_query = $conn->prepare("SELECT a.*, ac.name as category_name FROM assets a LEFT JOIN asset_categories ac ON a.category = ac.id WHERE a.pc_unit_id = ? ORDER BY a.asset_type, a.asset_name");
$comp_query->bind_param('i', $pc_unit_id);
$comp_query->execute();
$comp_result = $comp_query->get_result();
while ($row = $comp_result->fetch_assoc()) {
    $components[] = $row;
}
$comp_query->close();

// Fetch available assets (not assigned to any PC) with pagination
$avail_page = isset($_GET['avail_page']) ? max(1, intval($_GET['avail_page'])) : 1;
$avail_limit = 6;
$avail_offset = ($avail_page - 1) * $avail_limit;

// Get filter parameters
$category_filter = isset($_GET['category_filter']) ? intval($_GET['category_filter']) : 0;

// Fetch categories for filter dropdown (only PC categories)
$categories = [];
$cat_query = $conn->prepare("SELECT id, name FROM asset_categories WHERE is_pc_category = 1 ORDER BY name ASC");
$cat_query->execute();
$cat_result = $cat_query->get_result();
while ($row = $cat_result->fetch_assoc()) {
    $categories[] = $row;
}
$cat_query->close();

// Count total available assets with filter
$count_query = "SELECT COUNT(*) as total FROM assets a WHERE a.room_id = ? AND (a.pc_unit_id IS NULL OR a.pc_unit_id = 0)";
$params = [$room_id];
$types = 'i';

if ($category_filter > 0) {
    $count_query .= " AND a.category = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

$avail_count_query = $conn->prepare($count_query);
$avail_count_query->bind_param($types, ...$params);
$avail_count_query->execute();
$avail_count_result = $avail_count_query->get_result();
$total_available = $avail_count_result->fetch_assoc()['total'];
$avail_count_query->close();

$total_avail_pages = ceil($total_available / $avail_limit);

$available_assets = [];
$query = "SELECT a.*, ac.name as category_name FROM assets a LEFT JOIN asset_categories ac ON a.category = ac.id WHERE a.room_id = ? AND (a.pc_unit_id IS NULL OR a.pc_unit_id = 0)";
$params = [$room_id];
$types = 'i';

if ($category_filter > 0) {
    $query .= " AND a.category = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

$query .= " ORDER BY a.asset_type, a.asset_tag LIMIT ? OFFSET ?";
$params[] = $avail_limit;
$params[] = $avail_offset;
$types .= 'ii';

$avail_query = $conn->prepare($query);
$avail_query->bind_param($types, ...$params);
$avail_query->execute();
$avail_result = $avail_query->get_result();
while ($row = $avail_result->fetch_assoc()) {
    $available_assets[] = $row;
}
$avail_query->close();

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
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    padding: 0.5rem;
    background-color: #f9fafb;
}
</style>

<main>
    <div class="flex flex-col">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white rounded shadow-sm border border-gray-200 mb-3">
            <div class="flex items-center gap-3">
                <!-- Breadcrumb Navigation -->
                <div class="flex items-center gap-2">
                    <a href="buildings.php" 
                       class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded transition-colors"
                       title="Back to Buildings">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Buildings</span>
                    </a>
                    <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                    <a href="rooms.php?building_id=<?php echo $pc_unit['building_id']; ?>" 
                       class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded transition-colors"
                       title="Back to <?php echo htmlspecialchars($pc_unit['building_name'] ?? 'Building'); ?> Rooms">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span><?php echo htmlspecialchars($pc_unit['building_name'] ?? 'Building'); ?></span>
                    </a>
                    <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                    <a href="roomassets.php?room_id=<?php echo $room_id; ?>" 
                       class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded transition-colors"
                       title="Back to <?php echo htmlspecialchars($pc_unit['room_name']); ?> Assets">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span><?php echo htmlspecialchars($pc_unit['room_name']); ?></span>
                    </a>
                </div>
                <div class="h-8 w-px bg-gray-300 mx-2"></div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-desktop text-blue-600"></i>
                        <?php echo htmlspecialchars($pc_unit['terminal_number']); ?> - Components
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        <?php echo count($components); ?> component(s) assigned
                    </p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 text-sm font-medium <?php echo $pc_unit['status'] === 'Active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?> rounded-full">
                    <?php echo htmlspecialchars($pc_unit['status']); ?>
                </span>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="flex gap-3 min-h-[600px]">
            
            <!-- Left Column - Assigned Components -->
            <div class="flex-1 flex flex-col bg-white rounded shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-microchip text-blue-600"></i>
                        Assigned Components
                    </h4>
                    <p class="text-xs text-gray-500 mt-0.5">Components currently assigned to this PC unit</p>
                </div>
                
                <div class="flex-1 overflow-auto">
                    <?php if (empty($components)): ?>
                        <div class="flex flex-col items-center justify-center h-full text-gray-400 px-6 py-12">
                            <i class="fa-solid fa-box-open text-6xl mb-4 opacity-30"></i>
                            <p class="text-lg font-medium text-gray-500">No Components Assigned</p>
                            <p class="text-sm text-gray-400 mt-1">Add components from the available assets list</p>
                        </div>
                    <?php else: ?>
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Tag</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Component Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand/Model</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($components as $index => $comp): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 text-sm text-gray-500"><?php echo $index + 1; ?></td>
                                        <td class="px-4 py-3 text-sm font-medium text-blue-600"><?php echo htmlspecialchars($comp['asset_tag']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($comp['category_name'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($comp['asset_name']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($comp['asset_type']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            <?php 
                                            $brand_model = array_filter([$comp['brand'] ?? '', $comp['model'] ?? '']);
                                            echo htmlspecialchars(implode(' - ', $brand_model) ?: 'N/A'); 
                                            ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php
                                            $status_colors = [
                                                'Active' => 'bg-green-100 text-green-700',
                                                'In Use' => 'bg-blue-100 text-blue-700',
                                                'Available' => 'bg-green-100 text-green-700',
                                                'Under Maintenance' => 'bg-yellow-100 text-yellow-700',
                                                'Damaged' => 'bg-orange-100 text-orange-700'
                                            ];
                                            $status_class = $status_colors[$comp['status']] ?? 'bg-gray-100 text-gray-700';
                                            ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($comp['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button onclick="removeComponent(<?php echo $comp['id']; ?>, '<?php echo htmlspecialchars($comp['asset_tag']); ?>')" 
                                                    class="text-red-600 hover:text-red-800 transition-colors" 
                                                    title="Remove from PC">
                                                <i class="fa-solid fa-times-circle text-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column - Available Assets -->
            <div class="w-96 flex flex-col bg-white rounded shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fa-solid fa-box text-green-600"></i>
                            Available Assets
                        </h4>
                        <span class="text-xs text-gray-500"><?php echo $total_available; ?> total</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">Click to add to this PC unit</p>
                </div>
                
                <!-- Filter Section -->
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-medium text-gray-600">Filter by Category:</label>
                        <select id="categoryFilter" onchange="applyCategoryFilter()" 
                                class="text-xs px-2 py-1 border border-gray-300 rounded focus:ring-1 focus:ring-green-500 focus:border-green-500">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="flex-1 overflow-auto">
                    <?php if (empty($available_assets)): ?>
                        <div class="flex flex-col items-center justify-center h-full text-gray-400 px-6 py-8">
                            <i class="fa-solid fa-inbox text-5xl mb-3 opacity-30"></i>
                            <p class="text-sm font-medium text-gray-500">No Available Assets</p>
                            <p class="text-xs text-gray-400 mt-1 text-center">All assets in this room are assigned</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($available_assets as $asset): ?>
                                <div class="p-3 hover:bg-gray-50 transition-colors cursor-pointer" 
                                     onclick="addComponent(<?php echo $asset['id']; ?>, '<?php echo htmlspecialchars($asset['asset_tag'], ENT_QUOTES); ?>')">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-semibold text-blue-600"><?php echo htmlspecialchars($asset['asset_tag']); ?></span>
                                                <span class="px-1.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded">
                                                    <?php echo htmlspecialchars($asset['asset_type']); ?>
                                                </span>
                                                <?php if (!empty($asset['category_name'])): ?>
                                                <span class="px-1.5 py-0.5 text-xs font-medium bg-purple-100 text-purple-700 rounded">
                                                    <?php echo htmlspecialchars($asset['category_name']); ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm font-medium text-gray-900 mt-1 truncate"><?php echo htmlspecialchars($asset['asset_name']); ?></p>
                                            <?php if (!empty($asset['brand']) || !empty($asset['model'])): ?>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    <?php 
                                                    $brand_model = array_filter([$asset['brand'] ?? '', $asset['model'] ?? '']);
                                                    echo htmlspecialchars(implode(' - ', $brand_model)); 
                                                    ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <button class="flex-shrink-0 p-2 text-green-600 hover:bg-green-100 rounded transition-colors">
                                            <i class="fa-solid fa-plus-circle text-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Available Assets Pagination -->
                <?php if ($total_avail_pages > 1): ?>
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between text-xs">
                        <div class="text-gray-600">
                            Page <?php echo $avail_page; ?> of <?php echo $total_avail_pages; ?>
                        </div>
                        <div class="flex gap-2">
                            <?php 
                            $base_url = "?pc_unit_id=$pc_unit_id";
                            if ($category_filter > 0) {
                                $base_url .= "&category_filter=$category_filter";
                            }
                            ?>
                            <?php if ($avail_page > 1): ?>
                                <a href="<?php echo $base_url; ?>&avail_page=<?php echo $avail_page - 1; ?>" 
                                   class="px-2 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 transition-colors">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($avail_page < $total_avail_pages): ?>
                                <a href="<?php echo $base_url; ?>&avail_page=<?php echo $avail_page + 1; ?>" 
                                   class="px-2 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 transition-colors">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</main>

<!-- Component Action Confirmation Modal -->
<div id="componentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white" id="componentModalTitle">Confirm Action</h3>
        </div>
        <div class="p-6">
            <div class="flex items-center mb-4">
                <i class="fa-solid fa-question-circle text-blue-500 text-2xl mr-3" id="componentModalIcon"></i>
                <div>
                    <p class="text-gray-800 font-medium" id="componentModalMessage">Are you sure?</p>
                </div>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeComponentModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button type="button" id="confirmComponentBtn" onclick="confirmComponentAction()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-check mr-2"></i>Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Modal functions
function openComponentModal(title, message, iconClass, confirmCallback) {
    document.getElementById('componentModalTitle').textContent = title;
    document.getElementById('componentModalMessage').textContent = message;
    document.getElementById('componentModalIcon').className = `fa-solid ${iconClass} text-2xl mr-3`;
    document.getElementById('componentModal').classList.remove('hidden');
    window.confirmComponentCallback = confirmCallback;
}

function closeComponentModal() {
    document.getElementById('componentModal').classList.add('hidden');
    document.getElementById('componentModalTitle').textContent = 'Confirm Action';
    document.getElementById('componentModalMessage').textContent = 'Are you sure?';
    window.confirmComponentCallback = null;
}

function confirmComponentAction() {
    if (window.confirmComponentCallback) {
        window.confirmComponentCallback();
    }
    closeComponentModal();
}

// Add component to PC
async function addComponent(assetId, assetTag) {
    openComponentModal(
        'Add Component',
        `Add "${assetTag}" to this PC unit?`,
        'fa-plus-circle text-green-500',
        async () => {
            const formData = new URLSearchParams();
            formData.append('ajax', '1');
            formData.append('action', 'add_component');
            formData.append('asset_id', assetId);
            
            try {
                const response = await fetch(location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    setTimeout(() => location.reload(), 800);
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'An error occurred while adding the component');
            }
        }
    );
}

// Remove component from PC
async function removeComponent(assetId, assetTag) {
    openComponentModal(
        'Remove Component',
        `Remove "${assetTag}" from this PC unit?`,
        'fa-minus-circle text-red-500',
        async () => {
            const formData = new URLSearchParams();
            formData.append('ajax', '1');
            formData.append('action', 'remove_component');
            formData.append('asset_id', assetId);
            
            try {
                const response = await fetch(location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    setTimeout(() => location.reload(), 800);
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'An error occurred while removing the component');
            }
        }
    );
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

// Category filter function
function applyCategoryFilter() {
    const categoryFilter = document.getElementById('categoryFilter');
    const selectedCategory = categoryFilter.value;
    
    // Build URL with current parameters
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('category_filter', selectedCategory);
    urlParams.delete('avail_page'); // Reset to first page when filtering
    
    // Redirect to the same page with filter
    window.location.href = window.location.pathname + '?' + urlParams.toString();
}
</script>

<?php include '../components/layout_footer.php'; ?>
