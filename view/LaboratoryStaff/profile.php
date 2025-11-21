<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
require_once '../../model/Database.php';

// Get current user's data
$user_id = $_SESSION['user_id'];
$user_data = null;
$current_signature = null;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get user information
    $stmt = $conn->prepare("SELECT id, full_name, email, role, id_number, status, created_at, last_login, e_signature FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    $current_signature = $user_data['e_signature'] ?? null;
    
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
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-purple-100 text-purple-700">
                                    <i class="fa-solid fa-flask mr-1"></i>
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

            <!-- E-Signature Section -->
            <div class="bg-white rounded shadow-sm border border-gray-200 p-3">
                <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-signature text-[#1E3A8A]"></i>
                    E-Signature Management
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <!-- Current Signature Display -->
                    <div>
                        <label class="block text-[10px] font-medium text-gray-700 mb-2">Current Signature</label>
                        <div class="border-2 border-dashed border-gray-300 rounded p-3 bg-gray-50 flex items-center justify-center" style="min-height: 120px;">
                            <?php if ($current_signature && file_exists('../../uploads/signatures/' . $current_signature)): ?>
                                <img src="../../uploads/signatures/<?php echo htmlspecialchars($current_signature); ?>" 
                                     alt="Current E-Signature" 
                                     class="max-h-24 max-w-full object-contain">
                            <?php else: ?>
                                <div class="text-center text-gray-400">
                                    <i class="fa-solid fa-signature text-2xl mb-1"></i>
                                    <p class="text-[10px]">No signature uploaded</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($current_signature): ?>
                        <button type="button" onclick="removeSignature()" class="mt-2 w-full px-2 py-1 bg-red-600 hover:bg-red-700 text-white text-[10px] rounded transition-colors">
                            <i class="fa-solid fa-trash mr-1"></i>Remove Signature
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Upload Form -->
                    <div>
                        <label class="block text-[10px] font-medium text-gray-700 mb-2">Upload New Signature</label>
                        <form id="signatureForm" enctype="multipart/form-data" class="space-y-2">
                            <input type="file" 
                                   id="signatureFile" 
                                   name="signature" 
                                   accept="image/jpeg,image/jpg,image/png,image/gif"
                                   class="block w-full text-[10px] text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                            <p class="text-[9px] text-gray-500">JPG, PNG, GIF (Max 2MB)</p>
                            
                            <!-- Preview Area -->
                            <div id="signaturePreview" class="hidden border-2 border-dashed border-gray-300 rounded p-2 bg-gray-50">
                                <img id="signaturePreviewImage" src="" alt="Preview" class="max-h-20 max-w-full object-contain mx-auto">
                            </div>
                            
                            <button type="submit" class="w-full px-2 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-[10px] font-medium rounded transition-colors">
                                <i class="fa-solid fa-upload mr-1"></i>Upload Signature
                            </button>
                        </form>
                        
                        <div class="mt-2 p-2 bg-blue-50 rounded border border-blue-200">
                            <p class="text-[9px] text-blue-700">
                                <i class="fa-solid fa-info-circle mr-1"></i>
                                Your signature will be used for approving borrowing requests and official documents.
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
                        <span class="px-1.5 py-0.5 bg-purple-100 text-purple-700 text-[10px] rounded-full font-medium">
                            Laboratory Staff
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-gray-600">Access Level</span>
                        <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-[10px] rounded-full font-medium">
                            Staff Access
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded shadow-sm border border-gray-200 p-3">
                <h3 class="text-xs font-semibold text-gray-800 mb-2 flex items-center gap-1">
                    <i class="fa-solid fa-user-check text-[#1E3A8A]"></i>
                    Staff Privileges
                </h3>
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2 text-[10px] text-gray-700">
                        <i class="fa-solid fa-check text-green-600"></i>
                        <span>Asset Management</span>
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-gray-700">
                        <i class="fa-solid fa-check text-green-600"></i>
                        <span>Borrowing Requests</span>
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-gray-700">
                        <i class="fa-solid fa-check text-green-600"></i>
                        <span>Ticket Management</span>
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-gray-700">
                        <i class="fa-solid fa-check text-green-600"></i>
                        <span>PC Health Dashboard</span>
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-gray-700">
                        <i class="fa-solid fa-check text-green-600"></i>
                        <span>Equipment Registry</span>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded shadow-sm p-3 text-white">
                <div class="text-center">
                    <i class="fa-solid fa-flask text-3xl mb-2 opacity-80"></i>
                    <h4 class="text-xs font-semibold mb-1">Laboratory Staff</h4>
                    <p class="text-[10px] opacity-90">Manage lab assets and operations</p>
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
// Signature upload functionality
document.getElementById('signatureFile').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('signaturePreviewImage').src = e.target.result;
            document.getElementById('signaturePreview').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById('signatureForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('signatureFile');
    if (!fileInput.files[0]) {
        showToast('Please select a signature file', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('signature', fileInput.files[0]);
    
    try {
        const response = await fetch('../../controller/upload_signature.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Signature uploaded successfully', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.message || 'Failed to upload signature', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
});

async function removeSignature() {
    if (!confirm('Are you sure you want to remove your signature?')) {
        return;
    }
    
    try {
        const response = await fetch('../../controller/delete_signature.php');
        const result = await response.json();
        
        if (result.success) {
            showToast('Signature removed successfully', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.message || 'Failed to remove signature', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
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
