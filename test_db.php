<?php
require_once 'config/config.php';
require_once 'model/Database.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query('SELECT COUNT(*) as count FROM assets WHERE is_borrowable = 1');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Borrowable assets: ' . $result['count'] . PHP_EOL;
    
    $stmt2 = $conn->query('SELECT COUNT(*) as count FROM assets');
    $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    echo 'Total assets: ' . $result2['count'] . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>