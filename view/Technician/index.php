<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has technician role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Technician') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
include '../components/layout_header.php';
?>

<!-- Main Content -->
<main class="p-6 bg-gray-50 min-h-screen">
    <!-- Welcome Section -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow-md p-6 mb-6" style="background: linear-gradient(to right, #2563eb, #1d4ed8);">
        <h2 class="text-2xl font-bold text-white" style="color: white;">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
        <p class="text-blue-100 mt-2" style="color: #dbeafe;">Manage equipment maintenance, repairs, and ensure optimal asset performance.</p>
    </div>

    <!-- Dashboard Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <!-- Maintenance Tasks -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Maintenance Tasks</h3>
                    <p class="text-sm text-gray-600 mt-1">Scheduled maintenance</p>
                </div>
                <div class="bg-orange-100 p-3 rounded-lg">
                    <i class="fas fa-tools text-orange-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <button class="text-orange-600 hover:text-orange-800 font-medium text-sm">View Tasks →</button>
            </div>
        </div>

        <!-- Repair Requests -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Repair Requests</h3>
                    <p class="text-sm text-gray-600 mt-1">Equipment repairs</p>
                </div>
                <div class="bg-red-100 p-3 rounded-lg">
                    <i class="fas fa-wrench text-red-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <button class="text-red-600 hover:text-red-800 font-medium text-sm">View Requests →</button>
            </div>
        </div>

        <!-- Asset Inspection -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Asset Inspection</h3>
                    <p class="text-sm text-gray-600 mt-1">Equipment checks</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="fas fa-clipboard-check text-blue-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <button class="text-blue-600 hover:text-blue-800 font-medium text-sm">Start Inspection →</button>
            </div>
        </div>

        <!-- Inventory Update -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Inventory Update</h3>
                    <p class="text-sm text-gray-600 mt-1">Asset tracking</p>
                </div>
                <div class="bg-green-100 p-3 rounded-lg">
                    <i class="fas fa-clipboard-list text-green-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <button class="text-green-600 hover:text-green-800 font-medium text-sm">Update Inventory →</button>
            </div>
        </div>

        <!-- Technical Reports -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Technical Reports</h3>
                    <p class="text-sm text-gray-600 mt-1">Equipment analysis</p>
                </div>
                <div class="bg-purple-100 p-3 rounded-lg">
                    <i class="fas fa-chart-bar text-purple-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <button class="text-purple-600 hover:text-purple-800 font-medium text-sm">Generate Report →</button>
            </div>
        </div>

        <!-- Parts & Tools -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Parts & Tools</h3>
                    <p class="text-sm text-gray-600 mt-1">Resource management</p>
                </div>
                <div class="bg-indigo-100 p-3 rounded-lg">
                    <i class="fas fa-box text-indigo-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <button class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">Manage Resources →</button>
            </div>
        </div>
    </div>

    <!-- Dashboard Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- Work Queue -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Work Queue</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                    <span class="text-sm text-gray-600">Pending Maintenance</span>
                    <span class="text-xl font-bold text-orange-600">--</span>
                </div>
                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                    <span class="text-sm text-gray-600">Open Repair Requests</span>
                    <span class="text-xl font-bold text-red-600">--</span>
                </div>
                <div class="flex justify-between items-center py-3">
                    <span class="text-sm text-gray-600">Inspections Due</span>
                    <span class="text-xl font-bold text-blue-600">--</span>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Activity</h3>
            <div class="space-y-3">
                <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                    <div class="w-2 h-2 bg-green-500 rounded-full mr-3 mt-1.5"></div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Technician Portal Access</p>
                        <p class="text-sm text-gray-600 mt-1">Ready to begin maintenance tasks</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Priority Tasks -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Priority Tasks</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-orange-50 p-4 rounded-lg border-l-4 border-orange-500">
                <h4 class="text-base font-semibold text-orange-800">Scheduled Maintenance</h4>
                <p class="text-sm text-orange-600 mt-2">Equipment requiring routine maintenance</p>
                <button class="text-orange-600 hover:text-orange-800 font-medium text-sm mt-3 flex items-center gap-2">
                    <span>View Tasks</span>
                    <i class="fas fa-arrow-right text-xs"></i>
                </button>
            </div>
            <div class="bg-red-50 p-4 rounded-lg border-l-4 border-red-500">
                <h4 class="text-base font-semibold text-red-800">Urgent Repairs</h4>
                <p class="text-sm text-red-600 mt-2">Equipment requiring immediate attention</p>
                <button class="text-red-600 hover:text-red-800 font-medium text-sm mt-3 flex items-center gap-2">
                    <span>View Repairs</span>
                    <i class="fas fa-arrow-right text-xs"></i>
                </button>
            </div>
        </div>
    </div>
</main>

<?php include '../components/layout_footer.php'; ?>