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
    overflow-y: auto;
    padding: 0.5rem;
    background-color: #f9fafb;
}
</style>

        <!-- Main Content -->
        <main>
            <!-- Welcome Section -->
            <div class="bg-white rounded shadow-sm border border-gray-200 p-2 mb-2">
                <h2 class="text-sm font-semibold text-gray-800">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                <p class="text-[10px] text-gray-600 mt-0.5">Manage equipment maintenance, repairs, and ensure optimal asset performance.</p>
            </div>

            <!-- Dashboard Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 mb-2">
                <!-- Maintenance Tasks -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-semibold text-gray-800">Maintenance Tasks</h3>
                            <p class="text-[10px] text-gray-600">Scheduled maintenance</p>
                        </div>
                        <div class="bg-orange-100 p-1.5 rounded">
                            <i class="fas fa-tools text-orange-600 text-sm"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <button class="text-orange-600 hover:text-orange-800 font-medium text-[10px]">View Tasks →</button>
                    </div>
                </div>

                <!-- Repair Requests -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-semibold text-gray-800">Repair Requests</h3>
                            <p class="text-[10px] text-gray-600">Equipment repairs</p>
                        </div>
                        <div class="bg-red-100 p-1.5 rounded">
                            <i class="fas fa-wrench text-red-600 text-sm"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <button class="text-red-600 hover:text-red-800 font-medium text-[10px]">View Requests →</button>
                    </div>
                </div>

                <!-- Asset Inspection -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-semibold text-gray-800">Asset Inspection</h3>
                            <p class="text-[10px] text-gray-600">Equipment checks</p>
                        </div>
                        <div class="bg-blue-100 p-1.5 rounded">
                            <i class="fas fa-clipboard-check text-blue-600 text-sm"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <button class="text-blue-600 hover:text-blue-800 font-medium text-[10px]">Start Inspection →</button>
                    </div>
                </div>

                <!-- Inventory Update -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-semibold text-gray-800">Inventory Update</h3>
                            <p class="text-[10px] text-gray-600">Asset tracking</p>
                        </div>
                        <div class="bg-green-100 p-1.5 rounded">
                            <i class="fas fa-clipboard-list text-green-600 text-sm"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <button class="text-green-600 hover:text-green-800 font-medium text-[10px]">Update Inventory →</button>
                    </div>
                </div>

                <!-- Technical Reports -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-semibold text-gray-800">Technical Reports</h3>
                            <p class="text-[10px] text-gray-600">Equipment analysis</p>
                        </div>
                        <div class="bg-purple-100 p-1.5 rounded">
                            <i class="fas fa-chart-bar text-purple-600 text-sm"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <button class="text-purple-600 hover:text-purple-800 font-medium text-[10px]">Generate Report →</button>
                    </div>
                </div>

                <!-- Parts & Tools -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2 hover:shadow transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-semibold text-gray-800">Parts & Tools</h3>
                            <p class="text-[10px] text-gray-600">Resource management</p>
                        </div>
                        <div class="bg-indigo-100 p-1.5 rounded">
                            <i class="fas fa-box text-indigo-600 text-sm"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <button class="text-indigo-600 hover:text-indigo-800 font-medium text-[10px]">Manage Resources →</button>
                    </div>
                </div>
            </div>

            <!-- Dashboard Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-2 mb-2">
                <!-- Work Queue -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                    <h3 class="text-xs font-semibold text-gray-800 mb-2">Work Queue</h3>
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center py-1 border-b border-gray-100">
                            <span class="text-[10px] text-gray-600">Pending Maintenance</span>
                            <span class="text-sm font-semibold text-orange-600">--</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-gray-100">
                            <span class="text-[10px] text-gray-600">Open Repair Requests</span>
                            <span class="text-sm font-semibold text-red-600">--</span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-[10px] text-gray-600">Inspections Due</span>
                            <span class="text-sm font-semibold text-blue-600">--</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                    <h3 class="text-xs font-semibold text-gray-800 mb-2">Recent Activity</h3>
                    <div class="space-y-1.5">
                        <div class="flex items-center p-2 bg-gray-50 rounded">
                            <div class="w-1.5 h-1.5 bg-green-500 rounded-full mr-2"></div>
                            <div>
                                <p class="text-[10px] font-semibold text-gray-800">Technician Portal Access</p>
                                <p class="text-[10px] text-gray-600">Ready to begin maintenance tasks</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Priority Tasks -->
            <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
                <h3 class="text-xs font-semibold text-gray-800 mb-2">Priority Tasks</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div class="bg-orange-50 p-2 rounded border-l-2 border-orange-500">
                        <h4 class="text-[10px] font-semibold text-orange-800">Scheduled Maintenance</h4>
                        <p class="text-[10px] text-orange-600 mt-0.5">Equipment requiring routine maintenance</p>
                        <button class="text-orange-600 hover:text-orange-800 font-medium text-[10px] mt-1 flex items-center gap-1">
                            <span>View Tasks</span>
                            <i class="fas fa-arrow-right text-[8px]"></i>
                        </button>
                    </div>
                    <div class="bg-red-50 p-2 rounded border-l-2 border-red-500">
                        <h4 class="text-[10px] font-semibold text-red-800">Urgent Repairs</h4>
                        <p class="text-[10px] text-red-600 mt-0.5">Equipment requiring immediate attention</p>
                        <button class="text-red-600 hover:text-red-800 font-medium text-[10px] mt-1 flex items-center gap-1">
                            <span>View Repairs</span>
                            <i class="fas fa-arrow-right text-[8px]"></i>
                        </button>
                    </div>
                </div>
            </div>
        </main>

<?php include '../components/layout_footer.php'; ?>