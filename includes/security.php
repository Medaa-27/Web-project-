<?php
// Security functions for Aksum Rental Management System

// CSRF Token generation and validation
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Alias for backward compatibility
function validateCSRFToken($token) {
    return verifyCSRFToken($token);
}

// Input validation
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    // Basic phone validation for Ethiopian numbers
    return preg_match('/^(\+251|0)?[9][0-9]{8}$/', $phone);
}

function validatePassword($password) {
    // Password must be at least 8 characters with at least one uppercase, one lowercase, and one number
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
}

// XSS prevention
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// SQL injection prevention (using prepared statements is preferred)
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

// Rate limiting
function checkRateLimit($identifier, $max_attempts = 5, $time_window = 300) {
    $cache_key = "rate_limit_" . md5($identifier);
    
    if (!isset($_SESSION[$cache_key])) {
        $_SESSION[$cache_key] = ['attempts' => 0, 'first_attempt' => time()];
    }
    
    $rate_data = $_SESSION[$cache_key];
    
    // Reset if time window has passed
    if (time() - $rate_data['first_attempt'] > $time_window) {
        $_SESSION[$cache_key] = ['attempts' => 1, 'first_attempt' => time()];
        return true;
    }
    
    // Check if max attempts exceeded
    if ($rate_data['attempts'] >= $max_attempts) {
        return false;
    }
    
    // Increment attempts
    $_SESSION[$cache_key]['attempts']++;
    return true;
}

// Password hashing
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Secure session management
function secureSession() {
    // Prevent session fixation
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
    
    // Check user agent consistency
    if (isset($_SESSION['user_agent'])) {
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            session_destroy();
            return false;
        }
    } else {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }
    
    // Check IP address consistency (optional, may cause issues with dynamic IPs)
    if (isset($_SESSION['ip_address'])) {
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            session_destroy();
            return false;
        }
    } else {
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
    
    return true;
}

// File upload security
function validateFileUpload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 5242880) {
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error';
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $errors[] = 'File size exceeds maximum limit';
    }
    
    // Check file type
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $file['tmp_name']);
    finfo_close($file_info);
    
    $allowed_mimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_types) || 
        !in_array($mime_type, $allowed_mimes)) {
        $errors[] = 'Invalid file type';
    }
    
    // Check if file is actually an image
    if (getimagesize($file['tmp_name']) === false) {
        $errors[] = 'File is not a valid image';
    }
    
    return $errors;
}

// Input sanitization for different data types
function sanitizeString($input, $max_length = null) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    if ($max_length && strlen($input) > $max_length) {
        $input = substr($input, 0, $max_length);
    }
    
    return $input;
}

function sanitizeNumber($input, $min = null, $max = null) {
    $input = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    
    if ($input !== false && is_numeric($input)) {
        if ($min !== null && $input < $min) {
            return $min;
        }
        if ($max !== null && $input > $max) {
            return $max;
        }
        return $input;
    }
    
    return 0;
}

function sanitizeEmail($input) {
    $input = trim($input);
    $input = filter_var($input, FILTER_SANITIZE_EMAIL);
    return filter_var($input, FILTER_VALIDATE_EMAIL) ? $input : '';
}

// Security headers
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data: https:; font-src \'self\' https:; connect-src \'self\'');
}

// Log security events
function logSecurityEvent($event_type, $description, $user_id = null) {
    global $db;
    
    $sql = "INSERT INTO security_logs (event_type, description, user_id, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    
    $db->execute($stmt, [
        $event_type,
        $description,
        $user_id,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}

// Check for suspicious activity
function checkSuspiciousActivity($user_id) {
    // Check for multiple failed login attempts
    $failed_attempts_key = "failed_login_" . $user_id;
    
    if (isset($_SESSION[$failed_attempts_key]) && $_SESSION[$failed_attempts_key] > 5) {
        logSecurityEvent('SUSPICIOUS_ACTIVITY', 'Multiple failed login attempts detected', $user_id);
        return true;
    }
    
    return false;
}
?>