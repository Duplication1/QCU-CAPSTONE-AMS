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
<header class="fixed top-0 left-0 w-full z-30 text-white shadow-md h-[85px] border-b border-gray-200" style="background-color: #1E3A8A;">
  <div class="flex items-center justify-between h-full px-4 md:px-6">

    <!-- Left: Burger Menu + Logo + oneQCU -->
    <div class="flex items-center space-x-2 md:space-x-2">
      <!-- Logo + Text -->
      <div class="flex items-center gap-2 md:gap-3">
        <img src="../../assets/images/QCU-LOGO.png" alt="QCU Logo" class="w-12 h-12 md:w-14 md:h-14">
        <div class="flex flex-col leading-tight">
          <h1 class="font-bold font-[Poppins] text-white" style="font-size: 20px;">
            Quezon City University
          </h1>
          <p class="font-normal font-[] text-white" style="font-size: 12px;">
            Asset Management System
          </p>
        </div>
      </div>
      
      <!-- Burger Menu Button - Only visible on mobile -->
      <button id="sidebar-toggle" class="md:hidden p-2 rounded-lg hover:bg-blue-800 transition-all duration-300">
        <i id="toggle-icon" class="fa-solid fa-bars text-lg md:text-xl text-white"></i>
      </button>
    </div>

<!-- Right: User Info + Notifications -->
<div class="flex items-center gap-4 md:gap-6">

<!-- Notification Icon with Dropdown -->
<div class="relative group">
<button id="notification-button" class="p-2 rounded-full hover:bg-blue-800 text-lg md:text-xl focus:outline-none relative" style="color: white;" title="Notifications">
    <img src="../../assets/images/ri_notification-line.png" style="filter: brightness(0) invert(1);"></img>
    <div id="notification-badge" class="hidden absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow-md">
      0
    </div>
  </button>

  <!-- Notification Dropdown -->
  <div id="notification-dropdown" class="absolute right-0 top-10 w-80 bg-white text-gray-800 rounded-lg shadow-lg border border-gray-200 hidden z-50 max-h-96 overflow-y-auto">
    <div class="p-3 border-b font-semibold text-sm text-gray-700">
      <span>Notifications</span>
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
<button id="avatar-button" class="p-2 relative w-8 h-8 md:w-10 md:h-10 bg-gradient-to-r from-blue-600 to-blue-700 rounded-full flex items-center justify-center hover:opacity-90 focus:outline-none" title="View profile and logout">
    <span class="text-white text-sm font-semibold">
      <?php echo $user_initial; ?>
    </span>
  </button>



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
      <a href="../../controller/logout_controller.php" class="px-4 py-2 cursor-pointer flex items-center gap-2 text-gray-800 hover:text-red-600 w-full">
        <i class="fa-solid fa-right-from-bracket text-red-600"></i>
        Logout
      </a>
    </li>
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
        badge.classList.remove('hidden');
      } else {
        badge.classList.add('hidden');
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
        const isUnread = notif.is_read == 0 ? '1' : '0';
        
        return `
          <li class="px-4 py-3 border-b ${unreadClass} hover:bg-gray-100 cursor-pointer transition-colors duration-200" 
              data-notification-id="${notif.id}"
              data-is-unread="${isUnread}"
              data-related-type="${notif.related_type || ''}"
              data-related-id="${notif.related_id || ''}"
              onclick="handleNotificationClick(${notif.id}, '${notif.related_type || ''}', ${notif.related_id || 'null'}, this)">
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
  
  function handleNotificationClick(notifId, relatedType, relatedId, element) {
    const currentPath = window.location.pathname;
    let controllerPath = '../../controller/mark_notification_read.php';
    
    if (currentPath.includes('/view/StudentFaculty/')) {
      controllerPath = '../../controller/mark_notification_read.php';
    } else if (currentPath.includes('/view/')) {
      controllerPath = '../controller/mark_notification_read.php';
    }
    
    // Get current badge count and element's unread status
    const badge = document.getElementById('notification-badge');
    const isUnread = element.getAttribute('data-is-unread') === '1';
    
    // Update UI immediately if this was an unread notification
    if (isUnread && badge) {
      const currentCount = parseInt(badge.textContent) || 0;
      const newCount = Math.max(0, currentCount - 1);
      
      if (newCount > 0) {
        badge.textContent = newCount > 99 ? '99+' : newCount;
        badge.classList.remove('hidden');
      } else {
        badge.classList.add('hidden');
      }
      
      // Update notification item styling
      element.classList.remove('bg-blue-50', 'border-l-4', 'border-[#1E3A8A]');
      element.setAttribute('data-is-unread', '0');
    }
    
    // Close dropdown
    const dropdown = document.getElementById('notification-dropdown');
    if (dropdown) {
      dropdown.classList.add('hidden');
    }
    
    // Determine navigation target based on role and notification type
    let targetUrl = null;
    
    if (relatedType === 'issue' && relatedId) {
      // For issue/ticket notifications
      if (currentPath.includes('/view/StudentFaculty/')) {
        targetUrl = 'ticket_issues.php';
      } else if (currentPath.includes('/view/Technician/')) {
        targetUrl = 'tickets.php';
      } else if (currentPath.includes('/view/LaboratoryStaff/')) {
        targetUrl = 'tickets.php';
      } else if (currentPath.includes('/view/Administrator/')) {
        targetUrl = 'index.php'; // Admin dashboard
      }
    } else if (relatedType === 'borrowing' && relatedId) {
      // For borrowing request notifications
      if (currentPath.includes('/view/StudentFaculty/')) {
        targetUrl = 'requests.php';
      } else if (currentPath.includes('/view/LaboratoryStaff/')) {
        targetUrl = 'borrowing.php';
      } else if (currentPath.includes('/view/Administrator/')) {
        targetUrl = 'index.php'; // Admin dashboard
      }
    } else if (relatedType === 'asset' && relatedId) {
      // For asset-related notifications
      if (currentPath.includes('/view/LaboratoryStaff/')) {
        targetUrl = 'allassets.php';
      } else if (currentPath.includes('/view/Technician/')) {
        targetUrl = 'allassets.php';
      } else if (currentPath.includes('/view/Administrator/')) {
        targetUrl = 'index.php';
      }
    }
    
    // Mark as read on server, then navigate
    fetch(controllerPath, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `notification_id=${notifId}`
    }).then(response => response.json())
    .then(data => {
      console.log('Notification marked as read:', notifId, data);
      
      // Navigate to target URL if determined
      if (targetUrl) {
        window.location.href = targetUrl;
      } else {
        // Only reload notifications if not navigating away
        loadNotifications();
      }
    }).catch(err => {
      console.error('Failed to mark notification as read:', err);
      // Still navigate even if marking failed
      if (targetUrl) {
        window.location.href = targetUrl;
      }
    });
  }
  
  function markAllAsRead() {
    const currentPath = window.location.pathname;
    let controllerPath = '../../controller/mark_all_notifications_read.php';
    
    if (currentPath.includes('/view/StudentFaculty/') || currentPath.includes('/view/Technician/') || currentPath.includes('/view/LaboratoryStaff/') || currentPath.includes('/view/Administrator/')) {
      controllerPath = '../../controller/mark_all_notifications_read.php';
    } else if (currentPath.includes('/view/')) {
      controllerPath = '../controller/mark_all_notifications_read.php';
    }
    
    // Update badge immediately
    const badge = document.getElementById('notification-badge');
    if (badge) {
      badge.classList.add('hidden');
    }
    
    const list = document.getElementById('notifications-list');
    const items = list.querySelectorAll('li[data-notification-id]');
    
    // Update all notification items styling
    items.forEach(item => {
      item.classList.remove('bg-blue-50', 'border-l-4', 'border-[#1E3A8A]');
      item.setAttribute('data-is-unread', '0');
    });
    
    // Mark all as read on server with single request
    fetch(controllerPath, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        console.log('All notifications marked as read in database:', data.affected_rows, 'rows updated');
      } else {
        console.error('Failed to mark all notifications as read:', data.message);
      }
    })
    .catch(err => {
      console.error('Failed to mark all notifications as read:', err);
    });
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
      // Load fresh notifications and mark all as read when opening
      if (!bellDropdown.classList.contains('hidden')) {
        loadNotifications();
        // Mark all notifications as read after a short delay to ensure they're loaded
        setTimeout(() => {
          markAllAsRead();
        }, 500);
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




