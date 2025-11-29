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
            i.assigned_technician as technician_name,
            CASE 
                WHEN i.status = 'Open' AND i.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'Overdue'
                ELSE i.status
            END as display_status
        FROM issues i
        LEFT JOIN rooms r ON i.room_id = r.id
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

    <div class="bg-white rounded shadow-sm border border-gray-200 p-3 mb-2 flex-shrink-0">
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
                            <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Issue ID</th>
                            <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Room</th>
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
        <?php endif; ?>
    </div>
</main>

<!-- Issue Details Modal -->
<div id="issueDetailsModal" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black opacity-50" onclick="closeIssueDetailsModal()"></div>
    <div class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded shadow-lg w-full max-w-2xl z-10 p-4 mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-3 border-b pb-2">
            <h3 class="text-sm font-semibold text-gray-800">Issue Details</h3>
            <button type="button" onclick="closeIssueDetailsModal()" class="text-gray-600 hover:text-gray-800 text-xl">&times;</button>
        </div>

        <div id="issueDetailsContent" class="space-y-3 text-xs">
            <!-- Details will be loaded here dynamically -->
        </div>

        <div class="mt-4 flex justify-end gap-2 border-t pt-3">
            <button type="button" onclick="closeIssueDetailsModal()" 
                class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded text-xs transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<script>
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
        
        if (matchesSearch && matchesStatus && matchesCategory) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

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
                        <label class="block text-[10px] font-medium text-gray-700 mb-1">Issue ID</label>
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
