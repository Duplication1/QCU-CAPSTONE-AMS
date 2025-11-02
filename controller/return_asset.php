<?php
session_start();
require_once '../config/config.php';
require_once '../model/AssetBorrowing.php';

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    $_SESSION['error_message'] = 'Unauthorized access';
    header("Location: ../view/LaboratoryStaff/borrowing.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Invalid request method';
    header("Location: ../view/LaboratoryStaff/borrowing.php");
    exit();
}

$borrowing_id = $_POST['borrowing_id'] ?? null;
$returned_condition = $_POST['returned_condition'] ?? null;
$return_notes = $_POST['return_notes'] ?? null;

if (!$borrowing_id || !$returned_condition) {
    $_SESSION['error_message'] = 'Borrowing ID and returned condition are required';
    header("Location: ../view/LaboratoryStaff/borrowing.php");
    exit();
}

try {
    $borrowing = new AssetBorrowing();
    
    if ($borrowing->returnAsset($borrowing_id, $returned_condition, $return_notes)) {
        $_SESSION['success_message'] = 'Asset marked as returned successfully';
    } else {
        $_SESSION['error_message'] = 'Failed to mark asset as returned';
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error processing return: ' . $e->getMessage();
}

header("Location: ../view/LaboratoryStaff/borrowing.php");
exit();
?>
