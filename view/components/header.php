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
    <div class="flex items-center space-x-2 md:space-x-2">
      <!-- Burger Menu Button - Only visible on mobile -->
      <button id="sidebar-toggle" class="md:hidden p-2 rounded-lg hover:bg-white/10 transition-all duration-300">
        <i id="toggle-icon" class="fa-solid fa-bars text-lg md:text-xl"></i>
      </button>

      <!-- Logo + Text -->
      <div class="flex items-center gap-1 md:pl-1">
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
    <div id="notification-badge" class="hidden absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow-md">
      0
    </div>
  </button>

  <!-- Notification Dropdown -->
  <div id="notification-dropdown" class="absolute right-0 top-10 w-80 bg-white text-gray-800 rounded-lg shadow-lg border border-gray-200 hidden z-50 max-h-96 overflow-y-auto">
    <div class="p-3 border-b font-semibold text-sm text-gray-700 flex justify-between items-center flex-shrink-0">
      <span>Notifications</span>
      <button onclick="markAllAsRead()" class="text-xs text-[#1E3A8A] hover:underline">Mark all read</button>
    </div>
    <ul id="notifications-list" class="text-sm">
      <li class="px-4 py-3 text-center text-gray-500">
        <i class="fa-solid fa-spinner fa-spin"></i> Loading...
      </li>
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
  // Load notifications on page load
  let notificationsInterval;
  
  function loadNotifications() {
    const currentPath = window.location.pathname;
    let controllerPath = '../../controller/get_notifications.php';
    
    // Adjust path based on current location
    if (currentPath.includes('/view/StudentFaculty/') || currentPath.includes('/view/Technician/') || currentPath.includes('/view/LaboratoryStaff/') || currentPath.includes('/view/Administrator/')) {
      controllerPath = '../../controller/get_notifications.php';
    } else if (currentPath.includes('/view/')) {
      controllerPath = '../controller/get_notifications.php';
    }
    
    fetch(controllerPath)
      .then(res => {
        if (!res.ok) {
          throw new Error('Network response was not ok');
        }
        return res.json();
      })
      .then(data => {
        console.log('Notifications loaded:', data);
        if (data.success) {
          updateNotificationUI(data.notifications, data.unread_count);
        } else {
          console.error('Failed to load notifications:', data.message);
        }
      })
      .catch(err => {
        console.error('Failed to load notifications:', err);
        // Show error in dropdown if open
        const list = document.getElementById('notifications-list');
        if (list && !document.getElementById('notification-dropdown').classList.contains('hidden')) {
          list.innerHTML = '<li class="px-4 py-3 text-center text-red-500 text-xs">Failed to load notifications</li>';
        }
      });
  }
  
  function updateNotificationUI(notifications, unreadCount) {
    const badge = document.getElementById('notification-badge');
    const list = document.getElementById('notifications-list');
    
    // Update badge
    if (badge) {
      if (unreadCount > 0) {
        badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    }
    
    // Update list
    if (notifications.length === 0) {
      list.innerHTML = '<li class="px-4 py-3 text-center text-gray-500">No notifications</li>';
    } else {
      list.innerHTML = notifications.map(notif => {
        const iconMap = {
          'info': 'fa-circle-info text-blue-500',
          'success': 'fa-circle-check text-green-500',
          'warning': 'fa-triangle-exclamation text-yellow-500',
          'error': 'fa-circle-xmark text-red-500'
        };
        const icon = iconMap[notif.type] || iconMap['info'];
        const unreadClass = notif.is_read == 0 ? 'bg-blue-50 border-l-4 border-[#1E3A8A]' : '';
        
        return `
          <li class="px-4 py-3 hover:bg-gray-100 cursor-pointer border-b ${unreadClass}" 
              onclick="handleNotificationClick(${notif.id}, '${notif.related_type}', ${notif.related_id})">
            <div class="flex gap-2">
              <i class="fa-solid ${icon} mt-1"></i>
              <div class="flex-1">
                <div class="font-semibold text-xs text-gray-800">${escapeHtml(notif.title)}</div>
                <div class="text-xs text-gray-600 mt-1">${escapeHtml(notif.message)}</div>
                <div class="text-[10px] text-gray-400 mt-1">${notif.time_ago}</div>
              </div>
            </div>
          </li>
        `;
      }).join('');
    }
  }
  
  function handleNotificationClick(notifId, relatedType, relatedId) {
    const currentPath = window.location.pathname;
    let controllerPath = '../../controller/mark_notification_read.php';
    
    if (currentPath.includes('/view/StudentFaculty/')) {
      controllerPath = '../../controller/mark_notification_read.php';
    } else if (currentPath.includes('/view/')) {
      controllerPath = '../controller/mark_notification_read.php';
    }
    
    // Mark as read
    fetch(controllerPath, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `notification_id=${notifId}`
    }).then(() => {
      console.log('Notification marked as read:', notifId);
    }).catch(err => {
      console.error('Failed to mark notification as read:', err);
    });
    
    // Navigate based on type
    const dropdown = document.getElementById('notification-dropdown');
    if (dropdown) {
      dropdown.classList.add('hidden');
    }
    
    if (relatedType === 'issue' && relatedId) {
      window.location.href = 'ticket_issues.php';
    } else if (relatedType === 'borrowing' && relatedId) {
      window.location.href = 'requests.php';
    }
    
    // Reload notifications
    setTimeout(loadNotifications, 500);
  }
  
  function markAllAsRead() {
    const currentPath = window.location.pathname;
    let controllerPath = '../../controller/mark_notification_read.php';
    
    if (currentPath.includes('/view/StudentFaculty/')) {
      controllerPath = '../../controller/mark_notification_read.php';
    } else if (currentPath.includes('/view/')) {
      controllerPath = '../controller/mark_notification_read.php';
    }
    
    const list = document.getElementById('notifications-list');
    const items = list.querySelectorAll('li[onclick]');
    
    items.forEach(item => {
      const onclick = item.getAttribute('onclick');
      const match = onclick.match(/handleNotificationClick\((\d+)/);
      if (match) {
        fetch(controllerPath, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `notification_id=${match[1]}`
        }).catch(err => console.error('Failed to mark notification as read:', err));
      }
    });
    
    setTimeout(loadNotifications, 500);
  }
  
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  // Load notifications on page load
  document.addEventListener('DOMContentLoaded', function() {
    console.log('Loading initial notifications...');
    loadNotifications();
    // Refresh every 30 seconds
    notificationsInterval = setInterval(loadNotifications, 30000);
  });

  // Handle dropdown clicks
  document.addEventListener('click', function (e) {
    const bellBtn = document.getElementById('notification-button');
    const bellDropdown = document.getElementById('notification-dropdown');
    const avatarBtn = document.getElementById('avatar-button');
    const profileDropdown = document.getElementById('profile-dropdown');

    // Bell toggle
    if (bellBtn && bellBtn.contains(e.target)) {
      e.stopPropagation();
      bellDropdown.classList.toggle('hidden');
      profileDropdown.classList.add('hidden');
      // Load fresh notifications when opening
      if (!bellDropdown.classList.contains('hidden')) {
        loadNotifications();
      }
    } 
    // Avatar toggle
    else if (avatarBtn && avatarBtn.contains(e.target)) {
      e.stopPropagation();
      profileDropdown.classList.toggle('hidden');
      bellDropdown.classList.add('hidden');
    }
    // Close all if clicking outside
    else if (!bellDropdown.contains(e.target) && !profileDropdown.contains(e.target)) {
      bellDropdown.classList.add('hidden');
      profileDropdown.classList.add('hidden');
    }
  });

  </script>
</header>




