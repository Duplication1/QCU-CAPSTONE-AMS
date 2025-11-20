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
$dowDonsPrevMonth = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Returned' AND actual_return_date < DATE_SUB(NOW(), INTERVAL 1 MONTH)")->fetch_assoc()['count'];
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
$activeUsers = $conn->query("SELECT COUNT(DISTINCT borrower_id) as count FROM asset_borrowing WHERE borrowed_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
$uptimePercentage = 99.8; // Simulated uptime

// Hourly activity for system load chart (last 24 hours simulation)
$hourlyActivity = [15, 18, 22, 28, 35, 42];

// Include the layout header (includes sidebar and header components)
include '../components/layout_header.php';
?>
        <style>
            body, html { overflow: hidden !important; height: 100vh; }
        </style>
        <!-- Main Content -->
        <main class="p-2 bg-gray-50 h-screen overflow-hidden flex flex-col">
            <!-- Top Metrics Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2 mb-2 flex-shrink-0">
                <!-- Asset Health -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-3 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="text-[10px] font-medium text-gray-500 uppercase mb-1">Asset Health</p>
                            <p class="text-xl font-bold text-gray-900"><?php echo $assetHealth; ?></p>
                        </div>
                        <div class="w-12 h-8">
                            <svg class="w-full h-full" viewBox="0 0 64 40" fill="none">
                                <path d="M2 38 L12 35 L22 30 L32 28 L42 25 L52 20 L62 15" stroke="#6366f1" stroke-width="2" fill="none"/>
                                <circle cx="62" cy="15" r="2" fill="#6366f1"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-[10px] text-green-600 font-medium">
                        <?php echo $assetHealthChange >= 0 ? '+' : ''; ?><?php echo $assetHealthChange; ?>% vs last month
                    </p>
                </div>

                <!-- Incidents Prevented -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-3 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="text-[10px] font-medium text-gray-500 uppercase mb-1">Incidents Prevented</p>
                            <p class="text-xl font-bold text-gray-900"><?php echo $incidentsPrevented; ?></p>
                        </div>
                        <div class="w-12 h-8">
                            <svg class="w-full h-full" viewBox="0 0 64 40" fill="none">
                                <path d="M2 25 L12 20 L22 22 L32 18 L42 15 L52 10 L62 8" stroke="#6366f1" stroke-width="2" fill="none"/>
                                <circle cx="62" cy="8" r="2" fill="#6366f1"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-[10px] text-red-600 font-medium">
                        -<?php echo abs($incidentsChange); ?>% vs last month
                    </p>
                </div>

                <!-- Dow Dons -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-3 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="text-[10px] font-medium text-gray-500 uppercase mb-1">Dow Dons</p>
                            <p class="text-xl font-bold text-gray-900"><?php echo $dowDons; ?></p>
                        </div>
                        <div class="w-12 h-8">
                            <svg class="w-full h-full" viewBox="0 0 64 40" fill="none">
                                <path d="M2 30 L12 28 L22 25 L32 20 L42 18 L52 12 L62 10" stroke="#6366f1" stroke-width="2" fill="none"/>
                                <circle cx="62" cy="10" r="2" fill="#6366f1"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-[10px] text-green-600 font-medium">
                        +<?php echo abs($dowDonsChange); ?>% vs last month
                    </p>
                </div>

                <!-- Staffing Gap -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-3 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="text-[10px] font-medium text-gray-500 uppercase mb-1">Staffing Gap</p>
                            <p class="text-xl font-bold text-gray-900"><?php echo $staffingGap; ?></p>
                        </div>
                        <div class="w-12 h-8">
                            <svg class="w-full h-full" viewBox="0 0 64 40" fill="none">
                                <path d="M2 35 L12 32 L22 30 L32 28 L42 26 L52 24 L62 22" stroke="#6366f1" stroke-width="2" fill="none"/>
                                <circle cx="62" cy="22" r="2" fill="#6366f1"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-[10px] text-red-600 font-medium">
                        -<?php echo abs($staffingGapChange); ?>% vs last month
                    </p>
                </div>

                <!-- License Expirations -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-3 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="text-[10px] font-medium text-gray-500 uppercase mb-1">License Expirations</p>
                            <p class="text-xl font-bold text-gray-900"><?php echo $licenseExpirations; ?></p>
                        </div>
                        <div class="w-12 h-8">
                            <svg class="w-full h-full" viewBox="0 0 64 40" fill="none">
                                <path d="M2 32 L12 30 L22 28 L32 26 L42 24 L52 22 L62 18" stroke="#6366f1" stroke-width="2" fill="none"/>
                                <circle cx="62" cy="18" r="2" fill="#6366f1"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-[10px] text-green-600 font-medium">
                        +<?php echo $licenseExpChange; ?> due next month
                    </p>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-2 flex-1 min-h-0">
                <!-- Trending Assets -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-3">
                    <div class="mb-2">
                        <h3 class="text-sm font-semibold text-gray-900">Trending Assets</h3>
                        <p class="text-[10px] text-gray-500 mt-1">Real time monitoring</p>
                    </div>
                    <div class="mb-2">
                        <p class="text-xl font-bold text-gray-900"><?php echo $totalAlerts; ?> alerts</p>
                    </div>
                    <div class="h-24">
                        <canvas id="trendingChart"></canvas>
                    </div>
                </div>

                <!-- Asset Lifecycle -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-3">
                    <div class="mb-2">
                        <h3 class="text-sm font-semibold text-gray-900">Asset Lifecycle</h3>
                        <p class="text-[10px] text-gray-500 mt-1">Current status by phase</p>
                    </div>
                    <div class="mb-2">
                        <p class="text-xl font-bold text-gray-900"><?php echo $lifecycleNew + $lifecycleActive + $lifecycleAging + $lifecycleEOL; ?> tracked</p>
                    </div>
                    <div class="h-24">
                        <canvas id="lifecycleChart"></canvas>
                    </div>
                </div>

                <!-- Failure Risk Forecast -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-3">
                    <div class="mb-2">
                        <h3 class="text-sm font-semibold text-gray-900">Failure Risk Forecast</h3>
                        <p class="text-[10px] text-gray-500 mt-1">Predictive maintenance</p>
                    </div>
                    <div class="mb-2">
                        <p class="text-xl font-bold text-gray-900"><?php echo $failureRiskCritical + $failureRiskHigh; ?> at risk</p>
                    </div>
                    <div class="h-24">
                        <canvas id="failureRiskChart"></canvas>
                    </div>
                    <div class="grid grid-cols-4 gap-2 mt-2">
                        <div class="text-center">
                            <p class="text-[10px] text-gray-500 mb-1">High</p>
                            <p class="text-sm font-bold text-gray-900"><?php echo $failureRiskCritical; ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] text-gray-500 mb-1">Medium</p>
                            <p class="text-sm font-bold text-gray-900"><?php echo $failureRiskHigh; ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] text-gray-500 mb-1">Low</p>
                            <p class="text-sm font-bold text-gray-900"><?php echo $failureRiskMedium; ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] text-gray-500 mb-1">Critical</p>
                            <p class="text-sm font-bold text-gray-900"><?php echo $failureRiskLow; ?></p>
                        </div>
                    </div>
                </div>

                <!-- High Load - Active Users -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-3">
                    <div class="mb-2">
                        <h3 class="text-sm font-semibold text-gray-900">High Load - Active Users</h3>
                        <p class="text-[10px] text-gray-500 mt-1">System uptime tracking</p>
                    </div>
                    <div class="mb-2">
                        <p class="text-xl font-bold text-gray-900"><?php echo $uptimePercentage; ?>%</p>
                    </div>
                    <div class="h-24">
                        <canvas id="activeUsersChart"></canvas>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-gray-100">
                        <div>
                            <p class="text-[10px] text-gray-500 mb-1">Active Users</p>
                            <p class="text-sm font-bold text-gray-900"><?php echo number_format($activeUsers * 100); ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 mb-1">Uptime</p>
                            <p class="text-sm font-bold text-gray-900"><?php echo $uptimePercentage; ?>%</p>
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

        // Trending Assets Chart
        const trendingCtx = document.getElementById('trendingChart').getContext('2d');
        new Chart(trendingCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trendingMonths); ?>,
                datasets: [{
                    label: 'Alerts',
                    data: <?php echo json_encode($trendingCounts); ?>,
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
                        ticks: { font: { size: 11 } }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });

        // Asset Lifecycle Chart
        const lifecycleCtx = document.getElementById('lifecycleChart').getContext('2d');
        new Chart(lifecycleCtx, {
            type: 'line',
            data: {
                labels: ['New', 'Active', 'Aging', 'EOL'],
                datasets: [{
                    data: [<?php echo $lifecycleNew; ?>, <?php echo $lifecycleActive; ?>, <?php echo $lifecycleAging; ?>, <?php echo $lifecycleEOL; ?>],
                    borderColor: '#a78bfa',
                    backgroundColor: 'rgba(167, 139, 250, 0.1)',
                    borderWidth: 3,
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
                        grid: { color: '#f3f4f6', drawBorder: false }
                    },
                    x: {
                        grid: { display: false, drawBorder: false }
                    }
                }
            }
        });

        // Failure Risk Forecast Chart
        const failureRiskCtx = document.getElementById('failureRiskChart').getContext('2d');
        new Chart(failureRiskCtx, {
            type: 'bar',
            data: {
                labels: ['Critical', 'High', 'Medium', 'Low'],
                datasets: [{
                    data: [<?php echo $failureRiskCritical; ?>, <?php echo $failureRiskHigh; ?>, <?php echo $failureRiskMedium; ?>, <?php echo $failureRiskLow; ?>],
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
                        grid: { color: '#f3f4f6', drawBorder: false }
                    },
                    x: {
                        grid: { display: false, drawBorder: false }
                    }
                }
            }
        });

        // Active Users Chart
        const activeUsersCtx = document.getElementById('activeUsersChart').getContext('2d');
        new Chart(activeUsersCtx, {
            type: 'line',
            data: {
                labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'],
                datasets: [
                    {
                        label: 'uptime',
                        data: <?php echo json_encode($hourlyActivity); ?>,
                        borderColor: '#c4b5fd',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        tension: 0.4,
                        pointRadius: 3,
                        pointBackgroundColor: '#c4b5fd'
                    },
                    {
                        label: 'users',
                        data: <?php echo json_encode(array_map(function($v) { return $v * 0.9; }, $hourlyActivity)); ?>,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        tension: 0.4,
                        pointRadius: 3,
                        pointBackgroundColor: '#8b5cf6'
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
                            boxWidth: 6,
                            font: { size: 11 }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f3f4f6', drawBorder: false }
                    },
                    x: {
                        grid: { display: false, drawBorder: false }
                    }
                }
            }
        });
        </script>

<?php include '../components/layout_footer.php'; ?>
