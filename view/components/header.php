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
$notification_count = $_SESSION['notification_count'] ?? 0;
$current_role = $_SESSION['role'] ?? 'Student';
$role_title = $role_titles[$current_role] ?? 'Student Portal';

$role_colors = [
    'Administrator' => 'text-red-300',
    'LaboratoryStaff' => 'text-yellow-300',
    'Laboratory Staff' => 'text-yellow-300',
    'Student' => 'text-blue-300',
    'Faculty' => 'text-green-300',
    'Technician' => 'text-purple-300'
];

$role_color_class = $role_colors[$current_role] ?? 'text-blue-300';


$user_name = $_SESSION['full_name'] ?? 'User';
$user_initial = strtoupper(substr($user_name, 0, 1));
?>
            
<!-- ========== HEADER ========== -->
<header class="fixed top-0 left-0 w-full z-30 bg-[#1E3A8A] text-white shadow-md h-[85px]">
  <div class="flex items-center justify-between h-full px-4 md:px-6">

    <!-- Left: Burger Menu + Logo + oneQCU -->
    <div class="flex items-center space-x-2 md:space-x-4">
      <!-- Burger Menu Button -->
      <button id="sidebar-toggle" class="p-2 rounded-lg hover:bg-white/10 transition-all duration-300">
        <i id="toggle-icon" class="fa-solid fa-bars text-lg md:text-xl"></i>
      </button>

      <!-- Logo + Text -->
      <div class="flex items-center gap-1">
        <img src="../../assets/images/QCU-LOGO.png" alt="QCU Logo" class="w-10 h-10 md:w-12 md:h-12">
        <div class="text-xl md:text-2xl font-bold font-[Poppins] leading-none whitespace-nowrap">
          <span class="text-white">one</span><span class="text-[#F87171]">Q</span><span class="text-[#60A5FA]">C</span><span class="text-[#FACC15]">U</span>
        </div>
      </div>
    </div>

<!-- Right: Search Bar + User Info -->
<div class="flex items-center gap-4 md:gap-6">

  <!-- Search Bar - Hidden on mobile -->
  <form action="search.php" method="GET" class="relative w-64 hidden md:block">
    <input type="text" name="query" placeholder="Search..." 
           class="w-full px-4 py-2 rounded-full border border-white/30 focus:outline-none focus:ring-2 focus:ring-gray-300 bg-white/80 text-gray-800 text-sm placeholder-gray-500">
    <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-[#0A39D2]">
      <i class="fas fa-search"></i>
    </button>
  </form>

<!-- User Info: Avatar + Name + Role + Notifications -->
<div class="relative group flex items-center gap-3">

  <!-- Avatar with notification badge -->
  <button id="avatar-button" class="relative w-8 h-8 md:w-10 md:h-10 bg-gradient-to-r from-blue-600 to-blue-700 rounded-full flex items-center justify-center focus:outline-none" title="You have <?php echo $notification_count; ?> new notification<?php echo $notification_count !== 1 ? 's' : ''; ?>">
    <span class="text-white text-sm font-semibold">
      <?php echo $user_initial; ?>
    </span>

    <?php if ($notification_count > 0): ?>
    <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow-md">
      <?php echo $notification_count; ?>
    </div>
    <?php endif; ?>
  </button>

  <!-- Name + Role -->
  <div class="hidden md:flex flex-col leading-tight text-white">
    <span class="text-sm font-medium truncate max-w-[120px]" title="<?php echo htmlspecialchars($user_name); ?>">
      <?php echo htmlspecialchars($user_name); ?>
    </span>
    <span class="text-xs <?php echo $role_color_class; ?>">
      <?php echo htmlspecialchars($role_title); ?>
    </span>
  </div>

  <!-- Dropdown -->
  <div id="notification-dropdown" class="absolute right-0 top-12 w-64 bg-white text-gray-800 rounded-lg shadow-lg border border-gray-200 hidden group-focus-within:block z-50">
    <div class="p-3 border-b font-semibold text-sm text-gray-700">Notifications</div>
    <ul class="max-h-64 overflow-y-auto text-sm">
      <li class="px-4 py-2 hover:bg-gray-100">No new notifications</li>
      <!-- You can loop through actual notifications here -->
    </ul>
  </div>

</div>

</div>

  </div>

  <script>
    document.addEventListener('click', function (e) {
  const avatarBtn = document.getElementById('avatar-button');
  const dropdown = document.getElementById('notification-dropdown');

  if (avatarBtn.contains(e.target)) {
    dropdown.classList.toggle('hidden');
  } else {
    dropdown.classList.add('hidden');
  }
});

  </script>
</header>




