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
$filterBuilding = isset($_GET['building']) ? $_GET['building'] : '';
$filterRoom = isset($_GET['room']) ? $_GET['room'] : '';

// Fetch buildings for filter
$buildingsQuery = "SELECT id, name FROM buildings ORDER BY name";
$buildingsResult = $conn->query($buildingsQuery);
$buildings = [];
if ($buildingsResult && $buildingsResult->num_rows > 0) {
    while ($building = $buildingsResult->fetch_assoc()) {
        $buildings[] = $building;
    }
}

// Fetch rooms for filter (optionally filtered by building)
if (!empty($filterBuilding)) {
    $roomsQuery = "SELECT id, name FROM rooms WHERE building_id = ? ORDER BY name";
    $roomsStmt = $conn->prepare($roomsQuery);
    $roomsStmt->bind_param('i', $filterBuilding);
    $roomsStmt->execute();
    $roomsResult = $roomsStmt->get_result();
} else {
    $roomsQuery = "SELECT id, name FROM rooms ORDER BY name";
    $roomsResult = $conn->query($roomsQuery);
}
$rooms = [];
if ($roomsResult && $roomsResult->num_rows > 0) {
    while ($room = $roomsResult->fetch_assoc()) {
        $rooms[] = $room;
    }
}

// Fetch all rooms for JavaScript
$allRoomsQuery = "SELECT id, name, building_id FROM rooms ORDER BY name";
$allRoomsResult = $conn->query($allRoomsQuery);
$allRooms = [];
if ($allRoomsResult && $allRoomsResult->num_rows > 0) {
    while ($room = $allRoomsResult->fetch_assoc()) {
        $allRooms[] = $room;
    }
}

// Normalize filter (categories stored as lowercase or mixed — compare case-insensitively)
$allowed = ['all','hardware','software','network','laboratory','other'];
$filterKey = strtolower($filterType);
if (!in_array($filterKey, $allowed)) $filterKey = 'all';

// Pagination setup
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query based on filter — use unified issues table and join users for reporter name
$whereConditions = ["COALESCE(i.category,'') <> 'borrow'"];
$params = [];
$types = '';

if ($filterKey !== 'all') {
    $whereConditions[] = "LOWER(COALESCE(i.category,'')) = ?";
    $params[] = $filterKey;
    $types .= 's';
}

if (!empty($filterBuilding)) {
    $whereConditions[] = "i.room COLLATE utf8mb4_unicode_ci IN (SELECT name COLLATE utf8mb4_unicode_ci FROM rooms WHERE building_id = ?)";
    $params[] = $filterBuilding;
    $types .= 'i';
}

if (!empty($filterRoom)) {
    $whereConditions[] = "i.room COLLATE utf8mb4_unicode_ci = (SELECT name COLLATE utf8mb4_unicode_ci FROM rooms WHERE id = ?)";
    $params[] = $filterRoom;
    $types .= 'i';
}

$whereClause = implode(' AND ', $whereConditions);

// Count total tickets for pagination
$whereConditions = ["COALESCE(i.category,'') <> 'borrow'"];
$params = [];
$types = '';

if ($filterKey !== 'all') {
    $whereConditions[] = "LOWER(COALESCE(i.category,'')) = ?";
    $params[] = $filterKey;
    $types .= 's';
}

if (!empty($filterBuilding)) {
    $whereConditions[] = "i.building_id = ?";
    $params[] = $filterBuilding;
    $types .= 'i';
}

if (!empty($filterRoom)) {
    $whereConditions[] = "i.room_id = ?";
    $params[] = $filterRoom;
    $types .= 'i';
}

$whereClause = implode(' AND ', $whereConditions);

$countQuery = "SELECT COUNT(*) as total FROM issues i WHERE {$whereClause}";
if (!empty($params)) {
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalTickets = $countResult->fetch_assoc()['total'];
    $countStmt->close();
} else {
    $countResult = $conn->query($countQuery);
    $totalTickets = $countResult->fetch_assoc()['total'];
}

$totalPages = ceil($totalTickets / $limit);

// Build main query with pagination
$whereConditions = ["COALESCE(i.category,'') <> 'borrow'"];
$params = [];
$types = '';

if ($filterKey !== 'all') {
    $whereConditions[] = "LOWER(COALESCE(i.category,'')) = ?";
    $params[] = $filterKey;
    $types .= 's';
}

if (!empty($filterBuilding)) {
    $whereConditions[] = "i.building_id = ?";
    $params[] = $filterBuilding;
    $types .= 'i';
}

if (!empty($filterRoom)) {
    $whereConditions[] = "i.room_id = ?";
    $params[] = $filterRoom;
    $types .= 'i';
}

$whereClause = implode(' AND ', $whereConditions);

$query = "SELECT i.id, i.user_id, i.category, r.name AS room, i.pc_id AS terminal, i.title, i.description, 
                 i.priority, i.status, i.created_at, i.updated_at, i.assigned_technician,
                 u.full_name AS reporter_name
          FROM issues i
          LEFT JOIN users u ON u.id = i.user_id
          LEFT JOIN rooms r ON r.id = i.room_id
          WHERE {$whereClause}
          ORDER BY 
            CASE i.priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END,
            i.created_at DESC
          LIMIT ? OFFSET ?";

// Add pagination params
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
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
        <main class="p-3">
            <!-- Alert Container for AJAX messages -->
            <div id="alertContainer"></div>
            
            <?php if ($successMessage): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-2 rounded mb-3 text-xs">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow p-3">
                <!-- Compact Filter Bar -->
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <select id="categoryFilter" onchange="applyFilters()" class="text-xs px-3 py-1.5 border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                        <option value="all" <?php echo $filterKey === 'all' ? 'selected' : ''; ?>>All (<?php echo $counts['all']; ?>)</option>
                        <option value="hardware" <?php echo $filterKey === 'hardware' ? 'selected' : ''; ?>>Hardware (<?php echo $counts['hardware']; ?>)</option>
                        <option value="software" <?php echo $filterKey === 'software' ? 'selected' : ''; ?>>Software (<?php echo $counts['software']; ?>)</option>
                        <option value="network" <?php echo $filterKey === 'network' ? 'selected' : ''; ?>>Network (<?php echo $counts['network']; ?>)</option>
                        <option value="laboratory" <?php echo $filterKey === 'laboratory' ? 'selected' : ''; ?>>Laboratory (<?php echo $counts['laboratory']; ?>)</option>
                        <option value="other" <?php echo $filterKey === 'other' ? 'selected' : ''; ?>>Other (<?php echo $counts['other']; ?>)</option>
                    </select>
                    
                    <select id="buildingFilter" onchange="onBuildingChange()" class="text-xs px-3 py-1.5 border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                        <option value="">All Buildings</option>
                        <?php foreach ($buildings as $building): ?>
                            <option value="<?php echo $building['id']; ?>" <?php echo $filterBuilding == $building['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($building['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select id="roomFilter" onchange="applyFilters()" class="text-xs px-3 py-1.5 border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                        <option value="">All Rooms</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>" <?php echo $filterRoom == $room['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($room['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button onclick="clearAllFilters()" class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded">Clear</button>
                    
                    <div class="flex-1"></div>
                    
                    <div class="text-[10px] text-gray-600">
                        <span class="font-semibold"><?php echo $result ? $result->num_rows : 0; ?></span> ticket(s)
                    </div>
                </div>

                <?php if ($result && $result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                 <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                 <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase tracking-wider">Technician</th>
                                 <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase tracking-wider">Room</th>
                                 <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase tracking-wider">Terminal</th>
                                 <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase tracking-wider">Issue Title</th>
                                 <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                 <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                 <th class="px-3 py-2 text-center text-[10px] font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                             </tr>
                         </thead>
                         <tbody class="bg-white divide-y divide-gray-200">
                             <?php while ($ticket = $result->fetch_assoc()): 
                                 $ticketId = (int)$ticket['id'];
                             ?>
                             <tr class="hover:bg-gray-50" data-ticket-id="<?php echo $ticketId; ?>">
                                <td class="px-3 py-2 whitespace-nowrap">
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
                                     <span class="px-2 py-0.5 inline-flex text-[10px] leading-tight font-semibold rounded <?php echo $typeClass; ?>">
                                         <?php echo htmlspecialchars(ucfirst($issueType)); ?>
                                     </span>
                                 </td>
                                 <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900 assigned-cell">
                                    <?php 
                                    $technician = $ticket['assigned_technician'] ?? null;
                                    if ($technician) {
                                        echo '<span class="text-green-700 font-medium">' . htmlspecialchars($technician) . '</span>';
                                    } else {
                                        echo '<span class="text-gray-400 italic">Not assigned</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">
                                    <?php echo htmlspecialchars($ticket['room'] ?? '-'); ?>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">
                                    <?php echo htmlspecialchars($ticket['terminal'] ?? '-'); ?>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-900">
                                    <?php echo htmlspecialchars($ticket['title'] ?? '-'); ?>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'Open' => 'bg-blue-100 text-blue-800',
                                        'In Progress' => 'bg-purple-100 text-purple-800',
                                        'Resolved' => 'bg-green-100 text-green-800',
                                        'Closed' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $statusClass = $statusColors[$ticket['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-0.5 inline-flex text-[10px] leading-tight font-semibold rounded <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($ticket['status'] ?? 'Open'); ?>
                                    </span>
                                </td>
                                 <td class="px-3 py-2 whitespace-nowrap text-[10px] text-gray-500">
                                     <?php echo !empty($ticket['created_at']) ? date('M d, Y', strtotime($ticket['created_at'])) : '-'; ?>
                                 </td>
                                 <td class="px-3 py-2 whitespace-nowrap text-center">
                                     <button onclick="viewTicket(<?php echo $ticketId; ?>)" class="text-[#1E3A8A] hover:text-blue-700 mr-2" title="View Details">
                                         <i class="fa-solid fa-eye"></i>
                                     </button>
                                     <button class="assignBtn text-gray-600 hover:text-[#1E3A8A]" data-ticket-id="<?php echo $ticketId; ?>" data-current-tech="<?php echo htmlspecialchars($ticket['assigned_technician'] ?? '', ENT_QUOTES); ?>" title="Assign Technician">
                                         <i class="fa-solid fa-user-plus"></i>
                                     </button>
                                 </td>
                             </tr>
                             <?php endwhile; ?>
                         </tbody>
                    </table>
                </div>
                
                <!-- Pagination Controls -->
                <?php if ($totalPages > 1): ?>
                <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($page > 1): ?>
                        <a href="?type=<?php echo $filterKey; ?>&building=<?php echo $filterBuilding; ?>&room=<?php echo $filterRoom; ?>&page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Previous
                        </a>
                        <?php else: ?>
                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-md cursor-not-allowed">
                            Previous
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="?type=<?php echo $filterKey; ?>&building=<?php echo $filterBuilding; ?>&room=<?php echo $filterRoom; ?>&page=<?php echo $page + 1; ?>" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Next
                        </a>
                        <?php else: ?>
                        <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-md cursor-not-allowed">
                            Next
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $limit, $totalTickets); ?></span> of <span class="font-medium"><?php echo $totalTickets; ?></span> tickets
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                <a href="?type=<?php echo $filterKey; ?>&building=<?php echo $filterBuilding; ?>&room=<?php echo $filterRoom; ?>&page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                                <?php else: ?>
                                <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-l-md cursor-not-allowed">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </span>
                                <?php endif; ?>
                                
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                if ($startPage > 1): ?>
                                    <a href="?type=<?php echo $filterKey; ?>&building=<?php echo $filterBuilding; ?>&room=<?php echo $filterRoom; ?>&page=1" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">1</a>
                                    <?php if ($startPage > 2): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-[#1E3A8A] border border-[#1E3A8A]"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?type=<?php echo $filterKey; ?>&building=<?php echo $filterBuilding; ?>&room=<?php echo $filterRoom; ?>&page=<?php echo $i; ?>" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300">...</span>
                                    <?php endif; ?>
                                    <a href="?type=<?php echo $filterKey; ?>&building=<?php echo $filterBuilding; ?>&room=<?php echo $filterRoom; ?>&page=<?php echo $totalPages; ?>" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50"><?php echo $totalPages; ?></a>
                                <?php endif; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <a href="?type=<?php echo $filterKey; ?>&building=<?php echo $filterBuilding; ?>&room=<?php echo $filterRoom; ?>&page=<?php echo $page + 1; ?>" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                                <?php else: ?>
                                <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-r-md cursor-not-allowed">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </span>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="fa-solid fa-inbox text-5xl text-gray-300 mb-3"></i>
                    <p class="text-xs text-gray-600 font-medium">No tickets found</p>
                    <p class="mt-1 text-[10px] text-gray-500">
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

// Cascading Building/Room Filter
const allRoomsData = <?php echo json_encode($allRooms); ?>;

function onBuildingChange() {
    const buildingSelect = document.getElementById('buildingFilter');
    const roomSelect = document.getElementById('roomFilter');
    const selectedBuilding = buildingSelect.value;
    
    // Clear room dropdown
    roomSelect.innerHTML = '<option value="">All Rooms</option>';
    
    // Filter rooms by building
    const filteredRooms = selectedBuilding 
        ? allRoomsData.filter(room => room.building_id == selectedBuilding)
        : allRoomsData;
    
    // Populate room dropdown
    filteredRooms.forEach(room => {
        const option = document.createElement('option');
        option.value = room.id;
        option.textContent = room.name;
        roomSelect.appendChild(option);
    });
    
    // Apply filters after building change
    applyFilters();
}

function applyFilters() {
    const category = document.getElementById('categoryFilter').value;
    const building = document.getElementById('buildingFilter').value;
    const room = document.getElementById('roomFilter').value;
    
    let url = 'tickets.php?type=' + category;
    if (building) url += '&building=' + building;
    if (room) url += '&room=' + room;
    
    window.location.href = url;
}

function clearAllFilters() {
    window.location.href = 'tickets.php?type=all';
}
</script>

<?php include '../components/layout_footer.php'; ?>
