<?php
/**
 * Export Disposal List Controller
 * Exports assets eligible for disposal as CSV
 */

session_start();

// Check authentication and authorization
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: ../view/login.php");
    exit();
}

// Only Laboratory Staff can export disposal list
if ($_SESSION['role'] !== 'Laboratory Staff') {
    die('Insufficient permissions');
}

require_once '../config/config.php';

// Create database connection
$conn = new mysqli('localhost', 'root', '', 'ams_database');
if ($conn->connect_error) {
    die('Database connection failed');
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=disposal_list_' . date('Y-m-d_His') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, [
    'Asset Tag',
    'Asset Name',
    'Asset Type',
    'Category',
    'Brand',
    'Model',
    'Serial Number',
    'Building',
    'Room',
    'Status',
    'Condition',
    'End of Life',
    'Days Past EOL',
    'Purchase Date',
    'Purchase Cost',
    'Disposal Reason',
    'Notes'
]);

// Query for disposal-eligible assets
$current_date = date('Y-m-d');

$disposal_query = "
    SELECT 
        a.*,
        r.name as room_name,
        b.name as building_name,
        CASE 
            WHEN a.end_of_life IS NOT NULL AND a.end_of_life < ? THEN 'End of Life Reached'
            WHEN a.`condition` IN ('Poor', 'Non-Functional') THEN 'Poor Condition'
            ELSE 'Other'
        END as disposal_reason,
        DATEDIFF(?, a.end_of_life) as days_past_eol
    FROM assets a
    LEFT JOIN rooms r ON a.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    WHERE 
        a.status NOT IN ('Disposed', 'Archive', 'Archived')
        AND (
            (a.end_of_life IS NOT NULL AND a.end_of_life < ?)
            OR a.`condition` IN ('Poor', 'Non-Functional')
        )
    ORDER BY a.end_of_life ASC, a.asset_name ASC
";

$stmt = $conn->prepare($disposal_query);
$stmt->bind_param('sss', $current_date, $current_date, $current_date);
$stmt->execute();
$result = $stmt->get_result();

// Write data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['asset_tag'],
        $row['asset_name'],
        $row['asset_type'],
        $row['category'] ?? 'N/A',
        $row['brand'] ?? 'N/A',
        $row['model'] ?? 'N/A',
        $row['serial_number'] ?? 'N/A',
        $row['building_name'] ?? 'N/A',
        $row['room_name'] ?? 'N/A',
        $row['status'],
        $row['condition'],
        $row['end_of_life'] ? date('Y-m-d', strtotime($row['end_of_life'])) : 'Not Set',
        $row['days_past_eol'] > 0 ? $row['days_past_eol'] : '0',
        $row['purchase_date'] ? date('Y-m-d', strtotime($row['purchase_date'])) : 'N/A',
        $row['purchase_cost'] ? number_format($row['purchase_cost'], 2) : 'N/A',
        $row['disposal_reason'],
        $row['notes'] ?? ''
    ]);
}

// Log the export action
$log_query = "INSERT INTO activity_logs (user_id, action, entity_type, description, ip_address, user_agent) 
              VALUES (?, 'export', 'disposal_list', ?, ?, ?)";

$description = "Exported disposal list with " . $result->num_rows . " assets";
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

$log_stmt = $conn->prepare($log_query);
$log_stmt->bind_param('isss', $_SESSION['user_id'], $description, $ip_address, $user_agent);
$log_stmt->execute();

fclose($output);
$conn->close();
exit();
?>
