<?php
session_start();
require_once '../config/config.php';
require_once '../model/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = isset($_POST['step']) ? $_POST['step'] : '1';
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Step 1: Verify ID number and retrieve security questions
        if ($step === '1') {
            $id_number = trim($_POST['id_number']);
            
            if (empty($id_number)) {
                $_SESSION['error_message'] = "Please enter your ID number.";
                header("Location: ../view/forgot_password.php");
                exit();
            }
            
            // Check if user exists and has security questions set
            $stmt = $conn->prepare("
                SELECT id, security_question_1, security_question_2, security_answer_1, security_answer_2 
                FROM users 
                WHERE id_number = ? AND status = 'Active'
            ");
            $stmt->execute([$id_number]);
            
            if ($stmt->rowCount() === 0) {
                $_SESSION['error_message'] = "ID number not found or account is not active.";
                header("Location: ../view/forgot_password.php");
                exit();
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (empty($user['security_question_1']) || empty($user['security_question_2'])) {
                $_SESSION['error_message'] = "No security questions found for this account. Please contact administrator.";
                header("Location: ../view/forgot_password.php");
                exit();
            }
            
            // Store user info in session
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['security_question_1'] = $user['security_question_1'];
            $_SESSION['security_question_2'] = $user['security_question_2'];
            $_SESSION['security_answer_1_hash'] = $user['security_answer_1'];
            $_SESSION['security_answer_2_hash'] = $user['security_answer_2'];
            
            header("Location: ../view/forgot_password.php");
            exit();
        }
        
        // Step 2: Verify security answers
        elseif ($step === '2') {
            if (!isset($_SESSION['reset_user_id'])) {
                $_SESSION['error_message'] = "Session expired. Please start over.";
                header("Location: ../view/forgot_password.php");
                exit();
            }
            
            $answer_1 = trim($_POST['security_answer_1']);
            $answer_2 = trim($_POST['security_answer_2']);
            
            if (empty($answer_1) || empty($answer_2)) {
                $_SESSION['error_message'] = "Please answer both security questions.";
                header("Location: ../view/forgot_password.php");
                exit();
            }
            
            // Verify answers (case-insensitive)
            $answer_1_match = password_verify(strtolower($answer_1), $_SESSION['security_answer_1_hash']);
            $answer_2_match = password_verify(strtolower($answer_2), $_SESSION['security_answer_2_hash']);
            
            if (!$answer_1_match || !$answer_2_match) {
                $_SESSION['error_message'] = "Security answers are incorrect. Please try again.";
                header("Location: ../view/forgot_password.php");
                exit();
            }
            
            // Answers verified
            $_SESSION['reset_verified'] = true;
            unset($_SESSION['security_answer_1_hash']);
            unset($_SESSION['security_answer_2_hash']);
            
            header("Location: ../view/forgot_password.php");
            exit();
        }
        
        // Step 3: Reset password
        elseif ($step === '3') {
            if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
                $_SESSION['error_message'] = "Unauthorized access. Please start over.";
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_verified']);
                header("Location: ../view/forgot_password.php");
                exit();
            }
            
            $new_password = trim($_POST['new_password']);
            $confirm_password = trim($_POST['confirm_password']);
            
            if (empty($new_password) || empty($confirm_password)) {
                $_SESSION['error_message'] = "Please enter your new password.";
                header("Location: ../view/forgot_password.php");
                exit();
            }
            
            if ($new_password !== $confirm_password) {
                $_SESSION['error_message'] = "Passwords do not match.";
                header("Location: ../view/forgot_password.php");
                exit();
            }
            
            if (strlen($new_password) < 6) {
                $_SESSION['error_message'] = "Password must be at least 6 characters long.";
                header("Location: ../view/forgot_password.php");
                exit();
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['reset_user_id']]);
            
            // Clear session variables
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['security_question_1']);
            unset($_SESSION['security_question_2']);
            unset($_SESSION['reset_verified']);
            
            $_SESSION['success'] = "Password reset successfully! You can now login with your new password.";
            header("Location: ../view/login.php");
            exit();
        }
        
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        $_SESSION['error_message'] = "An error occurred. Please try again.";
        header("Location: ../view/forgot_password.php");
        exit();
    }
} else {
    header("Location: ../view/forgot_password.php");
    exit();
}
?>
