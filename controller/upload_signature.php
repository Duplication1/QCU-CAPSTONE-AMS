<?php
session_start();
require_once '../config/config.php';
require_once '../model/Database.php';

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
            default => '../view/StudentFaculty/e-signature.php'
        };
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message'] = "Invalid file type. Only JPG, PNG, and GIF images are allowed.";
            header("Location: $redirect_url");
            exit();
        }
        
        // Validate file size (max 2MB)
        $max_size = 2 * 1024 * 1024; // 2MB in bytes
        if ($file['size'] > $max_size) {
            $_SESSION['error_message'] = "File size exceeds 2MB limit.";
            header("Location: $redirect_url");
            exit();
        }
        
        // Create signatures directory if it doesn't exist
        $upload_dir = '../uploads/signatures/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'signature_' . $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        // Delete old signature if exists
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Get old signature filename
            $stmt = $conn->prepare("SELECT e_signature FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $old_signature = $stmt->fetchColumn();
            
            if ($old_signature && file_exists($upload_dir . $old_signature)) {
                unlink($upload_dir . $old_signature);
            }
        } catch (PDOException $e) {
            // Continue even if old file deletion fails
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            try {
                $db = new Database();
                $conn = $db->getConnection();
                
                // Update user's e-signature in database
                $stmt = $conn->prepare("UPDATE users SET e_signature = ? WHERE id = ?");
                $stmt->execute([$new_filename, $user_id]);
                
                $_SESSION['success_message'] = "E-signature uploaded successfully!";
                header("Location: $redirect_url");
                exit();
            } catch (PDOException $e) {
                // Delete uploaded file if database update fails
                if (file_exists($upload_path)) {
                    unlink($upload_path);
                }
                $_SESSION['error_message'] = "Failed to save e-signature to database.";
                header("Location: $redirect_url");
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Failed to upload file.";
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
            default => '../view/StudentFaculty/e-signature.php'
        };
        
        header("Location: $redirect_url");
        exit();
    }
} else {
    $_SESSION['error_message'] = "Invalid request method.";
    
    // Determine redirect URL based on role
    $redirect_url = match($_SESSION['role']) {
        'Laboratory Staff' => '../view/LaboratoryStaff/e-signature.php',
        default => '../view/StudentFaculty/e-signature.php'
    };
    
    header("Location: $redirect_url");
    exit();
}
?>
