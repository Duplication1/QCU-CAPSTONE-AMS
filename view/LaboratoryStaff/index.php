<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
include '../components/layout_header.php';
?>

        <!-- Main Content -->
        <main class="p-4 sm:p-6 space-y-6">
            <!-- Welcome Section -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl shadow-lg p-6 text-white">
                <h2 class="text-2xl sm:text-3xl font-bold mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                <p class="text-blue-100">Manage laboratory equipment and coordinate asset usage efficiently.</p>
            </div>

            <!-- Dashboard Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Lab Equipment -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-blue-200 group">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-blue-700 transition-colors">Lab Equipment</h3>
                            <p class="text-sm text-gray-600 mt-1">Manage laboratory assets</p>
                        </div>
                        <div class="bg-blue-50 group-hover:bg-blue-100 p-3 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <button class="text-blue-600 hover:text-blue-800 font-semibold text-sm flex items-center space-x-2 group-hover:translate-x-1 transition-transform">
                            <span>View Equipment</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Asset Checkout -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-green-200 group">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-green-700 transition-colors">Asset Checkout</h3>
                            <p class="text-sm text-gray-600 mt-1">Process equipment loans</p>
                        </div>
                        <div class="bg-green-50 group-hover:bg-green-100 p-3 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <button class="text-green-600 hover:text-green-800 font-semibold text-sm flex items-center space-x-2 group-hover:translate-x-1 transition-transform">
                            <span>Checkout Assets</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Asset Return -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-orange-200 group">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-orange-700 transition-colors">Asset Return</h3>
                            <p class="text-sm text-gray-600 mt-1">Process returns</p>
                        </div>
                        <div class="bg-orange-50 group-hover:bg-orange-100 p-3 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m5 5v1a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <button class="text-orange-600 hover:text-orange-800 font-semibold text-sm flex items-center space-x-2 group-hover:translate-x-1 transition-transform">
                            <span>Return Assets</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- PC Health Monitor -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-cyan-200 group">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-cyan-700 transition-colors">PC Health Monitor</h3>
                            <p class="text-sm text-gray-600 mt-1">Real-time PC monitoring</p>
                        </div>
                        <div class="bg-cyan-50 group-hover:bg-cyan-100 p-3 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <a href="pc_health_dashboard.php" class="text-cyan-600 hover:text-cyan-800 font-semibold text-sm flex items-center space-x-2 group-hover:translate-x-1 transition-transform">
                            <span>View Dashboard</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Maintenance -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-purple-200 group">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-purple-700 transition-colors">Maintenance</h3>
                            <p class="text-sm text-gray-600 mt-1">Track equipment status</p>
                        </div>
                        <div class="bg-purple-50 group-hover:bg-purple-100 p-3 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <button class="text-purple-600 hover:text-purple-800 font-semibold text-sm flex items-center space-x-2 group-hover:translate-x-1 transition-transform">
                            <span>View Maintenance</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Usage Reports -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-indigo-200 group">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-indigo-700 transition-colors">Usage Reports</h3>
                            <p class="text-sm text-gray-600 mt-1">Analytics & reports</p>
                        </div>
                        <div class="bg-indigo-50 group-hover:bg-indigo-100 p-3 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <button class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm flex items-center space-x-2 group-hover:translate-x-1 transition-transform">
                            <span>View Reports</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Inventory -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-teal-200 group">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-teal-700 transition-colors">Inventory</h3>
                            <p class="text-sm text-gray-600 mt-1">Stock management</p>
                        </div>
                        <div class="bg-teal-50 group-hover:bg-teal-100 p-3 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <button class="text-teal-600 hover:text-teal-800 font-semibold text-sm flex items-center space-x-2 group-hover:translate-x-1 transition-transform">
                            <span>View Inventory</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Laboratory Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Quick Summary -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Lab Overview
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                            <span class="text-gray-700 font-medium">Equipment Available</span>
                            <span class="text-2xl font-bold text-blue-600">0</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg">
                            <span class="text-gray-700 font-medium">Currently Borrowed</span>
                            <span class="text-2xl font-bold text-yellow-600">0</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                            <span class="text-gray-700 font-medium">Under Maintenance</span>
                            <span class="text-2xl font-bold text-red-600">0</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Recent Activity
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-start p-4 bg-gray-50 rounded-lg border-l-4 border-green-500">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <p class="text-sm font-semibold text-gray-800">Lab System Access</p>
                                <p class="text-xs text-gray-600 mt-1">Welcome to Laboratory Staff Portal</p>
                                <p class="text-xs text-gray-500 mt-2">Just now</p>
                            </div>
                        </div>
                        <div class="text-center py-4">
                            <p class="text-sm text-gray-500">Recent lab activities will appear here</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="text-green-600 hover:text-green-800 font-semibold text-sm">Checkout Asset →</button>
                </div>
            </div>

            <!-- Asset Return -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Asset Return</h3>
                        <p class="text-sm text-gray-600">Return equipment</p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="text-yellow-600 hover:text-yellow-800 font-semibold text-sm">Return Asset →</button>
                </div>
            </div>

            <!-- Maintenance Requests -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Maintenance</h3>
                        <p class="text-sm text-gray-600">Equipment maintenance</p>
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

            <!-- Usage Reports -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Usage Reports</h3>
                        <p class="text-sm text-gray-600">Equipment usage stats</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="text-purple-600 hover:text-purple-800 font-semibold text-sm">View Reports →</button>
                </div>
            </div>

            <!-- Asset Inventory -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Inventory</h3>
                        <p class="text-sm text-gray-600">Current stock levels</p>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm">View Inventory →</button>
                </div>
            </div>
        </div>

        <!-- Current Status -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Quick Stats -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Quick Stats</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Available Equipment</span>
                        <span class="text-xl font-bold text-green-600">--</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Checked Out</span>
                        <span class="text-xl font-bold text-blue-600">--</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Under Maintenance</span>
                        <span class="text-xl font-bold text-red-600">--</span>
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
                            <p class="text-sm font-semibold text-gray-800">Laboratory Staff Dashboard</p>
                            <p class="text-xs text-gray-600">Access your equipment management tools</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </main>

<?php include '../components/layout_footer.php'; ?>