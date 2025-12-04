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
require_once '../../model/AssetHistory.php';

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
        $asset_id = intval($_POST['asset_id'] ?? 0);
        $new_asset_tag = trim($_POST['new_asset_tag'] ?? '');
        
        if ($asset_id <= 0 || empty($new_asset_tag)) {
            echo json_encode(['success' => false, 'message' => 'Invalid asset ID or asset tag']);
            exit;
        }
        
        try {
            $conn->begin_transaction();
            
            // Get old asset tag and asset info
            $select_stmt = $conn->prepare("SELECT asset_tag, asset_name FROM assets WHERE id = ?");
            $select_stmt->bind_param('i', $asset_id);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            $asset = $result->fetch_assoc();
            $select_stmt->close();
            
            if (!$asset) {
                throw new Exception("Asset not found");
            }
            
            $old_asset_tag = $asset['asset_tag'];
            
            // Check if new tag already exists (excluding current asset)
            $check_stmt = $conn->prepare("SELECT id FROM assets WHERE asset_tag = ? AND id != ?");
            $check_stmt->bind_param('si', $new_asset_tag, $asset_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $check_stmt->close();
                throw new Exception("Asset tag already exists");
            }
            $check_stmt->close();
            
            // Update asset tag
            $update_stmt = $conn->prepare("UPDATE assets SET asset_tag = ? WHERE id = ?");
            $update_stmt->bind_param('si', $new_asset_tag, $asset_id);
            $success = $update_stmt->execute();
            $update_stmt->close();
            
            if ($success) {
                $user_id = $_SESSION['user_id'];
                
                // Log to activity_logs
                try {
                    ActivityLog::record(
                        $user_id,
                        'update',
                        'asset',
                        "Updated asset tag from {$old_asset_tag} to {$new_asset_tag} for {$asset['asset_name']}"
                    );
                } catch (Exception $e) {
                    error_log("Failed to log to activity_logs: " . $e->getMessage());
                }
                
                // Log to asset_history
                try {
                    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                    
                    // Get user's full name
                    $performed_by_name = null;
                    $user_query = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
                    $user_query->bind_param('i', $user_id);
                    $user_query->execute();
                    $user_result = $user_query->get_result();
                    if ($user_row = $user_result->fetch_assoc()) {
                        $performed_by_name = $user_row['full_name'];
                    }
                    $user_query->close();
                    
                    // Insert asset tag change history
                    $history_stmt = $conn->prepare("INSERT INTO asset_history (asset_id, action_type, field_changed, old_value, new_value, description, performed_by, performed_by_name, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $action_type = 'Updated';
                    $field_changed = 'Asset Tag';
                    $description = "Asset tag changed from {$old_asset_tag} to {$new_asset_tag}";
                    $history_stmt->bind_param('isssssssss', $asset_id, $action_type, $field_changed, $old_asset_tag, $new_asset_tag, $description, $user_id, $performed_by_name, $ip_address, $user_agent);
                    $history_stmt->execute();
                    $history_stmt->close();
                } catch (Exception $e) {
                    error_log("Failed to log to asset_history: " . $e->getMessage());
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

// Get asset IDs from URL
$asset_ids_param = $_GET['asset_ids'] ?? '';
if (empty($asset_ids_param)) {
    header("Location: standbyassets.php");
    exit();
}

$asset_ids = array_map('intval', explode(',', $asset_ids_param));
$asset_ids = array_filter($asset_ids, function($id) { return $id > 0; });

if (empty($asset_ids)) {
    header("Location: standbyassets.php");
    exit();
}

// Fetch standby assets only (room_id IS NULL OR room_id = 0)
$placeholders = implode(',', array_fill(0, count($asset_ids), '?'));
$query = "SELECT a.id, a.asset_tag, a.asset_name, a.brand, a.model, a.serial_number, a.status, a.condition, r.name as room_name 
          FROM assets a 
          LEFT JOIN rooms r ON a.room_id = r.id 
          WHERE a.id IN ($placeholders) AND (a.room_id IS NULL OR a.room_id = 0)
          ORDER BY a.asset_tag";

$stmt = $conn->prepare($query);
$types = str_repeat('i', count($asset_ids));
$stmt->bind_param($types, ...$asset_ids);
$stmt->execute();
$result = $stmt->get_result();

$assets = [];
while ($row = $result->fetch_assoc()) {
    $assets[] = $row;
}
$stmt->close();

if (empty($assets)) {
    header("Location: standbyassets.php");
    exit();
}

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
                    <h1 class="text-2xl font-bold text-gray-800">Edit Standby Asset Tags</h1>
                    <p class="text-sm text-gray-600 mt-1">Update asset tags for <?php echo count($assets); ?> selected standby asset(s)</p>
                </div>
                <a href="standbyassets.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-2"></i>Back to Standby Assets
                </a>
            </div>
        </div>

        <!-- Assets List -->
        <div class="space-y-4">
            <?php foreach ($assets as $index => $asset): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-start gap-6">
                    <!-- Asset Number Badge -->
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-lg">
                            <?php echo $index + 1; ?>
                        </div>
                    </div>

                    <!-- Asset Info -->
                    <div class="flex-1">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase">Asset Name</label>
                                <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($asset['asset_name']); ?></p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase">Room</label>
                                <p class="text-sm text-gray-700">
                                    <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs font-medium">Standby (NOROOM)</span>
                                </p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase">Brand / Model</label>
                                <p class="text-sm text-gray-700"><?php echo htmlspecialchars(($asset['brand'] ?: 'N/A') . ' / ' . ($asset['model'] ?: 'N/A')); ?></p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 uppercase">Serial Number</label>
                                <p class="text-sm text-gray-700"><?php echo htmlspecialchars($asset['serial_number'] ?: 'N/A'); ?></p>
                            </div>
                        </div>

                        <!-- Asset Tag Editor -->
                        <div class="border-t pt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Current Asset Tag
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="text" 
                                       id="asset_tag_<?php echo $asset['id']; ?>" 
                                       value="<?php echo htmlspecialchars($asset['asset_tag']); ?>"
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm">
                                <button onclick="updateAssetTag(<?php echo $asset['id']; ?>)" 
                                        id="save_btn_<?php echo $asset['id']; ?>"
                                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                    <i class="fa-solid fa-save mr-2"></i>Save
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fa-solid fa-info-circle mr-1"></i>
                                Original: <span class="font-mono"><?php echo htmlspecialchars($asset['asset_tag']); ?></span>
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
                <a href="standbyassets.php" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                    <i class="fa-solid fa-check mr-2"></i>Done
                </a>
            </div>
        </div>
    </div>
</main>

<script>
async function updateAssetTag(assetId) {
    const input = document.getElementById('asset_tag_' + assetId);
    const saveBtn = document.getElementById('save_btn_' + assetId);
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
        formData.append('asset_id', assetId);
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
