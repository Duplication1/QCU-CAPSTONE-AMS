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
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <button onclick="exportLogs()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                            <i class="fa-solid fa-download"></i>
                            Export Logs
                        </button>
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
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Entity Type</label>
                            <select name="entity" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Entities</option>
                                <option value="asset" <?php echo $entity_filter === 'asset' ? 'selected' : ''; ?>>Asset</option>
                                <option value="pc_unit" <?php echo $entity_filter === 'pc_unit' ? 'selected' : ''; ?>>PC Unit</option>
                                <option value="ticket" <?php echo $entity_filter === 'ticket' ? 'selected' : ''; ?>>Ticket</option>
                                <option value="signature" <?php echo $entity_filter === 'signature' ? 'selected' : ''; ?>>Signature</option>
                                <option value="user" <?php echo $entity_filter === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="report" <?php echo $entity_filter === 'report' ? 'selected' : ''; ?>>Report</option>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-clock text-gray-400"></i>
                                                <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div>
                                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($log['full_name'] ?? 'Unknown'); ?></div>
                                                <div class="text-gray-500"><?php echo htmlspecialchars($log['id_number'] ?? 'N/A'); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
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
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $color; ?>">
                                                <?php echo ucfirst(htmlspecialchars($log['action'])); ?>
                                            </span>
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
                                        <td class="px-6 py-4 text-sm text-gray-900 max-w-md">
                                            <div class="truncate" title="<?php echo htmlspecialchars($log['description']); ?>">
                                                <?php echo htmlspecialchars($log['description']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
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
    </div>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
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
                    { "orderable": true, "targets": [0, 1, 2, 3] },
                    { "orderable": false, "targets": [4, 5] }
                ]
            });
        });

        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = '../../controller/export_logs.php?' + params.toString();
        }

        // Auto-refresh notification for new logs (optional)
        let lastLogCount = <?php echo count($logs); ?>;
        setInterval(function() {
            fetch('?ajax=1&count_only=1')
                .then(response => response.json())
                .then(data => {
                    if (data.count > lastLogCount) {
                        const notification = document.createElement('div');
                        notification.className = 'fixed top-24 right-6 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-3';
                        notification.innerHTML = `
                            <i class="fa-solid fa-bell"></i>
                            <span>New activity logs available. <a href="#" onclick="location.reload()" class="underline font-semibold">Refresh</a></span>
                        `;
                        document.body.appendChild(notification);
                        setTimeout(() => notification.remove(), 5000);
                        lastLogCount = data.count;
                    }
                })
                .catch(err => console.error('Failed to check for new logs:', err));
        }, 30000); // Check every 30 seconds
    </script>
</body>
</html>