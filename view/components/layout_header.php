<?php
/**
 * Main Layout Wrapper Component
 * 
 * This component provides the complete layout structure including:
 * - HTML document structure
 * - Sidebar navigation
 * - Header
 * - Main content wrapper
 * - JavaScript functionality
 * 
 * Usage: 
 * 1. Include this at the start of your page after session_start() and role checks
 * 2. Add your main content after including this
 * 3. Include 'components/layout_footer.php' at the end
 */

// Ensure session is started and user is authenticated
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

$current_role = $_SESSION['role'] ?? 'Student';
$page_title = "AMS - " . $current_role . " Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="min-h-screen bg-gray-50">
    
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <!-- Main Content Wrapper -->
    <div id="main-wrapper" class="lg:ml-64 transition-all duration-300 ease-in-out">
        
        <?php include __DIR__ . '/header.php'; ?>
        
        <!-- Main Content Container -->
        <div id="main-content-container">
            <!-- Content will be added here by individual pages -->