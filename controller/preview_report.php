<?php
session_start();

// Check if user is logged in and has administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../config/config.php';
require_once '../model/Database.php';
require_once '../model/ActivityLog.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();

$reportType = $_POST['report_type'] ?? '';
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';

if (empty($reportType) || empty($startDate) || empty($endDate)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Log the report preview activity
ActivityLog::record(
    $_SESSION['user_id'],
    'view',
    'report',
    null,
    "Previewed {$reportType} report ({$startDate} to {$endDate})"
);

try {
    $data = [];
    $headers = [];
    
    switch ($reportType) {
        case 'tickets':
            $headers = ['ID', 'Category', 'Title', 'Building', 'Room', 'PC', 'Priority', 'Status', 'Submitter', 'Created'];
            $query = "SELECT 
                i.id,
                i.category,
                i.title,
                b.name as building_name,
                r.name as room_name,
                pc.terminal_number,
                i.priority,
                i.status,
                u.full_name as submitter,
                DATE_FORMAT(i.created_at, '%Y-%m-%d %H:%i') as created
            FROM issues i
            LEFT JOIN users u ON i.user_id = u.id
            LEFT JOIN rooms r ON i.room_id = r.id
            LEFT JOIN buildings b ON i.building_id = b.id
            LEFT JOIN pc_units pc ON i.pc_id = pc.id
            WHERE DATE(i.created_at) BETWEEN ? AND ?
            ORDER BY i.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $data[] = [
                    'ID' => '#' . str_pad($row['id'], 4, '0', STR_PAD_LEFT),
                    'Category' => ucfirst($row['category']),
                    'Title' => $row['title'],
                    'Building' => $row['building_name'] ?? 'N/A',
                    'Room' => $row['room_name'] ?? 'N/A',
                    'PC' => $row['terminal_number'] ?? 'N/A',
                    'Priority' => $row['priority'],
                    'Status' => $row['status'],
                    'Submitter' => $row['submitter'],
                    'Created' => $row['created']
                ];
            }
            break;
            
        case 'borrowing':
            $headers = ['ID', 'Borrower', 'Asset', 'Purpose', 'Status', 'Borrowed Date', 'Expected Return', 'Actual Return'];
            $query = "SELECT 
                ab.id,
                u.full_name as borrower,
                a.asset_name,
                ab.purpose,
                ab.status,
                DATE_FORMAT(ab.borrowed_date, '%Y-%m-%d') as borrowed_date,
                DATE_FORMAT(ab.expected_return_date, '%Y-%m-%d') as expected_return,
                DATE_FORMAT(ab.actual_return_date, '%Y-%m-%d') as actual_return
            FROM asset_borrowing ab
            LEFT JOIN users u ON ab.borrower_id = u.id
            LEFT JOIN assets a ON ab.asset_id = a.id
            WHERE DATE(ab.borrowed_date) BETWEEN ? AND ?
            ORDER BY ab.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $data[] = [
                    'ID' => '#' . str_pad($row['id'], 4, '0', STR_PAD_LEFT),
                    'Borrower' => $row['borrower'],
                    'Asset' => $row['asset_name'],
                    'Purpose' => $row['purpose'],
                    'Status' => $row['status'],
                    'Borrowed Date' => $row['borrowed_date'],
                    'Expected Return' => $row['expected_return'],
                    'Actual Return' => $row['actual_return'] ?? 'Not Returned'
                ];
            }
            break;
            
        case 'assets':
            $headers = ['Asset Tag', 'Name', 'Type', 'Category', 'Brand', 'Status', 'Building', 'Room', 'Purchased'];
            $query = "SELECT 
                a.asset_tag,
                a.asset_name,
                a.asset_type,
                ac.name as category_name,
                a.brand,
                a.status,
                b.name as building_name,
                r.name as room_name,
                DATE_FORMAT(a.purchase_date, '%Y-%m-%d') as purchased
            FROM assets a
            LEFT JOIN rooms r ON a.room_id = r.id
            LEFT JOIN buildings b ON r.building_id = b.id
            LEFT JOIN asset_categories ac ON a.category = ac.id
            WHERE DATE(a.created_at) BETWEEN ? AND ?
            ORDER BY a.asset_tag";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $data[] = [
                    'Asset Tag' => $row['asset_tag'],
                    'Name' => $row['asset_name'],
                    'Type' => $row['asset_type'],
                    'Category' => $row['category_name'] ?? 'N/A',
                    'Brand' => $row['brand'] ?? 'N/A',
                    'Status' => $row['status'],
                    'Building' => $row['building_name'] ?? 'N/A',
                    'Room' => $row['room_name'] ?? 'N/A',
                    'Purchased' => $row['purchased'] ?? 'N/A'
                ];
            }
            break;
            
        case 'activity_logs':
            $headers = ['ID', 'User', 'Role', 'Action', 'Entity', 'Description', 'IP Address', 'Date/Time'];
            $query = "SELECT 
                al.id,
                u.full_name as user_name,
                u.role as user_role,
                al.action,
                al.entity_type,
                al.description,
                al.ip_address,
                DATE_FORMAT(al.created_at, '%Y-%m-%d %H:%i') as created
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE DATE(al.created_at) BETWEEN ? AND ?
            ORDER BY al.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $data[] = [
                    'ID' => '#' . str_pad($row['id'], 4, '0', STR_PAD_LEFT),
                    'User' => $row['user_name'] ?? 'System',
                    'Role' => $row['user_role'] ?? 'N/A',
                    'Action' => ucfirst($row['action']),
                    'Entity' => $row['entity_type'] ?? 'N/A',
                    'Description' => $row['description'] ?? 'N/A',
                    'IP Address' => $row['ip_address'] ?? 'N/A',
                    'Date/Time' => $row['created']
                ];
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid report type']);
            exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'headers' => $headers,
        'count' => count($data)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
