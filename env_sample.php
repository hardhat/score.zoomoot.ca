<?php
/**
 * Environment Configuration Template
 * 
 * Copy this file to env.php and update the values below.
 * DO NOT commit env.php to version control.
 */

// Database configuration
define('DB_NAME', 'score/zoomoot_scores.db');
define('DB_PATH', __DIR__ . '/' . DB_NAME);

// Authentication configuration
define('ADMIN_PASSWORD', 'changeme123');
define('PASSWORD_SALT', 'your-random-salt-string-here-change-this');

// Session configuration
define('SESSION_NAME', 'zoomoot_admin');
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Application settings
define('APP_NAME', 'Zoomoot Score Tracker');
define('DEBUG_MODE', false);

?>