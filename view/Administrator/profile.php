<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
require_once '../../model/Database.php';

// Get current user's data
$user_id = $_SESSION['user_id'];
$user_data = null;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get user information
    $stmt = $conn->prepare("SELECT id, full_name, email, role, id_number, status, created_at, last_login FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    
    // Check if user data was found
    if (!$user_data) {
        $_SESSION['error_message'] = "User profile not found. Please log in again.";
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    // Log the actual error for debugging
    error_log("Profile page database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Database error. Please contact support.";
    header("Location: index.php");
    exit();
}

include '../components/layout_header.php';
?>

<style>
html, body {
  margin: 0;
  padding: 0;
  height: 100%;
  overflow: hidden; 
  box-sizing: border-box;
}
*, *::before, *::after {
  box-sizing: inherit;
}
main {
  height: 100vh;       
  overflow: hidden;  
}
#security-settings-content {
  transition: all 0.3s ease;
}
.rotate-180 {
  transform: rotate(180deg);
}
@keyframes fillBar {
  from { width: 0%; }
  to { width: var(--fill); }
}
.animate-fill {
  animation: fillBar 1s ease-out forwards;
}
.tooltip {
  position: relative;
  display: inline-block;
}
.tooltip .tooltip-text {
  visibility: hidden;
  width: 180px;
  background-color: #1E3A8A;
  color: #fff;
  text-align: left;
  padding: 6px;
  border-radius: 4px;
  font-size: 10px;
  position: absolute;
  z-index: 10;
  bottom: 125%; /* above the bar */
  left: 50%;
  transform: translateX(-50%);
  opacity: 0;
  transition: opacity 0.3s ease;
}
.tooltip:hover .tooltip-text {
  visibility: visible;
  opacity: 1;
}
@keyframes fadeInAvatar {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
.avatar-animate {
  animation: fadeInAvatar 0.6s ease-out forwards;
}
#security-settings-content {
  transition: max-height 0.3s ease, opacity 0.3s ease;
  overflow: hidden;
  max-height: none; /* no forced height */
  opacity: 1;
}
</style>

<main class="p-6 bg-gray-50 min-h-screen text-[11px] text-gray-700">
  <div class="grid grid-cols-1 lg:grid-cols-1 gap-4">

 <!-- Profile Header Grid -->
<div class="space-y-4">

  <!-- Left Column: Avatar + Info -->
  <div class="md:col-span-1 flex flex-col gap-4">

<!-- Profile Card with 40/60 Split and Divider -->
<div id="user-profile" class="bg-white rounded-lg border p-4 grid grid-cols-[40%_60%] gap-6 items-start">

<!-- Left Layout Block with spacing between avatar and name -->
<div class="flex items-center gap-6">
  <!-- Avatar -->
  <div class="relative shrink-0">
    <img src="../../assets/images/admin_profile.png" alt="Avatar"
         class="w-24 h-24 rounded-full border border-gray-300 object-cover shadow-sm ring-2 ring-[#1E3A8A]">
    <span class="absolute bottom-1 right-1 inline-block w-4 h-4 bg-green-500 rounded-full border border-white"
          title="Online - Active Administrator"></span>
  </div>

  <!-- Name + Role + Last Login -->
  <div class="flex flex-col gap-1">
    <!-- Full Name -->
    <h2 class="text-lg font-bold text-gray-800 mt-1">
      <?php echo htmlspecialchars($user_data['full_name'] ?? 'N/A'); ?>
    </h2>

    <!-- Role + Divider + Last Login -->
    <div class="flex items-center gap-3 text-[10px] text-gray-700">
      <span class="flex items-center gap-1 text-red-700 font-medium">
        <i class="fa-solid fa-shield-halved"></i>
        <?php echo htmlspecialchars($user_data['role'] ?? 'N/A'); ?>
      </span>

      <!-- Divider -->
      <div class="h-4 w-px bg-gray-400"></div>

      <!-- Last Login -->
      <span class="text-gray-600">
        <i class="fa-solid fa-clock mr-1 text-[#1E3A8A]"></i>
        <?php echo date('M j, Y g:i A', strtotime($user_data['last_login'])); ?>
      </span>
    </div>
    <p class="text-[10px] text-gray-500 italic">“Ensuring smooth asset operations across QCU.”</p>
  </div>
</div>

<!-- Right Column: Two-Column Info Block -->
<div class="relative w-full pl-6">
  <!-- Vertical Divider -->
  <div class="absolute top-0 left-0 h-full w-px bg-gray-300"></div>

  <!-- Two-column grid: 2 rows, 2 columns -->
  <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-[10px] leading-5">
    <!-- Row 1 -->
    <div>
      <span class="font-medium text-gray-600">
        <i class="fa-solid fa-id-badge text-[#1E3A8A]"></i>
        Employee ID:
      </span>
      <span class="block text-gray-800 font-bold">
        <?php echo htmlspecialchars($user_data['id_number'] ?? 'N/A'); ?>
      </span>
    </div>
    <div>
      <span class="font-medium text-gray-600">
        <i class="fa-solid fa-phone text-[#1E3A8A]"></i>
         Phone:</span>
        <span class="block text-gray-800 font-bold">+63 (9) 972-22-22</span>
    </div>

    <!-- Row 2 -->
    <div>
      <span class="font-medium text-gray-600">
        <i class="fa-solid fa-gear text-[#1E3A8A]"></i>
        Role:
      </span>
      <span class="block text-gray-800 font-bold">
        <?php echo htmlspecialchars($user_data['role'] ?? 'N/A'); ?>
      </span>
    </div>
    <div>
      <span class="font-medium text-gray-600">
        <i class="fa-solid fa-envelope text-[#1E3A8A]"></i>
        Email:
        </span>
      <span class="block text-gray-800 font-bold">
        <?php echo htmlspecialchars($user_data['email'] ?? 'N/A'); ?>
      </span>
    </div>
  </div>
</div>

  </div>

</div>

 <!-- Profile Layout Grid -->
<div class="grid grid-cols-1 lg:grid-cols-[42%_57%] gap-4">

  <!-- LEFT COLUMN -->
  <div class="space-y-4">

    <!-- System Settings -->    
    <div id="system-settings" class="bg-white rounded-lg border p-4 space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
          <i class="fa-solid fa-gear text-[#1E3A8A]"></i> System Settings
        </h3>
        <button onclick="toggleSection('system-settings-content', this)" 
          class="text-[10px] text-[#1E3A8A] hover:text-blue-800 flex items-center gap-1">
          <i class="fa-solid fa-chevron-up transition-transform duration-300"></i>
        </button>
      </div>

      <div id="system-settings-content" class="space-y-4">
        <div class="space-y-2">
          <h4 class="text-xs font-semibold text-gray-800 flex items-center gap-1">
            <i class="fa-solid fa-circle-info text-[#1E3A8A]"></i> Account Status
          </h4>
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-gray-600">Profile Status</span>
            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-[10px] font-medium" 
              title="Last login: <?php echo date('M j, Y g:i A', strtotime($user_data['last_login'] ?? 'now')); ?>">
              <i class="fa-solid fa-circle-check mr-1"></i>Active
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-gray-600">Account Type</span>
            <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-[10px] font-medium">Administrator</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-gray-600">Access Level</span>
            <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-[10px] font-medium">Full Access</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-gray-600">Last Login</span>
            <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-[10px] rounded-full font-medium">
              <i class="fa-solid fa-clock mr-0.5"></i>
              <?php 
                if ($user_data['last_login']) {
                  echo date('M j, Y g:i A', strtotime($user_data['last_login']));
                } else {
                  echo 'Never';
                }
              ?>
            </span>
          </div>
        </div>

        <div class="space-y-1.5">
          <h4 class="text-xs font-semibold text-gray-800 flex items-center gap-1">
            <i class="fa-solid fa-user-shield text-[#1E3A8A]"></i> Admin Privileges
          </h4>
          <?php
            $privileges = ['User Management', 'System Reports', 'Activity Monitoring', 'Analytics Dashboard', 'PC Health Monitoring'];
            foreach ($privileges as $priv) {
              echo '<div class="flex items-center gap-2 text-[10px] text-gray-700">
                <i class="fa-solid fa-check text-green-600"></i>
                <span>' . $priv . '</span>
              </div>';
            }
          ?>
        </div>
      </div>
    </div>

    <!-- Login History -->
    <div class="bg-white rounded-lg border p-4 space-y-2">
      <h3 class="text-xs font-semibold text-gray-800 flex items-center gap-1">
        <i class="fa-solid fa-clock-rotate-left text-[#1E3A8A]"></i> Login History
      </h3>
      <ul class="text-[10px] text-gray-600 space-y-1">
        <li>Nov 26, 2025 – 11:04 PM</li>
        <li>Nov 25, 2025 – 9:18 AM</li>
        <li>Nov 24, 2025 – 3:42 PM</li>
      </ul>
    </div>

  </div>

      <!-- RIGHT COLUMN -->
      <div class="space-y-4">
        
      <!-- Basic Information -->
      <div id="basic-info" class="bg-white rounded-lg border p-4 space-y-3">
        <div class="flex justify-between items-center">
          <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <i class="fa-solid fa-user text-[#1E3A8A]"></i> Basic Information
          </h3>
          <button class="text-[10px] text-[#1E3A8A] hover:underline">
            <i class="fa-solid fa-pen mr-1 text-[#1E3A8A]"></i>Edit
          </button>
        </div>

        <div class="grid grid-cols-2 gap-4 text-[10px] text-gray-700">
          <div>
            <p class="font-medium text-gray-600 mb-1">Full Name</p>
            <p class="text-gray-900 font-semibold"><?php echo htmlspecialchars($user_data['full_name'] ?? 'N/A'); ?></p>
          </div>
          <div>
            <p class="font-medium text-gray-600 mb-1">Email Address</p>
            <p class="text-gray-900"><?php echo htmlspecialchars($user_data['email'] ?? 'N/A'); ?></p>
          </div>
          <div>
            <p class="font-medium text-gray-600 mb-1">Phone</p>
            <p class="text-gray-900">+63 (9) 972-22-22</p>
          </div>
          <div>
            <p class="font-medium text-gray-600 mb-1">Member Since</p>
            <p class="text-gray-900"><?php echo date('F j, Y', strtotime($user_data['created_at'] ?? 'now')); ?></p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-[10px] text-gray-700">
  <div>
    <p class="font-medium text-gray-600 mb-1">Employee ID</p>
    <p class="text-gray-900"><?php echo htmlspecialchars($user_data['id_number'] ?? 'N/A'); ?></p>
  </div>
  <div>
    <p class="font-medium text-gray-600 mb-1">Department</p>
    <p class="text-gray-900">Asset Management</p>
  </div>
</div>
</div>
    <!-- Security Settings -->
    <div id="security-settings" class="bg-white rounded-lg border p-4 space-y-3">
      <div class="flex justify-between items-center">
        <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
          <i class="fa-solid fa-lock text-[#1E3A8A]"></i> Security Settings
        </h3>
        <button onclick="toggleSection('security-settings-content', this)" 
          class="text-[10px] text-[#1E3A8A] hover:text-blue-800 flex items-center gap-1">
          <i class="fa-solid fa-chevron-up transition-transform duration-300"></i>
        </button>
      </div>

      <div id="security-settings-content" class="space-y-4">
        <div class="flex justify-between items-center">
          <div>
            <h4 class="text-xs font-semibold text-gray-800">Change Password</h4>
            <p class="text-[10px] text-gray-500">Update your password to keep your account secure</p>
          </div>
          <button onclick="openChangePasswordModal()" 
            class="px-3 py-1.5 bg-[#1E3A8A] hover:bg-blue-700 text-white text-[10px] font-medium rounded transition-colors">
            <i class="fa-solid fa-key mr-1"></i>Change Password
          </button>
        </div>

        <div class="bg-blue-50 border border-blue-100 rounded p-3">
          <h4 class="text-xs font-semibold text-blue-900 mb-1">Password Security Tips</h4>
          <ul class="list-disc list-inside text-[10px] text-blue-800 space-y-0.5">
            <li>Use at least 8 characters</li>
            <li>Include uppercase and lowercase letters</li>
            <li>Add numbers and special characters</li>
            <li>Avoid common words or personal information</li>
          </ul>
        </div>
        <br>
    </div>
      </div>
</main>

<!-- Change Password Modal -->
<div id="changePasswordModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-key text-[#1E3A8A]"></i>
                Change Password
            </h3>
            <button onclick="closeChangePasswordModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>

        <form id="changePasswordForm" class="space-y-3">
            <div>
                <label class="block text-[10px] font-medium text-gray-700 mb-1">Current Password</label>
                <input type="password" id="currentPassword" name="current_password" required
                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
            </div>

            <div>
                <label class="block text-[10px] font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" id="newPassword" name="new_password" required
                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
                <p class="text-[10px] text-gray-500 mt-1">Minimum 8 characters</p>
            </div>

            <div>
                <label class="block text-[10px] font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" id="confirmPassword" name="confirm_password" required
                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" onclick="closeChangePasswordModal()" 
                    class="flex-1 px-3 py-2 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                    class="flex-1 px-3 py-2 text-xs bg-[#1E3A8A] hover:bg-blue-700 text-white rounded transition-colors">
                    <i class="fa-solid fa-check mr-1"></i>Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSection(id, btn) {
  const section = document.getElementById(id);
  const icon = btn.querySelector('i');

  if (section.classList.contains('hidden')) {
    section.classList.remove('hidden');
    section.style.maxHeight = section.scrollHeight + 'px';
    section.style.opacity = 1;
  } else {
    section.style.maxHeight = '0px';
    section.style.opacity = 0;
    setTimeout(() => section.classList.add('hidden'), 300);
  }

  icon.classList.toggle('rotate-180');
}

  const filledFields = 6; // example
  const totalFields = 8;
  const percent = Math.round((filledFields / totalFields) * 100);
  document.querySelector('.animate-fill').style.setProperty('--fill', percent + '%');
  document.querySelector('.text-gray-500').textContent = `Profile completeness: ${percent}%`;

function toggleSection(id, btn) {
  const section = document.getElementById(id);
  const icon = btn.querySelector('i');
  if (section) {
    section.classList.toggle('hidden');
    icon.classList.toggle('rotate-180'); // rotate arrow
  }
}

function openChangePasswordModal() {
    document.getElementById('changePasswordModal').classList.remove('hidden');
}

function closeChangePasswordModal() {
    document.getElementById('changePasswordModal').classList.add('hidden');
    document.getElementById('changePasswordForm').reset();
}

// Handle password change form submission
document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    // Validate passwords match
    if (newPassword !== confirmPassword) {
        showToast('Passwords do not match', 'error');
        return;
    }
    
    // Validate password length
    if (newPassword.length < 8) {
        showToast('Password must be at least 8 characters', 'error');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('current_password', currentPassword);
        formData.append('new_password', newPassword);
        formData.append('confirm_password', confirmPassword);
        
        const response = await fetch('../../controller/change_password.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message || 'Password changed successfully', 'success');
            closeChangePasswordModal();
            document.getElementById('changePasswordForm').reset();
        } else {
            showToast(result.message || 'Failed to change password', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
});

// Close modal when clicking outside
document.getElementById('changePasswordModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeChangePasswordModal();
    }
});

// Toast notification function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600';
    
    toast.className = `fixed top-4 right-4 ${bgColor} text-white px-4 py-2 rounded shadow-lg z-50 text-xs`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>

<?php include '../components/layout_footer.php'; ?>
