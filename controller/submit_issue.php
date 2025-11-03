<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php'; // adjust path

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success'=>false,'message'=>'Only POST allowed']); exit;
}
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
  echo json_encode(['success'=>false,'message'=>'Not logged in']); exit;
}

// validate inputs (example)
$category = $_POST['category'] ?? '';
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$room = $_POST['room'] ?? null;

if ($category==='' || $title==='') {
  echo json_encode(['success'=>false,'message'=>'Missing required fields']); exit;
}

// insert into issues
$stmt = $conn->prepare("INSERT INTO issues (category, title, description, room, terminal, priority, status, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Open', ?, NOW())");
if (!$stmt) { echo json_encode(['success'=>false,'message'=>'DB prepare failed']); exit; }
$user_id = $_SESSION['user_id'] ?? null;
$terminal = $_POST['terminal'] ?? null;
$priority = $_POST['priority'] ?? 'Medium';
$stmt->bind_param('sssssis', $category, $title, $description, $room, $terminal, $priority, $user_id);
$stmt->execute();
$id = $stmt->insert_id;
$stmt->close();

echo json_encode(['success'=>true,'ticket_id'=>$id,'message'=>'Issue submitted']);
exit;
?>