<?php
/**
 * QR Code Generation API
 * 
 * Generates QR codes for activity leaders
 */

require_once __DIR__ . '/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Require authentication
Auth::requireAuthAPI();

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Get parameters
    $input = json_decode(file_get_contents('php://input'), true);
    $description = $input['description'] ?? 'Activity Leader QR Code';
    $expiresInHours = (int)($input['expires_in_hours'] ?? 24);
    
    // Validate expiration time (between 1 hour and 7 days)
    if ($expiresInHours < 1 || $expiresInHours > 168) {
        throw new Exception('Expiration time must be between 1 and 168 hours (7 days)');
    }
    
    // Generate the QR token
    $tokenData = Auth::generateQRToken($expiresInHours, $description);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => [
            'token' => $tokenData['token'],
            'login_url' => $tokenData['login_url'],
            'expires_at' => $tokenData['expires_at'],
            'expires_in_hours' => $tokenData['expires_in_hours'],
            'description' => $description,
            'qr_code_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($tokenData['login_url'])
        ]
    ]);
    
} catch (Exception $e) {
    error_log("QR Code generation error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>