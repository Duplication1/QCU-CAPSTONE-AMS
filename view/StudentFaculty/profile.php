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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 flex-1 overflow-hidden">
        
        <!-- Left Column: Profile Info -->
        <div class="lg:col-span-2 flex flex-col gap-2 h-full overflow-hidden">
            <!-- Profile Information Card -->
            <div class="bg-white rounded shadow-sm border border-gray-200 p-3 flex-shrink-0">
                <h3 class="text-sm font-semibold text-gray-800 mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-id-card text-[#1E3A8A]"></i>
                    Profile Information
                </h3>
                
                <div class="space-y-2 text-xs">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-medium text-gray-700">Full Name</label>
                            <p class="mt-0.5 text-xs text-gray-900"><?php echo htmlspecialchars($user_data['full_name'] ?? 'N/A'); ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-medium text-gray-700">Email Address</label>
                            <p class="mt-0.5 text-xs text-gray-900"><?php echo htmlspecialchars($user_data['email'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-medium text-gray-700">Role</label>
                            <p class="mt-0.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#1E3A8A] bg-opacity-10 text-[#1E3A8A]">
                                    <?php echo htmlspecialchars($user_data['role'] ?? 'N/A'); ?>
                                </span>
                            </p>
                        </div>
                        
                        <?php if (isset($user_data['id_number']) && $user_data['id_number']): ?>
                        <div>
                            <label class="block text-[10px] font-medium text-gray-700"><?php echo $user_data['role'] === 'Student' ? 'Student ID' : 'Employee ID'; ?></label>
                            <p class="mt-0.5 text-xs text-gray-900"><?php echo htmlspecialchars($user_data['id_number']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-[10px] font-medium text-gray-700">Member Since</label>
                        <p class="mt-0.5 text-xs text-gray-900">
                            <?php echo date('F j, Y', strtotime($user_data['created_at'] ?? 'now')); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- E-Signature Section -->
            <div class="bg-white rounded shadow-sm border border-gray-200 p-3 flex-1 overflow-y-auto">
                <h3 class="text-sm font-semibold text-gray-800 mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-signature text-[#1E3A8A]"></i>
                    E-Signature Management
                </h3>

                <div class="grid grid-cols-2 gap-2">
                    <!-- Current Signature Display -->
                    <div class="flex flex-col">
                        <h4 class="text-[10px] font-semibold text-gray-800 mb-1">Current Signature</h4>
                        <div class="border-2 border-dashed border-gray-300 rounded p-2 bg-gray-50 flex items-center justify-center" style="height: 100px;">
                            <?php if ($current_signature && file_exists('../../uploads/signatures/' . $current_signature)): ?>
                            <div class="text-center">
                                <img src="../../uploads/signatures/<?php echo htmlspecialchars($current_signature); ?>" 
                                     alt="Signature" 
                                     class="max-h-16 max-w-full object-contain mx-auto">
                                <span class="inline-block bg-green-100 text-green-800 text-[10px] font-semibold px-1.5 py-0.5 rounded-full mt-1">
                                    Active
                                </span>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-gray-400">
                                <i class="fa-solid fa-signature text-xl mb-1"></i>
                                <p class="text-[10px]">No signature</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Upload Form -->
                    <div class="flex flex-col">
                        <h4 class="text-[10px] font-semibold text-gray-800 mb-1">Upload Signature</h4>
                        <form action="../../controller/upload_signature.php" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                            <div id="drop-zone" class="border-2 border-dashed border-gray-300 rounded p-2 bg-gray-50 text-center cursor-pointer hover:border-[#1E3A8A] flex items-center justify-center" style="height: 100px;">
                                <p class="text-[10px] text-gray-600">Click to upload</p>
                            </div>
                            <input type="file" id="signature" name="signature" accept="image/jpeg,image/jpg,image/png,image/gif" required class="hidden">
                            
                            <div class="flex gap-1">
                                <button type="submit" class="flex-1 bg-[#1E3A8A] hover:bg-blue-700 text-white text-[10px] font-medium py-1 px-2 rounded transition-colors">
                                    <i class="fa-solid fa-upload mr-0.5"></i>Upload
                                </button>
                                <?php if ($current_signature): ?>
                                <button type="button" onclick="removeSignature()" class="bg-red-600 hover:bg-red-700 text-white text-[10px] font-medium py-1 px-2 rounded transition-colors">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </form>
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
                        <span class="px-1.5 py-0.5 bg-green-100 text-green-800 text-[10px] rounded-full">Active</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-gray-600">E-Signature</span>
                        <?php if ($current_signature): ?>
                        <span class="px-1.5 py-0.5 bg-green-100 text-green-800 text-[10px] rounded-full">Uploaded</span>
                        <?php else: ?>
                        <span class="px-1.5 py-0.5 bg-yellow-100 text-yellow-800 text-[10px] rounded-full">Pending</span>
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
        dropZone.querySelector('p').textContent = file.name;
    }
});

// Remove signature with confirmation
async function removeSignature() {
    const confirmed = await showConfirmModal({
        title: 'Remove Signature',
        message: 'Are you sure you want to remove your signature?',
        confirmText: 'Remove',
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
