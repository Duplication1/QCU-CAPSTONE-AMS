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
    default => '../view/StudentFaculty/profile.php'
};

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if user has a signature
    $stmt = $conn->prepare("SELECT e_signature FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $signature_data = $stmt->fetchColumn();
    
    if ($signature_data) {
        // Update database to remove signature
        $stmt = $conn->prepare("UPDATE users SET e_signature = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Log the signature deletion
        try {
            require_once '../model/ActivityLog.php';
            ActivityLog::record(
                $user_id,
                'delete',
                'signature',
                $user_id,
                'Deleted e-signature'
            );
        } catch (Exception $logError) {
            error_log('Failed to log signature deletion: ' . $logError->getMessage());
        }
        
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
