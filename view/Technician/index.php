<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has technician role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Technician') {
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

$technician_id = $_SESSION['user_id'];
$technician_name = $_SESSION['full_name'] ?? '';

// DEBUG: Check technician info
echo "<!-- DEBUG: Technician ID: " . $technician_id . " -->";
echo "<!-- DEBUG: Technician Name: " . $technician_name . " -->";
$debugQuery = $conn->query("SELECT COUNT(*) as total FROM issues");
$debugResult = $debugQuery ? $debugQuery->fetch_assoc() : null;
echo "<!-- DEBUG: Total issues in database: " . ($debugResult['total'] ?? 'Query failed') . " -->";
$debugAssigned = $conn->query("SELECT COUNT(*) as count FROM issues WHERE assigned_technician = '" . $conn->real_escape_string($technician_name) . "'");
echo "<!-- DEBUG: Issues assigned to " . $technician_name . ": " . ($debugAssigned ? $debugAssigned->fetch_assoc()['count'] : 'Query failed') . " -->";

// ============================================
// ASSIGNED ISSUES METRICS
// ============================================
// Total issues assigned to technician
$totalIssuesResult = $conn->query("
    SELECT COUNT(*) as count 
    FROM issues 
    WHERE assigned_technician = '" . $conn->real_escape_string($technician_name) . "'
");
$totalAssignedIssues = $totalIssuesResult ? ($totalIssuesResult->fetch_assoc()['count'] ?? 0) : 0;

// Open (unstarted) issues — DB uses 'Open', not 'Pending'
$pendingIssuesResult = $conn->query("
    SELECT COUNT(*) as count 
    FROM issues 
    WHERE assigned_technician = '" . $conn->real_escape_string($technician_name) . "' AND status = 'Open'
");
$pendingIssues = $pendingIssuesResult ? ($pendingIssuesResult->fetch_assoc()['count'] ?? 0) : 0;

// In Progress issues
$inProgressIssuesResult = $conn->query("
    SELECT COUNT(*) as count 
    FROM issues 
    WHERE assigned_technician = '" . $conn->real_escape_string($technician_name) . "' AND status = 'In Progress'
");
$inProgressIssues = $inProgressIssuesResult ? ($inProgressIssuesResult->fetch_assoc()['count'] ?? 0) : 0;

// Resolved issues
$resolvedIssuesResult = $conn->query("
    SELECT COUNT(*) as count 
    FROM issues 
    WHERE assigned_technician = '" . $conn->real_escape_string($technician_name) . "' AND status IN ('Resolved', 'Closed')
");
$resolvedIssues = $resolvedIssuesResult ? ($resolvedIssuesResult->fetch_assoc()['count'] ?? 0) : 0;

// Issues by priority (active only — exclude Resolved & Closed)
$highPriorityResult = $conn->query("
    SELECT COUNT(*) as count 
    FROM issues 
    WHERE assigned_technician = '" . $conn->real_escape_string($technician_name) . "' AND priority = 'High' AND status NOT IN ('Resolved', 'Closed')
");
$highPriorityIssues = $highPriorityResult ? ($highPriorityResult->fetch_assoc()['count'] ?? 0) : 0;

$mediumPriorityResult = $conn->query("
    SELECT COUNT(*) as count 
    FROM issues 
    WHERE assigned_technician = '" . $conn->real_escape_string($technician_name) . "' AND priority = 'Medium' AND status NOT IN ('Resolved', 'Closed')
");
$mediumPriorityIssues = $mediumPriorityResult ? ($mediumPriorityResult->fetch_assoc()['count'] ?? 0) : 0;

$lowPriorityResult = $conn->query("
    SELECT COUNT(*) as count 
    FROM issues 
    WHERE assigned_technician = '" . $conn->real_escape_string($technician_name) . "' AND priority = 'Low' AND status NOT IN ('Resolved', 'Closed')
");
$lowPriorityIssues = $lowPriorityResult ? ($lowPriorityResult->fetch_assoc()['count'] ?? 0) : 0;

// ============================================
// MAINTENANCE METRICS
// ============================================
// Assets under maintenance in technician's assigned buildings
$maintenanceAssetsResult = $conn->query("
    SELECT COUNT(DISTINCT a.id) as count 
    FROM assets a
    INNER JOIN rooms r ON a.room_id = r.id
    INNER JOIN building_technicians bt ON r.building_id = bt.building_id
    WHERE a.status = 'Under Maintenance' AND bt.technician_id = " . intval($technician_id) . "
");
$maintenanceAssets = $maintenanceAssetsResult ? ($maintenanceAssetsResult->fetch_assoc()['count'] ?? 0) : 0;

// Scheduled maintenance for technician's assigned buildings
$scheduledMaintenanceResult = $conn->query("
    SELECT COUNT(*) as count 
    FROM maintenance_schedules ms
    INNER JOIN building_technicians bt ON ms.building_id = bt.building_id
    WHERE ms.status = 'Scheduled' AND ms.maintenance_date >= CURDATE() AND bt.technician_id = " . intval($technician_id) . "
");
$scheduledMaintenance = $scheduledMaintenanceResult ? ($scheduledMaintenanceResult->fetch_assoc()['count'] ?? 0) : 0;

// Overdue maintenance for technician's assigned buildings
$overdueMaintenanceResult = $conn->query("
    SELECT COUNT(*) as count 
    FROM maintenance_schedules ms
    INNER JOIN building_technicians bt ON ms.building_id = bt.building_id
    WHERE ms.status = 'Scheduled' AND ms.maintenance_date < CURDATE() AND bt.technician_id = " . intval($technician_id) . "
");
$overdueMaintenance = $overdueMaintenanceResult ? ($overdueMaintenanceResult->fetch_assoc()['count'] ?? 0) : 0;

// Completed maintenance this month for technician's assigned buildings
$completedMaintenanceResult = $conn->query("
    SELECT COUNT(*) as count 
    FROM maintenance_schedules ms
    INNER JOIN building_technicians bt ON ms.building_id = bt.building_id
    WHERE ms.status = 'Completed' AND MONTH(ms.updated_at) = MONTH(CURDATE()) AND YEAR(ms.updated_at) = YEAR(CURDATE()) AND bt.technician_id = " . intval($technician_id) . "
");
$completedMaintenance = $completedMaintenanceResult ? ($completedMaintenanceResult->fetch_assoc()['count'] ?? 0) : 0;

// ============================================
// PERFORMANCE METRICS
// ============================================
// Average resolution time (in hours)
$avgResolutionResult = $conn->query("
    SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours 
    FROM issues 
    WHERE assigned_technician = '" . $conn->real_escape_string($technician_name) . "' AND status = 'Resolved'
");
$avgResolutionTime = $avgResolutionResult ? ($avgResolutionResult->fetch_assoc()['avg_hours'] ?? 0) : 0;

// Today's activity count
$todayActivityResult = $conn->query("
    SELECT COUNT(*) as count 
    FROM activity_logs 
    WHERE user_id = " . intval($technician_id) . " AND DATE(created_at) = CURDATE()
");
$todayActivity = $todayActivityResult ? ($todayActivityResult->fetch_assoc()['count'] ?? 0) : 0;

// This week's resolved issues
$weekResolvedResult = $conn->query("
    SELECT COUNT(*) as count 
    FROM issues 
    WHERE assigned_technician = '" . $conn->real_escape_string($technician_name) . "' 
    AND status = 'Resolved' 
    AND WEEK(updated_at) = WEEK(CURDATE()) 
    AND YEAR(updated_at) = YEAR(CURDATE())
");
$weekResolved = $weekResolvedResult ? ($weekResolvedResult->fetch_assoc()['count'] ?? 0) : 0;

// ============================================
// ISSUES BY TYPE
// ============================================
$issuesByTypeResult = $conn->query("
    SELECT category, COUNT(*) as count 
    FROM issues 
    WHERE assigned_technician = '" . $conn->real_escape_string($technician_name) . "' AND status NOT IN ('Resolved', 'Closed')
    GROUP BY category
    ORDER BY count DESC
");
$issueTypes = [];
$issueTypeCounts = [];
if ($issuesByTypeResult && $issuesByTypeResult->num_rows > 0) {
    while ($row = $issuesByTypeResult->fetch_assoc()) {
        $issueTypes[] = $row['category'];
        $issueTypeCounts[] = $row['count'];
    }
}
if (empty($issueTypes)) {
    $issueTypes = ['No Active Issues'];
    $issueTypeCounts = [0];
}

// ============================================
// MONTHLY TRENDS (Last 6 months)
// ============================================
$monthlyIssuesResult = $conn->query("
    SELECT DATE_FORMAT(created_at, '%b') as month, COUNT(*) as count 
    FROM issues 
    WHERE assigned_technician = '" . $conn->real_escape_string($technician_name) . "' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
");
$issueMonths = [];
$issueCounts = [];
if ($monthlyIssuesResult && $monthlyIssuesResult->num_rows > 0) {
    while ($row = $monthlyIssuesResult->fetch_assoc()) {
        $issueMonths[] = $row['month'];
        $issueCounts[] = $row['count'];
    }
}
if (empty($issueMonths)) {
    $issueMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $issueCounts = [0, 0, 0, 0, 0, 0];
}

// Resolved issues trend
$monthlyResolvedResult = $conn->query("
    SELECT 
        DATE_FORMAT(updated_at, '%b') as month,
        AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_resolution_hours
    FROM issues 
    WHERE assigned_technician = '" . $conn->real_escape_string($technician_name) . "' 
    AND status = 'Resolved'
    AND updated_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
    ORDER BY updated_at ASC
");
$resolvedMonths = [];
$resolvedAvgHours = [];
if ($monthlyResolvedResult && $monthlyResolvedResult->num_rows > 0) {
    while ($row = $monthlyResolvedResult->fetch_assoc()) {
        $resolvedMonths[] = $row['month'];
        $resolvedAvgHours[] = round($row['avg_resolution_hours'] ?? 0, 1);
    }
}
if (empty($resolvedMonths)) {
    $resolvedMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $resolvedAvgHours = [0, 0, 0, 0, 0, 0];
}

// ============================================
// RECENT ISSUES (Last 10)
// ============================================
$recentIssuesResult = $conn->query("
    SELECT 
        i.id,
        i.category,
        i.priority,
        i.status,
        i.description,
        i.created_at,
        i.title,
        a.asset_tag,
        a.asset_name,
        u.full_name as reporter_name
    FROM issues i
    LEFT JOIN assets a ON i.component_asset_id = a.id
    LEFT JOIN users u ON i.user_id = u.id
    WHERE i.assigned_technician = '" . $conn->real_escape_string($technician_name) . "'
    ORDER BY 
        CASE 
            WHEN i.status = 'Open' THEN 1
            WHEN i.status = 'In Progress' THEN 2
            WHEN i.status = 'Resolved' THEN 3
            ELSE 4
        END,
        CASE 
            WHEN i.priority = 'High' THEN 1
            WHEN i.priority = 'Medium' THEN 2
            ELSE 3
        END,
        i.created_at DESC
    LIMIT 10
");
$recentIssues = [];
if ($recentIssuesResult && $recentIssuesResult->num_rows > 0) {
    while ($row = $recentIssuesResult->fetch_assoc()) {
        $recentIssues[] = $row;
    }
}

// ============================================
// UPCOMING MAINTENANCE (Next 7 days)
// ============================================
$upcomingMaintenanceQuery = "
    SELECT 
        ms.id,
        ms.maintenance_date,
        ms.maintenance_type,
        ms.notes as description,
        r.name as room_name,
        b.name as building_name
    FROM maintenance_schedules ms
    INNER JOIN building_technicians bt ON ms.building_id = bt.building_id
    LEFT JOIN rooms r ON ms.room_id = r.id
    LEFT JOIN buildings b ON ms.building_id = b.id
    WHERE bt.technician_id = " . intval($technician_id) . "
    AND ms.status = 'Scheduled' 
    AND ms.maintenance_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY ms.maintenance_date ASC
    LIMIT 5
";
echo "<!-- DEBUG Upcoming Maintenance Query: " . htmlspecialchars($upcomingMaintenanceQuery) . " -->";
$upcomingMaintenanceResult = $conn->query($upcomingMaintenanceQuery);
echo "<!-- DEBUG Upcoming Maintenance Result: " . ($upcomingMaintenanceResult ? "Success, Rows: " . $upcomingMaintenanceResult->num_rows : "Failed: " . $conn->error) . " -->";

// Debug: Check all maintenance schedules for this technician
$debugAllMaintenance = $conn->query("SELECT COUNT(*) as total FROM maintenance_schedules ms INNER JOIN building_technicians bt ON ms.building_id = bt.building_id WHERE bt.technician_id = " . intval($technician_id));
$debugTotal = $debugAllMaintenance ? $debugAllMaintenance->fetch_assoc()['total'] : 0;
echo "<!-- DEBUG Total maintenance schedules for technician $technician_id: $debugTotal -->";

// Debug: Check scheduled maintenance
$debugScheduled = $conn->query("SELECT COUNT(*) as total FROM maintenance_schedules ms INNER JOIN building_technicians bt ON ms.building_id = bt.building_id WHERE bt.technician_id = " . intval($technician_id) . " AND ms.status = 'Scheduled'");
$debugScheduledTotal = $debugScheduled ? $debugScheduled->fetch_assoc()['total'] : 0;
echo "<!-- DEBUG Scheduled maintenance for technician $technician_id: $debugScheduledTotal -->";

$upcomingMaintenance = [];
if ($upcomingMaintenanceResult && $upcomingMaintenanceResult->num_rows > 0) {
    while ($row = $upcomingMaintenanceResult->fetch_assoc()) {
        echo "<!-- DEBUG Upcoming Maintenance Row: " . htmlspecialchars(json_encode($row)) . " -->";
        $upcomingMaintenance[] = $row;
    }
}

include '../components/layout_header.php';
?>

<style>
    body, html { overflow: hidden !important; height: 100vh; }
    main { height: calc(100vh - 85px); }
    .metric-card {
        display: block;
        text-decoration: none;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        cursor: pointer;
    }
    .metric-card:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 24px rgba(30, 58, 138, 0.25), 0 0 0 2px rgba(30, 58, 138, 0.15);
        text-decoration: none;
    }
</style>

<!-- Main Content -->
<main class="p-2 bg-gray-50 overflow-hidden flex flex-col" style="height: calc(100vh - 85px);">
    
    <!-- Key Metrics Row -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 mb-2 flex-shrink-0">
        <!-- Total Assigned Issues -->
        <a href="issue_details.php?filter=All" class="metric-card bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-ticket text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Total</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">Assigned Issues</p>
            <p class="text-lg font-bold"><?php echo $totalAssignedIssues; ?></p>
        </a>

        <!-- Open Issues -->
        <a href="issue_details.php?status=Open" class="metric-card bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-clock text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Open</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">Awaiting Action</p>
            <p class="text-lg font-bold"><?php echo $pendingIssues; ?></p>
        </a>

        <!-- In Progress -->
        <a href="issue_details.php?status=In+Progress" class="metric-card bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-wrench text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Active</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">In Progress</p>
            <p class="text-lg font-bold"><?php echo $inProgressIssues; ?></p>
        </a>

        <!-- Resolved Issues -->
        <a href="issue_details.php?status=Resolved" class="metric-card bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-check-circle text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Completed</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">Resolved</p>
            <p class="text-lg font-bold"><?php echo $resolvedIssues; ?></p>
        </a>

        <!-- High Priority -->
        <a href="issue_details.php?priority=High" class="metric-card bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-exclamation-triangle text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Urgent</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">High Priority</p>
            <p class="text-lg font-bold"><?php echo $highPriorityIssues; ?></p>
        </a>

        <!-- Under Maintenance -->
        <a href="maintenance.php" class="metric-card bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-tools text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Maintenance</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">Assets</p>
            <p class="text-lg font-bold"><?php echo $maintenanceAssets; ?></p>
        </a>
    </div>

    <!-- Secondary Metrics Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-1.5 mb-2 flex-shrink-0">
        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Scheduled Maintenance</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $scheduledMaintenance; ?></p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Overdue Tasks</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $overdueMaintenance; ?></p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Completed (Month)</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $completedMaintenance; ?></p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Week Resolved</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $weekResolved; ?></p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Avg Resolution</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo round($avgResolutionTime, 1); ?> hours</p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Medium Priority</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $mediumPriorityIssues; ?></p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Low Priority</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $lowPriorityIssues; ?></p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Today's Activity</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $todayActivity; ?></p>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="flex-1 min-h-0 overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 h-full">
            
            <!-- Left Column: Charts -->
            <div class="lg:col-span-2 flex flex-col gap-2 overflow-hidden">
                <!-- Charts Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 flex-1">
                    <!-- Issue Status Distribution -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Issue Status</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Current workload breakdown</p>
                        <div style="height: calc(100% - 2.5rem);">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>

                    <!-- Issue Types -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Issue Types</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Active issues by category</p>
                        <div style="height: calc(100% - 2.5rem);">
                            <canvas id="typeChart"></canvas>
                        </div>
                    </div>

                    <!-- Priority Distribution -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Priority Levels</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Active issues by priority</p>
                        <div style="height: calc(100% - 2.5rem);">
                            <canvas id="priorityChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Trends Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 flex-1">
                    <!-- Issues Trend -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Issues Trend</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Last 6 months assigned issues</p>
                        <div style="height: calc(100% - 2.5rem);">
                            <canvas id="issuesTrendChart"></canvas>
                        </div>
                    </div>

                    <!-- Resolution Performance -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Resolution Performance</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Avg hours to resolve (faster is better)</p>
                        <div style="height: calc(100% - 2.5rem);">
                            <canvas id="resolvedTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Recent Activity -->
            <div class="flex flex-col gap-2 overflow-hidden">
                <!-- Recent Issues -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 flex-1 overflow-hidden flex flex-col">
                    <h3 class="text-xs font-semibold text-gray-900 mb-1">Recent Issues</h3>
                    <div class="flex-1 overflow-y-auto space-y-1.5">
                        <?php if (empty($recentIssues)): ?>
                            <div class="flex items-center justify-center h-full">
                                <p class="text-xs text-gray-500 text-center">No issues assigned yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentIssues as $issue): ?>
                                <div class="border border-gray-200 rounded p-2 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start justify-between gap-2 mb-1">
                                        <span class="text-[10px] font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($issue['asset_tag'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($issue['asset_name'] ?? 'Unknown'); ?>
                                        </span>
                                        <?php 
                                        $statusColors = [
                                            'Pending' => 'bg-amber-100 text-amber-700',
                                            'In Progress' => 'bg-blue-100 text-blue-700',
                                            'Resolved' => 'bg-green-100 text-green-700'
                                        ];
                                        $statusColor = $statusColors[$issue['status']] ?? 'bg-gray-100 text-gray-700';
                                        ?>
                                        <span class="text-[9px] px-1.5 py-0.5 rounded <?php echo $statusColor; ?> whitespace-nowrap">
                                            <?php echo htmlspecialchars($issue['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-[9px] text-gray-600 mb-1">
                                        <?php echo htmlspecialchars(substr($issue['description'], 0, 80)) . (strlen($issue['description']) > 80 ? '...' : ''); ?>
                                    </p>
                                    <div class="flex items-center justify-between text-[9px]">
                                        <span class="text-gray-500">
                                            <i class="fas fa-tag mr-0.5"></i>
                                            <?php echo htmlspecialchars($issue['category']); ?>
                                        </span>
                                        <?php 
                                        $priorityColors = [
                                            'High' => 'text-red-600',
                                            'Medium' => 'text-amber-600',
                                            'Low' => 'text-gray-500'
                                        ];
                                        $priorityColor = $priorityColors[$issue['priority']] ?? 'text-gray-500';
                                        ?>
                                        <span class="<?php echo $priorityColor; ?> font-semibold">
                                            <i class="fas fa-exclamation-circle mr-0.5"></i>
                                            <?php echo htmlspecialchars($issue['priority']); ?>
                                        </span>
                                    </div>
                                    <div class="text-[9px] text-gray-400 mt-1">
                                        <i class="fas fa-clock mr-0.5"></i>
                                        <?php echo date('M j, Y g:i A', strtotime($issue['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Maintenance -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 flex-1 overflow-hidden flex flex-col">
                    <h3 class="text-xs font-semibold text-gray-900 mb-1">Upcoming Maintenance</h3>
                    <div class="flex-1 overflow-y-auto space-y-1.5">
                        <?php if (empty($upcomingMaintenance)): ?>
                            <div class="flex items-center justify-center h-full">
                                <p class="text-xs text-gray-500 text-center">No upcoming maintenance</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($upcomingMaintenance as $maintenance): ?>
                                <div class="border border-gray-200 rounded p-2 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start justify-between gap-2 mb-1">
                                        <span class="text-[10px] font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($maintenance['building_name'] ?? 'Unknown Building'); ?>
                                        </span>
                                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 whitespace-nowrap">
                                            <?php echo htmlspecialchars($maintenance['maintenance_type']); ?>
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-gray-700 font-medium mb-1">
                                        <?php echo htmlspecialchars($maintenance['room_name'] ?? 'Unknown Room'); ?>
                                    </p>
                                    <p class="text-[9px] text-gray-600 mb-1">
                                        <?php echo htmlspecialchars($maintenance['description'] ?? 'No description'); ?>
                                    </p>
                                    <div class="text-[9px] text-gray-500">
                                        <i class="fas fa-calendar mr-0.5"></i>
                                        <?php echo date('M j, Y', strtotime($maintenance['maintenance_date'])); ?>
                                        <?php 
                                        $daysUntil = ceil((strtotime($maintenance['maintenance_date']) - time()) / 86400);
                                        if ($daysUntil == 0) {
                                            echo '<span class="ml-1 text-red-600 font-semibold">(Today)</span>';
                                        } elseif ($daysUntil == 1) {
                                            echo '<span class="ml-1 text-amber-600 font-semibold">(Tomorrow)</span>';
                                        } else {
                                            echo '<span class="ml-1 text-blue-600">(' . $daysUntil . ' days)</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Chart.js configuration
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#6B7280';
Chart.defaults.plugins.legend.display = true;
Chart.defaults.plugins.legend.position = 'bottom';

const chartColors = {
    blue: '#1E3A8A',
    purple: '#8B5CF6',
    green: '#10B981',
    orange: '#F97316',
    red: '#EF4444',
    amber: '#F59E0B',
    indigo: '#3b82f6',
    gray: '#6B7280'
};

// 1. Issue Status Distribution
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Open', 'In Progress', 'Resolved'],
        datasets: [{
            data: [
                <?php echo $pendingIssues; ?>,
                <?php echo $inProgressIssues; ?>,
                <?php echo $resolvedIssues; ?>
            ],
            backgroundColor: [
                'rgba(30, 58, 138, 0.6)',
                'rgba(30, 58, 138, 0.8)',
                'rgba(30, 58, 138, 1)'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        onHover: function(event, elements) {
            event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
        },
        onClick: function(event, elements) {
            if (elements.length > 0) {
                const index = elements[0].index;
                const statusMap = ['Open', 'In Progress', 'Resolved'];
                const status = statusMap[index];
                window.location.href = 'issue_details.php?status=' + encodeURIComponent(status);
            }
        },
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { size: 10 }, padding: 8 }
            },
            tooltip: {
                callbacks: {
                    footer: function() { return 'Click to view details'; }
                }
            }
        }
    }
});

// 2. Issue Types
const typeLabels = <?php echo json_encode($issueTypes); ?>;
const typeCtx = document.getElementById('typeChart').getContext('2d');
new Chart(typeCtx, {
    type: 'doughnut',
    data: {
        labels: typeLabels,
        datasets: [{
            data: <?php echo json_encode($issueTypeCounts); ?>,
            backgroundColor: [
                'rgba(30, 58, 138, 1)',
                'rgba(30, 58, 138, 0.8)',
                'rgba(30, 58, 138, 0.6)',
                'rgba(30, 58, 138, 0.4)',
                'rgba(30, 58, 138, 0.3)'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        onHover: function(event, elements) {
            event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
        },
        onClick: function(event, elements) {
            if (elements.length > 0) {
                const index = elements[0].index;
                const category = typeLabels[index];
                if (category && category !== 'No Active Issues') {
                    window.location.href = 'issue_details.php?category=' + encodeURIComponent(category);
                }
            }
        },
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { size: 10 }, padding: 8 }
            },
            tooltip: {
                callbacks: {
                    footer: function() { return 'Click to view details'; }
                }
            }
        }
    }
});

// 3. Priority Distribution
const priorityCtx = document.getElementById('priorityChart').getContext('2d');
new Chart(priorityCtx, {
    type: 'doughnut',
    data: {
        labels: ['High', 'Medium', 'Low'],
        datasets: [{
            data: [
                <?php echo $highPriorityIssues; ?>,
                <?php echo $mediumPriorityIssues; ?>,
                <?php echo $lowPriorityIssues; ?>
            ],
            backgroundColor: [
                'rgba(30, 58, 138, 1)',
                'rgba(30, 58, 138, 0.7)',
                'rgba(30, 58, 138, 0.4)'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        onHover: function(event, elements) {
            event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
        },
        onClick: function(event, elements) {
            if (elements.length > 0) {
                const index = elements[0].index;
                const priorityMap = ['High', 'Medium', 'Low'];
                const priority = priorityMap[index];
                window.location.href = 'issue_details.php?priority=' + encodeURIComponent(priority);
            }
        },
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { size: 10 }, padding: 8 }
            },
            tooltip: {
                callbacks: {
                    footer: function() { return 'Click to view details'; }
                }
            }
        }
    }
});

// 4. Issues Trend
const issuesTrendCtx = document.getElementById('issuesTrendChart').getContext('2d');
new Chart(issuesTrendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($issueMonths); ?>,
        datasets: [{
            label: 'Assigned Issues',
            data: <?php echo json_encode($issueCounts); ?>,
            borderColor: chartColors.blue,
            backgroundColor: 'rgba(30, 58, 138, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: chartColors.blue
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
                grid: { color: '#f3f4f6' },
                ticks: { font: { size: 10 } }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 10 } }
            }
        }
    }
});

// 5. Resolution Performance (Bar Chart)
const resolvedTrendCtx = document.getElementById('resolvedTrendChart').getContext('2d');
new Chart(resolvedTrendCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($resolvedMonths); ?>,
        datasets: [{
            label: 'Avg Hours to Resolve',
            data: <?php echo json_encode($resolvedAvgHours); ?>,
            backgroundColor: chartColors.blue,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Avg: ' + context.parsed.y + ' hours';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f3f4f6' },
                ticks: { 
                    font: { size: 10 },
                    callback: function(value) {
                        return value + 'h';
                    }
                },
                title: {
                    display: true,
                    text: 'Average Hours',
                    font: { size: 10, weight: 'bold' }
                }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 10 } }
            }
        }
    }
});
</script>

<?php include '../components/layout_footer.php'; ?>
