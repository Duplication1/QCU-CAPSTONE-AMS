<?php
/**
 * Reusable Sidebar Component
 * 
 * This component generates the sidebar navigation based on the user's role.
 * Navigation items are dynamically populated based on the role parameter.
 * 
 * Usage: include 'components/sidebar.php';
 * Make sure $_SESSION['role'] is set before including this component.
 */

// Define navigation items for each role
$navigation_items = [
    'Administrator' => [
        [
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z M8 5a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2H10a2 2 0 01-2-2V5z',
            'color' => 'yellow',
            'href' => 'index.php'
        ],
        [
            'id' => 'users',
            'label' => 'User Management',
            'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
            'color' => 'yellow',
            'href' => 'users.php'
        ],
        [
            'id' => 'assets',
            'label' => 'Asset Management',
            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            'color' => 'yellow',
            'href' => 'assets.php'
        ],
        [
            'id' => 'analytics',
            'label' => 'Analytics',
            'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            'color' => 'yellow',
            'href' => 'analytics.php'
        ]
    ],
    'LaboratoryStaff' => [
        [
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z M8 5a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2H10a2 2 0 01-2-2V5z',
            'color' => 'yellow',
            'href' => 'index.php'
        ],
        [
            'id' => 'borrowing',
            'label' => 'Borrowing Management',
            'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
            'color' => 'yellow',
            'href' => 'borrowing.php'
        ],
        [
            'id' => 'tickets',
            'label' => 'Ticket Coordination',
            'icon' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
            'color' => 'yellow',
            'href' => 'tickets.php'
        ],
        [
            'id' => 'operations',
            'label' => 'Lab Operations',
            'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
            'color' => 'yellow',
            'href' => 'operations.php'
        ],
        [
            'id' => 'registry',
            'label' => 'Asset Registry',
            'icon' => 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
            'color' => 'yellow',
            'href' => 'registry.php'
        ]
    ],
    'Student' => [
        [
            'id' => 'dashboard',
            'label' => 'Home',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m0 0V9a1 1 0 011-1h2a1 1 0 011 1v12m0 0h3a1 1 0 001-1V10M9 21h6',
            'color' => 'yellow',
            'href' => 'index.php'
        ],
        [
            'id' => 'requests',
            'label' => 'My Requests',
            'icon' => 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7l2 2 4-4',
            'color' => 'yellow',
            'href' => 'requests.php'
        ]
    ],
    'Faculty' => [
        [
            'id' => 'dashboard',
            'label' => 'Home',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m0 0V9a1 1 0 011-1h2a1 1 0 011 1v12m0 0h3a1 1 0 001-1V10M9 21h6',
            'color' => 'yellow',
            'href' => 'index.php'
        ],
        [
            'id' => 'requests',
            'label' => 'My Requests',
            'icon' => 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7l2 2 4-4',
            'color' => 'yellow',
            'href' => 'requests.php'
        ]
    ],
    'Technician' => [
        [
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z M8 5a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2H10a2 2 0 01-2-2V5z',
            'color' => 'yellow',
            'href' => 'index.php'
        ],
        [
            'id' => 'tickets',
            'label' => 'Ticket Management',
            'icon' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
            'color' => 'yellow',
            'href' => 'tickets.php'
        ],
        [
            'id' => 'maintenance',
            'label' => 'Maintenance Tasks',
            'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
            'color' => 'yellow',
            'href' => 'maintenance.php'
        ],
        [
            'id' => 'registry',
            'label' => 'Asset Registry',
            'icon' => 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
            'color' => 'yellow',
            'href' => 'registry.php'
        ]
    ]
];

// Get the current user's role
$current_role = $_SESSION['role'] ?? 'Student';

// Handle role mapping for different role formats
$role_key = $current_role;
if ($current_role === 'StudentFaculty') {
    $role_key = 'Student'; // Default to Student navigation for StudentFaculty
} elseif ($current_role === 'Laboratory Staff') {
    $role_key = 'LaboratoryStaff'; // Map "Laboratory Staff" to "LaboratoryStaff"
}

// Get navigation items for current role
$nav_items = $navigation_items[$role_key] ?? $navigation_items['Student'];

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Define panel titles for each role
$panel_titles = [
    'Administrator' => 'Administrator Panel',
    'LaboratoryStaff' => 'Laboratory Staff Panel',
    'Laboratory Staff' => 'Laboratory Staff Panel', // Handle both formats
    'Student' => 'Student Portal',
    'Faculty' => 'Faculty Portal',
    'StudentFaculty' => 'Student/Faculty Portal',
    'Technician' => 'Technician Panel'
];

$panel_title = $panel_titles[$current_role] ?? 'Student Portal';
?>

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
                <p class="text-xs text-blue-100 whitespace-nowrap"><?php echo htmlspecialchars($panel_title); ?></p>
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
        <?php foreach ($nav_items as $item): 
            $is_active = ($item['href'] === $current_page);
            $active_classes = $is_active ? "bg-{$item['color']}-50 text-{$item['color']}-700 border-r-4 border-{$item['color']}-600" : "text-gray-700";
        ?>
        <!-- <?php echo htmlspecialchars($item['label']); ?> -->
        <a href="<?php echo htmlspecialchars($item['href']); ?>" class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg hover:bg-<?php echo $item['color']; ?>-50 hover:text-<?php echo $item['color']; ?>-700 transition-all duration-200 <?php echo $active_classes; ?>">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 <?php echo $is_active ? 'text-'.$item['color'].'-600' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo htmlspecialchars($item['icon']); ?>"></path>
                </svg>
            </div>
            <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0 <?php echo $is_active ? 'font-bold' : ''; ?>"><?php echo htmlspecialchars($item['label']); ?></span>
            <div class="ml-auto <?php echo $is_active ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'; ?> transition-opacity duration-200 flex-shrink-0">
                <div class="w-1 h-6 bg-<?php echo $item['color']; ?>-600 rounded-full"></div>
            </div>
        </a>
        <?php endforeach; ?>
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