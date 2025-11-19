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

// Fetch analytics data
// 1. Total counts
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$totalAssets = $conn->query("SELECT COUNT(*) as count FROM assets")->fetch_assoc()['count'];
$pendingBorrowings = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Pending'")->fetch_assoc()['count'];
$activeIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status IN ('Open', 'In Progress')")->fetch_assoc()['count'];

// 2. Users by role
$usersByRole = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY count DESC");
$roleLabels = [];
$roleCounts = [];
while ($row = $usersByRole->fetch_assoc()) {
    $roleLabels[] = $row['role'];
    $roleCounts[] = $row['count'];
}

// 3. Assets by type
$assetsByType = $conn->query("SELECT asset_type, COUNT(*) as count FROM assets GROUP BY asset_type ORDER BY count DESC");
$assetTypeLabels = [];
$assetTypeCounts = [];
while ($row = $assetsByType->fetch_assoc()) {
    $assetTypeLabels[] = $row['asset_type'];
    $assetTypeCounts[] = $row['count'];
}

// 4. Assets by status
$assetsByStatus = $conn->query("SELECT status, COUNT(*) as count FROM assets GROUP BY status ORDER BY count DESC");
$statusLabels = [];
$statusCounts = [];
while ($row = $assetsByStatus->fetch_assoc()) {
    $statusLabels[] = $row['status'];
    $statusCounts[] = $row['count'];
}

// 5. Assets by condition
$assetsByCondition = $conn->query("SELECT `condition`, COUNT(*) as count FROM assets GROUP BY `condition` ORDER BY 
    FIELD(`condition`, 'Excellent', 'Good', 'Fair', 'Poor', 'Non-Functional')");
$conditionLabels = [];
$conditionCounts = [];
while ($row = $assetsByCondition->fetch_assoc()) {
    $conditionLabels[] = $row['condition'];
    $conditionCounts[] = $row['count'];
}

// 6. Borrowing trends (last 6 months)
$borrowingTrends = $conn->query("
    SELECT DATE_FORMAT(borrowed_date, '%Y-%m') as month, COUNT(*) as count 
    FROM asset_borrowing 
    WHERE borrowed_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month 
    ORDER BY month ASC
");
$borrowingMonths = [];
$borrowingCounts = [];
while ($row = $borrowingTrends->fetch_assoc()) {
    $borrowingMonths[] = date('M Y', strtotime($row['month'] . '-01'));
    $borrowingCounts[] = $row['count'];
}

// 7. Top 5 most borrowed assets
$topBorrowedAssets = $conn->query("
    SELECT a.asset_name, a.asset_tag, COUNT(ab.id) as borrow_count
    FROM assets a
    INNER JOIN asset_borrowing ab ON a.id = ab.asset_id
    GROUP BY a.id
    ORDER BY borrow_count DESC
    LIMIT 5
");
$topAssetNames = [];
$topAssetCounts = [];
while ($row = $topBorrowedAssets->fetch_assoc()) {
    $topAssetNames[] = $row['asset_name'] . ' (' . $row['asset_tag'] . ')';
    $topAssetCounts[] = $row['borrow_count'];
}

// 8. Monthly asset acquisition (last 12 months)
$assetAcquisition = $conn->query("
    SELECT DATE_FORMAT(purchase_date, '%Y-%m') as month, COUNT(*) as count 
    FROM assets 
    WHERE purchase_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY month 
    ORDER BY month ASC
");
$acquisitionMonths = [];
$acquisitionCounts = [];
while ($row = $assetAcquisition->fetch_assoc()) {
    $acquisitionMonths[] = date('M Y', strtotime($row['month'] . '-01'));
    $acquisitionCounts[] = $row['count'];
}

// 9. Maintenance statistics
$dueMaintenance = $conn->query("SELECT COUNT(*) as count FROM assets WHERE next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND next_maintenance_date >= CURDATE()")->fetch_assoc()['count'];
$overdueMaintenance = $conn->query("SELECT COUNT(*) as count FROM assets WHERE next_maintenance_date < CURDATE()")->fetch_assoc()['count'];

// 11. Recent user registrations (last 30 days)
$newUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'];

// 12. Asset utilization rate
$inUseAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'In Use'")->fetch_assoc()['count'];
$utilizationRate = $totalAssets > 0 ? round(($inUseAssets / $totalAssets) * 100, 1) : 0;

// Include the layout header (includes sidebar and header components)
include '../components/layout_header.php';
?>

        <!-- Main Content -->
        <main class="p-3 h-screen overflow-hidden">
            <!-- Key Performance Indicators -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                <!-- Total Assets -->
                <div class="bg-gradient-to-br from-[#1E3A8A] to-[#1e40af] rounded-lg shadow-lg p-3 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs font-medium text-blue-100 mb-1">Total Assets</p>
                    <p class="text-2xl font-bold"><?php echo $totalAssets; ?></p>
                    <p class="text-xs text-blue-100 mt-1">Inventory items</p>
                </div>

                <!-- Asset Utilization Rate -->
                <div class="bg-gradient-to-br from-[#1E3A8A] to-[#1e40af] rounded-lg shadow-lg p-3 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs font-medium text-blue-100 mb-1">Utilization Rate</p>
                    <p class="text-2xl font-bold"><?php echo $utilizationRate; ?>%</p>
                    <p class="text-xs text-blue-100 mt-1"><?php echo $inUseAssets; ?> assets in use</p>
                </div>

                <!-- Pending Actions -->
                <div class="bg-gradient-to-br from-[#1E3A8A] to-[#1e40af] rounded-lg shadow-lg p-3 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs font-medium text-blue-100 mb-1">Pending Requests</p>
                    <p class="text-2xl font-bold"><?php echo $pendingBorrowings; ?></p>
                    <p class="text-xs text-blue-100 mt-1">Requires approval</p>
                </div>

                <!-- Active Users -->
                <div class="bg-gradient-to-br from-[#1E3A8A] to-[#1e40af] rounded-lg shadow-lg p-3 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs font-medium text-blue-100 mb-1">Total Users</p>
                    <p class="text-2xl font-bold"><?php echo $totalUsers; ?></p>
                    <p class="text-xs text-blue-100 mt-1">+<?php echo $newUsers; ?> this month</p>
                </div>
            </div>

            <!-- Charts Row: All Charts in One Row -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-3 mb-3">
                <!-- Asset Type Distribution -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Asset Types</h3>
                    <div class="h-48">
                        <canvas id="assetTypeChart"></canvas>
                    </div>
                </div>

                <!-- Asset Status Overview -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Asset Status</h3>
                    <div class="h-48">
                        <canvas id="assetStatusChart"></canvas>
                    </div>
                </div>

                <!-- User Distribution by Role -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Users by Role</h3>
                    <div class="h-48">
                        <canvas id="userRoleChart"></canvas>
                    </div>
                </div>

                <!-- Asset Condition Analysis -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Asset Condition</h3>
                    <div class="h-48">
                        <canvas id="assetConditionChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Trends and Insights -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
                <!-- Borrowing Trends -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Borrowing Trends (6 Months)</h3>
                    <div class="h-40">
                        <canvas id="borrowingTrendsChart"></canvas>
                    </div>
                </div>

                <!-- Asset Acquisition Timeline -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Asset Acquisition (12 Months)</h3>
                    <div class="h-40">
                        <canvas id="acquisitionChart"></canvas>
                    </div>
                </div>

                <!-- Top Borrowed Assets -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Top 5 Borrowed Assets</h3>
                    <div class="h-40">
                        <canvas id="topBorrowedChart"></canvas>
                </div>
            </div>
        </main>

        <!-- Chart.js Library -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        
        <script>
        // Chart color schemes - using header blue theme
        const colors = {
            primary: ['#1E3A8A', '#2563EB', '#3B82F6', '#60A5FA', '#93C5FD', '#BFDBFE', '#DBEAFE', '#1e40af'],
            blue: '#1E3A8A'
        };

        // Chart.js default settings
        Chart.defaults.font.family = 'Poppins, sans-serif';
        Chart.defaults.font.size = 10;
        Chart.defaults.color = '#6B7280';

        // 1. Asset Type Distribution (Doughnut Chart)
        const assetTypeCtx = document.getElementById('assetTypeChart').getContext('2d');
        new Chart(assetTypeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($assetTypeLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($assetTypeCounts); ?>,
                    backgroundColor: colors.primary,
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 5,
                            font: { size: 9 },
                            usePointStyle: true,
                            boxWidth: 8
                        }
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

        // 2. Asset Status Overview (Pie Chart)
        const assetStatusCtx = document.getElementById('assetStatusChart').getContext('2d');
        new Chart(assetStatusCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($statusLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($statusCounts); ?>,
                    backgroundColor: colors.primary,
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 5,
                            font: { size: 9 },
                            usePointStyle: true,
                            boxWidth: 8
                        }
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

        // 3. User Distribution by Role (Polar Area Chart)
        const userRoleCtx = document.getElementById('userRoleChart').getContext('2d');
        new Chart(userRoleCtx, {
            type: 'polarArea',
            data: {
                labels: <?php echo json_encode($roleLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($roleCounts); ?>,
                    backgroundColor: colors.primary.map(c => c + 'CC'),
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 5,
                            font: { size: 9 },
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 8 } }
                    }
                }
            }
        });

        // 4. Asset Condition Analysis (Bar Chart)
        const assetConditionCtx = document.getElementById('assetConditionChart').getContext('2d');
        new Chart(assetConditionCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($conditionLabels); ?>,
                datasets: [{
                    label: 'Assets',
                    data: <?php echo json_encode($conditionCounts); ?>,
                    backgroundColor: [
                        'rgba(30, 58, 138, 0.9)',
                        'rgba(30, 64, 175, 0.8)',
                        'rgba(37, 99, 235, 0.7)',
                        'rgba(59, 130, 246, 0.6)',
                        'rgba(96, 165, 250, 0.5)'
                    ],
                    borderColor: ['#1E3A8A', '#1e40af', '#2563eb', '#3B82F6', '#60A5FA'],
                    borderWidth: 1,
                    borderRadius: 4
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
                        ticks: { stepSize: 1, font: { size: 9 } },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: {
                        ticks: { font: { size: 9 } },
                        grid: { display: false }
                    }
                }
            }
        });

        // 5. Borrowing Trends (Line Chart)
        const borrowingTrendsCtx = document.getElementById('borrowingTrendsChart').getContext('2d');
        new Chart(borrowingTrendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($borrowingMonths); ?>,
                datasets: [{
                    label: 'Requests',
                    data: <?php echo json_encode($borrowingCounts); ?>,
                    borderColor: colors.blue,
                    backgroundColor: 'rgba(30, 58, 138, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: colors.blue,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1
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
                        ticks: { stepSize: 1, font: { size: 9 } },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: {
                        ticks: { font: { size: 9 } },
                        grid: { display: false }
                    }
                }
            }
        });

        // 6. Asset Acquisition Timeline (Area Chart)
        const acquisitionCtx = document.getElementById('acquisitionChart').getContext('2d');
        new Chart(acquisitionCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($acquisitionMonths); ?>,
                datasets: [{
                    label: 'Acquired',
                    data: <?php echo json_encode($acquisitionCounts); ?>,
                    borderColor: colors.blue,
                    backgroundColor: 'rgba(30, 58, 138, 0.15)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: colors.blue,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1
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
                        ticks: { stepSize: 1, font: { size: 9 } },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: {
                        ticks: { font: { size: 9 } },
                        grid: { display: false }
                    }
                }
            }
        });

        // 7. Top Borrowed Assets (Horizontal Bar Chart)
        const topBorrowedCtx = document.getElementById('topBorrowedChart').getContext('2d');
        new Chart(topBorrowedCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($topAssetNames); ?>,
                datasets: [{
                    label: 'Times',
                    data: <?php echo json_encode($topAssetCounts); ?>,
                    backgroundColor: 'rgba(30, 58, 138, 0.8)',
                    borderColor: '#1E3A8A',
                    borderWidth: 1,
                    borderRadius: 4
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
                        ticks: { stepSize: 1, font: { size: 9 } },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    y: {
                        ticks: { font: { size: 8 } },
                        grid: { display: false }
                    }
                }
            }
        });
        </script>

<?php include '../components/layout_footer.php'; ?>
