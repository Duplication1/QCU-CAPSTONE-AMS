<?php
session_start();

// Check if user is logged in and has administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
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
    <title>AMS - Administrator Dashboard</title>
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
                    <p class="text-xs text-blue-100 whitespace-nowrap">Administrator Panel</p>
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

            <!-- User Management -->
            <a href="#users" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">User Management</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-green-600 rounded-full"></div>
                </div>
            </a>

            <!-- Asset Management -->
            <a href="#assets" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-purple-50 hover:text-purple-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Asset Management</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-purple-600 rounded-full"></div>
                </div>
            </a>

            <!-- Reports -->
            <a href="#reports" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-indigo-50 hover:text-indigo-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Reports</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-indigo-600 rounded-full"></div>
                </div>
            </a>

            <!-- System Settings -->
            <a href="#settings" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-orange-50 hover:text-orange-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">System Settings</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-orange-600 rounded-full"></div>
                </div>
            </a>

            <!-- Audit Logs -->
            <a href="#audit" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-teal-50 hover:text-teal-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Audit Logs</span>
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
                                <p class="text-sm text-gray-500">Asset Management System</p>
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
        <!-- Main Content -->
        <main class="p-4 sm:p-6 lg:p-8">
            <!-- Welcome Section -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl shadow-lg p-6 mb-8 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                        <p class="text-blue-100">Manage your university's assets efficiently with administrator privileges.</p>
                    </div>
                    <div class="hidden sm:block">
                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Users -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Users</p>
                            <p class="text-2xl font-bold text-gray-900">156</p>
                            <p class="text-xs text-green-600 flex items-center mt-1">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                                +12 this month
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Assets -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Assets</p>
                            <p class="text-2xl font-bold text-gray-900">2,847</p>
                            <p class="text-xs text-green-600 flex items-center mt-1">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                                +85 this month
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Pending Requests -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Pending Requests</p>
                            <p class="text-2xl font-bold text-gray-900">24</p>
                            <p class="text-xs text-orange-600 flex items-center mt-1">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Needs attention
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- System Health -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">System Health</p>
                            <p class="text-2xl font-bold text-gray-900">98.5%</p>
                            <p class="text-xs text-green-600 flex items-center mt-1">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                All systems operational
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                <!-- User Management Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-all duration-200 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors duration-200">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Active</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">User Management</h3>
                    <p class="text-sm text-gray-600 mb-4">Add, edit, or remove users. Manage roles and permissions for all system users.</p>
                    <button class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium">
                        Manage Users
                    </button>
                </div>

                <!-- Asset Management Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-all duration-200 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors duration-200">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Active</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Asset Management</h3>
                    <p class="text-sm text-gray-600 mb-4">Track, monitor, and manage all university assets including equipment and facilities.</p>
                    <button class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors duration-200 font-medium">
                        Manage Assets
                    </button>
                </div>

                <!-- Reports Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-all duration-200 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-colors duration-200">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded-full">New</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Generate Reports</h3>
                    <p class="text-sm text-gray-600 mb-4">Create comprehensive reports on asset usage, user activity, and system performance.</p>
                    <button class="w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors duration-200 font-medium">
                        View Reports
                    </button>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                        <button class="text-sm text-blue-600 hover:text-blue-700 font-medium">View All</button>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-start space-x-4">
                        <div class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">System successfully initialized</p>
                            <p class="text-xs text-gray-500 mt-1">Administrator panel is ready for use</p>
                            <p class="text-xs text-gray-400 mt-1">Just now</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">Welcome to QCU Asset Management System</p>
                            <p class="text-xs text-gray-500 mt-1">Your administrator access has been granted</p>
                            <p class="text-xs text-gray-400 mt-1">Today</p>
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