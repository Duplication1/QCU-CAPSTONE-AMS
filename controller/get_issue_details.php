<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../config/config.php';
require_once '../model/Database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Issue ID is required']);
    exit();
}

$issue_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get issue details - verify the issue belongs to the user or user is staff/technician
    $stmt = $conn->prepare("
        SELECT 
            i.*,
            i.assigned_technician as assigned_to_name,
            pc.terminal_number,
            r.name as room
        FROM issues i
        LEFT JOIN pc_units pc ON i.pc_id = pc.id
        LEFT JOIN rooms r ON i.room_id = r.id
        WHERE i.id = ? 
        AND (i.user_id = ? OR ? IN (SELECT id FROM users WHERE id = ? AND role IN ('LaboratoryStaff', 'Technician', 'Administrator')))
    ");
    $stmt->execute([$issue_id, $user_id, $user_id, $user_id]);
    $issue = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($issue) {
        echo json_encode([
            'success' => true,
            'issue' => $issue
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Issue not found or access denied'
        ]);
    }
} catch (PDOException $e) {
    error_log("Error fetching issue details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>
