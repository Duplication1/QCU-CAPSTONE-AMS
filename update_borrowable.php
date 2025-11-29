<?php
require_once 'config/config.php';
require_once 'model/Database.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->exec('UPDATE assets SET is_borrowable = 1 WHERE asset_name LIKE "%MOUSE%" AND status = "Available" LIMIT 10');
    $conn->exec('UPDATE assets SET is_borrowable = 1 WHERE asset_name LIKE "%KEYBOARD%" AND status = "Available" LIMIT 5');
    echo 'Updated some assets to borrowable' . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>