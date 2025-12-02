<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has Technician role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Technician') {
    header("Location: ../employee_login.php");
    exit();
}

require_once '../../config/config.php';
require_once '../../model/Database.php';

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
          WHERE u.role = 'Technician'";

$params = [];
$types = '';

if (!empty($action_filter)) {
    $query .= " AND al.action = ?";
    $params[] = $action_filter;
    $types .= 's';
}

if (!empty($entity_filter)) {
    $query .= " AND al.entity_type = ?";
    $params[] = $entity_filter;
    $types .= 's';
}

if (!empty($date_from)) {
    $query .= " AND DATE(al.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $query .= " AND DATE(al.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

if (!empty($search)) {
    $query .= " AND (al.description LIKE ? OR u.full_name LIKE ? OR u.id_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$query .= " ORDER BY al.created_at DESC LIMIT 1000";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total_logs,
                COUNT(DISTINCT al.user_id) as unique_users,
                COUNT(CASE WHEN DATE(al.created_at) = CURDATE() THEN 1 END) as today_logs,
                COUNT(CASE WHEN DATE(al.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 END) as yesterday_logs
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE u.role = 'Technician'";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch(PDO::FETCH_ASSOC);

include '../components/layout_header.php';
?>

<main class="p-6">
    <div class="max-w-[1400px] mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Activity Logs</h1>
            <p class="text-gray-600">View and track all technician activities</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Logs</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_logs']); ?></p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <i class="fa-solid fa-clipboard-list text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Today's Logs</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['today_logs']); ?></p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <i class="fa-solid fa-calendar-day text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Yesterday's Logs</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['yesterday_logs']); ?></p>
                    </div>
                    <div class="bg-yellow-100 rounded-full p-3">
                        <i class="fa-solid fa-clock-rotate-left text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Unique Technicians</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['unique_users']); ?></p>
                    </div>
                    <div class="bg-purple-100 rounded-full p-3">
                        <i class="fa-solid fa-users text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow mb-6 p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-filter text-blue-600"></i>
                Filters
            </h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Action Type</label>
                    <select name="action" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Actions</option>
                        <option value="login" <?php echo $action_filter === 'login' ? 'selected' : ''; ?>>Login</option>
                        <option value="logout" <?php echo $action_filter === 'logout' ? 'selected' : ''; ?>>Logout</option>
                        <option value="update" <?php echo $action_filter === 'update' ? 'selected' : ''; ?>>Update</option>
                        <option value="complete" <?php echo $action_filter === 'complete' ? 'selected' : ''; ?>>Complete</option>
                        <option value="assign" <?php echo $action_filter === 'assign' ? 'selected' : ''; ?>>Assign</option>
                        <option value="resolve" <?php echo $action_filter === 'resolve' ? 'selected' : ''; ?>>Resolve</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Entity Type</label>
                    <select name="entity" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Entities</option>
                        <option value="ticket" <?php echo $entity_filter === 'ticket' ? 'selected' : ''; ?>>Ticket</option>
                        <option value="user" <?php echo $entity_filter === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="maintenance" <?php echo $entity_filter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i class="fa-solid fa-search"></i>
                        Apply
                    </button>
                    <a href="activity_logs.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors flex items-center justify-center">
                        <i class="fa-solid fa-times"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Activity Logs Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-table text-blue-600"></i>
                    Activity Logs (Showing <?php echo count($logs); ?> records)
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table id="logsTable" class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Technician</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                <i class="fa-solid fa-inbox text-4xl text-gray-300 mb-2"></i>
                                <p>No activity logs found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div>
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($log['full_name'] ?? 'Unknown'); ?></div>
                                        <div class="text-gray-500"><?php echo htmlspecialchars($log['id_number'] ?? 'N/A'); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($log['entity_type']): ?>
                                        <span class="font-medium"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($log['entity_type']))); ?></span>
                                        <?php if ($log['entity_id']): ?>
                                            <span class="text-gray-400">#<?php echo htmlspecialchars($log['entity_id']); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-clock text-gray-400"></i>
                                        <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 max-w-md">
                                    <div class="truncate" title="<?php echo htmlspecialchars($log['description']); ?>">
                                        <?php echo htmlspecialchars($log['description']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <button class="text-gray-400 hover:text-gray-600">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
    $(document).ready(function() {
        <?php if (!empty($logs)): ?>
        // Initialize DataTable only if there are logs
        const table = $('#logsTable').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            "language": {
                "search": "",
                "searchPlaceholder": "Search logs...",
                "emptyTable": "No activity logs found",
                "zeroRecords": "No matching records found"
            },
            "dom": '<"flex justify-between items-center mb-4"lf>rt<"flex justify-between items-center mt-4"ip>',
            "columnDefs": [
                { "orderable": true, "targets": [0, 1, 2, 3, 4] },
                { "orderable": false, "targets": [5] }
            ]
        });
        <?php endif; ?>
    });
</script>

<?php include '../components/layout_footer.php'; ?>
