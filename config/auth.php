<?php
/**
 * Authentication Helper Functions
 */

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
}

// Check if user has specific role
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (is_array($role)) {
        return in_array($_SESSION['role'], $role);
    }
    
    return $_SESSION['role'] === $role;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to access this page.";
        header("Location: ../login.php");
        exit();
    }
}

// Require specific role
function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        header("Location: ../login.php");
        exit();
    }
}

// Get current user info
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'id_number' => $_SESSION['id_number'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role']
    ];
}

// Check if user is admin
function isAdmin() {
    return hasRole('Administrator');
}

// Check if user is technician
function isTechnician() {
    return hasRole('Technician');
}

// Check if user is lab staff
function isLabStaff() {
    return hasRole('Laboratory Staff');
}

// Check if user is student or faculty
function isStudentOrFaculty() {
    return hasRole(['Student', 'Faculty']);
}
?>
