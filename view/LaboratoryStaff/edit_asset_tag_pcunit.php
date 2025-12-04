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
require_once '../../model/ActivityLog.php';

// Create database connection
if (!isset($conn)) {
    $conn = new mysqli('localhost', 'root', '', 'ams_database');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_asset_tag') {
        $pc_id = intval($_POST['pc_id'] ?? 0);
        $new_asset_tag = trim($_POST['new_asset_tag'] ?? '');
        
        if ($pc_id <= 0 || empty($new_asset_tag)) {
            echo json_encode(['success' => false, 'message' => 'Invalid PC unit ID or asset tag']);
            exit;
        }
        
        try {
            $conn->begin_transaction();
            
            // Get old asset tag and PC info
            $select_stmt = $conn->prepare("SELECT asset_tag, terminal_number, room_id FROM pc_units WHERE id = ?");
            $select_stmt->bind_param('i', $pc_id);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            $pc_unit = $result->fetch_assoc();
            $select_stmt->close();
            
            if (!$pc_unit) {
                throw new Exception("PC unit not found");
            }
            
            $old_asset_tag = $pc_unit['asset_tag'];
            
            // Check if new asset tag already exists (excluding current PC unit)
            $check_stmt = $conn->prepare("SELECT id FROM pc_units WHERE asset_tag = ? AND id != ?");
            $check_stmt->bind_param('si', $new_asset_tag, $pc_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $check_stmt->close();
                throw new Exception("Asset tag already exists");
            }
            $check_stmt->close();
            
            // Update asset tag
            $update_stmt = $conn->prepare("UPDATE pc_units SET asset_tag = ? WHERE id = ?");
            $update_stmt->bind_param('si', $new_asset_tag, $pc_id);
            $success = $update_stmt->execute();
            $update_stmt->close();
            
            if ($success) {
                $user_id = $_SESSION['user_id'];
                
                // Log to activity_logs
                try {
                    ActivityLog::record(
                        $user_id,
                        'update',
                        'pc_unit',
                        "Updated asset tag from {$old_asset_tag} to {$new_asset_tag} for PC unit {$pc_unit['terminal_number']}"
                    );
                } catch (Exception $e) {
                    error_log("Failed to log to activity_logs: " . $e->getMessage());
                }
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Asset tag updated successfully']);
            } else {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Failed to update asset tag']);
            }
        } catch (Exception $e) {
            if ($conn) {
                $conn->rollback();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Get PC unit IDs from URL
$pc_ids_param = $_GET['pc_ids'] ?? '';
if (empty($pc_ids_param)) {
    header("Location: buildings.php");
    exit();
}

$pc_ids = array_map('intval', explode(',', $pc_ids_param));
$pc_ids = array_filter($pc_ids, function($id) { return $id > 0; });

if (empty($pc_ids)) {
    header("Location: buildings.php");
    exit();
}

// Fetch PC units
$placeholders = implode(',', array_fill(0, count($pc_ids), '?'));
$query = "SELECT p.id, p.terminal_number, p.asset_tag, p.status, p.notes, r.name as room_name, b.name as building_name 
          FROM pc_units p 
          LEFT JOIN rooms r ON p.room_id = r.id 
          LEFT JOIN buildings b ON r.building_id = b.id
          WHERE p.id IN ($placeholders)
          ORDER BY p.terminal_number";

$stmt = $conn->prepare($query);
$types = str_repeat('i', count($pc_ids));
$stmt->bind_param($types, ...$pc_ids);
$stmt->execute();
$result = $stmt->get_result();

$pc_units = [];
while ($row = $result->fetch_assoc()) {
    $pc_units[] = $row;
}
$stmt->close();

if (empty($pc_units)) {
    header("Location: buildings.php");
    exit();
}

// Get the room_id for back navigation (use first PC unit's room)
$first_pc_query = $conn->prepare("SELECT room_id FROM pc_units WHERE id = ?");
$first_pc_query->bind_param('i', $pc_ids[0]);
$first_pc_query->execute();
$first_pc_result = $first_pc_query->get_result();
$back_room = $first_pc_result->fetch_assoc();
$first_pc_query->close();
$back_room_id = $back_room['room_id'] ?? 0;

include '../components/layout_header.php';
?>

<style>
main {
    padding: 1rem;
    background-color: #f9fafb;
    min-height: 100vh;
}
</style>

<main>
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Edit PC Unit Asset Tags</h1>
                    <p class="text-sm text-gray-600 mt-1">Update asset tags for <?php echo count($pc_units); ?> selected PC unit(s)</p>
                </div>
                <a href="pcunits.php?room_id=<?php echo $back_room_id; ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-2"></i>Back to PC Units
                </a>
            </div>
        </div>

        <!-- PC Units List -->
        <div class="space-y-4">
            <?php foreach ($pc_units as $index => $pc_unit): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-start gap-6">
                    <!-- PC Number Badge -->
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-lg">
                            <?php echo $index + 1; ?>
                        </div>
                    </div>

                    <!-- PC Info -->
                    <div class="flex-1">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase">Terminal Number</label>
                                <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($pc_unit['terminal_number']); ?></p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase">Location</label>
                                <p class="text-sm text-gray-700">
                                    <?php echo htmlspecialchars($pc_unit['building_name'] ?: 'N/A'); ?> - 
                                    <?php echo htmlspecialchars($pc_unit['room_name'] ?: 'No Room'); ?>
                                </p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase">Status</label>
                                <p class="text-sm text-gray-700">
                                    <span class="px-2 py-1 rounded text-xs font-medium <?php 
                                        echo $pc_unit['status'] === 'Active' ? 'bg-green-100 text-green-700' : 
                                            ($pc_unit['status'] === 'Inactive' ? 'bg-gray-100 text-gray-700' : 
                                            ($pc_unit['status'] === 'Under Maintenance' ? 'bg-yellow-100 text-yellow-700' : 
                                            'bg-red-100 text-red-700'));
                                    ?>">
                                        <?php echo htmlspecialchars($pc_unit['status']); ?>
                                    </span>
                                </p>
                            </div>
                            <?php if (!empty($pc_unit['notes'])): ?>
                            <div class="md:col-span-2">
                                <label class="text-xs font-medium text-gray-500 uppercase">Notes</label>
                                <p class="text-sm text-gray-700"><?php echo htmlspecialchars($pc_unit['notes']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Asset Tag Editor -->
                        <div class="border-t pt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Current Asset Tag
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="text" 
                                       id="asset_tag_<?php echo $pc_unit['id']; ?>" 
                                       value="<?php echo htmlspecialchars($pc_unit['asset_tag']); ?>"
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm">
                                <button onclick="updateAssetTag(<?php echo $pc_unit['id']; ?>)" 
                                        id="save_btn_<?php echo $pc_unit['id']; ?>"
                                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                    <i class="fa-solid fa-save mr-2"></i>Save
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fa-solid fa-info-circle mr-1"></i>
                                Original: <span class="font-mono"><?php echo htmlspecialchars($pc_unit['asset_tag']); ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Save All Button -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-6 p-4">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Make sure to save each asset tag individually by clicking the Save button next to each field.
                </p>
                <a href="pcunits.php?room_id=<?php echo $back_room_id; ?>" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                    <i class="fa-solid fa-check mr-2"></i>Done
                </a>
            </div>
        </div>
    </div>
</main>

<script>
async function updateAssetTag(pcId) {
    const input = document.getElementById('asset_tag_' + pcId);
    const saveBtn = document.getElementById('save_btn_' + pcId);
    const newAssetTag = input.value.trim();
    
    if (!newAssetTag) {
        showAlert('error', 'Asset tag cannot be empty');
        return;
    }
    
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Saving...';
    
    try {
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('action', 'update_asset_tag');
        formData.append('pc_id', pcId);
        formData.append('new_asset_tag', newAssetTag);
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            saveBtn.innerHTML = '<i class="fa-solid fa-check mr-2"></i>Saved';
            saveBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            saveBtn.classList.add('bg-green-600', 'hover:bg-green-700');
            
            // Reset button after 2 seconds
            setTimeout(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
                saveBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                saveBtn.disabled = false;
            }, 2000);
        } else {
            showAlert('error', result.message || 'Failed to update asset tag');
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while updating the asset tag');
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
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
