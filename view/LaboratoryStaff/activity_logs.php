<?php
session_start();
require_once '../../config/config.php';
require_once '../../model/Database.php';

// Check if user is logged in and has Laboratory Staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    $_SESSION['error_message'] = "Unauthorized access. Please log in as Laboratory Staff.";
    header("Location: ../employee_login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get filter parameters
$action_filter = $_GET['action'] ?? '';
$entity_filter = $_GET['entity'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$query = "SELECT al.*, u.full_name, u.id_number 
          FROM activity_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          WHERE u.role = 'Laboratory Staff'";

$params = [];

if (!empty($action_filter)) {
    $query .= " AND al.action = ?";
    $params[] = $action_filter;
}

if (!empty($entity_filter)) {
    $query .= " AND al.entity_type = ?";
    $params[] = $entity_filter;
}

if (!empty($date_from)) {
    $query .= " AND DATE(al.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(al.created_at) <= ?";
    $params[] = $date_to;
}

if (!empty($search)) {
    $query .= " AND (al.description LIKE ? OR u.full_name LIKE ? OR u.id_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Handle pagination
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
if ($per_page <= 0) $per_page = 999999; // Show all

$query .= " ORDER BY al.created_at DESC LIMIT " . $per_page;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total_logs,
                COUNT(DISTINCT al.user_id) as unique_users,
                COUNT(CASE WHEN DATE(al.created_at) = CURDATE() THEN 1 END) as today_logs,
                COUNT(CASE WHEN DATE(al.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 END) as yesterday_logs
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE u.role = 'Laboratory Staff'";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch(PDO::FETCH_ASSOC);

// Get action counts
$action_query = "SELECT al.action, COUNT(*) as count 
                 FROM activity_logs al
                 LEFT JOIN users u ON al.user_id = u.id
                 WHERE u.role = 'Laboratory Staff'
                 GROUP BY al.action 
                 ORDER BY count DESC";
$action_result = $conn->query($action_query);
$action_counts = $action_result->fetchAll(PDO::FETCH_ASSOC);

// Get entity counts
$entity_query = "SELECT al.entity_type, COUNT(*) as count 
                 FROM activity_logs al
                 LEFT JOIN users u ON al.user_id = u.id
                 WHERE u.role = 'Laboratory Staff' AND al.entity_type IS NOT NULL
                 GROUP BY al.entity_type 
                 ORDER BY count DESC";
$entity_result = $conn->query($entity_query);
$entity_counts = $entity_result->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Activity Logs";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Laboratory Staff</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body class="bg-gray-50">
    
    <?php include '../components/sidebar.php'; ?>
    
    <div id="main-wrapper" class="ml-0 lg:ml-20 lg:peer-hover:ml-[220px] pt-[90px] transition-all duration-300 ease-in-out">
        
        <?php include '../components/header.php'; ?>
        
        <div id="main-content-container" class="p-6">
            <div class="max-w-[1400px] mx-auto">
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 bg-white rounded shadow-sm border border-gray-200 mb-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Activity Logs</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Total: <?php echo count($logs); ?> log(s)</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="exportLogs()" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fa-solid fa-download"></i>
                            <span>Export Logs</span>
                        </button>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 px-4 py-3">
                    <form method="GET" action="" class="flex flex-wrap gap-3">
                        <div class="flex-1 min-w-[200px]">
                            <input type="text" name="search" id="searchInput" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search logs..." 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <select name="action" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Actions</option>
                            <option value="login" <?php echo $action_filter === 'login' ? 'selected' : ''; ?>>Login</option>
                            <option value="create" <?php echo $action_filter === 'create' ? 'selected' : ''; ?>>Create</option>
                            <option value="update" <?php echo $action_filter === 'update' ? 'selected' : ''; ?>>Update</option>
                            <option value="archive" <?php echo $action_filter === 'archive' ? 'selected' : ''; ?>>Archive</option>
                            <option value="restore" <?php echo $action_filter === 'restore' ? 'selected' : ''; ?>>Restore</option>
                            <option value="dispose" <?php echo $action_filter === 'dispose' ? 'selected' : ''; ?>>Dispose</option>
                            <option value="import" <?php echo $action_filter === 'import' ? 'selected' : ''; ?>>Import</option>
                            <option value="assign" <?php echo $action_filter === 'assign' ? 'selected' : ''; ?>>Assign</option>
                            <option value="upload" <?php echo $action_filter === 'upload' ? 'selected' : ''; ?>>Upload</option>
                            <option value="export" <?php echo $action_filter === 'export' ? 'selected' : ''; ?>>Export</option>
                        </select>
                        <select name="entity" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Entities</option>
                            <option value="asset" <?php echo $entity_filter === 'asset' ? 'selected' : ''; ?>>Asset</option>
                            <option value="pc_unit" <?php echo $entity_filter === 'pc_unit' ? 'selected' : ''; ?>>PC Unit</option>
                            <option value="scanner" <?php echo $entity_filter === 'scanner' ? 'selected' : ''; ?>>Scanner</option>
                            <option value="ticket" <?php echo $entity_filter === 'ticket' ? 'selected' : ''; ?>>Ticket</option>
                            <option value="signature" <?php echo $entity_filter === 'signature' ? 'selected' : ''; ?>>Signature</option>
                            <option value="user" <?php echo $entity_filter === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="report" <?php echo $entity_filter === 'report' ? 'selected' : ''; ?>>Report</option>
                        </select>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                               onchange="this.form.submit()"
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                               onchange="this.form.submit()"
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <select name="per_page" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <?php $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25; ?>
                            <option value="10" <?php echo ($per_page == 10) ? 'selected' : ''; ?>>Show 10</option>
                            <option value="25" <?php echo ($per_page == 25) ? 'selected' : ''; ?>>Show 25</option>
                            <option value="100" <?php echo ($per_page == 100) ? 'selected' : ''; ?>>Show 100</option>
                            <option value="0" <?php echo ($per_page == 0 || $per_page >= 1000) ? 'selected' : ''; ?>>Show All</option>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fa-solid fa-search"></i>
                        </button>
                        <?php if (!empty($search) || !empty($action_filter) || !empty($entity_filter) || !empty($date_from) || !empty($date_to)): ?>
                            <a href="activity_logs.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                <i class="fa-solid fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Activity Logs Table -->
                <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
                    <table id="logsTable" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-[#1E3A8A] text-white sticky top-0 z-10">
                            <tr>
                                <th class="px-3 py-1.5 text-left text-xs font-medium uppercase tracking-wider">Timestamp</th>
                                <th class="px-3 py-1.5 text-left text-xs font-medium uppercase tracking-wider">User</th>
                                <th class="px-3 py-1.5 text-left text-xs font-medium uppercase tracking-wider">Action</th>
                                <th class="px-3 py-1.5 text-left text-xs font-medium uppercase tracking-wider">Entity</th>
                                <th class="px-3 py-1.5 text-left text-xs font-medium uppercase tracking-wider">Description</th>
                                <th class="px-3 py-1.5 text-left text-xs font-medium uppercase tracking-wider">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fa-solid fa-clipboard-list text-5xl mb-3 opacity-30"></i>
                                        <p class="text-lg">No activity logs found</p>
                                        <?php if (!empty($search) || !empty($action_filter) || !empty($entity_filter) || !empty($date_from) || !empty($date_to)): ?>
                                            <p class="text-sm">Try adjusting your filters</p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-3 py-1.5 whitespace-nowrap text-xs text-gray-700">
                                            <div class="flex flex-col">
                                                <span class="font-medium"><?php echo date('M d, Y', strtotime($log['created_at'])); ?></span>
                                                <span class="text-gray-500"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-1.5 whitespace-nowrap text-xs">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-gray-900"><?php echo htmlspecialchars($log['full_name'] ?? 'Unknown'); ?></span>
                                                <span class="text-gray-500"><?php echo htmlspecialchars($log['id_number'] ?? 'N/A'); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-1.5 whitespace-nowrap">
                                            <?php
                                            $action_colors = [
                                                'login' => 'bg-blue-100 text-blue-800',
                                                'create' => 'bg-green-100 text-green-800',
                                                'update' => 'bg-yellow-100 text-yellow-800',
                                                'archive' => 'bg-orange-100 text-orange-800',
                                                'restore' => 'bg-purple-100 text-purple-800',
                                                'dispose' => 'bg-red-100 text-red-800',
                                                'import' => 'bg-indigo-100 text-indigo-800',
                                                'assign' => 'bg-cyan-100 text-cyan-800',
                                                'upload' => 'bg-pink-100 text-pink-800',
                                                'export' => 'bg-teal-100 text-teal-800',
                                            ];
                                            $color = $action_colors[$log['action']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full <?php echo $color; ?>">
                                                <?php echo ucfirst(htmlspecialchars($log['action'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-1.5 whitespace-nowrap text-xs text-gray-700">
                                            <?php if ($log['entity_type']): ?>
                                                <span class="font-medium"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($log['entity_type']))); ?></span>
                                                <?php if ($log['entity_id']): ?>
                                                    <span class="text-gray-500">#<?php echo htmlspecialchars($log['entity_id']); ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-gray-400">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-1.5 text-xs text-gray-700 max-w-md">
                                            <div class="truncate" title="<?php echo htmlspecialchars($log['description']); ?>">
                                                <?php echo htmlspecialchars($log['description']); ?>
                                            </div>
                                        </td>
                                        <td class="px-3 py-1.5 whitespace-nowrap text-xs text-gray-500">
                                            <?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-submit search with debounce
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    document.querySelector('form').submit();
                }, 800); // Wait 800ms after user stops typing
            });
        }

        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = '../../controller/export_logs.php?' + params.toString();
        }
    </script>
</body>
</html>