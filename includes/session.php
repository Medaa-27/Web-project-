<?php
class SessionManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        $this->initSession();
    }
    
    private function initSession() {
        // Session is now primarily initialized in config.php
        // This ensures consistent session name and security settings
        if (session_status() === PHP_SESSION_NONE) {
            // Fallback in case config.php didn't start it
            session_name('AksumRentalSession');
            @session_start();
        }
    }
    
    public function login($user_id, $user_role, $remember = false) {
        // Get user details for session
        $sql = "SELECT full_name, is_active FROM users WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $user = $this->db->getSingle($stmt, [$user_id]);
        
        if (!$user || (int)$user['is_active'] !== 1) {
            $_SESSION['error'] = "Account is not active or doesn't exist";
            return false;
        }
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Convert role to lowercase for consistency
        $role_lower = strtolower($user_role);
        
        // Set session variables
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_role'] = $role_lower;
        $_SESSION['original_role'] = $user_role; // Keep original for database queries
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Update user last login
        $this->updateLastLogin($user_id);
        
        // Create remember me token if requested
        if ($remember) {
            $this->createRememberToken($user_id);
        }
        
        // Log login activity
        $this->logActivity($user_id, 'login', 'users', $user_id);
        
        return true;
    }
    
    private function updateLastLogin($user_id) {
        // Update updated_at timestamp
        $sql = "UPDATE users SET updated_at = NOW() WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $this->db->execute($stmt, [$user_id]);
    }
    
    private function createRememberToken($user_id) {
        // Check if user_sessions table exists, create it if not
        $check_table = "SHOW TABLES LIKE 'user_sessions'";
        $check_stmt = $this->db->prepare($check_table);
        $this->db->execute($check_stmt);
        
        if ($check_stmt->rowCount() == 0) {
            $create_table = "CREATE TABLE IF NOT EXISTS user_sessions (
                session_id INT PRIMARY KEY AUTO_INCREMENT,
                user_id VARCHAR(50) NOT NULL,
                session_token VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $create_stmt = $this->db->prepare($create_table);
            $this->db->execute($create_stmt);
        }
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $sql = "INSERT INTO user_sessions (user_id, session_token, expires_at, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $this->db->execute($stmt, [
            $user_id, 
            $token, 
            $expires,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
    }
    
    public function validateSession() {
        // Check if session exists and is valid
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            // Check session timeout (30 minutes)
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
                $this->logout();
                return false;
            }
            
            // Check if user still exists and is active
            if (!$this->validateUser($_SESSION['user_id'])) {
                $this->logout();
                return false;
            }
            
            $_SESSION['last_activity'] = time();
            return true;
        }
        
        // Check for remember token
        if (isset($_COOKIE['remember_token'])) {
            return $this->validateRememberToken($_COOKIE['remember_token']);
        }
        
        return false;
    }
    
    private function validateUser($user_id) {
        $sql = "SELECT user_id FROM users WHERE user_id = ? AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $result = $this->db->getSingle($stmt, [$user_id]);
        return $result !== false;
    }
    
    private function validateRememberToken($token) {
        // Check if table exists first
        $check_table = "SHOW TABLES LIKE 'user_sessions'";
        $check_stmt = $this->db->prepare($check_table);
        $this->db->execute($check_stmt);
        
        if ($check_stmt->rowCount() == 0) {
            return false;
        }
        
        $sql = "SELECT user_id FROM user_sessions 
                WHERE session_token = ? AND expires_at > NOW()";
        $stmt = $this->db->prepare($sql);
        $result = $this->db->getSingle($stmt, [$token]);
        
        if ($result) {
            $user_id = $result['user_id'];
            
            // Get user role
            $sql = "SELECT role FROM users WHERE user_id = ? AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $user = $this->db->getSingle($stmt, [$user_id]);
            
            if ($user) {
                $this->login($user_id, $user['role'], true);
                return true;
            }
        }
        
        return false;
    }
    
    public function logout() {
        // Delete remember token if exists
        if (isset($_COOKIE['remember_token'])) {
            // Check if table exists
            $check_table = "SHOW TABLES LIKE 'user_sessions'";
            $check_stmt = $this->db->prepare($check_table);
            $this->db->execute($check_stmt);
            
            if ($check_stmt->rowCount() > 0) {
                $sql = "DELETE FROM user_sessions WHERE session_token = ?";
                $stmt = $this->db->prepare($sql);
                $this->db->execute($stmt, [$_COOKIE['remember_token']]);
            }
            
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Log logout activity
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id']);
        }
        
        // Clear session
        $_SESSION = array();
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    public function isLoggedIn() {
        return $this->validateSession();
    }
    
    public function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }
    
    public function getOriginalRole() {
        return $_SESSION['original_role'] ?? null;
    }
    
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function getUserName() {
        return $_SESSION['user_name'] ?? null;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $loginUrl = rtrim(SITE_URL, '/') . '/login.php';
            header("Location: $loginUrl?redirect=" . urlencode($_SERVER['REQUEST_URI']));
            exit();
        }

        // If password change is forced, don't allow access to other pages
        if (isset($_SESSION['force_change']) && $_SESSION['force_change'] === true) {
            $currentPage = basename($_SERVER['PHP_SELF']);
            if ($currentPage !== 'change-password.php' && $currentPage !== 'logout.php') {
                $changePasswordUrl = rtrim(SITE_URL, '/') . '/change-password.php';
                header("Location: $changePasswordUrl");
                exit();
            }
        }
    }
    
    public function requireRole($required_role) {
        $this->requireLogin();
        
        $user_role = $this->getUserRole();
        
        if ($user_role !== $required_role) {
            $_SESSION['error'] = "You don't have permission to access this page.";
            $this->redirectToDashboard();
        }
    }
    
    public function redirectToDashboard() {
        $role = $this->getUserRole();
        $dashboard = '';
        
        switch ($role) {
            case 'admin':
                $dashboard = 'admin/dashboard.php';
                break;
            case 'owner':
                $dashboard = 'owner/dashboard.php';
                break;
            case 'tenant':
                $dashboard = 'tenant/dashboard.php';
                break;
            case 'employee':
                $dashboard = 'employee/dashboard.php';
                break;
            default:
                $dashboard = 'login.php';
        }
        
        $dashboardUrl = rtrim(SITE_URL, '/') . '/' . $dashboard;
        header("Location: $dashboardUrl");
        exit();
    }
    
    public function logActivity($user_id, $action, $table = null, $record_id = null) {
        // Check if audit_log table exists, create it if not
        $check_table = "SHOW TABLES LIKE 'audit_log'";
        $check_stmt = $this->db->prepare($check_table);
        $this->db->execute($check_stmt);
        
        if ($check_stmt->rowCount() == 0) {
            $create_table = "CREATE TABLE IF NOT EXISTS audit_log (
                log_id INT PRIMARY KEY AUTO_INCREMENT,
                user_id VARCHAR(50),
                action VARCHAR(100) NOT NULL,
                table_name VARCHAR(50),
                record_id INT,
                old_value TEXT,
                new_value TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $create_stmt = $this->db->prepare($create_table);
            $this->db->execute($create_stmt);
        }
        
        $sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $this->db->execute($stmt, [
            $user_id,
            $action,
            $table,
            $record_id,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
}

// Initialize session manager
global $db;
$session = new SessionManager($db);
?>
