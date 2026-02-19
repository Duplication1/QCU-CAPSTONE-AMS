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
    
    // Create user model instances first to ensure DB connection
    $userModel = new User();
    
    // Auto-migrate: Add security columns if they don't exist
    try {
        $dbConfig = Config::database();
        $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
        $conn->set_charset('utf8mb4');
        
        // Check if columns exist
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'failed_login_attempts'");
        if ($result->num_rows === 0) {
            // Add the security columns
            $conn->query("ALTER TABLE `users` ADD COLUMN `failed_login_attempts` INT DEFAULT 0 AFTER `last_login`");
            $conn->query("ALTER TABLE `users` ADD COLUMN `account_locked_until` DATETIME DEFAULT NULL AFTER `failed_login_attempts`");
            $conn->query("ALTER TABLE `users` ADD INDEX `idx_account_locked` (`account_locked_until`)");
        }
        $conn->close();
    } catch (Exception $e) {
        error_log('Failed to add security columns: ' . $e->getMessage());
    }
    
    // Validate inputs
    if (empty($id_number) || empty($password)) {
        $_SESSION['error_message'] = "Please enter both ID number and password.";
        header("Location: ../view/login.php");
        exit();
    }
    
    // Create user model instances
    $userModel = new User();
    
    // Check if account is locked
    $lockedUntil = $userModel->isAccountLocked($id_number);
    if ($lockedUntil) {
        $remainingTime = ceil((strtotime($lockedUntil) - time()) / 60);
        $_SESSION['error_message'] = "Account is locked due to multiple failed login attempts. Please try again in {$remainingTime} minute(s).";
        header("Location: ../view/login.php");
        exit();
    }
    
    // Authenticate user
    $user = $userModel->authenticate($id_number, $password);
    
    if ($user) {
        // Reset failed login attempts on successful login
        $userModel->resetFailedAttempts($id_number);
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
        
        // Record login history and activity log
        try {
            $dbConfig = Config::database();
            $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
            $conn->set_charset('utf8mb4');
            
            // Get user's IP address
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            
            // Get user agent
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Determine device type
            $device_type = 'desktop';
            if ($user_agent) {
                if (preg_match('/mobile|android|iphone|ipad|phone/i', $user_agent)) {
                    $device_type = 'mobile';
                }
            }
            
            // Check if login_history table exists, create if not
            $tableCheck = $conn->query("SHOW TABLES LIKE 'login_history'");
            if ($tableCheck->num_rows === 0) {
                $createTable = "
                CREATE TABLE `login_history` (
                    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `user_id` int(10) UNSIGNED NOT NULL,
                    `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
                    `ip_address` varchar(45) DEFAULT NULL,
                    `user_agent` text DEFAULT NULL,
                    `device_type` varchar(20) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `user_id` (`user_id`),
                    KEY `login_time` (`login_time`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ";
                $conn->query($createTable);
            }
            
            // Insert login record
            $stmt = $conn->prepare("INSERT INTO login_history (user_id, ip_address, user_agent, device_type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('isss', $user['id'], $ip_address, $user_agent, $device_type);
            $stmt->execute();
            $stmt->close();
            
            // Log activity for Laboratory Staff and Technician
            if ($user['role'] === 'Laboratory Staff' || $user['role'] === 'Technician') {
                $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, entity_type, description, ip_address, user_agent) VALUES (?, 'login', 'user', ?, ?, ?)");
                $description = 'User logged in to ' . $user['role'] . ' panel';
                $logStmt->bind_param('isss', $user['id'], $description, $ip_address, $user_agent);
                $logStmt->execute();
                $logStmt->close();
            }
            
            $conn->close();
        } catch (Exception $e) {
            // non-fatal; proceed with login even if login history couldn't be recorded
            error_log('Failed to record login history for user ' . intval($user['id']) . ': ' . $e->getMessage());
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
                header("Location: ../view/login.php");
                exit();
        }
        
        // Redirect back to login page to show success modal
        header("Location: ../view/login.php");
        exit();
    } else {
        // Increment failed login attempts and get the new count
        $attemptCount = $userModel->incrementFailedAttempts($id_number);
        
        // Check if account is now locked
        $lockedUntil = $userModel->isAccountLocked($id_number);
        if ($lockedUntil) {
            $_SESSION['error_message'] = "Too many failed login attempts. Your account has been locked for 30 minutes.";
        } else if ($attemptCount > 0) {
            // User exists but wrong password
            $remainingAttempts = 3 - $attemptCount;
            if ($remainingAttempts > 0) {
                $_SESSION['error_message'] = "Invalid ID number or password. You have {$remainingAttempts} attempt(s) remaining before your account is locked.";
            } else {
                $_SESSION['error_message'] = "Invalid ID number or password.";
            }
        } else {
            // Invalid ID or user doesn't exist
            $_SESSION['error_message'] = "Invalid ID number or password.";
        }
        header("Location: ../view/login.php");
        exit();
    }
} else {
    header("Location: ../view/login.php");
    exit();
}
?>
