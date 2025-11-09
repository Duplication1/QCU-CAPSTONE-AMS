<?php
session_start();
require_once '../config/config.php';
require_once '../model/User.php';
require_once '../model/Database.php';

// Set session lifetime from config
ini_set('session.gc_maxlifetime', Config::sessionLifetime());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_number = trim($_POST['id_number']);
    $password = trim($_POST['password']);
    
    // Validate inputs
    if (empty($id_number) || empty($password)) {
        $_SESSION['error'] = "Please enter both ID number and password.";
        header("Location: ../view/login.php");
        exit();
    }
    
    // Create user model instances
    $userModel = new User();
    
    // Authenticate user
    $user = $userModel->authenticate($id_number, $password);
    
    if ($user) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['id_number'] = $user['id_number'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['is_logged_in'] = true;
        $_SESSION['login_success'] = true;
        
        // Store redirect URL based on role
        switch ($user['role']) {
            case 'Administrator':
                $_SESSION['redirect_url'] = "../view/Administrator/index.php";
                break;
            case 'Technician':
                $_SESSION['redirect_url'] = "../view/Technician/index.php";
                break;
            case 'Laboratory Staff':
                $_SESSION['redirect_url'] = "../view/LaboratoryStaff/index.php";
                break;
            case 'Student':
            case 'Faculty':
                $_SESSION['redirect_url'] = "../view/StudentFaculty/index.php";
                break;
            default:
                $_SESSION['error'] = "Invalid user role.";
                header("Location: ../view/login.php");
                exit();
        }
        
        // Redirect back to login page to show success modal
        header("Location: ../view/login.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid ID number or password.";
        header("Location: ../view/login.php");
        exit();
    }
} else {
    header("Location: ../view/login.php");
    exit();
}
?>
