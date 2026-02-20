<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has Technician role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Technician') {
    header("Location: ../login.php");
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

<style>
main {
    padding: 0.5rem;
    background-color: #f9fafb;
    min-height: 100%;
}
</style>

<main>
    <div class="flex-1 flex flex-col">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white rounded shadow-sm border border-gray-200 mb-3">
            <div>
                <h1 class="text-lg font-bold text-gray-800">Activity Logs</h1>
                <p class="text-xs text-gray-500 mt-0.5">Monitor technician activities and system events</p>
            </div>
        </div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 mb-3">

  <!-- Total Logs -->
  <div class="bg-white rounded-md shadow-sm border border-gray-200 p-4 flex items-center justify-between">   
    <div>
      <p class="text-xs font-medium text-gray-500 mb-1">Total Logs</p>
      <p class="text-2xl font-bold text-gray-900">
        <?php echo number_format($stats['total_logs']); ?>
      </p>
    </div>
    <div class="bg-blue-100 rounded-full p-3">
      <i class="fa-solid fa-clipboard-list text-blue-500 text-lg"></i>
    </div>
  </div>

  <!-- Today's Logs -->
  <div class="bg-white rounded-md shadow-sm border border-gray-200 p-4 flex items-center justify-between">
    <div>
      <p class="text-xs font-medium text-gray-500 mb-1">Today's Logs</p>
      <p class="text-2xl font-bold text-gray-900">
        <?php echo number_format($stats['today_logs']); ?>
      </p>
    </div>
    <div class="bg-green-100 rounded-full p-3">
      <i class="fa-solid fa-calendar-day text-green-600 text-lg"></i>
    </div>
  </div>

  <!-- Yesterday's Logs -->
  <div class="bg-white rounded-md shadow-sm border border-gray-200 p-4 flex items-center justify-between">
    <div>
      <p class="text-xs font-medium text-gray-500 mb-1">Yesterday's Logs</p>
      <p class="text-2xl font-bold text-gray-900">
        <?php echo number_format($stats['yesterday_logs']); ?>
      </p>
    </div>
    <div class="bg-amber-100 rounded-full p-3">
      <i class="fa-solid fa-clock-rotate-left text-amber-500 text-lg"></i>
    </div>
  </div>

  <!-- Unique Technicians -->
  <div class="bg-white rounded-md shadow-sm border border-gray-200 p-4 flex items-center justify-between">
    <div>
      <p class="text-xs font-medium text-gray-500 mb-1">Unique Technicians</p>
      <p class="text-2xl font-bold text-gray-900">
        <?php echo number_format($stats['unique_users']); ?>
      </p>
    </div>
    <div class="bg-purple-100 rounded-full p-3">
      <i class="fa-solid fa-users text-purple-500 text-lg"></i>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="bg-white rounded shadow-sm border border-gray-200 p-3 mb-3">
  <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-2">

    <!-- Action Type -->
    <div>
      <select name="action"
        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <option value="">All Actions</option>
        <option value="login" <?php echo $action_filter === 'login' ? 'selected' : ''; ?>>Login</option>
        <option value="logout" <?php echo $action_filter === 'logout' ? 'selected' : ''; ?>>Logout</option>
        <option value="update" <?php echo $action_filter === 'update' ? 'selected' : ''; ?>>Update</option>
        <option value="update_condition" <?php echo $action_filter === 'update_condition' ? 'selected' : ''; ?>>Update Condition</option>
        <option value="bulk_update_condition" <?php echo $action_filter === 'bulk_update_condition' ? 'selected' : ''; ?>>Bulk Update Condition</option>
        <option value="complete" <?php echo $action_filter === 'complete' ? 'selected' : ''; ?>>Complete</option>
        <option value="assign" <?php echo $action_filter === 'assign' ? 'selected' : ''; ?>>Assign</option>
        <option value="resolve" <?php echo $action_filter === 'resolve' ? 'selected' : ''; ?>>Resolve</option>
      </select>
    </div>

    <!-- Entity Type -->
    <div>
      <select name="entity"
        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <option value="">All Entities</option>
        <option value="ticket" <?php echo $entity_filter === 'ticket' ? 'selected' : ''; ?>>Ticket</option>
        <option value="user" <?php echo $entity_filter === 'user' ? 'selected' : ''; ?>>User</option>
        <option value="maintenance" <?php echo $entity_filter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
      </select>
    </div>

    <!-- Date From -->
    <div>
      <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"
        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <!-- Date To -->
    <div>
      <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"
        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <!-- Search -->
    <div>
      <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
        placeholder="Search logs..."
        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <!-- Buttons -->
    <div class="flex gap-2">
      <button type="submit"
        class="flex-1 bg-[#1E3A8A] hover:bg-[#153570] text-white px-4 py-2 rounded text-sm font-medium transition-colors flex items-center justify-center gap-2">
        <i class="fa-solid fa-filter"></i>
        Apply
      </button>
      <a href="activity_logs.php"
        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded text-sm transition-colors flex items-center justify-center">
        <i class="fa-solid fa-times"></i>
      </a>
    </div>
  </form>
</div>
        
<!-- Activity Logs Table -->
<div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
  <div class="overflow-x-auto">
    <table id="logsTable" class="min-w-full divide-y divide-gray-200">
<thead class="bg-[#1E3A8A] text-white">
  <tr>
    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-white">Technician</th>
    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-white">Entity</th>
    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-white">Timestamp</th>
    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-white">Description</th>
    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-white">IP Address</th>
  </tr>
</thead>

      <tbody class="bg-white divide-y divide-gray-200">
        <?php if (empty($logs)): ?>
        <tr>
          <td colspan="5" class="px-4 py-8 text-center text-gray-500">
            <i class="fa-solid fa-inbox text-4xl text-gray-300 mb-2"></i>
            <p class="text-sm">No activity logs found</p>
          </td>
        </tr>
        <?php else: ?>
          <?php foreach ($logs as $log): ?>
          <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
            <!-- Technician with avatar -->
            <td class="px-4 py-3 whitespace-nowrap text-sm">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-[#1E3A8A] text-white flex items-center justify-center text-xs font-semibold">
                  <?php echo strtoupper(substr($log['full_name'], 0, 1)); ?>
                </div>
                <div>
                  <div class="font-medium text-gray-900"><?php echo htmlspecialchars($log['full_name'] ?? 'Unknown'); ?></div>
                  <div class="text-gray-500"><?php echo htmlspecialchars($log['id_number'] ?? 'N/A'); ?></div>
                </div>
              </div>
            </td>

            <!-- Entity -->
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
              <?php if ($log['entity_type']): ?>
                <span class="font-medium"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($log['entity_type']))); ?></span>
                <?php if ($log['entity_id']): ?>
                  <span class="text-gray-400">#<?php echo htmlspecialchars($log['entity_id']); ?></span>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-gray-400">—</span>
              <?php endif; ?>
            </td>

            <!-- Timestamp -->
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-clock text-gray-400"></i>
                <?php echo date('M d, Y • h:i A', strtotime($log['created_at'])); ?>
              </div>
            </td>

            <!-- Description -->
            <td class="px-4 py-3 text-sm text-gray-900">
              <div class="max-w-md">
                <?php echo htmlspecialchars($log['description']); ?>
              </div>
            </td>

            <!-- IP Address -->
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
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
</main>

<?php include '../components/layout_footer.php'; ?>

