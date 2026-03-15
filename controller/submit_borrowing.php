<?php
session_start();
require_once '../config/config.php';
require_once '../model/Database.php';
require_once '../model/AssetBorrowing.php';

function getBorrowingRedirectPath($role)
{
    if ($role === 'Laboratory Staff') {
        return '../view/LaboratoryStaff/submit_tickets.php';
    }

    return '../view/StudentFaculty/tickets.php';
}

// Check if user is logged in and has student, faculty, or laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || !in_array($_SESSION['role'], ['Student', 'Faculty', 'Laboratory Staff'])) {
    $_SESSION['error_message'] = "Unauthorized access.";
    header("Location: ../view/login.php");
    exit();
}

$redirectPath = getBorrowingRedirectPath($_SESSION['role'] ?? 'Student');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_id = $_POST['asset_id'] ?? null;
    $borrowed_date = $_POST['borrowed_date'] ?? null;
    $expected_return_date = $_POST['expected_return_date'] ?? null;
    $purpose = $_POST['purpose'] ?? null;
    $agreed_to_terms = $_POST['agreed_to_terms'] ?? false;
    
    // Validation
    if (!$asset_id || !$borrowed_date || !$expected_return_date || !$purpose) {
        $_SESSION['error_message'] = "All fields are required.";
        header("Location: {$redirectPath}");
        exit();
    }
    
    if (!$agreed_to_terms) {
        $_SESSION['error_message'] = "You must agree to the terms and conditions.";
        header("Location: {$redirectPath}");
        exit();
    }
    
    try {
        $borrowing = new AssetBorrowing();
        $borrowing->asset_id = $asset_id;
        $borrowing->borrower_id = $_SESSION['user_id'];
        $borrowing->borrower_name = $_SESSION['full_name'];
        $borrowing->borrowed_date = $borrowed_date;
        $borrowing->expected_return_date = $expected_return_date;
        $borrowing->purpose = $purpose;
        $borrowing->status = 'Pending'; // Pending approval from lab staff
        
        if ($borrowing->create()) {
            $_SESSION['borrow_success_message'] = "Borrowing request submitted successfully! Awaiting approval.";
            unset($_SESSION['success_message']);
            
            // Log the borrowing request
            try {
                require_once '../model/ActivityLog.php';
                ActivityLog::record(
                    $_SESSION['user_id'],
                    'create',
                    'borrowing',
                    null,
                    "Submitted borrowing request for asset ID: {$asset_id}"
                );
            } catch (Exception $logError) {
                error_log('Failed to log borrowing request: ' . $logError->getMessage());
            }
        } else {
            $_SESSION['error_message'] = "Failed to submit borrowing request.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    }
    
    header("Location: {$redirectPath}");
    exit();
} else {
    $_SESSION['error_message'] = "Invalid request method.";
    header("Location: {$redirectPath}");
    exit();
}
?>
