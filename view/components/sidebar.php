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
            'id' => 'reports',
            'label' => 'Reports',
            'icon' => 'fa-solid fa-file-lines',
            'color' => 'blue',
            'href' => 'reports.php'
        ],
        [
            'id' => 'logs',
            'label' => 'Activity Logs',
            'icon' => 'fa-solid fa-clipboard-list',
            'color' => 'blue',
            'href' => 'logs.php'
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
            'href' => 'allassets.php',
            'submenu' => [
                [
                    'id' => 'all-assets',
                    'label' => 'All Assets',
                    'href' => 'allassets.php'
                ],
                [
                    'id' => 'buildings',
                    'label' => 'Buildings',
                    'href' => 'buildings.php'
                ],
                [
                    'id' => 'standby-assets',
                    'label' => 'Stand By Assets',
                    'href' => 'standbyassets.php'
                ]
            ]
        ]
    ],
    'Student' => [
        [
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'fa-solid fa-gauge',
            'color' => 'blue',
            'href' => 'index.php'
        ],
        [
            'id' => 'tickets',
            'label' => 'Submit Tickets',
            'icon' => 'fa-solid fa-ticket',
            'color' => 'blue',
            'href' => 'tickets.php'
        ],
        [
            'id' => 'ticket-issues',
            'label' => 'Ticket Issues',
            'icon' => 'fa-solid fa-list-check',
            'color' => 'blue',
            'href' => 'ticket_issues.php'
        ],
        [
            'id' => 'requests',
            'label' => 'My Requests',
            'icon' => 'fa-solid fa-clipboard-check',
            'color' => 'blue',
            'href' => 'requests.php'
        ]
    ],
    'Faculty' => [
        [
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'fa-solid fa-gauge',
            'color' => 'blue',
            'href' => 'index.php'
        ],
        [
            'id' => 'tickets',
            'label' => 'Submit Tickets',
            'icon' => 'fa-solid fa-ticket',
            'color' => 'blue',
            'href' => 'tickets.php'
        ],
        [
            'id' => 'ticket-issues',
            'label' => 'Ticket Issues',
            'icon' => 'fa-solid fa-list-check',
            'color' => 'blue',
            'href' => 'ticket_issues.php'
        ],
        [
            'id' => 'requests',
            'label' => 'My Requests',
            'icon' => 'fa-solid fa-clipboard-check',
            'color' => 'blue',
            'href' => 'requests.php'
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
            'href' => 'allassets.php',
            'submenu' => [
                [
                    'id' => 'all-assets',
                    'label' => 'All Assets',
                    'href' => 'allassets.php'
                ],
                [
                    'id' => 'buildings',
                    'label' => 'Buildings',
                    'href' => 'buildings.php'
                ],
                [
                    'id' => 'standby-assets',
                    'label' => 'Stand By Assets',
                    'href' => 'standbyassets.php'
                ]
            ]
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
    class="peer fixed left-0 top-[85px] h-[calc(100vh-85px)] bg-white shadow-xl border-r border-gray-200 flex flex-col z-50 
           -translate-x-full lg:translate-x-0 lg:w-20 lg:hover:w-[220px] 
           w-[220px] transition-all duration-300 ease-in-out group">

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto overflow-x-hidden">
        <?php foreach ($nav_items as $item): 
            $is_active = ($item['href'] === $current_page);
            $active_classes = $is_active ? "bg-{$item['color']}-50 text-{$item['color']}-700 border-r-4 border-{$item['color']}-600" : "text-gray-700";
            $show_badge = ($item['id'] === 'tickets' && $new_tickets_count > 0);
            $has_submenu = isset($item['submenu']) && !empty($item['submenu']);
        ?>
        <div class="nav-item-wrapper">
            <!-- <?php echo htmlspecialchars($item['label']); ?> -->
            <?php if ($has_submenu): ?>
                <!-- Parent menu with submenu -->
                <button type="button" onclick="toggleSubmenu('<?php echo $item['id']; ?>')"
                    class="group/item w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg 
                    hover:bg-[#1E3A8A] hover:text-white transition-all duration-200 
                    <?php echo $is_active ? 'bg-[#1E3A8A] text-white' : 'text-gray-700'; ?>">
                    <div class="flex items-center min-w-0 flex-1">
                        <div class="flex-shrink-0">
                            <i class="<?php echo htmlspecialchars($item['icon']); ?> w-5 text-center 
                            <?php echo $is_active ? 'text-white' : ''; ?>"></i>
                        </div>
                        <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap truncate 
                                     lg:opacity-0 lg:w-0 lg:group-hover:opacity-100 lg:group-hover:w-auto">
                            <?php echo htmlspecialchars($item['label']); ?>
                        </span>
                    </div>
                    <i class="fa-solid fa-chevron-down text-xs ml-2 transition-transform duration-200 
                              lg:opacity-0 lg:group-hover:opacity-100 submenu-arrow" 
                       id="arrow-<?php echo $item['id']; ?>"></i>
                </button>
                
                <!-- Submenu -->
                <div id="submenu-<?php echo $item['id']; ?>" class="submenu hidden pl-4 mt-1 space-y-1">
                    <?php foreach ($item['submenu'] as $subitem): 
                        $sub_is_active = ($subitem['href'] === $current_page . ($_SERVER['QUERY_STRING'] ?? ''));
                    ?>
                        <a href="<?php echo htmlspecialchars($subitem['href']); ?>"
                           class="block px-3 py-2 text-sm rounded-lg transition-colors duration-200
                                  <?php echo $sub_is_active ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100'; ?>
                                  lg:opacity-0 lg:group-hover:opacity-100">
                            <?php echo htmlspecialchars($subitem['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Regular menu item without submenu -->
                <a href="<?php echo htmlspecialchars($item['href']); ?>"
                    class="group/item flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg 
                    hover:bg-[#1E3A8A] hover:text-white transition-all duration-200 
                    <?php echo $is_active ? 'bg-[#1E3A8A] text-white' : 'text-gray-700'; ?>">
                    <div class="flex items-center min-w-0 flex-1">
                        <div class="flex-shrink-0">
                            <i class="<?php echo htmlspecialchars($item['icon']); ?> w-5 text-center 
                            <?php echo $is_active ? 'text-white' : ''; ?>"></i>
                        </div>
                        <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap truncate 
                                     lg:opacity-0 lg:w-0 lg:group-hover:opacity-100 lg:group-hover:w-auto">
                            <?php echo htmlspecialchars($item['label']); ?>
                        </span>
                    </div>
                    <?php if ($show_badge): ?>
                    <span class="flex-shrink-0 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-full ml-2 animate-pulse 
                                 lg:opacity-0 lg:w-0 lg:group-hover:opacity-100 lg:group-hover:w-auto transition-all duration-300">
                        <?php echo $new_tickets_count; ?>
                    </span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </nav>

    <!-- Logout at Bottom -->
    <div class="border-t border-gray-200 p-4 flex-shrink-0">
        <a href="../../controller/logout_controller.php" class="group/item flex items-center px-3 py-2.5 text-sm font-medium text-red-600 rounded-lg hover:bg-red-50 transition-all duration-200">
            <div class="flex-shrink-0">
                <i class="fa-solid fa-right-from-bracket w-5 text-center"></i>
            </div>
            <span class="ml-3 nav-text transition-all duration-300 whitespace-nowrap min-w-0 
                         lg:opacity-0 lg:w-0 lg:group-hover:opacity-100 lg:group-hover:w-auto">Logout</span>
        </a>
    </div>
</aside>

<script>
function toggleSubmenu(menuId) {
    const submenu = document.getElementById('submenu-' + menuId);
    const arrow = document.getElementById('arrow-' + menuId);
    
    if (submenu && arrow) {
        submenu.classList.toggle('hidden');
        arrow.classList.toggle('rotate-180');
    }
}

// Auto-expand submenu if on a submenu page
document.addEventListener('DOMContentLoaded', function() {
    const currentUrl = window.location.href;
    document.querySelectorAll('.submenu a').forEach(link => {
        if (link.href === currentUrl) {
            const submenu = link.closest('.submenu');
            if (submenu) {
                submenu.classList.remove('hidden');
                const menuId = submenu.id.replace('submenu-', '');
                const arrow = document.getElementById('arrow-' + menuId);
                if (arrow) arrow.classList.add('rotate-180');
            }
        }
    });
});
</script>