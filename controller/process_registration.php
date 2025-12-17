<?php
session_start();
require_once '../config/config.php';
require_once '../model/Database.php';

// Check if user is administrator
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'Administrator') {
    $_SESSION['error_message'] = "Unauthorized access.";
    header("Location: ../view/login.php");
    exit();
}

if (!isset($_GET['action']) || !isset($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: ../view/Administrator/registration_requests.php");
    exit();
}

$action = $_GET['action'];
$id = intval($_GET['id']);
$admin_id = $_SESSION['user_id'];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get registration request
    $stmt = $conn->prepare("SELECT * FROM registration_requests WHERE id = ? AND status = 'Pending'");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['error_message'] = "Registration request not found or already processed.";
        header("Location: ../view/Administrator/registration_requests.php");
        exit();
    }
    
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($action === 'approve') {
        // Begin transaction
        $conn->beginTransaction();
        
        try {
            // Create user account
            $stmt = $conn->prepare("
                INSERT INTO users 
                (id_number, password, full_name, email, role, status, 
                 security_question_1, security_answer_1, security_question_2, security_answer_2, 
                 created_at) 
                VALUES (?, ?, ?, ?, ?, 'Active', ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $request['id_number'],
                $request['password'],
                $request['full_name'],
                $request['email'],
                $request['role'],
                $request['security_question_1'],
                $request['security_answer_1'],
                $request['security_question_2'],
                $request['security_answer_2']
            ]);
            
            // Update registration request status
            $stmt = $conn->prepare("
                UPDATE registration_requests 
                SET status = 'Approved', reviewed_by = ?, reviewed_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$admin_id, $id]);
            
            $conn->commit();
            
            $_SESSION['success_message'] = "Registration request approved successfully! User account has been created.";
            
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        
    } elseif ($action === 'reject') {
        $reason = isset($_GET['reason']) ? trim($_GET['reason']) : 'No reason provided';
        
        // Update registration request status
        $stmt = $conn->prepare("
            UPDATE registration_requests 
            SET status = 'Rejected', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ? 
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $reason, $id]);
        
        $_SESSION['success_message'] = "Registration request rejected.";
        
    } else {
        $_SESSION['error_message'] = "Invalid action.";
    }
    
    header("Location: ../view/Administrator/registration_requests.php");
    exit();
    
} catch (Exception $e) {
    error_log("Error processing registration request: " . $e->getMessage());
    $_SESSION['error_message'] = "An error occurred while processing the request.";
    header("Location: ../view/Administrator/registration_requests.php");
    exit();
}
?>
