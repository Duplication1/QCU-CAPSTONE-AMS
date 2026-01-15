<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has Administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
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
    header("Location: building_analytics.php");
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
    header("Location: building_analytics.php");
    exit();
}

// Fetch rooms with analytics data
$rooms = [];
$query = "SELECT 
    r.id,
    r.name,
    r.created_at,
    COUNT(DISTINCT CASE WHEN a.status IN ('Available', 'In Use') THEN a.id END) as active_assets,
    COUNT(DISTINCT CASE WHEN a.status = 'Disposed' THEN a.id END) as disposed_assets,
    COUNT(DISTINCT CASE WHEN a.status IN ('Available', 'In Use') AND a.condition = 'Good' THEN a.id END) as good_condition,
    COUNT(DISTINCT CASE WHEN a.status IN ('Available', 'In Use') AND a.condition = 'Fair' THEN a.id END) as fair_condition,
    COUNT(DISTINCT CASE WHEN a.status IN ('Available', 'In Use') AND a.condition = 'Poor' THEN a.id END) as poor_condition,
    COUNT(DISTINCT CASE WHEN a.category = 'Desktop Computer' THEN a.id END) as desktop_count,
    COUNT(DISTINCT CASE WHEN a.category = 'Laptop' THEN a.id END) as laptop_count,
    COUNT(DISTINCT CASE WHEN a.category = 'Monitor' THEN a.id END) as monitor_count,
    COUNT(DISTINCT i.id) as total_issues,
    COUNT(DISTINCT CASE WHEN i.status = 'pending' THEN i.id END) as pending_issues,
    COUNT(DISTINCT CASE WHEN i.status = 'in_progress' THEN i.id END) as inprogress_issues,
    COUNT(DISTINCT CASE WHEN i.status = 'resolved' THEN i.id END) as resolved_issues,
    COUNT(DISTINCT CASE WHEN i.category = 'hardware' THEN i.id END) as hardware_issues,
    COUNT(DISTINCT CASE WHEN i.category = 'software' THEN i.id END) as software_issues,
    COUNT(DISTINCT CASE WHEN i.category = 'network' THEN i.id END) as network_issues
FROM rooms r
LEFT JOIN assets a ON r.id = a.room_id
LEFT JOIN issues i ON r.id = i.room_id
WHERE r.building_id = ?
GROUP BY r.id, r.name, r.created_at
ORDER BY r.name ASC";

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
                <a href="building_analytics.php" class="text-gray-600 hover:text-gray-800">
                    <i class="fa-solid fa-arrow-left text-lg"></i>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($building['name']) ?> - Room Analytics</h1>
                    <p class="text-sm text-gray-500">Detailed analytics for each room</p>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0 z-10">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Assets</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Types</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Issues</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Issue Types</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($rooms)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <i class="fa-solid fa-door-open text-6xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg">No rooms found in this building</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rooms as $room): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                <i class="fa-solid fa-door-open text-blue-600"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($room['name']) ?></div>
                                                <div class="text-xs text-gray-500">ID: <?= $room['id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-sm">
                                            <div class="font-bold text-green-600 text-lg"><?= $room['active_assets'] ?></div>
                                            <div class="text-xs text-gray-500">Active</div>
                                            <?php if ($room['disposed_assets'] > 0): ?>
                                                <div class="text-xs text-red-500 mt-1"><?= $room['disposed_assets'] ?> disposed</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center gap-3">
                                            <div class="text-center">
                                                <div class="text-sm font-bold text-green-600"><?= $room['good_condition'] ?></div>
                                                <div class="text-xs text-gray-500">Good</div>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-sm font-bold text-yellow-600"><?= $room['fair_condition'] ?></div>
                                                <div class="text-xs text-gray-500">Fair</div>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-sm font-bold text-red-600"><?= $room['poor_condition'] ?></div>
                                                <div class="text-xs text-gray-500">Poor</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center gap-3">
                                            <?php if ($room['desktop_count'] > 0): ?>
                                                <div class="text-center">
                                                    <div class="text-sm font-bold text-blue-600"><?= $room['desktop_count'] ?></div>
                                                    <div class="text-xs text-gray-500">Desktop</div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($room['laptop_count'] > 0): ?>
                                                <div class="text-center">
                                                    <div class="text-sm font-bold text-purple-600"><?= $room['laptop_count'] ?></div>
                                                    <div class="text-xs text-gray-500">Laptop</div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($room['monitor_count'] > 0): ?>
                                                <div class="text-center">
                                                    <div class="text-sm font-bold text-indigo-600"><?= $room['monitor_count'] ?></div>
                                                    <div class="text-xs text-gray-500">Monitor</div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($room['desktop_count'] == 0 && $room['laptop_count'] == 0 && $room['monitor_count'] == 0): ?>
                                                <span class="text-xs text-gray-400">No major assets</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center gap-3">
                                            <div class="text-center">
                                                <div class="text-sm font-bold text-orange-600"><?= $room['pending_issues'] ?></div>
                                                <div class="text-xs text-gray-500">Pending</div>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-sm font-bold text-blue-600"><?= $room['inprogress_issues'] ?></div>
                                                <div class="text-xs text-gray-500">In Progress</div>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-sm font-bold text-green-600"><?= $room['resolved_issues'] ?></div>
                                                <div class="text-xs text-gray-500">Resolved</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center gap-3">
                                            <?php if ($room['hardware_issues'] > 0): ?>
                                                <div class="text-center">
                                                    <div class="text-sm font-bold text-red-600"><?= $room['hardware_issues'] ?></div>
                                                    <div class="text-xs text-gray-500">Hardware</div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($room['software_issues'] > 0): ?>
                                                <div class="text-center">
                                                    <div class="text-sm font-bold text-blue-600"><?= $room['software_issues'] ?></div>
                                                    <div class="text-xs text-gray-500">Software</div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($room['network_issues'] > 0): ?>
                                                <div class="text-center">
                                                    <div class="text-sm font-bold text-purple-600"><?= $room['network_issues'] ?></div>
                                                    <div class="text-xs text-gray-500">Network</div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($room['hardware_issues'] == 0 && $room['software_issues'] == 0 && $room['network_issues'] == 0): ?>
                                                <span class="text-xs text-gray-400">No issues</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <a href="asset_analytics.php?room_id=<?= $room['id'] ?>" 
                                           class="inline-flex items-center px-3 py-2 border border-blue-600 text-sm font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50 transition-colors">
                                            <i class="fa-solid fa-chart-bar mr-2"></i>View Details
                                        </a>
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

<script>
// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // Future modal handling
    }
});
</script>

<?php include '../components/layout_footer.php'; ?>
