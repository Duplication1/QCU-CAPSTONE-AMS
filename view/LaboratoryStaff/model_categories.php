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
        die("Connection failed: " . $conn->connect_error);
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_model') {
            $model_name = trim($_POST['model_name'] ?? '');
            $brand_id = intval($_POST['brand_id'] ?? 0);
            
            if (empty($model_name) || $brand_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Model name and brand are required']);
                exit();
            }
            
            // Check if model already exists for this brand
            $check_stmt = $conn->prepare("SELECT id FROM asset_model_categories WHERE name = ? AND brand_id = ?");
            $check_stmt->bind_param('si', $model_name, $brand_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Model already exists for this brand']);
                $check_stmt->close();
                exit();
            }
            $check_stmt->close();
            
            // Insert new model
            $insert_stmt = $conn->prepare("INSERT INTO asset_model_categories (name, brand_id) VALUES (?, ?)");
            $insert_stmt->bind_param('si', $model_name, $brand_id);
            
            if ($insert_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Model added successfully', 'id' => $conn->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add model']);
            }
            $insert_stmt->close();
            exit();
        }
        
        if ($action === 'rename_model') {
            $model_id = intval($_POST['model_id'] ?? 0);
            $new_name = trim($_POST['new_name'] ?? '');
            
            if ($model_id <= 0 || empty($new_name)) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit();
            }
            
            // Get the brand_id of the model being renamed
            $get_brand_stmt = $conn->prepare("SELECT brand_id FROM asset_model_categories WHERE id = ?");
            $get_brand_stmt->bind_param('i', $model_id);
            $get_brand_stmt->execute();
            $result = $get_brand_stmt->get_result();
            $brand_id = $result->fetch_assoc()['brand_id'];
            $get_brand_stmt->close();
            
            // Check if new name already exists for this brand
            $check_stmt = $conn->prepare("SELECT id FROM asset_model_categories WHERE name = ? AND brand_id = ? AND id != ?");
            $check_stmt->bind_param('sii', $new_name, $brand_id, $model_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Model name already exists for this brand']);
                $check_stmt->close();
                exit();
            }
            $check_stmt->close();
            
            // Update model name
            $update_stmt = $conn->prepare("UPDATE asset_model_categories SET name = ? WHERE id = ?");
            $update_stmt->bind_param('si', $new_name, $model_id);
            
            if ($update_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Model renamed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to rename model']);
            }
            $update_stmt->close();
            exit();
        }
        
        if ($action === 'delete_model') {
            $model_id = intval($_POST['model_id'] ?? 0);
            
            if ($model_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid model ID']);
                exit();
            }
            
            // Delete model
            $delete_stmt = $conn->prepare("DELETE FROM asset_model_categories WHERE id = ?");
            $delete_stmt->bind_param('i', $model_id);
            
            if ($delete_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Model deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete model']);
            }
            $delete_stmt->close();
            exit();
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Fetch all brands for dropdown
$brands_query = "SELECT * FROM asset_brand_categories ORDER BY name ASC";
$brands_result = $conn->query($brands_query);
$brands = [];
if ($brands_result && $brands_result->num_rows > 0) {
    while ($row = $brands_result->fetch_assoc()) {
        $brands[] = $row;
    }
}

// Fetch all models with brand information
$models_query = "SELECT m.*, b.name as brand_name 
                 FROM asset_model_categories m 
                 JOIN asset_brand_categories b ON m.brand_id = b.id 
                 ORDER BY b.name ASC, m.name ASC";
$models_result = $conn->query($models_query);
$models = [];
if ($models_result && $models_result->num_rows > 0) {
    while ($row = $models_result->fetch_assoc()) {
        $models[] = $row;
    }
}

include '../components/layout_header.php';
?>

<style>
main {
    padding: 0.5rem;
    background-color: #f9fafb;
    min-height: 100%;
}
</style>

<main>
    <div class="flex-1 flex flex-col">
        <!-- Info Banner -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4 rounded-lg">
            <div class="flex items-start">
                <i class="fa-solid fa-info-circle text-blue-600 mt-0.5 mr-3"></i>
                <div class="text-sm text-blue-900">
                    <p class="font-medium mb-1">About Model Categories</p>
                    <p>Manage asset model categories here. Models are associated with specific brands and assets for better organization.</p>
                    <p class="mt-1 text-xs">Examples: Latitude 5420, EliteBook 840, ThinkPad T14, etc.</p>
                </div>
            </div>
        </div>

        <!-- Actions Bar -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">All Models</h2>
                    <p class="text-sm text-gray-600 mt-1"><?php echo count($models); ?> total models across <?php echo count($brands); ?> brands</p>
                </div>
                <button onclick="openAddModelModal()" 
                        class="px-4 py-2 bg-[#1E3A8A] text-white rounded-lg hover:bg-[#153570] transition-colors flex items-center text-sm font-medium shadow-sm"
                        <?php echo empty($brands) ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-plus mr-2"></i>Add Model
                </button>
            </div>
            <?php if (empty($brands)): ?>
            <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-sm text-yellow-800">
                    <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                    Please add at least one brand before creating models.
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Models Table -->
        <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#1E3A8A] text-white sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">#</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Model ID</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Brand</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Model Name</th>
                        <th class="px-3 py-2 text-center text-xs font-medium uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                    if (!empty($models)):
                        $index = 0;
                        foreach ($models as $model): 
                            $index++;
                    ?>
                    <tr class="hover:bg-blue-50 transition-colors">
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                            <?php echo $index; ?>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs font-medium text-blue-600">
                            <?php echo $model['id']; ?>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-700">
                            <div class="flex items-center">
                                <i class="fa-solid fa-copyright mr-2 text-gray-400"></i>
                                <span class="font-medium"><?php echo htmlspecialchars($model['brand_name']); ?></span>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-900">
                            <div class="flex items-center">
                                <i class="fa-solid fa-tag mr-2 text-gray-400"></i>
                                <span class="font-semibold"><?php echo htmlspecialchars($model['name']); ?></span>
                            </div>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                            <div class="flex gap-1 justify-center">
                                <button onclick="renameModelModal(<?php echo $model['id']; ?>, '<?php echo htmlspecialchars($model['name'], ENT_QUOTES); ?>')"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors" 
                                        title="Rename">
                                    <i class="fas fa-edit text-xs"></i>
                                </button>
                                <button onclick="deleteModel(<?php echo $model['id']; ?>, '<?php echo htmlspecialchars($model['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($model['brand_name'], ENT_QUOTES); ?>')"
                                        class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors" 
                                        title="Delete">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-box-open text-5xl mb-3 opacity-30"></i>
                            <p class="text-lg font-semibold">No models found</p>
                            <p class="text-sm">Add your first model to get started</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Add Model Modal -->
<div id="addModelModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Add Model</h3>
            <button onclick="closeAddModelModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
            <select id="newModelBrand" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                <option value="">Select Brand</option>
                <?php foreach ($brands as $brand): ?>
                <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Model Name</label>
            <input type="text" id="newModelName" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent"
                   placeholder="e.g., Latitude 5420, EliteBook 840"
                   onkeypress="if(event.key === 'Enter') addModel()">
        </div>
        
        <div class="flex justify-end gap-3">
            <button onclick="closeAddModelModal()" 
                    class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 text-sm font-medium">
                Cancel
            </button>
            <button onclick="addModel()" 
                    class="px-4 py-2 bg-[#1E3A8A] text-white rounded-md hover:bg-[#153570] text-sm font-medium">
                <i class="fa-solid fa-plus mr-2"></i>Add Model
            </button>
        </div>
    </div>
</div>

<!-- Rename Model Modal -->
<div id="renameModelModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Rename Model</h3>
            <button onclick="closeRenameModelModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">New Name</label>
            <input type="hidden" id="renameModelId">
            <input type="text" id="renameModelName" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent"
                   onkeypress="if(event.key === 'Enter') confirmRenameModel()">
        </div>
        
        <div class="flex justify-end gap-3">
            <button onclick="closeRenameModelModal()" 
                    class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 text-sm font-medium">
                Cancel
            </button>
            <button onclick="confirmRenameModel()" 
                    class="px-4 py-2 bg-[#1E3A8A] text-white rounded-md hover:bg-[#153570] text-sm font-medium">
                <i class="fa-solid fa-save mr-2"></i>Save
            </button>
        </div>
    </div>
</div>

<!-- Delete Model Modal -->
<div id="deleteModelModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-red-600">Delete Model</h3>
            <button onclick="closeDeleteModelModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        
        <div class="mb-4">
            <input type="hidden" id="deleteModelId">
            <input type="hidden" id="deleteModelName">
            <input type="hidden" id="deleteModelBrand">
            <div class="flex items-start">
                <i class="fa-solid fa-exclamation-triangle text-red-500 mt-0.5 mr-3 text-lg"></i>
                <div>
                    <p class="text-sm text-gray-900 font-medium mb-2">Are you sure you want to delete this model?</p>
                    <p class="text-sm text-gray-700 mb-2" id="deleteModelText"></p>
                    <p class="text-xs text-red-600 font-medium">This action cannot be undone!</p>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end gap-3">
            <button onclick="closeDeleteModelModal()" 
                    class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 text-sm font-medium">
                Cancel
            </button>
            <button onclick="confirmDeleteModel()" 
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm font-medium">
                <i class="fa-solid fa-trash mr-2"></i>Delete Model
            </button>
        </div>
    </div>
</div>

<script>
// Add Model Modal
function openAddModelModal() {
    document.getElementById('addModelModal').classList.remove('hidden');
    document.getElementById('newModelBrand').focus();
}

function closeAddModelModal() {
    document.getElementById('addModelModal').classList.add('hidden');
    document.getElementById('newModelBrand').value = '';
    document.getElementById('newModelName').value = '';
}

async function addModel() {
    const brandId = document.getElementById('newModelBrand').value;
    const modelName = document.getElementById('newModelName').value.trim().toUpperCase();
    
    if (!brandId) {
        showAlert('error', 'Please select a brand');
        return;
    }
    
    if (!modelName) {
        showAlert('error', 'Please enter a model name');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'add_model');
        formData.append('brand_id', brandId);
        formData.append('model_name', modelName);
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            closeAddModelModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while adding the model');
    }
}

// Rename Model
function renameModelModal(modelId, currentName) {
    document.getElementById('renameModelId').value = modelId;
    document.getElementById('renameModelName').value = currentName;
    document.getElementById('renameModelModal').classList.remove('hidden');
    document.getElementById('renameModelName').focus();
}

function closeRenameModelModal() {
    document.getElementById('renameModelModal').classList.add('hidden');
    document.getElementById('renameModelId').value = '';
    document.getElementById('renameModelName').value = '';
}

async function confirmRenameModel() {
    const modelId = document.getElementById('renameModelId').value;
    const newName = document.getElementById('renameModelName').value.trim().toUpperCase();
    
    if (!newName) {
        showAlert('error', 'Please enter a model name');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'rename_model');
        formData.append('model_id', modelId);
        formData.append('new_name', newName);
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            closeRenameModelModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while renaming the model');
    }
}

// Delete Model
function deleteModel(modelId, modelName, brandName) {
    document.getElementById('deleteModelId').value = modelId;
    document.getElementById('deleteModelName').value = modelName;
    document.getElementById('deleteModelBrand').value = brandName;
    document.getElementById('deleteModelText').textContent = `${brandName} - ${modelName}`;
    document.getElementById('deleteModelModal').classList.remove('hidden');
}

function closeDeleteModelModal() {
    document.getElementById('deleteModelModal').classList.add('hidden');
    document.getElementById('deleteModelId').value = '';
    document.getElementById('deleteModelName').value = '';
    document.getElementById('deleteModelBrand').value = '';
}

async function confirmDeleteModel() {
    const modelId = document.getElementById('deleteModelId').value;
    
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'delete_model');
        formData.append('model_id', modelId);
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            closeDeleteModelModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while deleting the model');
    }
}

// Show Alert
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    } text-white font-medium`;
    alertDiv.textContent = message;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => alertDiv.remove(), 3000);
}

// Close modals when clicking outside
document.getElementById('addModelModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAddModelModal();
});

document.getElementById('renameModelModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeRenameModelModal();
});

document.getElementById('deleteModelModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModelModal();
});
</script>

<?php include '../components/layout_footer.php'; ?>
