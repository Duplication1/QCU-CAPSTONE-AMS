<?php
/**
 * Reusable Header Component
 * 
 * This component generates the top navigation header that appears across all role dashboards.
 * It includes the mobile menu button, logo, title, and user information.
 * 
 * Usage: include 'components/header.php';
 * Make sure $_SESSION is available before including this component.
 */

// Define titles for each role
$role_titles = [
    'Administrator' => 'Administrator Panel',
    'LaboratoryStaff' => 'Laboratory Staff Panel',
    'Laboratory Staff' => 'Laboratory Staff Panel', // Handle both formats
    'Student' => 'Student Portal',
    'Faculty' => 'Faculty Portal',
    'StudentFaculty' => 'Student/Faculty Portal',
    'Technician' => 'Technician Panel'
];

$current_role = $_SESSION['role'] ?? 'Student';
$role_title = $role_titles[$current_role] ?? 'Student Portal';
$user_name = $_SESSION['full_name'] ?? 'User';
$user_initial = strtoupper(substr($user_name, 0, 1));
?>

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
                        <p class="text-sm text-gray-500">Asset Management System - <?php echo htmlspecialchars($role_title); ?></p>
                    </div>
                </div>
            </div>

            <!-- User Info -->
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($user_name); ?></p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($current_role); ?></p>
                </div>
                <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-blue-700 rounded-full flex items-center justify-center">
                    <span class="text-white text-sm font-semibold"><?php echo $user_initial; ?></span>
                </div>
            </div>
        </div>
    </div>
</header>