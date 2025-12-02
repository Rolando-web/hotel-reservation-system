<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hotel_reservation_system');

// Application Configuration
define('APP_NAME', 'Aurora Suite');

// Dynamically determine the app URL based on current host and project folder
// Avoid hardcoding the folder name to prevent mismatches when moved/renamed
$__scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$__host = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Project root folder name (the folder that contains this config directory)
$__projectFolder = basename(dirname(__DIR__));
define('APP_URL', $__scheme . '://' . $__host . '/' . $__projectFolder);

define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    // Lightweight self-healing: ensure payment_method stores wallet names
    try {
        $colRes = $conn->query("SHOW COLUMNS FROM reservations LIKE 'payment_method'");
        if ($colRes && $col = $colRes->fetch_assoc()) {
            $type = strtolower($col['Type'] ?? '');
            if (strpos($type, "enum(") !== false && (strpos($type, "gcash") === false || strpos($type, "paymaya") === false)) {
                $conn->query("ALTER TABLE reservations MODIFY payment_method ENUM('cash','online','gcash','paymaya') NULL DEFAULT NULL");
            }
            // Backfill for historical rows where method was saved as empty due to enum mismatch
            $conn->query("UPDATE reservations SET payment_method = 'online' WHERE payment_status='paid' AND (payment_method IS NULL OR payment_method='') AND payment_reference IS NOT NULL");
            $conn->query("UPDATE reservations SET payment_method = 'cash' WHERE payment_status='paid' AND (payment_method IS NULL OR payment_method='') AND (payment_reference IS NULL OR payment_reference='')");
        }
    } catch (Exception $e) {
        // Ignore silently if ALTER not permitted; app will still run
    }
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Helper Functions
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

function redirect($url) {
    header("Location: " . APP_URL . $url);
    exit();
}

function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'type' => $_SESSION['flash_type'],
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please login to access this page');
        redirect('/login.php');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlashMessage('error', 'Access denied. Admin privileges required.');
        redirect('/user/dashboard.php');
    }
}


function getCurrentUser() {
    global $conn;
    if (!isLoggedIn()) {
        return null;
    }
    
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, name, email, phone, address, role FROM users WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

function calculateNights($check_in, $check_out) {
    $start = new DateTime($check_in);
    $end = new DateTime($check_out);
    return $start->diff($end)->days;
}
?>
