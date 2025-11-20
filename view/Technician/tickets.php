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
      <table class="min-w-full divide-y divide-gray-200">
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
        <tbody id="ticketsTableBody" class="bg-white divide-y divide-gray-100">
          <?php while ($ticket = $result->fetch_assoc()): 
            $status = $ticket['status'] ?? 'Open';
            $category = htmlspecialchars(strtolower($ticket['category'] ?? 'other'));
            $categoryLabel = htmlspecialchars(ucfirst($category));
            $title = htmlspecialchars($ticket['title'] ?? '-');
            $desc = htmlspecialchars($ticket['description'] ?? '');
            $loc = htmlspecialchars(($ticket['room'] ?? '-') . ' / ' . ($ticket['terminal'] ?? '-'));
          ?>
          <tr class="ticket-row" data-ticket-id="<?php echo (int)$ticket['id']; ?>" data-category="<?php echo $category; ?>" data-ticket='<?php echo json_encode($ticket, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT); ?>'>
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

    <!-- Pagination (only shows when more than 10 tickets) -->
    <div id="paginationContainer" class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6 rounded-b-lg hidden">
      <div class="flex justify-center">
        <nav id="pagination" class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
          <!-- Pagination buttons will be inserted here by JavaScript -->
        </nav>
      </div>
    </div>

<?php endif; ?>

  </div>
</div>

<!-- Archive Confirmation Modal -->
<div id="archiveModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
  <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
    <div class="mt-3 text-center">
      <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
        <i class="fa-solid fa-box-archive text-yellow-600 text-xl"></i>
      </div>
      <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Archive Ticket</h3>
      <div class="mt-2 px-7 py-3">
        <p class="text-sm text-gray-500">
          Are you sure you want to archive this ticket? It will be moved to the archived view and can be accessed later.
        </p>
      </div>
      <div class="flex gap-3 px-4 py-3">
        <button id="cancelArchive" class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-300 transition">
          Cancel
        </button>
        <button id="confirmArchive" class="flex-1 px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
          Archive
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const apiUrl = '../../controller/technician_update_status.php';

// Pagination variables
let currentPage = 1;
const itemsPerPage = 10; // Fixed at 10 items per page
let allTicketRows = [];
let filteredTicketRows = [];
let currentCategory = 'all';
let pendingArchiveTicketId = null;
let pendingArchiveRow = null;

function showToast(msg, ok = true) {
  const t = document.createElement('div');
  t.textContent = msg;
  t.className = 'fixed top-6 right-6 px-4 py-2 rounded shadow z-50 ' + (ok ? 'bg-green-600 text-white' : 'bg-red-600 text-white');
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

// Initialize pagination
function initPagination() {
  allTicketRows = Array.from(document.querySelectorAll('#ticketsTableBody .ticket-row'));
  filteredTicketRows = [...allTicketRows];
  currentPage = 1;
  updatePagination();
  attachTicketHandlers();
}

// Filter tickets by search and category
function filterTickets() {
  const searchQuery = (document.getElementById('techSearch')?.value || '').toLowerCase();
  
  filteredTicketRows = allTicketRows.filter(row => {
    const category = row.dataset.category || 'other';
    const text = (row.textContent || '').toLowerCase();
    
    const matchesCategory = currentCategory === 'all' || category.toLowerCase() === currentCategory.toLowerCase();
    const matchesSearch = !searchQuery || text.includes(searchQuery);
    
    return matchesCategory && matchesSearch;
  });
  
  currentPage = 1;
  updatePagination();
}

// Update pagination display
function updatePagination() {
  const totalItems = filteredTicketRows.length;
  const totalPages = Math.ceil(totalItems / itemsPerPage);
  const start = (currentPage - 1) * itemsPerPage;
  const end = Math.min(start + itemsPerPage, totalItems);
  
  // Hide all rows
  allTicketRows.forEach(row => row.style.display = 'none');
  
  // Show only current page rows
  filteredTicketRows.slice(start, end).forEach(row => row.style.display = '');
  
  // Update pagination buttons
  renderPaginationButtons(totalPages);
}

// Render pagination buttons
function renderPaginationButtons(totalPages) {
  const pagination = document.getElementById('pagination');
  const paginationContainer = document.getElementById('paginationContainer');
  if (!pagination) return;
  
  pagination.innerHTML = '';
  
  // Only show pagination if there are more than 10 items (more than 1 page)
  if (totalPages <= 1) {
    paginationContainer.classList.add('hidden');
    return;
  }
  paginationContainer.classList.remove('hidden');
  
  // Calculate which page numbers to show
  const maxButtons = 5;
  let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
  let endPage = Math.min(totalPages, startPage + maxButtons - 1);
  
  // Adjust if we're near the end
  if (endPage - startPage < maxButtons - 1) {
    startPage = Math.max(1, endPage - maxButtons + 1);
  }
  
  // Add ellipsis and first page if needed
  if (startPage > 1) {
    pagination.appendChild(createPageButton('1', true, () => goToPage(1)));
    if (startPage > 2) {
      const dots = document.createElement('span');
      dots.className = 'relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700';
      dots.textContent = '...';
      pagination.appendChild(dots);
    }
  }
  
  // Page number buttons
  for (let i = startPage; i <= endPage; i++) {
    const btn = createPageButton(i.toString(), true, () => goToPage(i));
    if (i === currentPage) {
      btn.className = 'relative inline-flex items-center px-4 py-2 border border-blue-600 bg-blue-600 text-sm font-medium text-white';
    }
    pagination.appendChild(btn);
  }
  
  // Add ellipsis and last page if needed
  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      const dots = document.createElement('span');
      dots.className = 'relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700';
      dots.textContent = '...';
      pagination.appendChild(dots);
    }
    pagination.appendChild(createPageButton(totalPages.toString(), true, () => goToPage(totalPages)));
  }
}

// Create page button
function createPageButton(text, enabled, onClick) {
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = enabled 
    ? 'relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-blue-600 focus:border-blue-600 transition-colors duration-150'
    : 'relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed';
  btn.textContent = text;
  btn.disabled = !enabled;
  if (enabled) btn.onclick = onClick;
  return btn;
}

// Go to specific page
function goToPage(page) {
  currentPage = page;
  updatePagination();
  // Re-attach handlers after pagination update
  attachTicketHandlers();
}

// Archive Modal Functions
function showArchiveModal(ticketId, row) {
  pendingArchiveTicketId = ticketId;
  pendingArchiveRow = row;
  document.getElementById('archiveModal').classList.remove('hidden');
}

function hideArchiveModal() {
  document.getElementById('archiveModal').classList.add('hidden');
  pendingArchiveTicketId = null;
  pendingArchiveRow = null;
}

// Archive Modal Event Listeners
document.getElementById('cancelArchive')?.addEventListener('click', hideArchiveModal);

document.getElementById('confirmArchive')?.addEventListener('click', function() {
  if (pendingArchiveTicketId && pendingArchiveRow) {
    hideArchiveModal();
    performArchive(pendingArchiveTicketId, pendingArchiveRow);
  }
});

// Close modal when clicking outside
document.getElementById('archiveModal')?.addEventListener('click', function(e) {
  if (e.target.id === 'archiveModal') {
    hideArchiveModal();
  }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    const modal = document.getElementById('archiveModal');
    if (modal && !modal.classList.contains('hidden')) {
      hideArchiveModal();
    }
  }
});

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
    currentCategory = btn.dataset.cat;
    setActiveCatButton(btn);
    filterTickets();
    attachTicketHandlers();
  });
});

// Attach handlers to visible rows
function attachTicketHandlers() {
  document.querySelectorAll('#ticketsTableBody .ticket-row').forEach(row => {
    if (row.style.display === 'none') return; // Skip hidden rows
    
    const ticketId = row.dataset.ticketId;
    const select = row.querySelector('.statusSelect');
    const archiveBtn = row.querySelector('.archiveBtn');

    // Remove old listeners by cloning
    if (select) {
      const newSelect = select.cloneNode(true);
      select.parentNode.replaceChild(newSelect, select);
    }
    if (archiveBtn) {
      const newArchiveBtn = archiveBtn.cloneNode(true);
      archiveBtn.parentNode.replaceChild(newArchiveBtn, archiveBtn);
    }

    const currentSelect = row.querySelector('.statusSelect');
    const currentArchiveBtn = row.querySelector('.archiveBtn');

    // submit immediately when dropdown changes
    currentSelect?.addEventListener('change', () => {
      const newStatus = currentSelect.value;
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
            if (j.status === 'Resolved' && !currentArchiveBtn) {
              const actionsCell = row.querySelector('td:last-child > div');
              if (actionsCell) {
                const newArchiveBtn = document.createElement('button');
                newArchiveBtn.className = 'archiveBtn px-3 py-1 rounded text-sm bg-gray-600 text-white hover:bg-gray-700';
                newArchiveBtn.title = 'Archive this ticket';
                newArchiveBtn.innerHTML = '<i class="fa-solid fa-box-archive"></i> Archive';
                newArchiveBtn.addEventListener('click', () => showArchiveModal(ticketId, row));
                actionsCell.appendChild(newArchiveBtn);
              }
            }
            // Hide archive button if status changed from Resolved
            else if (j.status !== 'Resolved' && currentArchiveBtn) {
              currentArchiveBtn.remove();
            }
          } else {
            // revert select to previous value
            if (currentSelect) currentSelect.value = prevStatus;
            showToast(j.message || 'Failed to update status', false);
          }
        }, wait);
      }).catch((err) => {
        console.error(err);
        hideRowProcessing(row);
        if (currentSelect) currentSelect.value = prevStatus;
        showToast('Request failed', false);
      });
    });

    // Archive button click handler
    currentArchiveBtn?.addEventListener('click', () => showArchiveModal(ticketId, row));
  });
}

// Perform archive action
function performArchive(ticketId, row) {
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
          setTimeout(() => {
            row.remove();
            // Update arrays and pagination after removal
            allTicketRows = allTicketRows.filter(r => r !== row);
            filterTickets();
          }, 300);
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

// Search with pagination
document.getElementById('techSearch')?.addEventListener('input', function() {
  filterTickets();
  attachTicketHandlers();
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  const hasTickets = document.querySelectorAll('#ticketsTableBody .ticket-row').length > 0;
  if (hasTickets) {
    initPagination();
    // Set initial category to "All"
    document.querySelector('#categoryFilters .cat-btn[data-cat="all"]')?.click();
  }
});
</script>

<?php include '../components/layout_footer.php'; ?>
