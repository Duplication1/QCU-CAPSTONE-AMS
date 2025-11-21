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
            $headers = ['ID', 'Category', 'Title', 'Room', 'Priority', 'Status', 'Submitter', 'Created'];
            $query = "SELECT 
                i.id,
                i.category,
                i.title,
                i.room,
                i.priority,
                i.status,
                u.full_name as submitter,
                DATE_FORMAT(i.created_at, '%Y-%m-%d %H:%i') as created
            FROM issues i
            LEFT JOIN users u ON i.user_id = u.id
            WHERE DATE(i.created_at) BETWEEN ? AND ?
            ORDER BY i.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $data[] = [
                    'ID' => '#' . str_pad($row['id'], 4, '0', STR_PAD_LEFT),
                    'Category' => $row['category'],
                    'Title' => $row['title'],
                    'Room' => $row['room'],
                    'Priority' => $row['priority'],
                    'Status' => $row['status'],
                    'Submitter' => $row['submitter'],
                    'Created' => $row['created']
                ];
            }
            break;
            
        case 'borrowing':
            $headers = ['ID', 'Borrower', 'Asset', 'Purpose', 'Status', 'Borrow Date', 'Return Date'];
            $query = "SELECT 
                ab.id,
                u.full_name as borrower,
                a.asset_name,
                ab.purpose,
                ab.status,
                DATE_FORMAT(ab.borrow_date, '%Y-%m-%d') as borrow_date,
                DATE_FORMAT(ab.return_date, '%Y-%m-%d') as return_date
            FROM asset_borrowing ab
            LEFT JOIN users u ON ab.borrower_id = u.id
            LEFT JOIN assets a ON ab.asset_id = a.id
            WHERE DATE(ab.borrow_date) BETWEEN ? AND ?
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
                    'Borrow Date' => $row['borrow_date'],
                    'Return Date' => $row['return_date']
                ];
            }
            break;
            
        case 'assets':
            $headers = ['Asset Tag', 'Name', 'Type', 'Brand', 'Status', 'Location', 'Acquired'];
            $query = "SELECT 
                asset_tag,
                asset_name,
                type,
                brand,
                status,
                room,
                DATE_FORMAT(date_acquired, '%Y-%m-%d') as acquired
            FROM assets
            WHERE DATE(date_acquired) BETWEEN ? AND ?
            ORDER BY asset_tag";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $data[] = [
                    'Asset Tag' => $row['asset_tag'],
                    'Name' => $row['asset_name'],
                    'Type' => $row['type'],
                    'Brand' => $row['brand'],
                    'Status' => $row['status'],
                    'Location' => $row['room'],
                    'Acquired' => $row['acquired']
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
