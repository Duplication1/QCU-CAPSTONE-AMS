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
    $login_type = isset($_POST['login_type']) ? $_POST['login_type'] : '';
    
    // Validate inputs
    if (empty($id_number) || empty($password)) {
        $_SESSION['error_message'] = "Please enter both ID number and password.";
        $redirect = $login_type === 'student' ? "../view/student_login.php" : 
                   ($login_type === 'employee' ? "../view/employee_login.php" : "../view/login.php");
        header("Location: $redirect");
        exit();
    }
    
    // Create user model instances
    $userModel = new User();
    
    // Authenticate user
    $user = $userModel->authenticate($id_number, $password);
    
    if ($user) {
        // Validate login type matches user role
        if ($login_type === 'student' && $user['role'] !== 'Student') {
            $_SESSION['error_message'] = "Invalid credentials for student login.";
            header("Location: ../view/student_login.php");
            exit();
        }
        
        $employeeRoles = ['Administrator', 'Technician', 'Laboratory Staff', 'Faculty'];
        if ($login_type === 'employee' && !in_array($user['role'], $employeeRoles)) {
            $_SESSION['error_message'] = "Invalid credentials for employee login.";
            header("Location: ../view/employee_login.php");
            exit();
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['id_number'] = $user['id_number'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['is_logged_in'] = true;
        $_SESSION['login_success'] = true;
        
        // update last_login time so admins can see last active
        try {
            $userModel->updateLastLogin($user['id']);
        } catch (Exception $e) {
            // non-fatal; proceed with login even if last_login couldn't be updated
            error_log('Failed to update last_login for user ' . intval($user['id']) . ': ' . $e->getMessage());
        }
        
        // Store redirect URL and redirect back to login page to show success modal
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
                $_SESSION['error_message'] = "Invalid user role.";
                $redirect = $login_type === 'student' ? "../view/student_login.php" : 
                           ($login_type === 'employee' ? "../view/employee_login.php" : "../view/student_login.php");
                header("Location: $redirect");
                exit();
        }
        
        // Redirect back to appropriate login page to show success modal
        $redirect = $login_type === 'student' ? "../view/student_login.php" : "../view/employee_login.php";
        header("Location: $redirect");
        exit();
    } else {
        $_SESSION['error_message'] = "Invalid ID number or password.";
        $redirect = $login_type === 'student' ? "../view/student_login.php" : 
                   ($login_type === 'employee' ? "../view/employee_login.php" : "../view/login.php");
        header("Location: $redirect");
        exit();
    }
} else {
    header("Location: ../view/login.php");
    exit();
}
?>
