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
    SELECT DATE_FORMAT(created_at, '%b') as month, COUNT(*) as count 
    FROM assets 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
");
$additionMonths = [];
$additionCounts = [];
if ($monthlyAdditionsResult) {
    while ($row = $monthlyAdditionsResult->fetch_assoc()) {
        $additionMonths[] = $row['month'];
        $additionCounts[] = $row['count'];
    }
}
// Ensure we have data for charts
if (empty($additionMonths)) {
    $additionMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $additionCounts = [0, 0, 0, 0, 0, 0];
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
// TOP CATEGORIES
// ============================================
$topCategoriesResult = $conn->query("
    SELECT 
        COALESCE(ac.category_name, 'Uncategorized') as category_name, 
        COUNT(a.id) as count 
    FROM assets a
    LEFT JOIN asset_categories ac ON CAST(a.category AS UNSIGNED) = ac.id
    WHERE a.status NOT IN ('Disposed', 'Archive')
    GROUP BY ac.category_name
    ORDER BY count DESC
    LIMIT 5
");
$topCategories = [];
$topCategoryCounts = [];
if ($topCategoriesResult) {
    while ($row = $topCategoriesResult->fetch_assoc()) {
        $topCategories[] = $row['category_name'];
        $topCategoryCounts[] = $row['count'];
    }
}
// Ensure we have data for the chart
if (empty($topCategories)) {
    $topCategories = ['No Data'];
    $topCategoryCounts = [0];
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

// Include the layout header (includes sidebar and header components)
include '../components/layout_header.php';
?>
        <style>
            body, html { overflow: hidden !important; height: 100vh; }
            main { height: calc(100vh - 85px); }
        </style>
        <!-- Main Content -->
        <main class="p-2 bg-gray-50 overflow-hidden flex flex-col" style="height: calc(100vh - 85px);">
            <!-- Key Financial & Inventory Metrics -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 mb-2 flex-shrink-0">
                <!-- Total Asset Value -->
                <div class="bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
                    <div class="flex items-center justify-between mb-1">
                        <i class="fas fa-dollar-sign text-lg opacity-80"></i>
                        <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Financial</span>
                    </div>
                    <p class="text-[9px] opacity-70 mb-0.5">Total Asset Value</p>
                    <p class="text-lg font-bold">₱<?php echo number_format($totalAssetValue, 0); ?></p>
                </div>

                <!-- Total Assets -->
                <div class="bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
                    <div class="flex items-center justify-between mb-1">
                        <i class="fas fa-boxes-stacked text-lg opacity-80"></i>
                        <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Inventory</span>
                    </div>
                    <p class="text-[9px] opacity-70 mb-0.5">Total Assets</p>
                    <p class="text-lg font-bold"><?php echo number_format($totalAssets); ?></p>
                </div>

                <!-- Available Assets -->
                <div class="bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
                    <div class="flex items-center justify-between mb-1">
                        <i class="fas fa-check-circle text-lg opacity-80"></i>
                        <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Status</span>
                    </div>
                    <p class="text-[9px] opacity-70 mb-0.5">Available</p>
                    <p class="text-lg font-bold"><?php echo number_format($availableAssets); ?></p>
                </div>

                <!-- In Use Assets -->
                <div class="bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
                    <div class="flex items-center justify-between mb-1">
                        <i class="fas fa-laptop text-lg opacity-80"></i>
                        <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Active</span>
                    </div>
                    <p class="text-[9px] opacity-70 mb-0.5">In Use</p>
                    <p class="text-lg font-bold"><?php echo number_format($inUseAssets); ?></p>
                </div>

                <!-- Asset Health -->
                <div class="bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
                    <div class="flex items-center justify-between mb-1">
                        <i class="fas fa-heart-pulse text-lg opacity-80"></i>
                        <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Health</span>
                    </div>
                    <p class="text-[9px] opacity-70 mb-0.5">Health Score</p>
                    <p class="text-lg font-bold"><?php echo $assetHealthPercentage; ?>%</p>
                </div>

                <!-- Utilization Rate -->
                <div class="bg-white rounded-lg shadow-sm p-2" style="color: #1E3A8A;">
                    <div class="flex items-center justify-between mb-1">
                        <i class="fas fa-chart-line text-lg opacity-80"></i>
                        <span class="text-[9px] font-medium bg-blue-100 px-1.5 py-0.5 rounded">Usage</span>
                    </div>
                    <p class="text-[9px] opacity-70 mb-0.5">Utilization</p>
                    <p class="text-lg font-bold"><?php echo $utilizationRate; ?>%</p>
                </div>
            </div>

            <!-- Secondary Metrics Row -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-1.5 mb-2 flex-shrink-0">
                <!-- Pending Approvals -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
                    <p class="text-[9px] text-gray-500 mb-0.5">Pending Approvals</p>
                    <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $pendingApprovals; ?></p>
                </div>

                <!-- Active Borrowings -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
                    <p class="text-[9px] text-gray-500 mb-0.5">Active Borrowings</p>
                    <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $activeBorrowings; ?></p>
                </div>

                <!-- Pending Issues -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
                    <p class="text-[9px] text-gray-500 mb-0.5">Pending Issues</p>
                    <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $pendingIssues; ?></p>
                </div>

                <!-- In Progress Issues -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
                    <p class="text-[9px] text-gray-500 mb-0.5">In Progress</p>
                    <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $inProgressIssues; ?></p>
                </div>

                <!-- Resolved Issues -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
                    <p class="text-[9px] text-gray-500 mb-0.5">Resolved</p>
                    <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $resolvedIssues; ?></p>
                </div>

                <!-- Under Maintenance -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
                    <p class="text-[9px] text-gray-500 mb-0.5">Maintenance</p>
                    <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $maintenanceAssets; ?></p>
                </div>

                <!-- Avg Resolution Time -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
                    <p class="text-[9px] text-gray-500 mb-0.5">Avg Resolution</p>
                    <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo round($avgResolutionTime, 1); ?> days</p>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition-shadow">
                    <p class="text-[9px] text-gray-500 mb-0.5">Week Activity</p>
                    <p class="text-base font-bold" style="color: #1E3A8A;"><?php echo $recentActivityCount; ?></p>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="flex-1 min-h-0 overflow-hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2 h-full">
                    <!-- Asset Status Distribution -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Asset Status Distribution</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Current inventory status breakdown</p>
                        <div style="height: calc(100% - 2.5rem);">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>

                    <!-- Asset Condition Analysis -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Asset Condition Analysis</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Health assessment of all assets</p>
                        <div style="height: calc(100% - 2.5rem);">
                            <canvas id="conditionChart"></canvas>
                        </div>
                    </div>

                    <!-- Asset Type Distribution -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Asset Type Distribution</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Breakdown by asset category</p>
                        <div style="height: calc(100% - 2.5rem);">
                            <canvas id="typeChart"></canvas>
                        </div>
                    </div>

                    <!-- Monthly Asset Additions -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Asset Acquisition Trend</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Last 6 months additions</p>
                        <div style="height: calc(100% - 2.5rem);">
                            <canvas id="additionsChart"></canvas>
                        </div>
                    </div>

                    <!-- Issues Trend -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Issues Trend</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Support tickets over 6 months</p>
                        <div style="height: calc(100% - 2.5rem);">
                            <canvas id="issuesChart"></canvas>
                        </div>
                    </div>

                    <!-- Borrowing Activity -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Borrowing Activity</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Asset checkouts last 6 months</p>
                        <div style="height: calc(100% - 2.5rem);">
                            <canvas id="borrowingChart"></canvas>
                        </div>
                    </div>

                    <!-- Top Categories -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-0.5">Top Asset Categories</h3>
                        <p class="text-[9px] text-gray-500 mb-1">Most common asset types</p>
                        <div style="height: calc(100% - 2.5rem);">
                            <canvas id="topCategoriesChart"></canvas>
                        </div>
                    </div>

                    <!-- Quick Stats Summary -->
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                        <h3 class="text-xs font-semibold text-gray-900 mb-1.5">Quick Statistics</h3>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="border-l-2 pl-2" style="border-color: #1E3A8A;">
                                <p class="text-[9px] text-gray-500">Borrowable Assets</p>
                                <p class="text-base font-bold text-gray-900"><?php echo $borrowableAssets; ?></p>
                            </div>
                            <div class="border-l-2 pl-2" style="border-color: #10B981;">
                                <p class="text-[9px] text-gray-500">Total Returned</p>
                                <p class="text-base font-bold text-gray-900"><?php echo $totalReturned; ?></p>
                            </div>
                            <div class="border-l-2 pl-2" style="border-color: #EF4444;">
                                <p class="text-[9px] text-gray-500">Disposed Assets</p>
                                <p class="text-base font-bold text-gray-900"><?php echo $disposedAssets; ?></p>
                            </div>
                            <div class="border-l-2 pl-2" style="border-color: #F59E0B;">
                                <p class="text-[9px] text-gray-500">Poor Condition</p>
                                <p class="text-base font-bold text-gray-900"><?php echo $poorAssets + $nonFunctionalAssets; ?></p>
                            </div>
                            <div class="border-l-2 pl-2" style="border-color: #2563eb;">
                                <p class="text-[9px] text-gray-500">Avg Asset Cost</p>
                                <p class="text-base font-bold text-gray-900">₱<?php echo number_format($avgAssetCost, 0); ?></p>
                            </div>
                            <div class="border-l-2 pl-2" style="border-color: #14B8A6;">
                                <p class="text-[9px] text-gray-500">Active Users (7d)</p>
                                <p class="text-base font-bold text-gray-900"><?php echo $recentUsersCount; ?></p>
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
            purple: '#2563eb',
            green: '#10B981',
            orange: '#F97316',
            red: '#EF4444',
            teal: '#14B8A6',
            indigo: '#3b82f6',
            amber: '#F59E0B',
            pink: '#60a5fa',
            cyan: '#1e40af'
        };

        // 1. Asset Status Distribution (Doughnut Chart)
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Available', 'In Use', 'Maintenance', 'Disposed'],
                datasets: [{
                    data: [
                        <?php echo $availableAssets; ?>,
                        <?php echo $inUseAssets; ?>,
                        <?php echo $maintenanceAssets; ?>,
                        <?php echo $disposedAssets; ?>
                    ],
                    backgroundColor: [
                        chartColors.green,
                        chartColors.orange,
                        chartColors.amber,
                        chartColors.red
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
                        labels: { font: { size: 10 }, padding: 10 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // 2. Asset Condition Analysis (Bar Chart)
        const conditionCtx = document.getElementById('conditionChart').getContext('2d');
        new Chart(conditionCtx, {
            type: 'bar',
            data: {
                labels: ['Excellent', 'Good', 'Fair', 'Poor', 'Non-Functional'],
                datasets: [{
                    label: 'Assets',
                    data: [
                        <?php echo $excellentAssets; ?>,
                        <?php echo $goodAssets; ?>,
                        <?php echo $fairAssets; ?>,
                        <?php echo $poorAssets; ?>,
                        <?php echo $nonFunctionalAssets; ?>
                    ],
                    backgroundColor: [
                        chartColors.green,
                        chartColors.teal,
                        chartColors.amber,
                        chartColors.orange,
                        chartColors.red
                    ],
                    borderRadius: 6
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

        // 3. Asset Type Distribution (Doughnut Chart)
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($assetTypes); ?>,
                datasets: [{
                    data: <?php echo json_encode($assetTypeCounts); ?>,
                    backgroundColor: [
                        chartColors.blue,
                        chartColors.purple,
                        chartColors.green,
                        chartColors.orange,
                        chartColors.teal,
                        chartColors.indigo,
                        chartColors.pink
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
                        labels: { font: { size: 10 }, padding: 10 }
                    }
                }
            }
        });

        // 4. Monthly Asset Additions (Line Chart)
        const additionsCtx = document.getElementById('additionsChart').getContext('2d');
        new Chart(additionsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($additionMonths); ?>,
                datasets: [{
                    label: 'New Assets',
                    data: <?php echo json_encode($additionCounts); ?>,
                    borderColor: chartColors.blue,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
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

        // 5. Issues Trend (Line Chart)
        const issuesCtx = document.getElementById('issuesChart').getContext('2d');
        new Chart(issuesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($issueMonths); ?>,
                datasets: [{
                    label: 'Issues',
                    data: <?php echo json_encode($issueCounts); ?>,
                    borderColor: chartColors.red,
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: chartColors.red
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

        // 6. Borrowing Activity (Bar Chart)
        const borrowingCtx = document.getElementById('borrowingChart').getContext('2d');
        new Chart(borrowingCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($borrowingMonths); ?>,
                datasets: [{
                    label: 'Borrowings',
                    data: <?php echo json_encode($borrowingCounts); ?>,
                    backgroundColor: chartColors.purple,
                    borderRadius: 6
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

        // 7. Top Categories (Horizontal Bar Chart)
        const topCategoriesCtx = document.getElementById('topCategoriesChart').getContext('2d');
        new Chart(topCategoriesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($topCategories); ?>,
                datasets: [{
                    label: 'Count',
                    data: <?php echo json_encode($topCategoryCounts); ?>,
                    backgroundColor: [
                        chartColors.blue,
                        chartColors.green,
                        chartColors.orange,
                        chartColors.purple,
                        chartColors.teal
                    ],
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: '#f3f4f6' },
                        ticks: { font: { size: 10 } }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
        </script>

<?php include '../components/layout_footer.php'; ?>
