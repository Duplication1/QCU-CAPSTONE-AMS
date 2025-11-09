<?php
session_start();
require_once '../config/config.php';
require_once '../model/Asset.php';

// Check if user is logged in and has administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }
    $_SESSION['error_message'] = 'Unauthorized access';
    header("Location: ../view/Administrator/assets.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit();
    }
    $_SESSION['error_message'] = 'Invalid request method';
    header("Location: ../view/Administrator/assets.php");
    exit();
}

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

try {
    $asset = new Asset();
    
    // Set required fields
    $asset->asset_tag = $_POST['asset_tag'] ?? null;
    $asset->asset_name = $_POST['asset_name'] ?? null;
    $asset->asset_type = $_POST['asset_type'] ?? null;
    
    if (!$asset->asset_tag || !$asset->asset_name || !$asset->asset_type) {
        throw new Exception('Required fields are missing');
    }
    
    // Set optional fields
    $asset->category = $_POST['category'] ?? null;
    $asset->brand = $_POST['brand'] ?? null;
    $asset->model = $_POST['model'] ?? null;
    $asset->serial_number = $_POST['serial_number'] ?? null;
    $asset->specifications = $_POST['specifications'] ?? null;
    $asset->room_id = !empty($_POST['room_id']) ? $_POST['room_id'] : null;
    $asset->location = $_POST['location'] ?? null;
    $asset->terminal_number = $_POST['terminal_number'] ?? null;
    $asset->status = $_POST['status'] ?? 'Available';
    $asset->condition = $_POST['condition'] ?? 'Good';
    $asset->is_borrowable = isset($_POST['is_borrowable']) ? 1 : 0;
    $asset->purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $asset->purchase_cost = !empty($_POST['purchase_cost']) ? $_POST['purchase_cost'] : null;
    $asset->supplier = $_POST['supplier'] ?? null;
    $asset->warranty_expiry = !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null;
    $asset->notes = $_POST['notes'] ?? null;
    $asset->created_by = $_SESSION['user_id'];
    $asset->updated_by = $_SESSION['user_id'];
    
    // Handle image upload if present
    if (isset($_FILES['asset_image']) && $_FILES['asset_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/assets/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['asset_image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = 'asset_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['asset_image']['tmp_name'], $uploadPath)) {
                $asset->image = 'uploads/assets/' . $fileName;
            }
        }
    }
    
    // Check if we're updating or creating
    $asset_id = $_POST['asset_id'] ?? null;
    
    if ($asset_id) {
        // Update existing asset
        $asset->id = $asset_id;
        if ($asset->update()) {
            $message = 'Asset updated successfully';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $message]);
                exit();
            }
            $_SESSION['success_message'] = $message;
        } else {
            throw new Exception('Failed to update asset');
        }
    } else {
        // Create new asset
        if ($asset->create()) {
            $message = 'Asset created successfully';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $message]);
                exit();
            }
            $_SESSION['success_message'] = $message;
        } else {
            throw new Exception('Failed to create asset');
        }
    }
    
} catch (Exception $e) {
    $errorMessage = 'Error: ' . $e->getMessage();
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $errorMessage]);
        exit();
    }
    $_SESSION['error_message'] = $errorMessage;
}

if (!$isAjax) {
    header("Location: ../view/Administrator/assets.php");
    exit();
}
?>
