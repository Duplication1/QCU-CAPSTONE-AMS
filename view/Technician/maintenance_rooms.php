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
    
    if ($action === 'update_maintenance_date') {
        $room_id = intval($_POST['room_id'] ?? 0);
        $maintenance_date = trim($_POST['maintenance_date'] ?? '');
        
        if ($room_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid room ID']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE rooms SET next_maintenance_date = ?, last_maintenance_date = CURDATE() WHERE id = ? AND building_id = ?");
            $stmt->bind_param('sii', $maintenance_date, $room_id, $building_id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Maintenance date updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update maintenance date']);
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
    r.next_maintenance_date,
    r.last_maintenance_date,
    r.created_at,
    COUNT(DISTINCT a.id) as total_assets,
    COUNT(DISTINCT CASE WHEN a.status IN ('Available', 'In Use') THEN a.id END) as active_assets
FROM rooms r
LEFT JOIN assets a ON r.id = a.room_id
WHERE r.building_id = ?
GROUP BY r.id, r.name, r.next_maintenance_date, r.last_maintenance_date, r.created_at
ORDER BY 
    CASE 
        WHEN r.next_maintenance_date IS NULL THEN 2
        WHEN r.next_maintenance_date < CURDATE() THEN 0
        ELSE 1
    END,
    r.next_maintenance_date ASC,
    r.name ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $building_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
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
                    <p class="text-sm text-gray-500">Manage maintenance schedules for rooms</p>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
            <?php
            $total_rooms = count($rooms);
            $overdue = count(array_filter($rooms, fn($r) => $r['next_maintenance_date'] && strtotime($r['next_maintenance_date']) < time()));
            $upcoming = count(array_filter($rooms, fn($r) => $r['next_maintenance_date'] && strtotime($r['next_maintenance_date']) >= time() && strtotime($r['next_maintenance_date']) <= strtotime('+7 days')));
            $not_scheduled = count(array_filter($rooms, fn($r) => !$r['next_maintenance_date']));
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
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Last Maintenance</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Next Maintenance</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($rooms)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
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
                                
                                if ($room['next_maintenance_date']) {
                                    $days_until = floor((strtotime($room['next_maintenance_date']) - time()) / 86400);
                                    if ($days_until < 0) {
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
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-door-open text-blue-600 mr-3"></i>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($room['name']) ?></div>
                                                <div class="text-xs text-gray-500">Created: <?= date('M d, Y', strtotime($room['created_at'])) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-sm">
                                            <div class="font-bold text-gray-800"><?= $room['total_assets'] ?></div>
                                            <div class="text-xs text-gray-500"><?= $room['active_assets'] ?> active</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if ($room['last_maintenance_date']): ?>
                                            <div class="text-sm text-gray-700">
                                                <?= date('M d, Y', strtotime($room['last_maintenance_date'])) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php
                                                $days_ago = floor((time() - strtotime($room['last_maintenance_date'])) / 86400);
                                                echo $days_ago . ' day' . ($days_ago != 1 ? 's' : '') . ' ago';
                                                ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-400">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div id="next-maintenance-<?= $room['id'] ?>">
                                            <?php if ($room['next_maintenance_date']): ?>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= date('M d, Y', strtotime($room['next_maintenance_date'])) ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <?php
                                                    $days_until = floor((strtotime($room['next_maintenance_date']) - time()) / 86400);
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
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?= $status_class ?>">
                                            <i class="fa-solid <?= $status_icon ?> mr-1"></i><?= $status_text ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button onclick="openMaintenanceModal(<?= $room['id'] ?>, '<?= htmlspecialchars($room['name'], ENT_QUOTES) ?>', '<?= $room['next_maintenance_date'] ?? '' ?>')" 
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

<!-- Maintenance Schedule Modal -->
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Next Maintenance Date</label>
                <input type="date" id="maintenanceDate" name="maintenance_date" required
                       min="<?= date('Y-m-d') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Select the date for the next scheduled maintenance</p>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeMaintenanceModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-save mr-2"></i>Save Schedule
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openMaintenanceModal(roomId, roomName, currentDate) {
    document.getElementById('maintenanceRoomId').value = roomId;
    document.getElementById('maintenanceRoomName').value = roomName;
    document.getElementById('maintenanceDate').value = currentDate || '';
    document.getElementById('maintenanceModal').classList.remove('hidden');
}

function closeMaintenanceModal() {
    document.getElementById('maintenanceModal').classList.add('hidden');
    document.getElementById('maintenanceForm').reset();
}

document.getElementById('maintenanceForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'update_maintenance_date');
    formData.append('room_id', document.getElementById('maintenanceRoomId').value);
    formData.append('maintenance_date', document.getElementById('maintenanceDate').value);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            closeMaintenanceModal();
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error updating maintenance date');
        console.error(error);
    }
});

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMaintenanceModal();
    }
});
</script>

<?php include '../components/layout_footer.php'; ?>
