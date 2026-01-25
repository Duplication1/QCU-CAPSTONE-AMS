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
    * { font-family: 'Poppins', sans-serif; }
    
    .glass-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }
    
    .stat-card {
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3));
        transform: translateX(-100%);
        transition: transform 0.6s ease;
    }
    
    .stat-card:hover::before {
        transform: translateX(100%);
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    }
    
    .gradient-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    #requestsTable thead tr {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    #requestsTable thead th {
        color: white !important;
        font-weight: 600;
        padding: 16px !important;
        border: none !important;
    }
    
    #requestsTable tbody tr {
        transition: all 0.2s ease;
    }
    
    #requestsTable tbody tr:hover {
        background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%) !important;
        transform: scale(1.01);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    #requestsTable tbody td {
        padding: 16px !important;
        vertical-align: middle;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .action-btn {
        transition: all 0.2s ease;
        padding: 8px 12px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .action-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .modal-backdrop {
        backdrop-filter: blur(8px);
        animation: fadeIn 0.3s ease;
    }
    
    .modal-content {
        animation: slideUp 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from { 
            opacity: 0;
            transform: translateY(20px);
        }
        to { 
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .info-card {
        background: linear-gradient(135deg, #f6f8fb 0%, #ffffff 100%);
        border-left: 4px solid #667eea;
        transition: all 0.3s ease;
    }
    
    .info-card:hover {
        border-left-width: 6px;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border: none !important;
        color: white !important;
        border-radius: 6px !important;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border: none !important;
        color: white !important;
        border-radius: 6px !important;
    }
    
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 6px 12px;
        transition: all 0.2s ease;
    }
    
    .dataTables_wrapper .dataTables_length select:focus,
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    /* Enhanced Search Styling */
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
        float: none !important;
    }
    
    .dataTables_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        color: #4b5563;
        margin: 0;
    }
    
    .dataTables_wrapper .dataTables_filter input {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border: 2px solid #e5e7eb;
        padding: 10px 16px;
        border-radius: 12px;
        font-size: 14px;
        min-width: 300px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15), 0 4px 16px rgba(102, 126, 234, 0.2);
        background: #ffffff;
        transform: translateY(-1px);
    }
    
    /* DataTable Info Text - Remove Bold */
    .dataTables_wrapper .dataTables_info {
        font-weight: normal !important;
        color: #6b7280;
        padding-top: 0 !important;
        padding-left: 0 !important;
        float: none !important;
        margin: 0 !important;
    }
    
    /* Enhanced Table Styling */
    .dataTables_wrapper .dataTables_length {
        margin-bottom: 0 !important;
        float: none !important;
    }
    
    .dataTables_wrapper .dataTables_length label {
        font-weight: 600;
        color: #4b5563;
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
    }
    
    .dataTables_wrapper .dataTables_length select {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        padding: 10px 36px 10px 14px;
        border-radius: 12px;
        font-weight: 500;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        cursor: pointer;
        border: 2px solid #e5e7eb;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .dataTables_wrapper .dataTables_length select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15), 0 4px 16px rgba(102, 126, 234, 0.2);
        transform: translateY(-1px);
        outline: none;
    }
    
    /* Modern Layout - Controls in Row */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        display: inline-block;
    }
    
    .dataTables_wrapper > div:first-child {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        padding: 20px 24px;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-bottom: 2px solid #e5e7eb;
        margin-bottom: 0;
    }
    
    /* Bottom Info and Pagination Row */
    .dataTables_wrapper > div:last-child {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        padding: 20px 24px;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-top: 2px solid #e5e7eb;
    }
    
    /* Add spacing above and below table */
    .dataTables_wrapper .dataTables_scroll {
        margin: 0 !important;
    }
    
    #requestsTable_wrapper {
        padding: 0 !important;
    }
    
    /* Pagination Enhancement */
    .dataTables_wrapper .dataTables_paginate {
        padding-top: 0 !important;
        margin: 0 !important;
        float: none !important;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 8px 14px !important;
        margin: 0 3px !important;
        border-radius: 8px !important;
        font-weight: 500 !important;
        transition: all 0.2s ease !important;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        transform: translateY(-2px);
    }
    
    /* Table Row Enhancement */
    #requestsTable tbody tr {
        transition: all 0.3s ease;
        border-bottom: 1px solid #f3f4f6;
    }
    
    #requestsTable tbody tr:hover {
        background: linear-gradient(90deg, #f8f9fa 0%, #f3f4f6 100%) !important;
        transform: translateX(4px);
        box-shadow: -4px 0 0 0 #667eea, 0 4px 12px rgba(0, 0, 0, 0.08);
    }
</style>

        <!-- Main Content -->
        <main class="flex-1 p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="glass-card rounded-2xl p-8 mb-6 shadow-xl">
                    <h1 class="text-4xl font-bold mb-2">
                        <span class="gradient-header">
                            <i class="fa-solid fa-user-check mr-3"></i>Registration Requests
                        </span>
                    </h1>
                    <p class="text-gray-600 text-lg">Review and approve new account registrations</p>
                </div>

                <?php
                // Display messages
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="glass-card bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-xl mb-6 flex items-center gap-3 shadow-lg">' .
                         '<i class="fa-solid fa-circle-check text-2xl"></i>' .
                         '<span class="font-medium">' . htmlspecialchars($_SESSION['success_message']) . '</span></div>';
                    unset($_SESSION['success_message']);
                }
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="glass-card bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-xl mb-6 flex items-center gap-3 shadow-lg">' .
                         '<i class="fa-solid fa-circle-exclamation text-2xl"></i>' .
                         '<span class="font-medium">' . htmlspecialchars($_SESSION['error_message']) . '</span></div>';
                    unset($_SESSION['error_message']);
                }
                ?>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="glass-card stat-card rounded-2xl p-6 shadow-xl border-l-4 border-yellow-400">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-yellow-600 text-sm font-semibold uppercase tracking-wide mb-2">Pending</p>
                                <p class="text-4xl font-bold bg-gradient-to-r from-yellow-500 to-orange-500 bg-clip-text text-transparent"><?php echo $pending_count; ?></p>
                            </div>
                            <div class="bg-gradient-to-br from-yellow-400 to-orange-500 p-4 rounded-2xl shadow-lg">
                                <i class="fa-solid fa-clock text-4xl text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="glass-card stat-card rounded-2xl p-6 shadow-xl border-l-4 border-green-400">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-600 text-sm font-semibold uppercase tracking-wide mb-2">Approved</p>
                                <p class="text-4xl font-bold bg-gradient-to-r from-green-500 to-emerald-500 bg-clip-text text-transparent"><?php echo $approved_count; ?></p>
                            </div>
                            <div class="bg-gradient-to-br from-green-400 to-emerald-500 p-4 rounded-2xl shadow-lg">
                                <i class="fa-solid fa-check-circle text-4xl text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="glass-card stat-card rounded-2xl p-6 shadow-xl border-l-4 border-red-400">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-red-600 text-sm font-semibold uppercase tracking-wide mb-2">Rejected</p>
                                <p class="text-4xl font-bold bg-gradient-to-r from-red-500 to-rose-500 bg-clip-text text-transparent"><?php echo $rejected_count; ?></p>
                            </div>
                            <div class="bg-gradient-to-br from-red-400 to-rose-500 p-4 rounded-2xl shadow-lg">
                                <i class="fa-solid fa-times-circle text-4xl text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Requests Table -->
                <div class="glass-card rounded-2xl shadow-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table id="requestsTable" class="w-full">
                            <thead class="bg-gray-100 border-b">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ID Number</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Full Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Email</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Role</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Requested</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
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
                                            <span class="status-badge bg-gradient-to-r from-yellow-100 to-orange-100 text-yellow-700">
                                                <i class="fa-solid fa-clock"></i>Pending
                                            </span>
                                        <?php elseif ($request['status'] === 'Approved'): ?>
                                            <span class="status-badge bg-gradient-to-r from-green-100 to-emerald-100 text-green-700">
                                                <i class="fa-solid fa-check"></i>Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge bg-gradient-to-r from-red-100 to-rose-100 text-red-700">
                                                <i class="fa-solid fa-times"></i>Rejected
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm"><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex gap-2">
                                            <?php if ($request['status'] === 'Pending'): ?>
                                                <button onclick="viewRequest(<?php echo $request['id']; ?>)" 
                                                        class="action-btn bg-blue-500 hover:bg-blue-600 text-white" title="View Details">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                                <button onclick="approveRequest(<?php echo $request['id']; ?>)" 
                                                        class="action-btn bg-green-500 hover:bg-green-600 text-white" title="Approve">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                                <button onclick="rejectRequest(<?php echo $request['id']; ?>)" 
                                                        class="action-btn bg-red-500 hover:bg-red-600 text-white" title="Reject">
                                                    <i class="fa-solid fa-times"></i>
                                                </button>
                                            <?php else: ?>
                                                <button onclick="viewRequest(<?php echo $request['id']; ?>)" 
                                                        class="action-btn bg-blue-500 hover:bg-blue-600 text-white" title="View Details">
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
    <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-60 modal-backdrop hidden items-center justify-center z-50">
        <div class="modal-content glass-card rounded-2xl shadow-2xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6 rounded-t-2xl">
                <div class="flex justify-between items-center">
                    <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-user-circle"></i>
                        Registration Details
                    </h3>
                    <button onclick="closeModal()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-2 transition-all">
                        <i class="fa-solid fa-times text-xl"></i>
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
                            <div class="space-y-5">
                                <div class="grid grid-cols-2 gap-5">
                                    <div class="info-card p-4 rounded-xl">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">ID Number</p>
                                        <p class="font-bold text-gray-800 text-lg">${request.id_number}</p>
                                    </div>
                                    <div class="info-card p-4 rounded-xl">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Status</p>
                                        <p>${statusBadge}</p>
                                    </div>
                                    <div class="info-card p-4 rounded-xl">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Full Name</p>
                                        <p class="font-bold text-gray-800 text-lg">${request.full_name}</p>
                                    </div>
                                    <div class="info-card p-4 rounded-xl">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Role</p>
                                        <p class="font-bold text-gray-800 text-lg">${request.role}</p>
                                    </div>
                                    <div class="col-span-2 info-card p-4 rounded-xl">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Email</p>
                                        <p class="font-bold text-gray-800 text-lg">${request.email}</p>
                                    </div>
                                    <div class="col-span-2 info-card p-4 rounded-xl">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Security Question 1</p>
                                        <p class="font-semibold text-gray-700">${request.security_question_1}</p>
                                    </div>
                                    <div class="col-span-2 info-card p-4 rounded-xl">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Security Question 2</p>
                                        <p class="font-semibold text-gray-700">${request.security_question_2}</p>
                                    </div>
                                    <div class="info-card p-4 rounded-xl">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Requested At</p>
                                        <p class="font-semibold text-gray-700">${new Date(request.requested_at).toLocaleString()}</p>
                                    </div>
                                    ${request.reviewed_at ? `
                                    <div class="info-card p-4 rounded-xl">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Reviewed At</p>
                                        <p class="font-semibold text-gray-700">${new Date(request.reviewed_at).toLocaleString()}</p>
                                    </div>
                                    ` : ''}
                                    ${request.reviewed_by_name ? `
                                    <div class="col-span-2 info-card p-4 rounded-xl">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Reviewed By</p>
                                        <p class="font-semibold text-gray-700">${request.reviewed_by_name}</p>
                                    </div>
                                    ` : ''}
                                    ${request.rejection_reason ? `
                                    <div class="col-span-2 bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 p-4 rounded-xl">
                                        <p class="text-xs text-red-600 uppercase tracking-wide font-semibold mb-1">Rejection Reason</p>
                                        <p class="font-semibold text-red-700">${request.rejection_reason}</p>
                                    </div>
                                    ` : ''}
                                </div>
                                ${request.status === 'Pending' ? `
                                <div class="flex gap-4 mt-6 pt-6 border-t border-gray-200">
                                    <button onclick="approveRequest(${request.id}); closeModal();" 
                                            class="flex-1 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                                        <i class="fa-solid fa-check mr-2"></i>Approve Request
                                    </button>
                                    <button onclick="rejectRequest(${request.id}); closeModal();" 
                                            class="flex-1 py-3 bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                                        <i class="fa-solid fa-times mr-2"></i>Reject Request
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
