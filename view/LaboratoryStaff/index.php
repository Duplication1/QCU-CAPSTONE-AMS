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
// Unassigned issues (exclude borrow category)
$unassignedIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE (assigned_technician IS NULL OR assigned_technician = '') AND status = 'Open' AND (category IS NULL OR category != 'borrow')")->fetch_assoc()['count'];

// In Progress issues (exclude borrow category)
$inProgressIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'In Progress' AND (category IS NULL OR category != 'borrow')")->fetch_assoc()['count'];

// Resolved issues (exclude borrow category)
$resolvedIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'Resolved' AND (category IS NULL OR category != 'borrow')")->fetch_assoc()['count'];

// Total issues (exclude borrow category)
$totalIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE (category IS NULL OR category != 'borrow')")->fetch_assoc()['count'];

// ============================================
// ASSET METRICS
// ============================================
$assetsBorrowed = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Pending'")->fetch_assoc()['count'];
$assetsAvailable = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Available'")->fetch_assoc()['count'];
$assetsInUse = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'In Use'")->fetch_assoc()['count'];
// Critical assets are those with poor/non-functional condition regardless of status
$assetsCritical = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` IN ('Non-Functional', 'Poor')")->fetch_assoc()['count'];
// Needs attention are Fair condition assets that are either Available or In Use
$needsAttention = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Fair' AND status IN ('Available', 'In Use')")->fetch_assoc()['count'];
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
    AND (category IS NULL OR category != 'borrow')
");
$avgAssignmentTime = $avgAssignmentResult ? ($avgAssignmentResult->fetch_assoc()['avg_hours'] ?? 0) : 0;

// This week's assigned issues
$weekAssignedResult = $conn->query("
    SELECT COUNT(*) as count 
    FROM issues 
    WHERE assigned_at IS NOT NULL 
    AND WEEK(assigned_at) = WEEK(CURDATE()) 
    AND YEAR(assigned_at) = YEAR(CURDATE())
    AND (category IS NULL OR category != 'borrow')
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
    SELECT category, COUNT(*) as count 
    FROM issues 
    WHERE status != 'Resolved' 
    AND (category IS NOT NULL AND category != 'borrow')
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
// Generate all 6 months to ensure accurate representation
$issueMonths = [];
$issueCounts = [];
$monthlyData = [];
$monthMetadata = []; // Store metadata for JavaScript

// Create array of last 6 months
for ($i = 5; $i >= 0; $i--) {
    $monthDate = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M', strtotime("-$i months"));
    $yearMonth = date('F Y', strtotime("-$i months"));
    $monthlyData[$monthDate] = [
        'label' => $monthLabel,
        'count' => 0,
        'index' => 5 - $i,
        'year_month' => $yearMonth
    ];
}

// Fetch actual issue counts
$monthlyIssuesResult = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as ym,
        COUNT(*) as count 
    FROM issues 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    AND (category IS NULL OR category != 'borrow')
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
");

if ($monthlyIssuesResult && $monthlyIssuesResult->num_rows > 0) {
    while ($row = $monthlyIssuesResult->fetch_assoc()) {
        if (isset($monthlyData[$row['ym']])) {
            $monthlyData[$row['ym']]['count'] = (int)$row['count'];
        }
    }
}

// Build arrays for chart
foreach ($monthlyData as $yearMonth => $data) {
    $issueMonths[] = $data['label'];
    $issueCounts[] = $data['count'];
    $monthMetadata[] = [
        'index' => $data['index'],
        'year_month' => $data['year_month'],
        'count' => $data['count']
    ];
}

// Assignment speed trend (avg hours per month)
$assignmentMonths = [];
$assignmentAvgHours = [];
$assignmentData = [];

// Create array of last 6 months
for ($i = 5; $i >= 0; $i--) {
    $monthDate = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M', strtotime("-$i months"));
    $assignmentData[$monthDate] = [
        'label' => $monthLabel,
        'avg_hours' => 0
    ];
}

$monthlyAssignmentResult = $conn->query("
    SELECT 
        DATE_FORMAT(assigned_at, '%Y-%m') as ym,
        AVG(TIMESTAMPDIFF(HOUR, created_at, assigned_at)) as avg_hours
    FROM issues 
    WHERE assigned_at IS NOT NULL
    AND assigned_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    AND (category IS NULL OR category != 'borrow')
    GROUP BY DATE_FORMAT(assigned_at, '%Y-%m')
    ORDER BY assigned_at ASC
");

if ($monthlyAssignmentResult && $monthlyAssignmentResult->num_rows > 0) {
    while ($row = $monthlyAssignmentResult->fetch_assoc()) {
        if (isset($assignmentData[$row['ym']])) {
            $assignmentData[$row['ym']]['avg_hours'] = round($row['avg_hours'] ?? 0, 1);
        }
    }
}

// Build arrays for chart
foreach ($assignmentData as $yearMonth => $data) {
    $assignmentMonths[] = $data['label'];
    $assignmentAvgHours[] = $data['avg_hours'];
}

include '../components/layout_header.php';
?>

<style>
    body, html { overflow: hidden !important; height: 100vh; }
    main { height: calc(100vh - 85px); }
    
    /* Fixed chart sizes - no resizing */
    #statusChart, #typeChart, #assetChart {
        width: 220px !important;
        height: 220px !important;
    }
    
    #issuesTrendChart {
        width: 420px !important;
        height: 190px !important;
    }
    
    #assignmentTrendChart {
        width: 420px !important;
        height: 190px !important;
    }

    /* Metric card hover animation */
    .metric-card {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        display: block;
    }
    .metric-card:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 18px rgba(30, 58, 138, 0.18);
        z-index: 10;
        position: relative;
    }
</style>

<!-- Main Content -->
<main class="p-2 bg-gray-50 overflow-hidden flex flex-col" style="height: calc(100vh - 85px);">
    
    <!-- Key Metrics Row -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 mb-2 flex-shrink-0">
        <!-- Unassigned Tickets -->
        <a href="issue_details.php?status=Open" class="metric-card bg-white rounded-lg shadow-sm p-2 hover:shadow-md transition-shadow cursor-pointer" style="color: #1E3A8A; text-decoration: none;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-ticket text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Unassigned</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">Pending Assignment</p>
            <p class="text-lg font-bold"><?php echo $unassignedIssues; ?></p>
        </a>

        <!-- In Progress -->
        <a href="issue_details.php?status=In%20Progress" class="metric-card bg-white rounded-lg shadow-sm p-2 hover:shadow-md transition-shadow cursor-pointer" style="color: #1E3A8A; text-decoration: none;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-wrench text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Active</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">In Progress</p>
            <p class="text-lg font-bold"><?php echo $inProgressIssues; ?></p>
        </a>

        <!-- Resolved -->
        <a href="issue_details.php?status=Resolved" class="metric-card bg-white rounded-lg shadow-sm p-2 hover:shadow-md transition-shadow cursor-pointer" style="color: #1E3A8A; text-decoration: none;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-check-circle text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Completed</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">Resolved</p>
            <p class="text-lg font-bold"><?php echo $resolvedIssues; ?></p>
        </a>

        <!-- Total Assets -->
        <a href="asset_details.php?filter=All" class="metric-card bg-white rounded-lg shadow-sm p-2 hover:shadow-md transition-shadow cursor-pointer" style="color: #1E3A8A; text-decoration: none;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-boxes text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Total</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">Total Assets</p>
            <p class="text-lg font-bold"><?php echo $totalAssets; ?></p>
        </a>

        <!-- Healthy Assets -->
        <a href="asset_details.php?filter=Healthy" class="metric-card bg-white rounded-lg shadow-sm p-2 hover:shadow-md transition-shadow cursor-pointer" style="color: #1E3A8A; text-decoration: none;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-shield-alt text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Healthy</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">Good Condition</p>
            <p class="text-lg font-bold"><?php echo $healthyAssets; ?></p>
        </a>

        <!-- Pending Borrows -->
        <a href="borrowing.php" class="metric-card bg-white rounded-lg shadow-sm p-2 hover:shadow-md transition-shadow cursor-pointer" style="color: #1E3A8A; text-decoration: none;">
            <div class="flex items-center justify-between mb-1">
                <i class="fas fa-hand-holding text-lg opacity-80"></i>
                <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Borrow</span>
            </div>
            <p class="text-[9px] opacity-70 mb-0.5">Pending Requests</p>
            <p class="text-lg font-bold"><?php echo $assetsBorrowed; ?></p>
        </a>
    </div>

    <!-- Secondary Metrics Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-1.5 mb-2 flex-shrink-0">
        <a href="asset_details.php?filter=Available" class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow-md transition-shadow cursor-pointer" style="text-decoration: none;">
            <p class="text-[9px] text-gray-500 mb-0.5">Available Assets</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $assetsAvailable; ?></p>
        </a>

        <a href="asset_details.php?filter=In%20Use" class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow-md transition-shadow cursor-pointer" style="text-decoration: none;">
            <p class="text-[9px] text-gray-500 mb-0.5">Assets In Use</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $assetsInUse; ?></p>
        </a>

        <a href="asset_details.php?filter=Fair" class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow-md transition-shadow cursor-pointer" style="text-decoration: none;">
            <p class="text-[9px] text-gray-500 mb-0.5">Needs Attention</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $needsAttention; ?></p>
        </a>

        <a href="asset_details.php?filter=Critical" class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow-md transition-shadow cursor-pointer" style="text-decoration: none;">
            <p class="text-[9px] text-gray-500 mb-0.5">Critical Assets</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $assetsCritical; ?></p>
        </a>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
            <p class="text-[9px] text-gray-500 mb-0.5">Avg Assignment</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo round($avgAssignmentTime, 1); ?> hours</p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
            <p class="text-[9px] text-gray-500 mb-0.5">Week Assigned</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $weekAssigned; ?></p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
            <p class="text-[9px] text-gray-500 mb-0.5">Today's Activity</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $todayActivity; ?></p>
        </div>

        <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
            <p class="text-[9px] text-gray-500 mb-0.5">Total Issues</p>
            <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $totalIssues; ?></p>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="flex-1 min-h-0 overflow-hidden">
        <div class="flex flex-col gap-2 h-full">
            <!-- Charts Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 flex-1">
                    <!-- Issue Status Distribution -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2 cursor-pointer hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-0.5">
                            <h3 class="text-xs font-semibold text-gray-900">Issue Status</h3>
                            <span class="text-[9px] bg-blue-100 text-blue-800 px-2 py-0.5 rounded">
                                <i class="fas fa-hand-pointer mr-1"></i>Clickable
                            </span>
                        </div>
                        <p class="text-[9px] text-gray-500 mb-1">Current ticket breakdown - Click to view</p>
                        <div style="width: 220px; height: 220px; margin: 0 auto;">
                            <canvas id="statusChart" width="220" height="220"></canvas>
                        </div>
                    </div>

                    <!-- Issue Types -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2 cursor-pointer hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-0.5">
                            <h3 class="text-xs font-semibold text-gray-900">Issue Types</h3>
                            <span class="text-[9px] bg-blue-100 text-blue-800 px-2 py-0.5 rounded">
                                <i class="fas fa-hand-pointer mr-1"></i>Clickable
                            </span>
                        </div>
                        <p class="text-[9px] text-gray-500 mb-1">Active issues by category - Click to view</p>
                        <div style="width: 220px; height: 220px; margin: 0 auto;">
                            <canvas id="typeChart" width="220" height="220"></canvas>
                        </div>
                    </div>

                    <!-- Asset Status -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2 cursor-pointer hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-0.5">
                            <h3 class="text-xs font-semibold text-gray-900">Asset Status</h3>
                            <span class="text-[9px] bg-blue-100 text-blue-800 px-2 py-0.5 rounded">
                                <i class="fas fa-hand-pointer mr-1"></i>Clickable
                            </span>
                        </div>
                        <p class="text-[9px] text-gray-500 mb-1">Asset distribution - Click to view</p>
                        <div style="width: 220px; height: 220px; margin: 0 auto;">
                            <canvas id="assetChart" width="220" height="220"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Trends Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 flex-1">
                    <!-- Issues Trend -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2 cursor-pointer hover:shadow-md transition-shadow flex flex-col">
                        <div class="flex items-center justify-between mb-0.5">
                            <h3 class="text-xs font-semibold text-gray-900">Issues Trend</h3>
                            <div class="flex items-center gap-1">
                                <span class="text-[9px] text-gray-500">
                                    Last 6 months
                                </span>
                                <span class="text-[9px] bg-blue-100 text-blue-800 px-2 py-0.5 rounded">
                                    <i class="fas fa-hand-pointer mr-1"></i>Clickable
                                </span>
                            </div>
                        </div>
                        <p class="text-[9px] text-gray-500 mb-1">
                            Click any point to see detailed breakdown
                            <span class="ml-1 text-blue-900 font-semibold">
                                (Total: <?php echo array_sum($issueCounts); ?> issues)
                            </span>
                        </p>
                        <div style="width: 420px; height: 190px; margin: 0 auto;">
                            <canvas id="issuesTrendChart" width="420" height="190"></canvas>
                        </div>
                        <!-- Monthly breakdown -->
                        <div class="mt-1 pt-1 border-t border-gray-200 grid grid-cols-6 gap-0.5 text-center">
                            <?php foreach ($monthMetadata as $idx => $meta): ?>
                                <div class="text-[8px]">
                                    <div class="font-medium text-gray-700"><?php echo $issueMonths[$idx]; ?></div>
                                    <div class="<?php echo $meta['count'] > 0 ? 'text-blue-900 font-bold' : 'text-gray-400'; ?>">
                                        <?php echo $meta['count']; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Assignment Performance -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Assignment Performance</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Avg hours to assign (faster is better)</p>
                        <div style="width: 420px; height: 190px; margin: 0 auto;">
                            <canvas id="assignmentTrendChart" width="420" height="190"></canvas>
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

// 1. Issue Status Distribution (Clickable)
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusLabels = ['Unassigned', 'In Progress', 'Resolved'];
const statusMap = ['Open', 'In Progress', 'Resolved'];

const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: statusLabels,
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
        responsive: false,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { size: 10 }, padding: 8 }
            },
            tooltip: {
                callbacks: {
                    afterLabel: function(context) {
                        return '\n👆 Click to view details';
                    }
                }
            }
        },
        onClick: (event, activeElements) => {
            if (activeElements.length > 0) {
                const index = activeElements[0].index;
                const status = statusMap[index];
                window.location.href = `issue_details.php?status=${encodeURIComponent(status)}`;
            }
        },
        onHover: (event, activeElements) => {
            event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
        }
    }
});

// 2. Issue Types (Clickable)
const typeCtx = document.getElementById('typeChart').getContext('2d');
const issueTypeLabels = <?php echo json_encode($issueTypes); ?>;

const typeChart = new Chart(typeCtx, {
    type: 'doughnut',
    data: {
        labels: issueTypeLabels,
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
        responsive: false,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { size: 10 }, padding: 8 }
            },
            tooltip: {
                callbacks: {
                    afterLabel: function(context) {
                        return '\n👆 Click to view details';
                    }
                }
            }
        },
        onClick: (event, activeElements) => {
            if (activeElements.length > 0) {
                const index = activeElements[0].index;
                const category = issueTypeLabels[index];
                window.location.href = `issue_details.php?category=${encodeURIComponent(category)}`;
            }
        },
        onHover: (event, activeElements) => {
            event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
        }
    }
});

// 3. Asset Status (Clickable)
const assetCtx = document.getElementById('assetChart').getContext('2d');
const assetLabels = ['Available', 'In Use', 'Needs Attention', 'Critical'];
const assetStatusMap = ['Available', 'In Use', 'Fair', 'Critical'];

const assetChart = new Chart(assetCtx, {
    type: 'doughnut',
    data: {
        labels: assetLabels,
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
        responsive: false,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { size: 10 }, padding: 8 }
            },
            tooltip: {
                callbacks: {
                    afterLabel: function(context) {
                        return '\n👆 Click to view details';
                    }
                }
            }
        },
        onClick: (event, activeElements) => {
            if (activeElements.length > 0) {
                const index = activeElements[0].index;
                const statusOrCondition = assetStatusMap[index];
                window.location.href = `asset_details.php?filter=${encodeURIComponent(statusOrCondition)}`;
            }
        },
        onHover: (event, activeElements) => {
            event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
        }
    }
});

// 4. Issues Trend (Clickable)
const issuesTrendCtx = document.getElementById('issuesTrendChart').getContext('2d');
const monthMetadata = <?php echo json_encode($monthMetadata); ?>;

const issuesTrendChart = new Chart(issuesTrendCtx, {
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
            pointBackgroundColor: chartColors.blue,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: chartColors.orange
        }]
    },
    options: {
        responsive: false,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    title: function(context) {
                        const index = context[0].dataIndex;
                        return monthMetadata[index].year_month;
                    },
                    label: function(context) {
                        const count = context.parsed.y;
                        return count === 1 ? '1 issue reported' : count + ' issues reported';
                    },
                    afterLabel: function(context) {
                        return '\n👆 Click to view detailed breakdown';
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
                    stepSize: 1
                }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 10 } }
            }
        },
        onClick: (event, activeElements) => {
            if (activeElements.length > 0) {
                const monthIndex = activeElements[0].index;
                const currentYear = new Date().getFullYear();
                window.location.href = `historical_issues.php?month=${monthIndex}&year=${currentYear}`;
            }
        },
        // Change cursor on hover to indicate clickability
        onHover: (event, activeElements) => {
            event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
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
        responsive: false,
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

<script>
// Debounce resize events to prevent excessive chart redraws
let resizeTimer;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
        // Charts will auto-resize smoothly
    }, 250);
});
</script>

<?php include '../components/layout_footer.php'; ?>