<?php
session_start();
require_once '../../config/config.php';
require_once '../../model/Database.php';

// Check if user is logged in and is Administrator
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get all registration requests
$query = "SELECT rr.*, u.full_name as reviewed_by_name 
          FROM registration_requests rr 
          LEFT JOIN users u ON rr.reviewed_by = u.id 
          ORDER BY 
            CASE rr.status 
              WHEN 'Pending' THEN 1 
              WHEN 'Approved' THEN 2 
              WHEN 'Rejected' THEN 3 
            END,
            rr.requested_at DESC";
$stmt = $conn->query($query);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;
foreach ($requests as $request) {
    if ($request['status'] === 'Pending') $pending_count++;
    elseif ($request['status'] === 'Approved') $approved_count++;
    elseif ($request['status'] === 'Rejected') $rejected_count++;
}
?>
<?php include '../components/layout_header.php'; ?>
<style>
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .action-btn {
        padding: 6px 10px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .action-btn:hover {
        opacity: 0.9;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #1E3A8A !important;
        border: none !important;
        color: white !important;
        border-radius: 4px !important;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #1E3A8A !important;
        border: none !important;
        color: white !important;
        border-radius: 4px !important;
    }
    
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 6px 12px;
    }
    
    .dataTables_wrapper .dataTables_length select:focus,
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #1E3A8A;
        outline: none;
    }
    
    #requestsTable tbody tr:hover {
        background-color: #f9fafb !important;
    }
</style>

        <!-- Main Content -->
        <main class="flex-1 p-4 bg-gray-50">
            <div class="w-full">
                <?php
                // Display messages
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 flex items-center gap-2">' .
                         '<i class="fa-solid fa-circle-check"></i>' .
                         '<span>' . htmlspecialchars($_SESSION['success_message']) . '</span></div>';
                    unset($_SESSION['success_message']);
                }
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 flex items-center gap-2">' .
                         '<i class="fa-solid fa-circle-exclamation"></i>' .
                         '<span>' . htmlspecialchars($_SESSION['error_message']) . '</span></div>';
                    unset($_SESSION['error_message']);
                }
                ?>

                <!-- Requests Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table id="requestsTable" class="w-full">
                            <thead style="background-color: #1E3A8A;">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">ID Number</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Full Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Email</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Role</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Requested</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($requests as $request): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($request['id_number']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($request['full_name']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($request['email']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($request['role']); ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php if ($request['status'] === 'Pending'): ?>
                                            <span class="status-badge bg-yellow-100 text-yellow-700">
                                                <i class="fa-solid fa-clock"></i>Pending
                                            </span>
                                        <?php elseif ($request['status'] === 'Approved'): ?>
                                            <span class="status-badge bg-green-100 text-green-700">
                                                <i class="fa-solid fa-check"></i>Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge bg-red-100 text-red-700">
                                                <i class="fa-solid fa-times"></i>Rejected
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm"><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex gap-1">
                                            <?php if ($request['status'] === 'Pending'): ?>
                                                <button onclick="viewRequest(<?php echo $request['id']; ?>)" 
                                                        class="action-btn bg-blue-500 text-white" title="View Details">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                                <button onclick="approveRequest(<?php echo $request['id']; ?>)" 
                                                        class="action-btn bg-green-500 text-white" title="Approve">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                                <button onclick="rejectRequest(<?php echo $request['id']; ?>)" 
                                                        class="action-btn bg-red-500 text-white" title="Reject">
                                                    <i class="fa-solid fa-times"></i>
                                                </button>
                                            <?php else: ?>
                                                <button onclick="viewRequest(<?php echo $request['id']; ?>)" 
                                                        class="action-btn bg-blue-500 text-white" title="View Details">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

    <!-- View Modal -->
    <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200" style="background-color: #1E3A8A;">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-user-circle"></i>
                        Registration Details
                    </h3>
                    <button onclick="closeModal()" class="text-white hover:opacity-75 rounded p-1">
                        <i class="fa-solid fa-times text-lg"></i>
                    </button>
                </div>
            </div>
            <div class="p-6" id="modalContent"></div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#requestsTable').DataTable({
                order: [[5, 'desc']],
                pageLength: 25
            });
        });

        function viewRequest(id) {
            fetch(`../../controller/get_registration_details.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const request = data.request;
                        let statusBadge = '';
                        if (request.status === 'Pending') {
                            statusBadge = '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-700">Pending</span>';
                        } else if (request.status === 'Approved') {
                            statusBadge = '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-700">Approved</span>';
                        } else {
                            statusBadge = '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-700">Rejected</span>';
                        }

                        let content = `
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-gray-50 p-3 rounded border border-gray-200">
                                        <p class="text-xs text-gray-500 mb-1">ID Number</p>
                                        <p class="font-semibold" style="color: #1E3A8A;">${request.id_number}</p>
                                    </div>
                                    <div class="bg-gray-50 p-3 rounded border border-gray-200">
                                        <p class="text-xs text-gray-500 mb-1">Status</p>
                                        <p>${statusBadge}</p>
                                    </div>
                                    <div class="bg-gray-50 p-3 rounded border border-gray-200">
                                        <p class="text-xs text-gray-500 mb-1">Full Name</p>
                                        <p class="font-semibold" style="color: #1E3A8A;">${request.full_name}</p>
                                    </div>
                                    <div class="bg-gray-50 p-3 rounded border border-gray-200">
                                        <p class="text-xs text-gray-500 mb-1">Role</p>
                                        <p class="font-semibold" style="color: #1E3A8A;">${request.role}</p>
                                    </div>
                                    <div class="col-span-2 bg-gray-50 p-3 rounded border border-gray-200">
                                        <p class="text-xs text-gray-500 mb-1">Email</p>
                                        <p class="font-semibold" style="color: #1E3A8A;">${request.email}</p>
                                    </div>
                                    <div class="col-span-2 bg-gray-50 p-3 rounded border border-gray-200">
                                        <p class="text-xs text-gray-500 mb-1">Security Question 1</p>
                                        <p class="text-sm text-gray-700">${request.security_question_1}</p>
                                    </div>
                                    <div class="col-span-2 bg-gray-50 p-3 rounded border border-gray-200">
                                        <p class="text-xs text-gray-500 mb-1">Security Question 2</p>
                                        <p class="text-sm text-gray-700">${request.security_question_2}</p>
                                    </div>
                                    <div class="bg-gray-50 p-3 rounded border border-gray-200">
                                        <p class="text-xs text-gray-500 mb-1">Requested At</p>
                                        <p class="text-sm text-gray-700">${new Date(request.requested_at).toLocaleString()}</p>
                                    </div>
                                    ${request.reviewed_at ? `
                                    <div class="bg-gray-50 p-3 rounded border border-gray-200">
                                        <p class="text-xs text-gray-500 mb-1">Reviewed At</p>
                                        <p class="text-sm text-gray-700">${new Date(request.reviewed_at).toLocaleString()}</p>
                                    </div>
                                    ` : ''}
                                    ${request.reviewed_by_name ? `
                                    <div class="col-span-2 bg-gray-50 p-3 rounded border border-gray-200">
                                        <p class="text-xs text-gray-500 mb-1">Reviewed By</p>
                                        <p class="text-sm text-gray-700">${request.reviewed_by_name}</p>
                                    </div>
                                    ` : ''}
                                    ${request.rejection_reason ? `
                                    <div class="col-span-2 bg-red-50 border-l-4 border-red-500 p-3 rounded">
                                        <p class="text-xs text-red-600 mb-1">Rejection Reason</p>
                                        <p class="text-sm text-red-700">${request.rejection_reason}</p>
                                    </div>
                                    ` : ''}
                                </div>
                                ${request.status === 'Pending' ? `
                                <div class="flex gap-3 mt-4 pt-4 border-t border-gray-200">
                                    <button onclick="approveRequest(${request.id}); closeModal();" 
                                            class="flex-1 py-2 bg-green-500 hover:bg-green-600 text-white rounded font-semibold">
                                        <i class="fa-solid fa-check mr-1"></i>Approve Request
                                    </button>
                                    <button onclick="rejectRequest(${request.id}); closeModal();" 
                                            class="flex-1 py-2 bg-red-500 hover:bg-red-600 text-white rounded font-semibold">
                                        <i class="fa-solid fa-times mr-1"></i>Reject Request
                                    </button>
                                </div>
                                ` : ''}
                            </div>
                        `;
                        document.getElementById('modalContent').innerHTML = content;
                        document.getElementById('viewModal').classList.remove('hidden');
                        document.getElementById('viewModal').classList.add('flex');
                    }
                });
        }

        function closeModal() {
            document.getElementById('viewModal').classList.add('hidden');
            document.getElementById('viewModal').classList.remove('flex');
        }

        function approveRequest(id) {
            if (confirm('Are you sure you want to approve this registration request?')) {
                window.location.href = `../../controller/process_registration.php?action=approve&id=${id}`;
            }
        }

        function rejectRequest(id) {
            const reason = prompt('Please enter the reason for rejection:');
            if (reason !== null && reason.trim() !== '') {
                window.location.href = `../../controller/process_registration.php?action=reject&id=${id}&reason=${encodeURIComponent(reason)}`;
            }
        }
    </script>

<?php include '../components/layout_footer.php'; ?>
