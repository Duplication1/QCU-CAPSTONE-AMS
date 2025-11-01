<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli('localhost', 'root', '', 'ams_database');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['error_message'] = 'You must be logged in.';
    header('Location: ../view/StudentFaculty/index.php');
    exit;
}

// Check POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Invalid request method.';
    header('Location: ../view/StudentFaculty/index.php');
    exit;
}

// Get user info
$userId = $_SESSION['user_id'] ?? null;
$requesterName = $_SESSION['full_name'] ?? 'Unknown';

// Get form data
$room = trim($_POST['room'] ?? '');
$terminal = trim($_POST['terminal'] ?? '');
$networkIssueType = trim($_POST['network_issue_type'] ?? '');
$networkIssueTypeOther = trim($_POST['network_issue_type_other'] ?? '');
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$priority = trim($_POST['priority'] ?? 'Medium');

// If "Others" is selected, use the custom input
if ($networkIssueType === 'Others' && !empty($networkIssueTypeOther)) {
    $networkIssueType = $networkIssueTypeOther;
}

// Validate
if (empty($room) || empty($terminal) || empty($title)) {
    $_SESSION['error_message'] = 'Please fill in all required fields.';
    header('Location: ../view/StudentFaculty/index.php');
    exit;
}

// Validate priority
if (!in_array($priority, ['Low', 'Medium', 'High'])) {
    $priority = 'Medium';
}

$issueType = 'Network';
$status = 'Open';
$titleWithType = !empty($networkIssueType) ? $networkIssueType . ' - ' . $title : $title;

// Insert query
$sql = "INSERT INTO hardware_issues (user_id, requester_name, room, terminal, title, description, priority, issue_type, submitted_by, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log('Prepare failed: ' . $conn->error);
    $_SESSION['error_message'] = 'Database error: ' . $conn->error;
    header('Location: ../view/StudentFaculty/index.php');
    exit;
}

$stmt->bind_param('isssssssss', $userId, $requesterName, $room, $terminal, $titleWithType, $description, $priority, $issueType, $requesterName, $status);

if ($stmt->execute()) {
    $_SESSION['success_message'] = 'Network issue submitted successfully!';
    error_log('Network issue submitted: ID=' . $stmt->insert_id);
} else {
    error_log('Execute failed: ' . $stmt->error);
    $_SESSION['error_message'] = 'Failed to submit: ' . $stmt->error;
}

$stmt->close();
$conn->close();

header('Location: ../view/StudentFaculty/index.php');
exit;
?>