<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/Database.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || !in_array($_SESSION['role'], ['Student', 'Faculty', 'Laboratory Staff'])) {
    $_SESSION['error_message'] = "Unauthorized access.";
    header("Location: ../view/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Check if file was uploaded
    if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['signature'];
        
        // Determine redirect URL based on role
        $redirect_url = match($_SESSION['role']) {
            'Laboratory Staff' => '../view/LaboratoryStaff/e-signature.php',
            default => '../view/StudentFaculty/profile.php'
        };
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message'] = "Invalid file type. Only JPG, PNG, and GIF images are allowed.";
            header("Location: $redirect_url");
            exit();
        }
        
        // Check if GD extension is available
        $has_gd = extension_loaded('gd');
        
        if ($has_gd) {
            // GD is available - resize and optimize image
            $max_size = 2 * 1024 * 1024; // 2MB for original file
            if ($file['size'] > $max_size) {
                $_SESSION['error_message'] = "File size exceeds 2MB limit.";
                header("Location: $redirect_url");
                exit();
            }
            
            // Load image based on type
            $image = null;
            $max_width = 400;
            $max_height = 150;
            
            switch ($file_type) {
                case 'image/jpeg':
                case 'image/jpg':
                    $image = @imagecreatefromjpeg($file['tmp_name']);
                    break;
                case 'image/png':
                    $image = @imagecreatefrompng($file['tmp_name']);
                    break;
                case 'image/gif':
                    $image = @imagecreatefromgif($file['tmp_name']);
                    break;
            }
            
            if (!$image) {
                $_SESSION['error_message'] = "Failed to process image.";
                header("Location: $redirect_url");
                exit();
            }
            
            // Resize image
            $orig_width = imagesx($image);
            $orig_height = imagesy($image);
            $ratio = min($max_width / $orig_width, $max_height / $orig_height, 1);
            $new_width = (int)($orig_width * $ratio);
            $new_height = (int)($orig_height * $ratio);
            
            $resized = imagecreatetruecolor($new_width, $new_height);
            
            // Preserve transparency
            if ($file_type === 'image/png' || $file_type === 'image/gif') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
            }
            
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
            
            // Convert to PNG
            ob_start();
            imagepng($resized, null, 6);
            $image_data = ob_get_clean();
            
            imagedestroy($image);
            imagedestroy($resized);
            
            $base64_image = 'data:image/png;base64,' . base64_encode($image_data);
            
        } else {
            // GD not available - use original file with stricter size limit
            $max_size = 200 * 1024; // 200KB limit when no compression available
            if ($file['size'] > $max_size) {
                $_SESSION['error_message'] = "File size exceeds 200KB limit. Please use a smaller image or enable GD extension for auto-compression.";
                header("Location: $redirect_url");
                exit();
            }
            
            $image_data = file_get_contents($file['tmp_name']);
            if ($image_data === false) {
                $_SESSION['error_message'] = "Failed to read uploaded file.";
                header("Location: $redirect_url");
                exit();
            }
            
            $base64_image = 'data:' . $file_type . ';base64,' . base64_encode($image_data);
        }
        
        // Verify final Base64 size
        if (strlen($base64_image) > 524288) { // 500KB
            $_SESSION['error_message'] = "Signature file is too large. Please use a smaller or simpler image.";
            header("Location: $redirect_url");
            exit();
        }
        
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Update user's e-signature in database with Base64 data
            $stmt = $conn->prepare("UPDATE users SET e_signature = ? WHERE id = ?");
            $stmt->execute([$base64_image, $user_id]);
            
            // Log activity for Laboratory Staff
            if ($_SESSION['role'] === 'Laboratory Staff') {
                require_once __DIR__ . '/../model/ActivityLog.php';
                ActivityLog::record(
                    $user_id,
                    'upload',
                    'signature',
                    $user_id,
                    'Uploaded e-signature'
                );
            }
            
            $_SESSION['success_message'] = "E-signature uploaded successfully!";
            header("Location: $redirect_url");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Failed to save e-signature to database: " . $e->getMessage();
            header("Location: $redirect_url");
            exit();
        }
    } else {
        $error_message = "No file uploaded.";
        if (isset($_FILES['signature'])) {
            switch ($_FILES['signature']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message = "File size exceeds maximum allowed.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message = "File was only partially uploaded.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_message = "No file was uploaded.";
                    break;
                default:
                    $error_message = "An error occurred during file upload.";
            }
        }
        $_SESSION['error_message'] = $error_message;
        
        // Determine redirect URL based on role
        $redirect_url = match($_SESSION['role']) {
            'Laboratory Staff' => '../view/LaboratoryStaff/e-signature.php',
            default => '../view/StudentFaculty/profile.php'
        };
        
        header("Location: $redirect_url");
        exit();
    }
} else {
    $_SESSION['error_message'] = "Invalid request method.";
    
    // Determine redirect URL based on role
    $redirect_url = match($_SESSION['role']) {
        'Laboratory Staff' => '../view/LaboratoryStaff/e-signature.php',
        default => '../view/StudentFaculty/profile.php'
    };
    
    header("Location: $redirect_url");
    exit();
}
?>
