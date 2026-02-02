<?php
session_start();

// Check if user is logged in and has administrator or laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || 
    !in_array($_SESSION['role'], ['Administrator', 'Laboratory Staff'])) {
    die('Unauthorized access');
}

require_once '../config/config.php';
require_once '../model/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Get filters from GET parameters (from activity_logs.php)
$actionFilter = $_GET['action'] ?? '';
$entityFilter = $_GET['entity'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$searchFilter = $_GET['search'] ?? '';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, ['Timestamp', 'User', 'ID Number', 'Action', 'Entity', 'Description', 'IP Address']);

// Build query - filter by Laboratory Staff role if user is Lab Staff
$query = "SELECT 
    al.*,
    u.full_name,
    u.id_number,
    u.role
FROM activity_logs al
LEFT JOIN users u ON al.user_id = u.id
WHERE 1=1";

$params = [];

// If user is Laboratory Staff, only show their logs
if ($_SESSION['role'] === 'Laboratory Staff') {
    $query .= " AND u.role = 'Laboratory Staff'";
}

if ($actionFilter) {
    $query .= " AND al.action = ?";
    $params[] = $actionFilter;
}

if ($entityFilter) {
    $query .= " AND al.entity_type = ?";
    $params[] = $entityFilter;
}

if ($dateFrom) {
    $query .= " AND DATE(al.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $query .= " AND DATE(al.created_at) <= ?";
    $params[] = $dateTo;
}

if ($searchFilter) {
    $query .= " AND (al.description LIKE ? OR u.full_name LIKE ? OR u.id_number LIKE ?)";
    $searchParam = "%$searchFilter%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$query .= " ORDER BY al.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $entityInfo = '';
    if ($row['entity_type']) {
        $entityInfo = ucfirst(str_replace('_', ' ', $row['entity_type']));
        if ($row['entity_id']) {
            $entityInfo .= ' #' . $row['entity_id'];
        }
    }
    
    fputcsv($output, [
        $row['created_at'],
        $row['full_name'] ?? 'Unknown',
        $row['id_number'] ?? 'N/A',
        ucfirst($row['action']),
        $entityInfo ?: '—',
        $row['description'] ?? '',
        $row['ip_address'] ?? '-'
    ]);
}

fclose($output);
exit;
