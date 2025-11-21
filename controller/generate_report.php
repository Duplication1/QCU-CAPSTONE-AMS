<?php
session_start();

// Check if user is logged in and has administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
    die('Unauthorized access');
}

require_once '../config/config.php';
require_once '../model/Database.php';
require_once '../model/ActivityLog.php';

$db = new Database();
$conn = $db->getConnection();

$reportType = $_POST['report_type'] ?? '';
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';

if (empty($reportType)) {
    die('Invalid report type');
}

// Log the report generation activity
$dateRange = ($startDate && $endDate) ? " ({$startDate} to {$endDate})" : "";
ActivityLog::record(
    $_SESSION['user_id'],
    'export',
    'report',
    null,
    "Generated {$reportType} report{$dateRange}"
);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

switch ($reportType) {
    case 'users':
        generateUsersReport($conn, $output);
        break;
    case 'assets':
        generateAssetsReport($conn, $output);
        break;
    case 'tickets':
        generateTicketsReport($conn, $output, $startDate, $endDate);
        break;
    case 'borrowing':
        generateBorrowingReport($conn, $output, $startDate, $endDate);
        break;
    case 'pc_health':
        generatePCHealthReport($conn, $output);
        break;
    case 'summary':
        generateSummaryReport($conn, $output);
        break;
    default:
        fputcsv($output, ['Error: Unknown report type']);
}

fclose($output);
exit;

function generateUsersReport($conn, $output) {
    fputcsv($output, ['ID Number', 'Full Name', 'Email', 'Role', 'Status', 'Created At', 'Last Login']);
    
    $stmt = $conn->query("SELECT id_number, full_name, email, role, status, created_at, last_login FROM users ORDER BY created_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id_number'],
            $row['full_name'],
            $row['email'],
            $row['role'],
            $row['status'],
            $row['created_at'],
            $row['last_login'] ?? 'Never'
        ]);
    }
}

function generateAssetsReport($conn, $output) {
    fputcsv($output, ['Asset Tag', 'Asset Name', 'Type', 'Brand', 'Model', 'Serial Number', 'Status', 'Location', 'Acquired Date']);
    
    $stmt = $conn->query("SELECT asset_tag, asset_name, type, brand, model, serial_number, status, room, date_acquired FROM assets ORDER BY asset_tag");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['asset_tag'],
            $row['asset_name'],
            $row['type'],
            $row['brand'],
            $row['model'],
            $row['serial_number'],
            $row['status'],
            $row['room'],
            $row['date_acquired']
        ]);
    }
}

function generateTicketsReport($conn, $output, $startDate, $endDate) {
    fputcsv($output, ['Ticket ID', 'Category', 'Title', 'Description', 'Room', 'Priority', 'Status', 'Submitter', 'Assigned To', 'Created At', 'Updated At']);
    
    $query = "SELECT 
        i.id,
        i.category,
        i.title,
        i.description,
        i.room,
        i.priority,
        i.status,
        u1.full_name as submitter,
        i.assigned_technician,
        i.created_at,
        i.updated_at
    FROM issues i
    LEFT JOIN users u1 ON i.user_id = u1.id
    WHERE 1=1";
    
    if ($startDate && $endDate) {
        $query .= " AND DATE(i.created_at) BETWEEN ? AND ?";
        $stmt = $conn->prepare($query . " ORDER BY i.created_at DESC");
        $stmt->execute([$startDate, $endDate]);
    } else {
        $stmt = $conn->query($query . " ORDER BY i.created_at DESC");
    }
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['category'],
            $row['title'],
            $row['description'],
            $row['room'],
            $row['priority'],
            $row['status'],
            $row['submitter'],
            $row['assigned_technician'] ?? 'Unassigned',
            $row['created_at'],
            $row['updated_at']
        ]);
    }
}

function generateBorrowingReport($conn, $output, $startDate, $endDate) {
    fputcsv($output, ['Request ID', 'Borrower', 'Asset', 'Purpose', 'Status', 'Borrowed Date', 'Return Date', 'Actual Return', 'Condition']);
    
    $query = "SELECT 
        ab.id,
        u.full_name as borrower,
        a.asset_name,
        ab.purpose,
        ab.status,
        ab.borrow_date,
        ab.return_date,
        ab.actual_return_date,
        ab.return_condition
    FROM asset_borrowing ab
    LEFT JOIN users u ON ab.borrower_id = u.id
    LEFT JOIN assets a ON ab.asset_id = a.id
    WHERE 1=1";
    
    if ($startDate && $endDate) {
        $query .= " AND DATE(ab.borrow_date) BETWEEN ? AND ?";
        $stmt = $conn->prepare($query . " ORDER BY ab.created_at DESC");
        $stmt->execute([$startDate, $endDate]);
    } else {
        $stmt = $conn->query($query . " ORDER BY ab.created_at DESC");
    }
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['borrower'],
            $row['asset_name'],
            $row['purpose'],
            $row['status'],
            $row['borrow_date'],
            $row['return_date'],
            $row['actual_return_date'] ?? '-',
            $row['return_condition'] ?? '-'
        ]);
    }
}

function generatePCHealthReport($conn, $output) {
    fputcsv($output, ['PC Unit', 'Room', 'CPU Usage', 'RAM Usage', 'Disk Usage', 'Health Score', 'Status', 'Last Updated']);
    
    $stmt = $conn->query("SELECT 
        pc.pc_name,
        pc.room,
        pc.cpu_usage,
        pc.ram_usage,
        pc.disk_usage,
        pc.health_score,
        pc.status,
        pc.last_updated
    FROM pc_units pc
    ORDER BY pc.room, pc.pc_name");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['pc_name'],
            $row['room'],
            $row['cpu_usage'] . '%',
            $row['ram_usage'] . '%',
            $row['disk_usage'] . '%',
            $row['health_score'],
            $row['status'],
            $row['last_updated']
        ]);
    }
}

function generateSummaryReport($conn, $output) {
    fputcsv($output, ['System Activity Summary - Generated on ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Users summary
    $stmt = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    fputcsv($output, ['Users by Role']);
    fputcsv($output, ['Role', 'Count']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [$row['role'], $row['count']]);
    }
    fputcsv($output, []);
    
    // Assets summary
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM assets GROUP BY status");
    fputcsv($output, ['Assets by Status']);
    fputcsv($output, ['Status', 'Count']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [$row['status'], $row['count']]);
    }
    fputcsv($output, []);
    
    // Tickets summary
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM issues GROUP BY status");
    fputcsv($output, ['Tickets by Status']);
    fputcsv($output, ['Status', 'Count']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [$row['status'], $row['count']]);
    }
    fputcsv($output, []);
    
    // Borrowing summary
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM asset_borrowing GROUP BY status");
    fputcsv($output, ['Borrowing Requests by Status']);
    fputcsv($output, ['Status', 'Count']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [$row['status'], $row['count']]);
    }
}
