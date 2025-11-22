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

// Prevent page caching to avoid back button access after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <!-- Include Poppins font in your <head> if not already included -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&display=swap" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.tailwind.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Dark mode overrides for Student/Faculty (applies when body has .dark-mode) -->
    <style>
        /* Ensure main background and text colors flip to dark/white */
        body.dark-mode {
            background-color: #0b1220 !important; /* dark background */
            color: #ffffff !important;
        }

        /* Force common text utility classes to white when dark-mode is active */
        body.dark-mode, 
        body.dark-mode p, 
        body.dark-mode span, 
        body.dark-mode a, 
        body.dark-mode label, 
        body.dark-mode h1, 
        body.dark-mode h2, 
        body.dark-mode h3, 
        body.dark-mode h4, 
        body.dark-mode h5, 
        body.dark-mode h6, 
        body.dark-mode th, 
        body.dark-mode td {
            color: #ffffff !important;
        }

        /* Neutralize strong dark text classes used in templates */
        body.dark-mode .text-gray-900,
        body.dark-mode .text-gray-800,
        body.dark-mode .text-gray-700,
        body.dark-mode .text-gray-600,
        body.dark-mode .text-gray-500,
        body.dark-mode .text-gray-400,
        body.dark-mode .text-gray-300 {
            color: #ffffff !important;
        }

        /* Make light backgrounds darker so white text is readable */
        body.dark-mode .bg-white { background-color: #071127 !important; }
        body.dark-mode .bg-gray-50 { background-color: #071127 !important; }
        body.dark-mode .bg-gray-100 { background-color: #0b1530 !important; }

        /* Adjust borders */
        body.dark-mode .border-gray-200 { border-color: rgba(255,255,255,0.08) !important; }
        body.dark-mode .border-gray-300 { border-color: rgba(255,255,255,0.06) !important; }

        /* Inputs and selects need readable background */
        body.dark-mode input, body.dark-mode select, body.dark-mode textarea {
            background-color: #071127 !important;
            color: #fff !important;
            border-color: rgba(255,255,255,0.08) !important;
        }

        /* Toggle button style when active */
        #dark-mode-toggle.active { background-color: rgba(255,255,255,0.06); color: #fff; }

        /* Component-specific tweaks for better contrast */
        /* Header / Top bar */
        body.dark-mode header { background: linear-gradient(90deg,#062033,#0a1930) !important; border-color: rgba(255,255,255,0.06) !important; }

        /* Sidebar adjustments */
        body.dark-mode #sidebar { background-color: #071127 !important; border-right-color: rgba(255,255,255,0.04) !important; }
        body.dark-mode #sidebar .nav-text { color: #e6eef8 !important; }

        /* DataTables styling */
        body.dark-mode .dataTables_wrapper .dataTables_filter input,
        body.dark-mode .dataTables_wrapper .dataTables_length select {
            background-color: #071127 !important;
            color: #fff !important;
            border-color: rgba(255,255,255,0.06) !important;
        }

        body.dark-mode #assetsTable thead th,
        body.dark-mode #requestsTable thead th {
            background-color: #0f1724 !important;
            color: #fff !important;
            border-bottom-color: rgba(255,255,255,0.06) !important;
        }

        body.dark-mode #assetsTable tbody td,
        body.dark-mode #requestsTable tbody td {
            border-bottom-color: rgba(255,255,255,0.03) !important;
            color: #e6eef8 !important;
        }

        /* Prevent bright white hover in borrowing assets table inside modal */
        /* Prevent bright white hover in borrowing assets table inside modal */
        /* Target both rows and cells, and make selector specific to the borrowing modal and DataTables */
        body.dark-mode #borrowingModal #assetsTable tbody tr:hover,
        body.dark-mode #borrowingModal #assetsTable tbody tr:hover td,
        body.dark-mode .dataTables_wrapper #assetsTable tbody tr:hover,
        body.dark-mode .dataTables_wrapper #assetsTable tbody tr:hover td,
        body.dark-mode #assetsTable.display tbody tr:hover,
        body.dark-mode #assetsTable.display tbody tr:hover td {
            background-color: rgba(255,255,255,0.02) !important; /* subtle highlight */
            color: #ffffff !important;
        }

        /* Selected / highlighted asset row should use a muted amber (not bright white) */
        body.dark-mode #assetsTable tbody tr.bg-yellow-100,
        body.dark-mode #assetsTable tbody tr.selected,
        body.dark-mode #assetsTable tbody tr.border-l-4 {
            background-color: rgba(180,83,9,0.12) !important; /* muted amber */
            border-left-color: #b45309 !important;
            color: #fff !important;
        }

        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #c2410c !important; /* keep a warm accent for current page */
            color: white !important;
            border-color: #c2410c !important;
        }

        /* Yellow accents: keep them readable on dark background */
        body.dark-mode .bg-yellow-50, body.dark-mode .bg-yellow-100, body.dark-mode .bg-yellow-200 {
            background-color: rgba(180,83,9,0.12) !important; /* muted yellow */
            color: #fffbeb !important;
        }
        body.dark-mode .bg-yellow-600, body.dark-mode .bg-yellow-500 {
            background-color: #b45309 !important; /* darker amber */
            color: #fff !important;
        }
        body.dark-mode .text-yellow-800, body.dark-mode .text-yellow-700 {
            color: #ffefcf !important;
        }

        /* Status badges (ensure readable backgrounds) */
        body.dark-mode .bg-green-100 { background-color: rgba(16,185,129,0.12) !important; color: #bbf7d0 !important; }
        body.dark-mode .bg-red-100 { background-color: rgba(239,68,68,0.08) !important; color: #ffd6d6 !important; }
        body.dark-mode .bg-blue-100 { background-color: rgba(59,130,246,0.08) !important; color: #dbeafe !important; }

        /* Modal backgrounds and cards */
        body.dark-mode .modal, body.dark-mode .bg-white, body.dark-mode .rounded-lg, body.dark-mode .rounded-xl {
            background-color: #071127 !important;
            color: #e6eef8 !important;
            box-shadow: 0 6px 18px rgba(2,6,23,0.6) !important;
        }

        /* Printable document preview card */
        body.dark-mode #printableDocument {
            background-color: #071127 !important;
            border-color: rgba(255,255,255,0.06) !important;
            color: #fff !important;
        }

        /* Forms, selects and inputs contrast */
        body.dark-mode select, body.dark-mode input[type="date"], body.dark-mode input[type="text"], body.dark-mode textarea {
            background: #071127 !important;
            color: #fff !important;
            box-shadow: none !important;
        }

        /* Date picker: make the input appear brown in dark mode for better visual grouping */
        body.dark-mode input[type="date"] {
            background-color: #8B5A2B !important; /* brown */
            color: #fff !important;
            border-color: rgba(255,255,255,0.06) !important;
        }

        /* WebKit: colorize the calendar picker indicator so it looks good on brown background */
        body.dark-mode input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1) sepia(1) saturate(2) hue-rotate(-10deg) brightness(0.9) !important;
        }

        /* WebKit: ensure editable date parts are readable */
        body.dark-mode input[type="date"]::-webkit-datetime-edit, 
        body.dark-mode input[type="date"]::-webkit-datetime-edit-year-field,
        body.dark-mode input[type="date"]::-webkit-datetime-edit-month-field,
        body.dark-mode input[type="date"]::-webkit-datetime-edit-day-field {
            color: #fff !important;
        }

        /* Progress / step indicators */
        body.dark-mode #step1Indicator, body.dark-mode #step2Indicator, body.dark-mode #step3Indicator {
            box-shadow: 0 2px 6px rgba(0,0,0,0.4) inset;
        }

        /* Ensure links keep some accent color */
        body.dark-mode a { color: #dbeafe !important; }

        /* Minor: make dashed borders visible */
        body.dark-mode .border-dashed { border-color: rgba(255,255,255,0.04) !important; }

        /* Modal-specific tweaks */
        /* Darken modal overlays so content stands out */

        /* Common modal card adjustments */
        body.dark-mode #issueModal .rounded-lg,
        body.dark-mode #issueModal .bg-white,
        body.dark-mode #borrowingModal .rounded-lg,
        body.dark-mode #borrowingModal .bg-white,
        body.dark-mode #requestDetailsModal .rounded-lg,
        body.dark-mode #requestDetailsModal .bg-white {
            background-color: #071127 !important;
            color: #e6eef8 !important;
            border-color: rgba(255,255,255,0.06) !important;
        }

        /* Modal overlay (backdrop) darker in dark mode */
        body.dark-mode #issueModal > .absolute.inset-0,
        body.dark-mode #requestDetailsModal.fixed.inset-0,
        body.dark-mode #borrowingModal.fixed.inset-0 {
            background-color: rgba(2,6,23,0.7) !important;
        }

        /* Modal headers: keep strong accent but slightly darker for contrast */
        body.dark-mode #borrowingModal .bg-yellow-600 { background-color: #92400e !important; }
        body.dark-mode #issueModal .text-lg, body.dark-mode #requestDetailsModal .text-xl { color: #fff !important; }

        /* Modal buttons: cancel/confirm contrast */
        body.dark-mode #issueModal .bg-gray-200, body.dark-mode #borrowingModal .bg-gray-300 {
            background-color: rgba(255,255,255,0.04) !important;
            color: #e6eef8 !important;
        }
        body.dark-mode #issueModal button.bg-blue-600, body.dark-mode #borrowingModal button.bg-yellow-600 {
            box-shadow: none !important;
            border: 1px solid rgba(255,255,255,0.06) !important;
        }

        /* Make dashed signature and upload boxes visible */
        body.dark-mode .border-dashed { border-color: rgba(255,255,255,0.06) !important; }

        /* Progress steps and progress bars in borrowing modal */
        /* Use a light amber indicator when dark mode is active */
        body.dark-mode #step1Indicator, body.dark-mode #step2Indicator, body.dark-mode #step3Indicator {
            background-color: #FFEFCF !important; /* user requested color */
            color: #0b1220 !important; /* dark text for contrast */
            border: 1px solid rgba(0,0,0,0.12) !important;
        }
        body.dark-mode #progress1, body.dark-mode #progress2 { background-color: #b45309 !important; }

        /* Terms container scroll area */
        body.dark-mode #termsContainer { background-color: #071127 !important; color: #e6eef8 !important; border-color: rgba(255,255,255,0.04) !important; }

        /* Request Details modal content */
        body.dark-mode #requestDetailsContent { background: transparent !important; color: #e6eef8 !important; }
        body.dark-mode #requestDetailsContent .font-semibold { color: #fff !important; }

        /* Printable document inside modal: ensure table/lines remain visible */
        body.dark-mode #printableDocument { box-shadow: none !important; border-color: rgba(255,255,255,0.06) !important; }

        /* Small helpers: ensure modal icons are visible */
        body.dark-mode .fa-spinner, body.dark-mode .fa-exclamation-triangle, body.dark-mode .fa-info-circle { color: #ffd580 !important; }
    </style>
    
    <!-- Prevent back button after logout -->
    <script>
        // Disable back button by manipulating browser history
        (function() {
            if (window.history && window.history.pushState) {
                // Add a dummy state
                window.history.pushState(null, null, window.location.href);
                
                // Listen for back button
                window.addEventListener('popstate', function() {
                    // Push state again and redirect to login
                    window.history.pushState(null, null, window.location.href);
                    window.location.href = '../login.php';
                });
            }
            
            // Detect if page was loaded from cache (back/forward button)
            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    // Page loaded from cache, force reload to trigger session check
                    window.location.reload();
                }
            });
        })();
    </script>
</head>
<body class="min-h-screen bg-gray-100" data-role="<?php echo htmlspecialchars($current_role); ?>">
    
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <!-- Main Content Wrapper -->
    <div id="main-wrapper" class="ml-0 lg:ml-20 lg:peer-hover:ml-[220px] pt-[90px] transition-all duration-300 ease-in-out">
        
        <?php include __DIR__ . '/header.php'; ?>
        
        <!-- Main Content Container -->
        <div id="main-content-container">
            <!-- Content will be added here by individual pages -->