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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Requests - AMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <style>
        * { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../components/layout_header.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fa-solid fa-user-check mr-2"></i>Registration Requests
                    </h1>
                    <p class="text-gray-600">Review and approve new account registrations</p>
                </div>

                <?php
                // Display messages
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">' .
                         '<i class="fa-solid fa-circle-check"></i>' .
                         htmlspecialchars($_SESSION['success_message']) . '</div>';
                    unset($_SESSION['success_message']);
                }
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">' .
                         '<i class="fa-solid fa-circle-exclamation"></i>' .
                         htmlspecialchars($_SESSION['error_message']) . '</div>';
                    unset($_SESSION['error_message']);
                }
                ?>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-yellow-600 text-sm font-medium">Pending</p>
                                <p class="text-3xl font-bold text-yellow-700"><?php echo $pending_count; ?></p>
                            </div>
                            <i class="fa-solid fa-clock text-4xl text-yellow-400"></i>
                        </div>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-600 text-sm font-medium">Approved</p>
                                <p class="text-3xl font-bold text-green-700"><?php echo $approved_count; ?></p>
                            </div>
                            <i class="fa-solid fa-check-circle text-4xl text-green-400"></i>
                        </div>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-red-600 text-sm font-medium">Rejected</p>
                                <p class="text-3xl font-bold text-red-700"><?php echo $rejected_count; ?></p>
                            </div>
                            <i class="fa-solid fa-times-circle text-4xl text-red-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Requests Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
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
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-700">
                                                <i class="fa-solid fa-clock mr-1"></i>Pending
                                            </span>
                                        <?php elseif ($request['status'] === 'Approved'): ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                                                <i class="fa-solid fa-check mr-1"></i>Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
                                                <i class="fa-solid fa-times mr-1"></i>Rejected
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm"><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php if ($request['status'] === 'Pending'): ?>
                                            <button onclick="viewRequest(<?php echo $request['id']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-800 mr-3">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <button onclick="approveRequest(<?php echo $request['id']; ?>)" 
                                                    class="text-green-600 hover:text-green-800 mr-3">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <button onclick="rejectRequest(<?php echo $request['id']; ?>)" 
                                                    class="text-red-600 hover:text-red-800">
                                                <i class="fa-solid fa-times"></i>
                                            </button>
                                        <?php else: ?>
                                            <button onclick="viewRequest(<?php echo $request['id']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-800">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
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
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Registration Details</h3>
                    <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <div id="modalContent"></div>
            </div>
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
                                    <div>
                                        <p class="text-sm text-gray-600">ID Number</p>
                                        <p class="font-semibold">${request.id_number}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Status</p>
                                        <p>${statusBadge}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Full Name</p>
                                        <p class="font-semibold">${request.full_name}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Role</p>
                                        <p class="font-semibold">${request.role}</p>
                                    </div>
                                    <div class="col-span-2">
                                        <p class="text-sm text-gray-600">Email</p>
                                        <p class="font-semibold">${request.email}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Security Question 1</p>
                                        <p class="font-semibold text-sm">${request.security_question_1}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Security Question 2</p>
                                        <p class="font-semibold text-sm">${request.security_question_2}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Requested At</p>
                                        <p class="font-semibold">${new Date(request.requested_at).toLocaleString()}</p>
                                    </div>
                                    ${request.reviewed_at ? `
                                    <div>
                                        <p class="text-sm text-gray-600">Reviewed At</p>
                                        <p class="font-semibold">${new Date(request.reviewed_at).toLocaleString()}</p>
                                    </div>
                                    ` : ''}
                                    ${request.reviewed_by_name ? `
                                    <div class="col-span-2">
                                        <p class="text-sm text-gray-600">Reviewed By</p>
                                        <p class="font-semibold">${request.reviewed_by_name}</p>
                                    </div>
                                    ` : ''}
                                    ${request.rejection_reason ? `
                                    <div class="col-span-2">
                                        <p class="text-sm text-gray-600">Rejection Reason</p>
                                        <p class="font-semibold text-red-600">${request.rejection_reason}</p>
                                    </div>
                                    ` : ''}
                                </div>
                                ${request.status === 'Pending' ? `
                                <div class="flex gap-3 mt-6">
                                    <button onclick="approveRequest(${request.id}); closeModal();" 
                                            class="flex-1 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold">
                                        <i class="fa-solid fa-check mr-2"></i>Approve
                                    </button>
                                    <button onclick="rejectRequest(${request.id}); closeModal();" 
                                            class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold">
                                        <i class="fa-solid fa-times mr-2"></i>Reject
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
