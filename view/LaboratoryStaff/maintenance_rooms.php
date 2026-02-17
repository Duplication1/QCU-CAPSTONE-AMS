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
    header("Location: maintenance.php");
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
    header("Location: maintenance.php");
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    
    if ($action === 'schedule_maintenance') {
        $room_id = intval($_POST['room_id'] ?? 0);
        $maintenance_date = trim($_POST['maintenance_date'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if ($room_id <= 0 || empty($maintenance_date)) {
            echo json_encode(['success' => false, 'message' => 'Invalid room ID or date']);
            exit;
        }
        
        try {
            // Insert maintenance schedule
            $stmt = $conn->prepare("INSERT INTO maintenance_schedules (room_id, building_id, maintenance_date, notes, created_by, status) VALUES (?, ?, ?, ?, ?, 'Scheduled')");
            $user_id = $_SESSION['user_id'];
            $stmt->bind_param('iissi', $room_id, $building_id, $maintenance_date, $notes, $user_id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Maintenance schedule created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create maintenance schedule']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'delete_schedule') {
        $schedule_id = intval($_POST['schedule_id'] ?? 0);
        
        if ($schedule_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid schedule ID']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("DELETE FROM maintenance_schedules WHERE id = ? AND building_id = ?");
            $stmt->bind_param('ii', $schedule_id, $building_id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Maintenance schedule deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete maintenance schedule']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Fetch rooms with maintenance info
$rooms = [];
$query = "SELECT 
    r.id,
    r.name,
    r.created_at,
    COUNT(DISTINCT a.id) as total_assets,
    COUNT(DISTINCT CASE WHEN a.status IN ('Available', 'In Use') THEN a.id END) as active_assets,
    COUNT(DISTINCT ms.id) as total_schedules,
    COUNT(DISTINCT CASE WHEN ms.status = 'Scheduled' AND ms.maintenance_date < CURDATE() THEN ms.id END) as overdue_count,
    COUNT(DISTINCT CASE WHEN ms.status = 'Scheduled' AND ms.maintenance_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN ms.id END) as upcoming_count,
    MIN(CASE WHEN ms.status = 'Scheduled' AND ms.maintenance_date >= CURDATE() THEN ms.maintenance_date END) as next_maintenance
FROM rooms r
LEFT JOIN assets a ON r.id = a.room_id
LEFT JOIN maintenance_schedules ms ON r.id = ms.room_id
WHERE r.building_id = ?
GROUP BY r.id, r.name, r.created_at
ORDER BY 
    CASE 
        WHEN MIN(CASE WHEN ms.status = 'Scheduled' AND ms.maintenance_date < CURDATE() THEN ms.maintenance_date END) IS NOT NULL THEN 0
        WHEN MIN(CASE WHEN ms.status = 'Scheduled' AND ms.maintenance_date >= CURDATE() THEN ms.maintenance_date END) IS NOT NULL THEN 1
        ELSE 2
    END,
    MIN(CASE WHEN ms.status = 'Scheduled' THEN ms.maintenance_date END) ASC,
    r.name ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $building_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Get maintenance schedules for this room
        $schedule_query = $conn->prepare("SELECT * FROM maintenance_schedules WHERE room_id = ? ORDER BY maintenance_date DESC LIMIT 5");
        $schedule_query->bind_param('i', $row['id']);
        $schedule_query->execute();
        $schedule_result = $schedule_query->get_result();
        $row['schedules'] = [];
        while ($schedule = $schedule_result->fetch_assoc()) {
            $row['schedules'][] = $schedule;
        }
        $schedule_query->close();
        
        $rooms[] = $row;
    }
}
$stmt->close();

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
</style>

<main>
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white rounded shadow-sm border border-gray-200 mb-3">
            <div class="flex items-center gap-3">
                <a href="maintenance.php" class="text-gray-600 hover:text-gray-800">
                    <i class="fa-solid fa-arrow-left text-lg"></i>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($building['name']) ?> - Room Maintenance</h1>
                    <p class="text-sm text-gray-500">Schedule and assign maintenance tasks for rooms</p>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
            <?php
            $total_rooms = count($rooms);
            $overdue = array_sum(array_column($rooms, 'overdue_count'));
            $upcoming = array_sum(array_column($rooms, 'upcoming_count'));
            $not_scheduled = count(array_filter($rooms, fn($r) => !$r['next_maintenance']));
            ?>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Rooms</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $total_rooms ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i class="fa-solid fa-door-open text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Overdue</p>
                        <p class="text-2xl font-bold text-red-600"><?= $overdue ?></p>
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
                        <p class="text-2xl font-bold text-orange-600"><?= $upcoming ?></p>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-lg">
                        <i class="fa-solid fa-calendar-days text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Not Scheduled</p>
                        <p class="text-2xl font-bold text-gray-600"><?= $not_scheduled ?></p>
                    </div>
                    <div class="bg-gray-100 p-3 rounded-lg">
                        <i class="fa-solid fa-calendar-xmark text-gray-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rooms Table -->
        <div class="bg-white rounded shadow-sm border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Assets</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Next Maintenance</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($rooms)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <i class="fa-solid fa-door-open text-6xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg">No rooms found in this building</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rooms as $room): ?>
                                <?php
                                $status_class = 'bg-gray-100 text-gray-700';
                                $status_text = 'Not Scheduled';
                                $status_icon = 'fa-calendar-xmark';
                                
                                if ($room['next_maintenance']) {
                                    $days_until = floor((strtotime($room['next_maintenance']) - time()) / 86400);
                                    if ($days_until < 0 || $room['overdue_count'] > 0) {
                                        $status_class = 'bg-red-100 text-red-700';
                                        $status_text = abs($days_until) . ' days overdue';
                                        $status_icon = 'fa-exclamation-triangle';
                                    } elseif ($days_until <= 7) {
                                        $status_class = 'bg-orange-100 text-orange-700';
                                        $status_text = 'Due in ' . $days_until . ' day' . ($days_until != 1 ? 's' : '');
                                        $status_icon = 'fa-calendar-days';
                                    } else {
                                        $status_class = 'bg-green-100 text-green-700';
                                        $status_text = 'Scheduled';
                                        $status_icon = 'fa-calendar-check';
                                    }
                                }
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <i class="fa-solid fa-door-open text-blue-600 mr-3"></i>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($room['name']) ?></div>
                                                    <div class="text-xs text-gray-500"><?= $room['total_schedules'] ?> schedule(s)</div>
                                                </div>
                                            </div>
                                            <?php if (!empty($room['schedules'])): ?>
                                                <button onclick="viewSchedules(<?= $room['id'] ?>, '<?= htmlspecialchars($room['name'], ENT_QUOTES) ?>')" 
                                                        class="text-blue-600 hover:text-blue-800 text-xs">
                                                    <i class="fa-solid fa-list mr-1"></i>View All
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-sm">
                                            <div class="font-bold text-gray-800"><?= $room['total_assets'] ?></div>
                                            <div class="text-xs text-gray-500"><?= $room['active_assets'] ?> active</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if ($room['next_maintenance']): ?>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= date('M d, Y', strtotime($room['next_maintenance'])) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php
                                                $days_until = floor((strtotime($room['next_maintenance']) - time()) / 86400);
                                                if ($days_until < 0) {
                                                    echo abs($days_until) . ' days overdue';
                                                } else {
                                                    echo 'In ' . $days_until . ' day' . ($days_until != 1 ? 's' : '');
                                                }
                                                ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-400">Not scheduled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?= $status_class ?>">
                                            <i class="fa-solid <?= $status_icon ?> mr-1"></i><?= $status_text ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button onclick="openMaintenanceModal(<?= $room['id'] ?>, '<?= htmlspecialchars($room['name'], ENT_QUOTES) ?>')" 
                                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                            <i class="fa-solid fa-calendar-plus mr-1"></i>Schedule
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Schedule Maintenance Modal -->
<div id="maintenanceModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Schedule Maintenance</h3>
        </div>
        <form id="maintenanceForm" class="p-6">
            <input type="hidden" id="maintenanceRoomId" name="room_id">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Room</label>
                <input type="text" id="maintenanceRoomName" readonly
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Maintenance Date *</label>
                <input type="date" id="maintenanceDate" name="maintenance_date" required
                       min="<?= date('Y-m-d') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Technicians are assigned at the building level</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <textarea id="maintenanceNotes" name="notes" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Add any special instructions or notes..."></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeMaintenanceModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-save mr-2"></i>Create Schedule
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Schedules Modal -->
<div id="schedulesModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-white" id="schedulesModalTitle">Maintenance Schedules</h3>
            <button onclick="closeSchedulesModal()" class="text-white hover:text-gray-200">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>
        <div id="schedulesContent" class="p-6 max-h-96 overflow-y-auto">
            <!-- Content loaded dynamically -->
        </div>
    </div>
</div>

<script>
const roomSchedules = <?= json_encode(array_column($rooms, 'schedules', 'id')) ?>;

function openMaintenanceModal(roomId, roomName) {
    document.getElementById('maintenanceRoomId').value = roomId;
    document.getElementById('maintenanceRoomName').value = roomName;
    document.getElementById('maintenanceForm').reset();
    document.getElementById('maintenanceRoomId').value = roomId;
    document.getElementById('maintenanceRoomName').value = roomName;
    document.getElementById('maintenanceModal').classList.remove('hidden');
}

function closeMaintenanceModal() {
    document.getElementById('maintenanceModal').classList.add('hidden');
    document.getElementById('maintenanceForm').reset();
}

function viewSchedules(roomId, roomName) {
    document.getElementById('schedulesModalTitle').textContent = roomName + ' - Maintenance Schedules';
    const schedules = roomSchedules[roomId] || [];
    
    if (schedules.length === 0) {
        document.getElementById('schedulesContent').innerHTML = '<p class="text-gray-500 text-center">No maintenance schedules found</p>';
    } else {
        let html = '<div class="space-y-3">';
        schedules.forEach(schedule => {
            const date = new Date(schedule.maintenance_date);
            const statusClass = {
                'Scheduled': 'bg-blue-100 text-blue-700',
                'In Progress': 'bg-yellow-100 text-yellow-700',
                'Completed': 'bg-green-100 text-green-700',
                'Cancelled': 'bg-red-100 text-red-700'
            }[schedule.status] || 'bg-gray-100 text-gray-700';
            
            html += `
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">${date.toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'})}</div>
                            <div class="text-xs text-gray-500">Created: ${new Date(schedule.created_at).toLocaleDateString()}</div>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusClass}">${schedule.status}</span>
                    </div>
                    ${schedule.notes ? `<div class="text-xs text-gray-600 mt-2 p-2 bg-gray-50 rounded">${schedule.notes}</div>` : ''}
                    ${schedule.status === 'Scheduled' ? `<button onclick="deleteSchedule(${schedule.id})" class="mt-2 text-xs text-red-600 hover:text-red-800"><i class="fa-solid fa-trash mr-1"></i>Delete</button>` : ''}
                </div>
            `;
        });
        html += '</div>';
        document.getElementById('schedulesContent').innerHTML = html;
    }
    
    document.getElementById('schedulesModal').classList.remove('hidden');
}

function closeSchedulesModal() {
    document.getElementById('schedulesModal').classList.add('hidden');
}

async function deleteSchedule(scheduleId) {
    if (!confirm('Are you sure you want to delete this maintenance schedule?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_schedule');
    formData.append('schedule_id', scheduleId);
    
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
        showNotification('Error deleting schedule', 'error');
        console.error(error);
    }
}

document.getElementById('maintenanceForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'schedule_maintenance');
    formData.append('room_id', document.getElementById('maintenanceRoomId').value);
    formData.append('maintenance_date', document.getElementById('maintenanceDate').value);
    formData.append('notes', document.getElementById('maintenanceNotes').value);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeMaintenanceModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification('Error scheduling maintenance', 'error');
        console.error(error);
    }
});

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMaintenanceModal();
        closeSchedulesModal();
    }
});
</script>

<?php include '../components/layout_footer.php'; ?>
