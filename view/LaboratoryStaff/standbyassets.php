<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has Laboratory Staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';

include '../components/layout_header.php';
?>

<style>
html, body {
    height: 100vh;
    overflow: hidden;
}
#app-container {
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
main {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    padding: 0.5rem;
    background-color: #f9fafb;
}
</style>

<main>
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white rounded shadow-sm border border-gray-200 mb-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Stand By Assets</h3>
                <p class="text-xs text-gray-500 mt-0.5">View assets on standby</p>
            </div>
        </div>

        <!-- Content Area -->
        <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200 p-4">
            <div class="text-center py-12 text-gray-500">
                <i class="fa-solid fa-clock text-6xl mb-4 opacity-30"></i>
                <p class="text-lg">Stand By Assets</p>
                <p class="text-sm">This section is under development</p>
            </div>
        </div>
    </div>
</main>

<?php include '../components/layout_footer.php'; ?>
