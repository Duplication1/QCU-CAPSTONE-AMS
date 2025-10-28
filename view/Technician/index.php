<?php
session_start();

// Check if user is logged in and has technician role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Technician') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMS - Technician Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Mobile Menu Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"></div>
    
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed top-0 left-0 z-50 h-screen transition-transform duration-300 ease-in-out bg-white shadow-xl border-r border-gray-200 w-64 lg:translate-x-0 -translate-x-full flex flex-col">
        <!-- Sidebar Header -->
        <div id="sidebar-header" class="relative flex items-center justify-between p-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700 transition-all duration-300 flex-shrink-0">
            <!-- Logo/Toggle Button Container -->
            <div class="flex items-center space-x-3">
                <!-- Desktop Toggle Button - positioned where logo was -->
                <button id="sidebar-toggle" class="hidden lg:flex p-2 rounded-lg text-white hover:bg-blue-500 transition-all duration-300">
                    <svg id="toggle-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7"></path>
                    </svg>
                </button>
                
                <div id="sidebar-brand" class="text-white overflow-hidden transition-all duration-300">
                    <h2 class="text-lg font-bold whitespace-nowrap">QCU AMS</h2>
                    <p class="text-xs text-blue-100 whitespace-nowrap">Technician Panel</p>
                </div>
            </div>
            
            <!-- Mobile Close Button -->
            <button id="mobile-close" class="lg:hidden p-2 rounded-lg text-white hover:bg-blue-500 transition-colors duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto overflow-x-hidden">
            <!-- Dashboard -->
            <a href="#dashboard" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-700 transition-all duration-200 active:bg-blue-100">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2H10a2 2 0 01-2-2V5z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Dashboard</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
                </div>
            </a>

            <!-- Maintenance Tasks -->
            <a href="#maintenance" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-orange-50 hover:text-orange-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Maintenance Tasks</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-orange-600 rounded-full"></div>
                </div>
            </a>

            <!-- Repair Requests -->
            <a href="#repairs" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-red-50 hover:text-red-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Repair Requests</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-red-600 rounded-full"></div>
                </div>
            </a>

            <!-- Equipment Status -->
            <a href="#status" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Equipment Status</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-green-600 rounded-full"></div>
                </div>
            </a>

            <!-- Work Orders -->
            <a href="#orders" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-purple-50 hover:text-purple-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7l2 2 4-4"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Work Orders</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-purple-600 rounded-full"></div>
                </div>
            </a>

            <!-- Parts Inventory -->
            <a href="#inventory" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-indigo-50 hover:text-indigo-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Parts Inventory</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-indigo-600 rounded-full"></div>
                </div>
            </a>

            <!-- Reports -->
            <a href="#reports" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-teal-50 hover:text-teal-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Reports</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-teal-600 rounded-full"></div>
                </div>
            </a>
        </nav>

        <!-- Logout at Bottom -->
        <div class="border-t border-gray-200 p-4 flex-shrink-0">
            <a href="../../controller/logout_controller.php" class="group flex items-center px-3 py-2.5 text-sm font-medium text-red-600 rounded-lg hover:bg-red-50 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div id="main-wrapper" class="lg:ml-64 transition-all duration-300 ease-in-out">
        <!-- Top Navigation Bar -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Mobile Menu Button & Logo -->
                    <div class="flex items-center space-x-4">
                        <button id="mobile-menu-btn" class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <div class="flex items-center space-x-3">
                            <img src="../../assets/images/QCU-LOGO.png" alt="QCU Logo" class="w-8 h-8">
                            <div class="hidden sm:block">
                                <h1 class="text-xl font-semibold text-gray-900">Quezon City University</h1>
                                <p class="text-sm text-gray-500">Asset Management System - Technician Panel</p>
                            </div>
                        </div>
                    </div>

                    <!-- User Info -->
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                        </div>
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-blue-700 rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-semibold"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="p-4 sm:p-6 space-y-6">
            <!-- Welcome Section -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl shadow-lg p-6 text-white">
                <h2 class="text-2xl sm:text-3xl font-bold mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                <p class="text-blue-100">Manage equipment maintenance, repairs, and technical support tasks efficiently.</p>
            </div>

            <!-- Dashboard Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Maintenance Tasks -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-orange-200 group">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-orange-700 transition-colors">Maintenance</h3>
                            <p class="text-sm text-gray-600 mt-1">Active tasks</p>
                        </div>
                        <div class="bg-orange-50 group-hover:bg-orange-100 p-3 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <span class="text-2xl font-bold text-orange-600">0</span>
                        <button class="block text-orange-600 hover:text-orange-800 font-semibold text-sm mt-2 flex items-center space-x-2 group-hover:translate-x-1 transition-transform">
                            <span>View Tasks</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Repair Requests -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-red-200 group">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-red-700 transition-colors">Repairs</h3>
                            <p class="text-sm text-gray-600 mt-1">Pending requests</p>
                        </div>
                        <div class="bg-red-50 group-hover:bg-red-100 p-3 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <span class="text-2xl font-bold text-red-600">0</span>
                        <button class="block text-red-600 hover:text-red-800 font-semibold text-sm mt-2 flex items-center space-x-2 group-hover:translate-x-1 transition-transform">
                            <span>View Requests</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Equipment Status -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-green-200 group">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-green-700 transition-colors">Equipment</h3>
                            <p class="text-sm text-gray-600 mt-1">Monitor status</p>
                        </div>
                        <div class="bg-green-50 group-hover:bg-green-100 p-3 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <button class="text-green-600 hover:text-green-800 font-semibold text-sm flex items-center space-x-2 group-hover:translate-x-1 transition-transform">
                            <span>View Status</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Work Orders -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-purple-200 group">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-purple-700 transition-colors">Work Orders</h3>
                            <p class="text-sm text-gray-600 mt-1">Manage tasks</p>
                        </div>
                        <div class="bg-purple-50 group-hover:bg-purple-100 p-3 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7l2 2 4-4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <button class="text-purple-600 hover:text-purple-800 font-semibold text-sm flex items-center space-x-2 group-hover:translate-x-1 transition-transform">
                            <span>View Orders</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Technical Dashboard -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Maintenance Schedule -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Maintenance Schedule
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-start p-4 bg-yellow-50 rounded-lg border-l-4 border-yellow-500">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <p class="text-sm font-semibold text-gray-800">Preventive Maintenance</p>
                                <p class="text-xs text-gray-600 mt-1">Weekly equipment checks scheduled</p>
                                <span class="inline-block text-xs bg-yellow-200 text-yellow-800 px-2 py-1 rounded mt-2">Upcoming</span>
                            </div>
                        </div>
                        <div class="flex items-start p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <p class="text-sm font-semibold text-gray-800">Calibration Schedule</p>
                                <p class="text-xs text-gray-600 mt-1">Monthly calibration tasks</p>
                                <span class="inline-block text-xs bg-blue-200 text-blue-800 px-2 py-1 rounded mt-2">Scheduled</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Equipment Health -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        Equipment Health
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                            <span class="text-gray-700 font-medium">Operational</span>
                            <span class="text-2xl font-bold text-green-600">0</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg">
                            <span class="text-gray-700 font-medium">Under Maintenance</span>
                            <span class="text-2xl font-bold text-yellow-600">0</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                            <span class="text-gray-700 font-medium">Out of Service</span>
                            <span class="text-2xl font-bold text-red-600">0</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Quick Actions
                    </h3>
                    <div class="space-y-3">
                        <button class="w-full text-left p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-all duration-200 border border-green-200 hover:border-green-300 group">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-green-800">Create Work Order</p>
                                    <p class="text-xs text-green-600">Start a new maintenance task</p>
                                </div>
                                <svg class="w-4 h-4 text-green-600 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </button>
                        <button class="w-full text-left p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-all duration-200 border border-blue-200 hover:border-blue-300 group">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-blue-800">Update Equipment Status</p>
                                    <p class="text-xs text-blue-600">Change equipment availability</p>
                                </div>
                                <svg class="w-4 h-4 text-blue-600 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </button>
                        <button class="w-full text-left p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-all duration-200 border border-purple-200 hover:border-purple-300 group">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-purple-800">Generate Report</p>
                                    <p class="text-xs text-purple-600">Create maintenance report</p>
                                </div>
                                <svg class="w-4 h-4 text-purple-600 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Current Tasks -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7l2 2 4-4"></path>
                    </svg>
                    Current Tasks
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Task ID</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Equipment</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Type</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Priority</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-gray-100">
                                <td colspan="6" class="text-center py-12">
                                    <div class="flex flex-col items-center space-y-3">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        <p class="text-gray-500 font-medium">No active tasks at the moment</p>
                                        <p class="text-sm text-gray-400">Your active maintenance tasks will appear here</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- JavaScript for Sidebar Toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainWrapper = document.getElementById('main-wrapper');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileClose = document.getElementById('mobile-close');
            const mobileOverlay = document.getElementById('mobile-overlay');
            const navTexts = document.querySelectorAll('.nav-text');
            const sidebarBrand = document.getElementById('sidebar-brand');
            const toggleIcon = document.getElementById('toggle-icon');
            
            let isCollapsed = false;

            // Update desktop sidebar state
            function updateDesktopSidebar() {
                if (isCollapsed) {
                    sidebar.classList.remove('w-64');
                    sidebar.classList.add('w-20');
                    mainWrapper.classList.remove('lg:ml-64');
                    mainWrapper.classList.add('lg:ml-20');
                    hideTexts();
                    toggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>';
                } else {
                    sidebar.classList.remove('w-20');
                    sidebar.classList.add('w-64');
                    mainWrapper.classList.remove('lg:ml-20');
                    mainWrapper.classList.add('lg:ml-64');
                    showAllTexts();
                    toggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7"></path>';
                }
            }

            function hideTexts() {
                navTexts.forEach(text => {
                    text.style.opacity = '0';
                    text.style.width = '0';
                    text.style.overflow = 'hidden';
                });
                sidebarBrand.style.opacity = '0';
                sidebarBrand.style.width = '0';
                sidebarBrand.style.overflow = 'hidden';
            }

            function showAllTexts() {
                navTexts.forEach(text => {
                    text.style.opacity = '1';
                    text.style.width = 'auto';
                    text.style.overflow = 'visible';
                });
                sidebarBrand.style.opacity = '1';
                sidebarBrand.style.width = 'auto';
                sidebarBrand.style.overflow = 'visible';
            }

            // Desktop toggle
            sidebarToggle?.addEventListener('click', function() {
                isCollapsed = !isCollapsed;
                updateDesktopSidebar();
            });

            // Mobile menu toggle
            mobileMenuBtn?.addEventListener('click', function() {
                sidebar.classList.remove('-translate-x-full');
                mobileOverlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            });

            // Mobile close
            mobileClose?.addEventListener('click', function() {
                sidebar.classList.add('-translate-x-full');
                mobileOverlay.classList.add('hidden');
                document.body.style.overflow = 'auto';
            });

            // Mobile overlay click
            mobileOverlay?.addEventListener('click', function() {
                sidebar.classList.add('-translate-x-full');
                mobileOverlay.classList.add('hidden');
                document.body.style.overflow = 'auto';
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    // Desktop view
                    sidebar.classList.add('-translate-x-full', 'lg:translate-x-0');
                    mobileOverlay.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                } else {
                    // Mobile view
                    sidebar.classList.add('-translate-x-full');
                    mobileOverlay.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }
            });

            // Initialize based on screen size
            if (window.innerWidth < 1024) {
                sidebar.classList.add('-translate-x-full');
            }
        });
    </script>
</body>
</html>
                    toggleBtn.innerHTML = `
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                        </svg>
                    `;
                    
                    isExpanded = false;
                } else {
                    // Expand sidebar
                    sidebar.classList.remove('sidebar-collapsed');
                    sidebar.classList.add('sidebar-expanded');
                    mainContent.classList.remove('content-collapsed');
                    mainContent.classList.add('content-expanded');
                    
                    // Show text elements
                    sidebarTexts.forEach(text => {
                        text.classList.remove('sidebar-text-hidden');
                        text.classList.add('sidebar-text');
                    });
                    
                    // Reset toggle button icon
                    toggleBtn.innerHTML = `
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                        </svg>
                    `;
                    
                    isExpanded = true;
                }
            });

            // Handle responsive behavior
            window.addEventListener('resize', function() {
                if (window.innerWidth < 768) {
                    sidebar.classList.add('sidebar-collapsed');
                    sidebar.classList.remove('sidebar-expanded');
                    mainContent.classList.add('content-collapsed');
                    mainContent.classList.remove('content-expanded');
                    isExpanded = false;
                }
            });
        });
    </script>
</body>
</html>