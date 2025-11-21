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
html, body {
    height: 100vh;
    overflow: hidden;
}
#app-container {
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
main {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    padding: 0.5rem;
    background-color: #f9fafb;
}
</style>

<!-- Main Content -->
<main class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-3 py-2 bg-white rounded shadow-sm border border-gray-200 mb-2">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">Generate Reports</h3>
                <p class="text-[10px] text-gray-500 mt-0.5">Export system data and analytics</p>
            </div>
        </div>

        <!-- Reports Grid -->
        <div class="flex-1 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                
                <!-- User Report -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-4 hover:shadow-md transition">
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 bg-[#1E3A8A] bg-opacity-10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-users text-[#1E3A8A] text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-gray-800 mb-1">Users Report</h4>
                            <p class="text-[10px] text-gray-500 mb-3">Export all user accounts with details</p>
                            <button onclick="generateReport('users')" class="w-full px-3 py-1.5 bg-[#1E3A8A] text-white text-xs rounded hover:bg-[#152e6e] transition">
                                <i class="fas fa-download mr-1"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Assets Report -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-4 hover:shadow-md transition">
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 bg-[#1E3A8A] bg-opacity-10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-box text-[#1E3A8A] text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-gray-800 mb-1">Assets Report</h4>
                            <p class="text-[10px] text-gray-500 mb-3">Export inventory and asset details</p>
                            <button onclick="generateReport('assets')" class="w-full px-3 py-1.5 bg-[#1E3A8A] text-white text-xs rounded hover:bg-[#152e6e] transition">
                                <i class="fas fa-download mr-1"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tickets Report -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-4 hover:shadow-md transition">
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 bg-[#1E3A8A] bg-opacity-10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-ticket text-[#1E3A8A] text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-gray-800 mb-1">Tickets Report</h4>
                            <p class="text-[10px] text-gray-500 mb-3">Export all support tickets and status</p>
                            <button onclick="generateReport('tickets')" class="w-full px-3 py-1.5 bg-[#1E3A8A] text-white text-xs rounded hover:bg-[#152e6e] transition">
                                <i class="fas fa-download mr-1"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Borrowing Report -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-4 hover:shadow-md transition">
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 bg-[#1E3A8A] bg-opacity-10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-clipboard-check text-[#1E3A8A] text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-gray-800 mb-1">Borrowing Report</h4>
                            <p class="text-[10px] text-gray-500 mb-3">Export asset borrowing records</p>
                            <button onclick="generateReport('borrowing')" class="w-full px-3 py-1.5 bg-[#1E3A8A] text-white text-xs rounded hover:bg-[#152e6e] transition">
                                <i class="fas fa-download mr-1"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PC Health Report -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-4 hover:shadow-md transition">
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 bg-[#1E3A8A] bg-opacity-10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-heartbeat text-[#1E3A8A] text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-gray-800 mb-1">PC Health Report</h4>
                            <p class="text-[10px] text-gray-500 mb-3">Export system health data</p>
                            <button onclick="generateReport('pc_health')" class="w-full px-3 py-1.5 bg-[#1E3A8A] text-white text-xs rounded hover:bg-[#152e6e] transition">
                                <i class="fas fa-download mr-1"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Activity Summary Report -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-4 hover:shadow-md transition">
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 bg-[#1E3A8A] bg-opacity-10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-chart-line text-[#1E3A8A] text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-gray-800 mb-1">Activity Summary</h4>
                            <p class="text-[10px] text-gray-500 mb-3">Export overall system activity summary</p>
                            <button onclick="generateReport('summary')" class="w-full px-3 py-1.5 bg-[#1E3A8A] text-white text-xs rounded hover:bg-[#152e6e] transition">
                                <i class="fas fa-download mr-1"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Custom Date Range Report -->
            <div class="bg-white rounded shadow-sm border border-gray-200 p-4 mt-3">
                <h4 class="text-sm font-semibold text-gray-800 mb-3">
                    <i class="fas fa-calendar-alt mr-2 text-[#1E3A8A]"></i>Custom Date Range Report
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                    <div>
                        <label class="block text-[10px] text-gray-600 mb-1">Report Type</label>
                        <select id="customReportType" onchange="previewCustomReport()" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
                            <option value="tickets">Tickets</option>
                            <option value="borrowing">Borrowing</option>
                            <option value="assets">Assets</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] text-gray-600 mb-1">Start Date</label>
                        <input type="date" id="startDate" onchange="previewCustomReport()" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
                    </div>
                    <div>
                        <label class="block text-[10px] text-gray-600 mb-1">End Date</label>
                        <input type="date" id="endDate" onchange="previewCustomReport()" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
                    </div>
                    <div class="flex items-end gap-2">
                        <button onclick="previewCustomReport()" class="flex-1 px-3 py-1.5 bg-gray-600 text-white text-xs rounded hover:bg-gray-700 transition">
                            <i class="fas fa-eye mr-1"></i>Preview
                        </button>
                        <button onclick="generateCustomReport()" class="flex-1 px-3 py-1.5 bg-[#1E3A8A] text-white text-xs rounded hover:bg-[#152e6e] transition">
                            <i class="fas fa-download mr-1"></i>Export
                        </button>
                    </div>
                </div>
                
                <!-- Preview Table -->
                <div id="previewContainer" class="hidden">
                    <div class="flex items-center justify-between mb-2 pb-2 border-b">
                        <div>
                            <h5 class="text-xs font-semibold text-gray-800">Preview</h5>
                            <p id="previewInfo" class="text-[10px] text-gray-500"></p>
                        </div>
                        <span id="previewCount" class="text-xs text-gray-600"></span>
                    </div>
                    <div class="overflow-x-auto border rounded max-h-96">
                        <table id="previewTable" class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-[#1E3A8A] text-white sticky top-0">
                                <tr id="previewTableHead"></tr>
                            </thead>
                            <tbody id="previewTableBody" class="bg-white divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                    <div id="previewEmpty" class="hidden text-center py-8 text-xs text-gray-500">
                        <i class="fas fa-inbox text-3xl text-gray-300 mb-2"></i>
                        <p>No records found for the selected date range</p>
                    </div>
                </div>
                
                <div id="previewLoading" class="hidden text-center py-8">
                    <i class="fas fa-spinner fa-spin text-2xl text-[#1E3A8A] mb-2"></i>
                    <p class="text-xs text-gray-600">Loading preview...</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../components/layout_footer.php'; ?>

<script>
function generateReport(reportType) {
    // Show loading state
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Generating...';
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../../controller/generate_report.php';
    form.target = '_blank';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'report_type';
    input.value = reportType;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    // Reset button after 2 seconds
    setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }, 2000);
}

function generateCustomReport() {
    const reportType = document.getElementById('customReportType').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        alert('Please select both start and end dates');
        return;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        alert('Start date cannot be after end date');
        return;
    }
    
    // Show loading state
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Generating...';
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../../controller/generate_report.php';
    form.target = '_blank';
    
    const inputs = [
        { name: 'report_type', value: reportType },
        { name: 'start_date', value: startDate },
        { name: 'end_date', value: endDate }
    ];
    
    inputs.forEach(data => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = data.name;
        input.value = data.value;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    // Reset button after 2 seconds
    setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }, 2000);
}

// Set default dates (last 30 days)
document.addEventListener('DOMContentLoaded', function() {
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 30);
    
    document.getElementById('endDate').valueAsDate = endDate;
    document.getElementById('startDate').valueAsDate = startDate;
});

// Preview Custom Report Data
async function previewCustomReport() {
    const reportType = document.getElementById('customReportType').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        return;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        alert('Start date cannot be after end date');
        return;
    }
    
    const previewContainer = document.getElementById('previewContainer');
    const previewLoading = document.getElementById('previewLoading');
    const previewTable = document.getElementById('previewTable');
    const previewEmpty = document.getElementById('previewEmpty');
    const previewInfo = document.getElementById('previewInfo');
    const previewCount = document.getElementById('previewCount');
    
    // Show loading
    previewContainer.classList.add('hidden');
    previewLoading.classList.remove('hidden');
    
    try {
        const formData = new FormData();
        formData.append('action', 'preview_report');
        formData.append('report_type', reportType);
        formData.append('start_date', startDate);
        formData.append('end_date', endDate);
        
        const response = await fetch('../../controller/preview_report.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        previewLoading.classList.add('hidden');
        
        if (result.success && result.data && result.data.length > 0) {
            // Show preview container
            previewContainer.classList.remove('hidden');
            previewEmpty.classList.add('hidden');
            previewTable.classList.remove('hidden');
            
            // Update info
            previewInfo.textContent = `${reportType.charAt(0).toUpperCase() + reportType.slice(1)} from ${startDate} to ${endDate}`;
            previewCount.textContent = `${result.data.length} record${result.data.length !== 1 ? 's' : ''}`;
            
            // Build table headers
            const headers = result.headers || Object.keys(result.data[0]);
            const headerRow = document.getElementById('previewTableHead');
            headerRow.innerHTML = headers.map(h => 
                `<th class="px-3 py-2 text-left text-[10px] font-medium uppercase">${h}</th>`
            ).join('');
            
            // Build table body (show first 50 rows)
            const tbody = document.getElementById('previewTableBody');
            tbody.innerHTML = result.data.slice(0, 50).map(row => {
                const cells = headers.map(h => {
                    let value = row[h] || '-';
                    // Truncate long values
                    if (typeof value === 'string' && value.length > 50) {
                        value = value.substring(0, 50) + '...';
                    }
                    return `<td class="px-3 py-2 text-xs text-gray-600">${value}</td>`;
                }).join('');
                return `<tr class="hover:bg-gray-50">${cells}</tr>`;
            }).join('');
            
            if (result.data.length > 50) {
                tbody.innerHTML += `<tr><td colspan="${headers.length}" class="px-3 py-2 text-center text-xs text-gray-500 bg-gray-50">
                    Showing first 50 of ${result.data.length} records. Export to view all.
                </td></tr>`;
            }
            
        } else {
            previewContainer.classList.remove('hidden');
            previewTable.classList.add('hidden');
            previewEmpty.classList.remove('hidden');
        }
        
    } catch (error) {
        console.error('Preview error:', error);
        previewLoading.classList.add('hidden');
        alert('Failed to load preview. Please try again.');
    }
}
</script>
