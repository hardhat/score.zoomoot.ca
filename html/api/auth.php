<?php
/**
 * Authentication Helper Functions
 * 
 * Provides secure authentication functionality including
 * password hashing, cookie management, and session validation
 */

require_once __DIR__ . '/../../env.php';
require_once __DIR__ . '/db.php';

class Auth {
    
    /**
     * Hash a password with salt
     */
    public static function hashPassword($password) {
        return hash('sha256', $password . PASSWORD_SALT);
    }
    
    /**
     * Verify if provided password matches the admin password
     */
    public static function verifyPassword($password) {
        $hashedInput = self::hashPassword($password);
        $hashedAdmin = self::hashPassword(ADMIN_PASSWORD);
        return hash_equals($hashedAdmin, $hashedInput);
    }
    
    /**
     * Set authentication cookie
     */
    public static function setAuthCookie() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            
            // Set session cookie parameters before starting session
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
            session_start();
        }
        
        $token = self::hashPassword(ADMIN_PASSWORD . time());
        
        // Store in session only - PHP handles the cookie
        $_SESSION[SESSION_NAME . '_token'] = $token;
        $_SESSION['auth_time'] = time();
        $_SESSION['authenticated'] = true;
        
        return $token;
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
        
        // Check if authenticated flag is set
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            return false;
        }
        
        // Check if session has expired
        if (isset($_SESSION['auth_time'])) {
            $elapsed = time() - $_SESSION['auth_time'];
            if ($elapsed > SESSION_LIFETIME) {
                self::clearAuth();
                return false;
            }
        } else {
            return false;
        }
        
        return true;
    }
    
    /**
     * Require authentication or redirect to login
     */
    public static function requireAuth($redirectUrl = '/html/index.php') {
        if (!self::isAuthenticated()) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    /**
     * Clear authentication (logout)
     */
    public static function clearAuth() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
        
        // Clear session variables
        $_SESSION = [];
        
        // Delete the session cookie
        if (isset($_COOKIE[SESSION_NAME])) {
            $params = session_get_cookie_params();
            setcookie(
                SESSION_NAME,
                '',
                [
                    'expires' => time() - 3600,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite']
                ]
            );
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Get remaining session time in seconds
     */
    public static function getRemainingTime() {
        if (!self::isAuthenticated()) {
            return 0;
        }
        
        if (isset($_SESSION['auth_time'])) {
            $elapsed = time() - $_SESSION['auth_time'];
            return max(0, SESSION_LIFETIME - $elapsed);
        }
        
        return 0;
    }
    
    /**
     * Refresh session timestamp
     */
    public static function refreshSession() {
        if (self::isAuthenticated()) {
            $_SESSION['auth_time'] = time();
            return true;
        }
        return false;
    }
    
    /**
     * Check authentication for API endpoints
     * Returns JSON error if not authenticated
     */
    public static function requireAuthAPI() {
        if (!self::isAuthenticated()) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Authentication required'
            ]);
            exit;
        }
    }
    
    /**
     * Generate a secure QR code login token
     */
    public static function generateQRToken($expiresInHours = 24, $description = 'Activity Leader QR Code') {
        // Create database table if it doesn't exist
        self::initQRTokenTable();
        
        // Generate a secure random token
        $token = bin2hex(random_bytes(32));
        $expiresAt = time() + ($expiresInHours * 3600);
        
        // Store token in database
        $db = Database::getInstance();
        $result = $db->query(
            "INSERT INTO qr_tokens (token, expires_at, description, created_at, used_count) VALUES (?, ?, ?, ?, 0)",
            [1 => $token, 2 => $expiresAt, 3 => $description, 4 => time()]
        );
        
        if (!$result) {
            throw new Exception("Failed to create QR token");
        }
        
        return [
            'token' => $token,
            'expires_at' => $expiresAt,
            'expires_in_hours' => $expiresInHours,
            'login_url' => self::getBaseUrl() . 'qr_login.php?token=' . $token
        ];
    }
    
    /**
     * Validate and use a QR code token
     */
    public static function validateQRToken($token, $maxUses = 50) {
        if (empty($token)) {
            return false;
        }
        
        // Ensure table exists
        self::initQRTokenTable();
        
        $db = Database::getInstance();
        
        // Check if token exists and is valid
        $result = $db->query(
            "SELECT * FROM qr_tokens WHERE token = ? AND expires_at > ? LIMIT 1",
            [1 => $token, 2 => time()]
        );
        
        $tokenData = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$tokenData) {
            return false;
        }
        
        // Check if token has been used too many times
        if ($tokenData['used_count'] >= $maxUses) {
            return false;
        }
        
        // Increment usage count
        $db->query(
            "UPDATE qr_tokens SET used_count = used_count + 1, last_used_at = ? WHERE token = ?",
            [1 => time(), 2 => $token]
        );
        
        return true;
    }
    
    /**
     * Clean up expired QR tokens
     */
    public static function cleanupExpiredTokens() {
        // Ensure table exists
        self::initQRTokenTable();
        
        $db = Database::getInstance();
        $result = $db->query(
            "DELETE FROM qr_tokens WHERE expires_at < ?",
            [1 => time()]
        );
        
        return $db->getConnection()->changes();
    }
    
    /**
     * Get all active QR tokens (for admin display)
     */
    public static function getActiveQRTokens() {
        // Ensure table exists
        self::initQRTokenTable();
        
        $db = Database::getInstance();
        $result = $db->query(
            "SELECT token, description, created_at, expires_at, used_count FROM qr_tokens WHERE expires_at > ? ORDER BY created_at DESC",
            [1 => time()]
        );
        
        $tokens = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $tokens[] = $row;
        }
        
        return $tokens;
    }
    
    /**
     * Initialize QR token database table
     */
    private static function initQRTokenTable() {
        $db = Database::getInstance();
        $db->query("
            CREATE TABLE IF NOT EXISTS qr_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                token TEXT UNIQUE NOT NULL,
                description TEXT,
                created_at INTEGER NOT NULL,
                expires_at INTEGER NOT NULL,
                last_used_at INTEGER,
                used_count INTEGER DEFAULT 0
            )
        ");
        
        // Create index for faster lookups
        $db->query("CREATE INDEX IF NOT EXISTS idx_qr_tokens_token ON qr_tokens(token)");
        $db->query("CREATE INDEX IF NOT EXISTS idx_qr_tokens_expires ON qr_tokens(expires_at)");
    }
    
    /**
     * Get the base URL of the application
     */
    private static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['PHP_SELF'] ?? '');
        $path = str_replace('/api', '/', $path); // Remove api path if present
        return $protocol . '://' . $host . $path;
    }
}

/**
 * Helper function to check if user is authenticated
 */
function isAuthenticated() {
    return Auth::isAuthenticated();
}

/**
 * Helper function to require authentication
 */
function requireAuth($redirectUrl = '/html/index.php') {
    Auth::requireAuth($redirectUrl);
}

/**
 * Helper function to require authentication for API
 */
function requireAuthAPI() {
    Auth::requireAuthAPI();
}

?>