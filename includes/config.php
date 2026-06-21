<?php

// Aksum House Rental Management System - Configuration

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    // Session security settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    // Set custom session name
    session_name('AksumRentalSession');
    
    // Create session directory if it doesn't exist
    if (!file_exists('C:\x\tmp')) {
        @mkdir('C:\x\tmp', 0777, true);
    }
    ini_set('session.save_path', 'C:\x\tmp');
    
    @session_start();
}



// Error reporting

error_reporting(E_ALL);
ini_set('display_startup_errors', 0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');



// Site configuration

define('SITE_NAME', 'Aksum House Rental Management System');

// base URL - set to local network host so email links work from mobile devices
if (!defined('SITE_URL')) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // We'll keep the host as detected by the browser (localhost or IP)
    // Replaced the auto-IP detection as it can break links when browsing from the same machine
    // NOTE: If testing on mobile, use your computer's IP address (e.g. 192.168.1.5) 
    // to access the site instead of 'localhost'.
    
    define('SITE_URL', $protocol . '://' . $host . '/aksum-rental/');
}

define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') ?: 'YOUR_GOOGLE_MAPS_API_KEY'); // Replace with your Google Maps API key or set environment variable

define('SITE_EMAIL', 'joyeshu7@gmail.com');

define('CURRENCY', 'ETB');

define('ADVANCE_PERCENTAGE', 20); // 20% advance payment as per business rule

define('AGREEMENT_PERIOD', 6); // 6-month agreement

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'aksum_rental_db');

// File upload paths
define('UPLOAD_PATH', dirname(__DIR__) . '/assets/uploads/');
define('PROPERTY_IMG_PATH', 'assets/uploads/properties/');
define('PROFILE_IMG_PATH', 'assets/uploads/profiles/');
define('DOCUMENT_PATH', 'assets/uploads/documents/');

// Email configuration (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'joyeshu7@gmail.com');
define('SMTP_PASS', 'dgqfakuhvyqsjznr');

// Timezone for Ethiopia
date_default_timezone_set('Africa/Addis_Ababa');

// Include classes and functions
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/wallet-functions.php';

$db = new Database();
$session = new SessionManager($db);

// Social News Migration
try {
    // 1. news_likes table
    $db->execute($db->prepare("CREATE TABLE IF NOT EXISTS `news_likes` (
        `like_id` int(11) NOT NULL AUTO_INCREMENT,
        `news_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`like_id`),
        UNIQUE KEY `news_user_like` (`news_id`, `user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"));

    // 2. news_comments parent_id column
    $checkSql = "SHOW COLUMNS FROM `news_comments` LIKE 'parent_id'";
    $stmt = $db->prepare($checkSql);
    $column = $db->getSingle($stmt);
    if (!$column) {
        $db->execute($db->prepare("ALTER TABLE `news_comments` ADD COLUMN `parent_id` int(11) DEFAULT NULL AFTER `news_id`"));
    }
} catch (Exception $e) {
    error_log("Social Migration Error: " . $e->getMessage());
}

// Check for expired payments (runs once per hour roughly)
if (!isset($_SESSION['last_expiry_check']) || (time() - $_SESSION['last_expiry_check'] > 3600)) {
    $_SESSION['last_expiry_check'] = time();
    if (function_exists('checkExpiredPayments')) {
        checkExpiredPayments();
    }
}

?>
