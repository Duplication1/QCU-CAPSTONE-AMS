<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
require_once '../../model/Database.php';

// Get activity logs and login history
$db = new Database();
$conn = $db->getConnection();

// Get filter parameters
$actionFilter = $_GET['action'] ?? '';
$userFilter = $_GET['user'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$logType = $_GET['type'] ?? 'activity'; // activity or login

// Activity Logs Query
$activityQuery = "SELECT 
    al.*,
    u.full_name,
    u.email,
    u.role
FROM activity_logs al
LEFT JOIN users u ON al.user_id = u.id
WHERE 1=1";

$activityParams = [];

if ($actionFilter) {
    $activityQuery .= " AND al.action = ?";
    $activityParams[] = $actionFilter;
}

if ($userFilter) {
    $activityQuery .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%{$userFilter}%";
    $activityParams[] = $searchTerm;
    $activityParams[] = $searchTerm;
}

if ($dateFilter) {
    $activityQuery .= " AND DATE(al.created_at) = ?";
    $activityParams[] = $dateFilter;
}

$activityQuery .= " ORDER BY al.created_at DESC LIMIT 500";

$activityStmt = $conn->prepare($activityQuery);
$activityStmt->execute($activityParams);
$logs = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

// Login History Query (for Students and Faculty)
$loginQuery = "SELECT 
    lh.*,
    u.full_name,
    u.email,
    u.role
FROM login_history lh
LEFT JOIN users u ON lh.user_id = u.id
WHERE u.role IN ('Student', 'Faculty')";

$loginParams = [];

if ($userFilter) {
    $loginQuery .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%{$userFilter}%";
    $loginParams[] = $searchTerm;
    $loginParams[] = $searchTerm;
}

if ($dateFilter) {
    $loginQuery .= " AND DATE(lh.login_time) = ?";
    $loginParams[] = $dateFilter;
}

$loginQuery .= " ORDER BY lh.login_time DESC LIMIT 500";

$loginStmt = $conn->prepare($loginQuery);
$loginStmt->execute($loginParams);
$loginHistory = $loginStmt->fetchAll(PDO::FETCH_ASSOC);

include '../components/layout_header.php';
?>

<style>
html, body {
    height: 100vh;
    overflow: hidden;
}
#app-container {
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
main {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    padding: 0.5rem;
    background-color: #f9fafb;
}
</style>

<!-- Main Content -->
<main class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Header with Tabs -->
        <div class="px-3 py-2 bg-white rounded shadow-sm border border-gray-200 mb-2">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Activity Logs & Login History</h3>
                    <p class="text-[10px] text-gray-500 mt-0.5">Monitor user activities and system events</p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Search -->
                    <div class="relative">
                        <input id="logSearch" type="search" placeholder="Search user..." 
                            class="w-48 pl-8 pr-3 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]"
                            oninput="filterLogs()">
                        <i class="fas fa-search absolute left-2.5 top-2 text-gray-400 text-xs"></i>
                    </div>
                    
                    <!-- Filter Button -->
                    <div class="relative">
                        <button id="filterBtn" onclick="toggleFilterMenu()" 
                            class="px-2 py-1.5 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
                            <i class="fas fa-filter text-gray-600 text-xs"></i>
                        </button>
                        
                        <!-- Filter Dropdown -->
                        <div id="filterMenu" class="hidden absolute right-0 mt-1 w-48 bg-white border border-gray-200 rounded shadow-lg z-50">
                            <div class="p-2">
                                <h4 class="text-xs font-semibold text-gray-700 mb-2">Filter Logs</h4>
                                
                                <div class="mb-2" id="actionFilterDiv">
                                    <label class="block text-[10px] text-gray-600 mb-1">Action</label>
                                    <select id="actionFilter" onchange="applyFilters()" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                                        <option value="">All Actions</option>
                                        <option value="login">Login</option>
                                        <option value="logout">Logout</option>
                                        <option value="create">Create</option>
                                        <option value="update">Update</option>
                                        <option value="delete">Delete</option>
                                        <option value="export">Export</option>
                                        <option value="view">View</option>
                                    </select>
                                </div>
                                
                                <div class="mb-2">
                                    <label class="block text-[10px] text-gray-600 mb-1">Date</label>
                                    <input type="date" id="dateFilter" onchange="applyFilters()" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                                </div>
                                
                                <button onclick="clearFilters()" class="w-full px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded">
                                    Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Export Button -->
                    <button onclick="exportLogs()" class="px-3 py-1.5 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                        <i class="fas fa-file-export mr-1"></i>Export
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-gray-200">
                <button onclick="switchTab('activity')" id="activityTab" 
                    class="px-4 py-2 text-xs font-medium border-b-2 border-[#1E3A8A] text-[#1E3A8A] focus:outline-none">
                    <i class="fas fa-history mr-1"></i>Activity Logs (<?php echo count($logs); ?>)
                </button>
                <button onclick="switchTab('login')" id="loginTab" 
                    class="px-4 py-2 text-xs font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 focus:outline-none">
                    <i class="fas fa-sign-in-alt mr-1"></i>Student/Faculty Login History (<?php echo count($loginHistory); ?>)
                </button>
            </div>
        </div>

        <!-- Activity Logs Table -->
        <div id="activityContent" class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#1E3A8A] text-white sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Time</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">User</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Role</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Action</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Description</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">IP Address</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-xs text-gray-500">
                                <i class="fas fa-inbox text-2xl text-gray-300 mb-2"></i>
                                <p>No activity logs found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-blue-50 transition log-row" 
                            data-action="<?php echo htmlspecialchars($log['action']); ?>"
                            data-user="<?php echo htmlspecialchars(strtolower($log['full_name'] ?? '')); ?>"
                            data-date="<?php echo date('Y-m-d', strtotime($log['created_at'])); ?>">
                            <td class="px-3 py-2 whitespace-nowrap text-xs">
                                <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                            </td>
                            <td class="px-3 py-2 text-xs">
                                <div>
                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($log['full_name'] ?? 'Unknown'); ?></div>
                                    <div class="text-[10px] text-gray-500"><?php echo htmlspecialchars($log['email'] ?? ''); ?></div>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-xs">
                                <?php 
                                $roleColors = [
                                    'Administrator' => 'bg-red-100 text-red-700',
                                    'Technician' => 'bg-blue-100 text-blue-700',
                                    'Laboratory Staff' => 'bg-green-100 text-green-700',
                                    'Student' => 'bg-gray-100 text-gray-700',
                                    'Faculty' => 'bg-purple-100 text-purple-700'
                                ];
                                $roleClass = $roleColors[$log['role'] ?? ''] ?? 'bg-gray-100 text-gray-700';
                                ?>
                                <span class="inline-flex px-1.5 py-0.5 rounded-full text-[10px] font-medium <?php echo $roleClass; ?>">
                                    <?php echo htmlspecialchars($log['role'] ?? ''); ?>
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs">
                                <?php 
                                $actionColors = [
                                    'login' => 'bg-green-100 text-green-700',
                                    'logout' => 'bg-gray-100 text-gray-700',
                                    'create' => 'bg-blue-100 text-blue-700',
                                    'update' => 'bg-yellow-100 text-yellow-700',
                                    'delete' => 'bg-red-100 text-red-700',
                                    'export' => 'bg-indigo-100 text-indigo-700',
                                    'view' => 'bg-cyan-100 text-cyan-700'
                                ];
                                $actionClass = $actionColors[$log['action']] ?? 'bg-gray-100 text-gray-700';
                                ?>
                                <span class="inline-flex px-1.5 py-0.5 rounded-full text-[10px] font-medium <?php echo $actionClass; ?>">
                                    <?php echo htmlspecialchars(ucfirst($log['action'])); ?>
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-600 max-w-md truncate">
                                <?php echo htmlspecialchars($log['description'] ?? ''); ?>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500 font-mono">
                                <?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Login History Table -->
        <div id="loginContent" class="hidden flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#1E3A8A] text-white sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Login Time</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">User</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Role</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">IP Address</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Device Type</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">User Agent</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if (empty($loginHistory)): ?>
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-xs text-gray-500">
                                <i class="fas fa-inbox text-2xl text-gray-300 mb-2"></i>
                                <p>No login history found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($loginHistory as $login): ?>
                        <tr class="hover:bg-blue-50 transition login-row" 
                            data-user="<?php echo htmlspecialchars(strtolower($login['full_name'] ?? '')); ?>"
                            data-date="<?php echo date('Y-m-d', strtotime($login['login_time'])); ?>">
                            <td class="px-3 py-2 whitespace-nowrap text-xs">
                                <?php echo date('M d, Y H:i:s', strtotime($login['login_time'])); ?>
                            </td>
                            <td class="px-3 py-2 text-xs">
                                <div>
                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($login['full_name'] ?? 'Unknown'); ?></div>
                                    <div class="text-[10px] text-gray-500"><?php echo htmlspecialchars($login['email'] ?? ''); ?></div>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-xs">
                                <?php 
                                $roleClass = $login['role'] === 'Student' ? 'bg-gray-100 text-gray-700' : 'bg-purple-100 text-purple-700';
                                ?>
                                <span class="inline-flex px-1.5 py-0.5 rounded-full text-[10px] font-medium <?php echo $roleClass; ?>">
                                    <?php echo htmlspecialchars($login['role'] ?? ''); ?>
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500 font-mono">
                                <?php echo htmlspecialchars($login['ip_address'] ?? '-'); ?>
                            </td>
                            <td class="px-3 py-2 text-xs">
                                <?php 
                                $deviceIcon = $login['device_type'] === 'mobile' ? 'fa-mobile-alt' : 'fa-desktop';
                                $deviceColor = $login['device_type'] === 'mobile' ? 'text-blue-600' : 'text-gray-600';
                                ?>
                                <span class="<?php echo $deviceColor; ?>">
                                    <i class="fas <?php echo $deviceIcon; ?> mr-1"></i>
                                    <?php echo htmlspecialchars(ucfirst($login['device_type'] ?? 'Unknown')); ?>
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500 max-w-xs truncate">
                                <?php echo htmlspecialchars($login['user_agent'] ?? '-'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include '../components/layout_footer.php'; ?>

<script>
let currentTab = 'activity';

function switchTab(tab) {
    currentTab = tab;
    const activityTab = document.getElementById('activityTab');
    const loginTab = document.getElementById('loginTab');
    const activityContent = document.getElementById('activityContent');
    const loginContent = document.getElementById('loginContent');
    const actionFilterDiv = document.getElementById('actionFilterDiv');
    
    if (tab === 'activity') {
        activityTab.classList.add('border-[#1E3A8A]', 'text-[#1E3A8A]');
        activityTab.classList.remove('border-transparent', 'text-gray-500');
        loginTab.classList.remove('border-[#1E3A8A]', 'text-[#1E3A8A]');
        loginTab.classList.add('border-transparent', 'text-gray-500');
        activityContent.classList.remove('hidden');
        loginContent.classList.add('hidden');
        actionFilterDiv.style.display = 'block';
    } else {
        loginTab.classList.add('border-[#1E3A8A]', 'text-[#1E3A8A]');
        loginTab.classList.remove('border-transparent', 'text-gray-500');
        activityTab.classList.remove('border-[#1E3A8A]', 'text-[#1E3A8A]');
        activityTab.classList.add('border-transparent', 'text-gray-500');
        loginContent.classList.remove('hidden');
        activityContent.classList.add('hidden');
        actionFilterDiv.style.display = 'none';
    }
    
    clearFilters();
}
// Filter menu toggle
function toggleFilterMenu() {
    const menu = document.getElementById('filterMenu');
    menu.classList.toggle('hidden');
}

// Close filter menu when clicking outside
document.addEventListener('click', function(e) {
    const filterBtn = document.getElementById('filterBtn');
    const filterMenu = document.getElementById('filterMenu');
    
    if (!filterBtn.contains(e.target) && !filterMenu.contains(e.target)) {
        filterMenu.classList.add('hidden');
    }
});

// Filter logs
function filterLogs() {
    const searchQuery = document.getElementById('logSearch').value.toLowerCase();
    const actionFilter = document.getElementById('actionFilter').value.toLowerCase();
    const dateFilter = document.getElementById('dateFilter').value;
    
    if (currentTab === 'activity') {
        const rows = document.querySelectorAll('.log-row');
        
        rows.forEach(row => {
            const user = row.dataset.user || '';
            const action = row.dataset.action || '';
            const date = row.dataset.date || '';
            
            const matchesSearch = !searchQuery || user.includes(searchQuery);
            const matchesAction = !actionFilter || action === actionFilter;
            const matchesDate = !dateFilter || date === dateFilter;
            
            if (matchesSearch && matchesAction && matchesDate) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    } else {
        const rows = document.querySelectorAll('.login-row');
        
        rows.forEach(row => {
            const user = row.dataset.user || '';
            const date = row.dataset.date || '';
            
            const matchesSearch = !searchQuery || user.includes(searchQuery);
            const matchesDate = !dateFilter || date === dateFilter;
            
            if (matchesSearch && matchesDate) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
}

function applyFilters() {
    filterLogs();
}

function clearFilters() {
    document.getElementById('logSearch').value = '';
    document.getElementById('actionFilter').value = '';
    document.getElementById('dateFilter').value = '';
    filterLogs();
}

function exportLogs() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Exporting...';
    
    // Get current filters
    const actionFilter = document.getElementById('actionFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../../controller/export_logs.php';
    form.target = '_blank';
    
    if (actionFilter) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'action';
        input.value = actionFilter;
        form.appendChild(input);
    }
    
    if (dateFilter) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'date';
        input.value = dateFilter;
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }, 2000);
}
</script>
