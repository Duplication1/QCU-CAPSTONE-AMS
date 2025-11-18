<?php
/**
 * Reusable Header Component
 * 
 * This component generates the top navigation header that appears across all role dashboards.
 * It includes the mobile menu button, logo, title, and user information.
 * 
 * Usage: include 'components/header.php';
 * Make sure $_SESSION is available before including this component.
 */

// Define titles for each role
$role_titles = [
    'Administrator' => 'Administrator Panel',
    'LaboratoryStaff' => 'Laboratory Staff Panel',
    'Laboratory Staff' => 'Laboratory Staff Panel', // Handle both formats
    'Student' => 'Student Portal',
    'Faculty' => 'Faculty Portal',
    'Technician' => 'Technician Panel'
];

$current_role = $_SESSION['role'] ?? 'Student';
$role_title = $role_titles[$current_role] ?? 'Student Portal';
$user_name = $_SESSION['full_name'] ?? 'User';
$user_initial = strtoupper(substr($user_name, 0, 1));
?>
            
<!-- ========== HEADER ========== -->
<header class="fixed top-0 left-0 w-full z-30 border-b bg-white/20 backdrop-blur-lg"
        style="border-color:#D6D6D6; height:100px;">

    <!-- Gradient + Blur Layer -->
    <div class="absolute inset-0"
         style="
            background: linear-gradient(90deg, rgba(13,53,255,0.6) 28%, rgba(255,249,87,0.4) 52%, rgba(255,0,0,0.5) 100%);
            filter: blur(2px);
            box-shadow: inset 0 4px 4px rgba(0,0,0,0.25);
         ">
    </div>

    <!-- Content Layer -->
    <div class="relative h-full">

        <!-- QCU Logo Absolute Positioned -->
        <img src="../../assets/images/QCU-LOGO.png" alt="QCU Logo" style="position: absolute; left: 699px; top: 15px; width: 43px; height: 43px;">

        <!-- oneQCU Text Absolute Positioned -->
        <div style="position: absolute; left: 665px; top: 60px; /* moved 5px lower for spacing */ width: 132px; height: 40px; font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 28px; line-height: 1; letter-spacing: 0; white-space: nowrap;">
        <span style="color: #504848;">one</span><span style="color: #E21414;">Q</span><span style="color: #0A39D2;">C</span><span style="color: #F0CB36;">U</span>
        </div>

</header>


