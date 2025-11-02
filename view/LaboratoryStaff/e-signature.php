<?php
session_start();

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
require_once '../../model/Database.php';

// Get current user's signature
$user_id = $_SESSION['user_id'];
$current_signature = null;

try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT e_signature FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_signature = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Handle error silently
}

include '../components/layout_header.php';
?>

        <!-- Main Content -->
        <main class="p-6">
        
        <?php if (isset($_SESSION['error_message'])): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    <strong>Error:</strong> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
</div>
<?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
    <strong>Success:</strong> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
</div>
<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

        <!-- E-Signature Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                <i class="fa-solid fa-signature text-blue-600"></i> My E-Signature
            </h2>
            <p class="text-gray-600 mb-6">Upload and manage your electronic signature for document signing and approvals.</p>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Current Signature Display -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Current Signature</h3>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 flex items-center justify-center" style="min-height: 200px;">
                        <?php if ($current_signature && file_exists('../../uploads/signatures/' . $current_signature)): ?>
                            <img src="../../uploads/signatures/<?php echo htmlspecialchars($current_signature); ?>" 
                                 alt="Current E-Signature" 
                                 class="max-h-48 max-w-full object-contain">
                        <?php else: ?>
                            <div class="text-center text-gray-400">
                                <i class="fa-solid fa-signature text-4xl mb-2"></i>
                                <p>No signature uploaded yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($current_signature): ?>
                    <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-700">
                            <i class="fa-solid fa-circle-check mr-2"></i>
                            Your signature will be used for approving borrowing requests and other documents.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Upload Form -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Upload New Signature</h3>
                    <form action="../../controller/upload_signature.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <div>
                            <label for="signature" class="block text-sm font-medium text-gray-700 mb-2">
                                Choose Signature Image
                            </label>
                            <input type="file" 
                                   id="signature" 
                                   name="signature" 
                                   accept="image/jpeg,image/jpg,image/png,image/gif"
                                   required
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                            <p class="mt-2 text-xs text-gray-500">
                                Accepted formats: JPG, PNG, GIF (Max size: 2MB)
                            </p>
                        </div>

                        <!-- Preview Area -->
                        <div id="preview-container" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Preview</label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 bg-gray-50">
                                <img id="preview-image" src="" alt="Preview" class="max-h-32 max-w-full object-contain mx-auto">
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" 
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                <i class="fa-solid fa-upload mr-2"></i>Upload Signature
                            </button>
                            <?php if ($current_signature): ?>
                            <button type="button" 
                                    onclick="if(confirm('Are you sure you want to remove your signature?')) window.location.href='../../controller/delete_signature.php'"
                                    class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                <i class="fa-solid fa-trash mr-2"></i>Remove
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Guidelines -->
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <h4 class="font-semibold text-blue-800 mb-2">
                            <i class="fa-solid fa-circle-info mr-2"></i>Signature Guidelines
                        </h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>• Use a clear, high-contrast signature image</li>
                            <li>• White or transparent background works best</li>
                            <li>• Sign on white paper and scan/photograph it</li>
                            <li>• Ensure signature is centered and legible</li>
                            <li>• Your signature will appear on official documents</li>
                        </ul>
                    </div>
                    
                    <div class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                        <h4 class="font-semibold text-yellow-800 mb-2">
                            <i class="fa-solid fa-triangle-exclamation mr-2"></i>Important Note
                        </h4>
                        <p class="text-sm text-yellow-700">
                            As Laboratory Staff, your e-signature will be used to approve borrowing requests and validate official laboratory documents. Ensure your signature is professional and matches your official records.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // Image preview functionality
        document.getElementById('signature').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-image').src = e.target.result;
                    document.getElementById('preview-container').classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });
        </script>

        </main>

<?php include '../components/layout_footer.php'; ?>
