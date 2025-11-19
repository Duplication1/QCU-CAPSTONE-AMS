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
        <main class="pt-4 px-6">
        
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
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                <i class="fa-solid fa-signature text-[#1E3A8A]"></i> My E-Signature
            </h2>
            <p class="text-gray-600 mb-6">Upload and manage your electronic signature for document signing.</p>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Current Signature Display -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Current Signature</h3>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 flex items-center justify-center" style="min-height: 200px;">
<?php if ($current_signature && file_exists('../../uploads/signatures/' . $current_signature)): ?>
<div class="shadow-sm border rounded-lg p-4 bg-white text-center">
  <img src="../../uploads/signatures/<?php echo htmlspecialchars($current_signature); ?>" 
       alt="Preview of your uploaded signature" 
       class="max-h-48 max-w-full object-contain mx-auto">
  <span class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 rounded-full mt-2">
    Signature Active
  </span>
</div>



    <?php
    $timestamp = null;
    $path = '../../uploads/signatures/' . $current_signature;
    if (file_exists($path)) {
        $timestamp = date("F j, Y, g:i a", filemtime($path));
    }
    ?>
    <?php if ($timestamp): ?>
        <p class="text-xs text-gray-500 mt-2 text-center">
            Uploaded on <?php echo $timestamp; ?>
        </p>
    <?php endif; ?>

<?php else: ?>
    <div class="text-center text-gray-400">
        <i class="fa-solid fa-signature text-4xl mb-2"></i>
        <p>No signature uploaded yet</p>
    </div>
<?php endif; ?>

                    </div>
                </div>

                <!-- Upload Form -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Upload New Signature</h3>
                    <form action="../../controller/upload_signature.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <div>
                            <label for="signature" class="block text-sm font-medium text-gray-700 mb-2">
                                Choose Signature Image
                            </label>

                <!-- Drag & Drop Upload Zone -->
                <div id="drop-zone" class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 text-center cursor-pointer hover:border-blue-600">
                <p class="text-sm text-gray-600">Drag & drop your signature here or click to choose</p>
                </div>

                <!-- Hidden File Input -->
                <input type="file" 
                id="signature" 
                name="signature" 
                accept="image/jpeg,image/jpg,image/png,image/gif"
                required
                class="hidden">
                <p class="mt-2 text-xs text-gray-500">
                Accepted formats: JPG, PNG, GIF (Max size: 2MB)
                </p>
           </div>

            <!-- Preview Area -->
            <div id="preview-container" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Preview</label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 bg-gray-50">
                <img id="preview-image" src="" alt="Preview of your uploaded signature" class="max-h-32 max-w-full object-contain mx-auto">
            </div>

            <!-- Reset Button -->
            <div class="text-center mt-2">
            <button type="button" onclick="resetPreview()" class="text-sm text-red-600 hover:underline">
            Reset Preview
            </button>
            </div>
        </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="submit" 
                        aria-label="Upload your signature"
                        class="flex-1 bg-[#1E3A8A] hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
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
<hr class="my-4 border-gray-200">

<div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
    <h4 class="font-semibold text-blue-800 mb-3 flex items-center gap-2">
        <i class="fa-solid fa-circle-info text-blue-600 bg-blue-100 p-1 rounded-full"></i>
        Guidelines
    </h4>
    <ul class="list-disc pl-5 text-sm text-blue-700 space-y-1">
        <li>Use a clear, high-contrast signature image</li>
        <li>White or transparent background works best</li>
        <li>Sign on white paper and scan/photograph it</li>
        <li>Ensure signature is centered and legible</li>
    </ul>
</div>

                </div>
            </div>
        </div>

<script>
  // Enhanced image preview with reset and file name display
  document.getElementById('signature').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('preview-container');
    const img = document.getElementById('preview-image');
    const dropZone = document.getElementById('drop-zone');

    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        img.src = e.target.result;
        preview.classList.remove('hidden');
        preview.style.opacity = '1';
        dropZone.querySelector('p').textContent = file.name;
      };
      reader.readAsDataURL(file);
    } else {
      img.src = '';
      preview.classList.add('hidden');
      dropZone.querySelector('p').textContent = 'Drag & drop your signature here or click to choose';
    }
  });

  // Drag & drop zone click handler
  document.getElementById('drop-zone').addEventListener('click', () => {
    document.getElementById('signature').click();
  });

  // Reset preview
  function resetPreview() {
    const input = document.getElementById('signature');
    input.value = '';
    document.getElementById('preview-image').src = '';
    document.getElementById('preview-container').classList.add('hidden');
    document.getElementById('drop-zone').querySelector('p').textContent = 'Drag & drop your signature here or click to choose';
  }
</script>

</main>

<?php include '../components/layout_footer.php'; ?>
