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
        $updateStmt = $conn->prepare("UPDATE issues SET status = ? WHERE id = ?");
        $updateStmt->bind_param('si', $newStatus, $ticketId);
        $updateStmt->execute();
        $updateStmt->close();

        $_SESSION['success_message'] = 'Ticket status updated successfully!';
        header("Location: tickets.php");
        exit;
    }
}

// Handle assign technician (stored in assigned_technician column)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_technician'])) {
    $ticketId = intval($_POST['ticket_id']);
    $technicianName = trim($_POST['technician_name']);

    if (!empty($technicianName)) {
        // Get ticket details for notification
        $ticketStmt = $conn->prepare("SELECT user_id, title FROM issues WHERE id = ?");
        $ticketStmt->bind_param('i', $ticketId);
        $ticketStmt->execute();
        $ticketResult = $ticketStmt->get_result();
        $ticketData = $ticketResult->fetch_assoc();
        $ticketStmt->close();

        $assignStmt = $conn->prepare("UPDATE issues SET assigned_technician = ? WHERE id = ?");
        $assignStmt->bind_param('si', $technicianName, $ticketId);
        $assignStmt->execute();
        $affected = $assignStmt->affected_rows;
        $assignStmt->close();

        // Create notification for the student
        if ($affected > 0 && $ticketData) {
            try {
                // Create notifications table if it doesn't exist
                $conn->query("CREATE TABLE IF NOT EXISTS notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
                    related_type ENUM('issue', 'borrowing', 'asset', 'system') DEFAULT 'issue',
                    related_id INT NULL,
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_is_read (is_read)
                )");

                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_type, related_id) VALUES (?, ?, ?, 'info', 'issue', ?)");
                $notifTitle = "Ticket #{$ticketId} Assigned";
                $notifMessage = "Your ticket has been assigned to {$technicianName}. They will be working on your issue soon.";
                $notifStmt->bind_param('issi', $ticketData['user_id'], $notifTitle, $notifMessage, $ticketId);
                $notifStmt->execute();
                $notifStmt->close();
            } catch (Exception $notifError) {
                error_log('Failed to create notification: ' . $notifError->getMessage());
            }
        }

        $_SESSION['success_message'] = 'Successfully Technician Assigned!';
        header("Location: tickets.php");
        exit;
    }
}

// Fetch all technicians from users table with ID and name
$techniciansQuery = "SELECT id, full_name FROM users WHERE role = 'Technician' ORDER BY full_name";
$techniciansResult = $conn->query($techniciansQuery);
$technicians = [];
if ($techniciansResult && $techniciansResult->num_rows > 0) {
    while ($tech = $techniciansResult->fetch_assoc()) {
        $technicians[] = [
            'id' => $tech['id'],
            'name' => $tech['full_name']
        ];
    }
}

// Get filter from URL parameter
$filterType = isset($_GET['type']) ? $_GET['type'] : 'all';

// Normalize filter (categories stored as lowercase or mixed — compare case-insensitively)
$allowed = ['all','hardware','software','network','laboratory','other'];
$filterKey = strtolower($filterType);
if (!in_array($filterKey, $allowed)) $filterKey = 'all';

// Build query based on filter — use unified issues table and join users for reporter name
if ($filterKey === 'all') {
    $query = "SELECT i.id, i.user_id, i.category, i.room, i.terminal, i.title, i.description, 
                     i.priority, i.status, i.created_at, i.updated_at, i.assigned_technician,
                     u.full_name AS reporter_name
              FROM issues i
              LEFT JOIN users u ON u.id = i.user_id
              WHERE COALESCE(i.category,'') <> 'borrow'
              ORDER BY 
                CASE i.priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END,
                i.created_at DESC";
    $result = $conn->query($query);
} else {
    $query = "SELECT i.id, i.user_id, i.category, i.room, i.terminal, i.title, i.description, 
                     i.priority, i.status, i.created_at, i.updated_at, i.assigned_technician,
                     u.full_name AS reporter_name
              FROM issues i
              LEFT JOIN users u ON u.id = i.user_id
              WHERE LOWER(COALESCE(i.category,'')) = ?
                AND COALESCE(i.category,'') <> 'borrow'
              ORDER BY 
                CASE i.priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END,
                i.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $filterKey);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Count tickets by category (exclude borrow)
$countQuery = "SELECT LOWER(COALESCE(category,'')) AS category, COUNT(*) as count FROM issues WHERE COALESCE(category,'') <> 'borrow' GROUP BY LOWER(COALESCE(category,''))";
$countResult = $conn->query($countQuery);
$counts = ['all' => 0, 'hardware' => 0, 'software' => 0, 'network' => 0, 'laboratory' => 0, 'other' => 0];
if ($countResult) {
    while ($row = $countResult->fetch_assoc()) {
        $cat = $row['category'];
        if (!isset($counts[$cat])) $counts[$cat] = 0;
        $counts[$cat] = (int)$row['count'];
        $counts['all'] += (int)$row['count'];
    }
}

// Get success message
$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']);

include '../components/layout_header.php';
?>

        <!-- Main Content -->
        <main class="p-6">
            <!-- Alert Container for AJAX messages -->
            <div id="alertContainer"></div>
            
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
                           class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $filterKey === 'all' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            All Issues
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full <?php echo $filterKey === 'all' ? 'bg-gray-700' : 'bg-gray-300'; ?>">
                                <?php echo $counts['all']; ?>
                            </span>
                        </a>
                        
                        <a href="tickets.php?type=hardware" 
                           class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $filterKey === 'hardware' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-800 hover:bg-blue-200'; ?>">
                            Hardware
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full <?php echo $filterKey === 'hardware' ? 'bg-gray-500' : 'bg-blue-200'; ?>">
                                <?php echo $counts['hardware']; ?>
                            </span>
                        </a>
                        
                        <a href="tickets.php?type=software" 
                           class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $filterKey === 'software' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-800 hover:bg-green-200'; ?>">
                            Software
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full <?php echo $filterKey === 'software' ? 'bg-green-500' : 'bg-green-200'; ?>">
                                <?php echo $counts['software']; ?>
                            </span>
                        </a>
                        
                        <a href="tickets.php?type=network" 
                           class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $filterKey === 'network' ? 'bg-purple-600 text-white' : 'bg-purple-100 text-purple-800 hover:bg-purple-200'; ?>">
                            Network
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full <?php echo $filterKey === 'network' ? 'bg-purple-500' : 'bg-purple-200'; ?>">
                                <?php echo $counts['network']; ?>
                            </span>
                        </a>

                        <a href="tickets.php?type=laboratory" 
                           class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $filterKey === 'laboratory' ? 'bg-indigo-600 text-white' : 'bg-indigo-100 text-indigo-800 hover:bg-indigo-200'; ?>">
                            Laboratory
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full <?php echo $filterKey === 'laboratory' ? 'bg-indigo-500' : 'bg-indigo-200'; ?>">
                                <?php echo $counts['laboratory']; ?>
                            </span>
                        </a>

                        <a href="tickets.php?type=other" 
                           class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $filterKey === 'other' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Other
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full <?php echo $filterKey === 'other' ? 'bg-gray-700' : 'bg-gray-300'; ?>">
                                <?php echo $counts['other']; ?>
                            </span>
                        </a>
                    </div>

                    <!-- Active Filter Display -->
                    <div class="text-sm text-gray-600">
                        Showing: <span class="font-semibold text-gray-800">
                            <?php 
                            if ($filterKey === 'all') {
                                echo 'All Issues';
                            } else {
                                echo ucfirst($filterKey) . ' Issues';
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
                                <!-- ID column hidden for list view (still available in detail modal) -->
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Technician</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terminal</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue Title</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <!-- Reporter hidden in list; shown in modal details -->
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                             </tr>
                         </thead>
                         <tbody class="bg-white divide-y divide-gray-200">
                             <?php while ($ticket = $result->fetch_assoc()): 
                                 $ticketId = (int)$ticket['id'];
                             ?>
                             <tr class="hover:bg-gray-50" data-ticket-id="<?php echo $ticketId; ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                     <?php
                                     $typeColors = [
                                         'hardware' => 'bg-blue-100 text-blue-800',
                                         'software' => 'bg-green-100 text-green-800',
                                         'network' => 'bg-purple-100 text-purple-800',
                                         'laboratory' => 'bg-indigo-100 text-indigo-800',
                                         'other' => 'bg-gray-100 text-gray-800'
                                     ];
                                     $issueType = strtolower($ticket['category'] ?? 'hardware');
                                     $typeClass = $typeColors[$issueType] ?? 'bg-gray-100 text-gray-800';
                                     ?>
                                     <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $typeClass; ?>">
                                         <?php echo htmlspecialchars(ucfirst($issueType)); ?>
                                     </span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 assigned-cell">
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
                                    <?php echo htmlspecialchars($ticket['room'] ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($ticket['terminal'] ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars($ticket['title'] ?? '-'); ?>
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
                                        <?php echo htmlspecialchars($ticket['status'] ?? 'Open'); ?>
                                    </span>
                                </td>
                                <!-- Reporter column removed from list view -->
                                 <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                     <?php echo !empty($ticket['created_at']) ? date('M d, Y H:i', strtotime($ticket['created_at'])) : '-'; ?>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                     <button onclick="viewTicket(<?php echo $ticketId; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">View</button>
                                     <button class="assignBtn text-gray-600 hover:text-gray-900" data-ticket-id="<?php echo $ticketId; ?>" data-current-tech="<?php echo htmlspecialchars($ticket['assigned_technician'] ?? '', ENT_QUOTES); ?>">Assign</button>
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
                        if ($filterKey === 'all') {
                            echo 'No issues have been submitted yet.';
                        } else {
                            echo 'No ' . htmlspecialchars(ucfirst($filterKey)) . ' issues found.';
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
        <form id="assignForm" class="space-y-4">
            <input type="hidden" name="ticket_id" id="assignTicketId">
            <input type="hidden" name="assign_technician" value="1">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Technician:</label>
                <select name="technician_id" id="technicianId" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                    <option value="">-- Select a Technician --</option>
                    <?php foreach ($technicians as $tech): ?>
                        <option value="<?php echo htmlspecialchars($tech['id']); ?>"><?php echo htmlspecialchars($tech['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeAssignModal()" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">Cancel</button>
                <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">Assign</button>
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
// showAlert function is now in notifications.js as showNotification
// Keeping this as wrapper for backward compatibility
function showAlert(type, msg) {
    showNotification(msg, type, 5000);
}

function viewTicket(ticketId) {
    fetch('../../controller/get_ticket.php?id=' + ticketId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ticket = data.ticket;
                const issueType = ticket.category || 'Hardware';
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
                            <p class="text-base text-gray-900">${ticket.reporter_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Room</p>
                            <p class="text-base text-gray-900">${ticket.room || '-'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Terminal</p>
                            <p class="text-base text-gray-900">${ticket.terminal || '-'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Priority</p>
                            <p class="text-base text-gray-900">${ticket.priority || '-'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Status</p>
                            <p class="text-base text-gray-900">${ticket.status || '-'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Created At</p>
                            <p class="text-base text-gray-900">${ticket.created_at ? new Date(ticket.created_at).toLocaleString() : '-'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Last Updated</p>
                            <p class="text-base text-gray-900">${ticket.updated_at ? new Date(ticket.updated_at).toLocaleString() : '-'}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm font-medium text-gray-500">Issue Title</p>
                        <p class="text-base text-gray-900">${ticket.title || '-'}</p>
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
    console.log('assignTechnician called with:', ticketId, currentTechnician);
    const inputField = document.getElementById('assignTicketId');
    console.log('Input field found:', inputField);
    
    if (inputField) {
        inputField.value = ticketId;
        console.log('Ticket ID set to:', inputField.value);
    } else {
        console.error('assignTicketId input field not found!');
    }
    
    const techSelect = document.getElementById('technicianId');
    if (currentTechnician) {
        techSelect.value = currentTechnician;
    } else {
        techSelect.value = '';
    }
    document.getElementById('assignModal').classList.remove('hidden');
    setTimeout(() => techSelect.focus(), 100);
}

// Add event listeners to all assign buttons
document.addEventListener('DOMContentLoaded', function() {
    // Handle assign button clicks
    document.querySelectorAll('.assignBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            const ticketId = this.getAttribute('data-ticket-id');
            const currentTech = this.getAttribute('data-current-tech');
            console.log('Assign button clicked:', ticketId, currentTech);
            assignTechnician(ticketId, currentTech);
        });
    });

    // Handle assign form submission
    document.getElementById('assignForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Ensure ticket_id is set - check multiple times
        const ticketIdField = document.getElementById('assignTicketId');
        const ticketId = ticketIdField ? ticketIdField.value : null;
        
        console.log('Form submit - ticket ID field:', ticketIdField);
        console.log('Form submit - ticket ID value:', ticketId);
        console.log('Form submit - all form data before check:');
        const fd = new FormData(this);
        console.log(Array.from(fd.entries()));
        
        if (!ticketId || ticketId === '' || ticketId === '0') {
            console.error('FAILED: Missing ticket ID in form. Field exists:', !!ticketIdField, 'Value:', ticketId);
            showNotification('Error: Ticket ID is missing. Please try again.', 'error');
            return;
        }
        
        // Store ticketId for use in then() callback
        const submittedTicketId = ticketId;
        
        fetch('../../controller/assign_ticket.php', { method:'POST', body: fd, credentials:'same-origin' })
        .then(async r => {
            const text = await r.text();
            console.log('HTTP status', r.status, 'response text:', text);
            try { return JSON.parse(text); } 
            catch (err) { throw new Error('Invalid JSON response: ' + text); }
        })
        .then(json => {
            console.log('Parsed JSON:', json);
            if (json.success) {
                try {
                    // update UI - use submittedTicketId as fallback
                    const ticketIdForUpdate = json.ticket_id || submittedTicketId;
                    if (!ticketIdForUpdate) {
                        console.error('FAILED: Row not found for ticket ID:', ticketIdForUpdate);
                        showAlert('error', 'Failed to update UI: Ticket ID is null');
                        closeAssignModal();
                        return;
                    }
                    const row = document.querySelector('tr[data-ticket-id="'+ticketIdForUpdate+'"]');
                    if (row) {
                        const assignedCell = row.querySelector('.assigned-cell');
                        if (assignedCell) {
                            assignedCell.innerHTML = '<span class="text-green-700 font-medium">'+ (json.assigned_technician||'') +'</span>';
                        }
                    } else {
                        console.error('FAILED: Row not found for ticket ID:', ticketIdForUpdate);
                        // Row not found but assignment succeeded - just reload the page
                        console.log('Reloading page to show updated data...');
                        window.location.reload();
                        return;
                    }
                    closeAssignModal();
                    showAlert('success', json.message || 'Technician assigned successfully!');
                } catch (uiError) {
                    console.error('Error updating UI:', uiError);
                    // Assignment succeeded but UI update failed - reload page
                    closeAssignModal();
                    window.location.reload();
                }
            } else {
                showAlert('error', json.message || 'Failed to assign technician');
            }
        })
        .catch(err => {
            console.error('Fetch/parse error:', err);
            showAlert('error', 'Failed to submit. Please try again.');
        });
    });
});

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
