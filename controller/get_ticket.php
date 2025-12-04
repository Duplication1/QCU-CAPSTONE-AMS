<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

session_start();

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Try different config paths
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
} elseif (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
}

// Check if $conn exists, if not create connection manually
if (!isset($conn)) {
    $conn = new mysqli('localhost', 'root', '', 'ams_database');
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
}

// Get ticket ID from query parameter
$ticketId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($ticketId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
    exit();
}

// Fetch ticket details with reporter information
$query = "SELECT i.id, i.user_id, i.category, r.name AS room, p.terminal_number AS terminal, i.title, i.description, 
                 i.priority, i.status, i.created_at, i.updated_at, i.assigned_technician,
                 u.full_name AS reporter_name, u.email AS reporter_email,
                 i.component_asset_id, a.asset_name AS component_name, a.asset_tag AS component_tag
          FROM issues i
          LEFT JOIN users u ON u.id = i.user_id
          LEFT JOIN rooms r ON r.id = i.room_id
          LEFT JOIN pc_units p ON p.id = i.pc_id
          LEFT JOIN assets a ON a.id = i.component_asset_id
          WHERE i.id = ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database query preparation failed']);
    exit();
}

$stmt->bind_param('i', $ticketId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Ticket not found']);
    $stmt->close();
    $conn->close();
    exit();
}

$ticket = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Return ticket data
echo json_encode([
    'success' => true,
    'ticket' => [
        'id' => $ticket['id'],
        'user_id' => $ticket['user_id'],
        'category' => $ticket['category'],
        'room' => $ticket['room'],
        'terminal' => $ticket['terminal'],
        'title' => $ticket['title'],
        'description' => $ticket['description'],
        'priority' => $ticket['priority'],
        'status' => $ticket['status'],
        'created_at' => $ticket['created_at'],
        'updated_at' => $ticket['updated_at'],
        'assigned_technician' => $ticket['assigned_technician'],
        'reporter_name' => $ticket['reporter_name'],
        'reporter_email' => $ticket['reporter_email'],
        'component_asset_id' => $ticket['component_asset_id'],
        'component_name' => $ticket['component_name'],
        'component_tag' => $ticket['component_tag']
    ]
]);
?>
