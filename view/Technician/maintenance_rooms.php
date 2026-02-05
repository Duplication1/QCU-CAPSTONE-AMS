<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has Technician role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Technician') {
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
    
    if ($action === 'update_status') {
        $schedule_id = intval($_POST['schedule_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        
        if ($schedule_id <= 0 || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'Invalid schedule ID or status']);
            exit;
        }
        
        // Verify this schedule is assigned to the current user
        $verify_stmt = $conn->prepare("SELECT id FROM maintenance_schedules WHERE id = ? AND assigned_technician_id = ?");
        $technician_id = $_SESSION['user_id'];
        $verify_stmt->bind_param('ii', $schedule_id, $technician_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $verify_stmt->close();
        
        try {
            $stmt = $conn->prepare("UPDATE maintenance_schedules SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $status, $schedule_id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Fetch my assigned maintenance tasks for this building
$technician_id = $_SESSION['user_id'];
$schedules = [];
$query = "SELECT 
    ms.*,
    r.name as room_name,
    COUNT(DISTINCT a.id) as total_assets,
    COUNT(DISTINCT CASE WHEN a.status IN ('Available', 'In Use') THEN a.id END) as active_assets
FROM maintenance_schedules ms
INNER JOIN rooms r ON ms.room_id = r.id
LEFT JOIN assets a ON r.id = a.room_id
WHERE ms.building_id = ? AND ms.assigned_technician_id = ?
GROUP BY ms.id, r.name, r.id
ORDER BY 
    CASE 
        WHEN ms.status = 'Scheduled' AND ms.maintenance_date < CURDATE() THEN 0
        WHEN ms.status = 'Scheduled' AND ms.maintenance_date >= CURDATE() THEN 1
        WHEN ms.status = 'In Progress' THEN 2
        ELSE 3
    END,
    ms.maintenance_date ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $building_id, $technician_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
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
                    <h1 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($building['name']) ?> - My Assignments</h1>
                    <p class="text-sm text-gray-500">View and manage your assigned maintenance tasks</p>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
            <?php
            $total_tasks = count($schedules);
            $overdue = count(array_filter($schedules, fn($s) => $s['status'] === 'Scheduled' && strtotime($s['maintenance_date']) < time()));
            $upcoming = count(array_filter($schedules, fn($s) => $s['status'] === 'Scheduled' && strtotime($s['maintenance_date']) >= time() && strtotime($s['maintenance_date']) <= strtotime('+7 days')));
            $in_progress = count(array_filter($schedules, fn($s) => $s['status'] === 'In Progress'));
            $completed = count(array_filter($schedules, fn($s) => $s['status'] === 'Completed'));
            ?>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Tasks</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $total_tasks ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i class="fa-solid fa-clipboard-list text-blue-600 text-xl"></i>
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
                        <p class="text-sm text-gray-600">In Progress</p>
                        <p class="text-2xl font-bold text-yellow-600"><?= $in_progress ?></p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-lg">
                        <i class="fa-solid fa-spinner text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Completed</p>
                        <p class="text-2xl font-bold text-green-600"><?= $completed ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-lg">
                        <i class="fa-solid fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Tasks Table -->
        <div class="bg-white rounded shadow-sm border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Assets</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Maintenance Date</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($schedules)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <i class="fa-solid fa-clipboard-list text-6xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg">No maintenance tasks assigned for this building</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($schedules as $schedule): ?>
                                <?php
                                $status_class = [
                                    'Scheduled' => 'bg-blue-100 text-blue-700',
                                    'In Progress' => 'bg-yellow-100 text-yellow-700',
                                    'Completed' => 'bg-green-100 text-green-700',
                                    'Cancelled' => 'bg-red-100 text-red-700'
                                ][$schedule['status']] ?? 'bg-gray-100 text-gray-700';
                                
                                $days_until = floor((strtotime($schedule['maintenance_date']) - time()) / 86400);
                                $date_class = 'text-gray-900';
                                if ($schedule['status'] === 'Scheduled' && $days_until < 0) {
                                    $date_class = 'text-red-600 font-bold';
                                } elseif ($schedule['status'] === 'Scheduled' && $days_until <= 7) {
                                    $date_class = 'text-orange-600 font-semibold';
                                }
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors" data-schedule-id="<?= $schedule['id'] ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-door-open text-blue-600 mr-3"></i>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($schedule['room_name']) ?></div>
                                                <div class="text-xs text-gray-500">Created: <?= date('M d, Y', strtotime($schedule['created_at'])) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-sm">
                                            <div class="font-bold text-gray-800"><?= $schedule['total_assets'] ?></div>
                                            <div class="text-xs text-gray-500"><?= $schedule['active_assets'] ?> active</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-sm <?= $date_class ?>">
                                            <?= date('M d, Y', strtotime($schedule['maintenance_date'])) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?php
                                            if ($days_until < 0) {
                                                echo abs($days_until) . ' days overdue';
                                            } else {
                                                echo 'In ' . $days_until . ' day' . ($days_until != 1 ? 's' : '');
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?= $status_class ?> status-badge">
                                            <?= $schedule['status'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs text-gray-700 max-w-xs">
                                            <?= $schedule['notes'] ? htmlspecialchars($schedule['notes']) : '<span class="text-gray-400">No notes</span>' ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if ($schedule['status'] !== 'Completed' && $schedule['status'] !== 'Cancelled'): ?>
                                            <button onclick="updateStatus(<?= $schedule['id'] ?>, '<?= $schedule['status'] ?>')" 
                                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                                <i class="fa-solid fa-edit mr-1"></i>Update Status
                                            </button>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400">Task <?= strtolower($schedule['status']) ?></span>
                                        <?php endif; ?>
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

<!-- Update Status Modal -->
<div id="statusModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h3 class="text-xl font-semibold text-white">Update Maintenance Status</h3>
        </div>
        <div class="p-6">
            <input type="hidden" id="statusScheduleId">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Current Status: <span id="currentStatus" class="font-semibold"></span></label>
                <label class="block text-sm font-medium text-gray-700 mb-2 mt-4">New Status</label>
                <select id="statusSelect" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="Scheduled">Scheduled</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Update the maintenance task status</p>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeStatusModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="submitStatusUpdate()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-save mr-2"></i>Update Status
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(scheduleId, currentStatus) {
    document.getElementById('statusScheduleId').value = scheduleId;
    document.getElementById('currentStatus').textContent = currentStatus;
    document.getElementById('statusSelect').value = currentStatus;
    document.getElementById('statusModal').classList.remove('hidden');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
}

async function submitStatusUpdate() {
    const scheduleId = document.getElementById('statusScheduleId').value;
    const newStatus = document.getElementById('statusSelect').value;
    
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('schedule_id', scheduleId);
    formData.append('status', newStatus);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeStatusModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification('Error updating status', 'error');
        console.error(error);
    }
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeStatusModal();
    }
});
</script>

<?php include '../components/layout_footer.php'; ?>
