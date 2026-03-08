<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';

// Establish mysqli database connection for analytics
$dbConfig = Config::database();
try {
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die("Database connection error");
}

// ============================================
// FINANCIAL METRICS
// ============================================
// Total Asset Value
$totalValueResult = $conn->query("SELECT SUM(purchase_cost) as total_value FROM assets WHERE status NOT IN ('Disposed', 'Archive') AND purchase_cost IS NOT NULL");
$totalAssetValue = $totalValueResult->fetch_assoc()['total_value'] ?? 0;
$totalAssetValueFormatted = number_format($totalAssetValue, 2);

// Average Asset Cost
$avgCostResult = $conn->query("SELECT AVG(purchase_cost) as avg_cost FROM assets WHERE purchase_cost IS NOT NULL AND purchase_cost > 0");
$avgAssetCost = $avgCostResult->fetch_assoc()['avg_cost'] ?? 0;

// Monthly Acquisition Cost (last month)
$monthlyAcquisitionResult = $conn->query("SELECT SUM(purchase_cost) as monthly_cost FROM assets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) AND purchase_cost IS NOT NULL");
$monthlyAcquisition = $monthlyAcquisitionResult->fetch_assoc()['monthly_cost'] ?? 0;

// ============================================
// INVENTORY METRICS
// ============================================
// Total Active Assets (excluding disposed and archived)
$totalAssetsResult = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status NOT IN ('Disposed', 'Archive')");
$totalAssets = $totalAssetsResult->fetch_assoc()['count'];

// Assets by Status
$availableAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Available'")->fetch_assoc()['count'];
$inUseAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'In Use'")->fetch_assoc()['count'];
$maintenanceAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Under Maintenance'")->fetch_assoc()['count'];
$disposedAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Disposed'")->fetch_assoc()['count'];

// Assets by Condition
$excellentAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Excellent' AND status NOT IN ('Disposed', 'Archive')")->fetch_assoc()['count'];
$goodAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Good' AND status NOT IN ('Disposed', 'Archive')")->fetch_assoc()['count'];
$fairAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Fair' AND status NOT IN ('Disposed', 'Archive')")->fetch_assoc()['count'];
$poorAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Poor' AND status NOT IN ('Disposed', 'Archive')")->fetch_assoc()['count'];
$nonFunctionalAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Non-Functional' AND status NOT IN ('Disposed', 'Archive')")->fetch_assoc()['count'];

// Health Percentage
$healthyAssets = $excellentAssets + $goodAssets;
$assetHealthPercentage = $totalAssets > 0 ? round(($healthyAssets / $totalAssets) * 100, 1) : 0;

// ============================================
// UTILIZATION METRICS
// ============================================
// Borrowable Assets
$borrowableAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE is_borrowable = 1 AND status = 'Available'")->fetch_assoc()['count'];

// Active Borrowings
$activeBorrowings = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Approved'")->fetch_assoc()['count'];

// Pending Approvals
$pendingApprovals = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Pending'")->fetch_assoc()['count'];

// Total Returned
$totalReturned = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Returned'")->fetch_assoc()['count'];

// Utilization Rate (In Use / Total Available)
$utilizationRate = ($availableAssets + $inUseAssets) > 0 ? round(($inUseAssets / ($availableAssets + $inUseAssets)) * 100, 1) : 0;

// ============================================
// MAINTENANCE & ISSUES METRICS
// ============================================
// Total Issues
$totalIssues = $conn->query("SELECT COUNT(*) as count FROM issues")->fetch_assoc()['count'];

// Pending Issues
$pendingIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'Pending'")->fetch_assoc()['count'];

// In Progress Issues
$inProgressIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'In Progress'")->fetch_assoc()['count'];

// Resolved Issues
$resolvedIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'Resolved'")->fetch_assoc()['count'];

// Average Resolution Time
$avgResolutionResult = $conn->query("
    SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days 
    FROM issues 
    WHERE status = 'Resolved' AND updated_at IS NOT NULL
");
$avgResolutionTime = $avgResolutionResult->fetch_assoc()['avg_days'] ?? 0;

// ============================================
// ASSET DISTRIBUTION BY TYPE
// ============================================
$assetsByTypeResult = $conn->query("
    SELECT asset_type, COUNT(*) as count 
    FROM assets 
    WHERE status NOT IN ('Disposed', 'Archive')
    GROUP BY asset_type
    ORDER BY count DESC
");
$assetTypes = [];
$assetTypeCounts = [];
if ($assetsByTypeResult) {
    while ($row = $assetsByTypeResult->fetch_assoc()) {
        $assetTypes[] = $row['asset_type'];
        $assetTypeCounts[] = $row['count'];
    }
}
// Ensure we have data for the chart
if (empty($assetTypes)) {
    $assetTypes = ['No Data'];
    $assetTypeCounts = [0];
}

// ============================================
// MONTHLY TRENDS (Last 6 months)
// ============================================
// Asset Additions
$monthlyAdditionsResult = $conn->query("
    SELECT DATE_FORMAT(created_at, '%b') as month,
           DATE_FORMAT(created_at, '%Y-%m') as acq_ym,
           COUNT(*) as count 
    FROM assets 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
");
$additionMonths = [];
$additionCounts = [];
$additionYearMonths = [];
if ($monthlyAdditionsResult) {
    while ($row = $monthlyAdditionsResult->fetch_assoc()) {
        $additionMonths[] = $row['month'];
        $additionCounts[] = $row['count'];
        $additionYearMonths[] = $row['acq_ym'];
    }
}
// Ensure we have data for charts
if (empty($additionMonths)) {
    $additionMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $additionCounts = [0, 0, 0, 0, 0, 0];
    $additionYearMonths = ['', '', '', '', '', ''];
}

// Issues Trends
$issuesTrendResult = $conn->query("
    SELECT DATE_FORMAT(created_at, '%b') as month, COUNT(*) as count 
    FROM issues 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
");
$issueMonths = [];
$issueCounts = [];
if ($issuesTrendResult) {
    while ($row = $issuesTrendResult->fetch_assoc()) {
        $issueMonths[] = $row['month'];
        $issueCounts[] = $row['count'];
    }
}
if (empty($issueMonths)) {
    $issueMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $issueCounts = [0, 0, 0, 0, 0, 0];
}

// Borrowing Trends
$borrowingTrendResult = $conn->query("
    SELECT DATE_FORMAT(borrowed_date, '%b') as month, COUNT(*) as count 
    FROM asset_borrowing 
    WHERE borrowed_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(borrowed_date, '%Y-%m')
    ORDER BY borrowed_date ASC
");
$borrowingMonths = [];
$borrowingCounts = [];
if ($borrowingTrendResult) {
    while ($row = $borrowingTrendResult->fetch_assoc()) {
        $borrowingMonths[] = $row['month'];
        $borrowingCounts[] = $row['count'];
    }
}
if (empty($borrowingMonths)) {
    $borrowingMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $borrowingCounts = [0, 0, 0, 0, 0, 0];
}

// ============================================
// RECENT ACTIVITY COUNT
// ============================================
$recentActivityCount = $conn->query("
    SELECT COUNT(*) as count 
    FROM activity_logs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetch_assoc()['count'];

// Recent users (distinct users with recent activity)
$recentUsersCount = $conn->query("
    SELECT COUNT(DISTINCT user_id) as count 
    FROM activity_logs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetch_assoc()['count'];



// Lab Staff Assignment Speed and Technician Resolution Speed removed.

// Include the layout header (includes sidebar and header components)
include '../components/layout_header.php';
?>
        <style>
            body, html { overflow: hidden !important; height: 100vh; }
            main { height: calc(100vh - 85px); }
            .kpi-card { transition: transform 0.15s ease, box-shadow 0.15s ease; }
            .kpi-card:hover { transform: translateY(-1px); }
        </style>
        <!-- Main Content -->
        <main class="px-3 py-2 bg-gray-50 overflow-hidden flex flex-col gap-2" style="height: calc(100vh - 85px);">

            <!-- Primary KPI Cards -->
            <div class="grid grid-cols-5 gap-2 flex-shrink-0">

                <!-- Total Assets -->
                <div class="kpi-card bg-white rounded-xl shadow-sm border border-gray-100 p-3 cursor-pointer hover:shadow-md hover:border-blue-100" onclick="openAssetModal('asset_status','all')">
                    <div class="flex items-start justify-between mb-2">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-blue-50">
                            <i class="fas fa-boxes-stacked text-sm text-blue-700"></i>
                        </div>
                        <span class="text-[9px] font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">Inventory</span>
                    </div>
                    <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider leading-none mb-1">Total Assets</p>
                    <p class="text-2xl font-bold text-gray-800 leading-tight"><?php echo number_format($totalAssets); ?></p>
                    <div class="mt-2 h-0.5 rounded-full" style="background:linear-gradient(to right,#1e3a8a,#3b82f6);"></div>
                </div>

                <!-- Available Assets -->
                <div class="kpi-card bg-white rounded-xl shadow-sm border border-gray-100 p-3 cursor-pointer hover:shadow-md hover:border-emerald-100" onclick="openAssetModal('asset_status','Available')">
                    <div class="flex items-start justify-between mb-2">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-emerald-50">
                            <i class="fas fa-check-circle text-sm text-emerald-600"></i>
                        </div>
                        <span class="text-[9px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Ready</span>
                    </div>
                    <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider leading-none mb-1">Available</p>
                    <p class="text-2xl font-bold text-gray-800 leading-tight"><?php echo number_format($availableAssets); ?></p>
                    <div class="mt-2 h-0.5 rounded-full" style="background:linear-gradient(to right,#059669,#34d399);"></div>
                </div>

                <!-- In Use Assets -->
                <div class="kpi-card bg-white rounded-xl shadow-sm border border-gray-100 p-3 cursor-pointer hover:shadow-md hover:border-indigo-100" onclick="openAssetModal('asset_status','In Use')">
                    <div class="flex items-start justify-between mb-2">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-indigo-50">
                            <i class="fas fa-laptop text-sm text-indigo-600"></i>
                        </div>
                        <span class="text-[9px] font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">Active</span>
                    </div>
                    <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider leading-none mb-1">In Use</p>
                    <p class="text-2xl font-bold text-gray-800 leading-tight"><?php echo number_format($inUseAssets); ?></p>
                    <div class="mt-2 h-0.5 rounded-full" style="background:linear-gradient(to right,#4338ca,#818cf8);"></div>
                </div>

                <!-- Health Score -->
                <div class="kpi-card bg-white rounded-xl shadow-sm border border-gray-100 p-3">
                    <div class="flex items-start justify-between mb-2">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-rose-50">
                            <i class="fas fa-heart-pulse text-sm text-rose-500"></i>
                        </div>
                        <span class="text-[9px] font-semibold text-rose-500 bg-rose-50 px-2 py-0.5 rounded-full">Health</span>
                    </div>
                    <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider leading-none mb-1">Health Score</p>
                    <p class="text-2xl font-bold text-gray-800 leading-tight"><?php echo $assetHealthPercentage; ?><span class="text-sm font-normal text-gray-400">%</span></p>
                    <div class="mt-2 h-0.5 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full" style="width:<?php echo min($assetHealthPercentage,100); ?>%;background:linear-gradient(to right,#e11d48,#fb7185);"></div>
                    </div>
                </div>

                <!-- Utilization Rate -->
                <div class="kpi-card bg-white rounded-xl shadow-sm border border-gray-100 p-3">
                    <div class="flex items-start justify-between mb-2">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-50">
                            <i class="fas fa-chart-line text-sm text-amber-500"></i>
                        </div>
                        <span class="text-[9px] font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Usage</span>
                    </div>
                    <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider leading-none mb-1">Utilization</p>
                    <p class="text-2xl font-bold text-gray-800 leading-tight"><?php echo $utilizationRate; ?><span class="text-sm font-normal text-gray-400">%</span></p>
                    <div class="mt-2 h-0.5 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full" style="width:<?php echo min($utilizationRate,100); ?>%;background:linear-gradient(to right,#d97706,#fbbf24);"></div>
                    </div>
                </div>

            </div>

            <!-- Secondary Metrics Row -->
            <div class="grid grid-cols-8 gap-2 flex-shrink-0">

                <div class="bg-white rounded-lg border border-gray-100 px-2.5 py-2 cursor-pointer hover:border-yellow-200 hover:shadow-sm transition-all" onclick="openAssetModal('borrowing_status','Pending')">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 flex-shrink-0"></span>
                        <p class="text-[9px] text-gray-400 font-medium leading-none truncate">Pending</p>
                    </div>
                    <p class="text-xl font-bold text-gray-800 leading-none"><?php echo $pendingApprovals; ?></p>
                    <p class="text-[8px] text-gray-400 mt-0.5">Approvals</p>
                </div>

                <div class="bg-white rounded-lg border border-gray-100 px-2.5 py-2 cursor-pointer hover:border-blue-200 hover:shadow-sm transition-all" onclick="openAssetModal('borrowing_status','Approved')">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400 flex-shrink-0"></span>
                        <p class="text-[9px] text-gray-400 font-medium leading-none truncate">Active</p>
                    </div>
                    <p class="text-xl font-bold text-gray-800 leading-none"><?php echo $activeBorrowings; ?></p>
                    <p class="text-[8px] text-gray-400 mt-0.5">Borrowings</p>
                </div>

                <div class="bg-white rounded-lg border border-gray-100 px-2.5 py-2 cursor-pointer hover:border-orange-200 hover:shadow-sm transition-all" onclick="openAssetModal('issue_status','Pending')">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-orange-400 flex-shrink-0"></span>
                        <p class="text-[9px] text-gray-400 font-medium leading-none truncate">Pending</p>
                    </div>
                    <p class="text-xl font-bold text-gray-800 leading-none"><?php echo $pendingIssues; ?></p>
                    <p class="text-[8px] text-gray-400 mt-0.5">Issues</p>
                </div>

                <div class="bg-white rounded-lg border border-gray-100 px-2.5 py-2 cursor-pointer hover:border-blue-200 hover:shadow-sm transition-all" onclick="openAssetModal('issue_status','In Progress')">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                        <p class="text-[9px] text-gray-400 font-medium leading-none truncate">In Progress</p>
                    </div>
                    <p class="text-xl font-bold text-gray-800 leading-none"><?php echo $inProgressIssues; ?></p>
                    <p class="text-[8px] text-gray-400 mt-0.5">Issues</p>
                </div>

                <div class="bg-white rounded-lg border border-gray-100 px-2.5 py-2 cursor-pointer hover:border-green-200 hover:shadow-sm transition-all" onclick="openAssetModal('issue_status','Resolved')">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400 flex-shrink-0"></span>
                        <p class="text-[9px] text-gray-400 font-medium leading-none truncate">Resolved</p>
                    </div>
                    <p class="text-xl font-bold text-gray-800 leading-none"><?php echo $resolvedIssues; ?></p>
                    <p class="text-[8px] text-gray-400 mt-0.5">Issues</p>
                </div>

                <div class="bg-white rounded-lg border border-gray-100 px-2.5 py-2 cursor-pointer hover:border-yellow-200 hover:shadow-sm transition-all" onclick="openAssetModal('asset_status','Under Maintenance')">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 flex-shrink-0"></span>
                        <p class="text-[9px] text-gray-400 font-medium leading-none truncate">Maintenance</p>
                    </div>
                    <p class="text-xl font-bold text-gray-800 leading-none"><?php echo $maintenanceAssets; ?></p>
                    <p class="text-[8px] text-gray-400 mt-0.5">Assets</p>
                </div>

                <div class="bg-white rounded-lg border border-gray-100 px-2.5 py-2">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-400 flex-shrink-0"></span>
                        <p class="text-[9px] text-gray-400 font-medium leading-none truncate">Avg Resolution</p>
                    </div>
                    <p class="text-xl font-bold text-gray-800 leading-none"><?php echo round($avgResolutionTime, 1); ?></p>
                    <p class="text-[8px] text-gray-400 mt-0.5">Days</p>
                </div>

                <div class="bg-white rounded-lg border border-gray-100 px-2.5 py-2">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-teal-400 flex-shrink-0"></span>
                        <p class="text-[9px] text-gray-400 font-medium leading-none truncate">Week Activity</p>
                    </div>
                    <p class="text-xl font-bold text-gray-800 leading-none"><?php echo $recentActivityCount; ?></p>
                    <p class="text-[8px] text-gray-400 mt-0.5">Actions</p>
                </div>

            </div>

            <!-- Charts Section -->
            <div class="flex gap-2" style="flex:1;min-height:0;max-height:280px;">

                <!-- Asset Type Distribution -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col min-w-0" style="flex:1;">
                    <div class="px-3 pt-2 pb-1.5 border-b border-gray-50 flex-shrink-0 flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-semibold text-gray-700">Asset Type Distribution</h3>
                            <p class="text-[9px] text-gray-400">By category</p>
                        </div>
                        <span class="text-[9px] font-medium text-blue-500 bg-blue-50 px-2 py-0.5 rounded-full">Click segment</span>
                    </div>
                    <div class="flex-1 min-h-0 relative">
                        <canvas id="typeChart" style="position:absolute;inset:6px;width:calc(100% - 12px)!important;height:calc(100% - 12px)!important;"></canvas>
                    </div>
                </div>

                <!-- Asset Acquisition Trend -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col min-w-0" style="flex:1;">
                    <div class="px-3 pt-2 pb-1.5 border-b border-gray-50 flex-shrink-0 flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-semibold text-gray-700">Asset Acquisition Trend</h3>
                            <p class="text-[9px] text-gray-400">Last 6 months</p>
                        </div>
                        <span class="text-[9px] font-medium text-blue-500 bg-blue-50 px-2 py-0.5 rounded-full">Click point</span>
                    </div>
                    <div class="flex-1 min-h-0 relative">
                        <canvas id="additionsChart" style="position:absolute;inset:6px;width:calc(100% - 12px)!important;height:calc(100% - 12px)!important;"></canvas>
                    </div>
                </div>

            </div>
        </main>

        <!-- Chart.js Library -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

        <script>
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#6B7280';

        const chartColors = {
            blue: '#1E3A8A', purple: '#2563eb', green: '#10B981',
            orange: '#F97316', teal: '#14B8A6', indigo: '#3b82f6',
            amber: '#F59E0B', pink: '#60a5fa'
        };

        const assetTypeLabels = <?php echo json_encode($assetTypes); ?>;

        // 1. Asset Type Distribution (Doughnut)
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: assetTypeLabels,
                datasets: [{
                    data: <?php echo json_encode($assetTypeCounts); ?>,
                    backgroundColor: [chartColors.blue, chartColors.purple, chartColors.green, chartColors.orange, chartColors.teal, chartColors.indigo, chartColors.pink],
                    borderWidth: 2, borderColor: '#fff'
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                onClick: function(event, elements) {
                    if (elements.length > 0) {
                        const assetType = assetTypeLabels[elements[0].index];
                        if (assetType && assetType !== 'No Data') openAssetModal('type', assetType);
                    }
                },
                plugins: { legend: { position: 'bottom', labels: { font: { size: 10 }, padding: 10 } } }
            }
        });
        document.getElementById('typeChart').style.cursor = 'pointer';

        const additionYearMonths = <?php echo json_encode($additionYearMonths); ?>;

        // 2. Asset Acquisition Trend (Line)
        const additionsCtx = document.getElementById('additionsChart').getContext('2d');
        new Chart(additionsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($additionMonths); ?>,
                datasets: [{
                    label: 'New Assets',
                    data: <?php echo json_encode($additionCounts); ?>,
                    borderColor: chartColors.blue,
                    backgroundColor: 'rgba(59,130,246,0.1)',
                    borderWidth: 2, fill: true, tension: 0.4,
                    pointRadius: 5, pointHoverRadius: 7,
                    pointBackgroundColor: chartColors.blue
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                onClick: function(event, elements) {
                    if (elements.length > 0) {
                        const ym = additionYearMonths[elements[0].index];
                        if (ym) openAssetModal('month', ym);
                    }
                },
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                }
            }
        });
        document.getElementById('additionsChart').style.cursor = 'pointer';

        // ── Modal helpers ────────────────────────────────────────────────
        function escHtml(str) {
            const d = document.createElement('div');
            d.appendChild(document.createTextNode(str ?? ''));
            return d.innerHTML;
        }

        function openAssetModal(filter, value) {
            const modal   = document.getElementById('assetDetailModal');
            const title   = document.getElementById('assetModalTitle');
            const count   = document.getElementById('assetModalCount');
            const loading = document.getElementById('assetModalLoading');
            const thead   = document.getElementById('assetModalThead');
            const tbody   = document.getElementById('assetModalBody');

            title.textContent = 'Loading…';
            count.textContent = '';
            loading.style.display = 'flex';
            thead.innerHTML = '';
            tbody.innerHTML = '';
            modal.classList.remove('hidden');

            fetch(`../../controller/get_dashboard_asset_details.php?filter=${encodeURIComponent(filter)}&value=${encodeURIComponent(value)}`)
                .then(r => r.text())
                .then(text => {
                    let data;
                    try { data = JSON.parse(text); }
                    catch(e) { console.error('Non-JSON:', text); throw new Error('Invalid JSON'); }

                    loading.style.display = 'none';
                    title.textContent = data.title || 'Details';
                    count.textContent = data.total ? `${data.total} record(s)` : '';

                    if (data.error) {
                        tbody.innerHTML = `<tr><td colspan="7" class="px-3 py-6 text-xs text-center text-red-500">Server error: ${escHtml(data.error)}</td></tr>`;
                        return;
                    }

                    const mode = data.mode || 'asset';

                    if (mode === 'borrowing') {
                        thead.innerHTML = `<tr>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Tag</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Asset Name</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Type</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Borrower</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Status</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Borrowed Date</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Return Date</th>
                        </tr>`;
                        if (data.assets && data.assets.length > 0) {
                            data.assets.forEach(a => {
                                const tr = document.createElement('tr');
                                tr.className = 'hover:bg-gray-50 transition-colors';
                                tr.innerHTML = `
                                    <td class="px-3 py-1.5 text-[11px] text-gray-600 whitespace-nowrap">${escHtml(a.asset_tag)}</td>
                                    <td class="px-3 py-1.5 text-[11px] font-medium text-gray-800">${escHtml(a.asset_name)}</td>
                                    <td class="px-3 py-1.5 text-[11px] text-gray-600">${escHtml(a.asset_type)}</td>
                                    <td class="px-3 py-1.5 text-[11px] text-gray-600">${escHtml(a.borrower ?? '—')}</td>
                                    <td class="px-3 py-1.5 text-[11px]"><span class="px-1.5 py-0.5 rounded text-[10px] font-medium ${a.status === 'Approved' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700'}">${escHtml(a.status)}</span></td>
                                    <td class="px-3 py-1.5 text-[11px] text-gray-500 whitespace-nowrap">${escHtml(a.borrowed_date ?? '—')}</td>
                                    <td class="px-3 py-1.5 text-[11px] text-gray-500 whitespace-nowrap">${escHtml(a.return_date ?? '—')}</td>`;
                                tbody.appendChild(tr);
                            });
                        } else {
                            tbody.innerHTML = '<tr><td colspan="7" class="px-3 py-6 text-xs text-center text-gray-400">No records found.</td></tr>';
                        }

                    } else if (mode === 'issue') {
                        thead.innerHTML = `<tr>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">#</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Issue Title</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Category</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Status</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Technician</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Room</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Reported</th>
                        </tr>`;
                        if (data.assets && data.assets.length > 0) {
                            data.assets.forEach(a => {
                                const tr = document.createElement('tr');
                                tr.className = 'hover:bg-gray-50 transition-colors';
                                const sc = a.status === 'Resolved' ? 'bg-green-100 text-green-700' : a.status === 'In Progress' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700';
                                tr.innerHTML = `
                                    <td class="px-3 py-1.5 text-[11px] text-gray-400">#${escHtml(String(a.id))}</td>
                                    <td class="px-3 py-1.5 text-[11px] font-medium text-gray-800">${escHtml(a.issue_title)}</td>
                                    <td class="px-3 py-1.5 text-[11px] text-gray-600">${escHtml(a.category ?? '—')}</td>
                                    <td class="px-3 py-1.5 text-[11px]"><span class="px-1.5 py-0.5 rounded text-[10px] font-medium ${sc}">${escHtml(a.status)}</span></td>
                                    <td class="px-3 py-1.5 text-[11px] text-gray-600">${escHtml(a.assigned_technician ?? 'Unassigned')}</td>
                                    <td class="px-3 py-1.5 text-[11px] text-gray-600">${escHtml(a.room ?? '—')}</td>
                                    <td class="px-3 py-1.5 text-[11px] text-gray-500 whitespace-nowrap">${escHtml(a.reported_date ?? '—')}</td>`;
                                tbody.appendChild(tr);
                            });
                        } else {
                            tbody.innerHTML = '<tr><td colspan="7" class="px-3 py-6 text-xs text-center text-gray-400">No records found.</td></tr>';
                        }

                    } else {
                        thead.innerHTML = `<tr>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Tag</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Asset Name</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Type</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Status</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Condition</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Location</th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide border-b">Acquired</th>
                        </tr>`;
                        if (data.assets && data.assets.length > 0) {
                            data.assets.forEach(a => {
                                const tr = document.createElement('tr');
                                tr.className = 'hover:bg-gray-50 transition-colors';
                                const sc = a.status === 'Available' ? 'bg-green-100 text-green-700' : a.status === 'In Use' ? 'bg-blue-100 text-blue-700' : a.status === 'Under Maintenance' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600';
                                tr.innerHTML = `
                                    <td class="px-3 py-1.5 text-[11px] text-gray-600 whitespace-nowrap">${escHtml(a.asset_tag)}</td>
                                    <td class="px-3 py-1.5 text-[11px] font-medium text-gray-800">${escHtml(a.asset_name)}</td>
                                    <td class="px-3 py-1.5 text-[11px] text-gray-600">${escHtml(a.asset_type)}</td>
                                    <td class="px-3 py-1.5 text-[11px]"><span class="px-1.5 py-0.5 rounded text-[10px] font-medium ${sc}">${escHtml(a.status)}</span></td>
                                    <td class="px-3 py-1.5 text-[11px] text-gray-600">${escHtml(a.condition)}</td>
                                    <td class="px-3 py-1.5 text-[11px] text-gray-600">${escHtml(a.location ?? '—')}</td>
                                    <td class="px-3 py-1.5 text-[11px] text-gray-500 whitespace-nowrap">${escHtml(a.acquired_date ?? '—')}</td>`;
                                tbody.appendChild(tr);
                            });
                        } else {
                            tbody.innerHTML = '<tr><td colspan="7" class="px-3 py-6 text-xs text-center text-gray-400">No assets found.</td></tr>';
                        }
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    loading.style.display = 'none';
                    tbody.innerHTML = '<tr><td colspan="7" class="px-3 py-6 text-xs text-center text-red-500">Error loading data. Please try again.</td></tr>';
                });
        }

        function closeAssetModal() {
            document.getElementById('assetDetailModal').classList.add('hidden');
        }
        document.getElementById('assetDetailModal').addEventListener('click', function(e) {
            if (e.target === this) closeAssetModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeAssetModal();
        });
        </script>

        <!-- Asset Detail Modal -->
        <div id="assetDetailModal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(15,23,42,0.55);backdrop-filter:blur(3px);">
            <div class="bg-white rounded-2xl shadow-2xl flex flex-col border border-gray-100" style="width:92%;max-width:880px;max-height:84vh;">
                <div class="flex items-center justify-between px-5 py-3.5 flex-shrink-0" style="background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);border-radius:1rem 1rem 0 0;">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:rgba(255,255,255,0.15);">
                            <i class="fas fa-table-list text-white text-xs"></i>
                        </div>
                        <div>
                            <h2 id="assetModalTitle" class="text-sm font-semibold text-white leading-none"></h2>
                            <span id="assetModalCount" class="text-[10px] text-blue-200"></span>
                        </div>
                    </div>
                    <button onclick="closeAssetModal()" class="w-7 h-7 rounded-lg flex items-center justify-center text-white hover:bg-white hover:bg-opacity-20" aria-label="Close">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                <div id="assetModalLoading" class="items-center justify-center p-8 text-xs text-gray-400" style="display:none;">
                    <svg class="animate-spin h-5 w-5 mr-2 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    Loading data&hellip;
                </div>
                <div class="flex-1 overflow-auto">
                    <table class="w-full border-collapse">
                        <thead id="assetModalThead" class="bg-gray-50 sticky top-0 z-10"></thead>
                        <tbody id="assetModalBody" class="divide-y divide-gray-50"></tbody>
                    </table>
                </div>
            </div>
        </div>

<?php include '../components/layout_footer.php'; ?>
