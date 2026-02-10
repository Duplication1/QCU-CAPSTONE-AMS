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

<!-- Main Content -->
<main class="p-6 bg-gray-50 min-h-screen text-[11px] text-gray-700">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- LEFT COLUMN (2/3 width) -->
        <div class="lg:col-span-2 space-y-4">

            <!-- Profile Information Card -->
            <div class="bg-white rounded-lg border p-4 space-y-3">
                <div class="flex justify-between items-center">
                    <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-user text-[#1E3A8A]"></i> Profile Information
                    </h3>
                </div>

                <!-- Avatar and Basic Info -->
                <div class="flex items-start gap-4 pb-3 border-b border-gray-200">
                    <div class="relative shrink-0">
                        <div class="w-20 h-20 rounded-full border border-gray-300 bg-gradient-to-br from-purple-100 to-purple-200 flex items-center justify-center shadow-sm">
                            <i class="fa-solid fa-flask text-3xl text-purple-600"></i>
                        </div>
                        <span class="absolute bottom-0 right-0 inline-block w-3 h-3 bg-green-500 rounded-full border-2 border-white"
                              title="Active"></span>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-base font-bold text-gray-800">
                            <?php echo htmlspecialchars($user_data['full_name'] ?? 'N/A'); ?>
                        </h2>
                        <div class="flex items-center gap-2 text-[10px] text-gray-600 mt-1">
                            <span class="flex items-center gap-1">
                                <i class="fa-solid fa-flask text-purple-600"></i>
                                <?php echo htmlspecialchars($user_data['role'] ?? 'N/A'); ?>
                            </span>
                            <span class="text-gray-400">•</span>
                            <span><?php echo htmlspecialchars($user_data['id_number'] ?? 'N/A'); ?></span>
                        </div>
                        <p class="text-[10px] text-gray-500 mt-1 italic">Managing laboratory assets and operations</p>
                    </div>
                </div>

                <!-- Contact Information Grid -->
                <div class="grid grid-cols-2 gap-4 text-[10px] text-gray-700">
                    <div>
                        <p class="font-medium text-gray-600 mb-1">
                            <i class="fa-solid fa-envelope text-[#1E3A8A] mr-1"></i> Email Address
                        </p>
                        <p class="text-gray-900"><?php echo htmlspecialchars($user_data['email'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-600 mb-1">
                            <i class="fa-solid fa-id-badge text-[#1E3A8A] mr-1"></i> Employee ID
                        </p>
                        <p class="text-gray-900"><?php echo htmlspecialchars($user_data['id_number'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-600 mb-1">
                            <i class="fa-solid fa-user-tag text-[#1E3A8A] mr-1"></i> Role
                        </p>
                        <p class="text-gray-900">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-purple-100 text-purple-700">
                                <i class="fa-solid fa-flask mr-1"></i>
                                <?php echo htmlspecialchars($user_data['role'] ?? 'N/A'); ?>
                            </span>
                        </p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-600 mb-1">
                            <i class="fa-solid fa-building text-[#1E3A8A] mr-1"></i> Department
                        </p>
                        <p class="text-gray-900">Laboratory Services</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-600 mb-1">
                            <i class="fa-solid fa-calendar-plus text-[#1E3A8A] mr-1"></i> Member Since
                        </p>
                        <p class="text-gray-900"><?php echo date('F j, Y', strtotime($user_data['created_at'] ?? 'now')); ?></p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-600 mb-1">
                            <i class="fa-solid fa-clock text-[#1E3A8A] mr-1"></i> Last Login
                        </p>
                        <p class="text-gray-900">
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

            <!-- E-Signature Management -->
            <div class="bg-white rounded-lg border p-4 space-y-3">
                <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-signature text-[#1E3A8A]"></i> E-Signature Management
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Current Signature Display -->
                    <div>
                        <label class="block text-[10px] font-medium text-gray-700 mb-2">Current Signature</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 bg-gray-50 flex items-center justify-center" style="min-height: 140px;">
                            <?php if ($current_signature): ?>
                                <img src="<?php echo htmlspecialchars($current_signature); ?>" 
                                     alt="Current E-Signature" 
                                     class="max-h-28 max-w-full object-contain">
                            <?php else: ?>
                                <div class="text-center text-gray-400">
                                    <i class="fa-solid fa-signature text-3xl mb-2"></i>
                                    <p class="text-[10px]">No signature uploaded</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($current_signature): ?>
                        <button type="button" onclick="removeSignature()" class="mt-2 w-full px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-medium rounded transition-colors">
                            <i class="fa-solid fa-trash mr-1"></i>Remove Signature
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Upload Form -->
                    <div>
                        <label class="block text-[10px] font-medium text-gray-700 mb-2">Upload New Signature</label>
                        <form id="signatureForm" enctype="multipart/form-data" class="space-y-3">
                            <div>
                                <input type="file" 
                                       id="signatureFile" 
                                       name="signature" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif"
                                       class="block w-full text-[10px] text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-[10px] file:font-medium file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer border border-gray-300 rounded">
                                <p class="text-[9px] text-gray-500 mt-1">JPG, PNG, GIF (Max 2MB)</p>
                            </div>
                            
                            <!-- Preview Area -->
                            <div id="signaturePreview" class="hidden border-2 border-dashed border-gray-300 rounded-lg p-3 bg-gray-50">
                                <img id="signaturePreviewImage" src="" alt="Preview" class="max-h-24 max-w-full object-contain mx-auto">
                            </div>
                            
                            <button type="submit" class="w-full px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white text-[10px] font-medium rounded transition-colors">
                                <i class="fa-solid fa-upload mr-1"></i>Upload Signature
                            </button>
                        </form>
                        
                        <div class="mt-3 p-3 bg-purple-50 rounded-lg border border-purple-200">
                            <p class="text-[10px] text-purple-800 flex items-start gap-2">
                                <i class="fa-solid fa-info-circle mt-0.5"></i>
                                <span>Your signature will be used for approving borrowing requests and official documents.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="bg-white rounded-lg border p-4 space-y-3">
                <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-lock text-[#1E3A8A]"></i> Security Settings
                </h3>

                <div class="space-y-3">
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
                        <h4 class="text-xs font-semibold text-blue-900 mb-2 flex items-center gap-1">
                            <i class="fa-solid fa-shield-halved"></i> Password Security Tips
                        </h4>
                        <ul class="list-disc list-inside text-[10px] text-blue-800 space-y-0.5">
                            <li>Use at least 8 characters</li>
                            <li>Include uppercase and lowercase letters</li>
                            <li>Add numbers and special characters</li>
                            <li>Avoid common words or personal information</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT SIDEBAR (1/3 width) -->
        <div class="space-y-4">

            <!-- Account Status -->
            <div class="bg-white rounded-lg border p-4 space-y-3">
                <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-[#1E3A8A]"></i> Account Status
                </h3>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-gray-600">Profile Status</span>
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-[10px] font-medium">
                            <i class="fa-solid fa-circle-check mr-1"></i>Active
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-gray-600">Account Type</span>
                        <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-[10px] font-medium">Laboratory Staff</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-gray-600">Access Level</span>
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-medium">Staff Access</span>
                    </div>
                </div>
            </div>

            <!-- Staff Privileges -->
            <div class="bg-white rounded-lg border p-4 space-y-3">
                <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-user-check text-[#1E3A8A]"></i> Privileges
                </h3>

                <div class="space-y-2">
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

            <!-- Role Badge -->
            <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-lg shadow-sm p-4 text-white">
                <div class="text-center">
                    <i class="fa-solid fa-flask text-4xl mb-2 opacity-90"></i>
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
    
    toast.className = `fixed top-4 right-4 ${bgColor} text-white px-4 py-2 rounded shadow-lg z-50 text-xs`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>

<?php include '../components/layout_footer.php'; ?>
