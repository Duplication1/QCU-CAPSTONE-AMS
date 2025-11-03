<?php
session_start();
require_once __DIR__ . '/../config/config.php'; // adjust if your config path differs

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    $_SESSION['error_message'] = 'You must be logged in to submit an issue.';
    header('Location: ../view/StudentFaculty/index.php');
    exit;
}

$category = $_POST['category'] ?? '';
$room = trim($_POST['room'] ?? '');
$terminal = trim($_POST['terminal'] ?? '');
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$priority = $_POST['priority'] ?? 'Medium';

// category-specific fields
$hardware_component = $_POST['hardware_component'] ?? null;
$hardware_component_other = $_POST['hardware_component_other'] ?? null;
$software_name = trim($_POST['software_name'] ?? null);
$network_issue_type = $_POST['network_issue_type'] ?? null;
$network_issue_type_other = $_POST['network_issue_type_other'] ?? null;
$laboratory_room = $_POST['laboratory_room'] ?? null;
$laboratory_concern_type = $_POST['laboratory_concern_type'] ?? null;
$laboratory_concern_other = $_POST['laboratory_concern_other'] ?? null;
$other_concern_category = $_POST['other_concern_category'] ?? null;
$other_concern_other = $_POST['other_concern_other'] ?? null;

// basic validation
$errors = [];
if (!in_array($category, ['hardware','software','network','laboratory','other'])) $errors[] = 'Invalid category.';
if ($title === '') {
    // Title is required for hardware, software, network
    if (in_array($category, ['hardware','software','network'])) {
        $errors[] = 'Issue title is required.';
    }
}
if (in_array($category, ['hardware','software','network'])) {
    if ($room === '' || $terminal === '') $errors[] = 'Room and terminal are required.';
}
if (!in_array($priority, ['Low','Medium','High'])) $priority = 'Medium';

// per-category required checks
if ($category === 'hardware') {
    if (empty($hardware_component) && empty($hardware_component_other)) $errors[] = 'Select or specify hardware component.';
}
if ($category === 'software') {
    if (empty($software_name)) $errors[] = 'Software name is required.';
}
if ($category === 'network') {
    if (empty($network_issue_type) && empty($network_issue_type_other)) $errors[] = 'Select or specify network issue type.';
}
if ($category === 'laboratory') {
    if (empty($laboratory_concern_type) && empty($laboratory_concern_other)) $errors[] = 'Select or specify laboratory concern type.';
}
if ($category === 'other') {
    if (empty($other_concern_category) && empty($other_concern_other)) $errors[] = 'Select or specify concern category.';
}

if ($errors) {
    $_SESSION['error_message'] = implode(' ', $errors);
    header('Location: ../view/StudentFaculty/index.php');
    exit;
}

// map fallback values
$hw_comp = $hardware_component ?: null;
$hw_comp_other = $hardware_component_other ?: null;
$sw_name = $software_name ?: null;
$net_type = $network_issue_type ?: null;
$net_type_other = $network_issue_type_other ?: null;
$lab_concern = $laboratory_concern_type ?: null;
$lab_concern_other = $laboratory_concern_other ?: null;
$other_cat = $other_concern_category ?: null;
$other_cat_other = $other_concern_other ?: null;

// Set default title for laboratory and other if empty
if ($category === 'laboratory' && empty($title)) {
    $title = ($lab_concern ?: 'Laboratory Concern') . ' - ' . date('Y-m-d H:i:s');
}
if ($category === 'other' && empty($title)) {
    $title = ($other_cat ?: 'Other Concern') . ' - ' . date('Y-m-d H:i:s');
}

try {
    // Build DSN from config.php constants
    $host = Config::get('DB_HOST', 'localhost');
    $name = Config::get('DB_NAME', 'ams_database');
    $username = Config::get('DB_USERNAME', 'root');
    $password = Config::get('DB_PASSWORD', '');
    
    $dsn = "mysql:host=" . $host . ";dbname=" . $name . ";charset=utf8mb4";
    $db = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $sql = "INSERT INTO issues
        (user_id, category, room, terminal, hardware_component, hardware_component_other, software_name, network_issue_type, network_issue_type_other, laboratory_concern_type, laboratory_concern_other, other_concern_category, other_concern_other, title, description, priority)
        VALUES
        (:user_id, :category, :room, :terminal, :hardware_component, :hardware_component_other, :software_name, :network_issue_type, :network_issue_type_other, :laboratory_concern_type, :laboratory_concern_other, :other_concern_category, :other_concern_other, :title, :description, :priority)";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':user_id' => $userId,
        ':category' => $category,
        ':room' => $room,
        ':terminal' => $terminal,
        ':hardware_component' => $hw_comp,
        ':hardware_component_other' => $hw_comp_other,
        ':software_name' => $sw_name,
        ':network_issue_type' => $net_type,
        ':network_issue_type_other' => $net_type_other,
        ':laboratory_concern_type' => $lab_concern,
        ':laboratory_concern_other' => $lab_concern_other,
        ':other_concern_category' => $other_cat,
        ':other_concern_other' => $other_cat_other,
        ':title' => $title,
        ':description' => $description,
        ':priority' => $priority
    ]);

    $_SESSION['success_message'] = 'Issue submitted successfully.';
    header('Location: ../view/StudentFaculty/index.php');
    exit;
} catch (PDOException $e) {
    // log error on server; do not reveal DB errors to users
    error_log('Submit Issue Error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'An unexpected error occurred while submitting the issue.';
    header('Location: ../view/StudentFaculty/index.php');
    exit;
}
?>