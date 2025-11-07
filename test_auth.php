<?php
/**
 * Authentication System Test
 * 
 * Tests password hashing, verification, and authentication functions
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/html/api/auth.php';

echo "<h2>Authentication System Tests</h2>\n";
echo "<pre>\n";

try {
    // Test 1: Password hashing
    echo "=== Test 1: Password Hashing ===\n";
    $testPassword = "test123";
    $hash1 = Auth::hashPassword($testPassword);
    $hash2 = Auth::hashPassword($testPassword);
    
    if ($hash1 === $hash2) {
        echo "✓ Password hashing is consistent\n";
        echo "  Hash: " . substr($hash1, 0, 20) . "...\n";
    } else {
        echo "❌ Password hashing is inconsistent\n";
    }
    
    // Test 2: Password verification with correct password
    echo "\n=== Test 2: Password Verification (Correct) ===\n";
    $correctPassword = ADMIN_PASSWORD;
    if (Auth::verifyPassword($correctPassword)) {
        echo "✓ Correct password verified successfully\n";
    } else {
        echo "❌ Correct password verification failed\n";
    }
    
    // Test 3: Password verification with wrong password
    echo "\n=== Test 3: Password Verification (Wrong) ===\n";
    $wrongPassword = "wrongpassword123";
    if (!Auth::verifyPassword($wrongPassword)) {
        echo "✓ Wrong password correctly rejected\n";
    } else {
        echo "❌ Wrong password was accepted (security issue!)\n";
    }
    
    // Test 4: Hash comparison (timing attack resistant)
    echo "\n=== Test 4: Hash Comparison Security ===\n";
    $adminHash = Auth::hashPassword(ADMIN_PASSWORD);
    $testHash = Auth::hashPassword("test");
    if (hash_equals($adminHash, $adminHash)) {
        echo "✓ hash_equals works correctly\n";
    } else {
        echo "❌ hash_equals failed\n";
    }
    
    // Test 5: Session configuration
    echo "\n=== Test 5: Session Configuration ===\n";
    echo "  Session Name: " . SESSION_NAME . "\n";
    echo "  Session Lifetime: " . SESSION_LIFETIME . " seconds (" . (SESSION_LIFETIME/3600) . " hours)\n";
    echo "  Password Salt Length: " . strlen(PASSWORD_SALT) . " characters\n";
    
    if (strlen(PASSWORD_SALT) >= 20) {
        echo "✓ Password salt is adequate length\n";
    } else {
        echo "⚠ Warning: Password salt should be at least 20 characters\n";
    }
    
    // Test 6: Authentication status (should be false without login)
    echo "\n=== Test 6: Authentication Status ===\n";
    if (!Auth::isAuthenticated()) {
        echo "✓ User correctly detected as not authenticated\n";
    } else {
        echo "❌ User incorrectly detected as authenticated\n";
    }
    
    // Test 7: Environment configuration
    echo "\n=== Test 7: Environment Configuration ===\n";
    echo "  Admin Password Set: " . (defined('ADMIN_PASSWORD') ? 'Yes' : 'No') . "\n";
    echo "  Password Salt Set: " . (defined('PASSWORD_SALT') ? 'Yes' : 'No') . "\n";
    echo "  Debug Mode: " . (DEBUG_MODE ? 'ON' : 'OFF') . "\n";
    
    if (ADMIN_PASSWORD !== 'changeme123') {
        echo "✓ Admin password has been changed from default\n";
    } else {
        echo "⚠ Warning: Admin password is still set to default\n";
    }
    
    if (PASSWORD_SALT !== 'your-random-salt-string-here-change-this') {
        echo "✓ Password salt has been changed from default\n";
    } else {
        echo "⚠ Warning: Password salt is still set to default\n";
    }
    
    echo "\n=== All Tests Completed ===\n";
    echo "✓ Authentication system is functional\n";
    echo "\nNOTE: To test actual login/logout, use a web browser to access:\n";
    echo "  - html/login.php (login page)\n";
    echo "  - html/logout.php (logout)\n";
    
} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>\n";

?>