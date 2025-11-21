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
        WHERE i.assigned_technician = ?
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

<main>
  <div class="flex-1 flex flex-col overflow-hidden">
    
    <!-- Header -->
    <div class="flex items-center justify-between px-3 py-2 bg-white rounded shadow-sm border border-gray-200 mb-2">
      <div>
        <h3 class="text-sm font-semibold text-gray-800">My Tickets</h3>
        <p class="text-[10px] text-gray-500 mt-0.5">Manage assigned technical support tickets</p>
      </div>
      
      <div class="flex items-center gap-2">
        <!-- View Mode Tabs -->
        <div class="flex gap-1">
          <a href="?view=active" class="px-2 py-1 rounded text-[10px] font-medium <?php echo $viewMode === 'active' ? 'bg-[#1E3A8A] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
            <i class="fas fa-list mr-1"></i>Active
          </a>
          <a href="?view=archived" class="px-2 py-1 rounded text-[10px] font-medium <?php echo $viewMode === 'archived' ? 'bg-[#1E3A8A] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
            <i class="fas fa-archive mr-1"></i>Archived
          </a>
        </div>
        
        <!-- Search -->
        <div class="relative">
          <input id="techSearch" type="search" placeholder="Search tickets..." 
            class="w-48 pl-8 pr-3 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
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
              <h4 class="text-xs font-semibold text-gray-700 mb-2">Filter Tickets</h4>
              
              <div class="mb-2">
                <label class="block text-[10px] text-gray-600 mb-1">Category</label>
                <select id="categoryFilter" onchange="applyFilters()" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                  <option value="all">All Categories</option>
                  <option value="hardware">Hardware</option>
                  <option value="software">Software</option>
                  <option value="network">Network</option>
                </select>
              </div>
              
              <div class="mb-2">
                <label class="block text-[10px] text-gray-600 mb-1">Priority</label>
                <select id="priorityFilter" onchange="applyFilters()" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                  <option value="">All Priorities</option>
                  <option value="High">High</option>
                  <option value="Medium">Medium</option>
                  <option value="Low">Low</option>
                </select>
              </div>
              
              <div class="mb-2">
                <label class="block text-[10px] text-gray-600 mb-1">Status</label>
                <select id="statusFilter" onchange="applyFilters()" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                  <option value="">All Status</option>
                  <option value="Open">Open</option>
                  <option value="In Progress">In Progress</option>
                  <option value="Resolved">Resolved</option>
                  <option value="Closed">Closed</option>
                </select>
              </div>
              
              <button onclick="clearFilters()" class="w-full px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded">
                Clear Filters
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

<?php
// Show message when there are no tickets
if (!$result || $result->num_rows === 0): ?>
    <div class="flex-1 flex items-center justify-center bg-white rounded shadow-sm border border-gray-200">
      <div class="text-center p-8">
        <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
        <h3 class="text-sm font-medium text-gray-900 mb-1">No tickets found</h3>
        <p class="text-xs text-gray-500">No issues have been assigned to you yet.</p>
      </div>
    </div>
<?php else: ?>
    <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-[#1E3A8A] text-white sticky top-0 z-10">
          <tr>
            <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Type</th>
            <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Title / Details</th>
            <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Location</th>
            <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Priority</th>
            <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Status</th>
            <?php if ($viewMode === 'active'): ?>
            <th class="px-3 py-2 text-center text-[10px] font-medium uppercase">Actions</th>
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
            $rawPriority = $ticket['priority'] ?? '';
            $priority = trim((string)$rawPriority);
            if ($priority === '') $priority = 'Medium';
          ?>
          <tr class="ticket-row hover:bg-blue-50 transition" 
              data-ticket-id="<?php echo (int)$ticket['id']; ?>" 
              data-category="<?php echo $category; ?>"
              data-priority="<?php echo htmlspecialchars($priority); ?>"
              data-status="<?php echo htmlspecialchars($status); ?>"
              data-ticket='<?php echo json_encode($ticket, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT); ?>'>
            <td class="px-3 py-2 text-xs text-gray-700">
              <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium <?php 
                echo $category === 'hardware' ? 'bg-orange-100 text-orange-700' : 
                     ($category === 'software' ? 'bg-blue-100 text-blue-700' : 
                     ($category === 'network' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700')); 
              ?>">
                <?php echo $categoryLabel; ?>
              </span>
            </td>
            <td class="px-3 py-2">
              <div class="font-medium text-gray-900 text-xs"><?php echo $title; ?></div>
              <div class="text-[10px] text-gray-500 mt-0.5 line-clamp-1"><?php echo (strlen($desc) > 80 ? substr($desc,0,80).'...' : $desc); ?></div>
            </td>
            <td class="px-3 py-2 text-xs text-gray-700"><?php echo $loc; ?></td>
            <td class="px-3 py-2 text-xs">
              <?php
                $priorityEsc = htmlspecialchars($priority);
                $priorityClass = ($priority === 'High') ? 'bg-red-100 text-red-800' : (($priority === 'Medium') ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800');
              ?>
              <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium <?php echo $priorityClass; ?>">
                <?php echo $priorityEsc; ?>
              </span>
            </td>
            <td class="px-3 py-2 text-xs">
              <span class="status-badge inline-block px-1.5 py-0.5 rounded-full text-[10px] font-semibold <?php
                echo $status === 'Open' ? 'bg-blue-100 text-blue-800' : ($status === 'In Progress' ? 'bg-purple-100 text-purple-800' : ($status === 'Resolved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'));
              ?>"><?php echo htmlspecialchars($status); ?></span>
            </td>
            <?php if ($viewMode === 'active'): ?>
            <td class="px-3 py-2 text-center text-xs">
              <div class="flex items-center justify-center gap-1">
                <select class="statusSelect border rounded px-2 py-1 text-[10px]">
                  <option value="Open" <?php if ($status==='Open') echo 'selected'; ?>>Open</option>
                  <option value="In Progress" <?php if ($status==='In Progress') echo 'selected'; ?>>In Progress</option>
                  <option value="Resolved" <?php if ($status==='Resolved') echo 'selected'; ?>>Resolved</option>
                  <option value="Closed" <?php if ($status==='Closed') echo 'selected'; ?>>Closed</option>
                </select>
                <?php if ($status === 'Resolved'): ?>
                  <button class="archiveBtn px-2 py-1 rounded text-[10px] bg-gray-600 text-white hover:bg-gray-700" title="Archive this ticket">
                    <i class="fa-solid fa-box-archive"></i>
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

    <!-- Pagination -->
    <div id="paginationContainer" class="hidden px-3 py-2 bg-white rounded shadow-sm border border-gray-200 border-t-0 rounded-t-none">
      <div class="flex justify-center">
        <nav id="pagination" class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
          <!-- Pagination buttons will be inserted here by JavaScript -->
        </nav>
      </div>
    </div>

<?php endif; ?>

  </div>
</main>

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
let currentPriority = '';
let currentStatus = '';
let pendingArchiveTicketId = null;
let pendingArchiveRow = null;

function showToast(msg, ok = true) {
  const t = document.createElement('div');
  t.textContent = msg;
  t.className = 'fixed top-6 right-6 px-4 py-2 rounded shadow-lg z-50 text-xs ' + (ok ? 'bg-green-600 text-white' : 'bg-red-600 text-white');
  document.body.appendChild(t);
  setTimeout(()=> t.remove(), 2500);
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
  
  if (filterBtn && filterMenu && !filterBtn.contains(e.target) && !filterMenu.contains(e.target)) {
    filterMenu.classList.add('hidden');
  }
});

function applyFilters() {
  currentCategory = document.getElementById('categoryFilter').value;
  currentPriority = document.getElementById('priorityFilter').value;
  currentStatus = document.getElementById('statusFilter').value;
  filterTickets();
}

function clearFilters() {
  document.getElementById('categoryFilter').value = 'all';
  document.getElementById('priorityFilter').value = '';
  document.getElementById('statusFilter').value = '';
  document.getElementById('techSearch').value = '';
  currentCategory = 'all';
  currentPriority = '';
  currentStatus = '';
  filterTickets();
}

function updateRowStatus(row, newStatus) {
  const badge = row.querySelector('.status-badge');
  if (!badge) return;
  badge.textContent = newStatus;
  badge.className = 'status-badge inline-block px-1.5 py-0.5 rounded-full text-[10px] font-semibold ' +
    (newStatus === 'Open' ? 'bg-blue-100 text-blue-800' : (newStatus === 'In Progress' ? 'bg-purple-100 text-purple-800' : (newStatus === 'Resolved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')));
  
  // Update data attribute
  row.dataset.status = newStatus;
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
    p.className = 'row-processing flex items-center justify-center gap-2 text-xs text-gray-600 w-full';
    p.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs text-blue-600"></i><span>Processing...</span>';
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
    const priority = row.dataset.priority || '';
    const status = row.dataset.status || '';
    const text = (row.textContent || '').toLowerCase();
    
    const matchesCategory = currentCategory === 'all' || category.toLowerCase() === currentCategory.toLowerCase();
    const matchesPriority = !currentPriority || priority === currentPriority;
    const matchesStatus = !currentStatus || status === currentStatus;
    const matchesSearch = !searchQuery || text.includes(searchQuery);
    
    return matchesCategory && matchesPriority && matchesStatus && matchesSearch;
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
      dots.className = 'relative inline-flex items-center px-3 py-1.5 border border-gray-300 bg-white text-xs font-medium text-gray-700';
      dots.textContent = '...';
      pagination.appendChild(dots);
    }
  }
  
  // Page number buttons
  for (let i = startPage; i <= endPage; i++) {
    const btn = createPageButton(i.toString(), true, () => goToPage(i));
    if (i === currentPage) {
      btn.className = 'relative inline-flex items-center px-3 py-1.5 border border-[#1E3A8A] bg-[#1E3A8A] text-xs font-medium text-white';
    }
    pagination.appendChild(btn);
  }
  
  // Add ellipsis and last page if needed
  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      const dots = document.createElement('span');
      dots.className = 'relative inline-flex items-center px-3 py-1.5 border border-gray-300 bg-white text-xs font-medium text-gray-700';
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
    ? 'relative inline-flex items-center px-3 py-1.5 border border-gray-300 bg-white text-xs font-medium text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-[#1E3A8A] focus:border-[#1E3A8A] transition-colors duration-150'
    : 'relative inline-flex items-center px-3 py-1.5 border border-gray-300 bg-gray-100 text-xs font-medium text-gray-400 cursor-not-allowed';
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
                newArchiveBtn.className = 'archiveBtn px-2 py-1 rounded text-[10px] bg-gray-600 text-white hover:bg-gray-700';
                newArchiveBtn.title = 'Archive this ticket';
                newArchiveBtn.innerHTML = '<i class="fa-solid fa-box-archive"></i>';
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
  }
});
</script>

<?php include '../components/layout_footer.php'; ?>
