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

// Get building_id from URL
$building_id = isset($_GET['building_id']) ? intval($_GET['building_id']) : 0;

if ($building_id <= 0) {
    header("Location: buildings.php");
    exit();
}

// Get building details
$building_query = $conn->prepare("SELECT * FROM buildings WHERE id = ?");
$building_query->bind_param('i', $building_id);
$building_query->execute();
$building_result = $building_query->get_result();
$building = $building_result->fetch_assoc();
$building_query->close();

if (!$building) {
    header("Location: buildings.php");
    exit();
}

// Handle AJAX requests for rooms
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_room') {
        $name = trim($_POST['name'] ?? '');
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Room name is required']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("INSERT INTO rooms (building_id, name) VALUES (?, ?)");
            $stmt->bind_param('is', $building_id, $name);
            $success = $stmt->execute();
            $new_id = $conn->insert_id;
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Room created successfully', 'id' => $new_id, 'name' => $name]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create room']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'update_room') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        
        if ($id <= 0 || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE rooms SET name = ? WHERE id = ? AND building_id = ?");
            $stmt->bind_param('sii', $name, $id, $building_id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Room updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update room']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'delete_room') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid room ID']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ? AND building_id = ?");
            $stmt->bind_param('ii', $id, $building_id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Room deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete room']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Fetch rooms for this building with search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Count total rooms
$count_query = "SELECT COUNT(*) as total FROM rooms WHERE building_id = ?";
$params = [$building_id];
$types = 'i';

if (!empty($search)) {
    $count_query .= " AND name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_rooms = $total_result->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_rooms / $limit);

// Fetch rooms
$rooms = [];
$query_sql = "SELECT * FROM rooms WHERE building_id = ?";
$params = [$building_id];
$types = 'i';

if (!empty($search)) {
    $query_sql .= " AND name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

$query_sql .= " ORDER BY name ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$query = $conn->prepare($query_sql);
$query->bind_param($types, ...$params);
$query->execute();
$result = $query->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
}
$query->close();

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
</style>

<main>
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white rounded shadow-sm border border-gray-200 mb-3">
            <div class="flex items-center gap-3">
                <a href="buildings.php" class="text-gray-600 hover:text-gray-800">
                    <i class="fa-solid fa-arrow-left text-xl"></i>
                </a>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($building['name']); ?> - Rooms</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Total: <?php echo $total_rooms; ?> room(s) · Click a row to view room assets</p>
                </div>
            </div>
            
            <button onclick="openAddRoomModal()" 
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fa-solid fa-plus"></i>
                <span>Add Room</span>
            </button>
        </div>

        <!-- Search Bar -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 px-4 py-3">
            <form method="GET" action="" class="flex gap-3">
                <input type="hidden" name="building_id" value="<?php echo $building_id; ?>">
                <div class="flex-1">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search rooms by name..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-search mr-2"></i>Search
                </button>
                <?php if (!empty($search)): ?>
                <a href="?building_id=<?php echo $building_id; ?>" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fa-solid fa-times mr-2"></i>Clear
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Content Area -->
        <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
            <!-- Rooms Table -->
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($rooms)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                <i class="fa-solid fa-door-open text-5xl mb-3 opacity-30"></i>
                                <p class="text-lg">No rooms found</p>
                                <?php if (!empty($search)): ?>
                                    <p class="text-sm">Try adjusting your search</p>
                                <?php else: ?>
                                    <p class="text-sm">Click "Add Room" to create one</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rooms as $index => $room): ?>
                            <tr class="hover:bg-gray-50 transition-colors cursor-pointer" data-id="<?php echo $room['id']; ?>" onclick="viewRoomAssets(<?php echo $room['id']; ?>, event)">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $offset + $index + 1; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <i class="fa-solid fa-door-open text-green-600 mr-3"></i>
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($room['name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y g:i A', strtotime($room['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm" onclick="event.stopPropagation()">
                                    <div class="relative">
                                        <button onclick="toggleMenu(<?php echo $room['id']; ?>)" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                            <i class="fa-solid fa-ellipsis-vertical text-xl"></i>
                                        </button>
                                        <div id="menu-<?php echo $room['id']; ?>" class="hidden fixed bg-white rounded-lg shadow-lg border border-gray-200 z-50" style="min-width: 12rem;">
                                            <div class="py-1">
                                                <button onclick="viewRoomAssets(<?php echo $room['id']; ?>, event)" 
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                    <i class="fa-solid fa-eye text-green-600"></i> Handle Assets
                                                </button>
                                                <button onclick="editRoom(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['name'], ENT_QUOTES); ?>')" 
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                    <i class="fa-solid fa-pencil text-blue-600"></i> Edit
                                                </button>
                                                <button onclick="deleteRoom(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['name'], ENT_QUOTES); ?>')" 
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="bg-white rounded shadow-sm border border-gray-200 mt-3 px-4 py-3">
            <div class="flex items-center justify-center gap-2">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?building_id=<?php echo $building_id; ?>&page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-1 rounded <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition-colors">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Add Room Modal -->
<div id="addRoomModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Add New Room</h3>
        </div>
        <form id="addRoomForm" class="p-6">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Room Name</label>
                <input type="text" id="roomName" name="name" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                       placeholder="e.g., IK501, Room 101">
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeAddRoomModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fa-solid fa-plus mr-2"></i>Create Room
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Room Modal -->
<div id="editRoomModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Edit Room</h3>
        </div>
        <form id="editRoomForm" class="p-6">
            <input type="hidden" id="editRoomId" name="id">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Room Name</label>
                <input type="text" id="editRoomName" name="name" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                       placeholder="e.g., IK501, Room 101">
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeEditRoomModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fa-solid fa-save mr-2"></i>Update Room
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteRoomModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Delete Room</h3>
        </div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-6">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                    <i class="fa-solid fa-trash text-red-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-800 font-medium mb-2">Are you sure you want to delete this room?</p>
                    <p class="text-sm text-gray-600 mb-1">Room: <span id="deleteRoomName" class="font-semibold text-gray-800"></span></p>
                    <p class="text-xs text-red-600 mt-2 font-medium">This action cannot be undone!</p>
                </div>
            </div>
            <div class="flex gap-3 justify-end">
                <button onclick="closeDeleteRoomModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button onclick="confirmDeleteRoom()" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fa-solid fa-trash mr-2"></i>Delete Room
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Modal functions
function openAddRoomModal() {
    document.getElementById('addRoomModal').classList.remove('hidden');
    document.getElementById('roomName').focus();
}

function closeAddRoomModal() {
    document.getElementById('addRoomModal').classList.add('hidden');
    document.getElementById('addRoomForm').reset();
}

function closeEditRoomModal() {
    document.getElementById('editRoomModal').classList.add('hidden');
    document.getElementById('editRoomForm').reset();
}

// Add Room Form Submit
document.getElementById('addRoomForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    formData.append('action', 'create_room');
    formData.append('name', document.getElementById('roomName').value);
    
    try {
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Reload page to show new room in table
            window.location.reload();
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while creating the room');
    }
});

// Edit Room
function editRoom(id, name) {
    document.getElementById('editRoomId').value = id;
    document.getElementById('editRoomName').value = name;
    document.getElementById('editRoomModal').classList.remove('hidden');
    document.getElementById('editRoomName').focus();
    closeAllMenus();
}

// Edit Room Form Submit
document.getElementById('editRoomForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    formData.append('action', 'update_room');
    formData.append('id', document.getElementById('editRoomId').value);
    formData.append('name', document.getElementById('editRoomName').value);
    
    try {
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Reload page to show updated data
            window.location.reload();
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while updating the room');
    }
});

// Delete Room
let roomToDelete = { id: null, name: null };

function deleteRoom(id, name) {
    closeAllMenus();
    roomToDelete = { id, name };
    document.getElementById('deleteRoomName').textContent = name;
    document.getElementById('deleteRoomModal').classList.remove('hidden');
}

function closeDeleteRoomModal() {
    document.getElementById('deleteRoomModal').classList.add('hidden');
    roomToDelete = { id: null, name: null };
}

async function confirmDeleteRoom() {
    const { id } = roomToDelete;
    
    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    formData.append('action', 'delete_room');
    formData.append('id', id);
    
    try {
        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            closeDeleteRoomModal();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while deleting the room');
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

// View Room Assets
function viewRoomAssets(roomId, event) {
    if (event) event.stopPropagation();
    closeAllMenus();
    window.location.href = 'roomassets.php?room_id=' + roomId;
}

// Toggle three-dot menu
function toggleMenu(id) {
    const button = event.target.closest('button');
    const menu = document.getElementById('menu-' + id);
    const allMenus = document.querySelectorAll('[id^="menu-"]');
    
    // Close all other menus
    allMenus.forEach(m => {
        if (m.id !== 'menu-' + id) {
            m.classList.add('hidden');
        }
    });
    
    // Toggle current menu
    if (!menu.classList.contains('hidden')) {
        menu.classList.add('hidden');
    } else {
        menu.classList.remove('hidden');
        
        // Position the menu near the button
        const buttonRect = button.getBoundingClientRect();
        menu.style.top = (buttonRect.bottom + window.scrollY) + 'px';
        menu.style.left = (buttonRect.left + window.scrollX - menu.offsetWidth + button.offsetWidth) + 'px';
    }
}

// Close all menus
function closeAllMenus() {
    const allMenus = document.querySelectorAll('[id^="menu-"]');
    allMenus.forEach(m => m.classList.add('hidden'));
}

// Close menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('tr') && !e.target.closest('[id^="menu-"]')) {
        closeAllMenus();
    }
});

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddRoomModal();
        closeEditRoomModal();
        closeDeleteRoomModal();
    }
});
</script>

<?php include '../components/layout_footer.php'; ?>
