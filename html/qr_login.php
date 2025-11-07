<?php
/**
 * QR Code Login Handler
 * 
 * Processes QR code tokens and logs users in automatically
 */

require_once __DIR__ . '/api/auth.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$error = '';
$success = false;
$token = $_GET['token'] ?? '';

// Check if already authenticated
if (Auth::isAuthenticated()) {
    header('Location: activity.php');
    exit;
}

// Validate token if provided
if (!empty($token)) {
    if (Auth::validateQRToken($token)) {
        // Token is valid - authenticate user
        Auth::setAuthCookie();
        $success = true;
        
        // Clean up expired tokens while we're here
        Auth::cleanupExpiredTokens();
        
        // Redirect to activity page after a brief success message
        header('refresh:2;url=activity.php');
    } else {
        $error = 'Invalid or expired QR code token';
    }
} else {
    $error = 'No token provided';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #666;
            margin: 0;
        }
        .success-icon {
            color: #28a745;
            font-size: 3rem;
        }
        .error-icon {
            color: #dc3545;
            font-size: 3rem;
        }
        .spinner-border {
            width: 2rem;
            height: 2rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2><?php echo APP_NAME; ?></h2>
                <p>QR Code Login</p>
            </div>
            
            <?php if ($success): ?>
                <div class="text-center">
                    <i class="success-icon fas fa-check-circle mb-3"></i>
                    <h4 class="text-success mb-3">Login Successful!</h4>
                    <p class="text-muted mb-3">You have been logged in successfully.</p>
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span>Redirecting to activities...</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center">
                    <i class="error-icon fas fa-exclamation-circle mb-3"></i>
                    <h4 class="text-danger mb-3">Login Failed</h4>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <div class="mt-3">
                        <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Login
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>