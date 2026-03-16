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

// Include the layout header (includes sidebar and header components)
include '../components/layout_header.php';
?>
        <style>
            body, html { overflow: hidden !important; height: 100vh; }
            main { height: calc(100vh - 85px); }
            .stat-card {
                transition: all 0.2s ease;
            }
            .loading {
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 3px solid rgba(30, 58, 138, 0.3);
                border-radius: 50%;
                border-top-color: #1E3A8A;
                animation: spin 1s ease-in-out infinite;
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        </style>

        <!-- Main Content -->
        <main class="p-2 bg-gray-50 overflow-hidden flex flex-col" style="height: calc(100vh - 85px);">
            <div class="flex-1 min-h-0 overflow-hidden">
                <!-- Loading State -->
                <div id="loadingState" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <div class="loading mx-auto mb-4"></div>
                        <p class="text-gray-600 font-medium" id="loadingText">Loading predictive analytics...</p>
                        <p class="text-sm text-gray-500 mt-2" id="loadingSubtext">Analyzing your asset data...</p>
                        <div class="mt-4">
                            <div class="w-64 mx-auto bg-gray-200 rounded-full h-2">
                                <div id="progressBar" class="h-2 rounded-full transition-all duration-500" style="width: 0%; background-color: #1E3A8A;"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2" id="progressText">0%</p>
                        </div>
                        <button onclick="window.location.reload()" class="mt-6 px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded text-sm text-gray-700">
                            Taking too long? Click to retry
                        </button>
                    </div>
                </div>

                <!-- Analytics Content -->
                <div id="analyticsContent" class="hidden flex-col" style="gap: 0.375rem; height: 100%; overflow: hidden;">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-2 flex-shrink-0">
                        <div class="stat-card bg-white rounded-lg shadow-sm border border-gray-200 p-3" style="color: #1E3A8A;">
                            <div class="flex items-center justify-between mb-1">
                                <div>
                                    <p class="text-xs opacity-70 mb-0.5">Needs Attention</p>
                                    <p id="highRiskCount" class="text-2xl font-bold">-</p>
                                </div>
                                <div class="bg-red-100 p-2 rounded">
                                    <i class="fas fa-exclamation-triangle text-xl text-red-600"></i>
                                </div>
                            </div>
                            <p class="text-xs opacity-70">Assets likely to fail soon</p>
                        </div>

                        <div class="stat-card bg-white rounded-lg shadow-sm border border-gray-200 p-3" style="color: #1E3A8A;">
                            <div class="flex items-center justify-between mb-1">
                                <div>
                                    <p class="text-xs opacity-70 mb-0.5">Health Score</p>
                                    <div class="flex items-baseline">
                                        <p id="avgConditionScore" class="text-2xl font-bold">-</p>
                                        <p class="text-sm opacity-70 ml-1">/100</p>
                                    </div>
                                </div>
                                <div class="bg-blue-100 p-2 rounded">
                                    <i class="fas fa-heart-pulse text-xl text-blue-600"></i>
                                </div>
                            </div>
                            <p id="healthExplanation" class="text-xs font-medium">-</p>
                        </div>

                        <div class="stat-card bg-white rounded-lg shadow-sm border border-gray-200 p-3" style="color: #1E3A8A;">
                            <div class="flex items-center justify-between mb-1">
                                <div>
                                    <p class="text-xs opacity-70 mb-0.5">Next Month</p>
                                    <p id="predictedIssues" class="text-2xl font-bold">-</p>
                                </div>
                                <div class="bg-orange-100 p-2 rounded">
                                    <i class="fas fa-calendar-day text-xl text-orange-600"></i>
                                </div>
                            </div>
                            <p class="text-xs opacity-70">Predicted issues</p>
                        </div>

                        <div class="stat-card bg-white rounded-lg shadow-sm border border-gray-200 p-3" style="color: #1E3A8A;">
                            <div class="flex items-center justify-between mb-1">
                                <div>
                                    <p class="text-xs opacity-70 mb-0.5">Avg Resolution</p>
                                    <div class="flex items-baseline">
                                        <p id="avgResolutionHours" class="text-2xl font-bold">-</p>
                                        <p class="text-sm opacity-70 ml-1">hrs</p>
                                    </div>
                                </div>
                                <div class="bg-indigo-100 p-2 rounded">
                                    <i class="fas fa-clock text-xl text-indigo-600"></i>
                                </div>
                            </div>
                            <p class="text-xs opacity-70">Resolved tickets (latest period)</p>
                        </div>

                        <div class="stat-card bg-white rounded-lg shadow-sm border border-gray-200 p-3" style="color: #1E3A8A;">
                            <div class="flex items-center justify-between mb-1">
                                <div>
                                    <p class="text-xs opacity-70 mb-0.5">SLA Risk</p>
                                    <div class="flex items-baseline">
                                        <p id="slaRiskRate" class="text-2xl font-bold">-</p>
                                        <p class="text-sm opacity-70 ml-1">%</p>
                                    </div>
                                </div>
                                <div class="bg-rose-100 p-2 rounded">
                                    <i class="fas fa-hourglass-half text-xl text-rose-600"></i>
                                </div>
                            </div>
                            <p id="slaRiskText" class="text-xs opacity-70">Ticket breaches beyond 48h</p>
                        </div>
                    </div>

                    <!-- Main Charts Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-2 flex-shrink-0">
                        <!-- Condition Degradation Trend -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                            <div class="flex items-center justify-between p-2 border-b border-gray-200" style="background-color: rgba(30, 58, 138, 0.05);">
                                <div>
                                    <h3 class="text-sm font-semibold" style="color: #1E3A8A;">Asset Health Over Time</h3>
                                    <p class="text-xs text-gray-500">Past 12 months + 6 months forecast</p>
                                </div>
                                <span id="degradationBadge" class="px-2 py-1 text-xs font-medium rounded">-</span>
                            </div>
                            <div class="p-3">
                                <div class="h-40">
                                    <canvas id="conditionTrendChart"></canvas>
                                </div>
                            <div class="mt-2 grid grid-cols-2 gap-2 pt-2 border-t border-gray-200">
                                <div class="text-center">
                                    <p class="text-xs text-gray-500">Current</p>
                                    <p id="currentHealth" class="text-sm font-bold" style="color: #1E3A8A;">-</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500">In 6 Months</p>
                                    <p id="condition6M" class="text-sm font-bold" style="color: #1E3A8A;">-</p>
                                </div>
                            </div>
                            </div>
                        </div>

                        <!-- Maintenance Forecast -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                            <div class="flex items-center justify-between p-2 border-b border-gray-200" style="background-color: rgba(30, 58, 138, 0.05);">
                                <div>
                                    <h3 class="text-sm font-semibold" style="color: #1E3A8A;">Expected Problems</h3>
                                    <p class="text-xs text-gray-500">Based on past patterns</p>
                                </div>
                                <span id="maintenanceBadge" class="px-2 py-1 text-xs font-medium rounded">-</span>
                            </div>
                            <div class="p-3">
                                <div class="h-36">
                                    <canvas id="maintenanceForecastChart"></canvas>
                                </div>
                            <div class="mt-2 grid grid-cols-2 gap-2 pt-2 border-t border-gray-200">
                                <div class="text-center">
                                    <p class="text-xs text-gray-500">Last Month</p>
                                    <p id="lastMonthIssues" class="text-sm font-bold" style="color: #1E3A8A;">-</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500">Next Month</p>
                                    <p id="maintenanceNext" class="text-sm font-bold" style="color: #1E3A8A;">-</p>
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-2 flex-shrink-0 min-h-0">
                    <!-- Resolution Time Forecast -->
                    <div class="min-h-0">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden h-full flex flex-col">
                            <div class="flex items-center justify-between p-2 border-b border-gray-200" style="background-color: rgba(30, 58, 138, 0.05);">
                                <div>
                                    <h3 class="text-sm font-semibold" style="color: #1E3A8A;">Resolution Time Forecast</h3>
                                    <p class="text-xs text-gray-500">Average ticket resolution hours (12 months + 6 months forecast)</p>
                                </div>
                                <span id="resolutionTrendBadge" class="px-2 py-1 text-xs font-medium rounded">-</span>
                            </div>
                            <div class="p-3 flex-1 min-h-0 flex flex-col">
                                <div class="h-36 flex-1 min-h-[140px]">
                                    <canvas id="resolutionTimeChart"></canvas>
                                </div>
                                <div class="mt-2 grid grid-cols-3 gap-2 pt-2 border-t border-gray-200">
                                    <div class="text-center">
                                        <p class="text-xs text-gray-500">Current Avg</p>
                                        <p id="resolutionCurrent" class="text-sm font-bold" style="color: #1E3A8A;">-</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-xs text-gray-500">Next Month</p>
                                        <p id="resolutionNext" class="text-sm font-bold" style="color: #1E3A8A;">-</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-xs text-gray-500">Resolved (30d)</p>
                                        <p id="resolutionCount30" class="text-sm font-bold" style="color: #1E3A8A;">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Predicted Failures Section -->
                    <div class="min-h-0">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 h-full flex flex-col overflow-hidden">
                            <!-- Header and Controls -->
                            <div class="border-b border-gray-200 p-2" style="background-color: rgba(30, 58, 138, 0.05);">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="p-2 rounded" style="background-color: #1E3A8A;">
                                            <i class="fas fa-exclamation-triangle text-white text-sm"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold" style="color: #1E3A8A;">Assets That Might Break Next</h3>
                                            <p class="text-xs text-gray-600">Based on failure patterns</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Search and Filter -->
                                    <div class="flex items-center gap-2">
                                        <div class="relative">
                                            <input id="failureSearch" oninput="filterFailures()" type="search" placeholder="Search assets..."
                                                class="w-40 pl-7 pr-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1" style="focus:ring-color: #1E3A8A;" />
                                            <i class="fas fa-search absolute left-2 text-gray-400 text-xs pointer-events-none" style="top: 50%; transform: translateY(-50%);"></i>
                                        </div>
                                        <select id="riskLevelFilter" onchange="filterFailures()" 
                                            class="px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1" style="focus:ring-color: #1E3A8A;">
                                            <option value="">All Risk Levels</option>
                                            <option value="high">High Risk (≥80%)</option>
                                            <option value="medium">Medium Risk (50-79%)</option>
                                            <option value="low">Low Risk (<50%)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Table -->
                            <div class="overflow-x-auto flex-1 min-h-0">
                                <table id="failuresTable" class="min-w-full divide-y divide-gray-200">
                                    <thead class="sticky top-0" style="background-color: rgba(30, 58, 138, 0.1);">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Asset</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Tag</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Reason</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Condition</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Age</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Issues</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <!-- Will be populated by JS -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="bg-gray-50 px-3 py-2 border-t border-gray-200 flex items-center justify-between">
                                <div class="text-xs text-gray-600">
                                    Showing <span id="failureStart">0</span> to <span id="failureEnd">0</span> of <span id="failureTotal">0</span> assets
                                </div>
                                <div class="flex items-center gap-2">
                                    <button onclick="changeFailurePage(-1)" id="failurePrevBtn" 
                                        class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                        Previous
                                    </button>
                                    <span class="text-xs text-gray-600">Page <span id="failureCurrentPage">1</span> of <span id="failureTotalPages">1</span></span>
                                    <button onclick="changeFailurePage(1)" id="failureNextBtn" 
                                        class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                        Next
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Chart.js Library -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        
        <script>
        (function () {
            const hasChartJs = typeof Chart !== 'undefined';
            if (hasChartJs) {
                Chart.defaults.font.family = "'Inter', sans-serif";
                Chart.defaults.color = '#6B7280';
            }

            const charts = {};
            const state = {
                allFailures: [],
                filteredFailures: [],
                currentPage: 1,
                perPage: 7
            };

            function byId(id) {
                return document.getElementById(id);
            }

            function setText(id, value) {
                const element = byId(id);
                if (element) element.textContent = String(value);
            }

            function showContent() {
                const loading = byId('loadingState');
                const content = byId('analyticsContent');
                if (loading) loading.classList.add('hidden');
                if (content) {
                    content.classList.remove('hidden');
                    content.classList.add('flex');
                }
            }

            function renderFallback(message) {
                setText('highRiskCount', 0);
                setText('avgConditionScore', 0);
                setText('predictedIssues', 0);
                setText('avgResolutionHours', 0);
                setText('slaRiskRate', 0);
                setText('slaRiskText', 'Ticket breaches beyond 48h');

                const health = byId('healthExplanation');
                if (health) {
                    health.textContent = message || 'Analytics unavailable';
                    health.className = 'text-xs font-medium text-yellow-600';
                }

                renderFailures([]);
                setText('degradationBadge', 'No Data');
                setText('maintenanceBadge', 'No Data');
                setText('currentHealth', '0/100');
                setText('condition6M', '0/100');
                setText('lastMonthIssues', '0 problems');
                setText('maintenanceNext', '0 problems');
                setText('resolutionCurrent', '0h');
                setText('resolutionNext', '0h');
                setText('resolutionCount30', 0);
                setText('resolutionTrendBadge', 'No Data');
                showContent();
            }

            async function fetchAnalytics() {
                const response = await fetch('../../controller/get_predictive_analytics.php', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });

                const raw = await response.text();
                let payload;
                try {
                    payload = JSON.parse(raw);
                } catch (error) {
                    throw new Error('Invalid JSON from predictive API');
                }

                if (!response.ok || !payload.success) {
                    throw new Error(payload.error || payload.message || `API request failed (${response.status})`);
                }

                return payload.data || {};
            }

            function normalizeData(data) {
                const normalized = data || {};
                normalized.asset_failure_risk = Array.isArray(normalized.asset_failure_risk) ? normalized.asset_failure_risk : [];
                normalized.predicted_failures = Array.isArray(normalized.predicted_failures) ? normalized.predicted_failures : [];

                normalized.condition_degradation = normalized.condition_degradation || {};
                normalized.condition_degradation.historical = Array.isArray(normalized.condition_degradation.historical)
                    ? normalized.condition_degradation.historical
                    : [];
                normalized.condition_degradation.predictions = Array.isArray(normalized.condition_degradation.predictions)
                    ? normalized.condition_degradation.predictions
                    : [];

                normalized.maintenance_forecast = normalized.maintenance_forecast || {};
                normalized.maintenance_forecast.historical = Array.isArray(normalized.maintenance_forecast.historical)
                    ? normalized.maintenance_forecast.historical
                    : [];
                normalized.maintenance_forecast.predictions = Array.isArray(normalized.maintenance_forecast.predictions)
                    ? normalized.maintenance_forecast.predictions
                    : [];

                normalized.resolution_time = normalized.resolution_time || {};
                normalized.resolution_time.historical = Array.isArray(normalized.resolution_time.historical)
                    ? normalized.resolution_time.historical
                    : [];
                normalized.resolution_time.predictions = Array.isArray(normalized.resolution_time.predictions)
                    ? normalized.resolution_time.predictions
                    : [];
                normalized.resolution_time.current_avg_hours = Number(normalized.resolution_time.current_avg_hours) || 0;
                normalized.resolution_time.next_month_avg_hours = Number(normalized.resolution_time.next_month_avg_hours) || 0;
                normalized.resolution_time.sla_breach_rate = Number(normalized.resolution_time.sla_breach_rate) || 0;
                normalized.resolution_time.sla_threshold_hours = Number(normalized.resolution_time.sla_threshold_hours) || 48;
                normalized.resolution_time.resolved_last_30_days = Number(normalized.resolution_time.resolved_last_30_days) || 0;

                return normalized;
            }

            function monthSeries(count) {
                const result = [];
                const base = new Date();
                for (let i = count - 1; i >= 0; i--) {
                    const d = new Date(base);
                    d.setMonth(base.getMonth() - i);
                    result.push(d.toISOString().slice(0, 7));
                }
                return result;
            }

            function renderSummary(data) {
                const highRisk = data.asset_failure_risk.filter(item => item.risk_level === 'Critical' || item.risk_level === 'High').length;
                const lastCondition = data.condition_degradation.historical.length
                    ? Number(data.condition_degradation.historical[data.condition_degradation.historical.length - 1].score) || 0
                    : 0;
                const nextIssues = data.maintenance_forecast.predictions.length
                    ? Number(data.maintenance_forecast.predictions[0]) || 0
                    : 0;
                const avgResolutionHours = data.resolution_time.current_avg_hours || 0;
                const slaRiskRate = data.resolution_time.sla_breach_rate || 0;

                setText('highRiskCount', highRisk);
                setText('avgConditionScore', Math.round(lastCondition));
                setText('predictedIssues', nextIssues);
                setText('avgResolutionHours', avgResolutionHours.toFixed(1));
                setText('slaRiskRate', slaRiskRate.toFixed(1));
                setText('slaRiskText', `Tickets above ${data.resolution_time.sla_threshold_hours || 48}h`);

                const health = byId('healthExplanation');
                if (health) {
                    let text = 'Poor - Many assets need help!';
                    let cls = 'text-red-600';
                    if (lastCondition >= 80) {
                        text = 'Excellent - Assets in great shape!';
                        cls = 'text-green-600';
                    } else if (lastCondition >= 60) {
                        text = 'Good - Most assets doing fine';
                        cls = 'text-blue-600';
                    } else if (lastCondition >= 40) {
                        text = 'Fair - Some attention needed';
                        cls = 'text-yellow-600';
                    }
                    health.textContent = text;
                    health.className = `text-xs font-medium ${cls}`;
                }
            }

            function renderResolutionChart(data) {
                if (!hasChartJs) return;
                const canvas = byId('resolutionTimeChart');
                if (!canvas) return;

                const historical = data.resolution_time.historical;
                const predictions = data.resolution_time.predictions;

                let labels = historical.map(item => item.month);
                let values = historical.map(item => Number(item.hours) || 0);
                if (!labels.length) {
                    labels = monthSeries(6);
                    values = Array(6).fill(0);
                }

                const lastDate = new Date(labels[labels.length - 1] + '-01');
                const future = [];
                for (let i = 1; i <= 6; i++) {
                    const d = new Date(lastDate);
                    d.setMonth(lastDate.getMonth() + i);
                    future.push(d.toISOString().slice(0, 7));
                }

                const safePredictions = predictions.length ? predictions.map(v => Number(v) || 0) : Array(6).fill(values[values.length - 1] || 0);

                if (charts.resolutionTime) charts.resolutionTime.destroy();
                charts.resolutionTime = new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: [...labels, ...future],
                        datasets: [
                            {
                                label: 'Historical Hours',
                                data: values,
                                borderColor: '#4F46E5',
                                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                                borderWidth: 3,
                                tension: 0.35,
                                fill: true,
                                pointRadius: 4
                            },
                            {
                                label: 'Predicted Hours',
                                data: Array(values.length - 1).fill(null).concat([values[values.length - 1], ...safePredictions]),
                                borderColor: '#4F46E5',
                                backgroundColor: 'rgba(79, 70, 229, 0.05)',
                                borderDash: [6, 6],
                                borderWidth: 3,
                                tension: 0.35,
                                fill: true,
                                pointRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: true, position: 'top' } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Hours'
                                }
                            }
                        }
                    }
                });

                setText('resolutionCurrent', `${(data.resolution_time.current_avg_hours || 0).toFixed(1)}h`);
                setText('resolutionNext', `${(data.resolution_time.next_month_avg_hours || 0).toFixed(1)}h`);
                setText('resolutionCount30', data.resolution_time.resolved_last_30_days || 0);

                const badge = byId('resolutionTrendBadge');
                if (badge) {
                    const slower = data.resolution_time.trend === 'slower';
                    badge.textContent = slower ? '↑ Slower' : '↓ Faster';
                    badge.className = slower
                        ? 'px-2 py-1 text-xs font-medium rounded bg-orange-100 text-orange-700'
                        : 'px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-700';
                }
            }

            function renderConditionChart(data) {
                if (!hasChartJs) return;
                const canvas = byId('conditionTrendChart');
                if (!canvas) return;

                const historical = data.condition_degradation.historical;
                const predictions = data.condition_degradation.predictions;

                let labels = historical.map(item => item.month);
                let values = historical.map(item => Number(item.score) || 0);
                if (!labels.length) {
                    labels = monthSeries(6);
                    values = Array(6).fill(0);
                }

                const lastDate = new Date(labels[labels.length - 1] + '-01');
                const future = [];
                for (let i = 1; i <= 6; i++) {
                    const d = new Date(lastDate);
                    d.setMonth(lastDate.getMonth() + i);
                    future.push(d.toISOString().slice(0, 7));
                }

                const safePredictions = predictions.length ? predictions.map(v => Number(v) || 0) : Array(6).fill(values[values.length - 1] || 0);
                const predictedSeries = Array(values.length - 1).fill(null).concat([values[values.length - 1], ...safePredictions]);

                if (charts.conditionTrend) charts.conditionTrend.destroy();
                charts.conditionTrend = new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: [...labels, ...future],
                        datasets: [
                            {
                                label: 'Historical',
                                data: values,
                                borderColor: '#1E3A8A',
                                backgroundColor: 'rgba(30, 58, 138, 0.1)',
                                borderWidth: 3,
                                tension: 0.35,
                                fill: true,
                                pointRadius: 4
                            },
                            {
                                label: 'Predicted',
                                data: predictedSeries,
                                borderColor: '#1E3A8A',
                                backgroundColor: 'rgba(30, 58, 138, 0.05)',
                                borderDash: [6, 6],
                                borderWidth: 3,
                                tension: 0.35,
                                fill: true,
                                pointRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: true, position: 'top' } },
                        scales: { y: { beginAtZero: true, max: 100 } }
                    }
                });

                setText('currentHealth', `${Math.round(values[values.length - 1] || 0)}/100`);
                setText('condition6M', `${Math.round(safePredictions[5] || 0)}/100`);

                const badge = byId('degradationBadge');
                if (badge) {
                    const degrading = data.condition_degradation.trend === 'degrading';
                    badge.textContent = degrading ? '↓ Degrading' : '↑ Improving';
                    badge.className = degrading
                        ? 'px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-700'
                        : 'px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-700';
                }
            }

            function renderMaintenanceChart(data) {
                if (!hasChartJs) return;
                const canvas = byId('maintenanceForecastChart');
                if (!canvas) return;

                const historical = data.maintenance_forecast.historical;
                const predictions = data.maintenance_forecast.predictions;

                let labels = historical.map(item => item.month);
                let values = historical.map(item => Number(item.count) || 0);
                if (!labels.length) {
                    labels = monthSeries(6);
                    values = Array(6).fill(0);
                }

                const lastDate = new Date(labels[labels.length - 1] + '-01');
                const future = [];
                for (let i = 1; i <= 6; i++) {
                    const d = new Date(lastDate);
                    d.setMonth(lastDate.getMonth() + i);
                    future.push(d.toISOString().slice(0, 7));
                }

                const safePredictions = predictions.length ? predictions.map(v => Number(v) || 0) : Array(6).fill(values[values.length - 1] || 0);

                if (charts.maintenanceForecast) charts.maintenanceForecast.destroy();
                charts.maintenanceForecast = new Chart(canvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: [...labels, ...future],
                        datasets: [
                            {
                                label: 'Historical Issues',
                                data: values,
                                backgroundColor: '#1E3A8A',
                                borderRadius: 6
                            },
                            {
                                label: 'Predicted Issues',
                                data: Array(values.length).fill(null).concat(safePredictions),
                                backgroundColor: 'rgba(30, 58, 138, 0.6)',
                                borderRadius: 6
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: true, position: 'top' } },
                        scales: { y: { beginAtZero: true } }
                    }
                });

                setText('lastMonthIssues', `${values[values.length - 1] || 0} problems`);
                setText('maintenanceNext', `${safePredictions[0] || 0} problems`);

                const badge = byId('maintenanceBadge');
                if (badge) {
                    const increasing = data.maintenance_forecast.trend === 'increasing';
                    badge.textContent = increasing ? '↑ Increasing' : '↓ Decreasing';
                    badge.className = increasing
                        ? 'px-2 py-1 text-xs font-medium rounded bg-orange-100 text-orange-700'
                        : 'px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-700';
                }
            }

            function renderFailures(predictions) {
                state.allFailures = Array.isArray(predictions) ? predictions : [];
                state.filteredFailures = [...state.allFailures];
                state.currentPage = 1;
                renderFailuresPage();
            }

            function applyFailureFilter() {
                const query = ((byId('failureSearch') || {}).value || '').toLowerCase().trim();
                const riskLevel = ((byId('riskLevelFilter') || {}).value || '').trim();

                state.filteredFailures = state.allFailures.filter(item => {
                    const text = `${item.asset_name || ''} ${item.asset_tag || ''} ${item.reason || ''}`.toLowerCase();
                    const risk = Number(item.risk_percentage) || 0;
                    const textMatch = !query || text.includes(query);

                    let riskMatch = true;
                    if (riskLevel === 'high') riskMatch = risk >= 80;
                    else if (riskLevel === 'medium') riskMatch = risk >= 50 && risk < 80;
                    else if (riskLevel === 'low') riskMatch = risk < 50;

                    return textMatch && riskMatch;
                });

                state.currentPage = 1;
                renderFailuresPage();
            }

            function renderFailuresPage() {
                const tbody = document.querySelector('#failuresTable tbody');
                if (!tbody) return;

                const startIndex = (state.currentPage - 1) * state.perPage;
                const endIndex = startIndex + state.perPage;
                const rows = state.filteredFailures.slice(startIndex, endIndex);

                if (!rows.length) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="px-3 py-5 text-center text-sm text-gray-500">No assets match your current filters</td>
                        </tr>
                    `;
                } else {
                    tbody.innerHTML = rows.map(item => {
                        const ageMonths = Math.floor((Number(item.current_age_days) || 0) / 30);
                        const reason = item.reason || '-';
                        const shortReason = reason.length > 70 ? `${reason.slice(0, 70)}...` : reason;
                        const condition = item.condition || 'Unknown';
                        let conditionClass = 'bg-gray-100 text-gray-700';
                        if (condition === 'Poor') conditionClass = 'bg-orange-100 text-orange-700';
                        else if (condition === 'Fair') conditionClass = 'bg-yellow-100 text-yellow-700';

                        return `
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-xs font-medium text-gray-900">${item.asset_name || '-'}</td>
                                <td class="px-3 py-2 text-xs text-gray-600">${item.asset_tag || '-'}</td>
                                <td class="px-3 py-2 text-xs text-gray-600" style="max-width: 220px;" title="${reason.replace(/"/g, '&quot;')}">${shortReason}</td>
                                <td class="px-3 py-2 text-xs"><span class="px-2 py-1 rounded text-xs ${conditionClass}">${condition}</span></td>
                                <td class="px-3 py-2 text-xs text-gray-600">${ageMonths} months</td>
                                <td class="px-3 py-2 text-xs text-gray-600">${item.issue_count || 0}</td>
                            </tr>
                        `;
                    }).join('');
                }

                updatePagination();
            }

            function updatePagination() {
                const total = state.filteredFailures.length;
                const totalPages = Math.max(1, Math.ceil(total / state.perPage));
                const start = total === 0 ? 0 : (state.currentPage - 1) * state.perPage + 1;
                const end = Math.min(state.currentPage * state.perPage, total);

                setText('failureStart', start);
                setText('failureEnd', end);
                setText('failureTotal', total);
                setText('failureCurrentPage', state.currentPage);
                setText('failureTotalPages', totalPages);

                const prev = byId('failurePrevBtn');
                const next = byId('failureNextBtn');
                if (prev) prev.disabled = state.currentPage <= 1;
                if (next) next.disabled = state.currentPage >= totalPages;
            }

            window.changeFailurePage = function (direction) {
                const totalPages = Math.max(1, Math.ceil(state.filteredFailures.length / state.perPage));
                const nextPage = state.currentPage + Number(direction || 0);
                if (nextPage >= 1 && nextPage <= totalPages) {
                    state.currentPage = nextPage;
                    renderFailuresPage();
                }
            };

            window.filterFailures = function () {
                applyFailureFilter();
            };

            async function bootstrap() {
                try {
                    const data = normalizeData(await fetchAnalytics());
                    renderSummary(data);
                    renderConditionChart(data);
                    renderMaintenanceChart(data);
                    renderResolutionChart(data);
                    renderFailures(data.predicted_failures);
                    showContent();
                } catch (error) {
                    console.error('Predictive analytics bootstrap error:', error);
                    renderFallback(error.message || 'Could not load predictive analytics');
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                // Hard escape hatch for any unexpected blocker
                setTimeout(() => {
                    const loading = byId('loadingState');
                    if (loading && !loading.classList.contains('hidden')) {
                        renderFallback('Loading timeout. Showing fallback view.');
                    }
                }, 8000);

                bootstrap();
            });
        })();
        </script>

<?php include '../components/layout_footer.php'; ?>
