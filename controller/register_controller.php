<?php
session_start();
require_once '../config/config.php';
require_once '../model/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_number = trim($_POST['id_number']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $security_question_1 = trim($_POST['security_question_1']);
    $security_answer_1 = trim($_POST['security_answer_1']);
    $security_question_2 = trim($_POST['security_question_2']);
    $security_answer_2 = trim($_POST['security_answer_2']);
    
    // Validate inputs
    if (empty($id_number) || empty($full_name) || empty($email) || empty($role) || 
        empty($password) || empty($security_question_1) || empty($security_answer_1) ||
        empty($security_question_2) || empty($security_answer_2)) {
        $_SESSION['error_message'] = "All fields are required.";
        header("Location: ../view/register.php");
        exit();
    }
    
    // Validate password match
    if ($password !== $confirm_password) {
        $_SESSION['error_message'] = "Passwords do not match.";
        header("Location: ../view/register.php");
        exit();
    }
    
    // Validate password length
    if (strlen($password) < 6) {
        $_SESSION['error_message'] = "Password must be at least 6 characters long.";
        header("Location: ../view/register.php");
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
        header("Location: ../view/register.php");
        exit();
    }
    
    // Validate role
    $validRoles = ['Student', 'Faculty', 'Technician', 'Laboratory Staff'];
    if (!in_array($role, $validRoles)) {
        $_SESSION['error_message'] = "Invalid role selected.";
        header("Location: ../view/register.php");
        exit();
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Check if ID number already exists in users table
        $stmt = $conn->prepare("SELECT id FROM users WHERE id_number = ?");
        $stmt->execute([$id_number]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['error_message'] = "This ID number is already registered.";
            header("Location: ../view/register.php");
            exit();
        }
        
        // Check if ID number already has a pending request
        $stmt = $conn->prepare("SELECT id FROM registration_requests WHERE id_number = ? AND status = 'Pending'");
        $stmt->execute([$id_number]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['error_message'] = "You already have a pending registration request.";
            header("Location: ../view/register.php");
            exit();
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['error_message'] = "This email is already registered.";
            header("Location: ../view/register.php");
            exit();
        }
        
        // Hash password and security answers
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $hashed_answer_1 = password_hash(strtolower($security_answer_1), PASSWORD_DEFAULT);
        $hashed_answer_2 = password_hash(strtolower($security_answer_2), PASSWORD_DEFAULT);
        
        // Insert registration request
        $stmt = $conn->prepare("
            INSERT INTO registration_requests 
            (id_number, full_name, email, password, role, security_question_1, security_answer_1, 
             security_question_2, security_answer_2, status, requested_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
        ");
        
        $stmt->execute([
            $id_number, 
            $full_name, 
            $email, 
            $hashed_password, 
            $role,
            $security_question_1,
            $hashed_answer_1,
            $security_question_2,
            $hashed_answer_2
        ]);
        
        $_SESSION['success_message'] = "Registration request submitted successfully! Please wait for administrator approval.";
        header("Location: ../view/login.php");
        exit();
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        $_SESSION['error_message'] = "An error occurred during registration. Please try again.";
        header("Location: ../view/register.php");
        exit();
    }
} else {
    header("Location: ../view/register.php");
    exit();
}
?>
