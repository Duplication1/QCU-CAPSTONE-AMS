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
                <div id="analyticsContent" class="hidden flex flex-col" style="gap: 0.375rem; height: 100%; overflow: hidden;">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 flex-shrink-0">
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

                    <!-- Predicted Failures Section -->
                    <div class="flex-shrink-0">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
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
                            <div class="overflow-x-auto">
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
        </main>

        <!-- Chart.js Library -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        
        <script>
        // Chart.js configuration
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#6B7280';

        let charts = {};

        // Progress simulation
        function simulateProgress() {
            const steps = [
                { percent: 20, text: 'Analyzing asset conditions...', subtext: 'Reading database...' },
                { percent: 40, text: 'Calculating trends...', subtext: 'Running models...' },
                { percent: 60, text: 'Predicting issues...', subtext: 'Processing data...' },
                { percent: 80, text: 'Generating forecasts...', subtext: 'Almost done...' }
            ];
            
            let currentStep = 0;
            const interval = setInterval(() => {
                if (currentStep < steps.length) {
                    const step = steps[currentStep];
                    document.getElementById('progressBar').style.width = step.percent + '%';
                    document.getElementById('progressText').textContent = step.percent + '%';
                    document.getElementById('loadingText').textContent = step.text;
                    document.getElementById('loadingSubtext').textContent = step.subtext;
                    currentStep++;
                }
            }, 600);
            return interval;
        }

        // Fetch predictive analytics data
        async function loadPredictiveAnalytics() {
            const progressInterval = simulateProgress();
            
            try {
                console.log('Starting API request...');
                const response = await fetch('../../controller/get_predictive_analytics.php');
                console.log('Response received:', response.status);
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Server error:', errorText.substring(0, 500));
                    throw new Error(`Server error ${response.status}. Check browser console (F12)`);
                }
                
                const result = await response.json();
                console.log('Data loaded successfully');
                
                clearInterval(progressInterval);
                
                if (result.success) {
                    document.getElementById('progressBar').style.width = '100%';
                    document.getElementById('loadingText').textContent = 'Done!';
                    
                    setTimeout(() => {
                        renderAnalytics(result.data);
                        document.getElementById('loadingState').classList.add('hidden');
                        document.getElementById('analyticsContent').classList.remove('hidden');
                    }, 500);
                } else {
                    throw new Error(result.error || 'Failed to load analytics');
                }
            } catch (error) {
                clearInterval(progressInterval);
                console.error('Error:', error);
                
                document.getElementById('loadingState').innerHTML = `
                    <div class="max-w-2xl mx-auto text-center">
                        <p class="text-red-600 font-bold text-xl mb-2">Oops! Something went wrong</p>
                        <p class="text-gray-600 mb-4">${error.message}</p>
                        <div class="mt-4 bg-yellow-50 p-4 rounded-lg text-left">
                            <p class="font-semibold text-yellow-800 mb-2">Quick fixes:</p>
                            <ul class="text-sm text-yellow-700 space-y-1 list-disc list-inside">
                                <li>Press F12 and check the Console tab for errors</li>
                                <li>Make sure you're logged in as Administrator</li>
                                <li>Check if XAMPP Apache & MySQL are running</li>
                                <li>Try accessing the API: <a href="../../controller/get_predictive_analytics.php" target="_blank" class="underline">click here</a></li>
                            </ul>
                        </div>
                        <div class="mt-6 space-x-3">
                            <button onclick="window.location.reload()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                                Try Again
                            </button>
                            <button onclick="window.location.href='index.php'" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">
                                Back to Dashboard
                            </button>
                        </div>
                    </div>
                `;
            }
        }

        function renderAnalytics(data) {
            // Calculate summary metrics
            const highRiskAssets = data.asset_failure_risk.filter(a => a.risk_level === 'Critical' || a.risk_level === 'High');
            const avgCondition = data.condition_degradation.historical.length > 0 
                ? data.condition_degradation.historical[data.condition_degradation.historical.length - 1].score 
                : 0;
            const nextMonthIssues = data.maintenance_forecast.predictions[0] || 0;
            const modelR2 = data.condition_degradation.regression.r_squared;

            // Update summary cards
            document.getElementById('highRiskCount').textContent = highRiskAssets.length;
            document.getElementById('avgConditionScore').textContent = avgCondition.toFixed(0);
            document.getElementById('predictedIssues').textContent = nextMonthIssues;
            
            // Health explanations
            let healthText = avgCondition >= 80 ? 'Excellent - Assets in great shape!' :
                           avgCondition >= 60 ? 'Good - Most assets doing fine' :
                           avgCondition >= 40 ? 'Fair - Some attention needed' :
                           'Poor - Many assets need help!';
            let healthColor = avgCondition >= 80 ? 'text-green-600' : avgCondition >= 60 ? 'text-blue-600' : avgCondition >= 40 ? 'text-yellow-600' : 'text-red-600';
            document.getElementById('healthExplanation').textContent = healthText;
            document.getElementById('healthExplanation').className = `text-xs font-medium ${healthColor}`;

            // Render charts
            renderConditionTrendChart(data.condition_degradation);
            renderMaintenanceForecastChart(data.maintenance_forecast);
            renderPredictedFailures(data.predicted_failures);
        }

        // Pagination variables for failures table
        let allFailures = [];
        let filteredFailures = [];
        let currentFailurePage = 1;
        const failuresPerPage = 7;

        function renderPredictedFailures(predictions) {
            allFailures = predictions || [];
            filteredFailures = [...allFailures];
            currentFailurePage = 1;
            
            if (allFailures.length === 0) {
                document.querySelector('#failuresTable tbody').innerHTML = `
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center">
                            <p class="text-green-600 font-semibold">Good news!</p>
                            <p class="text-xs text-gray-600 mt-1">No clear failure patterns detected yet. Keep monitoring!</p>
                        </td>
                    </tr>
                `;
                updateFailurePagination();
                return;
            }
            
            renderFailuresTable();
        }

        function renderFailuresTable() {
            const tbody = document.querySelector('#failuresTable tbody');
            const start = (currentFailurePage - 1) * failuresPerPage;
            const end = start + failuresPerPage;
            const pageData = filteredFailures.slice(start, end);
            
            if (pageData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-3 py-4 text-center text-sm text-gray-500">No assets match your filters</td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = pageData.map(pred => {
                    const ageMonths = Math.floor(pred.current_age_days / 30);
                    
                    return `
                        <tr class="hover:bg-gray-50" 
                            data-search="${(pred.asset_name + ' ' + pred.asset_tag + ' ' + pred.reason).toLowerCase()}"
                            data-risk="${pred.risk_percentage}">
                            <td class="px-3 py-2 text-xs font-medium text-gray-900">${pred.asset_name}</td>
                            <td class="px-3 py-2 text-xs text-gray-600">${pred.asset_tag}</td>
                            <td class="px-3 py-2 text-xs text-gray-600" style="max-width: 200px;" title="${pred.reason}">${pred.reason.length > 50 ? pred.reason.substring(0, 50) + '...' : pred.reason}</td>
                            <td class="px-3 py-2 text-xs">
                                <span class="px-2 py-1 rounded text-xs ${
                                    pred.condition === 'Poor' ? 'bg-orange-100 text-orange-700' : 
                                    pred.condition === 'Fair' ? 'bg-yellow-100 text-yellow-700' : 
                                    'bg-gray-100 text-gray-700'
                                }">${pred.condition}</span>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-600">${ageMonths} months</td>
                            <td class="px-3 py-2 text-xs text-gray-600">${pred.issue_count}</td>
                        </tr>
                    `;
                }).join('');
            }
            
            updateFailurePagination();
        }

        function filterFailures() {
            const searchQuery = (document.getElementById('failureSearch')?.value || '').toLowerCase().trim();
            const riskLevel = document.getElementById('riskLevelFilter')?.value || '';
            
            filteredFailures = allFailures.filter(pred => {
                const searchText = (pred.asset_name + ' ' + pred.asset_tag + ' ' + pred.reason).toLowerCase();
                const matchesSearch = !searchQuery || searchText.includes(searchQuery);
                
                let matchesRisk = true;
                if (riskLevel === 'high') matchesRisk = pred.risk_percentage >= 80;
                else if (riskLevel === 'medium') matchesRisk = pred.risk_percentage >= 50 && pred.risk_percentage < 80;
                else if (riskLevel === 'low') matchesRisk = pred.risk_percentage < 50;
                
                return matchesSearch && matchesRisk;
            });
            
            currentFailurePage = 1;
            renderFailuresTable();
        }

        function updateFailurePagination() {
            const total = filteredFailures.length;
            const totalPages = Math.ceil(total / failuresPerPage);
            const start = total === 0 ? 0 : (currentFailurePage - 1) * failuresPerPage + 1;
            const end = Math.min(currentFailurePage * failuresPerPage, total);
            
            document.getElementById('failureStart').textContent = start;
            document.getElementById('failureEnd').textContent = end;
            document.getElementById('failureTotal').textContent = total;
            document.getElementById('failureCurrentPage').textContent = currentFailurePage;
            document.getElementById('failureTotalPages').textContent = totalPages || 1;
            
            document.getElementById('failurePrevBtn').disabled = currentFailurePage === 1;
            document.getElementById('failureNextBtn').disabled = currentFailurePage >= totalPages || total === 0;
        }

        function changeFailurePage(direction) {
            const totalPages = Math.ceil(filteredFailures.length / failuresPerPage);
            const newPage = currentFailurePage + direction;
            
            if (newPage >= 1 && newPage <= totalPages) {
                currentFailurePage = newPage;
                renderFailuresTable();
            }
        }

        function renderConditionTrendChart(data) {
            const ctx = document.getElementById('conditionTrendChart').getContext('2d');
            
            const historicalMonths = data.historical.map(d => d.month);
            const historicalScores = data.historical.map(d => d.score);
            
            // Generate future months
            const lastMonthDate = new Date(historicalMonths[historicalMonths.length - 1] + '-01');
            const futureMonths = [];
            for (let i = 1; i <= 6; i++) {
                const nextMonth = new Date(lastMonthDate);
                nextMonth.setMonth(lastMonthDate.getMonth() + i);
                futureMonths.push(nextMonth.toISOString().slice(0, 7));
            }
            
            const allMonths = [...historicalMonths, ...futureMonths];
            const allScores = [...historicalScores, ...data.predictions];
            
            charts.conditionTrend = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: allMonths,
                    datasets: [
                        {
                            label: 'Historical',
                            data: historicalScores,
                            borderColor: '#1E3A8A',
                            backgroundColor: 'rgba(30, 58, 138, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointBackgroundColor: '#1E3A8A'
                        },
                        {
                            label: 'Predicted',
                            data: Array(historicalScores.length - 1).fill(null).concat([historicalScores[historicalScores.length - 1], ...data.predictions]),
                            borderColor: '#1E3A8A',
                            backgroundColor: 'rgba(30, 58, 138, 0.05)',
                            borderWidth: 3,
                            borderDash: [5, 5],
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointBackgroundColor: '#1E3A8A'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
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
                            max: 100,
                            title: {
                                display: true,
                                text: 'Condition Score'
                            }
                        }
                    }
                }
            });

            // Update stats
            const currentScore = historicalScores[historicalScores.length - 1];
            document.getElementById('currentHealth').textContent = currentScore.toFixed(0) + '/100';
            document.getElementById('condition6M').textContent = data.predictions[5].toFixed(0) + '/100';
            
            const badge = document.getElementById('degradationBadge');
            if (data.trend === 'degrading') {
                badge.textContent = '↓ Degrading';
                badge.className = 'px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-700';
            } else {
                badge.textContent = '↑ Improving';
                badge.className = 'px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-700';
            }
        }

        function renderMaintenanceForecastChart(data) {
            const ctx = document.getElementById('maintenanceForecastChart').getContext('2d');
            
            const historicalMonths = data.historical.map(d => d.month);
            const historicalCounts = data.historical.map(d => d.count);
            
            // Generate future months
            const lastMonthDate = new Date(historicalMonths[historicalMonths.length - 1] + '-01');
            const futureMonths = [];
            for (let i = 1; i <= 6; i++) {
                const nextMonth = new Date(lastMonthDate);
                nextMonth.setMonth(lastMonthDate.getMonth() + i);
                futureMonths.push(nextMonth.toISOString().slice(0, 7));
            }
            
            charts.maintenanceForecast = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [...historicalMonths, ...futureMonths],
                    datasets: [
                        {
                            label: 'Historical Issues',
                            data: historicalCounts,
                            backgroundColor: '#1E3A8A',
                            borderRadius: 6
                        },
                        {
                            label: 'Predicted Issues',
                            data: Array(historicalCounts.length).fill(null).concat(data.predictions),
                            backgroundColor: 'rgba(30, 58, 138, 0.6)',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Issue Count'
                            }
                        }
                    }
                }
            });

            // Update stats
            const lastMonthCount = historicalCounts[historicalCounts.length - 1] || 0;
            document.getElementById('lastMonthIssues').textContent = lastMonthCount + ' problems';
            document.getElementById('maintenanceNext').textContent = data.predictions[0] + ' problems';
            
            const badge = document.getElementById('maintenanceBadge');
            if (data.trend === 'increasing') {
                badge.textContent = '↑ Increasing';
                badge.className = 'px-2 py-1 text-xs font-medium rounded bg-orange-100 text-orange-700';
            } else {
                badge.textContent = '↓ Decreasing';
                badge.className = 'px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-700';
            }
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', loadPredictiveAnalytics);
        </script>

<?php include '../components/layout_footer.php'; ?>
