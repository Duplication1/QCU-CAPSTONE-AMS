<?php
session_start();
require_once '../config/config.php';
require_once '../model/Database.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || !in_array($_SESSION['role'], ['Student', 'Faculty', 'Laboratory Staff'])) {
    $_SESSION['error_message'] = "Unauthorized access.";
    header("Location: ../view/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Determine redirect URL based on role
$redirect_url = match($_SESSION['role']) {
    'Laboratory Staff' => '../view/LaboratoryStaff/e-signature.php',
    default => '../view/StudentFaculty/e-signature.php'
};

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get current signature filename
    $stmt = $conn->prepare("SELECT e_signature FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $signature_file = $stmt->fetchColumn();
    
    if ($signature_file) {
        // Delete file from server
        $file_path = '../uploads/signatures/' . $signature_file;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Update database
        $stmt = $conn->prepare("UPDATE users SET e_signature = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $_SESSION['success_message'] = "E-signature removed successfully.";
    } else {
        $_SESSION['error_message'] = "No signature found to remove.";
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Failed to remove e-signature.";
}

header("Location: $redirect_url");
exit();
?>
