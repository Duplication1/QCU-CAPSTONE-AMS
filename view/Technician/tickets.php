<?php
session_start();

// Check if user is logged in and has technician role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Technician') {
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

// Get technician's full name for filtering assigned tickets
$technicianName = $_SESSION['full_name'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $ticketId = intval($_POST['ticket_id']);
    $newStatus = $_POST['status'];
    $notes = trim($_POST['notes'] ?? '');
    
    if (in_array($newStatus, ['Open', 'In Progress', 'Resolved', 'Closed'])) {
        // Update status and add notes
        $updateStmt = $conn->prepare("UPDATE hardware_issues SET status = ?, technician_notes = CONCAT(COALESCE(technician_notes, ''), ?) WHERE id = ? AND assigned_technician = ?");
        $noteEntry = "\n" . date('Y-m-d H:i:s') . " - Status changed to {$newStatus}: {$notes}";
        $updateStmt->bind_param('ssis', $newStatus, $noteEntry, $ticketId, $technicianName);
        $updateStmt->execute();
        $updateStmt->close();
        
        $_SESSION['success_message'] = 'Ticket status updated successfully!';
        echo "<script>window.location.href = 'tickets.php';</script>";
        exit;
    }
}

// Fetch tickets assigned to this technician
$query = "SELECT * FROM hardware_issues WHERE assigned_technician = ? ORDER BY 
          CASE priority 
            WHEN 'High' THEN 1 
            WHEN 'Medium' THEN 2 
            WHEN 'Low' THEN 3 
          END, 
          created_at DESC";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Error preparing statement: " . $conn->error . "<br>Make sure you've run the SQL to add the 'assigned_technician' column.");
}

$stmt->bind_param('s', $technicianName);
$stmt->execute();
$result = $stmt->get_result();

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
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">My Assigned Tickets</h2>
                    <div class="flex gap-2">
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                            Total: <?php echo $result ? $result->num_rows : 0; ?>
                        </span>
                    </div>
                </div>

                <?php if ($result && $result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terminal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewTicket(<?php echo $ticket['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">View</button>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No assigned tickets</h3>
                    <p class="mt-1 text-sm text-gray-500">No tickets have been assigned to you yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>

<!-- View Ticket Modal -->
<div id="viewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black opacity-50" onclick="closeViewModal()"></div>
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl z-10 p-6 mx-4 max-h-[90vh] overflow-y-auto">
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
                <select name="status" id="statusSelect" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="Open">Open</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                    <option value="Closed">Closed</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes (optional):</label>
                <textarea name="notes" id="statusNotes" rows="3" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Add any notes about this status change..."></textarea>
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
                document.getElementById('ticketDetails').innerHTML = `
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Ticket ID</p>
                            <p class="text-base text-gray-900">#${ticket.id}</p>
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
                    </div>
                    <div class="mt-4">
                        <p class="text-sm font-medium text-gray-500">Issue Title</p>
                        <p class="text-base text-gray-900">${ticket.title}</p>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm font-medium text-gray-500">Description</p>
                        <p class="text-base text-gray-900 whitespace-pre-wrap">${ticket.description || 'No description provided'}</p>
                    </div>
                    ${ticket.technician_notes ? `
                    <div class="mt-4">
                        <p class="text-sm font-medium text-gray-500">Technician Notes</p>
                        <div class="text-sm text-gray-900 bg-gray-50 p-3 rounded mt-2 whitespace-pre-wrap">${ticket.technician_notes}</div>
                    </div>
                    ` : ''}
                `;
                document.getElementById('viewModal').classList.remove('hidden');
            }
        })
        .catch(error => console.error('Error:', error));
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

function updateStatus(ticketId, currentStatus) {
    document.getElementById('statusTicketId').value = ticketId;
    document.getElementById('statusSelect').value = currentStatus;
    document.getElementById('statusNotes').value = '';
    document.getElementById('statusModal').classList.remove('hidden');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
}
</script>

<?php 
$stmt->close();
include '../components/layout_footer.php'; 
?>
