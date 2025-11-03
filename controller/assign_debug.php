<?php
header('Content-Type: application/json; charset=utf-8');
if (file_exists(__DIR__ . '/../config/config.php')) require_once __DIR__ . '/../config/config.php';
if (!isset($conn) || !$conn) {
    $conn = new mysqli('127.0.0.1','root','','ams_database');
}
if ($conn->connect_error) {
    echo json_encode(['ok'=>false,'error'=>'DB connect failed: '.$conn->connect_error]);
    exit;
}
$res1 = $conn->query("SHOW TABLES LIKE 'issues'");
$res2 = $conn->query("SHOW COLUMNS FROM `issues` LIKE 'assigned_group'");
echo json_encode([
    'ok'=>true,
    'has_issues_table'=>($res1 && $res1->num_rows>0),
    'has_assigned_group_column'=>($res2 && $res2->num_rows>0),
    'php_version'=>phpversion()
]);