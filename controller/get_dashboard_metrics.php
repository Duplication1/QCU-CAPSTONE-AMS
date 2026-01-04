<?php
/**
 * Real-time Dashboard Metrics API
 * Returns current metrics for Laboratory Staff dashboard
 */

session_start();

// Security check
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config/config.php';

header('Content-Type: application/json');

try {
    // Establish database connection
    $dbConfig = Config::database();
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    // Fetch all metrics
    $metrics = [];
    
    // Unassigned issues
    $result = $conn->query("SELECT COUNT(*) as count FROM issues WHERE (assigned_technician IS NULL OR assigned_technician = '') AND status = 'Open' AND category != 'borrow'");
    $metrics['unassignedIssues'] = $result->fetch_assoc()['count'];
    
    // In Progress issues
    $result = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'In Progress'");
    $metrics['inProgressIssues'] = $result->fetch_assoc()['count'];
    
    // Resolved issues
    $result = $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'Resolved'");
    $metrics['resolvedIssues'] = $result->fetch_assoc()['count'];
    
    // Asset status counts
    $result = $conn->query("SELECT COUNT(*) as count FROM asset_borrowing WHERE status = 'Pending'");
    $metrics['assetsBorrowed'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Available'");
    $metrics['assetsAvailable'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'In Use'");
    $metrics['assetsInUse'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` IN ('Non-Functional', 'Poor')");
    $metrics['assetsCritical'] = $result->fetch_assoc()['count'];
    
    // Attention needed assets
    $result = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Fair'");
    $metrics['needsAttention'] = $result->fetch_assoc()['count'];
    
    // Healthy assets (Good and Excellent condition)
    $result = $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` IN ('Good', 'Excellent')");
    $metrics['healthyAssets'] = $result->fetch_assoc()['count'];
    
    // Total assets
    $result = $conn->query("SELECT COUNT(*) as count FROM assets");
    $metrics['totalAssets'] = $result->fetch_assoc()['count'];
    
    // End of Life - Assets expiring within 6 months
    $result = $conn->query("
        SELECT COUNT(*) as count 
        FROM assets a
        LEFT JOIN asset_categories ac ON a.category = ac.id
        WHERE ac.end_of_life IS NOT NULL 
        AND DATE_ADD(a.created_at, INTERVAL ac.end_of_life YEAR) <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH) 
        AND DATE_ADD(a.created_at, INTERVAL ac.end_of_life YEAR) >= CURDATE()
    ");
    $metrics['assetsNearEOL'] = $result->fetch_assoc()['count'];
    
    // Fetch recent 5 activity logs
    $recent_logs_query = "SELECT al.action, al.entity_type, al.entity_id, al.description, 
                                 COALESCE(u.full_name, 'System') as performed_by, al.created_at 
                          FROM activity_logs al
                          LEFT JOIN users u ON al.user_id = u.id
                          ORDER BY al.created_at DESC 
                          LIMIT 5";
    $recent_logs_result = $conn->query($recent_logs_query);
    $recent_logs = [];
    
    if ($recent_logs_result && $recent_logs_result->num_rows > 0) {
        while ($log_row = $recent_logs_result->fetch_assoc()) {
            $recent_logs[] = [
                'action' => $log_row['action'],
                'entity_type' => ucfirst($log_row['entity_type']),
                'entity_id' => $log_row['entity_id'],
                'description' => $log_row['description'],
                'performed_by' => $log_row['performed_by'],
                'created_at' => $log_row['created_at']
            ];
        }
    }
    
    $metrics['recent_logs'] = $recent_logs;
    $metrics['timestamp'] = date('Y-m-d H:i:s');
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'unassignedIssues' => (int)$metrics['unassignedIssues'],
        'inProgressIssues' => (int)$metrics['inProgressIssues'],
        'resolvedIssues' => (int)$metrics['resolvedIssues'],
        'assetsBorrowed' => (int)$metrics['assetsBorrowed'],
        'assetsAvailable' => (int)$metrics['assetsAvailable'],
        'assetsInUse' => (int)$metrics['assetsInUse'],
        'assetsCritical' => (int)$metrics['assetsCritical'],
        'needsAttention' => (int)$metrics['needsAttention'],
        'healthyAssets' => (int)$metrics['healthyAssets'],
        'totalAssets' => (int)$metrics['totalAssets'],
        'assetsNearEOL' => (int)$metrics['assetsNearEOL'],
        'recent_logs' => $recent_logs,
        'timestamp' => $metrics['timestamp']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching metrics: ' . $e->getMessage()
    ]);
}
?>
