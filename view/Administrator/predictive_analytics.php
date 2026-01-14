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
            .stat-card {
                transition: all 0.3s ease;
            }
            .stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
            .tooltip-icon {
                cursor: help;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 16px;
                height: 16px;
                border-radius: 50%;
                background: #e5e7eb;
                color: #6b7280;
                font-size: 11px;
                font-weight: bold;
                margin-left: 4px;
            }
            .tooltip-icon:hover {
                background: #1E3A8A;
                color: white;
            }
            .info-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 10px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .help-text {
                font-size: 11px;
                color: #6b7280;
                font-style: italic;
                margin-top: 8px;
            }
        </style>

        <!-- Main Content -->
        <main class="p-2 bg-gray-50 h-screen overflow-y-auto">
            <div class="w-full">
                <!-- Loading State -->
                <div id="loadingState" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <div class="loading mx-auto mb-4"></div>
                        <p class="text-gray-600 font-medium" id="loadingText">Loading predictive analytics...</p>
                        <p class="text-sm text-gray-500 mt-2" id="loadingSubtext">Analyzing your asset data...</p>
                        <div class="mt-4">
                            <div class="w-64 mx-auto bg-gray-200 rounded-full h-2">
                                <div id="progressBar" class="bg-blue-900 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2" id="progressText">0%</p>
                        </div>
                        <button onclick="window.location.reload()" class="mt-6 px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded text-sm text-gray-700">
                            Taking too long? Click to retry
                        </button>
                    </div>
                </div>

                <!-- Analytics Content -->
                <div id="analyticsContent" class="hidden">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="stat-card bg-white rounded-lg shadow-md border-l-4 border-red-500 p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 flex items-center">
                                        Needs Attention Now
                                    </p>
                                    <p id="highRiskCount" class="text-3xl font-bold text-red-600 mt-2">-</p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-600 mt-4">Assets likely to fail soon - check these first!</p>
                        </div>

                        <div class="stat-card bg-white rounded-lg shadow-md border-l-4 border-blue-900 p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 flex items-center">
                                        Overall Health Score
                                    </p>
                                    <div class="flex items-baseline mt-2">
                                        <p id="avgConditionScore" class="text-3xl font-bold text-blue-900">-</p>
                                        <p class="text-lg text-gray-500 ml-1">/100</p>
                                    </div>
                                </div>
                            </div>
                            <p id="conditionTrend" class="text-xs text-gray-600 mt-4">-</p>
                            <p id="healthExplanation" class="text-xs font-medium mt-1">-</p>
                        </div>

                        <div class="stat-card bg-white rounded-lg shadow-md border-l-4 border-orange-500 p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 flex items-center">
                                        Expected Problems
                                    </p>
                                    <p id="predictedIssues" class="text-3xl font-bold text-orange-600 mt-2">-</p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-600 mt-4">Likely issues next month - plan ahead</p>
                        </div>

                        <div class="stat-card bg-white rounded-lg shadow-md border-l-4 border-green-500 p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 flex items-center">
                                        Prediction Reliability
                                        <span class="tooltip-icon" title="How accurate our predictions are">?</span>
                                    </p>
                                    <p id="modelAccuracy" class="text-3xl font-bold text-green-600 mt-2">-</p>
                                </div>
                            </div>
                            <p id="accuracyExplanation" class="text-xs font-medium mt-4">-</p>
                        </div>
                    </div>

                    <!-- Main Charts Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <!-- Condition Degradation Trend -->
                        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">How Asset Health Changes Over Time</h3>
                                    <p class="text-sm text-gray-600">Past 12 months + next 6 months forecast</p>
                                </div>
                                <span id="degradationBadge" class="px-3 py-1 text-xs font-medium rounded-full">-</span>
                            </div>
                            <div class="h-72">
                                <canvas id="conditionTrendChart"></canvas>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                                <div class="text-center">
                                    <p class="text-xs text-gray-600">Current Health</p>
                                    <p id="currentHealth" class="text-lg font-bold text-gray-900">-</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-600">In 6 Months</p>
                                    <p id="condition6M" class="text-lg font-bold text-gray-900">-</p>
                                </div>
                            </div>
                        </div>

                        <!-- Maintenance Forecast -->
                        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Expected Problems Each Month</h3>
                                    <p class="text-sm text-gray-600">Based on past problem patterns</p>
                                </div>
                                <span id="maintenanceBadge" class="px-3 py-1 text-xs font-medium rounded-full">-</span>
                            </div>
                            <div class="h-72">
                                <canvas id="maintenanceForecastChart"></canvas>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                                <div class="text-center">
                                    <p class="text-xs text-gray-600">Last Month</p>
                                    <p id="lastMonthIssues" class="text-lg font-bold text-gray-900">-</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-600">Next Month</p>
                                    <p id="maintenanceNext" class="text-lg font-bold text-gray-900">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Predicted Failures Section (NEW) -->
                    <div class="mb-6">
                        <div class="bg-gradient-to-r from-orange-50 to-red-50 rounded-lg shadow-md border-l-4 border-orange-500 p-6">
                            <div class="flex items-center mb-4">
                                <div class="bg-orange-500 p-3 rounded-full mr-4">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-gray-900">Assets That Might Break Next</h3>
                                    <p class="text-sm text-gray-700 mt-1">Based on patterns: if one power supply broke at 2 years old, similar ones will break around the same age</p>
                                </div>
                            </div>
                            <div id="predictedFailuresList" class="space-y-3">
                                <!-- Will be populated by JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Asset Risk Distribution -->
                        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Risk Levels</h3>
                            <p class="text-xs text-gray-600 mb-4">How many assets are in each risk category</p>
                            <div class="h-64">
                                <canvas id="riskDistributionChart"></canvas>
                            </div>
                            <div class="mt-4 space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Critical Risk</span>
                                    <span id="criticalCount" class="text-sm font-bold text-red-600">-</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">High Risk</span>
                                    <span id="highRiskCountDetail" class="text-sm font-bold text-orange-600">-</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Medium Risk</span>
                                    <span id="mediumCount" class="text-sm font-bold text-yellow-600">-</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Low Risk</span>
                                    <span id="lowCount" class="text-sm font-bold text-green-600">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Lifecycle Analysis -->
                        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Assets by Age</h3>
                            <p class="text-xs text-gray-600 mb-4">How condition changes as assets get older</p>
                            <div class="h-64">
                                <canvas id="lifecycleChart"></canvas>
                            </div>
                        </div>

                        <!-- Critical Assets List -->
                        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Currently Broken Assets</h3>
                            <p class="text-xs text-gray-600 mb-4">Assets already having problems</p>
                            <div class="h-64 overflow-y-auto" id="criticalAssetsList">
                                <!-- Will be populated by JS -->
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
                            <button onclick="window.location.reload()" class="px-6 py-3 bg-blue-900 hover:bg-blue-950 text-white rounded-lg">
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
            document.getElementById('modelAccuracy').textContent = (modelR2 * 100).toFixed(0) + '%';
            
            // Health explanations
            let healthText = avgCondition >= 80 ? 'Excellent - Assets in great shape!' :
                           avgCondition >= 60 ? 'Good - Most assets doing fine' :
                           avgCondition >= 40 ? 'Fair - Some attention needed' :
                           'Poor - Many assets need help!';
            let healthColor = avgCondition >= 80 ? 'text-green-600' : avgCondition >= 60 ? 'text-blue-900' : avgCondition >= 40 ? 'text-yellow-600' : 'text-red-600';
            document.getElementById('healthExplanation').textContent = healthText;
            document.getElementById('healthExplanation').className = `text-xs font-medium mt-1 ${healthColor}`;
            
            const trend = data.condition_degradation.trend === 'degrading' ? 'Things are getting worse over time' : 'Things are improving over time';
            document.getElementById('conditionTrend').textContent = trend;
            
            let accuracyText = modelR2 >= 0.8 ? 'Very reliable!' : modelR2 >= 0.6 ? 'Pretty reliable' : modelR2 >= 0.4 ? 'Use with caution' : 'Need more data';
            document.getElementById('accuracyExplanation').textContent = accuracyText;

            // Render charts
            renderConditionTrendChart(data.condition_degradation);
            renderMaintenanceForecastChart(data.maintenance_forecast);
            renderRiskDistributionChart(data.asset_failure_risk);
            renderLifecycleChart(data.lifecycle_predictions);
            renderCriticalAssetsList(data.critical_assets);
            renderPredictedFailures(data.predicted_failures);
        }

        function renderPredictedFailures(predictions) {
            const container = document.getElementById('predictedFailuresList');
            
            if (!predictions || predictions.length === 0) {
                container.innerHTML = `
                    <div class="bg-white rounded-lg p-6 text-center">
                        <p class="text-green-600 font-semibold">Good news!</p>
                        <p class="text-sm text-gray-600 mt-2">No clear failure patterns detected yet. Keep monitoring!</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = predictions.map((pred, index) => {
                const riskColor = pred.risk_percentage >= 80 ? 'red' : pred.risk_percentage >= 50 ? 'orange' : 'yellow';
                const riskBg = pred.risk_percentage >= 80 ? 'bg-red-50' : pred.risk_percentage >= 50 ? 'bg-orange-50' : 'bg-yellow-50';
                const riskBorder = pred.risk_percentage >= 80 ? 'border-red-200' : pred.risk_percentage >= 50 ? 'border-orange-200' : 'border-yellow-200';
                
                return `
                    <div class="${riskBg} border ${riskBorder} rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <p class="font-bold text-gray-900">${pred.asset_name}</p>
                                    <span class="text-xs px-2 py-1 bg-gray-200 rounded">${pred.asset_tag}</span>
                                </div>
                                <p class="text-sm text-gray-700 mb-2">
                                    <strong>Why this might fail:</strong> ${pred.reason}
                                </p>
                                <div class="flex items-center space-x-4 text-xs text-gray-600">
                                    <span>Condition: <strong>${pred.condition}</strong></span>
                                    <span>Age: <strong>${Math.floor(pred.current_age_days / 30)} months</strong></span>
                                    ${pred.issue_count > 0 ? `<span>Issues: <strong>${pred.issue_count}</strong></span>` : ''}
                                </div>
                            </div>
                            <div class="ml-4 text-right">
                                <div class="text-2xl font-bold text-${riskColor}-600">${pred.risk_percentage}%</div>
                                <div class="text-xs text-gray-600">Risk Level</div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
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
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            borderWidth: 3,
                            borderDash: [5, 5],
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointBackgroundColor: '#f59e0b'
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
                badge.className = 'px-3 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700';
            } else {
                badge.textContent = '↑ Improving';
                badge.className = 'px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700';
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
                            backgroundColor: '#fbbf24',
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
                badge.className = 'px-3 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-700';
            } else {
                badge.textContent = '↓ Decreasing';
                badge.className = 'px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700';
            }
        }

        function renderRiskDistributionChart(assets) {
            const riskCounts = {
                'Critical': 0,
                'High': 0,
                'Medium': 0,
                'Low': 0
            };
            
            assets.forEach(asset => {
                riskCounts[asset.risk_level]++;
            });
            
            const ctx = document.getElementById('riskDistributionChart').getContext('2d');
            charts.riskDistribution = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Critical', 'High', 'Medium', 'Low'],
                    datasets: [{
                        data: [riskCounts.Critical, riskCounts.High, riskCounts.Medium, riskCounts.Low],
                        backgroundColor: ['#dc2626', '#f97316', '#fbbf24', '#22c55e'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Update counts
            document.getElementById('criticalCount').textContent = riskCounts.Critical;
            document.getElementById('highRiskCountDetail').textContent = riskCounts.High;
            document.getElementById('mediumCount').textContent = riskCounts.Medium;
            document.getElementById('lowCount').textContent = riskCounts.Low;
        }

        function renderLifecycleChart(data) {
            const ctx = document.getElementById('lifecycleChart').getContext('2d');
            
            charts.lifecycle = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.stage),
                    datasets: [
                        {
                            label: 'Asset Count',
                            data: data.map(d => d.count),
                            backgroundColor: '#1E3A8A',
                            borderRadius: 6,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Avg Condition',
                            data: data.map(d => d.avg_condition),
                            type: 'line',
                            borderColor: '#f59e0b',
                            backgroundColor: 'transparent',
                            borderWidth: 3,
                            yAxisID: 'y1',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Asset Count'
                            }
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Avg Condition'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        }

        function renderCriticalAssetsList(assets) {
            const container = document.getElementById('criticalAssetsList');
            
            if (assets.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm text-center py-8">No critical assets</p>';
                return;
            }
            
            container.innerHTML = assets.map(asset => `
                <div class="border-b border-gray-200 py-3 hover:bg-gray-50 px-2 rounded transition">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">${asset.asset_name}</p>
                            <p class="text-xs text-gray-500">${asset.asset_tag}</p>
                        </div>
                        <span class="px-2 py-1 text-xs rounded-full ${
                            asset.condition === 'Non-Functional' ? 'bg-red-100 text-red-700' :
                            asset.condition === 'Poor' ? 'bg-orange-100 text-orange-700' :
                            'bg-yellow-100 text-yellow-700'
                        }">
                            ${asset.condition}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center space-x-4 text-xs text-gray-600">
                        <span>${asset.issue_count} issue${asset.issue_count !== 1 ? 's' : ''}</span>
                        <span>${Math.floor(asset.age_days / 365)}y ${Math.floor((asset.age_days % 365) / 30)}m old</span>
                    </div>
                </div>
            `).join('');
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', loadPredictiveAnalytics);
        </script>

<?php include '../components/layout_footer.php'; ?>
