<?php
require_once __DIR__ . '/config.php';

// User Middleware - Ensures user is logged in
function userMiddleware() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please login to access this page');
        redirect('/login.php');
    }
    
    // Check if user account is active
    global $conn;
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user || $user['status'] !== 'active') {
        session_destroy();
        setFlashMessage('error', 'Your account has been deactivated. Please contact support.');
        redirect('/login.php');
    }
}

// Admin Middleware - Ensures user is logged in and is an admin
function adminMiddleware() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please login to access this page');
        redirect('/login.php');
    }
    
    if (!isAdmin()) {
        setFlashMessage('error', 'Access denied. Admin privileges required.');
        redirect('/user/dashboard.php');
    }
    
    // Check if admin account is active
    global $conn;
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ? AND role = 'admin'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    if (!$admin || $admin['status'] !== 'active') {
        session_destroy();
        setFlashMessage('error', 'Your admin account has been deactivated.');
        redirect('/login.php');
    }
}

// Guest Middleware - Redirects logged-in users away from auth pages
function guestMiddleware() {
    if (isLoggedIn()) {
        if (isAdmin()) {
            redirect('/admin/dashboard.php');
        } else {
            redirect('/user/dashboard.php');
        }
    }
}
?>
