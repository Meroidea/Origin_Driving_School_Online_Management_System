<?php
/**
 * Configuration File for Origin Driving School Management System
 * 
 * This file contains all database and application configuration settings
 * Created for DWIN309 Final Assessment at Kent Institute Australia
 * 
 * file path: config/config.php
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */

// Prevent direct access
defined('APP_ACCESS') or die('Direct access not permitted');

// ========================================
// DATABASE CONFIGURATION
// ========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'origin_driving_school');
define('DB_USER', 'root');
define('DB_PASS', ''); // Leave empty for XAMPP default
define('DB_CHARSET', 'utf8mb4');

// ========================================
// APPLICATION CONFIGURATION
// ========================================
define('APP_NAME', 'Origin Driving School Management System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/origin_driving_school');

// ========================================
// PATH CONFIGURATION
// ========================================
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

// ========================================
// SECURITY CONFIGURATION
// ========================================
define('SESSION_NAME', 'ORIGIN_DS_SESSION');
define('SESSION_LIFETIME', 7200); // 2 hours in seconds
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes in seconds

// ========================================
// FILE UPLOAD CONFIGURATION
// ========================================
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// ========================================
// PAGINATION CONFIGURATION
// ========================================
define('RECORDS_PER_PAGE', 10);

// ========================================
// DATE & TIME CONFIGURATION
// ========================================
define('TIMEZONE', 'Australia/Melbourne');
define('DATE_FORMAT', 'd/m/Y');
define('TIME_FORMAT', 'H:i');
define('DATETIME_FORMAT', 'd/m/Y H:i');

// ========================================
// EMAIL CONFIGURATION (For future implementation)
// ========================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@origindrivingschool.com.au');
define('SMTP_PASS', ''); // Set your SMTP password
define('SMTP_FROM_EMAIL', 'noreply@origindrivingschool.com.au');
define('SMTP_FROM_NAME', 'Origin Driving School');

// ========================================
// SMS CONFIGURATION (For future implementation)
// ========================================
define('SMS_API_KEY', ''); // Set your SMS API key
define('SMS_API_URL', '');

// ========================================
// COLOR PALETTE (As per requirements)
// ========================================
define('COLOR_PRIMARY', '#4e7e95');
define('COLOR_SECONDARY', '#e78759');
define('COLOR_LIGHT', '#e5edf0');
define('COLOR_BACKGROUND', '#e5edf0');

// ========================================
// BUSINESS SETTINGS
// ========================================
define('TAX_RATE', 10); // GST percentage
define('CURRENCY_SYMBOL', '$');
define('LESSON_CANCELLATION_HOURS', 24);

// ========================================
// ERROR REPORTING
// ========================================
// Set to E_ALL during development, 0 in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========================================
// TIMEZONE SETTING
// ========================================
date_default_timezone_set(TIMEZONE);

// ========================================
// SESSION CONFIGURATION
// ========================================
ini_set('session.name', SESSION_NAME);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// AUTOLOADER FUNCTION
// ========================================
/**
 * Autoload classes when needed
 * 
 * @param string $className The name of the class to load
 * @return void
 */
spl_autoload_register(function ($className) {
    $paths = [
        APP_PATH . '/models/',
        APP_PATH . '/controllers/',
        APP_PATH . '/core/',
    ];
    
    foreach ($paths as $path) {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ========================================
// HELPER FUNCTIONS
// ========================================

/**
 * Sanitize input data
 * 
 * @param mixed $data The data to sanitize
 * @return mixed Sanitized data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a specific URL
 * 
 * @param string $url The URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: " . APP_URL . $url);
    exit();
}

/**
 * Format currency
 * 
 * @param float $amount The amount to format
 * @return string Formatted currency string
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

/**
 * Format date
 * 
 * @param string $date The date to format
 * @param string $format The format to use (default: DATE_FORMAT)
 * @return string Formatted date string
 */
function formatDate($date, $format = DATE_FORMAT) {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 * 
 * @param string|array $roles Role(s) to check
 * @return bool True if user has role, false otherwise
 */
function hasRole($roles) {
    if (!isLoggedIn()) return false;
    
    if (is_array($roles)) {
        return in_array($_SESSION['user_role'], $roles);
    }
    
    return $_SESSION['user_role'] === $roles;
}

/**
 * Get current user ID
 * 
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 * 
 * @return string|null User role or null if not logged in
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Set flash message
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message The message content
 * @return void
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * 
 * @return array|null Flash message array or null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Calculate age from date of birth
 * 
 * @param string $dateOfBirth Date of birth
 * @return int Age in years
 */
function calculateAge($dateOfBirth) {
    $dob = new DateTime($dateOfBirth);
    $now = new DateTime();
    return $now->diff($dob)->y;
}

/**
 * Generate unique invoice number
 * 
 * @return string Invoice number
 */
function generateInvoiceNumber() {
    return 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Require login - redirect if not logged in
 * 
 * @return void
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please login to access this page');
        redirect('/login.php');
    }
}

/**
 * Require specific role - redirect if user doesn't have role
 * 
 * @param string|array $roles Required role(s)
 * @return void
 */
function requireRole($roles) {
    requireLogin();
    
    if (!hasRole($roles)) {
        setFlashMessage('error', 'You do not have permission to access this page');
        redirect('/dashboard.php');
    }
}

/**
 * Get asset URL
 * 
 * @param string $asset Asset path
 * @return string Full asset URL
 */
function asset($asset) {
    return APP_URL . '/public/' . ltrim($asset, '/');
}

/**
 * Include view file
 * 
 * @param string $view View file name (without .php extension)
 * @param array $data Data to pass to view
 * @return void
 */
function view($view, $data = []) {
    extract($data);
    $viewFile = BASE_PATH . '/views/' . $view . '.php';
    
    if (file_exists($viewFile)) {
        require_once $viewFile;
    } else {
        die("View not found: $view");
    }
}

/**
 * Debug helper - print data (only in development)
 * 
 * @param mixed $data Data to print
 * @param bool $die Whether to die after printing
 * @return void
 */
function dd($data, $die = true) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    if ($die) die();
}

// ========================================
// ERROR HANDLER
// ========================================
/**
 * Custom error handler
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $errorTypes = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_NOTICE => 'Notice',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice'
    ];
    
    $type = $errorTypes[$errno] ?? 'Unknown Error';
    error_log("[$type] $errstr in $errfile on line $errline");
    
    // In production, don't display errors to users
    // In development, show detailed error info
    if (error_reporting() !== 0) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb;'>";
        echo "<strong>[$type]</strong> $errstr<br>";
        echo "File: $errfile (Line: $errline)";
        echo "</div>";
    }
}

set_error_handler('customErrorHandler');

?>