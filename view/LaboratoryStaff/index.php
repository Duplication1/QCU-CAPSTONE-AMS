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
$healthyAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` IN ('Good', 'Excellent')")->fetch_assoc()['count'];
$totalAssets = $conn->query("SELECT COUNT(*) as count FROM assets")->fetch_assoc()['count'];

// End of Life Assets - assets that have reached or exceeded their expected lifespan
// Calculate based on created_at + category's end_of_life years
$endOfLifeAssets = $conn->query("
    SELECT COUNT(*) as count 
    FROM assets a
    LEFT JOIN asset_categories ac ON a.category = ac.id
    WHERE ac.end_of_life IS NOT NULL 
    AND DATE_ADD(a.created_at, INTERVAL ac.end_of_life YEAR) <= CURDATE()
    AND a.status NOT IN ('Disposed', 'Retired', 'Archive')
")->fetch_assoc()['count'];

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

include '../components/layout_header.php';
?>

<style>
    body, html { overflow: hidden !important; height: 100vh; }
    main { height: calc(100vh - 85px); }
    
    /* Fixed chart sizes - no resizing */
    #statusChart, #typeChart {
        width: 120px !important;
        height: 120px !important;
    }
    
    #issuesTrendChart {
        width: 100% !important;
        height: 100% !important;
    }

    /* Professional metric card styling */
    .metric-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: block;
        position: relative;
        overflow: hidden;
    }
    
    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: #1E3A8A;
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    
    .metric-card:hover::before {
        transform: scaleX(1);
    }
    
    .metric-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(30, 58, 138, 0.15);
        z-index: 10;
    }
    
    /* Icon pulse animation */
    @keyframes pulse-subtle {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.8; }
    }
    
    .metric-icon {
        animation: pulse-subtle 3s ease-in-out infinite;
    }
    
    /* Professional gradient backgrounds */
    .gradient-blue {
        background: #1E3A8A;
    }
    
    .gradient-green {
        background: #10B981;
    }
    
    .gradient-amber {
        background: #F59E0B;
    }
    
    .gradient-red {
        background: #EF4444;
    }
    
    /* Chart container styling */
    .chart-container {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    
    .chart-container:hover {
        border-color: #3B82F6;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
    }
</style>

<!-- Main Content -->
<main class="p-2 bg-gray-50 overflow-hidden flex flex-col" style="height: calc(100vh - 85px);">
    
    <!-- Key Metrics Row -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-2 mb-2 flex-shrink-0">
        <!-- Unassigned Tickets -->
        <a href="issue_details.php?status=Open" class="metric-card bg-white rounded-lg shadow-sm p-2 hover:shadow-md cursor-pointer" style="color: #1E3A8A; text-decoration: none;">
            <div class="flex items-center justify-between mb-1">
                <div class="metric-icon w-8 h-8 rounded-full gradient-blue flex items-center justify-center">
                    <i class="fas fa-ticket text-white text-xs"></i>
                </div>
                <span class="text-[8px] font-semibold bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded-full">Pending</span>
            </div>
            <p class="text-[9px] text-gray-500 mb-0.5 font-medium">Unassigned Tickets</p>
            <p class="text-xl font-bold text-gray-900"><?php echo $unassignedIssues; ?></p>
        </a>

        <!-- In Progress -->
        <a href="issue_details.php?status=In%20Progress" class="metric-card bg-white rounded-lg shadow-sm p-2 hover:shadow-md cursor-pointer" style="color: #1E3A8A; text-decoration: none;">
            <div class="flex items-center justify-between mb-1">
                <div class="metric-icon w-8 h-8 rounded-full gradient-amber flex items-center justify-center">
                    <i class="fas fa-wrench text-white text-xs"></i>
                </div>
                <span class="text-[8px] font-semibold bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded-full">Active</span>
            </div>
            <p class="text-[9px] text-gray-500 mb-0.5 font-medium">In Progress</p>
            <p class="text-xl font-bold text-gray-900"><?php echo $inProgressIssues; ?></p>
        </a>

        <!-- Resolved -->
        <a href="issue_details.php?status=Resolved" class="metric-card bg-white rounded-lg shadow-sm p-2 hover:shadow-md cursor-pointer" style="color: #1E3A8A; text-decoration: none;">
            <div class="flex items-center justify-between mb-1">
                <div class="metric-icon w-8 h-8 rounded-full gradient-green flex items-center justify-center">
                    <i class="fas fa-check-circle text-white text-xs"></i>
                </div>
                <span class="text-[8px] font-semibold bg-green-50 text-green-700 px-1.5 py-0.5 rounded-full">Done</span>
            </div>
            <p class="text-[9px] text-gray-500 mb-0.5 font-medium">Resolved</p>
            <p class="text-xl font-bold text-gray-900"><?php echo $resolvedIssues; ?></p>
        </a>

        <!-- Total Assets -->
        <a href="asset_details.php?filter=All" class="metric-card bg-white rounded-lg shadow-sm p-2 hover:shadow-md cursor-pointer" style="color: #1E3A8A; text-decoration: none;">
            <div class="flex items-center justify-between mb-1">
                <div class="metric-icon w-8 h-8 rounded-full gradient-blue flex items-center justify-center">
                    <i class="fas fa-boxes text-white text-xs"></i>
                </div>
                <span class="text-[8px] font-semibold bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded-full">Total</span>
            </div>
            <p class="text-[9px] text-gray-500 mb-0.5 font-medium">Total Assets</p>
            <p class="text-xl font-bold text-gray-900"><?php echo $totalAssets; ?></p>
        </a>

        <!-- Healthy Assets -->
        <a href="asset_details.php?filter=Healthy" class="metric-card bg-white rounded-lg shadow-sm p-2 hover:shadow-md cursor-pointer" style="color: #1E3A8A; text-decoration: none;">
            <div class="flex items-center justify-between mb-1">
                <div class="metric-icon w-8 h-8 rounded-full gradient-green flex items-center justify-center">
                    <i class="fas fa-shield-alt text-white text-xs"></i>
                </div>
                <span class="text-[8px] font-semibold bg-green-50 text-green-700 px-1.5 py-0.5 rounded-full">Healthy</span>
            </div>
            <p class="text-[9px] text-gray-500 mb-0.5 font-medium">Good Condition</p>
            <p class="text-xl font-bold text-gray-900"><?php echo $healthyAssets; ?></p>
        </a>

        <!-- End of Life Assets -->
        <a href="asset_details.php?filter=EndOfLife" class="metric-card bg-white rounded-lg shadow-sm p-2 hover:shadow-md cursor-pointer" style="color: #1E3A8A; text-decoration: none;">
            <div class="flex items-center justify-between mb-1">
                <div class="metric-icon w-8 h-8 rounded-full gradient-red flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-white text-xs"></i>
                </div>
                <span class="text-[8px] font-semibold bg-red-50 text-red-700 px-1.5 py-0.5 rounded-full">EOL</span>
            </div>
            <p class="text-[9px] text-gray-500 mb-0.5 font-medium">End of Life</p>
            <p class="text-xl font-bold text-gray-900"><?php echo $endOfLifeAssets; ?></p>
        </a>

        <!-- Pending Borrows -->
        <a href="borrowing.php" class="metric-card bg-white rounded-lg shadow-sm p-2 hover:shadow-md cursor-pointer" style="color: #1E3A8A; text-decoration: none;">
            <div class="flex items-center justify-between mb-1">
                <div class="metric-icon w-8 h-8 rounded-full gradient-amber flex items-center justify-center">
                    <i class="fas fa-hand-holding text-white text-xs"></i>
                </div>
                <span class="text-[8px] font-semibold bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded-full">Borrow</span>
            </div>
            <p class="text-[9px] text-gray-500 mb-0.5 font-medium">Pending Requests</p>
            <p class="text-xl font-bold text-gray-900"><?php echo $assetsBorrowed; ?></p>
        </a>
    </div>

    <!-- Main Content Grid -->
    <div class="flex-1 min-h-0 overflow-hidden">
        <div class="flex flex-col gap-2 h-full">
            <!-- Charts Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2" style="height: 45%;">
                    <!-- Issue Status Distribution -->
                    <div class="chart-container bg-white rounded-lg shadow-sm p-2 hover:shadow-md transition-all flex flex-col h-full">
                        <div class="flex items-center justify-between mb-2 flex-shrink-0">
                            <h3 class="text-xs font-bold text-gray-900 flex items-center gap-2">
                                <span class="w-1 h-4 bg-blue-600 rounded"></span>
                                Issue Status
                            </h3>
                            <span class="text-[8px] text-gray-400 font-medium">Real-time</span>
                        </div>
                        <!-- Donut + Bars Row -->
                        <div class="flex items-center gap-4 flex-1">
                            <!-- Donut Chart -->
                            <div class="relative flex-shrink-0" style="width:130px;height:130px;">
                                <canvas id="statusChart" width="130" height="130"></canvas>
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <span class="font-bold text-gray-800" id="statusChartPct" style="font-size:18px;">0%</span>
                                </div>
                            </div>
                            <!-- Status Bars -->
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] font-semibold text-gray-700 mb-2">Status</p>
                                <?php
                                $statusBarColors = ['#F6C762', '#3B3663', '#1E3A8A'];
                                $statusLabelsPhp = ['Resolved', 'In Progress', 'Unassigned'];
                                $statusValuesPhp = [$resolvedIssues, $inProgressIssues, $unassignedIssues];
                                $totalStatusCount = array_sum($statusValuesPhp);
                                foreach ($statusLabelsPhp as $i => $label):
                                    $val = $statusValuesPhp[$i];
                                    $pct = $totalStatusCount > 0 ? round(($val / $totalStatusCount) * 100) : 0;
                                    $barColor = $statusBarColors[$i];
                                ?>
                                <div class="mb-2">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-[10px] text-gray-600 truncate max-w-[80px]"><?php echo $label; ?></span>
                                        <span class="text-[10px] text-gray-600 ml-1 flex-shrink-0"><?php echo $pct; ?>%</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full" style="height:6px;">
                                        <div class="h-full rounded-full" style="width:<?php echo $pct; ?>%;background:<?php echo $barColor; ?>;"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Legend -->
                        <div class="flex flex-wrap gap-3 mt-3 pt-2 border-t border-gray-100">
                            <?php foreach ($statusLabelsPhp as $i => $label):
                                $barColor = $statusBarColors[$i];
                            ?>
                            <div class="flex items-center gap-1">
                                <span class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:<?php echo $barColor; ?>;"></span>
                                <span class="text-[10px] text-gray-600"><?php echo $label; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Issue Types -->
                    <div class="chart-container bg-white rounded-lg shadow-sm p-2 hover:shadow-md transition-all flex flex-col h-full">
                        <div class="flex items-center justify-between mb-2 flex-shrink-0">
                            <h3 class="text-xs font-bold text-gray-900 flex items-center gap-2">
                                <span class="w-1 h-4 bg-purple-600 rounded"></span>
                                Issue Types
                            </h3>
                            <span class="text-[8px] text-gray-400 font-medium">Active</span>
                        </div>
                        <!-- Donut + Bars Row -->
                        <div class="flex items-center gap-4 flex-1">
                            <!-- Donut Chart -->
                            <div class="relative flex-shrink-0" style="width:130px;height:130px;">
                                <canvas id="typeChart" width="130" height="130"></canvas>
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <span class="font-bold text-gray-800" id="typeChartPct" style="font-size:18px;">0%</span>
                                </div>
                            </div>
                            <!-- Status Bars -->
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] font-semibold text-gray-700 mb-2">Status</p>
                                <?php
                                $typeBarColors = ['#F6C762', '#3B3663', '#1E3A8A', '#8B5CF6', '#10B981'];
                                $totalIssueTypeCount = array_sum($issueTypeCounts);
                                foreach ($issueTypes as $i => $type):
                                    $count = $issueTypeCounts[$i];
                                    $pct = $totalIssueTypeCount > 0 ? round(($count / $totalIssueTypeCount) * 100) : 0;
                                    $barColor = $typeBarColors[$i % count($typeBarColors)];
                                ?>
                                <div class="mb-2">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-[10px] text-gray-600 truncate max-w-[80px]"><?php echo htmlspecialchars(ucfirst($type)); ?></span>
                                        <span class="text-[10px] text-gray-600 ml-1 flex-shrink-0"><?php echo $pct; ?>%</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full" style="height:6px;">
                                        <div class="h-full rounded-full" style="width:<?php echo $pct; ?>%;background:<?php echo $barColor; ?>;"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Legend -->
                        <div class="flex flex-wrap gap-3 mt-3 pt-2 border-t border-gray-100">
                            <?php foreach ($issueTypes as $i => $type):
                                $barColor = $typeBarColors[$i % count($typeBarColors)];
                            ?>
                            <div class="flex items-center gap-1">
                                <span class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:<?php echo $barColor; ?>;"></span>
                                <span class="text-[10px] text-gray-600"><?php echo htmlspecialchars(ucfirst($type)); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Trends Row -->
                <div class="grid grid-cols-1 gap-2" style="height: 55%;">
                    <!-- Issues Trend -->
                    <div class="chart-container bg-white rounded-lg shadow-sm p-3 hover:shadow-md transition-all flex flex-col h-full overflow-hidden">
                        <div class="flex items-center justify-between mb-2 flex-shrink-0">
                            <h3 class="text-xs font-bold text-gray-900 flex items-center gap-2">
                                <span class="w-1 h-4 bg-indigo-600 rounded"></span>
                                Issues Trend Analysis
                            </h3>
                            <div class="flex items-center gap-2">
                                <span class="text-[8px] text-gray-400 font-medium">Last 6 months</span>
                                <span class="text-[8px] bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded-full font-semibold">
                                    <i class="fas fa-hand-pointer mr-1"></i>Interactive
                                </span>
                            </div>
                        </div>
                        
                        <p class="text-[9px] text-gray-500 mb-2 flex-shrink-0">
                            Click any data point to view details
                            <span class="ml-1 text-indigo-900 font-bold">
                                (Total: <?php echo array_sum($issueCounts); ?>)
                            </span>
                        </p>
                        
                        <!-- Chart Container with fixed height -->
                        <div class="flex-shrink-0" style="height: 180px; position: relative;">
                            <canvas id="issuesTrendChart"></canvas>
                        </div>
                        
                        <!-- Monthly breakdown -->
                        <div class="mt-2 pt-2 border-t border-gray-200 grid grid-cols-6 gap-1 text-center flex-shrink-0">
                            <?php foreach ($monthMetadata as $idx => $meta): ?>
                                <div class="text-[8px]">
                                    <div class="font-semibold text-gray-700"><?php echo $issueMonths[$idx]; ?></div>
                                    <div class="<?php echo $meta['count'] > 0 ? 'text-indigo-900 font-bold text-sm' : 'text-gray-400 text-xs'; ?>">
                                        <?php echo $meta['count']; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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
const statusLabels = ['Resolved', 'In Progress', 'Unassigned'];
const statusMap = ['Resolved', 'In Progress', 'Open'];
const statusData = [<?php echo $resolvedIssues; ?>, <?php echo $inProgressIssues; ?>, <?php echo $unassignedIssues; ?>];
const statusColors = ['#F6C762', '#3B3663', '#1E3A8A'];

const statusCenterPlugin = {
    id: 'statusCenterText',
    afterDraw(chart) {
        const total = chart.data.datasets[0].data.reduce((a, b) => Number(a) + Number(b), 0);
        const first = Number(chart.data.datasets[0].data[0]) || 0;
        const pct = total > 0 ? Math.round((first / total) * 100) : 0;
        document.getElementById('statusChartPct').textContent = pct + '%';
    }
};

const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    plugins: [statusCenterPlugin],
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusData,
            backgroundColor: statusColors,
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 6
        }]
    },
    options: {
        responsive: false,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const pct = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                        return context.label + ': ' + context.parsed + ' (' + pct + '%)';
                    }
                }
            }
        },
        onClick: (event, activeElements) => {
            if (activeElements.length > 0) {
                const index = activeElements[0].index;
                window.location.href = `issue_details.php?status=${encodeURIComponent(statusMap[index])}`;
            }
        },
        onHover: (event, activeElements) => {
            event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
        }
    }
});

(function() {
    const total = statusData.reduce((a, b) => a + b, 0);
    const pct = total > 0 ? Math.round((statusData[0] / total) * 100) : 0;
    document.getElementById('statusChartPct').textContent = pct + '%';
})();

// 2. Issue Types — Donut with center text
const typeCtx = document.getElementById('typeChart').getContext('2d');
const issueTypeLabels = <?php echo json_encode(array_map('ucfirst', $issueTypes)); ?>;
const issueTypeData   = <?php echo json_encode($issueTypeCounts); ?>;
const typeBarColors   = ['#F6C762', '#3B3663', '#1E3A8A', '#8B5CF6', '#10B981'];

// Center-text plugin
const typeCenterTextPlugin = {
    id: 'typeCenterText',
    afterDraw(chart) {
        const { ctx, chartArea } = chart;
        const total = chart.data.datasets[0].data.reduce((a, b) => Number(a) + Number(b), 0);
        const first = Number(chart.data.datasets[0].data[0]) || 0;
        const pct   = total > 0 ? Math.round((first / total) * 100) : 0;
        document.getElementById('typeChartPct').textContent = pct + '%';
        const cx = (chartArea.left + chartArea.right) / 2;
        const cy = (chartArea.top  + chartArea.bottom) / 2;
        ctx.save();
        ctx.font = 'bold 18px Inter, sans-serif';
        ctx.fillStyle = '#1f2937';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.restore();
    }
};

const typeChart = new Chart(typeCtx, {
    type: 'doughnut',
    plugins: [typeCenterTextPlugin],
    data: {
        labels: issueTypeLabels,
        datasets: [{
            data: issueTypeData,
            backgroundColor: typeBarColors,
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 6
        }]
    },
    options: {
        responsive: false,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const pct = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                        return context.label + ': ' + context.parsed + ' (' + pct + '%)';
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

// Set initial center percentage
(function() {
    const total = issueTypeData.reduce((a, b) => a + b, 0);
    const pct = total > 0 ? Math.round((issueTypeData[0] / total) * 100) : 0;
    document.getElementById('typeChartPct').textContent = pct + '%';
})();

// 4. Issues Trend (Clickable)
const issuesTrendCtx = document.getElementById('issuesTrendChart').getContext('2d');
const monthMetadata = <?php echo json_encode($monthMetadata); ?>;

// Set canvas size explicitly
const trendCanvas = document.getElementById('issuesTrendChart');
trendCanvas.width = trendCanvas.parentElement.offsetWidth;
trendCanvas.height = 180;

const issuesTrendChart = new Chart(issuesTrendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($issueMonths); ?>,
        datasets: [{
            label: 'Reported Issues',
            data: <?php echo json_encode($issueCounts); ?>,
            borderColor: '#4F46E5',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#4F46E5',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointHoverRadius: 7,
            pointHoverBackgroundColor: '#F59E0B',
            pointHoverBorderColor: '#fff',
            pointHoverBorderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: { 
                display: false 
            },
            tooltip: {
                backgroundColor: 'rgba(17, 24, 39, 0.95)',
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 10,
                cornerRadius: 6,
                displayColors: false,
                titleFont: {
                    size: 12,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 11
                },
                callbacks: {
                    title: function(context) {
                        const index = context[0].dataIndex;
                        return monthMetadata[index].year_month;
                    },
                    label: function(context) {
                        const count = context.parsed.y;
                        return count === 1 ? '1 issue reported' : count + ' issues reported';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { 
                    color: '#F3F4F6',
                    drawBorder: false
                },
                border: {
                    display: false
                },
                ticks: { 
                    font: { 
                        size: 10,
                        weight: '500'
                    },
                    color: '#6B7280',
                    stepSize: 1,
                    padding: 6
                },
                title: {
                    display: true,
                    text: 'Issues',
                    font: { 
                        size: 10, 
                        weight: 'bold' 
                    },
                    color: '#374151',
                    padding: { top: 0, bottom: 8 }
                }
            },
            x: {
                grid: { 
                    display: false,
                    drawBorder: false
                },
                border: {
                    display: false
                },
                ticks: { 
                    font: { 
                        size: 10,
                        weight: '600'
                    },
                    color: '#374151',
                    padding: 6
                }
            }
        },
        onClick: (event, activeElements) => {
            if (activeElements.length > 0) {
                const monthIndex = activeElements[0].index;
                const currentYear = new Date().getFullYear();
                window.location.href = `historical_issues.php?month=${monthIndex}&year=${currentYear}`;
            }
        },
        onHover: (event, activeElements) => {
            event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
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