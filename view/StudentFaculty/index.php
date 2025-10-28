<?php
session_start();

// Check if user is logged in and has student or faculty role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || !in_array($_SESSION['role'], ['Student', 'Faculty'])) {
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
    <title>AMS - <?php echo htmlspecialchars($_SESSION['role']); ?> Dashboard</title>
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
                    <p class="text-xs text-blue-100 whitespace-nowrap"><?php echo htmlspecialchars($_SESSION['role']); ?> Portal</p>
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

            <!-- Browse Equipment -->
            <a href="#browse" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Browse Equipment</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-green-600 rounded-full"></div>
                </div>
            </a>

            <!-- My Requests -->
            <a href="#requests" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-purple-50 hover:text-purple-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7l2 2 4-4"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">My Requests</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-purple-600 rounded-full"></div>
                </div>
            </a>

            <!-- Borrowed Items -->
            <a href="#borrowed" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-indigo-50 hover:text-indigo-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Borrowed Items</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-indigo-600 rounded-full"></div>
                </div>
            </a>

            <!-- Request History -->
            <a href="#history" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-orange-50 hover:text-orange-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Request History</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-orange-600 rounded-full"></div>
                </div>
            </a>

            <!-- My Profile -->
            <a href="#profile" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-teal-50 hover:text-teal-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">My Profile</span>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                    <div class="w-1 h-6 bg-teal-600 rounded-full"></div>
                </div>
            </a>

            <!-- Help & Support -->
            <a href="#help" class="group flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-pink-50 hover:text-pink-700 transition-all duration-200">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Help & Support</span>
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
                                <p class="text-sm text-gray-500">Asset Management System - <?php echo htmlspecialchars($_SESSION['role']); ?> Portal</p>
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
                
            </div>
        </nav>

        
    </div>

    <!-- Main Content -->
    <div id="main-content" class="content-expanded transition-all duration-300">
       
        <!-- Page Content -->
        <main class="p-6">
        <!-- Welcome Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
            <p class="text-gray-600">
                <?php echo $_SESSION['role'] === 'Student' ? 'Access equipment for your academic projects and research.' : 'Manage and borrow equipment for your teaching and research activities.'; ?>
            </p>
        </div>

        <!-- Dashboard Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Available Equipment -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Available Equipment</h3>
                        <p class="text-sm text-gray-600">Browse and request</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="text-blue-600 hover:text-blue-800 font-semibold text-sm">Browse Equipment →</button>
                </div>
            </div>

            <!-- My Requests -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">My Requests</h3>
                        <p class="text-sm text-gray-600">Track your requests</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7l2 2 4-4"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="text-green-600 hover:text-green-800 font-semibold text-sm">View Requests →</button>
                </div>
            </div>

            <!-- Borrowed Items -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Borrowed Items</h3>
                        <p class="text-sm text-gray-600">Currently with you</p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="text-yellow-600 hover:text-yellow-800 font-semibold text-sm">View Items →</button>
                </div>
            </div>

            <!-- Request History -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Request History</h3>
                        <p class="text-sm text-gray-600">Past borrowing records</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="text-purple-600 hover:text-purple-800 font-semibold text-sm">View History →</button>
                </div>
            </div>

            <!-- User Profile -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">My Profile</h3>
                        <p class="text-sm text-gray-600">Account settings</p>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm">View Profile →</button>
                </div>
            </div>

            <!-- Help & Support -->
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Help & Support</h3>
                        <p class="text-sm text-gray-600">Get assistance</p>
                    </div>
                    <div class="bg-gray-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="text-gray-600 hover:text-gray-800 font-semibold text-sm">Get Help →</button>
                </div>
            </div>
        </div>

        <!-- Dashboard Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Quick Summary -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Quick Summary</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Active Requests</span>
                        <span class="text-xl font-bold text-blue-600">--</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Items on Loan</span>
                        <span class="text-xl font-bold text-yellow-600">--</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Pending Returns</span>
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
                            <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['role']); ?> Portal Access</p>
                            <p class="text-xs text-gray-600">Welcome to your equipment portal</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($_SESSION['role'] === 'Faculty'): ?>
        <!-- Faculty-specific Features -->
        <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Faculty Features</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-blue-800">Bulk Equipment Requests</h4>
                    <p class="text-sm text-blue-600 mt-1">Request multiple items for classroom use</p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-green-800">Extended Loan Periods</h4>
                    <p class="text-sm text-green-600 mt-1">Request longer borrowing periods for research</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
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