<?php
session_start();

// Check if user is logged in and has administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
    die('Unauthorized access');
}

require_once '../config/config.php';
require_once '../model/Database.php';

$db = new Database();
$conn = $db->getConnection();

$actionFilter = $_POST['action'] ?? '';
$dateFilter = $_POST['date'] ?? '';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, ['Timestamp', 'User', 'Email', 'Role', 'Action', 'Description', 'IP Address']);

// Build query
$query = "SELECT 
    al.*,
    u.full_name,
    u.email,
    u.role
FROM activity_logs al
LEFT JOIN users u ON al.user_id = u.id
WHERE 1=1";

$params = [];

if ($actionFilter) {
    $query .= " AND al.action = ?";
    $params[] = $actionFilter;
}

if ($dateFilter) {
    $query .= " AND DATE(al.created_at) = ?";
    $params[] = $dateFilter;
}

$query .= " ORDER BY al.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['created_at'],
        $row['full_name'] ?? 'Unknown',
        $row['email'] ?? '',
        $row['role'] ?? '',
        ucfirst($row['action']),
        $row['description'] ?? '',
        $row['ip_address'] ?? '-'
    ]);
}

fclose($output);
exit;
