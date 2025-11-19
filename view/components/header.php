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
<header class="fixed top-0 left-0 w-full z-30 bg-[#1E3A8A] text-white shadow-md h-[85px]">
  <div class="flex items-center justify-between h-full px-6">

    <!-- Left: Logo + oneQCU -->
    <div class="flex items-center space-x-4">
      <img src="../../assets/images/QCU-LOGO.png" alt="QCU Logo" class="w-12 h-12">
      <div class="text-2xl font-bold font-[Poppins] leading-none whitespace-nowrap">
        <span class="text-white">one</span>
        <span class="text-[#F87171]">Q</span>
        <span class="text-[#60A5FA]">C</span>
        <span class="text-[#FACC15]">U</span>
      </div>
    </div>

    <!-- Right: Search Bar + User Info -->
    <div class="flex items-center space-x-6">

      <!-- Search Bar -->
      <form action="search.php" method="GET" class="relative w-64">
        <input type="text" name="query" placeholder="Search..." 
               class="w-full px-4 py-2 rounded-full border border-white/30 focus:outline-none focus:ring-2 focus:ring-yellow-300 bg-white/80 text-gray-800 text-sm placeholder-gray-500">
        <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-[#0A39D2]">
          <i class="fas fa-search"></i>
        </button>
      </form>

    <!-- User Info -->
    <div class="flex items-center space-x-4">

    <?php if (in_array($current_role, ['Student', 'Faculty'])): ?>
        
    <!-- Dark Mode Toggle (Student/Faculty only) -->
    <button id="dark-mode-toggle" title="Toggle dark mode"
          class="p-2 rounded-lg text-white hover:bg-white/20 transition-colors duration-200 hidden">
    <i id="dark-mode-icon" class="fa-solid fa-moon text-lg"></i>
    </button>
    <?php endif; ?>

    <!-- Avatar + Role Tooltip -->
    <div class="relative group">
        <div class="w-9 h-9 bg-gradient-to-r from-blue-600 to-blue-700 rounded-full flex items-center justify-center cursor-pointer">
            <span class="text-white text-sm font-semibold"><?php echo $user_initial; ?></span>
        </div>
    <div class="absolute top-full mt-2 left-1/2 transform -translate-x-1/2 bg-white text-gray-800 text-xs px-2 py-1 rounded shadow-md opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap z-50">
      Hi! <?php echo htmlspecialchars($current_role); ?>
    </div>
  </div>

</div>

    </div>
  </div>

  <script>
  const toggle = document.getElementById('dark-mode-toggle');
  if (toggle) {
    toggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
    });
  }
</script>

</header>