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
        
        if ($action === 'add_brand') {
            $brand_name = trim($_POST['brand_name'] ?? '');
            
            if (empty($brand_name)) {
                echo json_encode(['success' => false, 'message' => 'Brand name is required']);
                exit();
            }
            
            // Check if brand already exists
            $check_stmt = $conn->prepare("SELECT id FROM asset_brand_categories WHERE name = ?");
            $check_stmt->bind_param('s', $brand_name);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Brand already exists']);
                $check_stmt->close();
                exit();
            }
            $check_stmt->close();
            
            // Insert new brand
            $insert_stmt = $conn->prepare("INSERT INTO asset_brand_categories (name) VALUES (?)");
            $insert_stmt->bind_param('s', $brand_name);
            
            if ($insert_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Brand added successfully', 'id' => $conn->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add brand']);
            }
            $insert_stmt->close();
            exit();
        }
        
        if ($action === 'rename_brand') {
            $brand_id = intval($_POST['brand_id'] ?? 0);
            $new_name = trim($_POST['new_name'] ?? '');
            
            if ($brand_id <= 0 || empty($new_name)) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit();
            }
            
            // Check if new name already exists
            $check_stmt = $conn->prepare("SELECT id FROM asset_brand_categories WHERE name = ? AND id != ?");
            $check_stmt->bind_param('si', $new_name, $brand_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Brand name already exists']);
                $check_stmt->close();
                exit();
            }
            $check_stmt->close();
            
            // Update brand name
            $update_stmt = $conn->prepare("UPDATE asset_brand_categories SET name = ? WHERE id = ?");
            $update_stmt->bind_param('si', $new_name, $brand_id);
            
            if ($update_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Brand renamed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to rename brand']);
            }
            $update_stmt->close();
            exit();
        }
        
        if ($action === 'delete_brand') {
            $brand_id = intval($_POST['brand_id'] ?? 0);
            
            if ($brand_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid brand ID']);
                exit();
            }
            
            // Check if brand is being used by any models
            $check_models = $conn->prepare("SELECT COUNT(*) as count FROM asset_model_categories WHERE brand_id = ?");
            $check_models->bind_param('i', $brand_id);
            $check_models->execute();
            $result = $check_models->get_result();
            $model_count = $result->fetch_assoc()['count'];
            $check_models->close();
            
            if ($model_count > 0) {
                echo json_encode(['success' => false, 'message' => "Cannot delete brand. It is used by $model_count model(s)"]);
                exit();
            }
            
            // Delete brand
            $delete_stmt = $conn->prepare("DELETE FROM asset_brand_categories WHERE id = ?");
            $delete_stmt->bind_param('i', $brand_id);
            
            if ($delete_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Brand deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete brand']);
            }
            $delete_stmt->close();
            exit();
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Fetch all brands
$brands_query = "SELECT * FROM asset_brand_categories ORDER BY name ASC";
$brands_result = $conn->query($brands_query);
$brands = [];
if ($brands_result && $brands_result->num_rows > 0) {
    while ($row = $brands_result->fetch_assoc()) {
        $brands[] = $row;
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
                    <p class="font-medium mb-1">About Brand Categories</p>
                    <p>Manage asset brand categories here. Brands can be associated with models and assets for better organization.</p>
                    <p class="mt-1 text-xs">Examples: Dell, HP, Lenovo, Acer, Asus, etc.</p>
                </div>
            </div>
        </div>

        <!-- Actions Bar -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">All Brands</h2>
                    <p class="text-sm text-gray-600 mt-1"><?php echo count($brands); ?> total brands</p>
                </div>
                <button onclick="openAddBrandModal()" 
                        class="px-4 py-2 bg-[#1E3A8A] text-white rounded-lg hover:bg-[#153570] transition-colors flex items-center text-sm font-medium shadow-sm">
                    <i class="fa-solid fa-plus mr-2"></i>Add Brand
                </button>
            </div>
        </div>

        <!-- Brands Table -->
        <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#1E3A8A] text-white sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">#</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Brand ID</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Brand Name</th>
                        <th class="px-3 py-2 text-center text-xs font-medium uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                    if (!empty($brands)):
                        $index = 0;
                        foreach ($brands as $brand): 
                            $index++;
                    ?>
                    <tr class="hover:bg-blue-50 transition-colors">
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                            <?php echo $index; ?>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs font-medium text-blue-600">
                            <?php echo $brand['id']; ?>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-900">
                            <div class="flex items-center">
                                <i class="fa-solid fa-copyright mr-2 text-gray-400"></i>
                                <span class="font-medium"><?php echo htmlspecialchars($brand['name']); ?></span>
                            </div>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                            <div class="flex gap-1 justify-center">
                                <button onclick="renameBrandModal(<?php echo $brand['id']; ?>, '<?php echo htmlspecialchars($brand['name'], ENT_QUOTES); ?>')"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors" 
                                        title="Rename">
                                    <i class="fas fa-edit text-xs"></i>
                                </button>
                                <button onclick="deleteBrand(<?php echo $brand['id']; ?>, '<?php echo htmlspecialchars($brand['name'], ENT_QUOTES); ?>')"
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
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-box-open text-5xl mb-3 opacity-30"></i>
                            <p class="text-lg font-semibold">No brands found</p>
                            <p class="text-sm">Add your first brand to get started</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Add Brand Modal -->
<div id="addBrandModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Add Brand</h3>
            <button onclick="closeAddBrandModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Brand Name</label>
            <input type="text" id="newBrandName" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent"
                   placeholder="e.g., Dell, HP, Lenovo"
                   onkeypress="if(event.key === 'Enter') addBrand()">
        </div>
        
        <div class="flex justify-end gap-3">
            <button onclick="closeAddBrandModal()" 
                    class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 text-sm font-medium">
                Cancel
            </button>
            <button onclick="addBrand()" 
                    class="px-4 py-2 bg-[#1E3A8A] text-white rounded-md hover:bg-[#153570] text-sm font-medium">
                <i class="fa-solid fa-plus mr-2"></i>Add Brand
            </button>
        </div>
    </div>
</div>

<!-- Rename Brand Modal -->
<div id="renameBrandModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Rename Brand</h3>
            <button onclick="closeRenameBrandModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">New Name</label>
            <input type="hidden" id="renameBrandId">
            <input type="text" id="renameBrandName" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent"
                   onkeypress="if(event.key === 'Enter') confirmRenameBrand()">
        </div>
        
        <div class="flex justify-end gap-3">
            <button onclick="closeRenameBrandModal()" 
                    class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 text-sm font-medium">
                Cancel
            </button>
            <button onclick="confirmRenameBrand()" 
                    class="px-4 py-2 bg-[#1E3A8A] text-white rounded-md hover:bg-[#153570] text-sm font-medium">
                <i class="fa-solid fa-save mr-2"></i>Save
            </button>
        </div>
    </div>
</div>

<!-- Delete Brand Modal -->
<div id="deleteBrandModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-red-600">Delete Brand</h3>
            <button onclick="closeDeleteBrandModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        
        <div class="mb-4">
            <input type="hidden" id="deleteBrandId">
            <input type="hidden" id="deleteBrandName">
            <div class="flex items-start">
                <i class="fa-solid fa-exclamation-triangle text-red-500 mt-0.5 mr-3 text-lg"></i>
                <div>
                    <p class="text-sm text-gray-900 font-medium mb-2">Are you sure you want to delete this brand?</p>
                    <p class="text-sm text-gray-700 mb-2" id="deleteBrandText"></p>
                    <p class="text-xs text-red-600 font-medium">This action cannot be undone!</p>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end gap-3">
            <button onclick="closeDeleteBrandModal()" 
                    class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 text-sm font-medium">
                Cancel
            </button>
            <button onclick="confirmDeleteBrand()" 
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm font-medium">
                <i class="fa-solid fa-trash mr-2"></i>Delete Brand
            </button>
        </div>
    </div>
</div>

<script>
// Add Brand Modal
function openAddBrandModal() {
    document.getElementById('addBrandModal').classList.remove('hidden');
    document.getElementById('newBrandName').focus();
}

function closeAddBrandModal() {
    document.getElementById('addBrandModal').classList.add('hidden');
    document.getElementById('newBrandName').value = '';
}

async function addBrand() {
    const brandName = document.getElementById('newBrandName').value.trim().toUpperCase();
    
    if (!brandName) {
        showAlert('error', 'Please enter a brand name');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'add_brand');
        formData.append('brand_name', brandName);
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            closeAddBrandModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while adding the brand');
    }
}

// Rename Brand
function renameBrandModal(brandId, currentName) {
    document.getElementById('renameBrandId').value = brandId;
    document.getElementById('renameBrandName').value = currentName;
    document.getElementById('renameBrandModal').classList.remove('hidden');
    document.getElementById('renameBrandName').focus();
}

function closeRenameBrandModal() {
    document.getElementById('renameBrandModal').classList.add('hidden');
    document.getElementById('renameBrandId').value = '';
    document.getElementById('renameBrandName').value = '';
}

async function confirmRenameBrand() {
    const brandId = document.getElementById('renameBrandId').value;
    const newName = document.getElementById('renameBrandName').value.trim().toUpperCase();
    
    if (!newName) {
        showAlert('error', 'Please enter a brand name');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'rename_brand');
        formData.append('brand_id', brandId);
        formData.append('new_name', newName);
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            closeRenameBrandModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while renaming the brand');
    }
}

// Delete Brand
function deleteBrand(brandId, brandName) {
    document.getElementById('deleteBrandId').value = brandId;
    document.getElementById('deleteBrandName').value = brandName;
    document.getElementById('deleteBrandText').textContent = `Brand: "${brandName}"`;
    document.getElementById('deleteBrandModal').classList.remove('hidden');
}

function closeDeleteBrandModal() {
    document.getElementById('deleteBrandModal').classList.add('hidden');
    document.getElementById('deleteBrandId').value = '';
    document.getElementById('deleteBrandName').value = '';
}

async function confirmDeleteBrand() {
    const brandId = document.getElementById('deleteBrandId').value;
    
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'delete_brand');
        formData.append('brand_id', brandId);
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            closeDeleteBrandModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while deleting the brand');
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
document.getElementById('addBrandModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAddBrandModal();
});

document.getElementById('renameBrandModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeRenameBrandModal();
});

document.getElementById('deleteBrandModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeDeleteBrandModal();
});
</script>

<?php include '../components/layout_footer.php'; ?>
