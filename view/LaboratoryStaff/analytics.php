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

// ============================================
// ASSET USAGE METRICS
// ============================================
// Total assets by status
$availableAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Available'")->fetch_assoc()['count'];
$inUseAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'In Use'")->fetch_assoc()['count'];
$maintenanceAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Under Maintenance'")->fetch_assoc()['count'];
$totalAssets = $conn->query("SELECT COUNT(*) as count FROM assets")->fetch_assoc()['count'];

// Asset utilization rate
$utilizationRate = $totalAssets > 0 ? round(($inUseAssets / $totalAssets) * 100, 1) : 0;

// Borrowing statistics
$activeBorrows = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Approved'")->fetch_assoc()['count'];
$pendingBorrows = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Pending'")->fetch_assoc()['count'];
$totalBorrows = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Returned'")->fetch_assoc()['count'];

// ============================================
// EQUIPMENT CONDITION STATISTICS
// ============================================
$excellentCondition = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Excellent'")->fetch_assoc()['count'];
$goodCondition = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Good'")->fetch_assoc()['count'];
$fairCondition = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Fair'")->fetch_assoc()['count'];
$poorCondition = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Poor'")->fetch_assoc()['count'];
$nonFunctional = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Non-Functional'")->fetch_assoc()['count'];

// ============================================
// MAINTENANCE REPORTS
// ============================================
// Issues by status
$openIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'Open' AND (category IS NULL OR category != 'borrow')")->fetch_assoc()['count'];
$inProgressIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'In Progress' AND (category IS NULL OR category != 'borrow')")->fetch_assoc()['count'];
$resolvedIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'Resolved' AND (category IS NULL OR category != 'borrow')")->fetch_assoc()['count'];

// Maintenance by category (last 30 days)
$maintenanceByCategory = $conn->query("
    SELECT category, COUNT(*) as count 
    FROM issues 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND (category IS NOT NULL AND category != 'borrow')
    GROUP BY category
    ORDER BY count DESC
    LIMIT 5
");
$maintenanceCategories = [];
$maintenanceCounts = [];
if ($maintenanceByCategory && $maintenanceByCategory->num_rows > 0) {
    while ($row = $maintenanceByCategory->fetch_assoc()) {
        $maintenanceCategories[] = ucfirst($row['category']);
        $maintenanceCounts[] = $row['count'];
    }
}
if (empty($maintenanceCategories)) {
    $maintenanceCategories = ['No Issues'];
    $maintenanceCounts = [0];
}

// ============================================
// ASSET USAGE BY TYPE
// ============================================
$assetsByType = $conn->query("
    SELECT asset_type, COUNT(*) as count 
    FROM assets 
    GROUP BY asset_type 
    ORDER BY count DESC 
    LIMIT 6
");
$assetTypes = [];
$assetTypeCounts = [];
if ($assetsByType && $assetsByType->num_rows > 0) {
    while ($row = $assetsByType->fetch_assoc()) {
        $assetTypes[] = $row['asset_type'] ?: 'Uncategorized';
        $assetTypeCounts[] = $row['count'];
    }
}

// ============================================
// MONTHLY ASSET USAGE TREND (Last 6 months)
// ============================================
$usageTrendMonths = [];
$usageTrendCounts = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M', strtotime("-$i months"));
    $usageTrendMonths[] = $month;
}

$usageTrendResult = $conn->query("
    SELECT 
        DATE_FORMAT(borrowed_date, '%Y-%m') as month,
        COUNT(*) as count 
    FROM asset_borrowing 
    WHERE borrowed_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(borrowed_date, '%Y-%m')
    ORDER BY month ASC
");

$usageData = [];
while ($row = $usageTrendResult->fetch_assoc()) {
    $usageData[$row['month']] = $row['count'];
}

foreach ($usageTrendMonths as $month) {
    $yearMonth = date('Y-m', strtotime($month . ' ' . date('Y')));
    $usageTrendCounts[] = $usageData[$yearMonth] ?? 0;
}

// ============================================
// ROOM UTILIZATION
// ============================================
$roomUtilization = $conn->query("
    SELECT 
        r.name as room_name,
        COUNT(a.id) as asset_count,
        SUM(CASE WHEN a.status = 'In Use' THEN 1 ELSE 0 END) as in_use_count
    FROM rooms r
    LEFT JOIN assets a ON r.id = a.room_id
    GROUP BY r.id, r.name
    HAVING asset_count > 0
    ORDER BY asset_count DESC
    LIMIT 8
");
$roomNames = [];
$roomAssetCounts = [];
$roomInUseCounts = [];
if ($roomUtilization && $roomUtilization->num_rows > 0) {
    while ($row = $roomUtilization->fetch_assoc()) {
        $roomNames[] = $row['room_name'];
        $roomAssetCounts[] = $row['asset_count'];
        $roomInUseCounts[] = $row['in_use_count'];
    }
}

include '../components/layout_header.php';
?>

<style>
    body, html { overflow: hidden !important; height: 100vh; }
    main { height: calc(100vh - 85px); }
    
    .metric-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }
    
    .chart-container {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    
    .chart-container:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
</style>

<!-- Main Content -->
<main class="p-3 bg-gray-50 overflow-hidden flex flex-col">
    
    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="text-2xl font-bold text-gray-900">Asset Analytics Dashboard</h1>
        <p class="text-sm text-gray-600 mt-1">Comprehensive overview of asset usage, maintenance, and equipment conditions</p>
    </div>

    <!-- Key Metrics Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-4 flex-shrink-0">
        <!-- Total Assets -->
        <div class="metric-card bg-white rounded-lg shadow-sm p-3 border-l-4 border-blue-500">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-boxes text-blue-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mb-1 font-medium">Total Assets</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo $totalAssets; ?></p>
        </div>

        <!-- In Use -->
        <div class="metric-card bg-white rounded-lg shadow-sm p-3 border-l-4 border-green-500">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mb-1 font-medium">In Use</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo $inUseAssets; ?></p>
        </div>

        <!-- Available -->
        <div class="metric-card bg-white rounded-lg shadow-sm p-3 border-l-4 border-indigo-500">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                    <i class="fas fa-box-open text-indigo-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mb-1 font-medium">Available</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo $availableAssets; ?></p>
        </div>

        <!-- Under Maintenance -->
        <div class="metric-card bg-white rounded-lg shadow-sm p-3 border-l-4 border-amber-500">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                    <i class="fas fa-wrench text-amber-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mb-1 font-medium">Maintenance</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo $maintenanceAssets; ?></p>
        </div>

        <!-- Utilization Rate -->
        <div class="metric-card bg-white rounded-lg shadow-sm p-3 border-l-4 border-purple-500">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-chart-line text-purple-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mb-1 font-medium">Utilization</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo $utilizationRate; ?>%</p>
        </div>

        <!-- Active Borrows -->
        <div class="metric-card bg-white rounded-lg shadow-sm p-3 border-l-4 border-rose-500">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center">
                    <i class="fas fa-hand-holding text-rose-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mb-1 font-medium">Active Borrows</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo $activeBorrows; ?></p>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="flex-1 min-h-0 overflow-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 pb-4">
            
            <!-- Equipment Condition Distribution -->
            <div class="chart-container p-4">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Equipment Condition Statistics</h3>
                        <p class="text-xs text-gray-500 mt-1">Current condition of all assets</p>
                    </div>
                    <span class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-full font-semibold">
                        <?php echo $totalAssets; ?> Total
                    </span>
                </div>
                <div style="height: 280px;">
                    <canvas id="conditionChart"></canvas>
                </div>
            </div>

            <!-- Asset Usage by Type -->
            <div class="chart-container p-4">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Asset Distribution by Type</h3>
                        <p class="text-xs text-gray-500 mt-1">Breakdown of asset categories</p>
                    </div>
                    <span class="text-xs bg-purple-50 text-purple-700 px-2 py-1 rounded-full font-semibold">
                        <?php echo count($assetTypes); ?> Types
                    </span>
                </div>
                <div style="height: 280px;">
                    <canvas id="assetTypeChart"></canvas>
                </div>
            </div>

            <!-- Maintenance Reports -->
            <div class="chart-container p-4">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Maintenance Reports (Last 30 Days)</h3>
                        <p class="text-xs text-gray-500 mt-1">Issues by category</p>
                    </div>
                    <span class="text-xs bg-amber-50 text-amber-700 px-2 py-1 rounded-full font-semibold">
                        <?php echo array_sum($maintenanceCounts); ?> Issues
                    </span>
                </div>
                <div style="height: 280px;">
                    <canvas id="maintenanceChart"></canvas>
                </div>
            </div>

            <!-- Asset Usage Trend -->
            <div class="chart-container p-4">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Asset Usage Trend</h3>
                        <p class="text-xs text-gray-500 mt-1">Borrowing activity over last 6 months</p>
                    </div>
                    <span class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded-full font-semibold">
                        6 Months
                    </span>
                </div>
                <div style="height: 280px;">
                    <canvas id="usageTrendChart"></canvas>
                </div>
            </div>

            <!-- Room Utilization -->
            <div class="chart-container p-4 lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Room Utilization Analysis</h3>
                        <p class="text-xs text-gray-500 mt-1">Asset distribution and usage across rooms</p>
                    </div>
                    <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-1 rounded-full font-semibold">
                        Top <?php echo count($roomNames); ?> Rooms
                    </span>
                </div>
                <div style="height: 300px;">
                    <canvas id="roomUtilizationChart"></canvas>
                </div>
            </div>

            <!-- Maintenance Status Overview -->
            <div class="chart-container p-4 lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Maintenance Status Overview</h3>
                        <p class="text-xs text-gray-500 mt-1">Current status of all maintenance issues</p>
                    </div>
                    <span class="text-xs bg-rose-50 text-rose-700 px-2 py-1 rounded-full font-semibold">
                        <?php echo ($openIssues + $inProgressIssues + $resolvedIssues); ?> Total
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-red-50 rounded-lg">
                        <div class="text-3xl font-bold text-red-600"><?php echo $openIssues; ?></div>
                        <div class="text-sm text-gray-600 mt-1">Open Issues</div>
                        <div class="text-xs text-gray-500 mt-1">Requires attention</div>
                    </div>
                    <div class="text-center p-4 bg-amber-50 rounded-lg">
                        <div class="text-3xl font-bold text-amber-600"><?php echo $inProgressIssues; ?></div>
                        <div class="text-sm text-gray-600 mt-1">In Progress</div>
                        <div class="text-xs text-gray-500 mt-1">Being worked on</div>
                    </div>
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="text-3xl font-bold text-green-600"><?php echo $resolvedIssues; ?></div>
                        <div class="text-sm text-gray-600 mt-1">Resolved</div>
                        <div class="text-xs text-gray-500 mt-1">Completed</div>
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

// Color palette
const colors = {
    blue: '#3B82F6',
    green: '#10B981',
    amber: '#F59E0B',
    red: '#EF4444',
    purple: '#8B5CF6',
    indigo: '#6366F1',
    rose: '#F43F5E',
    gray: '#6B7280'
};

// 1. Equipment Condition Chart (Doughnut)
const conditionCtx = document.getElementById('conditionChart').getContext('2d');
new Chart(conditionCtx, {
    type: 'doughnut',
    data: {
        labels: ['Excellent', 'Good', 'Fair', 'Poor', 'Non-Functional'],
        datasets: [{
            data: [
                <?php echo $excellentCondition; ?>,
                <?php echo $goodCondition; ?>,
                <?php echo $fairCondition; ?>,
                <?php echo $poorCondition; ?>,
                <?php echo $nonFunctional; ?>
            ],
            backgroundColor: [
                colors.green,
                colors.blue,
                colors.amber,
                colors.red,
                colors.gray
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
                position: 'right',
                labels: {
                    padding: 15,
                    font: { size: 12 }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// 2. Asset Type Chart (Bar)
const assetTypeCtx = document.getElementById('assetTypeChart').getContext('2d');
new Chart(assetTypeCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($assetTypes); ?>,
        datasets: [{
            label: 'Number of Assets',
            data: <?php echo json_encode($assetTypeCounts); ?>,
            backgroundColor: colors.purple,
            borderRadius: 6,
            borderSkipped: false
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
                grid: { color: '#f3f4f6' },
                ticks: { font: { size: 11 } }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 } }
            }
        }
    }
});

// 3. Maintenance Chart (Horizontal Bar)
const maintenanceCtx = document.getElementById('maintenanceChart').getContext('2d');
new Chart(maintenanceCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($maintenanceCategories); ?>,
        datasets: [{
            label: 'Issues',
            data: <?php echo json_encode($maintenanceCounts); ?>,
            backgroundColor: colors.amber,
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        indexAxis: 'y',
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
            x: {
                beginAtZero: true,
                grid: { color: '#f3f4f6' },
                ticks: { font: { size: 11 } }
            },
            y: {
                grid: { display: false },
                ticks: { font: { size: 11 } }
            }
        }
    }
});

// 4. Usage Trend Chart (Line)
const usageTrendCtx = document.getElementById('usageTrendChart').getContext('2d');
new Chart(usageTrendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($usageTrendMonths); ?>,
        datasets: [{
            label: 'Borrowing Activity',
            data: <?php echo json_encode($usageTrendCounts); ?>,
            borderColor: colors.green,
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: colors.green,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointHoverRadius: 7
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
                grid: { color: '#f3f4f6' },
                ticks: { font: { size: 11 } }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 } }
            }
        }
    }
});

// 5. Room Utilization Chart (Grouped Bar)
const roomUtilizationCtx = document.getElementById('roomUtilizationChart').getContext('2d');
new Chart(roomUtilizationCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($roomNames); ?>,
        datasets: [
            {
                label: 'Total Assets',
                data: <?php echo json_encode($roomAssetCounts); ?>,
                backgroundColor: colors.indigo,
                borderRadius: 6,
                borderSkipped: false
            },
            {
                label: 'In Use',
                data: <?php echo json_encode($roomInUseCounts); ?>,
                backgroundColor: colors.green,
                borderRadius: 6,
                borderSkipped: false
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    padding: 15,
                    font: { size: 12 }
                }
            },
            tooltip: {
                backgroundColor: '#1f2937',
                padding: 12,
                cornerRadius: 8
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f3f4f6' },
                ticks: { font: { size: 11 } }
            },
            x: {
                grid: { display: false },
                ticks: { 
                    font: { size: 11 },
                    maxRotation: 45,
                    minRotation: 45
                }
            }
        }
    }
});
</script>

<?php include '../components/layout_footer.php'; ?>
