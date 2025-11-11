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
include '../components/layout_header.php';
?>

<style>
    .stat-card {
        @apply bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg p-6 border border-gray-100;
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        @apply shadow-xl transform -translate-y-1;
    }
    .chart-container {
        @apply bg-white rounded-xl shadow-lg p-6 border border-gray-100;
    }
    .risk-badge {
        @apply inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold;
    }
    .risk-high { @apply bg-red-100 text-red-800; }
    .risk-medium { @apply bg-yellow-100 text-yellow-800; }
    .risk-low { @apply bg-green-100 text-green-800; }
    
    .urgency-overdue { @apply bg-red-100 text-red-800 border-red-300; }
    .urgency-week { @apply bg-orange-100 text-orange-800 border-orange-300; }
    .urgency-month { @apply bg-yellow-100 text-yellow-800 border-yellow-300; }
    
    .condition-excellent { @apply text-green-600; }
    .condition-good { @apply text-blue-600; }
    .condition-fair { @apply text-yellow-600; }
    .condition-poor { @apply text-orange-600; }
    .condition-non-functional { @apply text-red-600; }
</style>

        <!-- Main Content -->
        <main class="p-6">
            <!-- Page Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-lg p-8 mb-6 text-white">
                <h2 class="text-3xl font-bold mb-2">
                    <i class="fas fa-chart-line mr-3"></i>Maintenance Analytics Dashboard
                </h2>
                <p class="text-blue-100">Monitor asset health, predict maintenance needs, and identify high-risk equipment</p>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="text-center py-12">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                <p class="text-gray-600">Loading analytics data...</p>
            </div>

            <!-- Error State -->
            <div id="errorState" class="hidden bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                <i class="fas fa-exclamation-circle text-4xl text-red-600 mb-4"></i>
                <p class="text-red-800 font-semibold mb-2">Failed to load analytics data</p>
                <p class="text-red-600 text-sm" id="errorMessage"></p>
                <button onclick="loadAnalytics()" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-redo mr-2"></i>Retry
                </button>
            </div>

            <!-- Analytics Content -->
            <div id="analyticsContent" class="hidden">
                <!-- Summary Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-tools text-2xl text-blue-600"></i>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Total Maintenance</p>
                        <p class="text-3xl font-bold text-gray-800" id="stat-total-maintenance">0</p>
                        <p class="text-xs text-gray-500 mt-2">All completed records</p>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-red-100 rounded-lg">
                                <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Overdue Maintenance</p>
                        <p class="text-3xl font-bold text-red-600" id="stat-overdue">0</p>
                        <p class="text-xs text-gray-500 mt-2">Requires immediate attention</p>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-orange-100 rounded-lg">
                                <i class="fas fa-heartbeat text-2xl text-orange-600"></i>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Poor Condition</p>
                        <p class="text-3xl font-bold text-orange-600" id="stat-poor-condition">0</p>
                        <p class="text-xs text-gray-500 mt-2">Assets needing attention</p>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-peso-sign text-2xl text-green-600"></i>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Total Cost</p>
                        <p class="text-3xl font-bold text-gray-800" id="stat-total-cost">₱0</p>
                        <p class="text-xs text-gray-500 mt-2">Maintenance expenses</p>
                    </div>
                </div>

                <!-- Main Analytics Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Frequent Maintenance Assets -->
                    <div class="chart-container">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-800">
                                <i class="fas fa-wrench text-blue-600 mr-2"></i>
                                Assets Requiring Maintenance Most Often
                            </h3>
                            <span class="text-sm text-gray-500" id="frequent-count">0 assets</span>
                        </div>
                        <div class="overflow-x-auto max-h-96 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asset</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Maint.</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Issues</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Condition</th>
                                    </tr>
                                </thead>
                                <tbody id="frequent-maintenance-table" class="bg-white divide-y divide-gray-200">
                                    <!-- Populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- High Risk Assets -->
                    <div class="chart-container">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-800">
                                <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                                High Risk Assets
                            </h3>
                            <span class="text-sm text-gray-500" id="risk-count">0 assets</span>
                        </div>
                        <div class="overflow-x-auto max-h-96 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asset</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Risk Score</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Condition</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cost</th>
                                    </tr>
                                </thead>
                                <tbody id="high-risk-table" class="bg-white divide-y divide-gray-200">
                                    <!-- Populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Maintenance by Type -->
                    <div class="chart-container">
                        <h3 class="text-xl font-bold text-gray-800 mb-6">
                            <i class="fas fa-chart-pie text-purple-600 mr-2"></i>
                            Maintenance by Asset Type
                        </h3>
                        <div style="position: relative; height: 300px;">
                            <canvas id="maintenanceByTypeChart"></canvas>
                        </div>
                    </div>

                    <!-- Condition Distribution -->
                    <div class="chart-container">
                        <h3 class="text-xl font-bold text-gray-800 mb-6">
                            <i class="fas fa-chart-bar text-green-600 mr-2"></i>
                            Asset Condition Distribution
                        </h3>
                        <div style="position: relative; height: 300px;">
                            <canvas id="conditionChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Timeline Chart -->
                <div class="chart-container mb-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-chart-line text-indigo-600 mr-2"></i>
                        Maintenance Timeline (Last 12 Months)
                    </h3>
                    <div style="position: relative; height: 300px;">
                        <canvas id="timelineChart"></canvas>
                    </div>
                </div>

                <!-- Upcoming Maintenance -->
                <div class="chart-container">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-calendar-alt text-orange-600 mr-2"></i>
                            Upcoming & Overdue Maintenance
                        </h3>
                        <span class="text-sm text-gray-500" id="upcoming-count">0 scheduled</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asset</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Days</th>
                                </tr>
                            </thead>
                            <tbody id="upcoming-maintenance-table" class="bg-white divide-y divide-gray-200">
                                <!-- Populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let analyticsData = null;
    let charts = {};

    // Load analytics on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadAnalytics();
    });

    async function loadAnalytics() {
        try {
            document.getElementById('loadingState').classList.remove('hidden');
            document.getElementById('errorState').classList.add('hidden');
            document.getElementById('analyticsContent').classList.add('hidden');

            const response = await fetch('../../controller/get_analytics_data.php');
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Failed to load analytics');
            }

            analyticsData = result.data;
            
            // Update UI
            updateSummaryStats();
            updateFrequentMaintenanceTable();
            updateHighRiskTable();
            updateUpcomingMaintenanceTable();
            createCharts();

            // Show content
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('analyticsContent').classList.remove('hidden');

        } catch (error) {
            console.error('Error loading analytics:', error);
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('errorState').classList.remove('hidden');
            document.getElementById('errorMessage').textContent = error.message;
        }
    }

    function updateSummaryStats() {
        const summary = analyticsData.summary;
        document.getElementById('stat-total-maintenance').textContent = 
            parseInt(summary.total_maintenance_records || 0).toLocaleString();
        document.getElementById('stat-overdue').textContent = 
            parseInt(summary.overdue_maintenance || 0).toLocaleString();
        document.getElementById('stat-poor-condition').textContent = 
            parseInt(summary.poor_condition_assets || 0).toLocaleString();
        document.getElementById('stat-total-cost').textContent = 
            '₱' + parseFloat(summary.total_maintenance_cost || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function updateFrequentMaintenanceTable() {
        const data = analyticsData.frequent_maintenance.slice(0, 15);
        const tbody = document.getElementById('frequent-maintenance-table');
        document.getElementById('frequent-count').textContent = `${data.length} assets`;
        
        tbody.innerHTML = data.map(asset => `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <div class="text-sm font-medium text-gray-900">${asset.asset_name}</div>
                    <div class="text-xs text-gray-500">${asset.asset_tag}</div>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                    ${asset.asset_age_years}y ${asset.asset_age_months}m
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        ${asset.maintenance_count}
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${asset.issue_count > 0 ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'}">
                        ${asset.issue_count}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <span class="text-sm font-medium condition-${asset.condition.toLowerCase().replace(' ', '-')}">
                        ${asset.condition}
                    </span>
                </td>
            </tr>
        `).join('');
    }

    function updateHighRiskTable() {
        const data = analyticsData.high_risk_assets.slice(0, 15);
        const tbody = document.getElementById('high-risk-table');
        document.getElementById('risk-count').textContent = `${data.length} assets`;
        
        tbody.innerHTML = data.map(asset => {
            const riskLevel = asset.risk_score > 60 ? 'high' : asset.risk_score > 30 ? 'medium' : 'low';
            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">${asset.asset_name}</div>
                        <div class="text-xs text-gray-500">${asset.asset_tag}</div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="risk-badge risk-${riskLevel}">
                            ${Math.round(asset.risk_score)}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-sm font-medium condition-${asset.condition.toLowerCase().replace(' ', '-')}">
                            ${asset.condition}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600">
                        ₱${parseFloat(asset.purchase_cost || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                    </td>
                </tr>
            `;
        }).join('');
    }

    function updateUpcomingMaintenanceTable() {
        const data = analyticsData.upcoming_maintenance;
        const tbody = document.getElementById('upcoming-maintenance-table');
        document.getElementById('upcoming-count').textContent = `${data.length} scheduled`;
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No upcoming maintenance scheduled</td></tr>';
            return;
        }
        
        tbody.innerHTML = data.map(asset => {
            const urgencyClass = asset.urgency === 'Overdue' ? 'urgency-overdue' : 
                                asset.urgency === 'Due This Week' ? 'urgency-week' : 
                                'urgency-month';
            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">${asset.asset_name}</div>
                        <div class="text-xs text-gray-500">${asset.asset_tag}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">${asset.asset_type}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        ${new Date(asset.next_maintenance_date).toLocaleDateString('en-PH', {year: 'numeric', month: 'short', day: 'numeric'})}
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${urgencyClass}">
                            ${asset.urgency}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-medium ${asset.days_until_due < 0 ? 'text-red-600' : 'text-gray-900'}">
                        ${asset.days_until_due < 0 ? Math.abs(asset.days_until_due) + ' days overdue' : asset.days_until_due + ' days'}
                    </td>
                </tr>
            `;
        }).join('');
    }

    function createCharts() {
        // Destroy existing charts
        Object.values(charts).forEach(chart => chart.destroy());
        charts = {};

        // Maintenance by Type Chart
        const typeData = analyticsData.maintenance_by_type;
        charts.typeChart = new Chart(document.getElementById('maintenanceByTypeChart'), {
            type: 'doughnut',
            data: {
                labels: typeData.map(d => d.asset_type),
                datasets: [{
                    data: typeData.map(d => d.total_maintenance),
                    backgroundColor: [
                        '#3B82F6', '#EF4444', '#10B981', '#F59E0B', 
                        '#8B5CF6', '#EC4899', '#14B8A6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const item = typeData[context.dataIndex];
                                return [
                                    `${label}: ${value} maintenance records`,
                                    `Assets: ${item.asset_count}`,
                                    `Cost: ₱${parseFloat(item.total_cost).toLocaleString('en-PH', {minimumFractionDigits: 2})}`
                                ];
                            }
                        }
                    }
                }
            }
        });

        // Condition Distribution Chart
        const conditionData = analyticsData.condition_distribution;
        charts.conditionChart = new Chart(document.getElementById('conditionChart'), {
            type: 'bar',
            data: {
                labels: conditionData.map(d => d.condition),
                datasets: [{
                    label: 'Asset Count',
                    data: conditionData.map(d => d.count),
                    backgroundColor: ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#991B1B']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Timeline Chart
        const timelineData = analyticsData.maintenance_timeline;
        charts.timelineChart = new Chart(document.getElementById('timelineChart'), {
            type: 'line',
            data: {
                labels: timelineData.map(d => {
                    const [year, month] = d.month.split('-');
                    return new Date(year, month - 1).toLocaleDateString('en-PH', {month: 'short', year: 'numeric'});
                }),
                datasets: [
                    {
                        label: 'Total Maintenance',
                        data: timelineData.map(d => d.maintenance_count),
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Emergency',
                        data: timelineData.map(d => d.emergency_count),
                        borderColor: '#EF4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
</script>

<?php include '../components/layout_footer.php'; ?>
