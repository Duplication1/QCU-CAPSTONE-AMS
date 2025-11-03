<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || ($_SESSION['role'] ?? '') !== 'Technician') {
    header('Location: ../../login.php'); exit;
}

if (file_exists(__DIR__ . '/../../config/config.php')) require_once __DIR__ . '/../../config/config.php';
if (!isset($conn) || !$conn) {
    $conn = new mysqli('127.0.0.1', 'root', '', 'ams_database', 3306);
    if ($conn->connect_error) die('DB connect error');
}

// technician name
$technicianName = $_SESSION['full_name'] ?? null;
if (!$technicianName && isset($_SESSION['user_id'])) {
    $s = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $s->bind_param('i', $_SESSION['user_id']);
    $s->execute();
    $r = $s->get_result()->fetch_assoc();
    $technicianName = $r['full_name'] ?? null;
    $s->close();
}

// fetch tickets assigned to this technician (only show tickets assigned to the logged-in technician)
$sql = "SELECT i.*, u.full_name AS reporter_name
        FROM issues i
        LEFT JOIN users u ON u.id = i.user_id
        WHERE i.assigned_group = ?
          AND LOWER(COALESCE(i.category,'')) IN ('hardware','software','network')
        ORDER BY CASE WHEN i.priority='High' THEN 1 WHEN i.priority='Medium' THEN 2 ELSE 3 END, i.created_at DESC";
$stmt = $conn->prepare($sql);
$param = $technicianName ?? '';
$stmt->bind_param('s', $param);
$stmt->execute();
$result = $stmt->get_result();

include '../components/layout_header.php';
?>
<div class="p-6 max-w-7xl mx-auto">
  <h1 class="text-2xl font-semibold mb-4">Technician — My Tickets</h1>

  <div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="p-4 border-b flex items-center justify-between gap-4">
      <div class="text-sm text-gray-600">Showing tickets assigned to you.</div>
      <div class="flex items-center gap-2">
        <input id="techSearch" type="search" placeholder="Search title, room..." class="border rounded px-3 py-2"/>
      </div>
    </div>

    <!-- Category filter buttons -->
    <div class="px-4 py-3 border-b bg-gray-50">
      <div id="categoryFilters" class="flex flex-wrap gap-2">
        <button class="cat-btn px-3 py-1 rounded text-sm bg-blue-600 text-white" data-cat="all">All</button>
        <button class="cat-btn px-3 py-1 rounded text-sm bg-gray-100 text-gray-800" data-cat="hardware">Hardware</button>
        <button class="cat-btn px-3 py-1 rounded text-sm bg-gray-100 text-gray-800" data-cat="software">Software</button>
        <button class="cat-btn px-3 py-1 rounded text-sm bg-gray-100 text-gray-800" data-cat="network">Network</button>
      </div>
    </div>

<?php
// Show message when there are no tickets
if (!$result || $result->num_rows === 0): ?>
    <div class="p-12 text-center">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 20 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
      </svg>
      <h3 class="mt-4 text-lg font-medium text-gray-900">No tickets found</h3>
      <p class="mt-2 text-sm text-gray-500">No issues have been submitted yet.</p>
    </div>
<?php else: ?>
    <div class="overflow-x-auto">
      <table id="techTickets" class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title / Details</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <?php while ($ticket = $result->fetch_assoc()): 
            $status = $ticket['status'] ?? 'Open';
            $category = htmlspecialchars(strtolower($ticket['category'] ?? 'other'));
            $categoryLabel = htmlspecialchars(ucfirst($category));
            $title = htmlspecialchars($ticket['title'] ?? '-');
            $desc = htmlspecialchars($ticket['description'] ?? '');
            $loc = htmlspecialchars(($ticket['room'] ?? '-') . ' / ' . ($ticket['terminal'] ?? '-'));
          ?>
          <tr data-ticket-id="<?php echo (int)$ticket['id']; ?>" data-category="<?php echo $category; ?>" data-ticket='<?php echo json_encode($ticket, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT); ?>'>
            <td class="px-4 py-3 text-sm text-gray-700"><?php echo $categoryLabel; ?></td>
            <td class="px-4 py-3">
              <div class="font-medium text-gray-900"><?php echo $title; ?></div>
              <div class="text-xs text-gray-500 mt-1"><?php echo (strlen($desc) > 120 ? substr($desc,0,120).'...' : $desc); ?></div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700"><?php echo $loc; ?></td>
            <td class="px-4 py-3 text-sm">
              <span class="px-2 py-1 rounded-full text-xs <?php echo $ticket['priority'] === 'High' ? 'bg-red-100 text-red-800' : ($ticket['priority'] === 'Medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'); ?>">
                <?php echo htmlspecialchars($ticket['priority'] ?? 'Medium'); ?>
              </span>
            </td>
            <td class="px-4 py-3 text-sm">
              <span class="status-badge inline-block px-2 py-1 rounded-full text-xs font-semibold <?php
                echo $status === 'Open' ? 'bg-blue-100 text-blue-800' : ($status === 'In Progress' ? 'bg-purple-100 text-purple-800' : ($status === 'Resolved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'));
              ?>"><?php echo htmlspecialchars($status); ?></span>
            </td>
            <td class="px-4 py-3 text-center text-sm">
              <div class="flex items-center justify-center gap-2">
                <select class="statusSelect border rounded px-2 py-1 text-sm">
                  <option value="Open" <?php if ($status==='Open') echo 'selected'; ?>>Open</option>
                  <option value="In Progress" <?php if ($status==='In Progress') echo 'selected'; ?>>In Progress</option>
                  <option value="Resolved" <?php if ($status==='Resolved') echo 'selected'; ?>>Resolved</option>
                  <option value="Closed" <?php if ($status==='Closed') echo 'selected'; ?>>Closed</option>
                </select>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
<?php endif; ?>

  </div>
</div>

<script>
const apiUrl = '../../controller/technician_update_status.php';

function showToast(msg, ok = true) {
  const t = document.createElement('div');
  t.textContent = msg;
  t.className = 'fixed top-6 right-6 px-4 py-2 rounded shadow ' + (ok ? 'bg-green-600 text-white' : 'bg-red-600 text-white');
  document.body.appendChild(t);
  setTimeout(()=> t.remove(), 2500);
}

function updateRowStatus(row, newStatus) {
  const badge = row.querySelector('.status-badge');
  if (!badge) return;
  badge.textContent = newStatus;
  badge.className = 'status-badge inline-block px-2 py-1 rounded-full text-xs font-semibold ' +
    (newStatus === 'Open' ? 'bg-blue-100 text-blue-800' : (newStatus === 'In Progress' ? 'bg-purple-100 text-purple-800' : (newStatus === 'Resolved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')));
}

// category filter buttons (client-side)
const catButtons = document.querySelectorAll('#categoryFilters .cat-btn');
function setActiveCatButton(activeBtn) {
  catButtons.forEach(b => {
    if (b === activeBtn) {
      b.classList.remove('bg-gray-100', 'text-gray-800');
      b.classList.add('bg-blue-600', 'text-white');
    } else {
      b.classList.remove('bg-blue-600','text-white');
      b.classList.add('bg-gray-100','text-gray-800');
    }
  });
}

catButtons.forEach(btn => {
  btn.addEventListener('click', () => {
    const cat = btn.dataset.cat; // 'hardware','software',...
    setActiveCatButton(btn);
    document.querySelectorAll('#techTickets tbody tr').forEach(row => {
      const rcat = row.dataset.category || 'other';
      if (cat === 'all' || rcat.toLowerCase() === cat.toLowerCase()) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  });
});

// Attach handlers: listen for dropdown change (auto-submit)
document.querySelectorAll('#techTickets tbody tr').forEach(row => {
  const ticketId = row.dataset.ticketId;
  const select = row.querySelector('.statusSelect');

  // submit immediately when dropdown changes
  select?.addEventListener('change', () => {
    const status = select.value;
    fetch(apiUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Accept':'application/json'},
      body: new URLSearchParams({ticket_id: ticketId, status})
    }).then(r => r.json()).then(j => {
      if (j.success) {
        updateRowStatus(row, j.status);
        showToast('Status updated');
      } else {
        showToast(j.message || 'Failed to update status', false);
      }
    }).catch(() => showToast('Request failed', false));
  });
});

// Search (unchanged)
document.getElementById('techSearch')?.addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('#techTickets tbody tr').forEach(r => {
    const t = (r.textContent||'').toLowerCase();
    r.style.display = t.includes(q) ? '' : 'none';
  });
});

// initial state: All active
document.querySelector('#categoryFilters .cat-btn[data-cat="all"]').click();
</script>

<?php include '../components/layout_footer.php'; ?>
