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

// Get current technician user ID
$technician_id = $_SESSION['user_id'];

// Fetch Buildings Assigned to this Technician
$buildings = [];
$query = "SELECT 
    b.id,
    b.name,
    b.created_at,
    bt.assigned_at,
    COUNT(DISTINCT r.id) as total_rooms,
    COUNT(DISTINCT ms.id) as total_schedules,
    COUNT(DISTINCT CASE WHEN ms.status = 'Scheduled' AND ms.maintenance_date < CURDATE() THEN ms.id END) as overdue_maintenance,
    COUNT(DISTINCT CASE WHEN ms.status = 'Scheduled' AND ms.maintenance_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN ms.id END) as upcoming_maintenance,
    MIN(CASE WHEN ms.status = 'Scheduled' AND ms.maintenance_date >= CURDATE() THEN ms.maintenance_date END) as earliest_maintenance
FROM building_technicians bt
INNER JOIN buildings b ON bt.building_id = b.id
LEFT JOIN rooms r ON b.id = r.building_id
LEFT JOIN maintenance_schedules ms ON r.id = ms.room_id AND b.id = ms.building_id
WHERE bt.technician_id = ?
GROUP BY b.id, b.name, b.created_at, bt.assigned_at
ORDER BY 
    CASE 
        WHEN MIN(CASE WHEN ms.status = 'Scheduled' AND ms.maintenance_date < CURDATE() THEN ms.maintenance_date END) IS NOT NULL THEN 0
        WHEN MIN(CASE WHEN ms.status = 'Scheduled' AND ms.maintenance_date >= CURDATE() THEN ms.maintenance_date END) IS NOT NULL THEN 1
        ELSE 2
    END,
    MIN(CASE WHEN ms.status = 'Scheduled' THEN ms.maintenance_date END) ASC,
    b.name ASC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}
$stmt->bind_param('i', $technician_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $buildings[] = $row;
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
            <div>
                <h1 class="text-xl font-bold text-gray-800">My Maintenance Assignments</h1>
                <p class="text-sm text-gray-500">View and manage your assigned maintenance tasks</p>
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
                        <p class="text-sm text-gray-600">Buildings Assigned</p>
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
                        <p class="text-sm text-gray-600">Rooms Assigned</p>
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
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Buildings with Assigned Tasks</h2>
            
            <?php if (empty($buildings)): ?>
                <div class="text-center py-12">
                    <i class="fa-solid fa-clipboard-list text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg font-medium">No Maintenance Tasks Assigned</p>
                    <p class="text-gray-400 text-sm mt-2">You will be notified when new maintenance tasks are assigned to you</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($buildings as $building): ?>
                        <a href="maintenance_rooms.php?building_id=<?= $building['id'] ?>" 
                           class="block bg-gray-50 rounded-lg border-2 border-gray-200 p-4 hover:border-blue-500 hover:shadow-md transition-all cursor-pointer">
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
                            
                            <div class="space-y-2">
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
                                <div class="flex items-center justify-between text-sm text-blue-600 font-medium">
                                    <span>Manage Rooms</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../components/layout_footer.php'; ?>
