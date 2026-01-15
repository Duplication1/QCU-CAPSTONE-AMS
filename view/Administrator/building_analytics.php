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

// Fetch buildings with analytics data
$buildings = [];
$query = "SELECT 
    b.id,
    b.name,
    b.created_at,
    COUNT(DISTINCT r.id) as total_rooms,
    COUNT(DISTINCT CASE WHEN a.status IN ('Available', 'In Use') THEN a.id END) as active_assets,
    COUNT(DISTINCT CASE WHEN a.status = 'Disposed' THEN a.id END) as disposed_assets,
    COUNT(DISTINCT CASE WHEN a.status IN ('Available', 'In Use') AND a.condition = 'Good' THEN a.id END) as good_condition,
    COUNT(DISTINCT CASE WHEN a.status IN ('Available', 'In Use') AND a.condition = 'Fair' THEN a.id END) as fair_condition,
    COUNT(DISTINCT CASE WHEN a.status IN ('Available', 'In Use') AND a.condition = 'Poor' THEN a.id END) as poor_condition,
    COUNT(DISTINCT i.id) as total_issues,
    COUNT(DISTINCT CASE WHEN i.status = 'pending' THEN i.id END) as pending_issues,
    COUNT(DISTINCT CASE WHEN i.status = 'in_progress' THEN i.id END) as inprogress_issues,
    COUNT(DISTINCT CASE WHEN i.status = 'resolved' THEN i.id END) as resolved_issues
FROM buildings b
LEFT JOIN rooms r ON b.id = r.building_id
LEFT JOIN assets a ON r.id = a.room_id
LEFT JOIN issues i ON r.id = i.room_id
GROUP BY b.id, b.name, b.created_at
ORDER BY b.name ASC";

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
.stat-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 0.5rem;
    padding: 0.75rem;
    color: white;
    text-align: center;
}
.stat-box.assets {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}
.stat-box.issues {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}
.stat-box.condition {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}
</style>

<main>
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white rounded shadow-sm border border-gray-200 mb-3">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Building Analytics</h1>
                <p class="text-sm text-gray-500">Overview of assets, rooms, and issues by building</p>
            </div>
        </div>

        <!-- Content Area -->
        <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200 p-4">
            <!-- Buildings Grid -->
            <div id="buildingsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php if (empty($buildings)): ?>
                    <div class="col-span-full text-center py-12">
                        <i class="fa-solid fa-building text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg">No buildings found</p>
                        <p class="text-gray-400 text-sm">Buildings will appear here once created</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($buildings as $building): ?>
                        <div class="building-card bg-white border-2 border-gray-200 rounded-xl p-5 cursor-pointer hover:border-blue-500"
                             onclick="window.location.href='room_analytics.php?building_id=<?= $building['id'] ?>'">
                            
                            <!-- Building Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-gray-800 mb-1"><?= htmlspecialchars($building['name']) ?></h3>
                                    <p class="text-xs text-gray-500">
                                        <i class="fa-solid fa-door-open mr-1"></i><?= $building['total_rooms'] ?> Room<?= $building['total_rooms'] != 1 ? 's' : '' ?>
                                    </p>
                                </div>
                                <div class="bg-blue-100 p-2 rounded-lg">
                                    <i class="fa-solid fa-building text-blue-600 text-xl"></i>
                                </div>
                            </div>

                            <!-- Analytics Grid -->
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <!-- Total Assets -->
                                <div class="stat-box assets">
                                    <div class="text-2xl font-bold"><?= $building['active_assets'] ?></div>
                                    <div class="text-xs opacity-90">Active Assets</div>
                                </div>
                                
                                <!-- Total Issues -->
                                <div class="stat-box issues">
                                    <div class="text-2xl font-bold"><?= $building['total_issues'] ?></div>
                                    <div class="text-xs opacity-90">Total Issues</div>
                                </div>
                            </div>

                            <!-- Condition Breakdown -->
                            <div class="bg-gray-50 rounded-lg p-3 mb-3">
                                <p class="text-xs font-semibold text-gray-600 mb-2">Asset Condition</p>
                                <div class="grid grid-cols-3 gap-1 text-center">
                                    <div>
                                        <div class="text-green-600 font-bold text-sm"><?= $building['good_condition'] ?></div>
                                        <div class="text-xs text-gray-500">Good</div>
                                    </div>
                                    <div>
                                        <div class="text-yellow-600 font-bold text-sm"><?= $building['fair_condition'] ?></div>
                                        <div class="text-xs text-gray-500">Fair</div>
                                    </div>
                                    <div>
                                        <div class="text-red-600 font-bold text-sm"><?= $building['poor_condition'] ?></div>
                                        <div class="text-xs text-gray-500">Poor</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Issue Status -->
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs font-semibold text-gray-600 mb-2">Issue Status</p>
                                <div class="grid grid-cols-3 gap-1 text-center">
                                    <div>
                                        <div class="text-orange-600 font-bold text-sm"><?= $building['pending_issues'] ?></div>
                                        <div class="text-xs text-gray-500">Pending</div>
                                    </div>
                                    <div>
                                        <div class="text-blue-600 font-bold text-sm"><?= $building['inprogress_issues'] ?></div>
                                        <div class="text-xs text-gray-500">In Progress</div>
                                    </div>
                                    <div>
                                        <div class="text-green-600 font-bold text-sm"><?= $building['resolved_issues'] ?></div>
                                        <div class="text-xs text-gray-500">Resolved</div>
                                    </div>
                                </div>
                            </div>

                            <!-- View Details Button -->
                            <div class="mt-4 pt-3 border-t border-gray-200">
                                <button class="w-full text-center text-blue-600 hover:text-blue-700 font-medium text-sm">
                                    <i class="fa-solid fa-chart-line mr-1"></i>View Room Analytics
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
