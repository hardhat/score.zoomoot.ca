<?php
/**
 * Authentication Helper Functions
 * 
 * Provides secure authentication functionality including
 * password hashing, cookie management, and session validation
 */

require_once __DIR__ . '/../../env.php';

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