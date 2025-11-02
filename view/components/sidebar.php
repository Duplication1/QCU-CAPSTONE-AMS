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
            'icon' => 'fa-solid fa-gauge',
            'color' => 'yellow',
            'href' => 'index.php'
        ],
        [
            'id' => 'users',
            'label' => 'User Management',
            'icon' => 'fa-solid fa-users',
            'color' => 'yellow',
            'href' => 'users.php'
        ],
        [
            'id' => 'assets',
            'label' => 'Asset Management',
            'icon' => 'fa-solid fa-box',
            'color' => 'yellow',
            'href' => 'assets.php'
        ],
        [
            'id' => 'analytics',
            'label' => 'Analytics',
            'icon' => 'fa-solid fa-chart-column',
            'color' => 'yellow',
            'href' => 'analytics.php'
        ]
    ],
    'LaboratoryStaff' => [
        [
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'fa-solid fa-gauge',
            'color' => 'yellow',
            'href' => 'index.php'
        ],
        [
            'id' => 'borrowing',
            'label' => 'Borrowing Management',
            'icon' => 'fa-solid fa-right-left',
            'color' => 'yellow',
            'href' => 'borrowing.php'
        ],
        [
            'id' => 'tickets',
            'label' => 'Ticket Coordination',
            'icon' => 'fa-solid fa-ticket',
            'color' => 'yellow',
            'href' => 'tickets.php'
        ],
        [
            'id' => 'operations',
            'label' => 'Lab Operations',
            'icon' => 'fa-solid fa-flask',
            'color' => 'yellow',
            'href' => 'operations.php'
        ],
        [
            'id' => 'registry',
            'label' => 'Asset Registry',
            'icon' => 'fa-solid fa-clipboard-list',
            'color' => 'yellow',
            'href' => 'registry.php'
        ],
        [
            'id' => 'e-signature',
            'label' => 'My E-Signature',
            'icon' => 'fa-solid fa-signature',
            'color' => 'yellow',
            'href' => 'e-signature.php'
        ]
    ],
    'Student' => [
        [
            'id' => 'dashboard',
            'label' => 'Home',
            'icon' => 'fa-solid fa-house',
            'color' => 'yellow',
            'href' => 'index.php'
        ],
        [
            'id' => 'requests',
            'label' => 'My Requests',
            'icon' => 'fa-solid fa-clipboard-check',
            'color' => 'yellow',
            'href' => 'requests.php'
        ],
        [
            'id' => 'e-signature',
            'label' => 'My E-Signature',
            'icon' => 'fa-solid fa-signature',
            'color' => 'yellow',
            'href' => 'e-signature.php'
        ]
    ],
    'Faculty' => [
        [
            'id' => 'dashboard',
            'label' => 'Home',
            'icon' => 'fa-solid fa-house',
            'color' => 'yellow',
            'href' => 'index.php'
        ],
        [
            'id' => 'requests',
            'label' => 'My Requests',
            'icon' => 'fa-solid fa-clipboard-check',
            'color' => 'yellow',
            'href' => 'requests.php'
        ],
        [
            'id' => 'e-signature',
            'label' => 'My E-Signature',
            'icon' => 'fa-solid fa-signature',
            'color' => 'yellow',
            'href' => 'e-signature.php'
        ]
    ],
    'Technician' => [
        [
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'fa-solid fa-gauge',
            'color' => 'yellow',
            'href' => 'index.php'
        ],
        [
            'id' => 'tickets',
            'label' => 'Ticket Management',
            'icon' => 'fa-solid fa-ticket',
            'color' => 'yellow',
            'href' => 'tickets.php'
        ],
        [
            'id' => 'maintenance',
            'label' => 'Maintenance Tasks',
            'icon' => 'fa-solid fa-wrench',
            'color' => 'yellow',
            'href' => 'maintenance.php'
        ],
        [
            'id' => 'registry',
            'label' => 'Asset Registry',
            'icon' => 'fa-solid fa-clipboard-list',
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
                <i id="toggle-icon" class="fa-solid fa-chevron-left"></i>
            </button>
            
            <div id="sidebar-brand" class="text-white overflow-hidden transition-all duration-300">
                <h2 class="text-lg font-bold whitespace-nowrap">QCU AMS</h2>
                <p class="text-xs text-blue-100 whitespace-nowrap"><?php echo htmlspecialchars($panel_title); ?></p>
            </div>
        </div>
        
        <!-- Mobile Close Button -->
        <button id="mobile-close" class="lg:hidden p-2 rounded-lg text-white hover:bg-blue-500 transition-colors duration-200">
            <i class="fa-solid fa-xmark"></i>
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
                <i class="<?php echo htmlspecialchars($item['icon']); ?> w-5 text-center <?php echo $is_active ? 'text-'.$item['color'].'-600' : ''; ?>"></i>
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
                <i class="fa-solid fa-right-from-bracket w-5 text-center"></i>
            </div>
            <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0">Logout</span>
        </a>
    </div>
</aside>