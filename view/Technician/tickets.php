<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || ($_SESSION['role'] ?? '') !== 'Technician') {
    header('Location: ../../login.php'); exit;
}

if (file_exists(__DIR__ . '/../../config/config.php')) require_once __DIR__ . '/../../config/config.php';
if (!isset($conn) || !$conn) {
    $conn = new mysqli('127.0.0.1', 'root', '', 'ams_database', 3306);
    if ($conn->connect_error) die('DB connect error');
}

// Add is_archived column if it doesn't exist
$checkCol = $conn->query("SHOW COLUMNS FROM issues LIKE 'is_archived'");
if ($checkCol->num_rows === 0) {
    $conn->query("ALTER TABLE issues ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
}

// Add archived_at column if it doesn't exist
$checkArchivedAt = $conn->query("SHOW COLUMNS FROM issues LIKE 'archived_at'");
if ($checkArchivedAt->num_rows === 0) {
    $conn->query("ALTER TABLE issues ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL");
}

// Auto-delete archived tickets older than 30 days
$conn->query("DELETE FROM issues WHERE is_archived = 1 AND archived_at IS NOT NULL AND archived_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");

// AJAX endpoint for archiving tickets
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archive_ticket') {
    header('Content-Type: application/json');
    $ticketId = intval($_POST['ticket_id'] ?? 0);
    if ($ticketId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE issues SET is_archived = 1, archived_at = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $ticketId);
    $success = $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => $success, 'message' => $success ? 'Ticket archived' : 'Archive failed']);
    exit;
}

// AJAX endpoint for deleting archived tickets
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_archived_ticket') {
    header('Content-Type: application/json');
    $ticketId = intval($_POST['ticket_id'] ?? 0);
    if ($ticketId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
        exit;
    }
    
    // Verify ticket is archived before allowing deletion
    $check = $conn->prepare("SELECT is_archived FROM issues WHERE id = ?");
    $check->bind_param('i', $ticketId);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();
    $check->close();
    
    if (!$res || !$res['is_archived']) {
        echo json_encode(['success' => false, 'message' => 'Only archived tickets can be deleted']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM issues WHERE id = ? AND is_archived = 1");
    $stmt->bind_param('i', $ticketId);
    $success = $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => $success, 'message' => $success ? 'Ticket deleted permanently' : 'Delete failed']);
    exit;
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

// Determine view mode: active (default) or archived
$viewMode = $_GET['view'] ?? 'active';
$isArchivedFilter = ($viewMode === 'archived') ? 1 : 0;

// fetch tickets assigned to this technician (only show tickets assigned to the logged-in technician)
$sql = "SELECT i.*, u.full_name AS reporter_name
        FROM issues i
        LEFT JOIN users u ON u.id = i.user_id
        WHERE i.assigned_group = ?
          AND LOWER(COALESCE(i.category,'')) IN ('hardware','software','network')
          AND COALESCE(i.is_archived, 0) = ?
        ORDER BY CASE WHEN i.priority='High' THEN 1 WHEN i.priority='Medium' THEN 2 ELSE 3 END, i.created_at DESC";
$stmt = $conn->prepare($sql);
$param = $technicianName ?? '';
$stmt->bind_param('si', $param, $isArchivedFilter);
$stmt->execute();
$result = $stmt->get_result();

include '../components/layout_header.php';
?>
<div class="p-6 max-w-7xl mx-auto">
  <h1 class="text-2xl font-semibold mb-4">Technician — My Tickets</h1>

  <div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="p-4 border-b flex items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="flex gap-2">
          <a href="?view=active" class="px-3 py-1 rounded text-sm <?php echo $viewMode === 'active' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?>">Active</a>
          <a href="?view=archived" class="px-3 py-1 rounded text-sm <?php echo $viewMode === 'archived' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?>">Archived</a>
        </div>
      </div>
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
            <?php if ($viewMode === 'active'): ?>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
            <?php endif; ?>
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
              <?php
                // Normalize priority value: treat empty/null as 'Medium'
                $rawPriority = $ticket['priority'] ?? '';
                $priority = trim((string)$rawPriority);
                if ($priority === '') $priority = 'Medium';
                $priorityEsc = htmlspecialchars($priority);
                $priorityClass = ($priority === 'High') ? 'bg-red-100 text-red-800' : (($priority === 'Medium') ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800');
              ?>
              <span class="px-2 py-1 rounded-full text-xs <?php echo $priorityClass; ?>">
                <?php echo $priorityEsc; ?>
              </span>
            </td>
            <td class="px-4 py-3 text-sm">
              <span class="status-badge inline-block px-2 py-1 rounded-full text-xs font-semibold <?php
                echo $status === 'Open' ? 'bg-blue-100 text-blue-800' : ($status === 'In Progress' ? 'bg-purple-100 text-purple-800' : ($status === 'Resolved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'));
              ?>"><?php echo htmlspecialchars($status); ?></span>
            </td>
            <?php if ($viewMode === 'active'): ?>
            <td class="px-4 py-3 text-center text-sm">
              <div class="flex items-center justify-center gap-2">
                <select class="statusSelect border rounded px-2 py-1 text-sm">
                  <option value="Open" <?php if ($status==='Open') echo 'selected'; ?>>Open</option>
                  <option value="In Progress" <?php if ($status==='In Progress') echo 'selected'; ?>>In Progress</option>
                  <option value="Resolved" <?php if ($status==='Resolved') echo 'selected'; ?>>Resolved</option>
                  <option value="Closed" <?php if ($status==='Closed') echo 'selected'; ?>>Closed</option>
                </select>
                <?php if ($status === 'Resolved'): ?>
                  <button class="archiveBtn px-3 py-1 rounded text-sm bg-gray-600 text-white hover:bg-gray-700" title="Archive this ticket">
                    <i class="fa-solid fa-box-archive"></i> Archive
                  </button>
                <?php endif; ?>
              </div>
            </td>
            <?php endif; ?>
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

// Row-level processing indicator and 1.5s minimum visual delay
const ROW_MIN_MS = 1500;

function showRowProcessing(row) {
  // disable the select control while processing
  const select = row.querySelector('.statusSelect');
  if (select) select.disabled = true;

  // avoid duplicate indicator
  let p = row.querySelector('.row-processing');
  if (!p) {
    p = document.createElement('div');
    // center the indicator inside the actions cell and make it full width
    p.className = 'row-processing flex items-center justify-center gap-2 text-sm text-gray-600 w-full';
    p.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-sm text-blue-600"></i><span>Processing, please wait...</span>';
    // append to actions cell (last td) and hide the select for cleaner centering
    const actionCell = row.querySelector('td:last-child');
    if (actionCell) {
      actionCell.appendChild(p);
      if (select) select.classList.add('hidden');
    }
  }
  return p;
}

function hideRowProcessing(row) {
  const select = row.querySelector('.statusSelect');
  if (select) {
    select.disabled = false;
    select.classList.remove('hidden');
  }
  const p = row.querySelector('.row-processing');
  if (p) p.remove();
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
  const archiveBtn = row.querySelector('.archiveBtn');

  // submit immediately when dropdown changes
  select?.addEventListener('change', () => {
    const newStatus = select.value;
    const prevStatus = row.querySelector('.status-badge')?.textContent.trim() || 'Open';

    const started = Date.now();
    showRowProcessing(row);

    fetch(apiUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Accept':'application/json'},
      body: new URLSearchParams({ticket_id: ticketId, status: newStatus})
    }).then(r => r.json()).then(j => {
      const elapsed = Date.now() - started;
      const wait = Math.max(0, ROW_MIN_MS - elapsed);
      setTimeout(() => {
        hideRowProcessing(row);
        if (j.success) {
          updateRowStatus(row, j.status);
          showToast('Status updated');
          
          // Show archive button if status is now Resolved
          if (j.status === 'Resolved' && !archiveBtn) {
            const actionsCell = row.querySelector('td:last-child > div');
            if (actionsCell) {
              const newArchiveBtn = document.createElement('button');
              newArchiveBtn.className = 'archiveBtn px-3 py-1 rounded text-sm bg-gray-600 text-white hover:bg-gray-700';
              newArchiveBtn.title = 'Archive this ticket';
              newArchiveBtn.innerHTML = '<i class="fa-solid fa-box-archive"></i> Archive';
              newArchiveBtn.addEventListener('click', () => archiveTicket(ticketId, row));
              actionsCell.appendChild(newArchiveBtn);
            }
          }
          // Hide archive button if status changed from Resolved
          else if (j.status !== 'Resolved' && archiveBtn) {
            archiveBtn.remove();
          }
        } else {
          // revert select to previous value
          if (select) select.value = prevStatus;
          showToast(j.message || 'Failed to update status', false);
        }
      }, wait);
    }).catch((err) => {
      console.error(err);
      hideRowProcessing(row);
      if (select) select.value = prevStatus;
      showToast('Request failed', false);
    });
  });

  // Archive button click handler
  archiveBtn?.addEventListener('click', () => archiveTicket(ticketId, row));
});

function archiveTicket(ticketId, row) {
  if (!confirm('Archive this ticket? It will be moved to the archived view.')) return;
  
  const started = Date.now();
  showRowProcessing(row);
  
  fetch(window.location.href, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {'Accept':'application/json'},
    body: new URLSearchParams({action: 'archive_ticket', ticket_id: ticketId})
  }).then(r => r.json()).then(j => {
    const elapsed = Date.now() - started;
    const wait = Math.max(0, ROW_MIN_MS - elapsed);
    setTimeout(() => {
      hideRowProcessing(row);
      if (j.success) {
        showToast('Ticket archived');
        // Fade out and remove the row
        row.style.transition = 'opacity 0.3s';
        row.style.opacity = '0';
        setTimeout(() => row.remove(), 300);
      } else {
        showToast(j.message || 'Archive failed', false);
      }
    }, wait);
  }).catch((err) => {
    console.error(err);
    hideRowProcessing(row);
    showToast('Request failed', false);
  });
}

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
