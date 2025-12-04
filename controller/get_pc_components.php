<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config/config.php';

try {
    $dbConfig = Config::database();
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    // Get PC unit ID from request
    $pc_id = isset($_GET['pc_id']) ? intval($_GET['pc_id']) : 0;
    
    if ($pc_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid PC ID']);
        exit();
    }
    
    // Fetch components for this PC unit
    $query = "SELECT id, asset_name, asset_tag, brand, model, category 
              FROM assets 
              WHERE pc_unit_id = ? 
              AND status NOT IN ('Disposed', 'Archive', 'Lost') 
              ORDER BY asset_name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $pc_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $components = [];
    while ($row = $result->fetch_assoc()) {
        $components[] = [
            'id' => $row['id'],
            'name' => $row['asset_name'],
            'asset_tag' => $row['asset_tag'],
            'brand' => $row['brand'],
            'model' => $row['model'],
            'category' => $row['category']
        ];
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'components' => $components
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_pc_components.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching PC components'
    ]);
}
