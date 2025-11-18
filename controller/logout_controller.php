<?php
session_start();

// Store user role before destroying session
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

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

// Redirect based on user role
$employeeRoles = ['Administrator', 'Technician', 'Laboratory Staff', 'Faculty'];
if ($userRole === 'Student') {
    header("Location: ../view/student_login.php");
} elseif (in_array($userRole, $employeeRoles)) {
    header("Location: ../view/employee_login.php");
} else {
    // Default to student login if role is unknown
    header("Location: ../view/student_login.php");
}
exit();
?>
