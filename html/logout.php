<?php
/**
 * Logout Handler
 * 
 * Clears authentication cookie and session, then redirects to index
 */

require_once __DIR__ . '/api/auth.php';

// Clear authentication
Auth::clearAuth();

// Redirect to index page
header('Location: index.php');
exit;

?>