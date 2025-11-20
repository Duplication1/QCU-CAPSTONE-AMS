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
            'color' => 'blue',
            'href' => 'index.php'
        ],
        [
            'id' => 'users',
            'label' => 'User Management',
            'icon' => 'fa-solid fa-users',
            'color' => 'blue',
            'href' => 'users.php'
        ],
        [
            'id' => 'analytics',
            'label' => 'Analytics',
            'icon' => 'fa-solid fa-chart-column',
            'color' => 'blue',
            'href' => 'analytics.php'
        ]
    ],
    'LaboratoryStaff' => [
        [
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'fa-solid fa-gauge',
            'color' => 'blue',
            'href' => 'index.php'
        ],
        [
            'id' => 'pc-health',
            'label' => 'PC Health Monitor',
            'icon' => 'fa-solid fa-heart-pulse',
            'color' => 'yellow',
            'href' => 'pc_health_dashboard.php'
        ],
        [
            'id' => 'borrowing',
            'label' => 'Borrowing Management',
            'icon' => 'fa-solid fa-right-left',
            'color' => 'blue',
            'href' => 'borrowing.php'
        ],
        [
            'id' => 'tickets',
            'label' => 'Ticket Coordination',
            'icon' => 'fa-solid fa-ticket',
            'color' => 'blue',
            'href' => 'tickets.php'
        ],
        [
            'id' => 'operations',
            'label' => 'Lab Operations',
            'icon' => 'fa-solid fa-flask',
            'color' => 'blue',
            'href' => 'operations.php'
        ],
        [
            'id' => 'registry',
            'label' => 'Asset Registry',
            'icon' => 'fa-solid fa-clipboard-list',
            'color' => 'blue',
            'href' => 'registry.php'
        ],
        [
            'id' => 'e-signature',
            'label' => 'My E-Signature',
            'icon' => 'fa-solid fa-signature',
            'color' => 'blue',
            'href' => 'e-signature.php'
        ]
    ],
    'Student' => [
        [
            'id' => 'dashboard',
            'label' => 'Home',
            'icon' => 'fa-solid fa-house',
            'color' => 'blue',
            'href' => 'index.php'
        ],
        [
            'id' => 'requests',
            'label' => 'My Requests',
            'icon' => 'fa-solid fa-clipboard-check',
            'color' => 'blue',
            'href' => 'requests.php'
        ],
        [
            'id' => 'e-signature',
            'label' => 'My E-Signature',
            'icon' => 'fa-solid fa-signature',
            'color' => 'blue',
            'href' => 'e-signature.php'
        ]
    ],
    'Faculty' => [
        [
            'id' => 'dashboard',
            'label' => 'Home',
            'icon' => 'fa-solid fa-house',
            'color' => 'blue',
            'href' => 'index.php'
        ],
        [
            'id' => 'requests',
            'label' => 'My Requests',
            'icon' => 'fa-solid fa-clipboard-check',
            'color' => 'blue',
            'href' => 'requests.php'
        ],
        [
            'id' => 'e-signature',
            'label' => 'My E-Signature',
            'icon' => 'fa-solid fa-signature',
            'color' => 'blue',
            'href' => 'e-signature.php'
        ]
    ],
    'Technician' => [
        [
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'fa-solid fa-gauge',
            'color' => 'blue',
            'href' => 'index.php'
        ],
        [
            'id' => 'tickets',
            'label' => 'Ticket Management',
            'icon' => 'fa-solid fa-ticket',
            'color' => 'blue',
            'href' => 'tickets.php'
        ],
        [
            'id' => 'maintenance',
            'label' => 'Maintenance Tasks',
            'icon' => 'fa-solid fa-wrench',
            'color' => 'blue',
            'href' => 'maintenance.php'
        ],
        [
            'id' => 'registry',
            'label' => 'Asset Registry',
            'icon' => 'fa-solid fa-clipboard-list',
            'color' => 'blue',
            'href' => 'registry.php'
        ]
    ]
];

// Get the current user's role
$current_role = $_SESSION['role'] ?? 'Student';

// Handle role mapping for different role formats
$role_key = $current_role;
if ($current_role === 'Laboratory Staff') {
    $role_key = 'LaboratoryStaff'; // Map "Laboratory Staff" to "LaboratoryStaff"
}

// Count new/unassigned tickets for Laboratory Staff
$new_tickets_count = 0;
if ($current_role === 'Laboratory Staff' && isset($conn)) {
    try {
        $ticket_count_query = "SELECT COUNT(*) as count FROM issues 
                              WHERE (assigned_group IS NULL OR assigned_group = '') 
                              AND status = 'Open' 
                              AND category != 'borrow'";
        
        // Check if $conn is PDO or mysqli
        if ($conn instanceof PDO) {
            $ticket_result = $conn->query($ticket_count_query);
            if ($ticket_result) {
                $ticket_row = $ticket_result->fetch(PDO::FETCH_ASSOC);
                $new_tickets_count = (int)$ticket_row['count'];
            }
        } else {
            // mysqli connection
            $ticket_result = $conn->query($ticket_count_query);
            if ($ticket_result) {
                $ticket_row = $ticket_result->fetch_assoc();
                $new_tickets_count = (int)$ticket_row['count'];
            }
        }
    } catch (Exception $e) {
        // Silently handle error
        $new_tickets_count = 0;
    }
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
    'Technician' => 'Technician Panel'
];

$panel_title = $panel_titles[$current_role] ?? 'Student Portal';
?>

<!-- Mobile Menu Overlay -->
<div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>

<!-- Sidebar -->
<aside id="sidebar"
    class="fixed left-0 top-[85px] h-[calc(100vh-85px)] w-[220px] bg-white shadow-xl border-r border-gray-200 flex flex-col -translate-x-full z-50">

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto overflow-x-hidden">
        <?php foreach ($nav_items as $item): 
            $is_active = ($item['href'] === $current_page);
                $active_classes = $is_active ? "bg-{$item['color']}-50 text-{$item['color']}-700 border-r-4 border-{$item['color']}-600" : "text-gray-700";
            $show_badge = ($item['id'] === 'tickets' && $new_tickets_count > 0);
        ?>
    <!-- <?php echo htmlspecialchars($item['label']); ?> -->
        <a href="<?php echo htmlspecialchars($item['href']); ?>"
            class="group flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg 
            hover:bg-[#1E3A8A] hover:text-white transition-all duration-200 
          <?php echo $is_active ? 'bg-[#1E3A8A] text-white' : 'text-gray-700'; ?>">
            <div class="flex items-center min-w-0 flex-1">
                <div class="flex-shrink-0">
                <i class="<?php echo htmlspecialchars($item['icon']); ?> w-5 text-center 
               <?php echo $is_active ? 'text-white' : ''; ?>"></i>
                </div>
                <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap truncate"><?php echo htmlspecialchars($item['label']); ?></span>
            </div>
            <?php if ($show_badge): ?>
            <span class="flex-shrink-0 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-full ml-2 animate-pulse">
                <?php echo $new_tickets_count; ?>
            </span>
            <?php endif; ?>
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