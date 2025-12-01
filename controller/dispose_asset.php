<?php
/**
 * Dispose Asset Controller
 * Marks an asset as disposed and archives it
 */

session_start();
header('Content-Type: application/json');

// Check authentication and authorization
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Only Laboratory Staff can dispose assets
if ($_SESSION['role'] !== 'Laboratory Staff') {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit();
}

require_once '../config/config.php';

// Create database connection
$conn = new mysqli('localhost', 'root', '', 'ams_database');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$asset_id = isset($_POST['asset_id']) ? intval($_POST['asset_id']) : 0;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

// Validate asset ID
if ($asset_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid asset ID']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Get asset details before disposal
    $asset_query = "SELECT asset_tag, asset_name, status FROM assets WHERE id = ?";
    $stmt = $conn->prepare($asset_query);
    $stmt->bind_param('i', $asset_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Asset not found');
    }
    
    $asset = $result->fetch_assoc();
    
    // Check if already disposed
    if ($asset['status'] === 'Disposed') {
        throw new Exception('Asset is already disposed');
    }
    
    // Update asset status to Disposed and Archive
    $update_query = "UPDATE assets 
                     SET status = 'Disposed', 
                         updated_by = ?,
                         notes = CONCAT(COALESCE(notes, ''), '\n\n[DISPOSAL - ', NOW(), ']\n', ?)
                     WHERE id = ?";
    
    $disposal_note = "Asset marked for disposal by " . $_SESSION['first_name'] . " " . $_SESSION['last_name'];
    if (!empty($notes)) {
        $disposal_note .= "\nDisposal Notes: " . $notes;
    }
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('isi', $_SESSION['user_id'], $disposal_note, $asset_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update asset status');
    }
    
    // Log the disposal action
    $log_query = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent) 
                  VALUES (?, 'dispose', 'asset', ?, ?, ?, ?)";
    
    $description = "Disposed asset: " . $asset['asset_tag'] . " - " . $asset['asset_name'];
    if (!empty($notes)) {
        $description .= " | Notes: " . $notes;
    }
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param('iisss', $_SESSION['user_id'], $asset_id, $description, $ip_address, $user_agent);
    
    if (!$log_stmt->execute()) {
        throw new Exception('Failed to log disposal action');
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Asset marked for disposal successfully',
        'asset_tag' => $asset['asset_tag']
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
