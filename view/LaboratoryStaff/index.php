<?php
session_start();

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
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
    <title>AMS - Laboratory Staff Dashboard</title>
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
                    <p class="text-xs text-blue-100 whitespace-nowrap">Laboratory Staff</p>
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

            <!-- Lab Equipment -->
            <a href="#equipment" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Lab Equipment</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-green-600 rounded-full"></div>
                </div>
            </a>

            <!-- Asset Checkout -->
            <a href="#checkout" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-purple-50 hover:text-purple-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Asset Checkout</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-purple-600 rounded-full"></div>
                </div>
            </a>

            <!-- Asset Return -->
            <a href="#return" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-indigo-50 hover:text-indigo-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Asset Return</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-indigo-600 rounded-full"></div>
                </div>
            </a>

            <!-- Maintenance -->
            <a href="#maintenance" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-orange-50 hover:text-orange-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Maintenance</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-orange-600 rounded-full"></div>
                </div>
            </a>

            <!-- Usage Reports -->
            <a href="#reports" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-teal-50 hover:text-teal-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Usage Reports</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-teal-600 rounded-full"></div>
                </div>
            </a>

            <!-- Inventory -->
            <a href="#inventory" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-pink-50 hover:text-pink-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Inventory</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-pink-600 rounded-full"></div>
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
                                <p class="text-sm text-gray-500">Asset Management System - Laboratory Staff</p>
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

        <!-- Logout at bottom -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200">
            <a href="../../controller/logout_controller.php" class="flex items-center px-4 py-3 text-red-600 rounded-lg hover:bg-red-50 transition-colors duration-200">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span class="sidebar-text transition-all duration-300">Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div id="main-content" class="content-expanded transition-all duration-300">
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
                                <p class="text-sm text-gray-500">Asset Management System - Laboratory Staff Panel</p>
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
    </div>

    <!-- JavaScript for Sidebar Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainWrapper = document.getElementById('main-wrapper');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileClose = document.getElementById('mobile-close');
            const mobileOverlay = document.getElementById('mobile-overlay');
            const sidebarBrand = document.getElementById('sidebar-brand');
            const navTexts = document.querySelectorAll('.nav-text');
            const toggleIcon = document.getElementById('toggle-icon');
            
            let isCollapsed = false;
            let isMobile = window.innerWidth < 1024;

            // Update mobile state on resize
            window.addEventListener('resize', function() {
                const wasMobile = isMobile;
                isMobile = window.innerWidth < 1024;
                
                if (wasMobile !== isMobile) {
                    if (!isMobile) {
                        // Switching to desktop
                        sidebar.classList.remove('-translate-x-full');
                        mobileOverlay.classList.add('hidden');
                        updateDesktopSidebar();
                    } else {
                        // Switching to mobile
                        sidebar.classList.add('-translate-x-full');
                        sidebar.classList.remove('w-20');
                        sidebar.classList.add('w-64');
                        mainWrapper.classList.remove('lg:ml-20');
                        mainWrapper.classList.add('lg:ml-64');
                        showAllTexts();
                        isCollapsed = false;
                    }
                }
            });

            // Desktop sidebar toggle
            sidebarToggle.addEventListener('click', function() {
                if (!isMobile) {
                    isCollapsed = !isCollapsed;
                    updateDesktopSidebar();
                }
            });

            // Mobile menu toggle
            mobileMenuBtn.addEventListener('click', function() {
                if (isMobile) {
                    sidebar.classList.remove('-translate-x-full');
                    mobileOverlay.classList.remove('hidden');
                }
            });

            // Mobile close button
            mobileClose.addEventListener('click', function() {
                if (isMobile) {
                    sidebar.classList.add('-translate-x-full');
                    mobileOverlay.classList.add('hidden');
                }
            });

            // Mobile overlay click
            mobileOverlay.addEventListener('click', function() {
                if (isMobile) {
                    sidebar.classList.add('-translate-x-full');
                    mobileOverlay.classList.add('hidden');
                }
            });

            function updateDesktopSidebar() {
                if (isCollapsed) {
                    // Collapse sidebar
                    sidebar.classList.remove('w-64');
                    sidebar.classList.add('w-20');
                    mainWrapper.classList.remove('lg:ml-64');
                    mainWrapper.classList.add('lg:ml-20');
                    
                    hideTexts();
                    updateToggleIcon(true);
                } else {
                    // Expand sidebar
                    sidebar.classList.remove('w-20');
                    sidebar.classList.add('w-64');
                    mainWrapper.classList.remove('lg:ml-20');
                    mainWrapper.classList.add('lg:ml-64');
                    
                    showAllTexts();
                    updateToggleIcon(false);
                }
            }

            function hideTexts() {
                sidebarBrand.classList.add('opacity-0', 'scale-0');
                navTexts.forEach(text => {
                    text.classList.add('opacity-0', 'scale-0', 'w-0');
                });
            }

            function showAllTexts() {
                sidebarBrand.classList.remove('opacity-0', 'scale-0');
                navTexts.forEach(text => {
                    text.classList.remove('opacity-0', 'scale-0', 'w-0');
                });
            }

            function updateToggleIcon(collapsed) {
                if (collapsed) {
                    toggleIcon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7"></path>
                    `;
                } else {
                    toggleIcon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7"></path>
                    `;
                }
            }

            // Active navigation highlighting
            const navLinks = document.querySelectorAll('nav a');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Remove active class from all links
                    navLinks.forEach(l => l.classList.remove('bg-blue-50', 'text-blue-700', 'border-r-2', 'border-blue-600'));
                    // Add active class to clicked link
                    this.classList.add('bg-blue-50', 'text-blue-700', 'border-r-2', 'border-blue-600');
                });
            });

            // Set default active state for dashboard
            if (navLinks[0]) {
                navLinks[0].classList.add('bg-blue-50', 'text-blue-700', 'border-r-2', 'border-blue-600');
            }
        });
    </script>
</body>
</html>