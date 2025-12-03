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
    case 'activity_logs':
        generateActivityLogsReport($conn, $output, $startDate, $endDate);
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
    fputcsv($output, ['Asset Tag', 'Asset Name', 'Type', 'Category', 'Brand', 'Model', 'Serial Number', 'Status', 'Condition', 'Room', 'Building', 'Purchase Date', 'Purchase Cost', 'Warranty Expiry', 'Is Borrowable']);
    
    $query = "SELECT 
        a.asset_tag, 
        a.asset_name, 
        a.asset_type, 
        ac.name as category_name,
        a.brand, 
        a.model, 
        a.serial_number, 
        a.status,
        a.condition,
        r.name as room_name,
        b.name as building_name,
        a.purchase_date, 
        a.purchase_cost,
        a.warranty_expiry,
        a.is_borrowable
    FROM assets a
    LEFT JOIN rooms r ON a.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    LEFT JOIN asset_categories ac ON a.category = ac.id
    ORDER BY a.asset_tag";
    
    $stmt = $conn->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['asset_tag'],
            $row['asset_name'],
            $row['asset_type'],
            $row['category_name'] ?? 'N/A',
            $row['brand'] ?? 'N/A',
            $row['model'] ?? 'N/A',
            $row['serial_number'] ?? 'N/A',
            $row['status'],
            $row['condition'],
            $row['room_name'] ?? 'N/A',
            $row['building_name'] ?? 'N/A',
            $row['purchase_date'] ?? 'N/A',
            $row['purchase_cost'] ?? 'N/A',
            $row['warranty_expiry'] ?? 'N/A',
            $row['is_borrowable'] ? 'Yes' : 'No'
        ]);
    }
}

function generateTicketsReport($conn, $output, $startDate, $endDate) {
    fputcsv($output, ['Ticket ID', 'Category', 'Title', 'Description', 'Building', 'Room', 'PC Unit', 'Priority', 'Status', 'Submitter', 'Assigned To', 'Created At', 'Updated At']);
    
    $query = "SELECT 
        i.id,
        i.category,
        i.title,
        i.description,
        b.name as building_name,
        r.name as room_name,
        pc.terminal_number as pc_terminal,
        i.priority,
        i.status,
        u1.full_name as submitter,
        i.assigned_technician,
        i.created_at,
        i.updated_at
    FROM issues i
    LEFT JOIN users u1 ON i.user_id = u1.id
    LEFT JOIN rooms r ON i.room_id = r.id
    LEFT JOIN buildings b ON i.building_id = b.id
    LEFT JOIN pc_units pc ON i.pc_id = pc.id
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
            ucfirst($row['category']),
            $row['title'],
            $row['description'],
            $row['building_name'] ?? 'N/A',
            $row['room_name'] ?? 'N/A',
            $row['pc_terminal'] ?? 'N/A',
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
    fputcsv($output, ['Request ID', 'Borrower', 'Borrower Name', 'Asset', 'Purpose', 'Status', 'Borrowed Date', 'Expected Return', 'Actual Return', 'Approved By', 'Return Condition', 'Return Notes']);
    
    $query = "SELECT 
        ab.id,
        u.full_name as borrower,
        ab.borrower_name,
        a.asset_name,
        ab.purpose,
        ab.status,
        ab.borrowed_date,
        ab.expected_return_date,
        ab.actual_return_date,
        u2.full_name as approved_by_name,
        ab.returned_condition,
        ab.return_notes
    FROM asset_borrowing ab
    LEFT JOIN users u ON ab.borrower_id = u.id
    LEFT JOIN assets a ON ab.asset_id = a.id
    LEFT JOIN users u2 ON ab.approved_by = u2.id
    WHERE 1=1";
    
    if ($startDate && $endDate) {
        $query .= " AND DATE(ab.borrowed_date) BETWEEN ? AND ?";
        $stmt = $conn->prepare($query . " ORDER BY ab.created_at DESC");
        $stmt->execute([$startDate, $endDate]);
    } else {
        $stmt = $conn->query($query . " ORDER BY ab.created_at DESC");
    }
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['borrower'] ?? 'N/A',
            $row['borrower_name'],
            $row['asset_name'] ?? 'N/A',
            $row['purpose'] ?? 'N/A',
            $row['status'],
            $row['borrowed_date'] ?? 'N/A',
            $row['expected_return_date'] ?? 'N/A',
            $row['actual_return_date'] ?? 'Not Returned',
            $row['approved_by_name'] ?? 'N/A',
            $row['returned_condition'] ?? 'N/A',
            $row['return_notes'] ?? 'N/A'
        ]);
    }
}

function generatePCHealthReport($conn, $output) {
    fputcsv($output, ['PC Unit ID', 'Terminal Number', 'Building', 'Room', 'Asset Tag', 'Status', 'Condition', 'Created At', 'Notes']);
    
    $query = "SELECT 
        pc.id,
        pc.terminal_number,
        b.name as building_name,
        r.name as room_name,
        pc.asset_tag,
        pc.status,
        pc.condition,
        pc.created_at,
        pc.notes
    FROM pc_units pc
    LEFT JOIN rooms r ON pc.room_id = r.id
    LEFT JOIN buildings b ON pc.building_id = b.id
    ORDER BY b.name, r.name, pc.terminal_number";
    
    $stmt = $conn->query($query);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['terminal_number'] ?? 'N/A',
            $row['building_name'] ?? 'N/A',
            $row['room_name'] ?? 'N/A',
            $row['asset_tag'] ?? 'N/A',
            $row['status'],
            $row['condition'],
            $row['created_at'],
            $row['notes'] ?? 'N/A'
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

function generateActivityLogsReport($conn, $output, $startDate, $endDate) {
    fputcsv($output, ['ID', 'User', 'User Role', 'Action', 'Entity Type', 'Entity ID', 'Description', 'IP Address', 'Created At']);
    
    $query = "SELECT 
        al.id,
        u.full_name as user_name,
        u.role as user_role,
        al.action,
        al.entity_type,
        al.entity_id,
        al.description,
        al.ip_address,
        al.created_at
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE 1=1";
    
    if ($startDate && $endDate) {
        $query .= " AND DATE(al.created_at) BETWEEN ? AND ?";
        $stmt = $conn->prepare($query . " ORDER BY al.created_at DESC");
        $stmt->execute([$startDate, $endDate]);
    } else {
        $stmt = $conn->query($query . " ORDER BY al.created_at DESC");
    }
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['user_name'] ?? 'System',
            $row['user_role'] ?? 'N/A',
            ucfirst($row['action']),
            $row['entity_type'] ?? 'N/A',
            $row['entity_id'] ?? 'N/A',
            $row['description'] ?? 'N/A',
            $row['ip_address'] ?? 'N/A',
            $row['created_at']
        ]);
    }
}
