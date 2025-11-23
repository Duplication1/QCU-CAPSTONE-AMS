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

// Handle AJAX requests for buildings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_building') {
        $name = trim($_POST['name'] ?? '');
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Building name is required']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("INSERT INTO buildings (name) VALUES (?)");
            $stmt->bind_param('s', $name);
            $success = $stmt->execute();
            $new_id = $conn->insert_id;
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Building created successfully', 'id' => $new_id, 'name' => $name]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create building']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'update_building') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        
        if ($id <= 0 || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE buildings SET name = ? WHERE id = ?");
            $stmt->bind_param('si', $name, $id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Building updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update building']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'delete_building') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid building ID']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("DELETE FROM buildings WHERE id = ?");
            $stmt->bind_param('i', $id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Building deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete building']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Fetch buildings data
$buildings = [];
$query = "SELECT * FROM buildings ORDER BY name ASC";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $buildings[] = $row;
    }
}

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
    overflow: hidden;
    display: flex;
    flex-direction: column;
    padding: 0.5rem;
    background-color: #f9fafb;
}
.building-card {
    transition: all 0.3s ease;
}
.building-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
</style>

<main>
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white rounded shadow-sm border border-gray-200 mb-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Building Management</h3>
                <p class="text-xs text-gray-500 mt-0.5">Manage building information</p>
            </div>
            
            <button onclick="openAddBuildingModal()" 
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fa-solid fa-plus"></i>
                <span>Add Building</span>
            </button>
        </div>

        <!-- Content Area -->
        <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200 p-4">
            <!-- Buildings Grid -->
            <div id="buildingsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php if (empty($buildings)): ?>
                    <div class="col-span-full text-center py-12 text-gray-500">
                        <i class="fa-solid fa-building text-6xl mb-4 opacity-30"></i>
                        <p class="text-lg">No buildings yet</p>
                        <p class="text-sm">Click "Add Building" to create one</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($buildings as $building): ?>
                        <div class="building-card bg-white border-2 border-gray-200 rounded-xl p-6 text-center relative" data-id="<?php echo $building['id']; ?>">
                            <!-- Three-dot menu -->
                            <div class="absolute top-3 right-3">
                                <button onclick="toggleMenu(<?php echo $building['id']; ?>)" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <i class="fa-solid fa-ellipsis-vertical text-xl"></i>
                                </button>
                                <div id="menu-<?php echo $building['id']; ?>" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                                    <div class="py-1">
                                        <button onclick="editBuilding(<?php echo $building['id']; ?>, '<?php echo htmlspecialchars($building['name'], ENT_QUOTES); ?>')" 
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                            <i class="fa-solid fa-pencil text-blue-600"></i> Edit
                                        </button>
                                        <button onclick="deleteBuilding(<?php echo $building['id']; ?>, '<?php echo htmlspecialchars($building['name'], ENT_QUOTES); ?>')" 
                                                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <a href="rooms.php?building_id=<?php echo $building['id']; ?>" class="block cursor-pointer">
                                <div class="mb-4">
                                    <i class="fa-solid fa-building text-6xl text-blue-600 hover:text-blue-700 transition-colors"></i>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-800 mb-2 hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($building['name']); ?></h4>
                            </a>
                            <p class="text-xs text-gray-500 mb-4">Created: <?php echo date('M d, Y', strtotime($building['created_at'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Add Building Modal -->
<div id="addBuildingModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Add New Building</h3>
        </div>
        <form id="addBuildingForm" class="p-6">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Building Name</label>
                <input type="text" id="buildingName" name="name" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Enter building name">
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeAddBuildingModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-plus mr-2"></i>Create Building
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Building Modal -->
<div id="editBuildingModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Edit Building</h3>
        </div>
        <form id="editBuildingForm" class="p-6">
            <input type="hidden" id="editBuildingId" name="id">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Building Name</label>
                <input type="text" id="editBuildingName" name="name" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Enter building name">
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeEditBuildingModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-save mr-2"></i>Update Building
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function openAddBuildingModal() {
    document.getElementById('addBuildingModal').classList.remove('hidden');
    document.getElementById('buildingName').focus();
}

function closeAddBuildingModal() {
    document.getElementById('addBuildingModal').classList.add('hidden');
    document.getElementById('addBuildingForm').reset();
}

function closeEditBuildingModal() {
    document.getElementById('editBuildingModal').classList.add('hidden');
    document.getElementById('editBuildingForm').reset();
}

// Add Building Form Submit
document.getElementById('addBuildingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    formData.append('action', 'create_building');
    formData.append('name', document.getElementById('buildingName').value);
    
    try {
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Add new building card to grid
            const grid = document.getElementById('buildingsGrid');
            const emptyState = grid.querySelector('.col-span-full');
            if (emptyState) emptyState.remove();
            
            const newCard = document.createElement('div');
            newCard.className = 'building-card bg-white border-2 border-gray-200 rounded-xl p-6 text-center relative';
            newCard.dataset.id = result.id;
            newCard.innerHTML = `
                <div class="absolute top-3 right-3">
                    <button onclick="toggleMenu(${result.id})" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                        <i class="fa-solid fa-ellipsis-vertical text-xl"></i>
                    </button>
                    <div id="menu-${result.id}" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                        <div class="py-1">
                            <button onclick="editBuilding(${result.id}, '${escapeHtml(result.name)}')" 
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <i class="fa-solid fa-pencil text-blue-600"></i> Edit
                            </button>
                            <button onclick="deleteBuilding(${result.id}, '${escapeHtml(result.name)}')" 
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                <i class="fa-solid fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
                
                <a href="rooms.php?building_id=${result.id}" class="block cursor-pointer">
                    <div class="mb-4">
                        <i class="fa-solid fa-building text-6xl text-blue-600 hover:text-blue-700 transition-colors"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-800 mb-2 hover:text-blue-600 transition-colors">${escapeHtml(result.name)}</h4>
                </a>
                <p class="text-xs text-gray-500 mb-4">Created: ${new Date().toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</p>
            `;
            
            grid.appendChild(newCard);
            
            // Animate the new card
            setTimeout(() => {
                newCard.style.animation = 'slideIn 0.3s ease-out';
            }, 10);
            
            showAlert('success', result.message);
            closeAddBuildingModal();
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while creating the building');
    }
});

// Edit Building
function editBuilding(id, name) {
    document.getElementById('editBuildingId').value = id;
    document.getElementById('editBuildingName').value = name;
    document.getElementById('editBuildingModal').classList.remove('hidden');
    document.getElementById('editBuildingName').focus();
    // Close the menu
    closeAllMenus();
}

// Edit Building Form Submit
document.getElementById('editBuildingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    formData.append('action', 'update_building');
    formData.append('id', document.getElementById('editBuildingId').value);
    formData.append('name', document.getElementById('editBuildingName').value);
    
    try {
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update the card
            const card = document.querySelector(`[data-id="${document.getElementById('editBuildingId').value}"]`);
            if (card) {
                const nameElement = card.querySelector('h4');
                nameElement.textContent = document.getElementById('editBuildingName').value;
                
                // Update onclick attributes
                const editBtn = card.querySelector('button[onclick^="editBuilding"]');
                const deleteBtn = card.querySelector('button[onclick^="deleteBuilding"]');
                const newName = escapeHtml(document.getElementById('editBuildingName').value);
                editBtn.setAttribute('onclick', `editBuilding(${document.getElementById('editBuildingId').value}, '${newName}')`);
                deleteBtn.setAttribute('onclick', `deleteBuilding(${document.getElementById('editBuildingId').value}, '${newName}')`);
            }
            
            showAlert('success', result.message);
            closeEditBuildingModal();
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while updating the building');
    }
});

// Delete Building
async function deleteBuilding(id, name) {
    // Close the menu first
    closeAllMenus();
    
    if (!confirm(`Are you sure you want to delete "${name}"?`)) return;
    
    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    formData.append('action', 'delete_building');
    formData.append('id', id);
    
    try {
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Remove the card
            const card = document.querySelector(`[data-id="${id}"]`);
            if (card) {
                card.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    card.remove();
                    
                    // Show empty state if no buildings left
                    const grid = document.getElementById('buildingsGrid');
                    if (grid.children.length === 0) {
                        grid.innerHTML = `
                            <div class="col-span-full text-center py-12 text-gray-500">
                                <i class="fa-solid fa-building text-6xl mb-4 opacity-30"></i>
                                <p class="text-lg">No buildings yet</p>
                                <p class="text-sm">Click "Add Building" to create one</p>
                            </div>
                        `;
                    }
                }, 300);
            }
            
            showAlert('success', result.message);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while deleting the building');
    }
}

// Alert function
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    } text-white font-medium animate-slideInRight`;
    alertDiv.textContent = message;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => alertDiv.remove(), 300);
    }, 3000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Toggle three-dot menu
function toggleMenu(id) {
    const menu = document.getElementById('menu-' + id);
    const allMenus = document.querySelectorAll('[id^="menu-"]');
    
    // Close all other menus
    allMenus.forEach(m => {
        if (m.id !== 'menu-' + id) {
            m.classList.add('hidden');
        }
    });
    
    // Toggle current menu
    menu.classList.toggle('hidden');
}

// Close all menus
function closeAllMenus() {
    const allMenus = document.querySelectorAll('[id^="menu-"]');
    allMenus.forEach(m => m.classList.add('hidden'));
}

// Close menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.building-card')) {
        closeAllMenus();
    }
});

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddBuildingModal();
        closeEditBuildingModal();
    }
});

// Animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideOut {
        from { opacity: 1; transform: scale(1); }
        to { opacity: 0; transform: scale(0.9); }
    }
    @keyframes slideInRight {
        from { opacity: 0; transform: translateX(100px); }
        to { opacity: 1; transform: translateX(0); }
    }
    @keyframes slideOutRight {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(100px); }
    }
`;
document.head.appendChild(style);
</script>

<?php include '../components/layout_footer.php'; ?>
