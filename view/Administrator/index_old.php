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

// Fetch dashboard data
// Asset Health - Good/Excellent condition
$assetHealth = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` IN ('Excellent', 'Good')")->fetch_assoc()['count'];
$assetHealthPrevMonth = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` IN ('Excellent', 'Good') AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)")->fetch_assoc()['count'];
$assetHealthChange = $assetHealthPrevMonth > 0 ? round((($assetHealth - $assetHealthPrevMonth) / $assetHealthPrevMonth) * 100, 1) : 0;

// Incidents Prevented - Resolved issues
$incidentsPrevented = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'Resolved'")->fetch_assoc()['count'];
$incidentsPreventedPrevMonth = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'Resolved' AND updated_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)")->fetch_assoc()['count'];
$incidentsChange = $incidentsPreventedPrevMonth > 0 ? round((($incidentsPrevented - $incidentsPreventedPrevMonth) / $incidentsPreventedPrevMonth) * 100, 1) : 0;

// Dow Dons - Completed tasks/returns
$dowDons = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Returned'")->fetch_assoc()['count'];
$dowDonsPrevMonth = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Returned' AND return_date < DATE_SUB(NOW(), INTERVAL 1 MONTH)")->fetch_assoc()['count'];
$dowDonsChange = $dowDonsPrevMonth > 0 ? round((($dowDons - $dowDonsPrevMonth) / $dowDonsPrevMonth) * 100, 1) : 0;

// Staffing Gap - Pending approvals
$staffingGap = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Pending'")->fetch_assoc()['count'];
$staffingGapPrevMonth = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Pending' AND borrowed_date < DATE_SUB(NOW(), INTERVAL 1 MONTH)")->fetch_assoc()['count'];
$staffingGapChange = $staffingGapPrevMonth > 0 ? round((($staffingGap - $staffingGapPrevMonth) / $staffingGapPrevMonth) * 100, 1) : 0;

// License Expirations - Maintenance due
$licenseExpirations = $conn->query("SELECT COUNT(*) as count FROM assets WHERE next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc()['count'];
$licenseExpirationsPrevMonth = $conn->query("SELECT COUNT(*) as count FROM assets WHERE next_maintenance_date <= DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), INTERVAL 30 DAY)")->fetch_assoc()['count'];
$licenseExpChange = $licenseExpirationsPrevMonth > 0 ? round((($licenseExpirations - $licenseExpirationsPrevMonth) / $licenseExpirationsPrevMonth) * 100, 1) : 0;

// Trending Assets - Last 6 months alert data
$trendingAlerts = $conn->query("
    SELECT DATE_FORMAT(created_at, '%b') as month, COUNT(*) as count 
    FROM issues 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY MONTH(created_at)
    ORDER BY created_at ASC
");
$trendingMonths = [];
$trendingCounts = [];
while ($row = $trendingAlerts->fetch_assoc()) {
    $trendingMonths[] = $row['month'];
    $trendingCounts[] = $row['count'];
}
$totalAlerts = array_sum($trendingCounts);

// Asset Lifecycle data
$lifecycleNew = $conn->query("SELECT COUNT(*) as count FROM assets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)")->fetch_assoc()['count'];
$lifecycleActive = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status IN ('Available', 'In Use') AND created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)")->fetch_assoc()['count'];
$lifecycleAging = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` IN ('Fair', 'Poor')")->fetch_assoc()['count'];
$lifecycleEOL = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Non-Functional'")->fetch_assoc()['count'];

// Failure Risk Forecast
$failureRiskCritical = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Non-Functional'")->fetch_assoc()['count'];
$failureRiskHigh = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Poor'")->fetch_assoc()['count'];
$failureRiskMedium = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Fair'")->fetch_assoc()['count'];
$failureRiskLow = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` IN ('Good', 'Excellent')")->fetch_assoc()['count'];

// High Load - Active Users (simulated with recent activity)
$activeUsers = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM asset_borrowing WHERE borrowed_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
$uptimePercentage = 99.8; // Simulated uptime

// Hourly activity for system load chart (last 24 hours simulation)
$hourlyActivity = [];
for ($i = 0; $i <= 20; $i += 4) {
    $hourlyActivity[] = rand(15, 25) + ($i * 2); // Simulated increasing activity
}

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
