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
        <main class="p-6">
            <!-- Welcome Section -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                <p class="text-gray-600">Manage equipment maintenance, repairs, and ensure optimal asset performance.</p>
            </div>

            <!-- Dashboard Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Maintenance Tasks -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Maintenance Tasks</h3>
                            <p class="text-sm text-gray-600">Scheduled maintenance</p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button class="text-orange-600 hover:text-orange-800 font-semibold text-sm">View Tasks →</button>
                    </div>
                </div>

                <!-- Repair Requests -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Repair Requests</h3>
                            <p class="text-sm text-gray-600">Equipment repairs</p>
                        </div>
                        <div class="bg-red-100 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button class="text-red-600 hover:text-red-800 font-semibold text-sm">View Requests →</button>
                    </div>
                </div>

                <!-- Asset Inspection -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Asset Inspection</h3>
                            <p class="text-sm text-gray-600">Equipment checks</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button class="text-blue-600 hover:text-blue-800 font-semibold text-sm">Start Inspection →</button>
                    </div>
                </div>

                <!-- Inventory Update -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Inventory Update</h3>
                            <p class="text-sm text-gray-600">Asset tracking</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7l2 2 4-4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button class="text-green-600 hover:text-green-800 font-semibold text-sm">Update Inventory →</button>
                    </div>
                </div>

                <!-- Technical Reports -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Technical Reports</h3>
                            <p class="text-sm text-gray-600">Equipment analysis</p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button class="text-purple-600 hover:text-purple-800 font-semibold text-sm">Generate Report →</button>
                    </div>
                </div>

                <!-- Parts & Tools -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Parts & Tools</h3>
                            <p class="text-sm text-gray-600">Resource management</p>
                        </div>
                        <div class="bg-indigo-100 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm">Manage Resources →</button>
                    </div>
                </div>
            </div>

            <!-- Dashboard Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Work Queue -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Work Queue</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Pending Maintenance</span>
                            <span class="text-xl font-bold text-orange-600">--</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Open Repair Requests</span>
                            <span class="text-xl font-bold text-red-600">--</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Inspections Due</span>
                            <span class="text-xl font-bold text-blue-600">--</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Recent Activity</h3>
                    <div class="space-y-3">
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Technician Portal Access</p>
                                <p class="text-xs text-gray-600">Ready to begin maintenance tasks</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Priority Tasks -->
            <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Priority Tasks</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-orange-50 p-4 rounded-lg border-l-4 border-orange-500">
                        <h4 class="font-semibold text-orange-800">Scheduled Maintenance</h4>
                        <p class="text-sm text-orange-600 mt-1">Equipment requiring routine maintenance</p>
                        <button class="text-orange-600 hover:text-orange-800 font-semibold text-sm mt-2 flex items-center space-x-2 group-hover:translate-x-1 transition-transform">
                            <span>View Tasks</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg border-l-4 border-red-500">
                        <h4 class="font-semibold text-red-800">Urgent Repairs</h4>
                        <p class="text-sm text-red-600 mt-1">Equipment requiring immediate attention</p>
                        <button class="text-red-600 hover:text-red-800 font-semibold text-sm mt-2 flex items-center space-x-2 group-hover:translate-x-1 transition-transform">
                            <span>View Repairs</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </main>

<?php include '../components/layout_footer.php'; ?>