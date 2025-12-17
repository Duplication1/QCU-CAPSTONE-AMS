<?php
session_start();

// Store user data before destroying session
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Log logout activity for Laboratory Staff and Technician
if (($userRole === 'Laboratory Staff' || $userRole === 'Technician') && $userId) {
    require_once '../config/config.php';
    require_once '../model/Database.php';
    require_once '../model/ActivityLog.php';
    
    try {
        ActivityLog::record(
            $userId,
            'logout',
            'user',
            null,
            'User logged out from ' . $userRole . ' panel'
        );
    } catch (Exception $e) {
        error_log('Failed to log logout activity: ' . $e->getMessage());
    }
}

// Destroy all session data
session_unset();
session_destroy();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Start a new session to store the logout message
session_start();
$_SESSION['success'] = "You have been successfully logged out.";

// Redirect to unified login page
header("Location: ../view/login.php");
exit();
?>
