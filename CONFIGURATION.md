# Configuration Guide - Zoomoot Score Tracker

This guide covers all configuration options and security settings for the Zoomoot Score Tracker application.

## Table of Contents

- [Environment Configuration](#environment-configuration)
- [Database Configuration](#database-configuration)
- [Session Configuration](#session-configuration)
- [Security Settings](#security-settings)
- [Performance Tuning](#performance-tuning)
- [Debug and Development](#debug-and-development)

## Environment Configuration

All configuration is managed through the `env.php` file. This file contains sensitive information and should **never be committed to version control**.

### Initial Setup

1. Copy the sample environment file:
   ```bash
   cp env_sample.php env.php
   ```

2. Edit `env.php` with your settings:
   ```bash
   nano env.php
   # or
   vim env.php
   # or use your preferred editor
   ```

### Configuration File Structure

```php
<?php
/**
 * Zoomoot Score Tracker - Environment Configuration
 */

// Application Settings
define('APP_NAME', 'Zoomoot Score Tracker');

// Database Configuration
define('DB_PATH', __DIR__ . '/score/zoomoot_scores.db');

// Authentication
define('ADMIN_PASSWORD', 'your-secure-password');
define('PASSWORD_SALT', 'your-random-salt');

// Session Configuration
define('SESSION_NAME', 'zoomoot_admin');
define('SESSION_LIFETIME', 3600);

// Debug Mode
define('DEBUG_MODE', false);
?>
```

## Database Configuration

### Database Path

**Setting**: `DB_PATH`

**Default**: `__DIR__ . '/score/zoomoot_scores.db'`

**Description**: Absolute path to the SQLite database file.

**Examples**:

```php
// Relative to env.php (default)
define('DB_PATH', __DIR__ . '/score/zoomoot_scores.db');

// Absolute path
define('DB_PATH', '/var/lib/zoomoot/database.db');

// Custom directory
define('DB_PATH', '/data/databases/zoomoot_scores.db');
```

**Considerations**:
- Directory must exist and be writable by the web server user
- Database file will be created automatically if it doesn't exist
- Consider placing outside the web root for additional security
- Ensure regular backups of this file

### Database Initialization

After setting `DB_PATH`, initialize the schema:

```bash
php html/api/init.php
```

**Output**:
```
Created activity table
Created team table
Created score table
Inserted sample data
Database schema initialized successfully!
```

### Database Permissions

Set appropriate permissions:

```bash
# Create directory if it doesn't exist
mkdir -p score

# Set ownership (adjust user/group for your web server)
chown www-data:www-data score

# Set permissions
chmod 755 score           # Directory
chmod 644 score/*.db      # Database file (after creation)
```

**Permission Levels**:
- **755** for directory: Owner can read/write/execute, others can read/execute
- **644** for database: Owner can read/write, others can read only
- Web server user needs **write** access to the directory for SQLite WAL files

## Session Configuration

Sessions handle authentication and maintain user login state.

### Session Name

**Setting**: `SESSION_NAME`

**Default**: `'zoomoot_admin'`

**Description**: Name of the session cookie.

**Examples**:

```php
// Default
define('SESSION_NAME', 'zoomoot_admin');

// Custom name (alphanumeric and underscore only)
define('SESSION_NAME', 'myorg_scores');

// Multiple instances on same domain
define('SESSION_NAME', 'zoomoot_instance_1');
```

**Considerations**:
- Must be unique if running multiple instances on the same domain
- Use alphanumeric characters and underscores only
- Cannot contain special characters or spaces

### Session Lifetime

**Setting**: `SESSION_LIFETIME`

**Default**: `3600` (1 hour)

**Description**: Session timeout in seconds. After this period of inactivity, users must log in again.

**Common Values**:

```php
// 30 minutes
define('SESSION_LIFETIME', 1800);

// 1 hour (default)
define('SESSION_LIFETIME', 3600);

// 2 hours
define('SESSION_LIFETIME', 7200);

// 8 hours (work day)
define('SESSION_LIFETIME', 28800);

// 24 hours
define('SESSION_LIFETIME', 86400);
```

**Considerations**:
- **Shorter** = More secure, users must log in more frequently
- **Longer** = More convenient, but higher security risk if device is left unattended
- Recommended: 1-2 hours for most use cases
- Production environments should use shorter timeouts

### Session Security

Sessions use the following security settings (configured in `html/api/auth.php`):

```php
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/',
    'domain' => '',
    'secure' => false,      // Set to true if using HTTPS
    'httponly' => true,     // Prevents JavaScript access
    'samesite' => 'Strict'  // Prevents CSRF attacks
]);
```

**For production with HTTPS**, modify `html/api/auth.php`:

```php
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/',
    'domain' => '',
    'secure' => true,       // ← Change this to true
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

## Security Settings

### Admin Password

**Setting**: `ADMIN_PASSWORD`

**Default**: `'changeme123'` (in env_sample.php)

**Description**: The password required to log in to the admin interface.

**⚠️ CRITICAL**: You **must** change this from the default before deployment.

**Password Requirements**:
- Minimum 12 characters (recommended)
- Mix of uppercase and lowercase letters
- Include numbers
- Include special characters
- Avoid common words or patterns

**Examples**:

```php
// Weak - DO NOT USE
define('ADMIN_PASSWORD', 'password123');
define('ADMIN_PASSWORD', 'admin');

// Strong - RECOMMENDED
define('ADMIN_PASSWORD', 'T7!kQ$mP9@xL2#vR');
define('ADMIN_PASSWORD', 'MyC0mpl3x!P@ssw0rd#2024');
```

**Generating Strong Passwords**:

```bash
# Linux/macOS - Generate random password
openssl rand -base64 24

# Or use a password manager like:
# - 1Password
# - LastPass
# - Bitwarden
```

### Password Salt

**Setting**: `PASSWORD_SALT`

**Default**: `'random-salt-string'` (in env_sample.php)

**Description**: A random string used to hash the admin password. This prevents rainbow table attacks.

**⚠️ CRITICAL**: You **must** change this from the default before deployment.

**Salt Requirements**:
- Minimum 32 characters
- Random and unpredictable
- Unique per installation

**Generating a Salt**:

```bash
# Generate 32-byte random salt (64 hex characters)
php -r "echo bin2hex(random_bytes(32));"

# Or 64-byte salt (128 hex characters) - more secure
php -r "echo bin2hex(random_bytes(64));"
```

**Example**:

```php
define('PASSWORD_SALT', 'a7f3c9e2b1d8f4a6c3e7b9d2f5a8c1e4b7d3f6a9c2e5b8d1f4a7c3e6b9d2f5a8');
```

**⚠️ WARNING**: Changing the salt after initial setup will invalidate the current password. You'll need to update `ADMIN_PASSWORD` as well.

### File Permissions Security

Protect sensitive files from web access:

**.gitignore**:
```
env.php
score/*.db
score/*.db-wal
score/*.db-shm
```

**Apache** (.htaccess):
```apache
<FilesMatch "^(env\.php|test_.*\.php)$">
    Require all denied
</FilesMatch>
```

**Nginx**:
```nginx
location ~ ^/(env\.php|test_.*\.php) {
    deny all;
    return 404;
}
```

### HTTPS Configuration

**Production environments MUST use HTTPS** to protect session cookies and passwords.

**Apache with Let's Encrypt**:

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Obtain certificate
sudo certbot --apache -d scores.yourdomain.com

# Auto-renewal is configured automatically
```

**After enabling HTTPS**, update `html/api/auth.php`:

```php
session_set_cookie_params([
    // ... other settings ...
    'secure' => true,  // ← Enable this
]);
```

## Performance Tuning

### SQLite Optimizations

The database uses the following optimizations (configured in `html/api/db.php`):

```php
// Enable Write-Ahead Logging for better concurrency
$this->exec('PRAGMA journal_mode=WAL;');

// Enforce foreign key constraints
$this->exec('PRAGMA foreign_keys=ON;');
```

**Additional Performance Settings** (add to `html/api/db.php` if needed):

```php
// Increase cache size (in pages, default is 2000)
$this->exec('PRAGMA cache_size=10000;');

// Set synchronous mode (trade-off between safety and speed)
$this->exec('PRAGMA synchronous=NORMAL;');  // Default is FULL

// Set temp_store to memory for faster sorting
$this->exec('PRAGMA temp_store=MEMORY;');
```

**⚠️ Note**: Changing `synchronous` mode reduces crash safety. Only use `NORMAL` if you have regular backups.

### PHP Performance

**Enable OPcache** (php.ini):

```ini
[opcache]
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  # Production only
```

**Session Storage** (php.ini):

```ini
# Use faster session storage
session.save_handler=files
session.save_path=/tmp

# Or use Redis/Memcached for high-traffic sites
; session.save_handler=redis
; session.save_path=tcp://127.0.0.1:6379
```

### Web Server Tuning

**Apache** - Enable compression:

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css application/json
</IfModule>
```

**Nginx** - Enable compression:

```nginx
gzip on;
gzip_types text/plain text/css application/json;
gzip_min_length 1000;
```

## Debug and Development

### Debug Mode

**Setting**: `DEBUG_MODE`

**Default**: `false`

**Description**: Controls whether detailed error messages are displayed.

**Values**:

```php
// Production - Hide error details
define('DEBUG_MODE', false);

// Development - Show error details
define('DEBUG_MODE', true);
```

**When enabled**, API errors include detailed messages and stack traces:

```json
{
  "success": false,
  "error": "SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: team.team_name",
  "details": "..." // Full stack trace
}
```

**When disabled**, errors show generic messages:

```json
{
  "success": false,
  "error": "An error occurred while processing your request"
}
```

**⚠️ CRITICAL**: Always set `DEBUG_MODE = false` in production to prevent information disclosure.

### PHP Error Reporting

**Development** (php.ini or .htaccess):

```ini
error_reporting = E_ALL
display_errors = On
log_errors = On
error_log = /var/log/php/error.log
```

**Production** (php.ini or .htaccess):

```ini
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
```

### Testing Configuration

For running tests, use a separate test database:

**test_env.php** (create this file):

```php
<?php
define('APP_NAME', 'Zoomoot Test');
define('DB_PATH', __DIR__ . '/score/test_database.db');
define('ADMIN_PASSWORD', 'test123');
define('PASSWORD_SALT', 'test-salt');
define('SESSION_NAME', 'zoomoot_test');
define('SESSION_LIFETIME', 3600);
define('DEBUG_MODE', true);
?>
```

**Run tests with test configuration**:

```bash
# Modify test files to load test_env.php instead of env.php
php test_database_integrity.php
bash test_authentication_flow.sh
```

## Environment-Specific Configurations

### Development Environment

```php
<?php
define('APP_NAME', 'Zoomoot Dev');
define('DB_PATH', __DIR__ . '/score/dev_database.db');
define('ADMIN_PASSWORD', 'dev123');  // Weak password OK for dev
define('PASSWORD_SALT', 'dev-salt');
define('SESSION_NAME', 'zoomoot_dev');
define('SESSION_LIFETIME', 86400);  // 24 hours - convenient for dev
define('DEBUG_MODE', true);  // Show errors
?>
```

### Staging Environment

```php
<?php
define('APP_NAME', 'Zoomoot Staging');
define('DB_PATH', __DIR__ . '/score/staging_database.db');
define('ADMIN_PASSWORD', 'staging-secure-password');
define('PASSWORD_SALT', 'a7f3c9e2b1d8f4a6c3e7b9d2f5a8c1e4');
define('SESSION_NAME', 'zoomoot_staging');
define('SESSION_LIFETIME', 3600);  // 1 hour
define('DEBUG_MODE', false);  // Production-like
?>
```

### Production Environment

```php
<?php
define('APP_NAME', 'Zoomoot Score Tracker');
define('DB_PATH', '/var/lib/zoomoot/production.db');  // Outside web root
define('ADMIN_PASSWORD', 'T7!kQ$mP9@xL2#vR');  // Strong password
define('PASSWORD_SALT', 'a7f3c9e2b1d8f4a6c3e7b9d2f5a8c1e4b7d3f6a9c2e5b8d1f4a7c3e6b9d2f5a8');
define('SESSION_NAME', 'zoomoot_admin');
define('SESSION_LIFETIME', 3600);  // 1 hour
define('DEBUG_MODE', false);  // Never show errors in production
?>
```

## Configuration Validation

### Verify Configuration

Run this check after setup:

```bash
php -r "
require 'env.php';
echo 'APP_NAME: ' . APP_NAME . PHP_EOL;
echo 'DB_PATH: ' . DB_PATH . PHP_EOL;
echo 'SESSION_NAME: ' . SESSION_NAME . PHP_EOL;
echo 'SESSION_LIFETIME: ' . SESSION_LIFETIME . ' seconds' . PHP_EOL;
echo 'DEBUG_MODE: ' . (DEBUG_MODE ? 'ENABLED' : 'DISABLED') . PHP_EOL;
echo 'Password set: ' . (ADMIN_PASSWORD !== 'changeme123' ? 'YES' : 'NO - CHANGE IT!') . PHP_EOL;
echo 'Salt set: ' . (PASSWORD_SALT !== 'random-salt-string' ? 'YES' : 'NO - CHANGE IT!') . PHP_EOL;
"
```

**Expected output**:

```
APP_NAME: Zoomoot Score Tracker
DB_PATH: /path/to/score/zoomoot_scores.db
SESSION_NAME: zoomoot_admin
SESSION_LIFETIME: 3600 seconds
DEBUG_MODE: DISABLED
Password set: YES
Salt set: YES
```

### Security Checklist

Before going to production, verify:

- [ ] `env.php` exists and is not in version control
- [ ] `ADMIN_PASSWORD` is changed from default
- [ ] `ADMIN_PASSWORD` is strong (12+ characters, mixed)
- [ ] `PASSWORD_SALT` is changed from default
- [ ] `PASSWORD_SALT` is at least 32 characters
- [ ] `DEBUG_MODE` is set to `false`
- [ ] `SESSION_LIFETIME` is appropriate (1-2 hours recommended)
- [ ] Database path is set correctly
- [ ] Database directory has correct permissions
- [ ] HTTPS is enabled and enforced
- [ ] `secure` flag is set to `true` in session cookie params
- [ ] Web server denies access to `env.php`
- [ ] Web server denies access to test files
- [ ] Regular backups are configured

## Troubleshooting Configuration Issues

### Configuration File Not Found

**Error**: `Warning: require(env.php): failed to open stream`

**Solution**:
```bash
# Ensure env.php exists
ls -la env.php

# If not, copy from sample
cp env_sample.php env.php
```

### Permission Denied on Database

**Error**: `SQLSTATE[HY000] [14] unable to open database file`

**Solution**:
```bash
# Check directory permissions
ls -la score/

# Fix permissions
chmod 755 score
chown www-data:www-data score
```

### Session Not Persisting

**Error**: Users are logged out immediately

**Solutions**:

1. **Check session save path**:
   ```bash
   php -i | grep session.save_path
   ```

2. **Ensure directory is writable**:
   ```bash
   sudo chmod 777 /var/lib/php/sessions
   ```

3. **Verify SESSION_NAME is set**:
   ```bash
   grep SESSION_NAME env.php
   ```

### Debug Mode Not Working

**Error**: Still seeing generic errors when `DEBUG_MODE = true`

**Solution**:
```bash
# Clear any opcode cache
sudo systemctl restart php8.1-fpm

# Check if env.php is being loaded
php -r "require 'env.php'; var_dump(DEBUG_MODE);"
```

## Related Documentation

- [Installation Guide](INSTALLATION.md) - Setup instructions
- [API Documentation](API_DOCUMENTATION.md) - API endpoint reference
- [Backup & Restore](BACKUP_RESTORE.md) - Database backup procedures
- [Deployment Guide](DEPLOYMENT.md) - Production deployment

## Support

For configuration issues, check:
1. PHP error logs (`/var/log/php/error.log`)
2. Web server logs (`/var/log/apache2/` or `/var/log/nginx/`)
3. Verify all settings with the validation script above
4. Ensure all required PHP extensions are installed
