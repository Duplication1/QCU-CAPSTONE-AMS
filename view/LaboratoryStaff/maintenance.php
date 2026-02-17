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

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create database connection
if (!isset($conn)) {
    $conn = new mysqli('localhost', 'root', '', 'ams_database');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    
    if ($action === 'assign_technician') {
        $building_id = intval($_POST['building_id'] ?? 0);
        $technician_ids = isset($_POST['technician_ids']) ? $_POST['technician_ids'] : [];
        
        if ($building_id <= 0 || empty($technician_ids)) {
            echo json_encode(['success' => false, 'message' => 'Invalid building or no technicians selected']);
            exit;
        }
        
        // Convert to array if single value
        if (!is_array($technician_ids)) {
            $technician_ids = [$technician_ids];
        }
        
        try {
            // Get building name
            $building_stmt = $conn->prepare("SELECT name FROM buildings WHERE id = ?");
            $building_stmt->bind_param('i', $building_id);
            $building_stmt->execute();
            $building_result = $building_stmt->get_result();
            $building_data = $building_result->fetch_assoc();
            $building_stmt->close();
            
            if (!$building_data) {
                echo json_encode(['success' => false, 'message' => 'Building not found']);
                exit;
            }
            
            $assigned_count = 0;
            $skipped_count = 0;
            $invalid_count = 0;
            $user_id = $_SESSION['user_id'];
            
            foreach ($technician_ids as $technician_id) {
                $technician_id = intval($technician_id);
                
                if ($technician_id <= 0) {
                    $invalid_count++;
                    continue;
                }
                
                // Check if technician exists and has correct role
                $tech_check = $conn->prepare("SELECT full_name FROM users WHERE id = ? AND role = 'Technician'");
                $tech_check->bind_param('i', $technician_id);
                $tech_check->execute();
                $tech_result = $tech_check->get_result();
                
                if ($tech_result->num_rows === 0) {
                    $tech_check->close();
                    $invalid_count++;
                    continue;
                }
                
                $tech_data = $tech_result->fetch_assoc();
                $tech_check->close();
                
                // Try to insert assignment
                $stmt = $conn->prepare("INSERT INTO building_technicians (building_id, technician_id, assigned_by) VALUES (?, ?, ?)");
                $stmt->bind_param('iii', $building_id, $technician_id, $user_id);
                
                if ($stmt->execute()) {
                    // Create notification for technician
                    $notif_title = "Building Assignment";
                    $notif_message = "You have been assigned to maintain " . $building_data['name'];
                    
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_type, related_id) VALUES (?, ?, ?, 'info', 'building', ?)");
                    $notif_stmt->bind_param('issi', $technician_id, $notif_title, $notif_message, $building_id);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                    
                    $assigned_count++;
                } else {
                    if ($conn->errno === 1062) {
                        $skipped_count++; // Already assigned
                    }
                }
                $stmt->close();
            }
            
            // Build response message
            $message_parts = [];
            if ($assigned_count > 0) {
                $message_parts[] = "$assigned_count technician(s) assigned successfully";
            }
            if ($skipped_count > 0) {
                $message_parts[] = "$skipped_count already assigned";
            }
            if ($invalid_count > 0) {
                $message_parts[] = "$invalid_count invalid";
            }
            
            $message = implode(', ', $message_parts);
            
            echo json_encode([
                'success' => $assigned_count > 0,
                'message' => $message ?: 'No technicians were assigned',
                'assigned' => $assigned_count,
                'skipped' => $skipped_count
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'remove_technician') {
        $building_id = intval($_POST['building_id'] ?? 0);
        $technician_id = intval($_POST['technician_id'] ?? 0);
        
        if ($building_id <= 0 || $technician_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid building or technician ID']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("DELETE FROM building_technicians WHERE building_id = ? AND technician_id = ?");
            $stmt->bind_param('ii', $building_id, $technician_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Technician removed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove technician']);
            }
            $stmt->close();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Fetch all technicians
$technicians = [];
$tech_query = "SELECT id, full_name FROM users WHERE role = 'Technician' ORDER BY full_name";
$tech_result = $conn->query($tech_query);
if ($tech_result && $tech_result->num_rows > 0) {
    while ($tech = $tech_result->fetch_assoc()) {
        $technicians[] = $tech;
    }
}

// Fetch all buildings with maintenance stats and assigned technicians
$buildings = [];
$query = "SELECT 
    b.id,
    b.name,
    b.created_at,
    COUNT(DISTINCT r.id) as total_rooms,
    COUNT(DISTINCT ms.id) as total_schedules,
    COUNT(DISTINCT CASE WHEN ms.status = 'Scheduled' AND ms.maintenance_date < CURDATE() THEN ms.id END) as overdue_maintenance,
    COUNT(DISTINCT CASE WHEN ms.status = 'Scheduled' AND ms.maintenance_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN ms.id END) as upcoming_maintenance,
    MIN(CASE WHEN ms.status = 'Scheduled' AND ms.maintenance_date >= CURDATE() THEN ms.maintenance_date END) as earliest_maintenance,
    COUNT(DISTINCT bt.technician_id) as technician_count
FROM buildings b
LEFT JOIN rooms r ON b.id = r.building_id
LEFT JOIN maintenance_schedules ms ON r.id = ms.room_id AND b.id = ms.building_id
LEFT JOIN building_technicians bt ON b.id = bt.building_id
GROUP BY b.id, b.name, b.created_at
ORDER BY b.name ASC";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Get assigned technicians for this building
        $tech_query = $conn->prepare("SELECT u.id, u.full_name, bt.assigned_at 
                                       FROM building_technicians bt 
                                       JOIN users u ON bt.technician_id = u.id 
                                       WHERE bt.building_id = ? 
                                       ORDER BY u.full_name");
        $tech_query->bind_param('i', $row['id']);
        $tech_query->execute();
        $tech_result = $tech_query->get_result();
        $row['technicians'] = [];
        while ($tech = $tech_result->fetch_assoc()) {
            $row['technicians'][] = $tech;
        }
        $tech_query->close();
        
        $buildings[] = $row;
    }
}

include '../components/layout_header.php';
?>

<style>
html, body {
    height: 100vh;
    overflow-y: auto;
}
#app-container {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
main {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 0.5rem;
    background-color: #f9fafb;
}

/* Technician chip styles */
.technician-chip {
    background-color: white;
    border-color: #d1d5db;
    color: #374151;
    cursor: pointer;
}
.technician-chip:hover {
    border-color: #3b82f6;
    background-color: #eff6ff;
}
.technician-chip.selected {
    background-color: #3b82f6;
    border-color: #2563eb;
    color: white;
}
.technician-chip.selected:hover {
    background-color: #2563eb;
    border-color: #1d4ed8;
}
.technician-chip.already-assigned {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: #e5e7eb;
    border-color: #9ca3af;
    color: #6b7280;
    text-decoration: line-through;
    pointer-events: none;
}
.technician-chip.already-assigned:hover {
    border-color: #9ca3af;
    background-color: #e5e7eb;
}
</style>

<main>
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white rounded shadow-sm border border-gray-200 mb-3">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Room Maintenance Management</h1>
                <p class="text-sm text-gray-500">Schedule and assign maintenance tasks for all rooms</p>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
            <?php
            $total_buildings = count($buildings);
            $total_rooms = array_sum(array_column($buildings, 'total_rooms'));
            $total_overdue = array_sum(array_column($buildings, 'overdue_maintenance'));
            $total_upcoming = array_sum(array_column($buildings, 'upcoming_maintenance'));
            ?>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Buildings</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $total_buildings ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i class="fa-solid fa-building text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Rooms</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $total_rooms ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-lg">
                        <i class="fa-solid fa-door-open text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Overdue Maintenance</p>
                        <p class="text-2xl font-bold text-red-600"><?= $total_overdue ?></p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-lg">
                        <i class="fa-solid fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Upcoming (7 days)</p>
                        <p class="text-2xl font-bold text-orange-600"><?= $total_upcoming ?></p>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-lg">
                        <i class="fa-solid fa-calendar-days text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buildings Grid -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Buildings</h2>
            
            <?php if (empty($buildings)): ?>
                <div class="text-center py-12">
                    <i class="fa-solid fa-building text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg">No buildings found</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($buildings as $building): ?>
                        <div class="bg-gray-50 rounded-lg border-2 border-gray-200 p-4 hover:border-blue-500 hover:shadow-md transition-all">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="bg-blue-100 p-3 rounded-lg">
                                        <i class="fa-solid fa-building text-blue-600 text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($building['name']) ?></h3>
                                        <p class="text-sm text-gray-500"><?= $building['total_rooms'] ?> room(s)</p>
                                    </div>
                                </div>
                                <?php if ($building['overdue_maintenance'] > 0): ?>
                                    <span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full">
                                        <?= $building['overdue_maintenance'] ?> Overdue
                                    </span>
                                <?php elseif ($building['upcoming_maintenance'] > 0): ?>
                                    <span class="px-2 py-1 bg-orange-100 text-orange-700 text-xs font-semibold rounded-full">
                                        <?= $building['upcoming_maintenance'] ?> Upcoming
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Assigned Technicians -->
                            <div class="mb-3 pb-3 border-b border-gray-200">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-semibold text-gray-600 uppercase">
                                        <i class="fa-solid fa-users mr-1"></i>Assigned Technicians
                                    </span>
                                    <button onclick="manageTechnicians(event, <?= $building['id'] ?>, '<?= htmlspecialchars($building['name'], ENT_QUOTES) ?>')" 
                                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                        <i class="fa-solid fa-user-plus"></i> Manage
                                    </button>
                                </div>
                                <?php if (empty($building['technicians'])): ?>
                                    <p class="text-xs text-gray-400 italic">No technicians assigned</p>
                                <?php else: ?>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach ($building['technicians'] as $tech): ?>
                                            <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">
                                                <i class="fa-solid fa-user mr-1"></i><?= htmlspecialchars($tech['full_name']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="space-y-2 mb-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">
                                        <i class="fa-solid fa-calendar mr-2 text-gray-400"></i>Earliest Maintenance:
                                    </span>
                                    <?php if ($building['earliest_maintenance']): ?>
                                        <?php 
                                        $days_until = floor((strtotime($building['earliest_maintenance']) - time()) / 86400);
                                        $color = $days_until < 0 ? 'text-red-600' : ($days_until <= 7 ? 'text-orange-600' : 'text-green-600');
                                        ?>
                                        <span class="font-semibold <?= $color ?>">
                                            <?= date('M d, Y', strtotime($building['earliest_maintenance'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">Not scheduled</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mt-3 pt-3 border-t border-gray-200">
                                <a href="maintenance_rooms.php?building_id=<?= $building['id'] ?>" 
                                   class="flex items-center justify-between text-sm text-blue-600 font-medium hover:text-blue-800">
                                    <span>Manage Room Schedules</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Manage Technicians Modal -->
<div id="techniciansModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-white" id="techniciansModalTitle">Manage Technicians</h3>
            <button onclick="closeTechniciansModal()" class="text-white hover:text-gray-200">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <input type="hidden" id="modalBuildingId">
            
            <!-- Assign New Technician -->
            <div class="mb-6 pb-6 border-b border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-700">
                        <i class="fa-solid fa-user-plus mr-2"></i>Assign Technicians
                    </h4>
                    <button onclick="assignTechnician()" 
                            id="assignButton"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                        <i class="fa-solid fa-plus mr-1"></i>Assign Selected (<span id="selectedCount">0</span>)
                    </button>
                </div>
                <p class="text-xs text-gray-500 mb-3">
                    <i class="fa-solid fa-info-circle mr-1"></i>Click to select/deselect technicians
                </p>
                <div id="technicianChips" class="flex flex-wrap gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200 min-h-[80px]">
                    <?php foreach ($technicians as $tech): ?>
                        <button type="button"
                                class="technician-chip px-3 py-2 rounded-full text-sm font-medium border-2 transition-all"
                                data-technician-id="<?= $tech['id'] ?>"
                                data-technician-name="<?= htmlspecialchars($tech['full_name'], ENT_QUOTES) ?>"
                                onclick="toggleTechnicianSelection(this)">
                            <i class="fa-solid fa-user mr-1"></i><?= htmlspecialchars($tech['full_name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Assigned Technicians List -->
            <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fa-solid fa-users mr-2"></i>Assigned Technicians
                </h4>
                <div id="assignedTechniciansList" class="space-y-2 max-h-64 overflow-y-auto">
                    <!-- Content loaded dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">
                <i class="fa-solid fa-exclamation-triangle mr-2"></i>Confirm Action
            </h3>
        </div>
        <div class="p-6">
            <p id="confirmMessage" class="text-gray-700 text-base mb-6"></p>
            <div class="flex gap-3 justify-end">
                <button onclick="closeConfirmModal(false)" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fa-solid fa-times mr-1"></i>Cancel
                </button>
                <button onclick="closeConfirmModal(true)" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fa-solid fa-trash mr-1"></i>Remove
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const buildingTechnicians = <?= json_encode(array_column($buildings, 'technicians', 'id')) ?>;

// Confirmation modal promise resolver
let confirmResolver = null;

function showConfirmModal(message) {
    return new Promise((resolve) => {
        confirmResolver = resolve;
        document.getElementById('confirmMessage').textContent = message;
        document.getElementById('confirmModal').classList.remove('hidden');
    });
}

function closeConfirmModal(confirmed) {
    document.getElementById('confirmModal').classList.add('hidden');
    if (confirmResolver) {
        confirmResolver(confirmed);
        confirmResolver = null;
    }
}

function manageTechnicians(event, buildingId, buildingName) {
    event.preventDefault();
    event.stopPropagation();
    
    document.getElementById('modalBuildingId').value = buildingId;
    document.getElementById('techniciansModalTitle').textContent = buildingName + ' - Manage Technicians';
    
    // Reset all chip selections
    resetChipSelections();
    
    // Mark already assigned technicians
    markAssignedTechnicians(buildingId);
    
    updateAssignedTechniciansList(buildingId);
    document.getElementById('techniciansModal').classList.remove('hidden');
}

function resetChipSelections() {
    const chips = document.querySelectorAll('.technician-chip');
    chips.forEach(chip => {
        chip.classList.remove('selected', 'already-assigned');
        chip.removeAttribute('disabled');
    });
    updateSelectedCount();
}

function markAssignedTechnicians(buildingId) {
    // Get all assigned technicians across ALL buildings
    const allAssignedIds = new Set();
    
    Object.keys(buildingTechnicians).forEach(bId => {
        const technicians = buildingTechnicians[bId] || [];
        technicians.forEach(tech => {
            allAssignedIds.add(tech.id.toString());
        });
    });
    
    const chips = document.querySelectorAll('.technician-chip');
    chips.forEach(chip => {
        const techId = chip.getAttribute('data-technician-id');
        if (allAssignedIds.has(techId)) {
            chip.classList.add('already-assigned');
            chip.setAttribute('disabled', 'true');
        }
    });
}

function toggleTechnicianSelection(element) {
    // Don't allow selecting already assigned technicians
    if (element.classList.contains('already-assigned')) {
        return;
    }
    
    element.classList.toggle('selected');
    updateSelectedCount();
}

function updateSelectedCount() {
    const selectedChips = document.querySelectorAll('.technician-chip.selected');
    const count = selectedChips.length;
    const countElement = document.getElementById('selectedCount');
    const assignButton = document.getElementById('assignButton');
    
    countElement.textContent = count;
    
    if (count > 0) {
        assignButton.disabled = false;
    } else {
        assignButton.disabled = true;
    }
}

function closeTechniciansModal() {
    document.getElementById('techniciansModal').classList.add('hidden');
    resetChipSelections();
}

function updateAssignedTechniciansList(buildingId) {
    const technicians = buildingTechnicians[buildingId] || [];
    const listContainer = document.getElementById('assignedTechniciansList');
    
    if (technicians.length === 0) {
        listContainer.innerHTML = '<p class="text-gray-500 text-center py-4 italic">No technicians assigned to this building</p>';
    } else {
        let html = '';
        technicians.forEach(tech => {
            const assignedDate = new Date(tech.assigned_at).toLocaleDateString('en-US', {
                year: 'numeric', 
                month: 'short', 
                day: 'numeric'
            });
            
            html += `
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 p-2 rounded-lg">
                            <i class="fa-solid fa-user text-blue-600"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">${tech.full_name}</div>
                            <div class="text-xs text-gray-500">Assigned on ${assignedDate}</div>
                        </div>
                    </div>
                    <button onclick="removeTechnician(${tech.id})" 
                            class="px-3 py-1 text-xs text-red-600 hover:bg-red-50 rounded transition-colors">
                        <i class="fa-solid fa-times mr-1"></i>Remove
                    </button>
                </div>
            `;
        });
        listContainer.innerHTML = html;
    }
}

async function assignTechnician() {
    const buildingId = document.getElementById('modalBuildingId').value;
    const selectedChips = document.querySelectorAll('.technician-chip.selected');
    const technicianIds = Array.from(selectedChips).map(chip => chip.getAttribute('data-technician-id'));
    
    if (technicianIds.length === 0) {
        showNotification('Please select at least one technician', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'assign_technician');
    formData.append('building_id', buildingId);
    
    // Append each technician ID as an array element
    technicianIds.forEach(id => {
        formData.append('technician_ids[]', id);
    });
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification('Error assigning technicians', 'error');
        console.error(error);
    }
}

async function removeTechnician(technicianId) {
    const confirmed = await showConfirmModal('Are you sure you want to remove this technician from the building?');
    
    if (!confirmed) {
        return;
    }
    
    const buildingId = document.getElementById('modalBuildingId').value;
    
    const formData = new FormData();
    formData.append('action', 'remove_technician');
    formData.append('building_id', buildingId);
    formData.append('technician_id', technicianId);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification('Error removing technician', 'error');
        console.error(error);
    }
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTechniciansModal();
        closeConfirmModal(false);
    }
});
</script>

<?php include '../components/layout_footer.php'; ?>
