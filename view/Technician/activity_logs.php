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

<main class="p-4">
<!-- Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 mb-4">

  <!-- Total Logs -->
<div class="bg-white rounded-md shadow-sm border border-gray-200 p-4 flex items-center justify-between">   
     <div>
      <p class="text-xs font-medium text-gray-500 mb-1"> Total Logs </p>
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
      <i class="fa-solid fa-calendar-day text-green-600 text-xl"></i>
    </div>
  </div>

  <!-- Yesterday's Logs -->
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 flex items-center justify-between">
    <div>
      <p class="text-sm font-medium text-gray-600">Yesterday's Logs</p>
      <p class="text-3xl font-semibold text-gray-900">
        <?php echo number_format($stats['yesterday_logs']); ?>
      </p>
    </div>
    <div class="bg-amber-100 rounded-full p-3">
      <i class="fa-solid fa-clock-rotate-left text-amber-500 text-xl"></i>
    </div>
  </div>

  <!-- Unique Technicians -->
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 flex items-center justify-between">
    <div>
      <p class="text-sm font-medium text-gray-600">Unique Technicians</p>
      <p class="text-3xl font-semibold text-gray-900">
        <?php echo number_format($stats['unique_users']); ?>
      </p>
    </div>
    <div class="bg-purple-100 rounded-full p-3">
      <i class="fa-solid fa-users text-purple-500 text-xl"></i>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow mb-6 p-5">
  <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">

    <!-- Action Type -->
    <div class="relative md:col-span-2">
      <i class="fa-solid fa-bolt absolute left-3 top-3 text-gray-400"></i>
      <select name="action"
        class="peer pl-10 w-full border border-gray-300 rounded-lg px-3 pt-5 pb-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
      <label class="absolute left-10 top-1 text-xs text-gray-500 peer-focus:text-blue-600">Action</label>
    </div>

    <!-- Entity Type -->
    <div class="relative md:col-span-2">
      <i class="fa-solid fa-layer-group absolute left-3 top-3 text-gray-400"></i>
      <select name="entity"
        class="peer pl-10 w-full border border-gray-300 rounded-lg px-3 pt-5 pb-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <option value="">All Entities</option>
        <option value="ticket" <?php echo $entity_filter === 'ticket' ? 'selected' : ''; ?>>Ticket</option>
        <option value="user" <?php echo $entity_filter === 'user' ? 'selected' : ''; ?>>User</option>
        <option value="maintenance" <?php echo $entity_filter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
      </select>
      <label class="absolute left-10 top-1 text-xs text-gray-500 peer-focus:text-blue-600">Entity</label>
    </div>

    <!-- Date From -->
    <div class="relative md:col-span-2">
      <i class="fa-solid fa-calendar-day absolute left-3 top-3 text-gray-400"></i>
      <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"
        class="peer pl-10 w-full border border-gray-300 rounded-lg px-3 pt-5 pb-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
      <label class="absolute left-10 top-1 text-xs text-gray-500 peer-focus:text-blue-600">Date From</label>
    </div>

    <!-- Date To -->
    <div class="relative md:col-span-2">
      <i class="fa-solid fa-calendar-check absolute left-3 top-3 text-gray-400"></i>
      <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"
        class="peer pl-10 w-full border border-gray-300 rounded-lg px-3 pt-5 pb-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
      <label class="absolute left-10 top-1 text-xs text-gray-500 peer-focus:text-blue-600">Date To</label>
    </div>

    <!-- Search -->
    <div class="relative md:col-span-2">
      <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400"></i>
      <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
        placeholder="Search logs..."
        class="peer pl-10 w-full border border-gray-300 rounded-lg px-3 pt-5 pb-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
      <label class="absolute left-10 top-1 text-xs text-gray-500 peer-focus:text-blue-600">Search</label>
    </div>

    <!-- Buttons -->
    <div class="md:col-span-2 flex gap-2">
      <button type="submit"
        class="flex-1 bg-blue-900 hover:bg-blue-800 text-white px-4 py-2 rounded-lg transition-colors flex items-center justify-center gap-2">
        <i class="fa-solid fa-filter"></i>
        Apply
      </button>
      <a href="activity_logs.php"
        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors flex items-center justify-center">
        <i class="fa-solid fa-times"></i>
      </a>
    </div>
  </form>
</div>
        
<!-- Activity Logs Table -->
<div class="bg-white rounded-lg shadow">
  <!-- Header with title and controls -->
  <div class="px-5 pt-5 flex flex-col md:flex-row justify-between items-center gap-4">
    <!-- Title -->
    <h3 class="text-lg font-semibold text-[#1E3A8A] flex items-center gap-2">
      <i class="fa-solid fa-table text-[#1E3A8A]"></i>
      Activity Logs <span id="recordCount" class="text-sm text-gray-500">(Showing 0 records)</span>
    </h3>
  </div>

  <!-- Table -->
  <div class="overflow-x-auto">
    <table id="logsTable" class="w-full text-sm rounded-lg overflow-hidden">
      <thead class="bg-blue-50 sticky top-0 z-10">
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
          <tr class="odd:bg-white even:bg-gray-50 hover:shadow-sm hover:bg-blue-100 transition duration-150 ease-in-out cursor-pointer">
            <!-- Technician with avatar -->
            <td class="px-6 py-4 whitespace-nowrap text-sm">
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

            <!-- Timestamp -->
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-clock text-gray-400"></i>
                <?php echo date('M d, Y • h:i A', strtotime($log['created_at'])); ?>
              </div>
            </td>

            <!-- Description -->
            <td class="px-6 py-4 text-sm text-gray-900 break-words max-w-xs">
              <div class="truncate" title="<?php echo htmlspecialchars($log['description']); ?>">
                <?php echo htmlspecialchars($log['description']); ?>
              </div>
            </td>

            <!-- IP Address -->
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?>
            </td>

            <!-- Action -->
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
              <button class="text-gray-400 hover:text-[#1E3A8A] transition duration-150 ease-in-out">
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

<!-- Modal -->
<div id="modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
  <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-lg relative">
    <button id="closeModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div id="modalContent"></div>
  </div>
</div>

</div>
</main>

<script>
$(document).ready(function() {
  <?php if (!empty($logs)): ?>
  const table = $('#logsTable').DataTable({
    pageLength: 25,
    stripeClasses: [],
    language: {
      search: "",
      searchPlaceholder: "Search logs...",
      paginate: {
        previous: "« Prev",
        next: "Next »"
      },
      info: "Showing _START_ to _END_ of _TOTAL_ entries",
      emptyTable: "No activity logs found",
      zeroRecords: "No matching records found"
    },
    dom: '<"px-5 pt-5 flex flex-col md:flex-row justify-between items-center gap-4"lf>rt<"flex items-center justify-between mt-4 px-5 pb-5"ip>',
    columnDefs: [
      { orderable: true, targets: [0, 1, 2, 3, 4] },
      { orderable: false, targets: [5] }
    ]
  });

  // Reapply Tailwind-like styles to pagination buttons
  table.on('draw', function() {
    $('.dataTables_paginate a.paginate_button').addClass(
      'px-3 py-1 mx-1 rounded-md text-sm bg-gray-100 text-gray-600 hover:bg-[#1E3A8A] hover:text-white transition'
    );
    $('.dataTables_paginate a.paginate_button.current').addClass(
      'bg-[#1E3A8A] text-white font-semibold'
    );
  });

  // Animated counter
  const targetCount = <?php echo count($logs); ?>;
  let current = 0;
  const counter = document.getElementById('recordCount');
  const animate = () => {
    if (current < targetCount) {
      current += 1;
      counter.textContent = `(Showing ${current} records)`;
      requestAnimationFrame(animate);
    }
  };
  requestAnimationFrame(animate);

  // Row click modal
  document.querySelectorAll('#logsTable tbody tr').forEach(row => {
    row.addEventListener('click', () => {
      const description = row.querySelector('[title]')?.getAttribute('title') || 'No details';
      const timestamp = row.querySelector('td:nth-child(3)')?.innerText || '';
      const entity = row.querySelector('td:nth-child(2)')?.innerText || '';
      const ip = row.querySelector('td:nth-child(5)')?.innerText || '';

      document.getElementById('modalContent').innerHTML = `
        <h2 class="text-lg font-semibold mb-2">Activity Details</h2>
        <p><strong>Timestamp:</strong> ${timestamp}</p>
        <p><strong>Entity:</strong> ${entity}</p>
        <p><strong>IP Address:</strong> ${ip}</p>
        <p><strong>Description:</strong> ${description}</p>
      `;
      document.getElementById('modal').classList.remove('hidden');
    });
  });

  document.getElementById('closeModal').addEventListener('click', () => {
    document.getElementById('modal').classList.add('hidden');
  });
  <?php endif; ?>
});
</script>

<?php include '../components/layout_footer.php'; ?>

