<?php
session_start();

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    header("Location: ../login.php");
    exit();
}

// Try different config paths
if (file_exists(__DIR__ . '/../../config/config.php')) {
    require_once __DIR__ . '/../../config/config.php';
} elseif (file_exists(__DIR__ . '/../../config/database.php')) {
    require_once __DIR__ . '/../../config/database.php';
}

// Check if $conn exists, if not create connection manually
if (!isset($conn)) {
    $conn = new mysqli('localhost', 'root', '', 'ams_database');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $ticketId = intval($_POST['ticket_id']);
    $newStatus = $_POST['status'];
    
    if (in_array($newStatus, ['Open', 'In Progress', 'Resolved', 'Closed'])) {
        $updateStmt = $conn->prepare("UPDATE hardware_issues SET status = ? WHERE id = ?");
        $updateStmt->bind_param('si', $newStatus, $ticketId);
        $updateStmt->execute();
        $updateStmt->close();
        
        $_SESSION['success_message'] = 'Ticket status updated successfully!';
        echo "<script>window.location.href = 'tickets.php';</script>";
        exit;
    }
}

// Handle assign technician
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_technician'])) {
    $ticketId = intval($_POST['ticket_id']);
    $technicianName = trim($_POST['technician_name']);
    
    if (!empty($technicianName)) {
        $assignStmt = $conn->prepare("UPDATE hardware_issues SET assigned_technician = ? WHERE id = ?");
        $assignStmt->bind_param('si', $technicianName, $ticketId);
        $assignStmt->execute();
        $assignStmt->close();
        
        $_SESSION['success_message'] = 'Technician assigned successfully!';
        echo "<script>window.location.href = 'tickets.php';</script>";
        exit;
    }
}

// Fetch all technicians from users table
$techniciansQuery = "SELECT full_name FROM users WHERE role = 'Technician' ORDER BY full_name";
$techniciansResult = $conn->query($techniciansQuery);
$technicians = [];
if ($techniciansResult && $techniciansResult->num_rows > 0) {
    while ($tech = $techniciansResult->fetch_assoc()) {
        $technicians[] = $tech['full_name'];
    }
}

// Get filter from URL parameter
$filterType = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build query based on filter
if ($filterType === 'all') {
    $query = "SELECT * FROM hardware_issues ORDER BY 
              CASE priority 
                WHEN 'High' THEN 1 
                WHEN 'Medium' THEN 2 
                WHEN 'Low' THEN 3 
              END, 
              created_at DESC";
} else {
    $query = "SELECT * FROM hardware_issues WHERE issue_type = ? ORDER BY 
              CASE priority 
                WHEN 'High' THEN 1 
                WHEN 'Medium' THEN 2 
                WHEN 'Low' THEN 3 
              END, 
              created_at DESC";
}

// Execute query
if ($filterType === 'all') {
    $result = $conn->query($query);
} else {
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $filterType);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Count tickets by type
$countQuery = "SELECT 
                issue_type,
                COUNT(*) as count
              FROM hardware_issues 
              GROUP BY issue_type";
$countResult = $conn->query($countQuery);
$counts = ['Hardware' => 0, 'Software' => 0, 'Network' => 0, 'all' => 0];

while ($row = $countResult->fetch_assoc()) {
    $counts[$row['issue_type']] = $row['count'];
    $counts['all'] += $row['count'];
}

// Get success message
$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']);

include '../components/layout_header.php';
?>

        <!-- Main Content -->
        <main class="p-6">
            <?php if ($successMessage): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <!-- Header with Filters -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Issue Tickets</h2>
                    
                    <!-- Filter Tabs -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        <a href="tickets.php?type=all" 
                           class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $filterType === 'all' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            All Issues
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full <?php echo $filterType === 'all' ? 'bg-gray-700' : 'bg-gray-300'; ?>">
                                <?php echo $counts['all']; ?>
                            </span>
                        </a>
                        
                        <a href="tickets.php?type=Hardware" 
                           class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $filterType === 'Hardware' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-800 hover:bg-blue-200'; ?>">
                            Hardware
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full <?php echo $filterType === 'Hardware' ? 'bg-blue-500' : 'bg-blue-200'; ?>">
                                <?php echo $counts['Hardware']; ?>
                            </span>
                        </a>
                        
                        <a href="tickets.php?type=Software" 
                           class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $filterType === 'Software' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-800 hover:bg-green-200'; ?>">
                            Software
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full <?php echo $filterType === 'Software' ? 'bg-green-500' : 'bg-green-200'; ?>">
                                <?php echo $counts['Software']; ?>
                            </span>
                        </a>
                        
                        <a href="tickets.php?type=Network" 
                           class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $filterType === 'Network' ? 'bg-purple-600 text-white' : 'bg-purple-100 text-purple-800 hover:bg-purple-200'; ?>">
                            Network
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full <?php echo $filterType === 'Network' ? 'bg-purple-500' : 'bg-purple-200'; ?>">
                                <?php echo $counts['Network']; ?>
                            </span>
                        </a>
                    </div>

                    <!-- Active Filter Display -->
                    <div class="text-sm text-gray-600">
                        Showing: <span class="font-semibold text-gray-800">
                            <?php 
                            if ($filterType === 'all') {
                                echo 'All Issues';
                            } else {
                                echo $filterType . ' Issues';
                            }
                            ?>
                        </span>
                        <span class="mx-2">•</span>
                        Total: <span class="font-semibold text-gray-800"><?php echo $result ? $result->num_rows : 0; ?></span> ticket(s)
                    </div>
                </div>

                <?php if ($result && $result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Technician</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terminal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($ticket = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?php echo htmlspecialchars($ticket['id']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $typeColors = [
                                        'Hardware' => 'bg-blue-100 text-blue-800',
                                        'Software' => 'bg-green-100 text-green-800',
                                        'Network' => 'bg-purple-100 text-purple-800'
                                    ];
                                    $issueType = $ticket['issue_type'] ?? 'Hardware';
                                    $typeClass = $typeColors[$issueType] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $typeClass; ?>">
                                        <?php echo htmlspecialchars($issueType); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php 
                                    $technician = $ticket['assigned_technician'] ?? null;
                                    if ($technician) {
                                        echo '<span class="text-green-700 font-medium">' . htmlspecialchars($technician) . '</span>';
                                    } else {
                                        echo '<span class="text-gray-400 italic">Not assigned</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($ticket['room']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($ticket['terminal']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars($ticket['title']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $priorityColors = [
                                        'Low' => 'bg-gray-100 text-gray-800',
                                        'Medium' => 'bg-yellow-100 text-yellow-800',
                                        'High' => 'bg-red-100 text-red-800'
                                    ];
                                    $priorityClass = $priorityColors[$ticket['priority']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $priorityClass; ?>">
                                        <?php echo htmlspecialchars($ticket['priority']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'Open' => 'bg-blue-100 text-blue-800',
                                        'In Progress' => 'bg-purple-100 text-purple-800',
                                        'Resolved' => 'bg-green-100 text-green-800',
                                        'Closed' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $statusClass = $statusColors[$ticket['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($ticket['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($ticket['submitted_by'] ?? $ticket['requester_name'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewTicket(<?php echo $ticket['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">View</button>
                                    <button onclick="assignTechnician(<?php echo $ticket['id']; ?>, '<?php echo htmlspecialchars($ticket['assigned_technician'] ?? '', ENT_QUOTES); ?>')" class="text-purple-600 hover:text-purple-900 mr-3">Assign</button>
                                    <button onclick="updateStatus(<?php echo $ticket['id']; ?>, '<?php echo $ticket['status']; ?>')" class="text-green-600 hover:text-green-900">Update</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No tickets found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        <?php 
                        if ($filterType === 'all') {
                            echo 'No issues have been submitted yet.';
                        } else {
                            echo 'No ' . htmlspecialchars($filterType) . ' issues found.';
                        }
                        ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </main>

<!-- View Ticket Modal -->
<div id="viewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black opacity-50" onclick="closeViewModal()"></div>
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl z-10 p-6 mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Ticket Details</h3>
            <button onclick="closeViewModal()" class="text-gray-600 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <div id="ticketDetails" class="space-y-4">
            <!-- Content loaded dynamically -->
        </div>
        <div class="flex justify-end mt-6">
            <button onclick="closeViewModal()" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">Close</button>
        </div>
    </div>
</div>

<!-- Assign Technician Modal -->
<div id="assignModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black opacity-50" onclick="closeAssignModal()"></div>
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md z-10 p-6 mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Assign Technician</h3>
            <button onclick="closeAssignModal()" class="text-gray-600 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="ticket_id" id="assignTicketId">
            <input type="hidden" name="assign_technician" value="1">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Technician:</label>
                <select name="technician_name" id="technicianName" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                    <option value="">-- Select a Technician --</option>
                    <?php foreach ($technicians as $tech): ?>
                        <option value="<?php echo htmlspecialchars($tech); ?>"><?php echo htmlspecialchars($tech); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeAssignModal()" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">Cancel</button>
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded">Assign</button>
            </div>
        </form>
    </div>
</div>

<!-- Update Status Modal -->
<div id="statusModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black opacity-50" onclick="closeStatusModal()"></div>
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md z-10 p-6 mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Update Ticket Status</h3>
            <button onclick="closeStatusModal()" class="text-gray-600 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="ticket_id" id="statusTicketId">
            <input type="hidden" name="update_status" value="1">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Status:</label>
                <select name="status" id="statusSelect" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="Open">Open</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                    <option value="Closed">Closed</option>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeStatusModal()" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">Cancel</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Update Status</button>
            </div>
        </form>
    </div>
</div>

<script>
function viewTicket(ticketId) {
    fetch('../../controller/get_ticket.php?id=' + ticketId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ticket = data.ticket;
                const issueType = ticket.issue_type || 'Hardware';
                document.getElementById('ticketDetails').innerHTML = `
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Ticket ID</p>
                            <p class="text-base text-gray-900">#${ticket.id}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Issue Type</p>
                            <p class="text-base text-gray-900">${issueType}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Assigned Technician</p>
                            <p class="text-base text-gray-900">${ticket.assigned_technician || 'Not assigned'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Submitted By</p>
                            <p class="text-base text-gray-900">${ticket.submitted_by || ticket.requester_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Room</p>
                            <p class="text-base text-gray-900">${ticket.room}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Terminal</p>
                            <p class="text-base text-gray-900">${ticket.terminal}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Priority</p>
                            <p class="text-base text-gray-900">${ticket.priority}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Status</p>
                            <p class="text-base text-gray-900">${ticket.status}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Created At</p>
                            <p class="text-base text-gray-900">${new Date(ticket.created_at).toLocaleString()}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Last Updated</p>
                            <p class="text-base text-gray-900">${new Date(ticket.updated_at).toLocaleString()}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm font-medium text-gray-500">Issue Title</p>
                        <p class="text-base text-gray-900">${ticket.title}</p>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm font-medium text-gray-500">Description</p>
                        <p class="text-base text-gray-900 whitespace-pre-wrap">${ticket.description || 'No description provided'}</p>
                    </div>
                `;
                document.getElementById('viewModal').classList.remove('hidden');
            }
        })
        .catch(error => console.error('Error:', error));
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

function assignTechnician(ticketId, currentTechnician) {
    document.getElementById('assignTicketId').value = ticketId;
    const techSelect = document.getElementById('technicianName');
    if (currentTechnician) {
        techSelect.value = currentTechnician;
    } else {
        techSelect.value = '';
    }
    document.getElementById('assignModal').classList.remove('hidden');
    setTimeout(() => techSelect.focus(), 100);
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
}

function updateStatus(ticketId, currentStatus) {
    document.getElementById('statusTicketId').value = ticketId;
    document.getElementById('statusSelect').value = currentStatus;
    document.getElementById('statusModal').classList.remove('hidden');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
}
</script>

<?php include '../components/layout_footer.php'; ?>
