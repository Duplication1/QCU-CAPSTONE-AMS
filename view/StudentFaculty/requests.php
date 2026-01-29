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
require_once '../../model/AssetBorrowing.php';

// Get user's borrowing requests
$user_id = $_SESSION['user_id'];
$borrowing = new AssetBorrowing();
$requests = $borrowing->getUserHistory($user_id);

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
                            id="requestSearch" 
                            type="search" 
                            placeholder="Search requests..." 
                            class="w-full pl-8 pr-3 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A] focus:border-[#1E3A8A]"
                            oninput="filterRequests()"
                        />
                        <i class="fas fa-search absolute left-2.5 top-2 text-gray-400 text-xs"></i>
                    </div>
                    
                    <!-- Filter Button -->
                    <div class="relative">
                        <button id="filterBtn" onclick="toggleFilterMenu()" 
                            class="px-2 py-1.5 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]"
                            title="Filter requests">
                            <i class="fas fa-filter text-gray-600 text-xs"></i>
                        </button>
                        
                        <!-- Filter Dropdown Menu -->
                        <div id="filterMenu" class="hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-lg z-50">
                            <div class="p-2">
                                <h4 class="text-xs font-semibold text-gray-700 mb-2">Filter by Status</h4>
                                
                                <select id="statusFilter" onchange="applyStatusFilter()" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
                                    <option value="">All Status</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Borrowed">Borrowed</option>
                                    <option value="Returned">Returned</option>
                                    <option value="Overdue">Overdue</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                                
                                <button onclick="clearStatusFilter()" class="w-full mt-2 px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded">
                                    Clear Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto">
                <?php if (empty($requests)): ?>
                    <div class="text-center py-8 bg-white rounded shadow-sm border border-gray-200">
                        <i class="fa-solid fa-inbox text-4xl text-gray-300 mb-2"></i>
                        <p class="text-gray-600 text-sm">No borrowing requests found.</p>
                        <p class="text-gray-500 text-[10px] mt-1">Start by borrowing equipment from the home page.</p>
                        <a href="tickets.php?action=borrow" class="mt-3 inline-block bg-[#1E3A8A] hover:bg-[#1E3A8A]/90 text-white px-4 py-1.5 rounded text-xs">
                            <i class="fa-solid fa-plus mr-1"></i>Create Request
                        </a>
                    </div>
                <?php else: ?>

                    <div class="overflow-x-auto rounded border border-gray-200 shadow-sm bg-white">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Request Date</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Asset Tag</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Asset Name</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Borrow Date</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Return Date</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-medium text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>

                            <tbody id="requestsTableBody" class="bg-white divide-y divide-gray-200">
                                <?php foreach ($requests as $request): ?>
                                <tr class="hover:bg-gray-50 transition request-row" data-request='<?php echo htmlspecialchars(json_encode($request), ENT_QUOTES); ?>'>
                                    <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">
                                        <?php echo date('M d, Y', strtotime($request['created_at'])); ?>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <span class="font-mono text-[10px] bg-gray-100 px-1.5 py-0.5 rounded">
                                            <?php echo htmlspecialchars($request['asset_tag']); ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-xs font-medium text-gray-900">
                                        <?php echo htmlspecialchars($request['asset_name']); ?>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <span class="text-[10px] bg-[#1E3A8A] bg-opacity-10 text-[#1E3A8A] px-1.5 py-0.5 rounded-full">
                                            <?php echo htmlspecialchars($request['asset_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">
                                        <?php echo date('M d, Y', strtotime($request['borrowed_date'])); ?>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">
                                        <?php 
                                          if ($request['actual_return_date']) {
                                            echo date('M d, Y', strtotime($request['actual_return_date']));
                                          } else {
                                            echo date('M d, Y', strtotime($request['expected_return_date']));
                                          }
                                        ?>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <?php
                                          $statusColors = [
                                            'Pending'   => 'bg-yellow-100 text-yellow-800',
                                            'Approved'  => 'bg-green-100 text-green-800',
                                            'Borrowed'  => 'bg-blue-100 text-blue-800',
                                            'Returned'  => 'bg-gray-100 text-gray-800',
                                            'Overdue'   => 'bg-red-100 text-red-800',
                                            'Cancelled' => 'bg-red-100 text-red-800'
                                          ];
                                          $statusClass = $statusColors[$request['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="inline-block px-1.5 py-0.5 rounded-full text-[10px] font-semibold <?php echo $statusClass; ?>">
                                          <?php echo htmlspecialchars($request['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-xs text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button onclick="viewRequestDetails(<?php echo $request['id']; ?>)" class="bg-[#1E3A8A] hover:bg-blue-700 text-white px-2 py-1 rounded text-[10px] transition-colors">
                                              <i class="fa-solid fa-eye mr-0.5"></i>View
                                            </button>
                                            <button onclick="printRequest(<?php echo $request['id']; ?>)" class="bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-[10px] transition-colors">
                                              <i class="fa-solid fa-print mr-0.5"></i>Print
                                            </button>
                                            <?php if ($request['status'] === 'Pending'): ?>
                                            <button onclick="showCancelModal(<?php echo $request['id']; ?>)" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-[10px] transition-colors">
                                              <i class="fa-solid fa-xmark mr-0.5"></i>Cancel
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="mt-3 flex justify-between items-center bg-white rounded border border-gray-200 p-3">
                        <div class="text-[10px] text-gray-600">
                            Showing <span id="showingStart">0</span> to <span id="showingEnd">0</span> of <span id="totalRequests">0</span> requests
                        </div>
                        <div id="paginationButtons" class="flex gap-1">
                            <!-- Pagination buttons will be inserted here -->
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </main>

<!-- Request Details Modal -->
<div id="requestDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden z-50 flex items-center justify-center px-4 py-6">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col">

    <!-- Modal Header -->
    <div class="bg-[#1E3A8A] text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
      <h3 class="text-xl font-bold flex items-center">
        <i class="fa-solid fa-file-lines mr-2"></i>
        Request Details
      </h3>
      <button onclick="closeRequestDetailsModal()" aria-label="Close modal" class="text-white hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-white rounded">
        <i class="fa-solid fa-xmark text-xl"></i>
      </button>
    </div>

    <!-- Scrollable Modal Body -->
    <div class="overflow-y-auto p-6 flex-1 space-y-6" id="requestDetailsContent">
      
      <!-- Request Information -->
      <div>
        <h4 class="text-md font-semibold text-gray-700 mb-2 border-b pb-1 flex items-center gap-2">
          <i class="fa-solid fa-circle-info text-[#1E3A8A]"></i>
          Request Information
        </h4>
        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
          <div><strong>Request ID:</strong> #1</div>
          <div>
            <strong>Status:</strong>
            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Pending</span>
          </div>
        </div>
      </div>

      <!-- Asset Details -->
      <div>
        <h5 class="font-semibold text-gray-800 mb-3 flex items-center gap-2 sticky top-0 bg-white z-10 py-2 border-b">
            <i class="fa-solid fa-laptop text-[#1E3A8A]"></i>
            Asset Details
        </h5>
        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
          <div><strong>Asset Tag:</strong> LAP-001</div>
          <div><strong>Asset Name:</strong> Laptop</div>
          <div><strong>Type:</strong> Hardware</div>
          <div><strong>Brand/Model:</strong> Lenovo ThinkPad E14</div>
        </div>
      </div>

      <!-- Borrowing Details -->
      <div>
        <h5 class="font-semibold text-gray-800 mb-3 flex items-center gap-2 sticky top-0 bg-white z-10 py-2 border-b">
        <i class="fa-solid fa-calendar-days text-[#1E3A8A]"></i>
        Borrowing Details
        </h5>
        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
          <div><strong>Request Date:</strong> November 2, 2025</div>
          <div><strong>Borrow Date:</strong> November 4, 2025</div>
          <div><strong>Expected Return:</strong> November 5, 2025</div>
        </div>
      </div>

      <!-- Purpose -->
      <div>
        <h5 class="font-semibold text-gray-800 mb-2 flex items-center gap-2 sticky top-0 bg-white z-10 py-2 border-b">
        <i class="fa-solid fa-pencil text-[#1E3A8A]"></i>
         Purpose
        </h5>
        <p class="text-sm text-gray-600">dasdsa</p>
      </div>

      <!-- Approval Information -->
      <div>
        <h5 class="font-semibold text-gray-800 mb-2 flex items-center gap-2 sticky top-0 bg-white z-10 py-2 border-b">
        <i class="fa-solid fa-user text-[#1E3A8A]"></i>
        Approval Information
        </h5>
        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
          <div><strong>Approved By:</strong> Maria Lab Staff</div>
          <div><strong>Approval Date:</strong> November 2, 2025</div>
        </div>
      </div>
    </div>

    <!-- Modal Footer -->
    <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end border-t">
<button onclick="closeRequestDetailsModal()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium">
  Close
</button>

    </div>
  </div>
</div>

<!-- Cancel Request Confirmation Modal -->
<div id="cancelRequestModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
  <div class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
    <div class="mt-3 text-center">
      <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
        <i class="fa-solid fa-triangle-exclamation text-red-600 text-xl"></i>
      </div>
      <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Cancel Request</h3>
      <div class="mt-2 px-7 py-3">
        <p class="text-sm text-gray-500">
          Are you sure you want to cancel this borrowing request? This action cannot be undone.
        </p>
      </div>
      <div class="flex gap-3 px-4 py-3">
        <button id="cancelCancelRequest" class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-300 transition">
          No, Keep it
        </button>
        <button id="confirmCancelRequest" class="flex-1 px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition">
          Yes, Cancel
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Animation -->
<style>
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}
</style>


<script>
// Pagination variables
let currentPage = 1;
const itemsPerPage = 10; // Fixed at 10 items per page
let allRows = [];
let filteredRows = [];
let pendingCancelRequestId = null;

document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($requests)): ?>
    allRows = Array.from(document.querySelectorAll('.request-row'));
    filteredRows = [...allRows];
    updatePagination();
    <?php endif; ?>
});

// Filter menu toggle
function toggleFilterMenu() {
    const menu = document.getElementById('filterMenu');
    if (!menu) return;
    
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
        // Close menu when clicking outside
        setTimeout(() => {
            document.addEventListener('click', closeFilterMenuOutside);
        }, 0);
    } else {
        menu.classList.add('hidden');
        document.removeEventListener('click', closeFilterMenuOutside);
    }
}

function closeFilterMenuOutside(e) {
    const menu = document.getElementById('filterMenu');
    const btn = document.getElementById('filterBtn');
    
    if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
        menu.classList.add('hidden');
        document.removeEventListener('click', closeFilterMenuOutside);
    }
}

// Apply status filter
function applyStatusFilter() {
    filterRequests();
    
    // Update filter button to show active state
    const statusFilter = document.getElementById('statusFilter').value;
    const filterBtn = document.getElementById('filterBtn');
    
    if (statusFilter) {
        filterBtn.classList.add('bg-blue-100', 'border-blue-300');
        filterBtn.classList.remove('bg-gray-100', 'border-gray-300');
    } else {
        filterBtn.classList.remove('bg-blue-100', 'border-blue-300');
        filterBtn.classList.add('bg-gray-100', 'border-gray-300');
    }
}

// Clear status filter
function clearStatusFilter() {
    document.getElementById('statusFilter').value = '';
    const filterBtn = document.getElementById('filterBtn');
    filterBtn.classList.remove('bg-blue-100', 'border-blue-300');
    filterBtn.classList.add('bg-gray-100', 'border-gray-300');
    filterRequests();
}

function filterRequests() {
    const searchQuery = document.getElementById('requestSearch').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    
    filteredRows = allRows.filter(row => {
        const data = JSON.parse(row.dataset.request);
        const searchText = (
            (data.asset_name || '') + ' ' +
            (data.asset_tag || '') + ' ' +
            (data.status || '') + ' ' +
            (data.asset_type || '') + ' ' +
            (data.purpose || '')
        ).toLowerCase();
        
        const matchesSearch = !searchQuery || searchText.includes(searchQuery);
        const matchesStatus = !statusFilter || (data.status || '').toLowerCase() === statusFilter;
        
        return matchesSearch && matchesStatus;
    });
    
    currentPage = 1;
    updatePagination();
}

function updatePagination() {
    const totalItems = filteredRows.length;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    const start = (currentPage - 1) * itemsPerPage;
    const end = Math.min(start + itemsPerPage, totalItems);
    
    // Hide all rows
    allRows.forEach(row => row.style.display = 'none');
    
    // Show only current page rows
    filteredRows.slice(start, end).forEach(row => row.style.display = '');
    
    // Update showing text
    const showStart = totalItems > 0 ? start + 1 : 0;
    const showEnd = end;
    document.getElementById('showingStart').textContent = showStart;
    document.getElementById('showingEnd').textContent = showEnd;
    document.getElementById('totalRequests').textContent = totalItems;
    
    // Update pagination buttons
    renderPaginationButtons(totalPages);
}

function renderPaginationButtons(totalPages) {
    const container = document.getElementById('paginationButtons');
    if (!container) return;
    
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

// View Request Details
async function viewRequestDetails(requestId) {
    document.getElementById('requestDetailsModal').classList.remove('hidden');
    
    try {
        const response = await fetch(`../../controller/get_request_details.php?id=${requestId}`);
        const data = await response.json();
        
        if (data.success) {
            displayRequestDetails(data.request);
        } else {
            document.getElementById('requestDetailsContent').innerHTML = `
                <div class="text-center py-8 text-red-600">
                    <i class="fa-solid fa-exclamation-triangle text-4xl mb-2"></i>
                    <p>Error loading request details.</p>
                </div>
            `;
        }
    } catch (error) {
        document.getElementById('requestDetailsContent').innerHTML = `
            <div class="text-center py-8 text-red-600">
                <i class="fa-solid fa-exclamation-triangle text-4xl mb-2"></i>
                <p>Error loading request details.</p>
            </div>
        `;
    }
}
    function displayRequestDetails(request) {
    const statusColors = {
        'Pending': 'bg-yellow-100 text-yellow-800',
        'Approved': 'bg-green-100 text-green-800',
        'Borrowed': 'bg-blue-100 text-blue-800',
        'Returned': 'bg-gray-100 text-gray-800',
        'Overdue': 'bg-red-100 text-red-800',
        'Cancelled': 'bg-red-100 text-red-800'
    };
    
    const statusClass = statusColors[request.status] || 'bg-gray-100 text-gray-800';
    
    const content = `
        <div class="space-y-4">
            <!-- Request Information -->
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-[#1E3A8A]"></i>
                        Request Information
                    </h4>
                    <p class="text-sm text-gray-600">Request ID: #${request.id}</p>
                </div>
                <span class="px-4 py-2 rounded-full text-sm font-semibold ${statusClass}">
                    ${request.status}
                </span>
            </div>
            
            <!-- Asset Details -->
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-laptop text-[#1E3A8A]"></i>
                    Asset Details
                </h5>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Asset Tag:</p>
                        <p class="font-semibold">${request.asset_tag}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Asset Name:</p>
                        <p class="font-semibold">${request.asset_name}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Type:</p>
                        <p class="font-semibold">${request.asset_type}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Brand/Model:</p>
                        <p class="font-semibold">${request.brand || 'N/A'} ${request.model || ''}</p>
                    </div>
                </div>
            </div>
            
            <!-- Borrowing Details -->
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-days text-[#1E3A8A]"></i>
                    Borrowing Details
                </h5>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Request Date:</p>
                        <p class="font-semibold">${new Date(request.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Borrow Date:</p>
                        <p class="font-semibold">${new Date(request.borrowed_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Expected Return:</p>
                        <p class="font-semibold">${new Date(request.expected_return_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    </div>
                    ${request.actual_return_date ? `
                    <div>
                        <p class="text-sm text-gray-600">Actual Return:</p>
                        <p class="font-semibold">${new Date(request.actual_return_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    </div>
                    ` : ''}
                </div>
            </div>
            
            <!-- Purpose -->
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-pencil text-[#1E3A8A]"></i>
                    Purpose
                </h5>
                <p class="text-gray-700 bg-gray-50 p-3 rounded">${request.purpose || 'N/A'}</p>
            </div>
            
            <!-- Approval Information -->
            ${request.approved_by_name ? `
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-user text-[#1E3A8A]"></i>
                    Approval Information
                </h5>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Approved By:</p>
                        <p class="font-semibold">${request.approved_by_name}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Approval Date:</p>
                        <p class="font-semibold">${new Date(request.approved_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    </div>
                </div>
            </div>
            ` : ''}
            
            <!-- Return Notes -->
            ${request.return_notes ? `
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-clipboard text-[#1E3A8A]"></i>
                    Return Notes
                </h5>
                <p class="text-gray-700 bg-gray-50 p-3 rounded">${request.return_notes}</p>
                ${request.returned_condition ? `
                <div class="mt-2">
                    <span class="text-sm text-gray-600">Returned Condition: </span>
                    <span class="font-semibold">${request.returned_condition}</span>
                </div>
                ` : ''}
            </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('requestDetailsContent').innerHTML = content;
}

// Close Request Details Modal
function closeRequestDetailsModal() {
    document.getElementById('requestDetailsModal').classList.add('hidden');
}

// Cancel Request Modal Functions
function showCancelModal(requestId) {
    pendingCancelRequestId = requestId;
    document.getElementById('cancelRequestModal').classList.remove('hidden');
}

function hideCancelModal() {
    document.getElementById('cancelRequestModal').classList.add('hidden');
    pendingCancelRequestId = null;
}

// Cancel Request Modal Event Listeners
document.getElementById('cancelCancelRequest')?.addEventListener('click', hideCancelModal);

document.getElementById('confirmCancelRequest')?.addEventListener('click', async function() {
    if (!pendingCancelRequestId) return;
    
    const requestId = pendingCancelRequestId;
    hideCancelModal();
    
    try {
        const response = await fetch('../../controller/cancel_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `request_id=${requestId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message
            showToast('Request cancelled successfully', 'success');
            
            // Find and remove the row from the table
            const row = document.querySelector(`tr[data-request*='"id":${requestId}']`);
            if (row) {
                // Fade out animation
                row.style.transition = 'opacity 0.3s';
                row.style.opacity = '0';
                
                setTimeout(() => {
                    row.remove();
                    
                    // Update pagination arrays
                    allRows = allRows.filter(r => r !== row);
                    filterRequests();
                    
                    // If no requests left, reload page to show empty state
                    if (allRows.length === 0) {
                        location.reload();
                    }
                }, 300);
            }
        } else {
            showToast(data.message || 'Failed to cancel request', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
});

// Close modal when clicking outside
document.getElementById('cancelRequestModal')?.addEventListener('click', function(e) {
    if (e.target.id === 'cancelRequestModal') {
        hideCancelModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('cancelRequestModal');
        if (modal && !modal.classList.contains('hidden')) {
            hideCancelModal();
        }
    }
});

// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed top-6 right-6 px-6 py-3 rounded-lg shadow-lg text-white z-50 ${
        type === 'success' ? 'bg-green-600' : 'bg-red-600'
    }`;
    toast.innerHTML = `
        <div class="flex items-center gap-2">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Print Request
async function printRequest(requestId) {
    try {
        const response = await fetch(`../../controller/get_request_details.php?id=${requestId}`);
        const data = await response.json();
        
        if (data.success) {
            const request = data.request;
            
            // Create printable content
            const printContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Borrowing Request #${request.id}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
                        .header h1 { margin: 0; font-size: 24px; }
                        .header p { margin: 5px 0; font-size: 12px; }
                        .section { margin: 20px 0; }
                        .section h3 { border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 10px; }
                        .row { display: flex; margin: 5px 0; }
                        .label { font-weight: bold; width: 180px; }
                        .value { flex: 1; }
                        .status { display: inline-block; padding: 5px 10px; border-radius: 5px; font-weight: bold; }
                        .status.pending { background: #fef3c7; color: #92400e; }
                        .status.approved { background: #d1fae5; color: #065f46; }
                        .status.borrowed { background: #dbeafe; color: #1e40af; }
                        .status.returned { background: #f3f4f6; color: #374151; }
                        .status.overdue { background: #fee2e2; color: #991b1b; }
                        .status.cancelled { background: #fee2e2; color: #991b1b; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>QUEZON CITY UNIVERSITY</h1>
                        <p>Asset Management System</p>
                        <h2>EQUIPMENT BORROWING REQUEST</h2>
                    </div>
                    
                    <div class="section">
                        <h3>Request Information</h3>
                        <div class="row">
                            <div class="label">Request ID:</div>
                            <div class="value">#${request.id}</div>
                        </div>
                        <div class="row">
                            <div class="label">Status:</div>
                            <div class="value"><span class="status ${request.status.toLowerCase()}">${request.status}</span></div>
                        </div>
                        <div class="row">
                            <div class="label">Request Date:</div>
                            <div class="value">${new Date(request.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3>Borrower Information</h3>
                        <div class="row">
                            <div class="label">Name:</div>
                            <div class="value">${request.borrower_name}</div>
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3>Asset Details</h3>
                        <div class="row">
                            <div class="label">Asset Tag:</div>
                            <div class="value">${request.asset_tag}</div>
                        </div>
                        <div class="row">
                            <div class="label">Asset Name:</div>
                            <div class="value">${request.asset_name}</div>
                        </div>
                        <div class="row">
                            <div class="label">Type:</div>
                            <div class="value">${request.asset_type}</div>
                        </div>
                        <div class="row">
                            <div class="label">Brand/Model:</div>
                            <div class="value">${request.brand || 'N/A'} ${request.model || ''}</div>
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3>Borrowing Details</h3>
                        <div class="row">
                            <div class="label">Borrow Date:</div>
                            <div class="value">${new Date(request.borrowed_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
                        </div>
                        <div class="row">
                            <div class="label">Expected Return Date:</div>
                            <div class="value">${new Date(request.expected_return_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
                        </div>
                        ${request.actual_return_date ? `
                        <div class="row">
                            <div class="label">Actual Return Date:</div>
                            <div class="value">${new Date(request.actual_return_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
                        </div>
                        ` : ''}
                        <div class="row">
                            <div class="label">Purpose:</div>
                            <div class="value">${request.purpose || 'N/A'}</div>
                        </div>
                    </div>
                    
                    ${request.approved_by_name ? `
                    <div class="section">
                        <h3>Approval Information</h3>
                        <div class="row">
                            <div class="label">Approved By:</div>
                            <div class="value">${request.approved_by_name}</div>
                        </div>
                        <div class="row">
                            <div class="label">Approval Date:</div>
                            <div class="value">${new Date(request.approved_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
                        </div>
                    </div>
                    ` : ''}
                    
                    ${request.return_notes ? `
                    <div class="section">
                        <h3>Return Information</h3>
                        ${request.returned_condition ? `
                        <div class="row">
                            <div class="label">Returned Condition:</div>
                            <div class="value">${request.returned_condition}</div>
                        </div>
                        ` : ''}
                        <div class="row">
                            <div class="label">Return Notes:</div>
                            <div class="value">${request.return_notes}</div>
                        </div>
                    </div>
                    ` : ''}
                </body>
                </html>
            `;
            
            // Open print window
            const printWindow = window.open('', '', 'width=800,height=600');
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        } else {
            showToast('Error loading request details', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred while printing', 'error');
    }
}
</script>

<?php include '../components/layout_footer.php'; ?>
