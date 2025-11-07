<?php
/**
 * Test login authentication
 */

require_once __DIR__ . '/html/api/auth.php';

echo "=== Login Authentication Test ===\n\n";

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

echo "Step 1: Clear any existing auth\n";
Auth::clearAuth();
echo "Is Authenticated: " . (Auth::isAuthenticated() ? 'Yes' : 'No') . "\n\n";

echo "Step 2: Verify password\n";
$password = ADMIN_PASSWORD;
$isValid = Auth::verifyPassword($password);
echo "Password '$password' is valid: " . ($isValid ? 'Yes' : 'No') . "\n\n";

echo "Step 3: Set auth cookie\n";
$token = Auth::setAuthCookie();
echo "Token set: " . substr($token, 0, 20) . "...\n";
echo "Session var set: " . (isset($_SESSION[SESSION_NAME]) ? 'Yes' : 'No') . "\n";
echo "Cookie would be set (can't verify in CLI)\n\n";

echo "Step 4: Check authentication\n";
$_COOKIE[SESSION_NAME] = $token; // Simulate cookie in CLI
echo "Is Authenticated: " . (Auth::isAuthenticated() ? 'Yes' : 'No') . "\n\n";

echo "Step 5: Check session data\n";
echo "Session Name: " . session_name() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Token: " . (isset($_SESSION[SESSION_NAME]) ? substr($_SESSION[SESSION_NAME], 0, 20) . '...' : 'Not set') . "\n";
echo "Auth Time: " . (isset($_SESSION['auth_time']) ? date('Y-m-d H:i:s', $_SESSION['auth_time']) : 'Not set') . "\n";

echo "\n=== Test Complete ===\n";
?>
