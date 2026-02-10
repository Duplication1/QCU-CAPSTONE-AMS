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
$lab_staff_id = $_SESSION['user_id'];

// ============================================
// TICKET METRICS
// ============================================
// Unassigned issues
$unassignedIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE (assigned_technician IS NULL OR assigned_technician = '') AND status = 'Open' AND category != 'borrow'")->fetch_assoc()['count'];

// In Progress issues
$inProgressIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'In Progress'")->fetch_assoc()['count'];

// Resolved issues
$resolvedIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'Resolved'")->fetch_assoc()['count'];

// Total issues
$totalIssues = $conn->query("SELECT COUNT(*) as count FROM issues")->fetch_assoc()['count'];

// ============================================
// ASSET METRICS
// ============================================
$assetsBorrowed = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Pending'")->fetch_assoc()['count'];
$assetsAvailable = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Available'")->fetch_assoc()['count'];
$assetsInUse = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'In Use'")->fetch_assoc()['count'];
$assetsCritical = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` IN ('Non-Functional', 'Poor')")->fetch_assoc()['count'];
$needsAttention = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Fair'")->fetch_assoc()['count'];
$healthyAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` IN ('Good', 'Excellent')")->fetch_assoc()['count'];
$totalAssets = $conn->query("SELECT COUNT(*) as count FROM assets")->fetch_assoc()['count'];

// ============================================
// PERFORMANCE METRICS
// ============================================
// Average assignment time (in hours) - how fast lab staff assigns tickets
$avgAssignmentResult = $conn->query("
    SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, assigned_at)) as avg_hours 
    FROM issues 
    WHERE assigned_at IS NOT NULL
");
$avgAssignmentTime = $avgAssignmentResult ? ($avgAssignmentResult->fetch_assoc()['avg_hours'] ?? 0) : 0;

// This week's assigned issues
$weekAssignedResult = $conn->query("
    SELECT COUNT(*) as count 
    FROM issues 
    WHERE assigned_at IS NOT NULL 
    AND WEEK(assigned_at) = WEEK(CURDATE()) 
    AND YEAR(assigned_at) = YEAR(CURDATE())
");
$weekAssigned = $weekAssignedResult ? ($weekAssignedResult->fetch_assoc()['count'] ?? 0) : 0;

// Today's activity count
$todayActivityResult = $conn->query("
    SELECT COUNT(*) as count 
    FROM activity_logs 
    WHERE user_id = " . intval($lab_staff_id) . " AND DATE(created_at) = CURDATE()
");
$todayActivity = $todayActivityResult ? ($todayActivityResult->fetch_assoc()['count'] ?? 0) : 0;

// ============================================
// ISSUES BY TYPE & STATUS
// ============================================
$issuesByTypeResult = $conn->query("
    SELECT issue_type, COUNT(*) as count 
    FROM issues 
    WHERE status != 'Resolved'
    GROUP BY issue_type
    ORDER BY count DESC
");
$issueTypes = [];
$issueTypeCounts = [];
if ($issuesByTypeResult && $issuesByTypeResult->num_rows > 0) {
    while ($row = $issuesByTypeResult->fetch_assoc()) {
        $issueTypes[] = $row['issue_type'];
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
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
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

// Assignment speed trend (avg hours per month)
$monthlyAssignmentResult = $conn->query("
    SELECT 
        DATE_FORMAT(assigned_at, '%b') as month,
        AVG(TIMESTAMPDIFF(HOUR, created_at, assigned_at)) as avg_assignment_hours
    FROM issues 
    WHERE assigned_at IS NOT NULL
    AND assigned_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(assigned_at, '%Y-%m')
    ORDER BY assigned_at ASC
");
$assignmentMonths = [];
$assignmentAvgHours = [];
if ($monthlyAssignmentResult && $monthlyAssignmentResult->num_rows > 0) {
    while ($row = $monthlyAssignmentResult->fetch_assoc()) {
        $assignmentMonths[] = $row['month'];
        $assignmentAvgHours[] = round($row['avg_assignment_hours'] ?? 0, 1);
    }
}
if (empty($assignmentMonths)) {
    $assignmentMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $assignmentAvgHours = [0, 0, 0, 0, 0, 0];
}

// ============================================
// RECENT ACTIVITY LOGS (Last 10)
// ============================================
$recent_logs_query = "SELECT al.action, al.entity_type, al.entity_id, al.description, 
                             COALESCE(u.full_name, 'System') as performed_by, al.created_at 
                      FROM activity_logs al
                      LEFT JOIN users u ON al.user_id = u.id
                      ORDER BY al.created_at DESC 
                      LIMIT 10";
$recent_logs_result = $conn->query($recent_logs_query);
$recent_logs = [];
if ($recent_logs_result && $recent_logs_result->num_rows > 0) {
    while ($log_row = $recent_logs_result->fetch_assoc()) {
        $recent_logs[] = $log_row;
    }
}


include '../components/layout_header.php';
?>

<style>
    body, html { overflow: hidden !important; height: 100vh; }
    main { height: calc(100vh - 85px); }
</style>

<!-- Main Content -->
<main class="p-2 bg-gray-50 overflow-hidden flex flex-col" style="height: calc(100vh - 85px);">
    
    <!-- Key Metrics Row -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 mb-2 flex-shrink-0">
        <!-- Unassigned Tickets -->
        <div class="bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-ticket text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Unassigned</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">Pending Assignment</p>
            <p class="text-lg font-bold"><?php echo $unassignedIssues; ?></p>
        </div>

        <!-- In Progress -->
        <div class="bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-wrench text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Active</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">In Progress</p>
            <p class="text-lg font-bold"><?php echo $inProgressIssues; ?></p>
        </div>

        <!-- Resolved -->
        <div class="bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-check-circle text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Completed</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">Resolved</p>
            <p class="text-lg font-bold"><?php echo $resolvedIssues; ?></p>
        </div>

        <!-- Total Assets -->
        <div class="bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-boxes text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Total</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">Total Assets</p>
            <p class="text-lg font-bold"><?php echo $totalAssets; ?></p>
        </div>

        <!-- Healthy Assets -->
        <div class="bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-shield-alt text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Healthy</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">Good Condition</p>
            <p class="text-lg font-bold"><?php echo $healthyAssets; ?></p>
        </div>

        <!-- Pending Borrows -->
        <div class="bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-hand-holding text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Borrow</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">Pending Requests</p>
            <p class="text-lg font-bold"><?php echo $assetsBorrowed; ?></p>
        </div>
    </div>

    <!-- Secondary Metrics Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-1.5 mb-2 flex-shrink-0">
        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Available Assets</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $assetsAvailable; ?></p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Assets In Use</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $assetsInUse; ?></p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Needs Attention</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $needsAttention; ?></p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Critical Assets</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $assetsCritical; ?></p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Avg Assignment</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo round($avgAssignmentTime, 1); ?> hours</p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Week Assigned</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $weekAssigned; ?></p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Today's Activity</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $todayActivity; ?></p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
            <p class="text-[9px] text-gray-500 mb-0.5">Total Issues</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $totalIssues; ?></p>
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
                        <p class="text-[9px] text-gray-500 mb-1">Current ticket breakdown</p>
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

                    <!-- Asset Status -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Asset Status</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Asset distribution</p>
                        <div style="height: calc(100% - 2.5rem);">
                            <canvas id="assetChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Trends Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 flex-1">
                    <!-- Issues Trend -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Issues Trend</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Last 6 months reported issues</p>
                        <div style="height: calc(100% - 2.5rem);">
                            <canvas id="issuesTrendChart"></canvas>
                        </div>
                    </div>

                    <!-- Assignment Performance -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Assignment Performance</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Avg hours to assign (faster is better)</p>
                        <div style="height: calc(100% - 2.5rem);">
                            <canvas id="assignmentTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Recent Activity -->
            <div class="flex flex-col gap-2 overflow-hidden">
                <!-- Recent Activity -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 flex-1 overflow-hidden flex flex-col">
                    <h3 class="text-xs font-semibold text-gray-900 mb-1">Recent Activity</h3>
                    <div id="activityLogsContainer" class="flex-1 overflow-y-auto space-y-1.5">
                        <?php if (empty($recent_logs)): ?>
                            <div class="flex items-center justify-center h-full">
                                <p class="text-xs text-gray-500 text-center">No recent activity</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_logs as $log): ?>
                                <div class="border border-gray-200 rounded p-2 hover:bg-gray-50 transition-colors">
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
        labels: ['Unassigned', 'In Progress', 'Resolved'],
        datasets: [{
            data: [
                <?php echo $unassignedIssues; ?>,
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
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { size: 10 }, padding: 8 }
            }
        }
    }
});

// 2. Issue Types
const typeCtx = document.getElementById('typeChart').getContext('2d');
new Chart(typeCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($issueTypes); ?>,
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
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { size: 10 }, padding: 8 }
            }
        }
    }
});

// 3. Asset Status
const assetCtx = document.getElementById('assetChart').getContext('2d');
new Chart(assetCtx, {
    type: 'doughnut',
    data: {
        labels: ['Available', 'In Use', 'Needs Attention', 'Critical'],
        datasets: [{
            data: [
                <?php echo $assetsAvailable; ?>,
                <?php echo $assetsInUse; ?>,
                <?php echo $needsAttention; ?>,
                <?php echo $assetsCritical; ?>
            ],
            backgroundColor: [
                'rgba(30, 58, 138, 1)',
                'rgba(30, 58, 138, 0.8)',
                'rgba(30, 58, 138, 0.6)',
                'rgba(30, 58, 138, 0.4)'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { size: 10 }, padding: 8 }
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
            label: 'Reported Issues',
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

// 5. Assignment Performance (Bar Chart)
const assignmentTrendCtx = document.getElementById('assignmentTrendChart').getContext('2d');
new Chart(assignmentTrendCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($assignmentMonths); ?>,
        datasets: [{
            label: 'Avg Hours to Assign',
            data: <?php echo json_encode($assignmentAvgHours); ?>,
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