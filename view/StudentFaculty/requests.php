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

        <!-- Main Content -->
        <main class="p-6">
            
            <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <strong>Error:</strong> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <strong>Success:</strong> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fa-solid fa-clipboard-list mr-2 text-blue-600"></i>
                        My Borrowing Requests
                    </h2>
                    <div class="text-sm text-gray-600">
                        Total Requests: <span class="font-bold text-gray-800"><?php echo count($requests); ?></span>
                    </div>
                </div>

                <?php if (empty($requests)): ?>
                    <div class="text-center py-12">
                        <i class="fa-solid fa-inbox text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-600 text-lg">No borrowing requests found.</p>
                        <p class="text-gray-500 text-sm mt-2">Start by borrowing equipment from the home page.</p>
                        <a href="index.php" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                            <i class="fa-solid fa-plus mr-2"></i>Create Request
                        </a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table id="requestsTable" class="display stripe hover w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left px-4 py-3">Request Date</th>
                                    <th class="text-left px-4 py-3">Asset Tag</th>
                                    <th class="text-left px-4 py-3">Asset Name</th>
                                    <th class="text-left px-4 py-3">Type</th>
                                    <th class="text-left px-4 py-3">Borrow Date</th>
                                    <th class="text-left px-4 py-3">Return Date</th>
                                    <th class="text-left px-4 py-3">Status</th>
                                    <th class="text-center px-4 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3"><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                    <td class="px-4 py-3"><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($request['asset_tag']); ?></span></td>
                                    <td class="px-4 py-3"><strong><?php echo htmlspecialchars($request['asset_name']); ?></strong></td>
                                    <td class="px-4 py-3"><span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded"><?php echo htmlspecialchars($request['asset_type']); ?></span></td>
                                    <td class="px-4 py-3"><?php echo date('M d, Y', strtotime($request['borrowed_date'])); ?></td>
                                    <td class="px-4 py-3">
                                        <?php 
                                        if ($request['actual_return_date']) {
                                            echo date('M d, Y', strtotime($request['actual_return_date']));
                                        } else {
                                            echo date('M d, Y', strtotime($request['expected_return_date']));
                                        }
                                        ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php
                                        $statusColors = [
                                            'Pending' => 'bg-yellow-100 text-yellow-800',
                                            'Approved' => 'bg-green-100 text-green-800',
                                            'Borrowed' => 'bg-blue-100 text-blue-800',
                                            'Returned' => 'bg-gray-100 text-gray-800',
                                            'Overdue' => 'bg-red-100 text-red-800',
                                            'Cancelled' => 'bg-red-100 text-red-800'
                                        ];
                                        $statusClass = $statusColors[$request['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($request['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button onclick="viewRequestDetails(<?php echo $request['id']; ?>)" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition-colors">
                                            <i class="fa-solid fa-eye mr-1"></i>View
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

<!-- Request Details Modal -->
<div id="requestDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl">
            <!-- Modal Header -->
            <div class="bg-blue-600 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                <h3 class="text-xl font-bold">
                    <i class="fa-solid fa-file-lines mr-2"></i>
                    Request Details
                </h3>
                <button onclick="closeRequestDetailsModal()" class="text-white hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6" id="requestDetailsContent">
                <div class="text-center py-8">
                    <i class="fa-solid fa-spinner fa-spin text-4xl text-blue-600"></i>
                    <p class="mt-2 text-gray-600">Loading request details...</p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end border-t">
                <button onclick="closeRequestDetailsModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize DataTable
$(document).ready(function() {
    <?php if (!empty($requests)): ?>
    $('#requestsTable').DataTable({
        pageLength: 10,
        order: [[0, 'desc']], // Sort by request date descending
        responsive: true,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search requests...",
            lengthMenu: "Show _MENU_ requests per page",
            info: "Showing _START_ to _END_ of _TOTAL_ requests",
            infoEmpty: "No requests available",
            infoFiltered: "(filtered from _MAX_ total requests)",
            zeroRecords: "No matching requests found",
            paginate: {
                first: '<i class="fa-solid fa-angles-left"></i>',
                last: '<i class="fa-solid fa-angles-right"></i>',
                next: '<i class="fa-solid fa-angle-right"></i>',
                previous: '<i class="fa-solid fa-angle-left"></i>'
            }
        },
        columnDefs: [
            { orderable: false, targets: 7 } // Disable sorting on Action column
        ],
        dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rtip',
        drawCallback: function() {
            // Add custom styling to pagination
            $('.dataTables_paginate .paginate_button').addClass('px-3 py-1 mx-1 border border-gray-300 rounded hover:bg-blue-600 hover:text-white transition-colors');
            $('.dataTables_paginate .paginate_button.current').addClass('bg-blue-600 text-white').removeClass('hover:bg-blue-600');
            $('.dataTables_paginate .paginate_button.disabled').addClass('opacity-50 cursor-not-allowed').removeClass('hover:bg-blue-600 hover:text-white');
        }
    });
    <?php endif; ?>
});

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

// Display Request Details
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
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="text-lg font-semibold text-gray-800">Request Information</h4>
                    <p class="text-sm text-gray-600">Request ID: #${request.id}</p>
                </div>
                <span class="px-4 py-2 rounded-full text-sm font-semibold ${statusClass}">
                    ${request.status}
                </span>
            </div>
            
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-3">Asset Details</h5>
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
            
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-3">Borrowing Details</h5>
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
            
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-2">Purpose:</h5>
                <p class="text-gray-700 bg-gray-50 p-3 rounded">${request.purpose || 'N/A'}</p>
            </div>
            
            ${request.approved_by_name ? `
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-2">Approval Information:</h5>
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
            
            ${request.return_notes ? `
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-2">Return Notes:</h5>
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
</script>

<?php include '../components/layout_footer.php'; ?>
