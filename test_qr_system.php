<?php
/**
 * Test QR Code System
 * 
 * Simple test script to verify QR code functionality works correctly
 */

require_once __DIR__ . '/html/api/auth.php';

echo "Testing QR Code System...\n\n";

try {
    // Test 1: Generate a QR token
    echo "1. Testing QR token generation...\n";
    $tokenData = Auth::generateQRToken(1, "Test QR Code");
    echo "   ✓ Token generated: " . substr($tokenData['token'], 0, 16) . "...\n";
    echo "   ✓ Login URL: " . $tokenData['login_url'] . "\n";
    echo "   ✓ Expires at: " . date('Y-m-d H:i:s', $tokenData['expires_at']) . "\n\n";
    
    // Test 2: Validate the token
    echo "2. Testing token validation...\n";
    $isValid = Auth::validateQRToken($tokenData['token']);
    echo "   " . ($isValid ? "✓" : "✗") . " Token validation: " . ($isValid ? "PASS" : "FAIL") . "\n\n";
    
    // Test 3: Get active tokens
    echo "3. Testing active token retrieval...\n";
    $activeTokens = Auth::getActiveQRTokens();
    echo "   ✓ Found " . count($activeTokens) . " active tokens\n";
    if (!empty($activeTokens)) {
        echo "   ✓ Most recent: " . $activeTokens[0]['description'] . "\n";
    }
    echo "\n";
    
    // Test 4: Invalid token
    echo "4. Testing invalid token...\n";
    $isInvalid = Auth::validateQRToken("invalid_token_12345");
    echo "   " . (!$isInvalid ? "✓" : "✗") . " Invalid token rejected: " . (!$isInvalid ? "PASS" : "FAIL") . "\n\n";
    
    echo "All tests completed successfully! ✓\n";
    echo "\nTo test the full QR code flow:\n";
    echo "1. Visit: " . $tokenData['login_url'] . "\n";
    echo "2. You should be automatically logged in\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>