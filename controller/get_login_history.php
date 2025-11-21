<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config/config.php';

$user_id = $_SESSION['user_id'];

// Database connection
$dbConfig = Config::database();
try {
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if login_history table exists, if not create it
$tableCheck = $conn->query("SHOW TABLES LIKE 'login_history'");
if ($tableCheck->num_rows === 0) {
    // Create the table
    $createTable = "
    CREATE TABLE `login_history` (
        `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` int(10) UNSIGNED NOT NULL,
        `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
        `ip_address` varchar(45) DEFAULT NULL,
        `user_agent` text DEFAULT NULL,
        `device_type` varchar(20) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `login_time` (`login_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if (!$conn->query($createTable)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create login history table']);
        exit();
    }
}

// Get login history for the user (last 20 logins)
$query = "SELECT id, login_time, ip_address, user_agent, device_type 
          FROM login_history 
          WHERE user_id = ? 
          ORDER BY login_time DESC 
          LIMIT 20";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = [
        'id' => $row['id'],
        'login_time' => $row['login_time'],
        'ip_address' => $row['ip_address'],
        'user_agent' => $row['user_agent'],
        'device_type' => $row['device_type'] ?? 'desktop'
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'history' => $history
]);
?>
