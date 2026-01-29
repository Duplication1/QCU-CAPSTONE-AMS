<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has student or faculty role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || !in_array($_SESSION['role'], ['Student', 'Faculty'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
require_once '../../model/Database.php';

// Get current user's data
$user_id = $_SESSION['user_id'];
$current_signature = null;
$user_data = null;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get user information including e-signature
    $stmt = $conn->prepare("SELECT id, full_name, email, role, id_number, status, created_at, e_signature FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    
    // Check if user data was found
    if (!$user_data) {
        $_SESSION['error_message'] = "User profile not found. Please log in again.";
        header("Location: index.php");
        exit();
    }
    
    // Extract e_signature from user data
    $current_signature = $user_data['e_signature'] ?? null;
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
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5"><?php echo $user_data['role'] === 'Student' ? 'Student ID' : 'Employee ID'; ?></label>
                            <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded border border-gray-200"><?php echo htmlspecialchars($user_data['id_number']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Member Since</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded border border-gray-200">
                            <?php echo date('F j, Y', strtotime($user_data['created_at'] ?? 'now')); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- E-Signature Section -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex-1 overflow-y-auto">
                <h3 class="text-base font-semibold text-gray-900 mb-4 pb-3 border-b border-gray-200">
                    E-Signature Management
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Current Signature Display -->
                    <div class="flex flex-col">
                        <h4 class="text-xs font-semibold text-gray-700 mb-3">Current Signature</h4>
                        <div class="border-2 border-gray-200 rounded-lg p-4 bg-gray-50 flex items-center justify-center" style="min-height: 150px;">
                            <?php if ($current_signature && file_exists('../../uploads/signatures/' . $current_signature)): ?>
                            <div class="text-center w-full">
                                <img src="../../uploads/signatures/<?php echo htmlspecialchars($current_signature); ?>" 
                                     alt="Signature" 
                                     class="max-h-20 max-w-full object-contain mx-auto mb-3">
                                <span class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 rounded-full">
                                    Active Signature
                                </span>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-gray-400">
                                <div class="w-16 h-16 mx-auto mb-2 bg-gray-200 rounded-full flex items-center justify-center">
                                    <span class="text-2xl">✍️</span>
                                </div>
                                <p class="text-xs font-medium">No signature uploaded</p>
                                <p class="text-xs text-gray-500 mt-1">Upload your signature to approve requests</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Upload Form -->
                    <div class="flex flex-col">
                        <h4 class="text-xs font-semibold text-gray-700 mb-3">Upload New Signature</h4>
                        <form action="../../controller/upload_signature.php" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3">
                            <div id="drop-zone" class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-white text-center cursor-pointer hover:border-[#1E3A8A] hover:bg-gray-50 transition-all" style="min-height: 150px; display: flex; flex-direction: column; justify-content: center;">
                                <div class="text-gray-500">
                                    <p class="text-sm font-medium mb-1">Click to select file</p>
                                    <p class="text-xs">Supported formats: JPG, PNG, GIF</p>
                                    <p class="text-xs text-gray-400 mt-2">Max file size: 2MB</p>
                                </div>
                            </div>
                            <input type="file" id="signature" name="signature" accept="image/jpeg,image/jpg,image/png,image/gif" required class="hidden">
                            
                            <div class="flex gap-2">
                                <button type="submit" class="flex-1 bg-[#1E3A8A] hover:bg-blue-700 text-white text-sm font-medium py-2.5 px-4 rounded-lg transition-colors">
                                    Upload Signature
                                </button>
                                <?php if ($current_signature): ?>
                                <button type="button" onclick="removeSignature()" class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-2.5 px-4 rounded-lg transition-colors">
                                    Remove
                                </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="flex flex-col gap-4 h-full">
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
                        <span class="text-sm text-gray-700 font-medium">E-Signature</span>
                        <?php if ($current_signature): ?>
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Uploaded</span>
                        <?php else: ?>
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">Pending</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

<script>
// Drag & drop zone click handler
document.getElementById('drop-zone').addEventListener('click', () => {
    document.getElementById('signature').click();
});

document.getElementById('signature').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const dropZone = document.getElementById('drop-zone');
    if (file) {
        const fileSize = (file.size / 1024 / 1024).toFixed(2); // Convert to MB
        dropZone.innerHTML = `
            <div class="text-gray-700">
                <p class="text-sm font-medium mb-1">Selected file:</p>
                <p class="text-xs text-[#1E3A8A] font-semibold">${file.name}</p>
                <p class="text-xs text-gray-500 mt-2">${fileSize} MB</p>
            </div>
        `;
    }
});

// Remove signature with confirmation
async function removeSignature() {
    const confirmed = await showConfirmModal({
        title: 'Remove Signature',
        message: 'Are you sure you want to remove your signature? You will need to upload a new one to approve future requests.',
        confirmText: 'Remove Signature',
        cancelText: 'Cancel',
        confirmColor: 'bg-red-600 hover:bg-red-700',
        type: 'danger'
    });
    
    if (confirmed) {
        window.location.href = '../../controller/delete_signature.php';
    }
}
</script>

<?php include '../components/layout_footer.php'; ?>
