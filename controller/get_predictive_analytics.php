<?php
/**
 * Predictive Analytics Controller
 * 
 * Implements linear regression and statistical models for asset predictions:
 * - Asset failure risk prediction
 * - Maintenance forecasting
 * - End-of-life estimation
 * - Condition degradation trends
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, we'll catch them
ini_set('log_errors', 1);

header('Content-Type: application/json');
session_start();

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please log in']);
    exit();
}

require_once '../config/config.php';

// Establish database connection
$dbConfig = Config::database();
try {
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection error: ' . $e->getMessage()]);
    exit();
}

/**
 * Calculate linear regression for time series data
 * Returns slope, intercept, and R-squared
 */
function calculateLinearRegression($x_values, $y_values) {
    $n = count($x_values);
    if ($n < 2) {
        return ['slope' => 0, 'intercept' => 0, 'r_squared' => 0];
    }
    
    $sum_x = array_sum($x_values);
    $sum_y = array_sum($y_values);
    $sum_xy = 0;
    $sum_x2 = 0;
    $sum_y2 = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $sum_xy += $x_values[$i] * $y_values[$i];
        $sum_x2 += $x_values[$i] * $x_values[$i];
        $sum_y2 += $y_values[$i] * $y_values[$i];
    }
    
    // Calculate slope (m) and intercept (b)
    $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_x2 - $sum_x * $sum_x);
    $intercept = ($sum_y - $slope * $sum_x) / $n;
    
    // Calculate R-squared
    $mean_y = $sum_y / $n;
    $ss_tot = 0;
    $ss_res = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $predicted = $slope * $x_values[$i] + $intercept;
        $ss_tot += pow($y_values[$i] - $mean_y, 2);
        $ss_res += pow($y_values[$i] - $predicted, 2);
    }
    
    $r_squared = $ss_tot > 0 ? 1 - ($ss_res / $ss_tot) : 0;
    
    return [
        'slope' => $slope,
        'intercept' => $intercept,
        'r_squared' => $r_squared
    ];
}

/**
 * Convert condition to numeric score for regression analysis
 */
function conditionToScore($condition) {
    $scores = [
        'Excellent' => 100,
        'Good' => 75,
        'Fair' => 50,
        'Poor' => 25,
        'Non-Functional' => 0
    ];
    return $scores[$condition] ?? 50;
}

/**
 * Get asset failure risk prediction
 */
function getAssetFailureRisk($conn) {
    // Get assets with their age, condition changes, and issue counts
    $query = "
        SELECT 
            a.id,
            a.asset_tag,
            a.asset_name,
            a.condition,
            DATEDIFF(NOW(), a.created_at) as age_days,
            COUNT(DISTINCT i.id) as issue_count,
            COUNT(DISTINCT ah.id) as condition_changes,
            (SELECT COUNT(*) FROM asset_history ah2 
             WHERE ah2.asset_id = a.id 
             AND ah2.action_type = 'Condition Changed'
             AND ah2.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)) as recent_condition_changes
        FROM assets a
        LEFT JOIN issues i ON i.component_asset_id = a.id
        LEFT JOIN asset_history ah ON ah.asset_id = a.id AND ah.action_type = 'Condition Changed'
        WHERE a.status NOT IN ('Disposed', 'Archive')
        GROUP BY a.id
        HAVING age_days > 0
        ORDER BY a.id
    ";
    
    $result = $conn->query($query);
    $assets = [];
    
    while ($row = $result->fetch_assoc()) {
        // Calculate risk score (0-100, higher = more risk)
        $condition_score = conditionToScore($row['condition']);
        $age_factor = min(100, ($row['age_days'] / 1095) * 30); // 3 years = max age factor of 30
        $issue_factor = min(40, $row['issue_count'] * 5); // Each issue adds 5 points, max 40
        $degradation_factor = min(30, $row['recent_condition_changes'] * 10); // Recent changes add 10 each, max 30
        
        $risk_score = 100 - $condition_score + $age_factor + $issue_factor + $degradation_factor;
        $risk_score = max(0, min(100, $risk_score)); // Clamp between 0-100
        
        // Categorize risk
        $risk_level = 'Low';
        if ($risk_score >= 70) $risk_level = 'Critical';
        elseif ($risk_score >= 50) $risk_level = 'High';
        elseif ($risk_score >= 30) $risk_level = 'Medium';
        
        $assets[] = [
            'id' => $row['id'],
            'asset_tag' => $row['asset_tag'],
            'asset_name' => $row['asset_name'],
            'condition' => $row['condition'],
            'age_days' => (int)$row['age_days'],
            'issue_count' => (int)$row['issue_count'],
            'risk_score' => round($risk_score, 2),
            'risk_level' => $risk_level
        ];
    }
    
    return $assets;
}

/**
 * Get condition degradation trend with linear regression
 */
function getConditionDegradationTrend($conn) {
    // Get condition changes over time
    $query = "
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            AVG(CASE 
                WHEN new_value = 'Excellent' THEN 100
                WHEN new_value = 'Good' THEN 75
                WHEN new_value = 'Fair' THEN 50
                WHEN new_value = 'Poor' THEN 25
                WHEN new_value = 'Non-Functional' THEN 0
                ELSE 50
            END) as avg_condition_score
        FROM asset_history
        WHERE action_type = 'Condition Changed'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ";
    
    $result = $conn->query($query);
    $data = [];
    $x_values = [];
    $y_values = [];
    $index = 0;
    
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'month' => $row['month'],
            'score' => round($row['avg_condition_score'], 2)
        ];
        $x_values[] = $index++;
        $y_values[] = $row['avg_condition_score'];
    }
    
    // Calculate regression
    $regression = calculateLinearRegression($x_values, $y_values);
    
    // Predict next 6 months
    $predictions = [];
    $last_index = count($x_values);
    for ($i = 1; $i <= 6; $i++) {
        $predicted_value = $regression['slope'] * ($last_index + $i - 1) + $regression['intercept'];
        $predictions[] = max(0, min(100, round($predicted_value, 2)));
    }
    
    return [
        'historical' => $data,
        'regression' => $regression,
        'predictions' => $predictions,
        'trend' => $regression['slope'] < 0 ? 'degrading' : 'improving'
    ];
}

/**
 * Get maintenance forecast based on issue trends
 */
function getMaintenanceForecast($conn) {
    // Get issue counts by month
    $query = "
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as issue_count
        FROM issues
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ";
    
    $result = $conn->query($query);
    $data = [];
    $x_values = [];
    $y_values = [];
    $index = 0;
    
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'month' => $row['month'],
            'count' => (int)$row['issue_count']
        ];
        $x_values[] = $index++;
        $y_values[] = (int)$row['issue_count'];
    }
    
    // Calculate regression
    $regression = calculateLinearRegression($x_values, $y_values);
    
    // Predict next 6 months
    $predictions = [];
    $last_index = count($x_values);
    for ($i = 1; $i <= 6; $i++) {
        $predicted_value = $regression['slope'] * ($last_index + $i - 1) + $regression['intercept'];
        $predictions[] = max(0, round($predicted_value));
    }
    
    return [
        'historical' => $data,
        'regression' => $regression,
        'predictions' => $predictions,
        'trend' => $regression['slope'] > 0 ? 'increasing' : 'decreasing'
    ];
}

/**
 * Get asset lifecycle predictions
 */
function getAssetLifecyclePredictions($conn) {
    // Analyze assets by age groups
    $query = "
        SELECT 
            CASE 
                WHEN DATEDIFF(NOW(), created_at) <= 180 THEN 'New (0-6m)'
                WHEN DATEDIFF(NOW(), created_at) <= 365 THEN 'Young (6-12m)'
                WHEN DATEDIFF(NOW(), created_at) <= 730 THEN 'Active (1-2y)'
                WHEN DATEDIFF(NOW(), created_at) <= 1095 THEN 'Mature (2-3y)'
                ELSE 'Aging (3y+)'
            END as lifecycle_stage,
            COUNT(*) as asset_count,
            AVG(CASE 
                WHEN `condition` = 'Excellent' THEN 100
                WHEN `condition` = 'Good' THEN 75
                WHEN `condition` = 'Fair' THEN 50
                WHEN `condition` = 'Poor' THEN 25
                WHEN `condition` = 'Non-Functional' THEN 0
            END) as avg_condition_score
        FROM assets
        WHERE status NOT IN ('Disposed', 'Archive')
        GROUP BY lifecycle_stage
        ORDER BY 
            CASE lifecycle_stage
                WHEN 'New (0-6m)' THEN 1
                WHEN 'Young (6-12m)' THEN 2
                WHEN 'Active (1-2y)' THEN 3
                WHEN 'Mature (2-3y)' THEN 4
                WHEN 'Aging (3y+)' THEN 5
            END
    ";
    
    $result = $conn->query($query);
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'stage' => $row['lifecycle_stage'],
            'count' => (int)$row['asset_count'],
            'avg_condition' => round($row['avg_condition_score'], 2)
        ];
    }
    
    return $data;
}

/**
 * Get critical assets requiring immediate attention
 */
function getCriticalAssets($conn) {
    $query = "
        SELECT 
            a.id,
            a.asset_tag,
            a.asset_name,
            a.condition,
            a.status,
            DATEDIFF(NOW(), a.created_at) as age_days,
            COUNT(i.id) as issue_count,
            MAX(i.created_at) as last_issue_date
        FROM assets a
        LEFT JOIN issues i ON i.component_asset_id = a.id
        WHERE a.status NOT IN ('Disposed', 'Archive')
        AND (
            a.condition IN ('Poor', 'Non-Functional')
            OR a.status = 'Under Maintenance'
        )
        GROUP BY a.id
        ORDER BY 
            FIELD(a.condition, 'Non-Functional', 'Poor', 'Fair', 'Good', 'Excellent'),
            issue_count DESC
        LIMIT 20
    ";
    
    $result = $conn->query($query);
    $assets = [];
    
    while ($row = $result->fetch_assoc()) {
        $assets[] = [
            'id' => $row['id'],
            'asset_tag' => $row['asset_tag'],
            'asset_name' => $row['asset_name'],
            'condition' => $row['condition'],
            'status' => $row['status'],
            'age_days' => (int)$row['age_days'],
            'issue_count' => (int)$row['issue_count'],
            'last_issue_date' => $row['last_issue_date']
        ];
    }
    
    return $assets;
}

/**
 * Predict which assets will fail next based on failure patterns
 * If a power supply of age X failed, predict similar power supplies will fail soon
 */
function getPredictedFailures($conn) {
    // Find recently failed or broken assets
    $query = "
        SELECT 
            a.asset_name,
            DATEDIFF(NOW(), a.created_at) as age_at_failure,
            COUNT(*) as failure_count
        FROM assets a
        WHERE (
            a.condition IN ('Poor', 'Non-Functional')
            OR a.status = 'Under Maintenance'
        )
        AND a.created_at >= DATE_SUB(NOW(), INTERVAL 2 YEAR)
        GROUP BY a.asset_name, age_at_failure
        HAVING failure_count >= 1
        ORDER BY failure_count DESC
    ";
    
    $result = $conn->query($query);
    $failure_patterns = [];
    
    while ($row = $result->fetch_assoc()) {
        $failure_patterns[] = [
            'asset_name' => $row['asset_name'],
            'age_at_failure' => (int)$row['age_at_failure'],
            'failure_count' => (int)$row['failure_count']
        ];
    }
    
    // Now find similar assets that might fail soon
    $predictions = [];
    
    foreach ($failure_patterns as $pattern) {
        $asset_name = $pattern['asset_name'];
        $age_at_failure = $pattern['age_at_failure'];
        
        // Find similar assets within 20% age range of when the pattern asset failed
        $min_age = $age_at_failure * 0.8;
        $max_age = $age_at_failure * 1.2;
        
        $query = "
            SELECT 
                a.id,
                a.asset_tag,
                a.asset_name,
                a.condition,
                DATEDIFF(NOW(), a.created_at) as current_age,
                COUNT(i.id) as issue_count
            FROM assets a
            LEFT JOIN issues i ON i.component_asset_id = a.id
            WHERE a.asset_name = ?
            AND a.condition NOT IN ('Poor', 'Non-Functional')
            AND a.status NOT IN ('Disposed', 'Archive', 'Under Maintenance')
            AND DATEDIFF(NOW(), a.created_at) BETWEEN ? AND ?
            GROUP BY a.id
            ORDER BY current_age DESC
            LIMIT 10
        ";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            continue; // Skip this pattern if prepare fails
        }
        
        $stmt->bind_param('sii', $asset_name, $min_age, $max_age);
        $stmt->execute();
        $similar_result = $stmt->get_result();
        
        while ($similar = $similar_result->fetch_assoc()) {
            $risk_percentage = round((($similar['current_age'] - $min_age) / ($age_at_failure - $min_age)) * 100);
            $risk_percentage = min(100, max(0, $risk_percentage));
            
            $predictions[] = [
                'asset_tag' => $similar['asset_tag'],
                'asset_name' => $similar['asset_name'],
                'condition' => $similar['condition'],
                'current_age_days' => (int)$similar['current_age'],
                'similar_to_age' => $age_at_failure,
                'issue_count' => (int)$similar['issue_count'],
                'risk_percentage' => $risk_percentage,
                'reason' => "Similar {$asset_name}s failed at age " . round($age_at_failure / 30) . " months. This one is " . round($similar['current_age'] / 30) . " months old."
            ];
        }
        
        $stmt->close(); // Close the prepared statement
    }
    
    // Remove duplicates and sort by risk
    $unique_predictions = [];
    $seen_tags = [];
    
    foreach ($predictions as $pred) {
        if (!in_array($pred['asset_tag'], $seen_tags)) {
            $seen_tags[] = $pred['asset_tag'];
            $unique_predictions[] = $pred;
        }
    }
    
    // Sort by risk percentage descending
    usort($unique_predictions, function($a, $b) {
        return $b['risk_percentage'] - $a['risk_percentage'];
    });
    
    return array_slice($unique_predictions, 0, 15);
}

// Main response
try {
    $response = [
        'success' => true,
        'data' => [
            'asset_failure_risk' => getAssetFailureRisk($conn),
            'condition_degradation' => getConditionDegradationTrend($conn),
            'maintenance_forecast' => getMaintenanceForecast($conn),
            'lifecycle_predictions' => getAssetLifecyclePredictions($conn),
            'critical_assets' => getCriticalAssets($conn),
            'predicted_failures' => getPredictedFailures($conn)
        ],
        'generated_at' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate predictive analytics: ' . $e->getMessage()
    ]);
}

$conn->close();
