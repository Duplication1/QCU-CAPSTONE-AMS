<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has student or faculty role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || !in_array($_SESSION['role'], ['Student', 'Faculty'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
require_once '../../model/Database.php';

// Get user's ticket issues
$user_id = $_SESSION['user_id'];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get all issues submitted by the user
    $stmt = $conn->prepare("
        SELECT 
            i.*,
            r.name as room,
            pc.terminal_number,
            i.assigned_technician as technician_name,
            CASE 
                WHEN i.status = 'Open' AND i.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'Overdue'
                ELSE i.status
            END as display_status
        FROM issues i
        LEFT JOIN rooms r ON i.room_id = r.id
        LEFT JOIN pc_units pc ON i.pc_id = pc.id
        WHERE i.user_id = ?
        ORDER BY 
            CASE i.status
                WHEN 'Open' THEN 1
                WHEN 'In Progress' THEN 2
                WHEN 'Resolved' THEN 3
                WHEN 'Closed' THEN 4
            END,
            i.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $issues = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching issues: " . $e->getMessage());
    $issues = [];
}

include '../components/layout_header.php';
?>

<style>
    body, html { overflow: hidden !important; height: 100vh; }
</style>

<!-- Main Content -->
<main class="p-2 bg-gray-50 h-screen overflow-hidden flex flex-col">
    
    <!-- Session Messages -->
    <?php include '../components/session_messages.php'; ?>

    <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-4 flex-shrink-0">
        <!-- Search and Filter Bar -->
        <div class="mt-2 flex items-center gap-2">
            <div class="relative flex-1">
                <input 
                    id="issueSearch" 
                    type="search" 
                    placeholder="Search by issue type, room, or description..." 
                    class="w-full pl-8 pr-3 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A] focus:border-[#1E3A8A]"
                    oninput="filterIssues()"
                />
                <i class="fas fa-search absolute left-2.5 top-2 text-gray-400 text-xs"></i>
            </div>
            
            <!-- Status Filter -->
            <select id="statusFilter" onchange="filterIssues()" class="px-2 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
                <option value="">All Status</option>
                <option value="Open">Open</option>
                <option value="In Progress">In Progress</option>
                <option value="Resolved">Resolved</option>
                <option value="Closed">Closed</option>
            </select>

            <!-- Category Filter -->
            <select id="categoryFilter" onchange="filterIssues()" class="px-2 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
                <option value="">All Categories</option>
                <option value="Hardware">Hardware</option>
                <option value="Software">Software</option>
                <option value="Network">Network</option>
                <option value="Laboratory">Laboratory</option>
            </select>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto">
        <?php if (empty($issues)): ?>
            <div class="text-center py-8 bg-white rounded shadow-sm border border-gray-200">
                <i class="fa-solid fa-inbox text-4xl text-gray-300 mb-2"></i>
                <p class="text-gray-600 text-sm">No ticket issues found.</p>
                <p class="text-gray-500 text-[10px] mt-1">Submit your first ticket from the Submit Tickets page.</p>
                <a href="tickets.php" class="mt-3 inline-block bg-[#1E3A8A] hover:bg-[#1E3A8A]/90 text-white px-4 py-1.5 rounded text-xs">
                    <i class="fa-solid fa-plus mr-1"></i>Submit Ticket
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto rounded border border-gray-200 shadow-sm bg-white">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Ticket ID</th>
                            <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Room</th>
                            <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Terminal No</th>
                            <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Submitted</th>
                            <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Assigned To</th>
                            <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody id="issuesTableBody" class="bg-white divide-y divide-gray-200">
                        <?php foreach ($issues as $issue): ?>
                        <tr class="hover:bg-gray-50 transition issue-row" 
                            data-category="<?php echo htmlspecialchars($issue['category']); ?>"
                            data-status="<?php echo htmlspecialchars($issue['status']); ?>"
                            data-search="<?php echo htmlspecialchars(strtolower($issue['category'] . ' ' . $issue['room'] . ' ' . $issue['description'])); ?>">
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span class="font-mono text-[10px] bg-gray-100 px-1.5 py-0.5 rounded">
                                    #<?php echo str_pad($issue['id'], 4, '0', STR_PAD_LEFT); ?>
                                </span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span class="text-[10px] bg-[#1E3A8A] bg-opacity-10 text-[#1E3A8A] px-1.5 py-0.5 rounded-full">
                                    <?php echo htmlspecialchars($issue['category']); ?>
                                </span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">
                                <?php echo htmlspecialchars($issue['room']); ?>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">
                                <?php 
                                if ($issue['terminal_number']) {
                                    echo htmlspecialchars($issue['terminal_number']);
                                } else {
                                    echo '<span class="text-gray-400 text-[10px]">(no terminal)</span>';
                                }
                                ?>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-900 max-w-xs truncate">
                                <?php echo htmlspecialchars($issue['description']); ?>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">
                                <?php echo date('M d, Y', strtotime($issue['created_at'])); ?>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">
                                <?php 
                                if ($issue['technician_name']) {
                                    echo htmlspecialchars($issue['technician_name']);
                                } else {
                                    echo '<span class="text-gray-400 text-[10px]">Unassigned</span>';
                                }
                                ?>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <?php
                                $statusColors = [
                                    'Open' => 'bg-yellow-100 text-yellow-800',
                                    'In Progress' => 'bg-blue-100 text-blue-800',
                                    'Resolved' => 'bg-green-100 text-green-800',
                                    'Closed' => 'bg-gray-100 text-gray-800',
                                    'Overdue' => 'bg-red-100 text-red-800'
                                ];
                                $statusClass = $statusColors[$issue['display_status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-block px-1.5 py-0.5 rounded-full text-[10px] font-semibold <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($issue['display_status']); ?>
                                </span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs">
                                <button onclick="viewIssueDetails(<?php echo $issue['id']; ?>)" 
                                    class="bg-[#1E3A8A] hover:bg-blue-700 text-white px-2 py-1 rounded text-[10px] transition-colors">
                                    <i class="fa-solid fa-eye mr-0.5"></i>View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <div class="mt-3 flex justify-between items-center bg-white rounded border border-gray-200 p-3">
                <div class="text-[10px] text-gray-600">
                    Showing <span id="showingStart">0</span> to <span id="showingEnd">0</span> of <span id="totalIssues">0</span> issues
                </div>
                <div id="paginationButtons" class="flex gap-1">
                    <!-- Pagination buttons will be inserted here -->
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Issue Details Modal -->
<div id="issueDetailsModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black opacity-50" onclick="closeIssueDetailsModal()"></div>
    <div class="relative bg-white rounded shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-3 border-b pb-2 p-4">
            <h3 class="text-sm font-semibold text-gray-800">Issue Details</h3>
            <button type="button" onclick="closeIssueDetailsModal()" class="text-gray-600 hover:text-gray-800 text-xl">&times;</button>
        </div>

        <div id="issueDetailsContent" class="space-y-3 text-xs p-4">
            <!-- Details will be loaded here dynamically -->
        </div>

        <div class="mt-4 flex justify-end gap-2 border-t pt-3 p-4">
            <button type="button" onclick="closeIssueDetailsModal()" 
                class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded text-xs transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Pagination variables
let currentPage = 1;
const itemsPerPage = 10;

// Filter issues
function filterIssues() {
    const searchTerm = document.getElementById('issueSearch').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const categoryFilter = document.getElementById('categoryFilter').value;
    const rows = document.querySelectorAll('.issue-row');
    
    rows.forEach(row => {
        const searchData = row.getAttribute('data-search');
        const status = row.getAttribute('data-status');
        const category = row.getAttribute('data-category');
        
        const matchesSearch = searchData.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;
        const matchesCategory = !categoryFilter || category === categoryFilter;
        
        // Use data attribute to mark filtered rows instead of display
        if (matchesSearch && matchesStatus && matchesCategory) {
            row.setAttribute('data-filtered', 'true');
        } else {
            row.setAttribute('data-filtered', 'false');
        }
    });
    
    // Reset to page 1 when filtering
    currentPage = 1;
    updatePagination();
}

// Pagination functions
function updatePagination() {
    const rows = Array.from(document.querySelectorAll('.issue-row'));
    
    // Get rows that match current filters
    const filteredRows = rows.filter(row => row.getAttribute('data-filtered') !== 'false');
    const totalItems = filteredRows.length;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    
    // Hide all rows first
    rows.forEach(row => row.style.display = 'none');
    
    // Show only the rows for the current page from filtered results
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    filteredRows.slice(start, end).forEach(row => {
        row.style.display = '';
    });
    
    // Update showing text
    const showStart = totalItems > 0 ? start + 1 : 0;
    const showEnd = Math.min(end, totalItems);
    document.getElementById('showingStart').textContent = showStart;
    document.getElementById('showingEnd').textContent = showEnd;
    document.getElementById('totalIssues').textContent = totalItems;
    
    // Render pagination buttons
    renderPaginationButtons(totalPages);
}

function renderPaginationButtons(totalPages) {
    const container = document.getElementById('paginationButtons');
    container.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    // Previous button
    const prevBtn = createPageButton('‹', currentPage > 1, () => {
        if (currentPage > 1) {
            currentPage--;
            updatePagination();
        }
    });
    container.appendChild(prevBtn);
    
    // Page number buttons
    const maxButtons = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
    let endPage = Math.min(totalPages, startPage + maxButtons - 1);
    
    if (endPage - startPage < maxButtons - 1) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }
    
    if (startPage > 1) {
        container.appendChild(createPageButton(1, true, () => { currentPage = 1; updatePagination(); }));
        if (startPage > 2) {
            const dots = document.createElement('span');
            dots.className = 'px-2 text-[10px] text-gray-500';
            dots.textContent = '...';
            container.appendChild(dots);
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const btn = createPageButton(i, true, () => {
            currentPage = i;
            updatePagination();
        }, i === currentPage);
        container.appendChild(btn);
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const dots = document.createElement('span');
            dots.className = 'px-2 text-[10px] text-gray-500';
            dots.textContent = '...';
            container.appendChild(dots);
        }
        container.appendChild(createPageButton(totalPages, true, () => { currentPage = totalPages; updatePagination(); }));
    }
    
    // Next button
    const nextBtn = createPageButton('›', currentPage < totalPages, () => {
        if (currentPage < totalPages) {
            currentPage++;
            updatePagination();
        }
    });
    container.appendChild(nextBtn);
}

function createPageButton(text, enabled, onClick, isActive = false) {
    const button = document.createElement('button');
    button.textContent = text;
    button.className = `px-2 py-1 text-[10px] rounded transition-colors ${
        isActive 
            ? 'bg-[#1E3A8A] text-white' 
            : enabled 
                ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300' 
                : 'bg-gray-100 text-gray-400 cursor-not-allowed'
    }`;
    
    if (enabled && !isActive) {
        button.onclick = onClick;
    } else if (!enabled) {
        button.disabled = true;
    }
    
    return button;
}

// Initialize pagination on page load
document.addEventListener('DOMContentLoaded', () => {
    // Mark all rows as visible initially
    const rows = document.querySelectorAll('.issue-row');
    rows.forEach(row => row.setAttribute('data-filtered', 'true'));
    updatePagination();
});

// View issue details
async function viewIssueDetails(issueId) {
    try {
        showLoadingModal('Loading issue details...');
        
        const response = await fetch(`../../controller/get_issue_details.php?id=${issueId}`);
        const data = await response.json();
        
        hideLoadingModal();
        
        if (data.success) {
            const issue = data.issue;
            const statusColors = {
                'Open': 'bg-yellow-100 text-yellow-800',
                'In Progress': 'bg-blue-100 text-blue-800',
                'Resolved': 'bg-green-100 text-green-800',
                'Closed': 'bg-gray-100 text-gray-800'
            };
            const statusClass = statusColors[issue.status] || 'bg-gray-100 text-gray-800';
            
            document.getElementById('issueDetailsContent').innerHTML = `
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-medium text-gray-700 mb-1">Ticket ID</label>
                        <p class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">#${String(issue.id).padStart(4, '0')}</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-medium text-gray-700 mb-1">Category</label>
                        <span class="inline-block text-[10px] bg-[#1E3A8A] bg-opacity-10 text-[#1E3A8A] px-2 py-1 rounded-full">${issue.category}</span>
                    </div>
                    <div>
                        <label class="block text-[10px] font-medium text-gray-700 mb-1">Room</label>
                        <p class="text-xs">${issue.room}</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-medium text-gray-700 mb-1">Terminal No</label>
                        <p class="text-xs">${issue.terminal_number || '<span class="text-gray-400">(no terminal)</span>'}</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-medium text-gray-700 mb-1">Status</label>
                        <span class="inline-block px-2 py-1 rounded-full text-[10px] font-semibold ${statusClass}">${issue.status}</span>
                    </div>
                    ${issue.asset_tag ? `
                    <div>
                        <label class="block text-[10px] font-medium text-gray-700 mb-1">Asset Tag</label>
                        <p class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">${issue.asset_tag}</p>
                    </div>
                    ` : ''}
                    <div>
                        <label class="block text-[10px] font-medium text-gray-700 mb-1">Submitted Date</label>
                        <p class="text-xs">${new Date(issue.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</p>
                    </div>
                    ${issue.assigned_to_name ? `
                    <div class="col-span-2">
                        <label class="block text-[10px] font-medium text-gray-700 mb-1">Assigned Technician</label>
                        <p class="text-xs">${issue.assigned_to_name}</p>
                    </div>
                    ` : ''}
                </div>
                <div>
                    <label class="block text-[10px] font-medium text-gray-700 mb-1">Description</label>
                    <p class="text-xs bg-gray-50 p-2 rounded border border-gray-200">${issue.description}</p>
                </div>
                ${issue.technician_notes ? `
                <div>
                    <label class="block text-[10px] font-medium text-gray-700 mb-1">Technician Notes</label>
                    <p class="text-xs bg-blue-50 p-2 rounded border border-blue-200">${issue.technician_notes}</p>
                </div>
                ` : ''}
                ${issue.resolved_at ? `
                <div>
                    <label class="block text-[10px] font-medium text-gray-700 mb-1">Resolved Date</label>
                    <p class="text-xs">${new Date(issue.resolved_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>
                </div>
                ` : ''}
            `;
            
            document.getElementById('issueDetailsModal').classList.remove('hidden');
        } else {
            showNotification(data.message || 'Failed to load issue details', 'error');
        }
    } catch (error) {
        hideLoadingModal();
        console.error('Error:', error);
        showNotification('An error occurred while loading issue details', 'error');
    }
}

function closeIssueDetailsModal() {
    document.getElementById('issueDetailsModal').classList.add('hidden');
}
</script>

<?php include '../components/layout_footer.php'; ?>
