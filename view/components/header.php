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

<!-- Right: User Info + Notifications -->
<div class="flex items-center gap-4 md:gap-6">

<!-- Notification Icon with Dropdown -->
<div class="relative group">
<button id="notification-button" class="p-2 rounded-full hover:bg-white/10 text-white text-lg md:text-xl focus:outline-none relative" title="Notifications">
    <i class="fa-solid fa-bell"></i>
    <?php if ($notification_count > 0): ?>
    <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow-md">
      <?php echo $notification_count; ?>
    </div>
    <?php endif; ?>
  </button>

  <!-- Notification Dropdown -->
  <div id="notification-dropdown" class="absolute right-0 top-10 w-64 bg-white text-gray-800 rounded-lg shadow-lg border border-gray-200 hidden group-focus-within:block z-50">
    <div class="p-3 border-b font-semibold text-sm text-gray-700">Notifications</div>
    <ul class="max-h-64 overflow-y-auto text-sm">
      <?php if (!empty($_SESSION['notifications'])): ?>
        <?php foreach ($_SESSION['notifications'] as $note): ?>
          <li class="px-4 py-2 hover:bg-gray-100"><?php echo htmlspecialchars($note); ?></li>
        <?php endforeach; ?>
      <?php else: ?>
        <li class="px-4 py-2 text-gray-500">No new notifications</li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<!-- User Info: Avatar + Name + Role + Notifications -->
<div class="relative group flex items-center gap-3">

  <!-- Avatar with notification badge -->
<button id="avatar-button" class="p-2 relative w-8 h-8 md:w-10 md:h-10 bg-gradient-to-r from-blue-600 to-blue-700 rounded-full flex items-center justify-center hover:bg-white/10 focus:outline-none" title="View profile and settings">
    <span class="text-white text-sm font-semibold">
      <?php echo $user_initial; ?>
    </span>
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

<!-- Profile Settings Dropdown -->
<div id="profile-dropdown" class="absolute right-0 top-12 w-50 bg-white text-gray-800 rounded-lg shadow-lg border border-gray-200 hidden z-50">
  <ul class="text-sm">
      <li class="hover:bg-gray-100 hover:rounded-md transition-all duration-200">
      <a href="profile.php" class="px-4 py-2 cursor-pointer flex items-center gap-2 text-gray-800 hover:text-[#1E3A8A] w-full">
        <i class="fa-solid fa-user text-[#1E3A8A]"></i>
        View Profile
      </a>
    </li>
    <li class="hover:bg-gray-100 hover:rounded-md transition-all duration-200">
      <a href="../../view/settings.php" class="px-4 py-2 cursor-pointer flex items-center gap-2 text-gray-800 hover:text-[#1E3A8A] w-full">
        <i class="fa-solid fa-gear text-[#1E3A8A]"></i>
        Settings
      </a>
    </li>
    <!--<li class="hover:bg-gray-100 hover:rounded-md transition-all duration-200">
      <a href="../../logout.php" class="px-4 py-2 cursor-pointer flex items-center gap-2 text-gray-800 hover:text-[#1E3A8A] w-full">
        <i class="fa-solid fa-right-from-bracket text-[#1E3A8A]"></i>
        Logout
      </a>
    </li> -->
  </ul>
</div>

  </div>

  <script>
  document.addEventListener('click', function (e) {
  const bellBtn = document.getElementById('notification-button');
  const dropdown = document.getElementById('notification-dropdown');

  if (bellBtn.contains(e.target)) {
    dropdown.classList.toggle('hidden');
  } else {
    dropdown.classList.add('hidden');
  }
});

document.addEventListener('click', function (e) {
  const bellBtn = document.getElementById('notification-button');
  const bellDropdown = document.getElementById('notification-dropdown');
  const avatarBtn = document.getElementById('avatar-button');
  const profileDropdown = document.getElementById('profile-dropdown');

  // Bell toggle
  if (bellBtn && bellBtn.contains(e.target)) {
    bellDropdown.classList.toggle('hidden');
  } else {
    bellDropdown.classList.add('hidden');
  }

  // Avatar toggle
  if (avatarBtn && avatarBtn.contains(e.target)) {
    profileDropdown.classList.toggle('hidden');
  } else {
    profileDropdown.classList.add('hidden');
  }
});

  </script>
</header>




