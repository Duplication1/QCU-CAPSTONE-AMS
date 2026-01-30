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
    body, html { overflow: hidden !important; height: 100vh; }
</style>

<!-- Main Content -->
<main class="p-2 bg-gray-50 h-screen overflow-hidden flex flex-col">
    
    <!-- Session Messages -->
    <?php include '../components/session_messages.php'; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 flex-1 overflow-hidden">
        
        <!-- Left Column: Profile Info -->
        <div class="lg:col-span-2 flex flex-col gap-4 h-full overflow-hidden">
            <!-- Profile Information Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex-shrink-0">
                <h3 class="text-base font-semibold text-gray-900 mb-4 pb-3 border-b border-gray-200">
                    Profile Information
                </h3>
                
                <div class="space-y-4 text-sm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Full Name</label>
                            <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded border border-gray-200"><?php echo htmlspecialchars($user_data['full_name'] ?? 'N/A'); ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Email Address</label>
                            <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded border border-gray-200"><?php echo htmlspecialchars($user_data['email'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Role</label>
                            <div class="bg-gray-50 px-3 py-2 rounded border border-gray-200">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-[#1E3A8A] text-white">
                                    <?php echo htmlspecialchars($user_data['role'] ?? 'N/A'); ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if (isset($user_data['id_number']) && $user_data['id_number']): ?>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Employee ID</label>
                            <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded border border-gray-200"><?php echo htmlspecialchars($user_data['id_number']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Member Since</label>
                            <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded border border-gray-200">
                                <?php echo date('F j, Y', strtotime($user_data['created_at'] ?? 'now')); ?>
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Department</label>
                            <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded border border-gray-200">Asset Management</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Settings Section -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex-1 overflow-y-auto">
                <h3 class="text-base font-semibold text-gray-900 mb-4 pb-3 border-b border-gray-200">
                    Security Settings
                </h3>

                <div class="space-y-4">
                    <!-- Change Password -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-800 mb-1">Change Password</h4>
                            <p class="text-xs text-gray-600">Update your password to keep your account secure</p>
                        </div>
                        <button onclick="openChangePasswordModal()" 
                            class="bg-[#1E3A8A] hover:bg-blue-700 text-white text-sm font-medium py-2.5 px-4 rounded-lg transition-colors whitespace-nowrap">
                            <i class="fa-solid fa-key mr-1"></i>Change Password
                        </button>
                    </div>

                    <!-- Password Security Tips -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-blue-900 mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-shield-halved"></i>
                            Password Security Tips
                        </h4>
                        <ul class="space-y-2 text-xs text-blue-800">
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-blue-600 mt-0.5"></i>
                                <span>Use at least 8 characters</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-blue-600 mt-0.5"></i>
                                <span>Include uppercase and lowercase letters</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-blue-600 mt-0.5"></i>
                                <span>Add numbers and special characters</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-blue-600 mt-0.5"></i>
                                <span>Avoid common words or personal information</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Admin Privileges Info -->
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-purple-900 mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-user-shield"></i>
                            Administrator Privileges
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <?php
                            $privileges = [
                                'User Management', 
                                'System Reports', 
                                'Activity Monitoring', 
                                'Analytics Dashboard', 
                                'PC Health Monitoring',
                                'Asset Management'
                            ];
                            foreach ($privileges as $priv) {
                                echo '<div class="flex items-center gap-2 text-xs text-purple-800">
                                    <i class="fa-solid fa-check-circle text-purple-600"></i>
                                    <span>' . $priv . '</span>
                                </div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="flex flex-col gap-4 h-full overflow-y-auto">
            <!-- Account Status Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4 pb-3 border-b border-gray-200">
                    Account Status
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between py-2">
                        <span class="text-sm text-gray-700 font-medium">Profile Status</span>
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Active</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-t border-gray-100">
                        <span class="text-sm text-gray-700 font-medium">Account Type</span>
                        <span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">Administrator</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-t border-gray-100">
                        <span class="text-sm text-gray-700 font-medium">Access Level</span>
                        <span class="px-3 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded-full">Full Access</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-t border-gray-100">
                        <span class="text-sm text-gray-700 font-medium">Last Login</span>
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                            <?php 
                            if (isset($user_data['last_login']) && $user_data['last_login']) {
                                echo date('M j, g:i A', strtotime($user_data['last_login']));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- System Information Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4 pb-3 border-b border-gray-200 flex items-center gap-2">
                    <i class="fa-solid fa-info-circle text-[#1E3A8A]"></i>
                    System Information
                </h3>
                <div class="space-y-3 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Login Sessions</span>
                        <span class="font-semibold text-gray-900">Active</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                        <span class="text-gray-600">Two-Factor Auth</span>
                        <span class="text-yellow-600 font-semibold">Disabled</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                        <span class="text-gray-600">Account Created</span>
                        <span class="font-semibold text-gray-900">
                            <?php echo date('M j, Y', strtotime($user_data['created_at'] ?? 'now')); ?>
                        </span>
                    </div>
                </div>
            </div>
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
                <input type="password" id="newPassword" name="new_password" required oninput="validateNewPassword()"
                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
            </div>

            <div>
                <label class="block text-[10px] font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" id="confirmPassword" name="confirm_password" required
                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
                <div id="password_requirements" class="mt-2 space-y-1 bg-gray-50 p-2 rounded">
                    <div id="req_length" class="text-[10px] text-gray-500 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle text-[5px]"></i>
                        <span>At least 8 characters</span>
                    </div>
                    <div id="req_capital" class="text-[10px] text-gray-500 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle text-[5px]"></i>
                        <span>Contains capital letter (A-Z)</span>
                    </div>
                    <div id="req_special" class="text-[10px] text-gray-500 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle text-[5px]"></i>
                        <span>Contains special character (!@#$%^&*)</span>
                    </div>
                </div>
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
function openChangePasswordModal() {
    document.getElementById('changePasswordModal').classList.remove('hidden');
}

function closeChangePasswordModal() {
    document.getElementById('changePasswordModal').classList.add('hidden');
    document.getElementById('changePasswordForm').reset();
    
    // Reset password requirement indicators
    const requirements = ['req_length', 'req_capital', 'req_special'];
    const labels = {
        'req_length': 'At least 8 characters',
        'req_capital': 'Contains capital letter (A-Z)',
        'req_special': 'Contains special character (!@#$%^&*)'
    };
    
    requirements.forEach(reqId => {
        const element = document.getElementById(reqId);
        element.className = 'text-[10px] text-gray-500 flex items-center gap-1.5';
        element.innerHTML = `<i class="fa-solid fa-circle text-[5px]"></i><span>`</span>`;
    });
}

function validateNewPassword() {
    const password = document.getElementById('newPassword').value;
    
    // Check length requirement
    const reqLength = document.getElementById('req_length');
    if (password.length >= 8) {
        reqLength.className = 'text-[10px] text-green-600 flex items-center gap-1.5';
        reqLength.innerHTML = '<i class="fa-solid fa-check-circle"></i><span>At least 8 characters</span>';
    } else {
        reqLength.className = 'text-[10px] text-gray-500 flex items-center gap-1.5';
        reqLength.innerHTML = '<i class="fa-solid fa-circle text-[5px]"></i><span>At least 8 characters</span>';
    }
    
    // Check capital letter requirement
    const capitalRegex = /[A-Z]/;
    const reqCapital = document.getElementById('req_capital');
    if (capitalRegex.test(password)) {
        reqCapital.className = 'text-[10px] text-green-600 flex items-center gap-1.5';
        reqCapital.innerHTML = '<i class="fa-solid fa-check-circle"></i><span>Contains capital letter (A-Z)</span>';
    } else {
        reqCapital.className = 'text-[10px] text-gray-500 flex items-center gap-1.5';
        reqCapital.innerHTML = '<i class="fa-solid fa-circle text-[5px]"></i><span>Contains capital letter (A-Z)</span>';
    }
    
    // Check special character requirement
    const specialCharRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/;
    const reqSpecial = document.getElementById('req_special');
    if (specialCharRegex.test(password)) {
        reqSpecial.className = 'text-[10px] text-green-600 flex items-center gap-1.5';
        reqSpecial.innerHTML = '<i class="fa-solid fa-check-circle"></i><span>Contains special character (!@#$%^&*)</span>';
    } else {
        reqSpecial.className = 'text-[10px] text-gray-500 flex items-center gap-1.5';
        reqSpecial.innerHTML = '<i class="fa-solid fa-circle text-[5px]"></i><span>Contains special character (!@#$%^&*)</span>';
    }
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
    
    // Validate capital letter
    const capitalRegex = /[A-Z]/;
    if (!capitalRegex.test(newPassword)) {
        showToast('Password must contain at least one capital letter (A-Z)', 'error');
        return;
    }
    
    // Validate special character
    const specialCharRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/;
    if (!specialCharRegex.test(newPassword)) {
        showToast('Password must contain at least one special character (!@#$%^&*)', 'error');
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
    
    toast.className = `fixed top-4 right-4 ` text-white px-4 py-2 rounded shadow-lg z-50 text-xs`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>

<?php include '../components/layout_footer.php'; ?>
