<?php
header('Content-Type: application/json');
session_start();

// Check if user is logged in and has administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/Database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $analytics = [];
    
    // 1. Assets Requiring Maintenance Most Often (by maintenance count)
    $stmt = $pdo->query("
        SELECT 
            a.id,
            a.asset_tag,
            a.asset_name,
            a.asset_type,
            a.category,
            a.brand,
            a.model,
            a.purchase_date,
            a.purchase_cost,
            a.last_maintenance_date,
            a.next_maintenance_date,
            a.status,
            a.condition,
            DATEDIFF(CURDATE(), a.purchase_date) as asset_age_days,
            TIMESTAMPDIFF(YEAR, a.purchase_date, CURDATE()) as asset_age_years,
            TIMESTAMPDIFF(MONTH, a.purchase_date, CURDATE()) % 12 as asset_age_months,
            COUNT(am.id) as maintenance_count,
            SUM(CASE WHEN am.maintenance_type = 'Corrective' THEN 1 ELSE 0 END) as corrective_count,
            SUM(CASE WHEN am.maintenance_type = 'Emergency' THEN 1 ELSE 0 END) as emergency_count,
            SUM(CASE WHEN am.maintenance_type = 'Preventive' THEN 1 ELSE 0 END) as preventive_count,
            SUM(COALESCE(am.cost, 0)) as total_maintenance_cost,
            MAX(am.maintenance_date) as latest_maintenance_date,
            DATEDIFF(CURDATE(), a.next_maintenance_date) as days_overdue,
            CASE 
                WHEN a.next_maintenance_date IS NOT NULL AND CURDATE() > a.next_maintenance_date 
                THEN DATEDIFF(CURDATE(), a.next_maintenance_date)
                ELSE 0 
            END as maintenance_overdue_days,
            COUNT(i.id) as issue_count
        FROM assets a
        LEFT JOIN asset_maintenance am ON a.id = am.asset_id AND am.status = 'Completed'
        LEFT JOIN issues i ON (
            CONCAT(a.room_id, '-', a.terminal_number) COLLATE utf8mb4_unicode_ci = CONCAT(i.room, '-', i.terminal)
            OR a.asset_tag COLLATE utf8mb4_unicode_ci LIKE CONCAT('%', i.room, '%')
        )
        GROUP BY a.id
        ORDER BY maintenance_count DESC, issue_count DESC
        LIMIT 50
    ");
    $analytics['frequent_maintenance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Maintenance by Asset Type
    $stmt = $pdo->query("
        SELECT 
            a.asset_type,
            COUNT(DISTINCT a.id) as asset_count,
            COUNT(am.id) as total_maintenance,
            AVG(DATEDIFF(CURDATE(), a.purchase_date)) as avg_asset_age,
            SUM(COALESCE(am.cost, 0)) as total_cost
        FROM assets a
        LEFT JOIN asset_maintenance am ON a.id = am.asset_id
        GROUP BY a.asset_type
        ORDER BY total_maintenance DESC
    ");
    $analytics['maintenance_by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Maintenance by Category
    $stmt = $pdo->query("
        SELECT 
            a.category,
            COUNT(DISTINCT a.id) as asset_count,
            COUNT(am.id) as total_maintenance,
            SUM(COALESCE(am.cost, 0)) as total_cost
        FROM assets a
        LEFT JOIN asset_maintenance am ON a.id = am.asset_id
        WHERE a.category IS NOT NULL
        GROUP BY a.category
        ORDER BY total_maintenance DESC
        LIMIT 10
    ");
    $analytics['maintenance_by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Asset Condition Distribution
    $stmt = $pdo->query("
        SELECT 
            a.condition,
            COUNT(*) as count,
            COUNT(am.id) as maintenance_count,
            AVG(DATEDIFF(CURDATE(), a.purchase_date)) as avg_age
        FROM assets a
        LEFT JOIN asset_maintenance am ON a.id = am.asset_id
        GROUP BY a.condition
        ORDER BY 
            FIELD(a.condition, 'Non-Functional', 'Poor', 'Fair', 'Good', 'Excellent')
    ");
    $analytics['condition_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. High Risk Assets (old, expensive, poor condition, frequent issues)
    $stmt = $pdo->query("
        SELECT 
            a.id,
            a.asset_tag,
            a.asset_name,
            a.asset_type,
            a.brand,
            a.model,
            a.condition,
            a.purchase_cost,
            TIMESTAMPDIFF(YEAR, a.purchase_date, CURDATE()) as age_years,
            COUNT(am.id) as maintenance_count,
            SUM(COALESCE(am.cost, 0)) as total_maintenance_cost,
            COUNT(i.id) as issue_count,
            (
                (CASE a.condition 
                    WHEN 'Non-Functional' THEN 50
                    WHEN 'Poor' THEN 40
                    WHEN 'Fair' THEN 25
                    WHEN 'Good' THEN 10
                    ELSE 0 END) +
                (TIMESTAMPDIFF(YEAR, a.purchase_date, CURDATE()) * 5) +
                (COUNT(am.id) * 3) +
                (COUNT(i.id) * 8)
            ) as risk_score
        FROM assets a
        LEFT JOIN asset_maintenance am ON a.id = am.asset_id
        LEFT JOIN issues i ON (
            CONCAT(a.room_id, '-', a.terminal_number) COLLATE utf8mb4_unicode_ci = CONCAT(i.room, '-', i.terminal)
            OR a.asset_tag COLLATE utf8mb4_unicode_ci LIKE CONCAT('%', i.room, '%')
        )
        WHERE a.status != 'Retired' AND a.status != 'Disposed'
        GROUP BY a.id
        HAVING risk_score > 20
        ORDER BY risk_score DESC
        LIMIT 20
    ");
    $analytics['high_risk_assets'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. Maintenance Timeline (last 12 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(am.maintenance_date, '%Y-%m') as month,
            COUNT(*) as maintenance_count,
            SUM(COALESCE(am.cost, 0)) as total_cost,
            SUM(CASE WHEN am.maintenance_type = 'Emergency' THEN 1 ELSE 0 END) as emergency_count,
            SUM(CASE WHEN am.maintenance_type = 'Corrective' THEN 1 ELSE 0 END) as corrective_count,
            SUM(CASE WHEN am.maintenance_type = 'Preventive' THEN 1 ELSE 0 END) as preventive_count
        FROM asset_maintenance am
        WHERE am.maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ");
    $analytics['maintenance_timeline'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 7. Upcoming Maintenance (overdue and due soon)
    $stmt = $pdo->query("
        SELECT 
            a.id,
            a.asset_tag,
            a.asset_name,
            a.asset_type,
            a.category,
            a.next_maintenance_date,
            a.last_maintenance_date,
            DATEDIFF(a.next_maintenance_date, CURDATE()) as days_until_due,
            CASE 
                WHEN a.next_maintenance_date < CURDATE() THEN 'Overdue'
                WHEN DATEDIFF(a.next_maintenance_date, CURDATE()) <= 7 THEN 'Due This Week'
                WHEN DATEDIFF(a.next_maintenance_date, CURDATE()) <= 30 THEN 'Due This Month'
                ELSE 'Scheduled'
            END as urgency
        FROM assets a
        WHERE a.next_maintenance_date IS NOT NULL
            AND a.status NOT IN ('Retired', 'Disposed')
            AND DATEDIFF(a.next_maintenance_date, CURDATE()) <= 30
        ORDER BY a.next_maintenance_date ASC
        LIMIT 30
    ");
    $analytics['upcoming_maintenance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 8. Issues by Category (for correlation)
    $stmt = $pdo->query("
        SELECT 
            category,
            COUNT(*) as count,
            SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_count
        FROM issues
        GROUP BY category
        ORDER BY count DESC
    ");
    $analytics['issues_by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 9. Summary Statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT a.id) as total_assets,
            COUNT(am.id) as total_maintenance_records,
            SUM(COALESCE(am.cost, 0)) as total_maintenance_cost,
            AVG(COALESCE(am.cost, 0)) as avg_maintenance_cost,
            SUM(CASE WHEN a.next_maintenance_date < CURDATE() THEN 1 ELSE 0 END) as overdue_maintenance,
            SUM(CASE WHEN a.condition IN ('Poor', 'Non-Functional') THEN 1 ELSE 0 END) as poor_condition_assets,
            COUNT(DISTINCT i.id) as total_issues
        FROM assets a
        LEFT JOIN asset_maintenance am ON a.id = am.asset_id
        LEFT JOIN issues i ON (
            CONCAT(a.room_id, '-', a.terminal_number) COLLATE utf8mb4_unicode_ci = CONCAT(i.room, '-', i.terminal)
            OR a.asset_tag COLLATE utf8mb4_unicode_ci LIKE CONCAT('%', i.room, '%')
        )
    ");
    $analytics['summary'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $analytics
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching analytics: ' . $e->getMessage()
    ]);
}
