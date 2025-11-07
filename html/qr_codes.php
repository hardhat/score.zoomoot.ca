<?php
/**
 * QR Code Management Interface
 * 
 * Generate and manage QR codes for activity leaders
 */

require_once __DIR__ . '/api/auth.php';

// Require authentication
Auth::requireAuth('login.php');

// Handle QR code generation
$qrCode = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate') {
        try {
            $description = trim($_POST['description'] ?? 'Activity Leader QR Code');
            $expiresInHours = (int)($_POST['expires_in_hours'] ?? 24);
            
            if (empty($description)) {
                throw new Exception('Description is required');
            }
            
            if ($expiresInHours < 1 || $expiresInHours > 168) {
                throw new Exception('Expiration time must be between 1 and 168 hours');
            }
            
            $qrCode = Auth::generateQRToken($expiresInHours, $description);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif ($action === 'cleanup') {
        try {
            $deleted = Auth::cleanupExpiredTokens();
            $success = "Cleaned up $deleted expired tokens";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get active QR codes
$activeTokens = Auth::getActiveQRTokens();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Manager - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .qr-code-display {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            margin: 2rem 0;
        }
        .qr-code-info {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        .token-list {
            max-height: 400px;
            overflow-y: auto;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .print-area, .print-area * {
                visibility: visible;
            }
            .print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light rounded mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="#"><?php echo APP_NAME; ?></a>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="activity.php">Activities</a>
                    <a class="nav-link" href="teams.php">Teams</a>
                    <a class="nav-link" href="logout.php">Logout</a>
                </div>
            </div>
        </nav>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Generate QR Code</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="action" value="generate">
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <input type="text" class="form-control" id="description" name="description" 
                                       value="<?php echo htmlspecialchars($_POST['description'] ?? 'Activity Leader QR Code'); ?>" 
                                       required>
                                <div class="form-text">Give this QR code a descriptive name for tracking purposes.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="expires_in_hours" class="form-label">Expires In (Hours)</label>
                                <select class="form-control" id="expires_in_hours" name="expires_in_hours">
                                    <option value="1">1 hour</option>
                                    <option value="6">6 hours</option>
                                    <option value="12">12 hours</option>
                                    <option value="24" selected>24 hours (1 day)</option>
                                    <option value="48">48 hours (2 days)</option>
                                    <option value="72">72 hours (3 days)</option>
                                    <option value="168">168 hours (1 week)</option>
                                </select>
                                <div class="form-text">How long should this QR code remain valid?</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-qrcode me-2"></i>Generate QR Code
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Active Tokens -->
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Active QR Codes</h5>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="cleanup">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-trash me-1"></i>Cleanup Expired
                            </button>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="token-list">
                            <?php if (empty($activeTokens)): ?>
                                <div class="p-3 text-muted text-center">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No active QR codes</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($activeTokens as $token): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($token['description']); ?></h6>
                                                    <small class="text-muted">
                                                        Created: <?php echo date('M j, Y g:i A', $token['created_at']); ?><br>
                                                        Expires: <?php echo date('M j, Y g:i A', $token['expires_at']); ?><br>
                                                        Uses: <?php echo $token['used_count']; ?>
                                                    </small>
                                                </div>
                                                <span class="badge bg-<?php echo ($token['expires_at'] > time() + 3600) ? 'success' : 'warning'; ?>">
                                                    <?php 
                                                    $remaining = $token['expires_at'] - time();
                                                    if ($remaining > 86400) {
                                                        echo floor($remaining / 86400) . 'd';
                                                    } elseif ($remaining > 3600) {
                                                        echo floor($remaining / 3600) . 'h';
                                                    } else {
                                                        echo floor($remaining / 60) . 'm';
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <?php if ($qrCode): ?>
                    <div class="print-area">
                        <div class="qr-code-display">
                            <h4 class="mb-3">Activity Leader Login</h4>
                            <div class="mb-3">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?php echo urlencode($qrCode['login_url']); ?>" 
                                     alt="QR Code" class="img-fluid">
                            </div>
                            <div class="qr-code-info">
                                <h6><?php echo htmlspecialchars($_POST['description'] ?? 'Activity Leader QR Code'); ?></h6>
                                <p class="small text-muted mb-2">
                                    <strong>Expires:</strong> <?php echo date('M j, Y g:i A', $qrCode['expires_at']); ?>
                                </p>
                                <p class="small text-muted">
                                    Scan with phone camera to login automatically
                                </p>
                            </div>
                        </div>
                        
                        <div class="text-center no-print">
                            <button onclick="window.print()" class="btn btn-success me-2">
                                <i class="fas fa-print me-2"></i>Print QR Code
                            </button>
                            <button onclick="copyToClipboard('<?php echo $qrCode['login_url']; ?>')" class="btn btn-outline-primary">
                                <i class="fas fa-copy me-2"></i>Copy URL
                            </button>
                        </div>
                        
                        <div class="mt-3 no-print">
                            <div class="card">
                                <div class="card-body">
                                    <h6>QR Code Details</h6>
                                    <p class="small mb-2">
                                        <strong>Token:</strong> <code class="small"><?php echo substr($qrCode['token'], 0, 16); ?>...</code>
                                    </p>
                                    <p class="small mb-2">
                                        <strong>Login URL:</strong> 
                                        <input type="text" class="form-control form-control-sm mt-1" 
                                               value="<?php echo htmlspecialchars($qrCode['login_url']); ?>" readonly>
                                    </p>
                                    <p class="small mb-0">
                                        <strong>Valid for:</strong> <?php echo $qrCode['expires_in_hours']; ?> hours
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center text-muted">
                            <i class="fas fa-qrcode fa-3x mb-3"></i>
                            <h5>Generate a QR Code</h5>
                            <p>Fill out the form on the left to generate a QR code for activity leaders.</p>
                            <p class="small">QR codes allow leaders to login automatically by scanning with their phone camera.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Create temporary success message
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check me-2"></i>Copied!';
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-success');
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-primary');
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy text: ', err);
                alert('Failed to copy URL to clipboard');
            });
        }
    </script>
</body>
</html>