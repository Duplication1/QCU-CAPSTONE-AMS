<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has technician role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Technician') {
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
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 flex-1 overflow-hidden">
        
        <!-- Left Column: Profile Info -->
        <div class="lg:col-span-2 flex flex-col gap-2 h-full overflow-hidden">
            <!-- Profile Information Card -->
            <div class="bg-white rounded shadow-sm border border-gray-200 p-3">
                <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-id-card text-[#1E3A8A]"></i>
                    Profile Information
                </h3>
                
                <div class="space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-medium text-gray-700 mb-1">Full Name</label>
                            <p class="text-xs text-gray-900 font-medium"><?php echo htmlspecialchars($user_data['full_name'] ?? 'N/A'); ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-medium text-gray-700 mb-1">Email Address</label>
                            <p class="text-xs text-gray-900"><?php echo htmlspecialchars($user_data['email'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-medium text-gray-700 mb-1">Role</label>
                            <p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-700">
                                    <i class="fa-solid fa-screwdriver-wrench mr-1"></i>
                                    <?php echo htmlspecialchars($user_data['role'] ?? 'N/A'); ?>
                                </span>
                            </p>
                        </div>
                        
                        <?php if (isset($user_data['id_number']) && $user_data['id_number']): ?>
                        <div>
                            <label class="block text-[10px] font-medium text-gray-700 mb-1">Employee ID</label>
                            <p class="text-xs text-gray-900 font-mono"><?php echo htmlspecialchars($user_data['id_number']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-medium text-gray-700 mb-1">Member Since</label>
                            <p class="text-xs text-gray-900">
                                <i class="fa-solid fa-calendar-check text-green-600 mr-1"></i>
                                <?php echo date('F j, Y', strtotime($user_data['created_at'] ?? 'now')); ?>
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-medium text-gray-700 mb-1">Last Login</label>
                            <p class="text-xs text-gray-900">
                                <i class="fa-solid fa-clock text-blue-600 mr-1"></i>
                                <?php 
                                if ($user_data['last_login']) {
                                    echo date('M j, Y g:i A', strtotime($user_data['last_login']));
                                } else {
                                    echo 'Never';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security & Password Section -->
            <div class="bg-white rounded shadow-sm border border-gray-200 p-3">
                <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-lock text-[#1E3A8A]"></i>
                    Security Settings
                </h3>

                <div class="space-y-2">
                    <div class="border border-gray-200 rounded p-3">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h4 class="text-xs font-semibold text-gray-800">Change Password</h4>
                                <p class="text-[10px] text-gray-500 mt-0.5">Update your password to keep your account secure</p>
                            </div>
                            <button onclick="openChangePasswordModal()" class="px-3 py-1.5 bg-[#1E3A8A] hover:bg-blue-700 text-white text-[10px] font-medium rounded transition-colors">
                                <i class="fa-solid fa-key mr-1"></i>Change Password
                            </button>
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 rounded p-3 bg-blue-50">
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-info text-blue-600 text-sm mt-0.5"></i>
                            <div>
                                <h4 class="text-xs font-semibold text-blue-900">Password Security Tips</h4>
                                <ul class="text-[10px] text-blue-800 mt-1 space-y-0.5 list-disc list-inside">
                                    <li>Use at least 8 characters</li>
                                    <li>Include uppercase and lowercase letters</li>
                                    <li>Add numbers and special characters</li>
                                    <li>Avoid common words or personal information</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="flex flex-col gap-2 h-full">
            <div class="bg-white rounded shadow-sm border border-gray-200 p-3">
                <h3 class="text-xs font-semibold text-gray-800 mb-2 flex items-center gap-1">
                    <i class="fa-solid fa-circle-info text-[#1E3A8A]"></i>
                    Account Status
                </h3>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-gray-600">Profile Status</span>
                        <span class="px-1.5 py-0.5 bg-green-100 text-green-800 text-[10px] rounded-full font-medium">
                            <i class="fa-solid fa-circle-check mr-0.5"></i>Active
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-gray-600">Account Type</span>
                        <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-[10px] rounded-full font-medium">
                            Technician
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-gray-600">Access Level</span>
                        <span class="px-1.5 py-0.5 bg-green-100 text-green-700 text-[10px] rounded-full font-medium">
                            Technical Access
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded shadow-sm border border-gray-200 p-3">
                <h3 class="text-xs font-semibold text-gray-800 mb-2 flex items-center gap-1">
                    <i class="fa-solid fa-user-gear text-[#1E3A8A]"></i>
                    Technician Privileges
                </h3>
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2 text-[10px] text-gray-700">
                        <i class="fa-solid fa-check text-green-600"></i>
                        <span>Ticket Resolution</span>
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-gray-700">
                        <i class="fa-solid fa-check text-green-600"></i>
                        <span>Maintenance Tasks</span>
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-gray-700">
                        <i class="fa-solid fa-check text-green-600"></i>
                        <span>Asset Registry</span>
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-gray-700">
                        <i class="fa-solid fa-check text-green-600"></i>
                        <span>Equipment Inspection</span>
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-gray-700">
                        <i class="fa-solid fa-check text-green-600"></i>
                        <span>Hardware Repair</span>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-[#1E3A8A] to-blue-700 rounded shadow-sm p-3 text-white">
                <div class="text-center">
                    <i class="fa-solid fa-screwdriver-wrench text-3xl mb-2 opacity-80"></i>
                    <h4 class="text-xs font-semibold mb-1">Technician Account</h4>
                    <p class="text-[10px] opacity-90">Handle technical support and repairs</p>
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
