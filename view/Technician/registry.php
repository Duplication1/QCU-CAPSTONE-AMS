<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has technician role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Technician') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
include '../components/layout_header.php';
?>

        <!-- Main Content -->
        <main class="p-6">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Asset Registry</h2>
                <p class="text-gray-600">Asset registry content will be displayed here.</p>
            </div>
        </main>

<?php include '../components/layout_footer.php'; ?>
