<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
require_once '../../model/Database.php';
require_once '../../model/AssetBorrowing.php';

// Get Laboratory Staff's e-signature
$lab_staff_signature = null;
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT e_signature FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $signature_file = $stmt->fetchColumn();
    if ($signature_file && file_exists('../../uploads/signatures/' . $signature_file)) {
        $lab_staff_signature = $signature_file;
    }
} catch (PDOException $e) {
    // Handle error silently
}

// Get borrowing requests with optional filter
$filter_status = $_GET['status'] ?? 'all';
$borrowing = new AssetBorrowing();
$requests = [];
$stats = ['total_borrowings' => 0, 'pending' => 0, 'approved' => 0, 'returned' => 0];
$db_error = null;

try {
    if ($filter_status === 'all') {
        $requests = $borrowing->getAll();
    } else {
        $requests = $borrowing->getAll(['status' => $filter_status]);
    }
    
    // Get statistics
    $stats = $borrowing->getStatistics();
} catch (Exception $e) {
    $db_error = $e->getMessage();
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
}

include '../components/layout_header.php';
?>

        <!-- Main Content -->
        <main class="p-6">
            
            <!-- Session Messages -->
            <?php include '../components/session_messages.php'; ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-yellow-100 text-sm font-medium">Pending Requests</p>
                            <p class="text-3xl font-bold mt-2"><?php echo $stats['pending'] ?? 0; ?></p>
                        </div>
                        <div class="bg-white bg-opacity-30 rounded-lg p-3">
                            <i class="fa-solid fa-clock text-3xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Currently Borrowed</p>
                            <p class="text-3xl font-bold mt-2"><?php echo $stats['approved'] ?? 0; ?></p>
                        </div>
                        <div class="bg-white bg-opacity-30 rounded-lg p-3">
                            <i class="fa-solid fa-hand-holding text-3xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-400 to-green-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Returned</p>
                            <p class="text-3xl font-bold mt-2"><?php echo $stats['returned'] ?? 0; ?></p>
                        </div>
                        <div class="bg-white bg-opacity-30 rounded-lg p-3">
                            <i class="fa-solid fa-check-circle text-3xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-red-400 to-red-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100 text-sm font-medium">Total Requests</p>
                            <p class="text-3xl font-bold mt-2"><?php echo $stats['total_borrowings'] ?? 0; ?></p>
                        </div>
                        <div class="bg-white bg-opacity-30 rounded-lg p-3">
                            <i class="fa-solid fa-list text-3xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Borrowing Requests Table -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fa-solid fa-clipboard-list mr-2 text-blue-600"></i>
                        Borrowing Requests
                    </h2>
                    
                    <!-- Filter Buttons -->
                    <div class="flex flex-wrap gap-2">
                        <a href="?status=all" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter_status === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            All
                        </a>
                        <a href="?status=Pending" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter_status === 'Pending' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            Pending
                        </a>
                        <a href="?status=Approved" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter_status === 'Approved' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            Approved
                        </a>
                        <a href="?status=Returned" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?php echo $filter_status === 'Returned' ? 'bg-gray-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            Returned
                        </a>
                    </div>
                </div>

                <?php if ($db_error): ?>
                    <div class="text-center py-12">
                        <i class="fa-solid fa-database text-6xl text-red-300 mb-4"></i>
                        <p class="text-red-600 text-lg font-semibold">Database Error</p>
                        <p class="text-gray-600 text-sm mt-2">The borrowing tables may not exist yet.</p>
                        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg max-w-2xl mx-auto text-left">
                            <p class="text-sm font-semibold text-red-800 mb-2">To fix this:</p>
                            <ol class="text-sm text-red-700 space-y-1 ml-4">
                                <li>1. Open phpMyAdmin</li>
                                <li>2. Select your database (ams_database)</li>
                                <li>3. Go to the SQL tab</li>
                                <li>4. Copy and run the content from: <code class="bg-red-100 px-2 py-1 rounded">database/create_assets_table.sql</code></li>
                            </ol>
                            <p class="text-xs text-red-600 mt-3 font-mono"><?php echo htmlspecialchars($db_error); ?></p>
                        </div>
                    </div>
                <?php elseif (empty($requests)): ?>
                    <div class="text-center py-12">
                        <i class="fa-solid fa-inbox text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-600 text-lg">No borrowing requests found.</p>
                        <p class="text-gray-500 text-sm mt-2">Borrowing requests will appear here when students or faculty submit them.</p>
                        <?php if ($filter_status !== 'all'): ?>
                        <p class="text-xs text-gray-400 mt-4">Current filter: <span class="font-semibold"><?php echo htmlspecialchars($filter_status); ?></span></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- <?php echo count($requests); ?> requests found -->
                <?php endif; ?>
                
                <?php if (!empty($requests)): ?>
                    <div class="overflow-x-auto">
                        <table id="borrowingTable" class="display stripe hover w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left px-4 py-3">Request Date</th>
                                    <th class="text-left px-4 py-3">Borrower</th>
                                    <th class="text-left px-4 py-3">Asset Tag</th>
                                    <th class="text-left px-4 py-3">Asset Name</th>
                                    <th class="text-left px-4 py-3">Borrow Date</th>
                                    <th class="text-left px-4 py-3">Return Date</th>
                                    <th class="text-left px-4 py-3">Status</th>
                                    <th class="text-center px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3"><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                    <td class="px-4 py-3">
                                        <strong><?php echo htmlspecialchars($request['borrower_full_name']); ?></strong>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">
                                            <?php echo htmlspecialchars($request['asset_tag']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3"><strong><?php echo htmlspecialchars($request['asset_name']); ?></strong></td>
                                    <td class="px-4 py-3"><?php echo date('M d, Y', strtotime($request['borrowed_date'])); ?></td>
                                    <td class="px-4 py-3">
                                        <?php 
                                        if ($request['actual_return_date']) {
                                            echo '<span class="text-green-600 font-medium">' . date('M d, Y', strtotime($request['actual_return_date'])) . '</span>';
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
                                        <div class="flex justify-center gap-2">
                                            <button onclick="viewRequestDetails(<?php echo $request['id']; ?>)" 
                                                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition-colors"
                                                    title="View Details">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <?php if ($request['status'] === 'Pending'): ?>
                                            <button onclick="approveRequest(<?php echo $request['id']; ?>)" 
                                                    class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs transition-colors"
                                                    title="Approve">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <button onclick="cancelRequest(<?php echo $request['id']; ?>)" 
                                                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs transition-colors"
                                                    title="Cancel">
                                                <i class="fa-solid fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($request['status'] === 'Approved'): ?>
                                            <button onclick="returnAsset(<?php echo $request['id']; ?>)" 
                                                    class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-xs transition-colors"
                                                    title="Mark as Returned">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>

<!-- View Request Details Modal -->
<div id="viewDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl">
            <div class="bg-blue-600 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                <h3 class="text-xl font-bold">
                    <i class="fa-solid fa-file-lines mr-2"></i>
                    Request Details
                </h3>
                <button onclick="closeViewModal()" class="text-white hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>
            </div>
            <div class="p-6" id="viewDetailsContent">
                <div class="text-center py-8">
                    <i class="fa-solid fa-spinner fa-spin text-4xl text-blue-600"></i>
                    <p class="mt-2 text-gray-600">Loading details...</p>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-between border-t">
                <button onclick="printBorrowingDocument()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fa-solid fa-print mr-2"></i>Print Document
                </button>
                <button onclick="closeViewModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Return Asset Modal -->
<div id="returnModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="bg-purple-600 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                <h3 class="text-xl font-bold">
                    <i class="fa-solid fa-rotate-left mr-2"></i>
                    Return Asset
                </h3>
                <button onclick="closeReturnModal()" class="text-white hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>
            </div>
            <form id="returnForm" method="POST" action="../../controller/return_asset.php">
                <div class="p-6">
                    <input type="hidden" name="borrowing_id" id="return_borrowing_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Returned Condition <span class="text-red-500">*</span>
                        </label>
                        <select name="returned_condition" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">Select condition...</option>
                            <option value="Excellent">Excellent - Like new</option>
                            <option value="Good">Good - Minor wear</option>
                            <option value="Fair">Fair - Visible wear</option>
                            <option value="Poor">Poor - Significant damage</option>
                            <option value="Damaged">Damaged - Needs repair</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Return Notes (Optional)
                        </label>
                        <textarea name="return_notes" rows="4" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                  placeholder="Add any notes about the returned asset..."></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end gap-3 border-t">
                    <button type="button" onclick="closeReturnModal()" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fa-solid fa-check mr-2"></i>Confirm Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store current request for printing (global scope)
let currentRequest = null;

// Initialize DataTable
$(document).ready(function() {
    <?php if (!empty($requests)): ?>
    $('#borrowingTable').DataTable({
        pageLength: 10,
        order: [[0, 'desc']],
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
            { orderable: false, targets: 7 }
        ],
        dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rtip',
        drawCallback: function() {
            $('.dataTables_paginate .paginate_button').addClass('px-3 py-1 mx-1 border border-gray-300 rounded hover:bg-blue-600 hover:text-white transition-colors');
            $('.dataTables_paginate .paginate_button.current').addClass('bg-blue-600 text-white').removeClass('hover:bg-blue-600');
            $('.dataTables_paginate .paginate_button.disabled').addClass('opacity-50 cursor-not-allowed').removeClass('hover:bg-blue-600 hover:text-white');
        }
    });
    <?php endif; ?>
});

// View Request Details
async function viewRequestDetails(requestId) {
    document.getElementById('viewDetailsModal').classList.remove('hidden');
    
    try {
        const response = await fetch(`../../controller/get_borrowing_details.php?id=${requestId}`);
        
        // Check if response is OK
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Response error:', response.status, errorText);
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }
        
        const data = await response.json();
        console.log('Request data:', data);
        
        if (data.success) {
            displayRequestDetails(data.request);
        } else {
            document.getElementById('viewDetailsContent').innerHTML = `
                <div class="text-center py-8 text-red-600">
                    <i class="fa-solid fa-exclamation-triangle text-4xl mb-2"></i>
                    <p>Error loading request details.</p>
                    <p class="text-sm mt-2">${data.error || 'Unknown error'}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Fetch error:', error);
        document.getElementById('viewDetailsContent').innerHTML = `
            <div class="text-center py-8 text-red-600">
                <i class="fa-solid fa-exclamation-triangle text-4xl mb-2"></i>
                <p>Error loading request details.</p>
                <p class="text-sm mt-2">${error.message}</p>
            </div>
        `;
    }
}

function displayRequestDetails(request) {
    // Store request data globally for printing
    currentRequest = request;
    
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
                <h5 class="font-semibold text-gray-800 mb-3">Borrower Information</h5>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Name:</p>
                        <p class="font-semibold">${request.borrower_full_name}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Email:</p>
                        <p class="font-semibold">${request.borrower_email || 'N/A'}</p>
                    </div>
                </div>
            </div>
            
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-3">Asset Details</h5>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Asset Tag:</p>
                        <p class="font-semibold font-mono">${request.asset_tag}</p>
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
                <h5 class="font-semibold text-gray-800 mb-3">Borrowing Timeline</h5>
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
                        <p class="font-semibold text-green-600">${new Date(request.actual_return_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
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
                <h5 class="font-semibold text-gray-800 mb-2">Return Information:</h5>
                ${request.returned_condition ? `
                <div class="mb-2">
                    <span class="text-sm text-gray-600">Condition: </span>
                    <span class="font-semibold">${request.returned_condition}</span>
                </div>
                ` : ''}
                <p class="text-gray-700 bg-gray-50 p-3 rounded">${request.return_notes}</p>
            </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('viewDetailsContent').innerHTML = content;
}

function closeViewModal() {
    document.getElementById('viewDetailsModal').classList.add('hidden');
}

// Approve Request
async function approveRequest(requestId) {
    const confirmed = await showConfirmModal({
        title: 'Approve Borrowing Request',
        message: 'Are you sure you want to approve this borrowing request?',
        confirmText: 'Approve',
        cancelText: 'Cancel',
        confirmColor: 'bg-green-600 hover:bg-green-700',
        type: 'success'
    });
    
    if (!confirmed) return;
    
    try {
        const formData = new FormData();
        formData.append('borrowing_id', requestId);
        
        const response = await fetch('../../controller/approve_borrowing.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.reload();
        } else {
            showNotification(data.error || 'Failed to approve request', 'error');
        }
    } catch (error) {
        showNotification('Error approving request. Please try again.', 'error');
    }
}

// Cancel Request
async function cancelRequest(requestId) {
    const confirmed = await showConfirmModal({
        title: 'Cancel Borrowing Request',
        message: 'Are you sure you want to cancel this borrowing request?',
        confirmText: 'Cancel Request',
        cancelText: 'Go Back',
        confirmColor: 'bg-red-600 hover:bg-red-700',
        type: 'danger'
    });
    
    if (!confirmed) return;
    
    try {
        const formData = new FormData();
        formData.append('borrowing_id', requestId);
        
        const response = await fetch('../../controller/cancel_borrowing.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.reload();
        } else {
            showNotification(data.error || 'Failed to cancel request', 'error');
        }
    } catch (error) {
        showNotification('Error cancelling request. Please try again.', 'error');
    }
}

// Return Asset
function returnAsset(requestId) {
    document.getElementById('return_borrowing_id').value = requestId;
    document.getElementById('returnModal').classList.remove('hidden');
}

function closeReturnModal() {
    document.getElementById('returnModal').classList.add('hidden');
    document.getElementById('returnForm').reset();
}

// Print Borrowing Document
async function printBorrowingDocument() {
    if (!currentRequest) {
        showNotification('No request data available', 'error');
        return;
    }
    
    try {
        // Fetch signatures
        const response = await fetch(`../../controller/get_signatures_for_print.php?borrower_id=${currentRequest.borrower_id}&lab_staff_id=<?php echo $_SESSION['user_id']; ?>`);
        const data = await response.json();
        
        if (!data.success) {
            showNotification(data.error || 'Unknown error', 'error');
            return;
        }
        
        // Generate printable document
        const printWindow = window.open('', '_blank');
        const printContent = generatePrintableDocument(currentRequest, data.borrower_signature, data.lab_staff_signature);
        
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        
        setTimeout(() => {
            printWindow.print();
        }, 250);
    } catch (error) {
        console.error('Print error:', error);
        showNotification('Error printing document. Please try again.', 'error');
    }
}

function generatePrintableDocument(request, borrowerSig, labStaffSig) {
    const today = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const refNo = 'BRW-' + request.id.toString().padStart(6, '0');
    
    return `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Equipment Borrowing Agreement - ${refNo}</title>
    <style>
        @media print {
            body { margin: 0; }
            @page { margin: 0.5in; }
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 8.5in;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            font-weight: bold;
        }
        .header p {
            margin: 3px 0;
            font-size: 12px;
            color: #666;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            padding: 5px;
            background-color: #f0f0f0;
            border-left: 4px solid #333;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-item {
            font-size: 12px;
        }
        .info-label {
            color: #666;
            font-size: 11px;
            margin-bottom: 2px;
        }
        .info-value {
            font-weight: bold;
        }
        .declaration-box {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .declaration-box h3 {
            margin-top: 0;
            font-size: 13px;
        }
        .declaration-box p {
            font-size: 11px;
            line-height: 1.5;
            margin: 0;
        }
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        .signature-box {
            text-align: center;
        }
        .signature-box .label {
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
        }
        .signature-box .sig-area {
            border: 2px dashed #ccc;
            background-color: #fafafa;
            padding: 15px;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .signature-box .sig-area img {
            max-height: 70px;
            max-width: 100%;
        }
        .signature-box .sig-area .no-sig {
            color: #999;
            font-size: 11px;
        }
        .signature-box .name-line {
            border-top: 2px solid #333;
            padding-top: 5px;
            font-weight: bold;
            font-size: 12px;
        }
        .signature-box .role-text {
            font-size: 10px;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>QUEZON CITY UNIVERSITY</h1>
        <p>Asset Management System</p>
        <h2>EQUIPMENT BORROWING AGREEMENT</h2>
    </div>
    
    <div class="info-grid" style="margin-bottom: 25px;">
        <div class="info-item">
            <div class="info-label">Date:</div>
            <div class="info-value">${today}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Reference No:</div>
            <div class="info-value">${refNo}</div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">BORROWER INFORMATION</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Name:</div>
                <div class="info-value">${request.borrower_full_name}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Email:</div>
                <div class="info-value">${request.borrower_email || 'N/A'}</div>
            </div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">EQUIPMENT DETAILS</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Asset Tag:</div>
                <div class="info-value">${request.asset_tag}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Asset Name:</div>
                <div class="info-value">${request.asset_name}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Type:</div>
                <div class="info-value">${request.asset_type}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Brand/Model:</div>
                <div class="info-value">${request.brand || 'N/A'} ${request.model || ''}</div>
            </div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">BORROWING DETAILS</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Borrow Date:</div>
                <div class="info-value">${new Date(request.borrowed_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Expected Return Date:</div>
                <div class="info-value">${new Date(request.expected_return_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
            </div>
        </div>
        <div class="info-item" style="margin-top: 10px;">
            <div class="info-label">Purpose:</div>
            <div class="info-value">${request.purpose || 'N/A'}</div>
        </div>
    </div>
    
    <div class="declaration-box">
        <h3>BORROWER'S DECLARATION</h3>
        <p>
            I hereby acknowledge that I have received the above-mentioned equipment in good working condition. 
            I agree to take full responsibility for the equipment and to return it on or before the expected return date 
            in the same condition as received. I have read, understood, and agree to comply with all terms and conditions 
            of the Equipment Borrowing Agreement.
        </p>
    </div>
    
    <div class="signature-section">
        <div class="signature-box">
            <div class="label">Borrower's E-Signature:</div>
            <div class="sig-area">
                ${borrowerSig ? `<img src="../../uploads/signatures/${borrowerSig}" alt="Borrower Signature">` : '<div class="no-sig">No signature available</div>'}
            </div>
            <div class="name-line">${request.borrower_full_name}</div>
            <div class="role-text">Borrower's Name</div>
        </div>
        
        <div class="signature-box">
            <div class="label">Released By (Laboratory Staff):</div>
            <div class="sig-area">
                ${labStaffSig ? `<img src="../../uploads/signatures/${labStaffSig}" alt="Lab Staff Signature">` : '<div class="no-sig">No signature available</div>'}
            </div>
            <div class="name-line"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            <div class="role-text">Laboratory Staff Signature</div>
        </div>
    </div>
    
    <div class="footer">
        <p>This is an official document from Quezon City University Asset Management System</p>
        <p>Printed on: ${new Date().toLocaleString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>
    </div>
</body>
</html>
    `;
}
</script>
    
<?php include '../components/layout_footer.php'; ?>
