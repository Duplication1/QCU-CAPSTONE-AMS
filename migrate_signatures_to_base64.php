<?php
/**
 * Migration Script: Convert file-based signatures to Base64 in database
 * Run this ONCE after changing the database column to LONGTEXT
 * Access via: http://localhost/QCU-CAPSTONE-AMS/migrate_signatures_to_base64.php
 */

require_once 'config/config.php';
require_once 'model/Database.php';

// Security: Only run in development or with proper authentication
// Uncomment to require authentication
// session_start();
// if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'Admin') {
//     die('Unauthorized access');
// }

echo "<!DOCTYPE html><html><head><title>Signature Migration</title>";
echo "<style>body{font-family:Arial;padding:20px;max-width:800px;margin:0 auto;}";
echo ".success{color:green;} .error{color:red;} .info{color:blue;}</style></head><body>";
echo "<h1>Migrating Signatures to Base64</h1>";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get all users with signatures that are filenames (not Base64)
    $stmt = $conn->prepare("SELECT id, full_name, e_signature FROM users WHERE e_signature IS NOT NULL AND e_signature NOT LIKE 'data:image/%'");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    $total = count($users);
    $migrated = 0;
    $failed = 0;
    $skipped = 0;
    
    echo "<p class='info'>Found {$total} users with file-based signatures</p>";
    echo "<hr>";
    
    foreach ($users as $user) {
        $filename = $user['e_signature'];
        $filepath = __DIR__ . '/uploads/signatures/' . $filename;
        
        echo "<p><strong>{$user['full_name']}</strong> (ID: {$user['id']}): ";
        
        // Check if file exists
        if (!file_exists($filepath)) {
            echo "<span class='error'>❌ File not found: {$filename}</span></p>";
            $failed++;
            continue;
        }
        
        // Get MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        
        // Validate MIME type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedTypes)) {
            echo "<span class='error'>❌ Invalid file type: {$mimeType}</span></p>";
            $failed++;
            continue;
        }
        
        // Read file
        $imageData = file_get_contents($filepath);
        if ($imageData === false) {
            echo "<span class='error'>❌ Cannot read file</span></p>";
            $failed++;
            continue;
        }
        
        // Load image based on type
        $image = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($filepath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($filepath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($filepath);
                break;
        }
        
        if (!$image) {
            echo "<span class='error'>❌ Cannot process image</span></p>";
            $failed++;
            continue;
        }
        
        // Resize to reduce size
        $max_width = 400;
        $max_height = 150;
        $orig_width = imagesx($image);
        $orig_height = imagesy($image);
        
        $ratio = min($max_width / $orig_width, $max_height / $orig_height, 1);
        $new_width = (int)($orig_width * $ratio);
        $new_height = (int)($orig_height * $ratio);
        
        $resized = imagecreatetruecolor($new_width, $new_height);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        
        // Convert to PNG
        ob_start();
        imagepng($resized, null, 6);
        $processedData = ob_get_clean();
        
        imagedestroy($image);
        imagedestroy($resized);
        
        // Create Base64 data URI
        $base64 = 'data:image/png;base64,' . base64_encode($processedData);
        
        // Update database
        $updateStmt = $conn->prepare("UPDATE users SET e_signature = ? WHERE id = ?");
        $result = $updateStmt->execute([$base64, $user['id']]);
        
        if ($result) {
            $size = strlen($base64);
            echo "<span class='success'>✅ Migrated ({$size} bytes)</span></p>";
            $migrated++;
        } else {
            echo "<span class='error'>❌ Database update failed</span></p>";
            $failed++;
        }
    }
    
    echo "<hr>";
    echo "<h2>Migration Summary</h2>";
    echo "<p class='info'><strong>Total:</strong> {$total}</p>";
    echo "<p class='success'><strong>Migrated:</strong> {$migrated}</p>";
    echo "<p class='error'><strong>Failed:</strong> {$failed}</p>";
    
    if ($migrated > 0) {
        echo "<hr>";
        echo "<p class='success'><strong>✅ Migration completed successfully!</strong></p>";
        echo "<p>You can now safely delete the old signature files from <code>uploads/signatures/</code></p>";
        echo "<p><em>Note: You may want to keep a backup before deleting.</em></p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p><a href='view/login.php'>← Back to Login</a></p>";
echo "</body></html>";
?>
