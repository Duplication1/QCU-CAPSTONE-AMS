<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../config/config.php';

try {
    // Establish database connection
    $dbConfig = Config::database();
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
    
    $action = $_GET['action'] ?? 'overview';
    
    switch ($action) {
        case 'overview':
            // Get overview metrics
            $data = [
                'assets' => [
                    'total' => $conn->query("SELECT COUNT(*) as count FROM assets")->fetch_assoc()['count'],
                    'available' => $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Available'")->fetch_assoc()['count'],
                    'in_use' => $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'In Use'")->fetch_assoc()['count'],
                    'maintenance' => $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Under Maintenance'")->fetch_assoc()['count']
                ],
                'conditions' => [
                    'excellent' => $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Excellent'")->fetch_assoc()['count'],
                    'good' => $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Good'")->fetch_assoc()['count'],
                    'fair' => $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Fair'")->fetch_assoc()['count'],
                    'poor' => $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Poor'")->fetch_assoc()['count'],
                    'non_functional' => $conn->query("SELECT COUNT(*) as count FROM assets WHERE `condition` = 'Non-Functional'")->fetch_assoc()['count']
                ],
                'maintenance' => [
                    'open' => $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'Open' AND (category IS NULL OR category != 'borrow')")->fetch_assoc()['count'],
                    'in_progress' => $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'In Progress' AND (category IS NULL OR category != 'borrow')")->fetch_assoc()['count'],
                    'resolved' => $conn->query("SELECT COUNT(*) as count FROM issues WHERE status = 'Resolved' AND (category IS NULL OR category != 'borrow')")->fetch_assoc()['count']
                ]
            ];
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'asset_types':
            // Get asset distribution by type
            $result = $conn->query("
                SELECT asset_type, COUNT(*) as count 
                FROM assets 
                GROUP BY asset_type 
                ORDER BY count DESC 
                LIMIT 10
            ");
            
            $types = [];
            $counts = [];
            while ($row = $result->fetch_assoc()) {
                $types[] = $row['asset_type'] ?: 'Uncategorized';
                $counts[] = (int)$row['count'];
            }
            
            echo json_encode(['success' => true, 'labels' => $types, 'data' => $counts]);
            break;
            
        case 'usage_trend':
            // Get usage trend for specified period
            $months = isset($_GET['months']) ? (int)$_GET['months'] : 6;
            
            $result = $conn->query("
                SELECT 
                    DATE_FORMAT(borrowed_date, '%Y-%m') as month,
                    DATE_FORMAT(borrowed_date, '%b %Y') as label,
                    COUNT(*) as count 
                FROM asset_borrowing 
                WHERE borrowed_date >= DATE_SUB(NOW(), INTERVAL $months MONTH)
                GROUP BY DATE_FORMAT(borrowed_date, '%Y-%m')
                ORDER BY month ASC
            ");
            
            $labels = [];
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['label'];
                $data[] = (int)$row['count'];
            }
            
            echo json_encode(['success' => true, 'labels' => $labels, 'data' => $data]);
            break;
            
        case 'maintenance_by_category':
            // Get maintenance issues by category
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
            
            $result = $conn->query("
                SELECT category, COUNT(*) as count 
                FROM issues 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
                AND (category IS NOT NULL AND category != 'borrow')
                GROUP BY category
                ORDER BY count DESC
                LIMIT 10
            ");
            
            $categories = [];
            $counts = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = ucfirst($row['category']);
                $counts[] = (int)$row['count'];
            }
            
            if (empty($categories)) {
                $categories = ['No Issues'];
                $counts = [0];
            }
            
            echo json_encode(['success' => true, 'labels' => $categories, 'data' => $counts]);
            break;
            
        case 'room_utilization':
            // Get room utilization data
            $result = $conn->query("
                SELECT 
                    r.name as room_name,
                    COUNT(a.id) as total_assets,
                    SUM(CASE WHEN a.status = 'In Use' THEN 1 ELSE 0 END) as in_use,
                    SUM(CASE WHEN a.status = 'Available' THEN 1 ELSE 0 END) as available
                FROM rooms r
                LEFT JOIN assets a ON r.id = a.room_id
                GROUP BY r.id, r.name
                HAVING total_assets > 0
                ORDER BY total_assets DESC
                LIMIT 10
            ");
            
            $rooms = [];
            $totalAssets = [];
            $inUse = [];
            $available = [];
            
            while ($row = $result->fetch_assoc()) {
                $rooms[] = $row['room_name'];
                $totalAssets[] = (int)$row['total_assets'];
                $inUse[] = (int)$row['in_use'];
                $available[] = (int)$row['available'];
            }
            
            echo json_encode([
                'success' => true,
                'labels' => $rooms,
                'datasets' => [
                    ['label' => 'Total Assets', 'data' => $totalAssets],
                    ['label' => 'In Use', 'data' => $inUse],
                    ['label' => 'Available', 'data' => $available]
                ]
            ]);
            break;
            
        case 'condition_trend':
            // Get condition changes over time
            $result = $conn->query("
                SELECT 
                    DATE_FORMAT(ah.changed_at, '%Y-%m') as month,
                    DATE_FORMAT(ah.changed_at, '%b %Y') as label,
                    ah.new_value as condition,
                    COUNT(*) as count
                FROM asset_history ah
                WHERE ah.action = 'Condition Changed'
                AND ah.changed_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(ah.changed_at, '%Y-%m'), ah.new_value
                ORDER BY month ASC
            ");
            
            $trendData = [];
            while ($row = $result->fetch_assoc()) {
                $month = $row['label'];
                $condition = $row['condition'];
                $count = (int)$row['count'];
                
                if (!isset($trendData[$month])) {
                    $trendData[$month] = [];
                }
                $trendData[$month][$condition] = $count;
            }
            
            echo json_encode(['success' => true, 'data' => $trendData]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
