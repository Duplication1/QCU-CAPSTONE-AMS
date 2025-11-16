<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Monitor Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --card-bg: #1a1d29;
            --card-border: #2d3139;
            --text-primary: #ffffff;
            --text-secondary: #a0aec0;
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            min-height: 100vh;
            color: var(--text-primary);
        }

        .navbar {
            background: rgba(26, 29, 41, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--card-border);
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            margin-bottom: 24px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
            border-color: #667eea;
            transition: all 0.2s ease;
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--card-border);
            padding: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .metric-card {
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .metric-value {
            font-size: 3rem;
            font-weight: 700;
            line-height: 1;
            margin: 1rem 0;
        }

        .metric-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }

        .progress {
            height: 8px;
            border-radius: 10px;
            background: rgba(255,255,255,0.1);
            overflow: hidden;
        }

        .progress-bar {
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 10px;
        }

        .progress-bar.bg-success {
            background: var(--success-gradient) !important;
        }

        .progress-bar.bg-warning {
            background: var(--warning-gradient) !important;
        }

        .progress-bar.bg-danger {
            background: var(--danger-gradient) !important;
        }

        .status-good { 
            color: #38ef7d;
            text-shadow: 0 0 10px rgba(56, 239, 125, 0.5);
        }
        .status-warning { 
            color: #f5576c;
            text-shadow: 0 0 10px rgba(245, 87, 108, 0.5);
        }
        .status-danger { 
            color: #fee140;
            text-shadow: 0 0 10px rgba(254, 225, 64, 0.5);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--card-border);
            align-items: center;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .info-value {
            color: var(--text-primary);
            font-weight: 600;
            text-align: right;
            font-size: 0.9rem;
        }

        .drive-item {
            margin-bottom: 20px;
            padding: 16px;
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            border: 1px solid var(--card-border);
            transition: all 0.3s ease;
        }

        .drive-item:hover {
            background: rgba(255,255,255,0.05);
            border-color: #667eea;
        }

        .drive-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .drive-letter {
            font-weight: 700;
            font-size: 1.1rem;
            color: #667eea;
        }

        .drive-size {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .icon-wrapper {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: rgba(102, 126, 234, 0.1);
            margin-bottom: 1rem;
        }

        .icon-wrapper i {
            font-size: 2rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 20px;
            font-size: 0.875rem;
            margin: 4px;
        }

        .stat-badge i {
            color: #667eea;
        }

        .system-info-detail {
            background: rgba(255,255,255,0.03);
            padding: 8px 12px;
            border-radius: 8px;
            margin: 6px 0;
            font-size: 0.85rem;
            border-left: 3px solid #667eea;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(102, 126, 234, 0.2);
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .last-updated {
            font-size: 0.875rem;
            color: var(--text-secondary);
            padding: 8px 16px;
            background: rgba(255,255,255,0.05);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .container-fluid {
            max-width: 1400px;
        }

        .network-item {
            background: rgba(255,255,255,0.03);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 3px solid #38ef7d;
        }

        .network-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .network-mac {
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid">
            <span class="navbar-brand mb-0">
                <i class="bi bi-activity"></i> System Health Monitoring
            </span>
            <div class="d-flex align-items-center">
                <span class="last-updated me-3" id="last-updated">
                    <i class="bi bi-clock"></i>
                    <span>Initializing...</span>
                </span>
                <button class="btn btn-sm btn-outline-light" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4 mb-5">
        <div class="row" id="health-data">
            <div class="col-12 text-center py-5">
                <div class="loading-spinner mx-auto"></div>
                <p class="text-muted mt-3">Loading system data...</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function getStatusClass(percentage) {
            if (percentage < 50) return 'status-good';
            if (percentage < 80) return 'status-warning';
            return 'status-danger';
        }

        function getProgressClass(percentage) {
            if (percentage < 50) return 'bg-success';
            if (percentage < 80) return 'bg-warning';
            return 'bg-danger';
        }

        function updateHealth() {
            fetch('api.php')
                .then(response => response.json())
                .then(data => {
                    const now = new Date().toLocaleString();
                    document.querySelector('#last-updated span').innerHTML = `<i class="bi bi-clock"></i> ${now}`;

                    // Build disk drives HTML
                    let disksHtml = '';
                    data.disk.forEach(drive => {
                        disksHtml += `
                            <div class="drive-item">
                                <div class="drive-header">
                                    <span class="drive-letter">
                                        <i class="bi bi-hdd"></i> ${drive.drive}
                                    </span>
                                    <span class="drive-size">${drive.used} / ${drive.total} GB</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar ${getProgressClass(drive.percent)}" 
                                         style="width: ${drive.percent}%" 
                                         role="progressbar">${drive.percent}%</div>
                                </div>
                                <div class="d-flex justify-content-between mt-2" style="font-size: 0.8rem; color: var(--text-secondary);">
                                    <span><i class="bi bi-check-circle"></i> Free: ${drive.free} GB</span>
                                    <span><i class="bi bi-exclamation-circle"></i> Used: ${drive.used} GB</span>
                                </div>
                            </div>
                        `;
                    });

                    document.getElementById('health-data').innerHTML = `
                        <!-- CPU Card -->
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="card metric-card">
                                <div class="card-body">
                                    <div class="icon-wrapper">
                                        <i class="bi bi-cpu-fill"></i>
                                    </div>
                                    <div class="metric-label">CPU Usage</div>
                                    <div class="metric-value ${getStatusClass(data.cpu.usage)}">${data.cpu.usage}%</div>
                                    <div class="progress mb-3">
                                        <div class="progress-bar ${getProgressClass(data.cpu.usage)}" 
                                             style="width: ${data.cpu.usage}%"></div>
                                    </div>
                                    <div class="stat-badge">
                                        <i class="bi bi-arrow-repeat"></i>
                                        ${data.cpu.details.cores} Cores / ${data.cpu.details.threads} Threads
                                    </div>
                                    <div class="stat-badge">
                                        <i class="bi bi-speedometer"></i>
                                        ${data.cpu.details.speed} GHz
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Memory Card -->
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="card metric-card">
                                <div class="card-body">
                                    <div class="icon-wrapper">
                                        <i class="bi bi-memory"></i>
                                    </div>
                                    <div class="metric-label">Memory Usage</div>
                                    <div class="metric-value ${getStatusClass(data.memory.percent)}">${data.memory.percent}%</div>
                                    <div class="progress mb-3">
                                        <div class="progress-bar ${getProgressClass(data.memory.percent)}" 
                                             style="width: ${data.memory.percent}%"></div>
                                    </div>
                                    <div class="stat-badge">
                                        <i class="bi bi-hdd-stack"></i>
                                        ${data.memory.used} GB / ${data.memory.total} GB
                                    </div>
                                    <div class="stat-badge">
                                        <i class="bi bi-check-circle"></i>
                                        ${data.memory.free} GB Free
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Processes Card -->
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="card metric-card">
                                <div class="card-body">
                                    <div class="icon-wrapper">
                                        <i class="bi bi-list-task"></i>
                                    </div>
                                    <div class="metric-label">Running Processes</div>
                                    <div class="metric-value status-good">${data.processes}</div>
                                    <div class="stat-badge">
                                        <i class="bi bi-clock-history"></i>
                                        Uptime: ${data.uptime.formatted}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- OS Info Card -->
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="card metric-card">
                                <div class="card-body">
                                    <div class="icon-wrapper">
                                        <i class="bi bi-windows"></i>
                                    </div>
                                    <div class="metric-label">Operating System</div>
                                    <div style="font-size: 0.9rem; margin: 1rem 0; font-weight: 600;">${data.os.name}</div>
                                    <div class="stat-badge">
                                        <i class="bi bi-code-square"></i>
                                        ${data.os.architecture}
                                    </div>
                                    <div class="stat-badge">
                                        <i class="bi bi-tag"></i>
                                        v${data.os.version}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CPU Details Card -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <span><i class="bi bi-cpu"></i> Processor Details</span>
                                </div>
                                <div class="card-body">
                                    <div class="system-info-detail">
                                        <strong>Model:</strong> ${data.cpu.details.name}
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-6">
                                            <div class="text-center p-3" style="background: rgba(102, 126, 234, 0.1); border-radius: 12px;">
                                                <i class="bi bi-grid-3x3-gap" style="font-size: 2rem; color: #667eea;"></i>
                                                <div class="mt-2" style="font-size: 1.5rem; font-weight: 700;">${data.cpu.details.cores}</div>
                                                <div style="font-size: 0.85rem; color: var(--text-secondary);">Physical Cores</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-3" style="background: rgba(56, 239, 125, 0.1); border-radius: 12px;">
                                                <i class="bi bi-bezier2" style="font-size: 2rem; color: #38ef7d;"></i>
                                                <div class="mt-2" style="font-size: 1.5rem; font-weight: 700;">${data.cpu.details.threads}</div>
                                                <div style="font-size: 0.85rem; color: var(--text-secondary);">Logical Threads</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Storage Devices Card -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <span><i class="bi bi-hdd-rack"></i> Storage Devices</span>
                                </div>
                                <div class="card-body">
                                    ${disksHtml}
                                </div>
                            </div>
                        </div>

                        <!-- Network Interfaces Card -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <span><i class="bi bi-ethernet"></i> Network Interfaces</span>
                                    <span class="badge bg-success">${data.network.length} Active</span>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        ${data.network.map(iface => `
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="network-item">
                                                    <div class="network-name">
                                                        <i class="bi bi-router"></i> ${iface.name}
                                                    </div>
                                                    <div class="network-mac">
                                                        <i class="bi bi-shield-check"></i> ${iface.mac}
                                                    </div>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                })
                .catch(error => {
                    document.getElementById('health-data').innerHTML = `
                        <div class="col-12">
                            <div class="alert alert-danger text-center">
                                <i class="bi bi-exclamation-triangle"></i> Error fetching health data. Make sure the PHP server is running.
                            </div>
                        </div>
                    `;
                    console.error('Error:', error);
                });
        }

        // Initial load with a small delay to let the page render
        setTimeout(() => {
            updateHealth();
        }, 100);

        // Update every 5 seconds
        setInterval(updateHealth, 5000);
    </script>
</body>
</html>