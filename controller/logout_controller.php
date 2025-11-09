<?php
session_start();

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

// Redirect to login page
header("Location: ../view/login.php");
exit();
?>
