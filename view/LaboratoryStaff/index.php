<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';

// Establish mysqli database connection
$dbConfig = Config::database();
try {
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die("Database connection error");
}

// Fetch dashboard data
// Unassigned issues
$unassignedIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE (assigned_group IS NULL OR assigned_group = '') AND status = 'Open' AND category != 'borrow'")->fetch_assoc()['count'];

// In Progress issues
$inProgressIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'In Progress'")->fetch_assoc()['count'];

// Resolved issues
$resolvedIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'Resolved'")->fetch_assoc()['count'];

// Asset status counts
$assetsBorrowed = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Pending'")->fetch_assoc()['count'];
$assetsAvailable = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Available'")->fetch_assoc()['count'];
$assetsInUse = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'In Use'")->fetch_assoc()['count'];
$assetsCritical = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` IN ('Non-Functional', 'Poor')")->fetch_assoc()['count'];

// Attention needed assets
$needsAttention = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Fair' OR next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['count'];

// Healthy assets (Good and Excellent condition)
$healthyAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` IN ('Good', 'Excellent')")->fetch_assoc()['count'];

// Asset number status (just for display variation)
$totalAssets = $conn->query("SELECT COUNT(*) as count FROM assets")->fetch_assoc()['count'];

// End of Life - Assets expiring within 6 months
$assetsNearEOL = $conn->query("SELECT COUNT(*) as count FROM assets WHERE end_of_life IS NOT NULL AND end_of_life <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH) AND end_of_life >= CURDATE()")->fetch_assoc()['count'];

// Fetch buildings dynamically from database
$buildingsData = [];
$buildings_query = "SELECT id, name FROM buildings ORDER BY name ASC";
$buildings_result = $conn->query($buildings_query);
if ($buildings_result && $buildings_result->num_rows > 0) {
    while ($building_row = $buildings_result->fetch_assoc()) {
        // Get rooms for each building
        $rooms_query = "SELECT id, name FROM rooms WHERE building_id = ? ORDER BY name ASC";
        $rooms_stmt = $conn->prepare($rooms_query);
        $rooms_stmt->bind_param('i', $building_row['id']);
        $rooms_stmt->execute();
        $rooms_result = $rooms_stmt->get_result();
        
        $rooms = [];
        while ($room_row = $rooms_result->fetch_assoc()) {
            // For now, all labs are offline
            $rooms[] = [
                'name' => $room_row['name'],
                'status' => 'offline',
                'ping' => 'N/A'
            ];
        }
        $rooms_stmt->close();
        
        $buildingsData[] = [
            'id' => $building_row['id'],
            'name' => $building_row['name'],
            'rooms' => $rooms,
            'room_count' => count($rooms)
        ];
    }
}

// Fetch recent 5 activity logs
$recent_logs_query = "SELECT al.action, al.entity_type, al.entity_id, al.description, 
                             COALESCE(u.full_name, 'System') as performed_by, al.created_at 
                      FROM activity_logs al
                      LEFT JOIN users u ON al.user_id = u.id
                      ORDER BY al.created_at DESC 
                      LIMIT 5";
$recent_logs_result = $conn->query($recent_logs_query);
$recent_logs = [];
if ($recent_logs_result && $recent_logs_result->num_rows > 0) {
    while ($log_row = $recent_logs_result->fetch_assoc()) {
        $recent_logs[] = $log_row;
    }
}

// Get count of new unassigned tickets
$new_tickets_query = "SELECT COUNT(*) as count FROM issues 
                      WHERE (assigned_group IS NULL OR assigned_group = '') 
                      AND status = 'Open' 
                      AND category != 'borrow'";
$new_tickets_result = $conn->query($new_tickets_query);
$new_tickets_count = 0;
if ($new_tickets_result) {
    $new_tickets_row = $new_tickets_result->fetch_assoc();
    $new_tickets_count = (int)$new_tickets_row['count'];
}

include '../components/layout_header.php';
?>
        <style>
            body, html { overflow: hidden !important; height: 100vh; }
            .metric-card {
                transition: all 0.2s ease;
                position: relative;
            }
            .metric-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            }
            .metric-increment {
                position: absolute;
                top: 10px;
                right: 10px;
                font-size: 0.875rem;
                font-weight: 600;
                color: #10b981;
                animation: fadeUp 5s ease-out forwards;
                pointer-events: none;
                z-index: 10;
            }
            .metric-decrement {
                position: absolute;
                top: 10px;
                right: 10px;
                font-size: 0.875rem;
                font-weight: 600;
                color: #ef4444;
                animation: fadeUp 5s ease-out forwards;
                pointer-events: none;
                z-index: 10;
            }
            @keyframes fadeUp {
                0% {
                    opacity: 1;
                    transform: translateY(0);
                }
                100% {
                    opacity: 0;
                    transform: translateY(-20px);
                }
            }
        </style>
        <!-- Main Content -->
        <main class="p-4 bg-gray-50 h-screen overflow-hidden flex flex-col">
            <!-- Top Metrics Row -->
            <div class="grid grid-cols-5 gap-4 mb-4 flex-shrink-0">
                <!-- Unassigned Tickets -->
                <div class="metric-card bg-white rounded-lg shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Unassigned Tickets</p>
                            <p class="text-3xl font-bold text-gray-900" data-metric="unassignedIssues"><?php echo $unassignedIssues; ?></p>
                        </div>
                        <div class="w-16 h-10">
                            <svg class="w-full h-full" viewBox="0 0 80 40" fill="none" preserveAspectRatio="none">
                                <path d="M0 35 L16 30 L32 28 L48 25 L64 22 L80 18" stroke="#ef4444" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 font-medium">Awaiting assignment</p>
                </div>

                <!-- In Progress Tickets -->
                <div class="metric-card bg-white rounded-lg shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">In Progress Tickets</p>
                            <p class="text-3xl font-bold text-gray-900" data-metric="inProgressIssues"><?php echo $inProgressIssues; ?></p>
                        </div>
                        <div class="w-16 h-10">
                            <svg class="w-full h-full" viewBox="0 0 80 40" fill="none" preserveAspectRatio="none">
                                <path d="M0 32 L16 28 L32 30 L48 26 L64 24 L80 20" stroke="#f59e0b" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 font-medium">Currently being worked on</p>
                </div>

                <!-- Resolved Tickets -->
                <div class="metric-card bg-white rounded-lg shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Resolved Tickets</p>
                            <p class="text-3xl font-bold text-gray-900" data-metric="resolvedIssues"><?php echo $resolvedIssues; ?></p>
                        </div>
                        <div class="w-16 h-10">
                            <svg class="w-full h-full" viewBox="0 0 80 40" fill="none" preserveAspectRatio="none">
                                <path d="M0 30 L16 28 L32 25 L48 27 L64 23 L80 20" stroke="#10b981" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-green-600 font-medium">Successfully completed</p>
                </div>

                <!-- Healthy Assets -->
                <div class="metric-card bg-white rounded-lg shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Healthy Assets</p>
                            <p class="text-3xl font-bold text-gray-900" data-metric="healthyAssets"><?php echo $healthyAssets; ?></p>
                        </div>
                        <div class="w-16 h-10">
                            <svg class="w-full h-full" viewBox="0 0 80 40" fill="none" preserveAspectRatio="none">
                                <path d="M0 25 L16 22 L32 20 L48 18 L64 16 L80 14" stroke="#10b981" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-green-600 font-medium">Good/Excellent condition</p>
                </div>

                <!-- End of Life Assets -->
                <div class="metric-card bg-white rounded-lg shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Near End of Life</p>
                            <p class="text-3xl font-bold text-gray-900" data-metric="assetsNearEOL"><?php echo $assetsNearEOL; ?></p>
                        </div>
                        <div class="w-16 h-10">
                            <svg class="w-full h-full" viewBox="0 0 80 40" fill="none" preserveAspectRatio="none">
                                <path d="M0 28 L16 26 L32 25 L48 23 L64 21 L80 19" stroke="#f59e0b" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-orange-600 font-medium">Expiring within 6 months</p>
                </div>
            </div>

            <!-- Asset Status Metrics Row -->
            <div class="grid grid-cols-5 gap-4 mb-4 flex-shrink-0">
                <!-- Assets Borrowed -->
                <div class="metric-card bg-white rounded-lg shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Assets Borrowed</p>
                            <p class="text-3xl font-bold text-gray-900" data-metric="assetsBorrowed"><?php echo $assetsBorrowed; ?></p>
                        </div>
                        <div class="w-16 h-10">
                            <svg class="w-full h-full" viewBox="0 0 80 40" fill="none" preserveAspectRatio="none">
                                <path d="M0 35 L16 30 L32 28 L48 25 L64 22 L80 18" stroke="#8b5cf6" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-purple-600 font-medium">Requesting to borrow</p>
                </div>

                <!-- Assets Available -->
                <div class="metric-card bg-white rounded-lg shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Assets Available</p>
                            <p class="text-3xl font-bold text-gray-900" data-metric="assetsAvailable"><?php echo $assetsAvailable; ?></p>
                        </div>
                        <div class="w-16 h-10">
                            <svg class="w-full h-full" viewBox="0 0 80 40" fill="none" preserveAspectRatio="none">
                                <path d="M0 32 L16 28 L32 30 L48 26 L64 24 L80 20" stroke="#10b981" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-green-600 font-medium">Ready for use</p>
                </div>

                <!-- Assets In Use -->
                <div class="metric-card bg-white rounded-lg shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Assets In Use</p>
                            <p class="text-3xl font-bold text-gray-900" data-metric="assetsInUse"><?php echo $assetsInUse; ?></p>
                        </div>
                        <div class="w-16 h-10">
                            <svg class="w-full h-full" viewBox="0 0 80 40" fill="none" preserveAspectRatio="none">
                                <path d="M0 30 L16 28 L32 25 L48 27 L64 23 L80 20" stroke="#3b82f6" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-blue-600 font-medium">Currently deployed</p>
                </div>

                <!-- Needs Attention -->
                <div class="metric-card bg-white rounded-lg shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Needs Attention</p>
                            <p class="text-3xl font-bold text-gray-900" data-metric="needsAttention"><?php echo $needsAttention; ?></p>
                        </div>
                        <div class="w-16 h-10">
                            <svg class="w-full h-full" viewBox="0 0 80 40" fill="none" preserveAspectRatio="none">
                                <path d="M0 28 L16 26 L32 24 L48 22 L64 20 L80 18" stroke="#f59e0b" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-orange-600 font-medium">Requires maintenance</p>
                </div>

                <!-- Critical Assets -->
                <div class="metric-card bg-white rounded-lg shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Critical Assets</p>
                            <p class="text-3xl font-bold text-gray-900" data-metric="assetsCritical"><?php echo $assetsCritical; ?></p>
                        </div>
                        <div class="w-16 h-10">
                            <svg class="w-full h-full" viewBox="0 0 80 40" fill="none" preserveAspectRatio="none">
                                <path d="M0 30 L16 28 L32 26 L48 25 L64 22 L80 19" stroke="#ef4444" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-red-600 font-medium">Poor/Non-functional</p>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-5 gap-3 flex-1 min-h-0">
                <?php foreach($buildingsData as $building): ?>
                <!-- <?php echo htmlspecialchars($building['name']); ?> Building -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
                    <div class="mb-2">
                        <h3 class="text-xs font-bold text-gray-900"><?php echo strtoupper(htmlspecialchars($building['name'])); ?></h3>
                        <p class="text-[10px] text-gray-500"><?php echo $building['room_count']; ?> Labs</p>
                    </div>
                    
                    <!-- Network Graph -->
                    <div class="mb-2 bg-gray-50 rounded p-2">
                        <p class="text-[9px] text-gray-600 mb-1">Network Activity</p>
                        <div class="h-16">
                            <canvas id="building<?php echo $building['id']; ?>NetworkChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Average Ping -->
                    <div class="mb-2 bg-gray-50 rounded p-2 text-center">
                        <p class="text-[9px] text-gray-600 mb-0.5">Avg Ping</p>
                        <p class="text-lg font-bold text-gray-600">N/A</p>
                    </div>
                    
                    <div class="space-y-1.5">
                        <?php foreach($building['rooms'] as $room): ?>
                        <div class="bg-gray-50 rounded p-1.5 border border-gray-200">
                            <div class="flex items-center justify-between mb-0.5">
                                <span class="text-[10px] font-semibold text-gray-900"><?php echo htmlspecialchars($room['name']); ?></span>
                                <span class="text-[9px] font-medium <?php echo $room['status'] === 'online' ? 'text-green-600' : ($room['status'] === 'warning' ? 'text-orange-600' : 'text-gray-600'); ?>">
                                    <?php echo ucfirst($room['status']); ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[9px] text-gray-500">Ping:</span>
                                <span class="text-[9px] font-medium text-gray-700"><?php echo $room['ping']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Recent Activity Logs -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-gray-900">Recent Activity</h3>
                        <a href="activity_logs.php" class="text-xs text-purple-600 hover:text-purple-700 font-medium">View All</a>
                    </div>
                    
                    <div id="activityLogsContainer" class="space-y-2">
                        <?php if (empty($recent_logs)): ?>
                            <p class="text-xs text-gray-500 text-center py-4">No recent activity</p>
                        <?php else: ?>
                            <?php foreach($recent_logs as $log): ?>
                            <div class="bg-gray-50 rounded p-2 border border-gray-200 hover:bg-gray-100 transition-colors">
                                <div class="flex items-start justify-between mb-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-medium
                                        <?php 
                                        switch($log['action']) {
                                            case 'create': echo 'bg-green-100 text-green-800'; break;
                                            case 'update': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'delete': echo 'bg-red-100 text-red-800'; break;
                                            case 'scan': echo 'bg-purple-100 text-purple-800'; break;
                                            case 'assign': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'view': echo 'bg-indigo-100 text-indigo-800'; break;
                                            case 'export': echo 'bg-cyan-100 text-cyan-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo strtoupper(htmlspecialchars($log['action'])); ?>
                                    </span>
                                    <span class="text-[9px] text-gray-500">
                                        <?php 
                                        $time_ago = abs(time() - strtotime($log['created_at']));
                                        if ($time_ago < 60) echo $time_ago . 's ago';
                                        elseif ($time_ago < 3600) echo floor($time_ago / 60) . 'm ago';
                                        elseif ($time_ago < 86400) echo floor($time_ago / 3600) . 'h ago';
                                        else echo floor($time_ago / 86400) . 'd ago';
                                        ?>
                                    </span>
                                </div>
                                <p class="text-[10px] text-gray-700 font-medium mb-1">
                                    <?php echo ucfirst(htmlspecialchars($log['entity_type'])); ?>
                                    <?php if ($log['entity_id']): ?>
                                        <span class="text-gray-500">#<?php echo htmlspecialchars($log['entity_id']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-[9px] text-gray-600 truncate">
                                    <?php echo htmlspecialchars($log['description']); ?>
                                </p>
                                <p class="text-[9px] text-gray-500 mt-1">
                                    by <?php echo htmlspecialchars($log['performed_by']); ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
                    <!-- UNASSIGNED -->
                    <div class="stat-card card-hover bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl shadow-lg border border-purple-200 p-4 flex flex-col justify-center items-center relative overflow-hidden">
                        <div class="absolute top-2 right-2 w-8 h-8 bg-purple-200 rounded-full flex items-center justify-center opacity-50">
                            <svg class="w-4 h-4 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        <p class="text-xs font-bold uppercase mb-2 text-purple-900 tracking-wider">UNASSIGNED</p>
                        <p class="text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-purple-900"><?php echo $unassignedIssues; ?></p>
                    </div>

                    <!-- IN PROGRESS -->
                    <div class="stat-card card-hover bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl shadow-lg border border-blue-200 p-4 flex flex-col justify-center items-center relative overflow-hidden" style="animation-delay: 0.1s;">
                        <div class="absolute top-2 right-2 w-8 h-8 bg-blue-200 rounded-full flex items-center justify-center opacity-50">
                            <svg class="w-4 h-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <p class="text-xs font-bold uppercase mb-2 text-blue-900 tracking-wider">IN PROGRESS</p>
                        <p class="text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-blue-900"><?php echo $inProgressIssues; ?></p>
                    </div>

                    <!-- RESOLVED -->
                    <div class="stat-card card-hover bg-gradient-to-br from-green-100 to-green-50 rounded-xl shadow-lg border border-green-200 p-4 flex flex-col justify-center items-center relative overflow-hidden" style="animation-delay: 0.2s;">
                        <div class="absolute top-2 right-2 w-8 h-8 bg-green-200 rounded-full flex items-center justify-center opacity-50">
                            <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <p class="text-xs font-bold uppercase mb-2 text-green-900 tracking-wider">RESOLVED</p>
                        <p class="text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-green-900"><?php echo $resolvedIssues; ?></p>
                    </div>
                </div>

                <!-- Column 2: Assets Status & Borrowed -->
                <div class="col-span-1 grid grid-rows-3 gap-3">
                    <!-- ASSETS STATUS -->
                    <div class="stat-card card-hover bg-gradient-to-br from-indigo-100 to-indigo-50 rounded-xl shadow-lg border border-indigo-200 p-3 flex flex-col justify-center items-center" style="animation-delay: 0.3s;">
                        <div class="mb-2">
                            <svg class="w-8 h-8 text-indigo-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <p class="text-[10px] font-bold uppercase mb-1 text-indigo-900 tracking-wider">ASSETS STATUS</p>
                        <p class="text-[8px] uppercase text-indigo-700">BORROWED • AVAILABLE</p>
                        <p class="text-[8px] uppercase text-indigo-700">IN USE • CRITICAL</p>
                    </div>

                    <!-- BORROWED -->
                    <div class="row-span-2 stat-card card-hover bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl shadow-lg border border-purple-200 p-5 flex flex-col justify-center items-center relative overflow-hidden" style="animation-delay: 0.4s;">
                        <div class="absolute -top-10 -right-10 w-32 h-32 bg-purple-200 rounded-full opacity-20"></div>
                        <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-purple-300 rounded-full opacity-20"></div>
                        <div class="mb-3">
                            <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-sm font-bold uppercase mb-3 text-purple-900 tracking-wider">BORROWED</p>
                        <p class="text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-purple-900 relative z-10"><?php echo $assetsBorrowed; ?></p>
                    </div>
                </div>

                <!-- Column 3: Buildings (4 rows) -->
                <div class="col-span-1 grid grid-rows-5 gap-3">
                    <?php $delay = 0.5; foreach($buildings as $building): ?>
                    <div class="stat-card card-hover bg-white rounded-lg shadow-md border border-gray-200 p-2 flex items-center justify-between hover:border-purple-300" style="animation-delay: <?php echo $delay; ?>s;">
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                            <div class="flex-1">
                                <p class="text-[9px] font-semibold text-gray-900 leading-tight">Building</p>
                                <p class="text-[7px] text-gray-600 mt-0.5"><?php echo htmlspecialchars($building['name']); ?></p>
                            </div>
                        </div>
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <?php $delay += 0.05; endforeach; ?>
                </div>

                <!-- Column 4: Lab Rooms (6 rows) -->
                <div class="col-span-1 grid grid-rows-6 gap-2">
                    <?php $delay = 0.6; foreach($labRooms as $room): ?>
                    <div class="stat-card card-hover bg-white rounded-lg shadow-md border border-gray-200 p-2 flex items-center justify-between hover:border-blue-300" style="animation-delay: <?php echo $delay; ?>s;">
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                            <div class="flex-1">
                                <p class="text-[9px] font-semibold text-gray-900 leading-tight">Lab Room</p>
                                <p class="text-[7px] text-gray-600 mt-0.5"><?php echo htmlspecialchars($room['name']); ?></p>
                            </div>
                        </div>
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <?php $delay += 0.05; endforeach; ?>
                </div>

                <!-- Column 5: HEALTHY ASSETS -->
                <div class="stat-card card-hover col-span-1 bg-gradient-to-br from-purple-500 via-purple-600 to-indigo-600 rounded-2xl shadow-2xl border border-purple-400 p-5 flex flex-col justify-center items-center relative overflow-hidden" style="animation-delay: 0.7s;">
                    <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent"></div>
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-20 -left-20 w-40 h-40 bg-white/10 rounded-full"></div>
                    <div class="relative z-10 text-center w-full">
                        <div class="mb-2">
                            <svg class="w-10 h-10 text-white mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <p class="text-xs font-bold uppercase mb-3 text-white tracking-widest">HEALTHY ASSETS</p>
                        <p class="text-7xl font-black mb-4 text-white drop-shadow-lg"><?php echo $assetsAvailable; ?></p>
                        <div class="w-full h-32 bg-white/20 rounded-lg p-2 backdrop-blur-sm">
                            <canvas id="healthyAssetsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Column 6: Three sections -->
                <div class="col-span-1 grid grid-rows-3 gap-3">
                    <!-- NEEDS ATTENTION ASSETS -->
                    <div class="stat-card card-hover bg-gradient-to-br from-yellow-100 to-amber-50 rounded-xl shadow-lg border border-yellow-300 p-4 flex flex-col justify-center items-center relative" style="animation-delay: 0.8s;">
                        <div class="absolute top-2 right-2">
                            <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <p class="text-[9px] font-bold uppercase text-center mb-2 text-yellow-900 tracking-wider">NEEDS ATTENTION<br/>ASSETS</p>
                        <p class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-yellow-600 to-amber-700"><?php echo $needsAttention; ?></p>
                    </div>

                    <!-- CRITICAL ASSETS -->
                    <div class="stat-card card-hover bg-gradient-to-br from-red-100 to-rose-50 rounded-xl shadow-lg border border-red-300 p-4 flex flex-col justify-center items-center relative" style="animation-delay: 0.9s;">
                        <div class="absolute top-2 right-2">
                            <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <p class="text-[9px] font-bold uppercase text-center mb-2 text-red-900 tracking-wider">CRITICAL ASSETS</p>
                        <p class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-red-600 to-rose-700"><?php echo $assetsCritical; ?></p>
                    </div>

                    <!-- ASSET NUMBER STATUS -->
                    <div class="stat-card card-hover bg-gradient-to-br from-cyan-100 to-teal-50 rounded-xl shadow-lg border border-cyan-300 p-4 flex flex-col justify-center items-center relative" style="animation-delay: 1s;">
                        <div class="absolute top-2 right-2">
                            <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                            </svg>
                        </div>
                        <p class="text-[9px] font-bold uppercase text-center mb-2 text-cyan-900 tracking-wider">ASSET NUMBER STATUS</p>
                        <p class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-cyan-600 to-teal-700"><?php echo $totalAssets; ?></p>
                    </div>
                </div>

                <!-- Column 7: Network/Building sections -->
                <div class="col-span-1 grid grid-rows-5 gap-3">
                    <!-- BUILDING NETWORK GRAPH (2 rows) -->
                    <div class="row-span-2 stat-card card-hover bg-white rounded-xl shadow-xl border border-gray-200 p-4 flex flex-col justify-center items-center" style="animation-delay: 1.1s;">
                        <div class="mb-2">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <p class="text-[10px] font-bold uppercase mb-3 text-center text-gray-800 tracking-wider">NETWORK GRAPH</p>
                        <div class="w-full h-16 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg p-2">
                            <canvas id="networkChart"></canvas>
                        </div>
                    </div>

                    <!-- BUILDING NETWORK GRAPH -->
                    <div class="stat-card card-hover bg-gradient-to-br from-emerald-100 to-green-50 rounded-lg shadow-md border border-emerald-200 p-2 flex items-center justify-center" style="animation-delay: 1.2s;">
                        <div class="text-center">
                            <div class="w-6 h-6 mx-auto mb-1 bg-emerald-500 rounded-full flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 7H7v6h6V7z"></path>
                                </svg>
                            </div>
                            <p class="text-[8px] font-bold uppercase text-emerald-900">NETWORK</p>
                        </div>
                    </div>

                    <!-- BUILDING PING -->
                    <div class="stat-card card-hover bg-gradient-to-br from-sky-100 to-blue-50 rounded-lg shadow-md border border-sky-200 p-2 flex items-center justify-center" style="animation-delay: 1.3s;">
                        <div class="text-center">
                            <div class="flex items-center justify-center space-x-1 mb-1">
                                <div class="w-1 h-2 bg-sky-500 rounded"></div>
                                <div class="w-1 h-3 bg-sky-500 rounded"></div>
                                <div class="w-1 h-4 bg-sky-500 rounded"></div>
                            </div>
                            <p class="text-[8px] font-bold uppercase text-sky-900">PING: 23ms</p>
                        </div>
                    </div>

                    <!-- BUILDING PING -->
                    <div class="stat-card card-hover bg-gradient-to-br from-violet-100 to-purple-50 rounded-lg shadow-md border border-violet-200 p-2 flex items-center justify-center" style="animation-delay: 1.4s;">
                        <div class="text-center">
                            <div class="flex items-center justify-center space-x-1 mb-1">
                                <div class="w-1 h-2 bg-violet-500 rounded"></div>
                                <div class="w-1 h-3 bg-violet-500 rounded"></div>
                                <div class="w-1 h-4 bg-violet-500 rounded"></div>
                            </div>
                            <p class="text-[8px] font-bold uppercase text-violet-900">PING: 18ms</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Chart.js Library -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        
        <script>
        // Real-time Dashboard Updates
        let refreshInterval = null;
        const REFRESH_RATE = 5000; // 5 seconds (faster updates)

        // Function to fetch and update dashboard metrics
        async function updateDashboardMetrics() {
            try {
                const response = await fetch('../../controller/get_dashboard_metrics.php', {
                    method: 'GET',
                    credentials: 'include'
                });
                
                if (!response.ok) throw new Error('Failed to fetch metrics');
                
                const data = await response.json();
                
                if (data.success) {
                    // Update metric values with smooth transitions
                    updateMetricValue('unassignedIssues', data.unassignedIssues);
                    updateMetricValue('inProgressIssues', data.inProgressIssues);
                    updateMetricValue('resolvedIssues', data.resolvedIssues);
                    updateMetricValue('healthyAssets', data.healthyAssets);
                    updateMetricValue('assetsNearEOL', data.assetsNearEOL);
                    updateMetricValue('assetsBorrowed', data.assetsBorrowed);
                    updateMetricValue('assetsAvailable', data.assetsAvailable);
                    updateMetricValue('assetsInUse', data.assetsInUse);
                    updateMetricValue('needsAttention', data.needsAttention);
                    updateMetricValue('assetsCritical', data.assetsCritical);
                    
                    // Update activity logs if changed
                    if (data.recent_logs) {
                        updateActivityLogs(data.recent_logs);
                    }
                }
            } catch (error) {
                console.error('Error updating dashboard:', error);
            }
        }

        // Smooth counter animation
        function updateMetricValue(elementId, newValue) {
            const elements = document.querySelectorAll(`[data-metric="${elementId}"]`);
            elements.forEach(element => {
                const currentValue = parseInt(element.textContent) || 0;
                if (currentValue !== newValue) {
                    const difference = newValue - currentValue;
                    
                    // Show increment/decrement indicator
                    const card = element.closest('.metric-card');
                    if (card && difference !== 0) {
                        const indicator = document.createElement('div');
                        indicator.className = difference > 0 ? 'metric-increment' : 'metric-decrement';
                        indicator.textContent = difference > 0 ? `+${difference}` : `${difference}`;
                        card.appendChild(indicator);
                        
                        // Remove indicator after animation
                        setTimeout(() => indicator.remove(), 5000);
                    }
                    
                    animateValue(element, currentValue, newValue, 1000);
                    // Add pulse effect
                    element.classList.add('animate-pulse');
                    setTimeout(() => element.classList.remove('animate-pulse'), 1000);
                }
            });
        }

        // Counter animation function
        function animateValue(element, start, end, duration) {
            const range = end - start;
            const increment = range / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                    element.textContent = end;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.round(current);
                }
            }, 16);
        }

        // Update activity logs
        function updateActivityLogs(logs) {
            const container = document.getElementById('activityLogsContainer');
            if (!container) return;
            
            const currentHTML = container.innerHTML;
            const newHTML = generateActivityLogsHTML(logs);
            
            if (currentHTML !== newHTML) {
                container.innerHTML = newHTML;
                container.classList.add('animate-pulse');
                setTimeout(() => container.classList.remove('animate-pulse'), 500);
            }
        }

        // Generate activity logs HTML
        function generateActivityLogsHTML(logs) {
            if (!logs || logs.length === 0) {
                return '<p class="text-xs text-gray-500 text-center py-4">No recent activity</p>';
            }
            
            return logs.map(log => {
                let badgeClass = 'bg-gray-100 text-gray-800';
                switch(log.action) {
                    case 'create': badgeClass = 'bg-green-100 text-green-800'; break;
                    case 'update': badgeClass = 'bg-blue-100 text-blue-800'; break;
                    case 'delete': badgeClass = 'bg-red-100 text-red-800'; break;
                    case 'scan': badgeClass = 'bg-purple-100 text-purple-800'; break;
                    case 'assign': badgeClass = 'bg-yellow-100 text-yellow-800'; break;
                    case 'view': badgeClass = 'bg-indigo-100 text-indigo-800'; break;
                    case 'export': badgeClass = 'bg-cyan-100 text-cyan-800'; break;
                }
                
                const timeAgo = formatTimeAgo(log.created_at);
                
                return `
                    <div class="bg-gray-50 rounded p-2 border border-gray-200 hover:bg-gray-100 transition-colors">
                        <div class="flex items-start justify-between mb-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-medium ${badgeClass}">
                                ${log.action.toUpperCase()}
                            </span>
                            <span class="text-[9px] text-gray-500">${timeAgo}</span>
                        </div>
                        <p class="text-[10px] text-gray-700 font-medium mb-1">
                            ${log.entity_type}
                            ${log.entity_id ? '<span class="text-gray-500">#' + log.entity_id + '</span>' : ''}
                        </p>
                        <p class="text-[9px] text-gray-600 truncate">${log.description}</p>
                        <p class="text-[9px] text-gray-500 mt-1">by ${log.performed_by}</p>
                    </div>
                `;
            }).join('');
        }

        // Format time ago
        function formatTimeAgo(timestamp) {
            const now = Date.now();
            const time = new Date(timestamp).getTime();
            const diff = Math.abs(Math.floor((now - time) / 1000));
            
            if (diff < 60) return diff + 's ago';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
            return Math.floor(diff / 86400) + 'd ago';
        }

        // Start/stop real-time updates
        function startRealTimeUpdates() {
            if (!refreshInterval) {
                refreshInterval = setInterval(updateDashboardMetrics, REFRESH_RATE);
                console.log('Real-time updates started (every 5s)');
            }
        }

        function stopRealTimeUpdates() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
                refreshInterval = null;
                console.log('Real-time updates stopped');
            }
        }

        // Auto-start updates when page loads
        document.addEventListener('DOMContentLoaded', () => {
            startRealTimeUpdates();
            
            // Stop updates when page is hidden (save resources)
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    stopRealTimeUpdates();
                } else {
                    startRealTimeUpdates();
                    updateDashboardMetrics(); // Immediate update when page becomes visible
                }
            });
        });

        // Chart.js configuration
        Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
        Chart.defaults.color = '#9CA3AF';

        // Trending Assets Chart
        const trendingCtx = document.getElementById('trendingChart');
        if (trendingCtx) {
            new Chart(trendingCtx, {
                type: 'line',
                data: {
                    labels: ['Nov'],
                    datasets: [{
                        data: [<?php echo $unassignedIssues; ?>],
                        borderColor: '#8b5cf6',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#8b5cf6',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            padding: 12,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f3f4f6', drawBorder: false },
                            ticks: { font: { size: 11 }, color: '#9CA3AF' }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { font: { size: 11 }, color: '#9CA3AF' }
                        }
                    }
                }
            });
        }

        // Asset Lifecycle Chart
        const lifecycleCtx = document.getElementById('lifecycleChart');
        if (lifecycleCtx) {
            new Chart(lifecycleCtx, {
                type: 'line',
                data: {
                    labels: ['New', 'Active', 'Aging', 'EOL'],
                    datasets: [{
                        data: [<?php echo $assetsAvailable; ?>, <?php echo $assetsInUse; ?>, <?php echo $needsAttention; ?>, <?php echo $assetsCritical; ?>],
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.05)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f3f4f6', drawBorder: false },
                            ticks: { font: { size: 11 }, color: '#9CA3AF' }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { font: { size: 11 }, color: '#9CA3AF' }
                        }
                    }
                }
            });
        }

        // Failure Risk Forecast Chart
        const failureRiskCtx = document.getElementById('failureRiskChart');
        if (failureRiskCtx) {
            new Chart(failureRiskCtx, {
                type: 'bar',
                data: {
                    labels: ['Critical', 'High', 'Medium', 'Low'],
                    datasets: [{
                        data: [<?php echo $assetsCritical; ?>, 0, 0, <?php echo $assetsAvailable; ?>],
                        backgroundColor: ['#8b5cf6', '#a78bfa', '#c4b5fd', '#ddd6fe'],
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f3f4f6', drawBorder: false },
                            ticks: { font: { size: 11 }, color: '#9CA3AF' }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { font: { size: 11 }, color: '#9CA3AF' }
                        }
                    }
                }
            });
        }

        // Active Users Chart
        const activeUsersCtx = document.getElementById('activeUsersChart');
        if (activeUsersCtx) {
            new Chart(activeUsersCtx, {
                type: 'line',
                data: {
                    labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'],
                    datasets: [
                        {
                            label: 'uptime',
                            data: [30, 32, 35, 38, 40, 42],
                            borderColor: '#c4b5fd',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#c4b5fd',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'users',
                            data: [28, 30, 33, 36, 38, 40],
                            borderColor: '#8b5cf6',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#8b5cf6',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                font: { size: 11 },
                                color: '#9CA3AF',
                                padding: 15
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f3f4f6', drawBorder: false },
                            ticks: { font: { size: 11 }, color: '#9CA3AF' }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { font: { size: 11 }, color: '#9CA3AF' }
                        }
                    }
                }
            });
        }

        // Building Network Charts
        // BELMONTE Network Chart
        const belmonteNetworkCtx = document.getElementById('belmonteNetworkChart');
        if (belmonteNetworkCtx) {
            new Chart(belmonteNetworkCtx, {
                type: 'line',
                data: {
                    labels: ['10s', '8s', '6s', '4s', '2s', '0s'],
                    datasets: [{
                        data: [45, 52, 48, 50, 47, 49],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { display: false },
                        x: { display: false }
                    }
                }
            });
        }

        // ACADEMIC Network Chart
        const academicNetworkCtx = document.getElementById('academicNetworkChart');
        if (academicNetworkCtx) {
            new Chart(academicNetworkCtx, {
                type: 'line',
                data: {
                    labels: ['10s', '8s', '6s', '4s', '2s', '0s'],
                    datasets: [{
                        data: [38, 42, 45, 48, 44, 46],
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { display: false },
                        x: { display: false }
                    }
                }
            });
        }

        // KORPHIL Network Chart
        const korphilNetworkCtx = document.getElementById('korphilNetworkChart');
        if (korphilNetworkCtx) {
            new Chart(korphilNetworkCtx, {
                type: 'line',
                data: {
                    labels: ['10s', '8s', '6s', '4s', '2s', '0s'],
                    datasets: [{
                        data: [50, 48, 52, 49, 51, 53],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { display: false },
                        x: { display: false }
                    }
                }
            });
        }

        // LAB BUILDING Network Chart
        const labBuildingNetworkCtx = document.getElementById('labBuildingNetworkChart');
        if (labBuildingNetworkCtx) {
            new Chart(labBuildingNetworkCtx, {
                type: 'line',
                data: {
                    labels: ['10s', '8s', '6s', '4s', '2s', '0s'],
                    datasets: [{
                        data: [47, 49, 46, 48, 50, 52],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { display: false },
                        x: { display: false }
                    }
                }
            });
        }
        </script>

<?php include '../components/layout_footer.php'; ?>