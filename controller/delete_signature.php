<?php
session_start();
require_once '../config/config.php';
require_once '../model/Database.php';

function wantsJsonResponse() {
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    return strpos($acceptHeader, 'application/json') !== false || $requestedWith === 'xmlhttprequest';
}

function finishDeleteResponse($success, $message, $redirectUrl, $isJson, $statusCode = 200) {
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

$user_id = $_SESSION['user_id'];

// Determine redirect URL based on role
$redirect_url = match($_SESSION['role']) {
    'Laboratory Staff' => '../view/LaboratoryStaff/e-signature.php',
    default => '../view/StudentFaculty/profile.php'
};

$isJson = wantsJsonResponse();

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if user has a signature
    $stmt = $conn->prepare("SELECT e_signature FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $signature_data = $stmt->fetchColumn();
    
    if ($signature_data) {
        if (strpos($signature_data, 'data:image/') !== 0) {
            $signature_file = basename($signature_data);
            $signature_path = __DIR__ . '/../uploads/signatures/' . $signature_file;
            if (is_file($signature_path)) {
                @unlink($signature_path);
            }
        }

        // Update database to remove signature
        $stmt = $conn->prepare("UPDATE users SET e_signature = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Log the signature deletion
        try {
            require_once '../model/ActivityLog.php';
            ActivityLog::record(
                $user_id,
                'delete',
                'signature',
                $user_id,
                'Deleted e-signature'
            );
        } catch (Exception $logError) {
            error_log('Failed to log signature deletion: ' . $logError->getMessage());
        }
        
        finishDeleteResponse(true, 'E-signature removed successfully.', $redirect_url, $isJson);
    } else {
        finishDeleteResponse(false, 'No signature found to remove.', $redirect_url, $isJson, 404);
    }
} catch (PDOException $e) {
    finishDeleteResponse(false, 'Failed to remove e-signature.', $redirect_url, $isJson, 500);
}

finishDeleteResponse(false, 'Failed to remove e-signature.', $redirect_url, $isJson, 500);
?>
