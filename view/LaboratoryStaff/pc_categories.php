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
        
        if ($action === 'add_pc_category') {
            $category_name = trim($_POST['category_name'] ?? '');
            $end_of_life = !empty($_POST['end_of_life']) ? intval($_POST['end_of_life']) : null;
            
            if (empty($category_name)) {
                echo json_encode(['success' => false, 'message' => 'Category name is required']);
                exit();
            }
            
            // Check if category already exists
            $check_stmt = $conn->prepare("SELECT id FROM asset_categories WHERE name = ?");
            $check_stmt->bind_param('s', $category_name);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Category already exists']);
                $check_stmt->close();
                exit();
            }
            $check_stmt->close();
            
            // Insert new PC category
            $insert_stmt = $conn->prepare("INSERT INTO asset_categories (name, is_pc_category, end_of_life) VALUES (?, 1, ?)");
            $insert_stmt->bind_param('si', $category_name, $end_of_life);
            
            if ($insert_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'PC category added successfully', 'id' => $conn->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add PC category']);
            }
            $insert_stmt->close();
            exit();
        }
        
        if ($action === 'update_pc_category') {
            $category_id = intval($_POST['category_id'] ?? 0);
            $is_pc_category = intval($_POST['is_pc_category'] ?? 0);
            
            if ($category_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
                exit();
            }
            
            $update_stmt = $conn->prepare("UPDATE asset_categories SET is_pc_category = ? WHERE id = ?");
            $update_stmt->bind_param('ii', $is_pc_category, $category_id);
            
            if ($update_stmt->execute()) {
                $status = $is_pc_category ? 'enabled' : 'disabled';
                echo json_encode(['success' => true, 'message' => "PC category $status successfully"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update PC category']);
            }
            $update_stmt->close();
            exit();
        }
        
        if ($action === 'delete_pc_category') {
            $category_id = intval($_POST['category_id'] ?? 0);
            
            if ($category_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
                exit();
            }
            
            // Check if category is being used by any assets
            $check_assets = $conn->prepare("SELECT COUNT(*) as count FROM assets WHERE category = ?");
            $check_assets->bind_param('i', $category_id);
            $check_assets->execute();
            $result = $check_assets->get_result();
            $asset_count = $result->fetch_assoc()['count'];
            $check_assets->close();
            
            if ($asset_count > 0) {
                echo json_encode(['success' => false, 'message' => "Cannot delete category. It is used by $asset_count asset(s)"]);
                exit();
            }
            
            // Delete category
            $delete_stmt = $conn->prepare("DELETE FROM asset_categories WHERE id = ?");
            $delete_stmt->bind_param('i', $category_id);
            
            if ($delete_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'PC category deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete PC category']);
            }
            $delete_stmt->close();
            exit();
        }
        
        if ($action === 'update_end_of_life') {
            $category_id = intval($_POST['category_id'] ?? 0);
            $end_of_life = !empty($_POST['end_of_life']) ? intval($_POST['end_of_life']) : null;
            
            if ($category_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
                exit();
            }
            
            $update_stmt = $conn->prepare("UPDATE asset_categories SET end_of_life = ? WHERE id = ?");
            $update_stmt->bind_param('ii', $end_of_life, $category_id);
            
            if ($update_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'End of life updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update end of life']);
            }
            $update_stmt->close();
            exit();
        }
        
        if ($action === 'rename_category') {
            $category_id = intval($_POST['category_id'] ?? 0);
            $new_name = trim($_POST['new_name'] ?? '');
            
            if ($category_id <= 0 || empty($new_name)) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit();
            }
            
            // Check if new name already exists
            $check_stmt = $conn->prepare("SELECT id FROM asset_categories WHERE name = ? AND id != ?");
            $check_stmt->bind_param('si', $new_name, $category_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Category name already exists']);
                $check_stmt->close();
                exit();
            }
            $check_stmt->close();
            
            // Update category name
            $update_stmt = $conn->prepare("UPDATE asset_categories SET name = ? WHERE id = ?");
            $update_stmt->bind_param('si', $new_name, $category_id);
            
            if ($update_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Category renamed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to rename category']);
            }
            $update_stmt->close();
            exit();
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Fetch all categories
$categories_query = "SELECT * FROM asset_categories ORDER BY name ASC";
$categories_result = $conn->query($categories_query);
$categories = [];
if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
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

.switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #1E3A8A;
}

input:checked + .slider:before {
    transform: translateX(20px);
}

input:disabled + .slider {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

<main>
    <div class="flex-1 flex flex-col">
        <!-- Header -->
        

        <!-- Info Banner -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4 rounded-lg">
            <div class="flex items-start">
                <i class="fa-solid fa-info-circle text-blue-600 mt-0.5 mr-3"></i>
                <div class="text-sm text-blue-900">
                    <p class="font-medium mb-1">About PC Component Categories</p>
                    <p>Categories marked as "PC Component" will appear as options when adding new PC units. Components selected during PC creation will automatically create corresponding assets.</p>
                    <p class="mt-1 text-xs">Examples: CPU, RAM, Monitor, Keyboard, Mouse, Motherboard, Hard Drive, etc.</p>
                </div>
            </div>
        </div>

        <!-- Actions Bar -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">All Categories</h2>
                    <p class="text-sm text-gray-600 mt-1"><?php echo count($categories); ?> total categories</p>
                </div>
                <button onclick="openAddCategoryModal()" 
                        class="px-4 py-2 bg-[#1E3A8A] text-white rounded-lg hover:bg-[#153570] transition-colors flex items-center text-sm font-medium shadow-sm">
                    <i class="fa-solid fa-plus mr-2"></i>Add PC Category
                </button>
            </div>
        </div>

        <!-- Categories Table -->
        <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#1E3A8A] text-white sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">#</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Category ID</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Category Name</th>
                        <th class="px-3 py-2 text-center text-xs font-medium uppercase tracking-wider">End of Life (Years)</th>
                        <th class="px-3 py-2 text-center text-xs font-medium uppercase tracking-wider">PC Component</th>
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                        <th class="px-3 py-2 text-center text-xs font-medium uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                    if (!empty($categories)):
                        $index = 0;
                        foreach ($categories as $category): 
                            $index++;
                    ?>
                    <tr class="hover:bg-blue-50 transition-colors">
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                            <?php echo $index; ?>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs font-medium text-blue-600">
                            <?php echo $category['id']; ?>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-900">
                            <div class="flex items-center">
                                <i class="fa-solid fa-tag mr-2 text-gray-400"></i>
                                <span class="font-medium"><?php echo htmlspecialchars($category['name']); ?></span>
                            </div>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-2">
                                <?php if ($category['end_of_life']): ?>
                                    <span class="text-xs font-medium text-gray-700"><?php echo $category['end_of_life']; ?> years</span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400 italic">Not set</span>
                                <?php endif; ?>
                                <button onclick="editEndOfLife(<?php echo $category['id']; ?>, <?php echo $category['end_of_life'] ?? 'null'; ?>)"
                                        class="p-1 text-blue-600 hover:bg-blue-50 rounded transition-colors" 
                                        title="Edit End of Life">
                                    <i class="fas fa-edit text-xs"></i>
                                </button>
                            </div>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-center">
                            <label class="switch inline-block">
                                <input type="checkbox" 
                                       <?php echo $category['is_pc_category'] ? 'checked' : ''; ?>
                                       onchange="togglePCCategory(<?php echo $category['id']; ?>, this.checked, event)">
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs">
                            <?php if ($category['is_pc_category']): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fa-solid fa-check mr-1"></i>
                                    Available in PC Creation
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                    <i class="fa-solid fa-minus mr-1"></i>
                                    Not a PC Component
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                            <div class="flex gap-1 justify-center">
                                <button onclick="renameCategoryModal(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>')"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors" 
                                        title="Rename">
                                    <i class="fas fa-edit text-xs"></i>
                                </button>
                                <button onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>')"
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
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-box-open text-5xl mb-3 opacity-30"></i>
                            <p class="text-lg font-semibold">No categories found</p>
                            <p class="text-sm">Add your first PC component category to get started</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Add PC Component Category</h3>
            <button onclick="closeAddCategoryModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Category Name</label>
            <input type="text" id="newCategoryName" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent"
                   placeholder="e.g., CPU, RAM, Monitor"
                   onkeypress="if(event.key === 'Enter') addPCCategory()">
            <p class="text-xs text-gray-500 mt-1">This will be automatically marked as a PC component</p>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">End of Life (Years)</label>
            <input type="number" id="newCategoryEndOfLife" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent"
                   placeholder="e.g., 5"
                   min="1"
                   step="1"
                   onkeypress="if(event.key === 'Enter') addPCCategory()">
            <p class="text-xs text-gray-500 mt-1">Expected lifespan in years for assets in this category</p>
        </div>
        
        <div class="flex justify-end gap-3">
            <button onclick="closeAddCategoryModal()" 
                    class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 text-sm font-medium">
                Cancel
            </button>
            <button onclick="addPCCategory()" 
                    class="px-4 py-2 bg-[#1E3A8A] text-white rounded-md hover:bg-[#153570] text-sm font-medium">
                <i class="fa-solid fa-plus mr-2"></i>Add Category
            </button>
        </div>
    </div>
</div>

<!-- Rename Category Modal -->
<div id="renameCategoryModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Rename Category</h3>
            <button onclick="closeRenameCategoryModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">New Name</label>
            <input type="hidden" id="renameCategoryId">
            <input type="text" id="renameCategoryName" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent"
                   onkeypress="if(event.key === 'Enter') confirmRenameCategory()">
        </div>
        
        <div class="flex justify-end gap-3">
            <button onclick="closeRenameCategoryModal()" 
                    class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 text-sm font-medium">
                Cancel
            </button>
            <button onclick="confirmRenameCategory()" 
                    class="px-4 py-2 bg-[#1E3A8A] text-white rounded-md hover:bg-[#153570] text-sm font-medium">
                <i class="fa-solid fa-save mr-2"></i>Save
            </button>
        </div>
    </div>
</div>

<!-- Edit End of Life Modal -->
<div id="editEndOfLifeModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Edit End of Life</h3>
            <button onclick="closeEditEndOfLifeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Expected Lifespan (Years)</label>
            <input type="hidden" id="editEndOfLifeCategoryId">
            <input type="number" id="editEndOfLifeValue" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent"
                   placeholder="e.g., 5"
                   min="1"
                   step="1"
                   onkeypress="if(event.key === 'Enter') confirmUpdateEndOfLife()">
            <p class="text-xs text-gray-500 mt-1">Leave empty to remove end of life for this category</p>
        </div>
        
        <div class="flex justify-end gap-3">
            <button onclick="closeEditEndOfLifeModal()" 
                    class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 text-sm font-medium">
                Cancel
            </button>
            <button onclick="confirmUpdateEndOfLife()" 
                    class="px-4 py-2 bg-[#1E3A8A] text-white rounded-md hover:bg-[#153570] text-sm font-medium">
                <i class="fa-solid fa-save mr-2"></i>Update
            </button>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div id="deleteCategoryModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-red-600">Delete Category</h3>
            <button onclick="closeDeleteCategoryModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        
        <div class="mb-4">
            <input type="hidden" id="deleteCategoryId">
            <input type="hidden" id="deleteCategoryName">
            <div class="flex items-start">
                <i class="fa-solid fa-exclamation-triangle text-red-500 mt-0.5 mr-3 text-lg"></i>
                <div>
                    <p class="text-sm text-gray-900 font-medium mb-2">Are you sure you want to delete this category?</p>
                    <p class="text-sm text-gray-700 mb-2" id="deleteCategoryText"></p>
                    <p class="text-xs text-red-600 font-medium">This action cannot be undone!</p>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end gap-3">
            <button onclick="closeDeleteCategoryModal()" 
                    class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 text-sm font-medium">
                Cancel
            </button>
            <button onclick="confirmDeleteCategory()" 
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm font-medium">
                <i class="fa-solid fa-trash mr-2"></i>Delete Category
            </button>
        </div>
    </div>
</div>

<script>
// Add Category Modal
function openAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.remove('hidden');
    document.getElementById('newCategoryName').focus();
}

function closeAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.add('hidden');
    document.getElementById('newCategoryName').value = '';
    document.getElementById('newCategoryEndOfLife').value = '';
}

async function addPCCategory() {
    const categoryName = document.getElementById('newCategoryName').value.trim().toUpperCase();
    const endOfLife = document.getElementById('newCategoryEndOfLife').value.trim();
    
    if (!categoryName) {
        showAlert('error', 'Please enter a category name');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'add_pc_category');
        formData.append('category_name', categoryName);
        if (endOfLife) {
            formData.append('end_of_life', endOfLife);
        }
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            closeAddCategoryModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while adding the category');
    }
}

// Toggle PC Category
async function togglePCCategory(categoryId, isEnabled, event) {
    try {
        const checkbox = event.target;
        const originalState = !isEnabled; // Store original state
        
        // Disable the checkbox during the request
        checkbox.disabled = true;
        
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'update_pc_category');
        formData.append('category_id', categoryId);
        formData.append('is_pc_category', isEnabled ? 1 : 0);
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', result.message);
            // Revert toggle on error
            checkbox.checked = originalState;
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while updating the category');
        // Revert toggle on error
        event.target.checked = !isEnabled;
    } finally {
        // Re-enable the checkbox
        event.target.disabled = false;
    }
}

// Rename Category
function renameCategoryModal(categoryId, currentName) {
    document.getElementById('renameCategoryId').value = categoryId;
    document.getElementById('renameCategoryName').value = currentName;
    document.getElementById('renameCategoryModal').classList.remove('hidden');
    document.getElementById('renameCategoryName').focus();
}

function closeRenameCategoryModal() {
    document.getElementById('renameCategoryModal').classList.add('hidden');
    document.getElementById('renameCategoryId').value = '';
    document.getElementById('renameCategoryName').value = '';
}

async function confirmRenameCategory() {
    const categoryId = document.getElementById('renameCategoryId').value;
    const newName = document.getElementById('renameCategoryName').value.trim().toUpperCase();
    
    if (!newName) {
        showAlert('error', 'Please enter a category name');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'rename_category');
        formData.append('category_id', categoryId);
        formData.append('new_name', newName);
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            closeRenameCategoryModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while renaming the category');
    }
}

// Edit End of Life
function editEndOfLife(categoryId, currentValue) {
    document.getElementById('editEndOfLifeCategoryId').value = categoryId;
    document.getElementById('editEndOfLifeValue').value = currentValue || '';
    document.getElementById('editEndOfLifeModal').classList.remove('hidden');
    document.getElementById('editEndOfLifeValue').focus();
}

function closeEditEndOfLifeModal() {
    document.getElementById('editEndOfLifeModal').classList.add('hidden');
    document.getElementById('editEndOfLifeCategoryId').value = '';
    document.getElementById('editEndOfLifeValue').value = '';
}

async function confirmUpdateEndOfLife() {
    const categoryId = document.getElementById('editEndOfLifeCategoryId').value;
    const endOfLife = document.getElementById('editEndOfLifeValue').value.trim();
    
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'update_end_of_life');
        formData.append('category_id', categoryId);
        if (endOfLife) {
            formData.append('end_of_life', endOfLife);
        }
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            closeEditEndOfLifeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while updating end of life');
    }
}

// Delete Category
function deleteCategory(categoryId, categoryName) {
    document.getElementById('deleteCategoryId').value = categoryId;
    document.getElementById('deleteCategoryName').value = categoryName;
    document.getElementById('deleteCategoryText').textContent = `Category: "${categoryName}"`;
    document.getElementById('deleteCategoryModal').classList.remove('hidden');
}

function closeDeleteCategoryModal() {
    document.getElementById('deleteCategoryModal').classList.add('hidden');
    document.getElementById('deleteCategoryId').value = '';
    document.getElementById('deleteCategoryName').value = '';
}

async function confirmDeleteCategory() {
    const categoryId = document.getElementById('deleteCategoryId').value;
    const categoryName = document.getElementById('deleteCategoryName').value;
    
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'delete_pc_category');
        formData.append('category_id', categoryId);
        
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            closeDeleteCategoryModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while deleting the category');
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
document.getElementById('addCategoryModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAddCategoryModal();
});

document.getElementById('renameCategoryModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeRenameCategoryModal();
});

document.getElementById('deleteCategoryModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeDeleteCategoryModal();
});

document.getElementById('editEndOfLifeModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeEditEndOfLifeModal();
});
</script>

<?php include '../components/layout_footer.php'; ?>
