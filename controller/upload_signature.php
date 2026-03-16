<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/Database.php';

function wantsJsonResponse() {
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    return strpos($acceptHeader, 'application/json') !== false || $requestedWith === 'xmlhttprequest';
}

function finishUploadResponse($success, $message, $redirectUrl, $isJson, $statusCode = 200) {
    if ($isJson) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit();
    }

    if ($success) {
        $_SESSION['success_message'] = $message;
    } else {
        $_SESSION['error_message'] = $message;
    }

    header("Location: $redirectUrl");
    exit();
}

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || !in_array($_SESSION['role'], ['Student', 'Faculty', 'Laboratory Staff'])) {
    $_SESSION['error_message'] = "Unauthorized access.";
    header("Location: ../view/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $redirect_url = match($_SESSION['role']) {
        'Laboratory Staff' => '../view/LaboratoryStaff/e-signature.php',
        default => '../view/StudentFaculty/profile.php'
    };
    finishUploadResponse(false, 'Invalid request method.', $redirect_url, wantsJsonResponse(), 405);
}

$user_id = $_SESSION['user_id'];
$redirect_url = match($_SESSION['role']) {
    'Laboratory Staff' => '../view/LaboratoryStaff/e-signature.php',
    default => '../view/StudentFaculty/profile.php'
};
$isJson = wantsJsonResponse();

if (!isset($_FILES['signature']) || $_FILES['signature']['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'No file uploaded.';
    if (isset($_FILES['signature'])) {
        switch ($_FILES['signature']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = 'File size exceeds maximum allowed.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'File was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'No file was uploaded.';
                break;
            default:
                $error_message = 'An error occurred during file upload.';
        }
    }
    finishUploadResponse(false, $error_message, $redirect_url, $isJson, 400);
}

$file = $_FILES['signature'];
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$type_to_extension = [
    'image/jpeg' => 'jpg',
    'image/jpg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif'
];

$file_type = mime_content_type($file['tmp_name']);
if (!in_array($file_type, $allowed_types, true)) {
    finishUploadResponse(false, 'Invalid file type. Only JPG, PNG, and GIF images are allowed.', $redirect_url, $isJson, 400);
}

$max_size = 2 * 1024 * 1024;
if ($file['size'] > $max_size) {
    finishUploadResponse(false, 'File size exceeds 2MB limit.', $redirect_url, $isJson, 400);
}

$signatures_directory = __DIR__ . '/../uploads/signatures';
if (!is_dir($signatures_directory) && !mkdir($signatures_directory, 0775, true)) {
    finishUploadResponse(false, 'Failed to create signature directory.', $redirect_url, $isJson, 500);
}

$safe_extension = $type_to_extension[$file_type] ?? 'png';
$filename = 'signature_' . $user_id . '_' . time();
$target_filename = $filename . '.' . $safe_extension;
$target_path = $signatures_directory . '/' . $target_filename;

$has_gd = extension_loaded('gd');

if ($has_gd) {
    $image = null;
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
        finishUploadResponse(false, 'Failed to process image.', $redirect_url, $isJson, 400);
    }

    $max_width = 400;
    $max_height = 150;
    $orig_width = imagesx($image);
    $orig_height = imagesy($image);
    $ratio = min($max_width / $orig_width, $max_height / $orig_height, 1);
    $new_width = max((int)($orig_width * $ratio), 1);
    $new_height = max((int)($orig_height * $ratio), 1);

    $resized = imagecreatetruecolor($new_width, $new_height);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
    imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
    imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);

    $target_filename = $filename . '.png';
    $target_path = $signatures_directory . '/' . $target_filename;
    $saved = imagepng($resized, $target_path, 6);

    imagedestroy($image);
    imagedestroy($resized);

    if (!$saved) {
        finishUploadResponse(false, 'Failed to save processed signature file.', $redirect_url, $isJson, 500);
    }
} else {
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        finishUploadResponse(false, 'Failed to save uploaded signature file.', $redirect_url, $isJson, 500);
    }
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $old_stmt = $conn->prepare('SELECT e_signature FROM users WHERE id = ?');
    $old_stmt->execute([$user_id]);
    $old_signature = $old_stmt->fetchColumn();

    $stmt = $conn->prepare('UPDATE users SET e_signature = ? WHERE id = ?');
    $stmt->execute([$target_filename, $user_id]);

    if ($old_signature && strpos($old_signature, 'data:image/') !== 0) {
        $old_file = basename($old_signature);
        $old_path = $signatures_directory . '/' . $old_file;
        if (is_file($old_path)) {
            @unlink($old_path);
        }
    }

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

    finishUploadResponse(true, 'E-signature uploaded successfully!', $redirect_url, $isJson);
} catch (PDOException $e) {
    if (is_file($target_path)) {
        @unlink($target_path);
    }
    finishUploadResponse(false, 'Failed to save e-signature.', $redirect_url, $isJson, 500);
}
?>
