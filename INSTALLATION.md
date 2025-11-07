# Installation Guide - Zoomoot Score Tracker

A lightweight PHP-based score tracking system for managing team activities and scores.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Detailed Installation](#detailed-installation)
- [Configuration](#configuration)
- [Running the Application](#running-the-application)
- [Troubleshooting](#troubleshooting)

## Prerequisites

### Required Software

- **PHP 8.0 or higher** with the following extensions:
  - SQLite3
  - Session support
  - JSON support
- **Web Server** (one of the following):
  - Apache with mod_rewrite
  - Nginx
  - PHP built-in server (development only)
- **SQLite3** database support

### System Requirements

- Minimum 50MB disk space
- Write permissions for database directory
- HTTPS recommended for production

## Quick Start

For development/testing on Linux/macOS/WSL:

```bash
# 1. Clone or download the repository
cd /path/to/score.zoomoot.ca

# 2. Copy and configure environment file
cp env_sample.php env.php
nano env.php  # Edit configuration (see below)

# 3. Create database directory
mkdir -p score
chmod 755 score

# 4. Initialize database
php html/api/init.php

# 5. Start development server
cd html
php -S localhost:8000

# 6. Open browser
# Navigate to http://localhost:8000
```

## Detailed Installation

### Step 1: Download and Extract

```bash
# Option A: Git clone
git clone https://github.com/yourusername/score.zoomoot.ca.git
cd score.zoomoot.ca

# Option B: Download and extract
unzip score.zoomoot.ca.zip
cd score.zoomoot.ca
```

### Step 2: Configure Environment

Create your environment configuration file:

```bash
cp env_sample.php env.php
```

Edit `env.php` with your settings:

```php
<?php
// Application name
define('APP_NAME', 'Zoomoot Score Tracker');

// Database configuration
define('DB_PATH', __DIR__ . '/score/zoomoot_scores.db');

// Authentication
define('ADMIN_PASSWORD', 'your-secure-password-here');  // CHANGE THIS!
define('PASSWORD_SALT', 'your-random-salt-here');       // CHANGE THIS!

// Session configuration
define('SESSION_NAME', 'zoomoot_admin');
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Debug mode (set to false in production)
define('DEBUG_MODE', false);
?>
```

**Important Security Notes:**
- ⚠️ **Always change** `ADMIN_PASSWORD` from the default
- ⚠️ **Always change** `PASSWORD_SALT` to a random string (32+ characters)
- Use a strong password (12+ characters, mixed case, numbers, symbols)
- Generate salt with: `php -r "echo bin2hex(random_bytes(32));"`

### Step 3: Set Up Database

Create the database directory with proper permissions:

```bash
# Create directory
mkdir -p score

# Set permissions (Linux/macOS)
chmod 755 score
chown www-data:www-data score  # Adjust user/group for your web server

# For development/testing
chmod 777 score  # Less secure, development only
```

Initialize the database schema:

```bash
php html/api/init.php
```

You should see:

```
Created activity table
Created team table
Created score table
Inserted sample data
Database schema initialized successfully!
```

### Step 4: Configure Web Server

#### Option A: PHP Built-in Server (Development Only)

```bash
cd html
php -S localhost:8000
```

Access at: `http://localhost:8000`

#### Option B: Apache

Create a virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName scores.yourdomain.com
    DocumentRoot /var/www/score.zoomoot.ca/html
    
    <Directory /var/www/score.zoomoot.ca/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Rewrite rules for clean URLs (optional)
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^api/(.*)$ api/$1.php [L]
    </Directory>
    
    # Deny access to sensitive files
    <FilesMatch "^(env\.php|\.git|\.gitignore|test_.*\.php)$">
        Require all denied
    </FilesMatch>
    
    ErrorLog ${APACHE_LOG_DIR}/scores_error.log
    CustomLog ${APACHE_LOG_DIR}/scores_access.log combined
</VirtualHost>
```

Enable and restart:

```bash
sudo a2ensite scores.conf
sudo systemctl restart apache2
```

#### Option C: Nginx

Create a server block:

```nginx
server {
    listen 80;
    server_name scores.yourdomain.com;
    root /var/www/score.zoomoot.ca/html;
    index index.php;
    
    # Deny access to sensitive files
    location ~ ^/(env\.php|\.git|test_.*\.php) {
        deny all;
        return 404;
    }
    
    # PHP handling
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to .ht files
    location ~ /\.ht {
        deny all;
    }
}
```

Test and reload:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

### Step 5: Verify Installation

1. **Access the application**:
   - Navigate to your configured URL (e.g., `http://localhost:8000`)
   - You should see the public standings page

2. **Test login**:
   - Click "Activity Leader Login"
   - Enter your configured admin password
   - You should be redirected to the activity management page

3. **Run tests** (optional):
   ```bash
   php test_db.php
   php test_auth.php
   php test_database_integrity.php
   bash test_api_simple.sh
   ```

## Configuration

### Database Configuration

The SQLite database is stored at `score/zoomoot_scores.db`. The location can be changed in `env.php`:

```php
define('DB_PATH', '/custom/path/to/database.db');
```

### Session Configuration

Adjust session settings in `env.php`:

```php
define('SESSION_NAME', 'zoomoot_admin');    // Cookie name
define('SESSION_LIFETIME', 3600);           // Session timeout (seconds)
```

Session lifetime options:
- `1800` = 30 minutes
- `3600` = 1 hour (default)
- `7200` = 2 hours
- `86400` = 24 hours

### Debug Mode

Enable debug mode for development:

```php
define('DEBUG_MODE', true);  // Shows detailed error messages
```

**⚠️ Always set to `false` in production** to prevent information disclosure.

## Running the Application

### Development

```bash
cd html
php -S localhost:8000
```

### Production

Use a proper web server (Apache/Nginx) with:
- HTTPS enabled (Let's Encrypt recommended)
- Database backups configured
- Proper file permissions
- Debug mode disabled

## Troubleshooting

### Database Issues

**Problem**: "Database file not found" or "unable to open database"

**Solution**:
```bash
# Check directory exists and has permissions
ls -la score/
chmod 755 score
```

**Problem**: "Database is locked"

**Solution**:
```bash
# Check for stale lock files
rm -f score/zoomoot_scores.db-wal
rm -f score/zoomoot_scores.db-shm

# Ensure only one process accesses the database
```

### Authentication Issues

**Problem**: "Login keeps redirecting to login page"

**Solution**:
- Check that `env.php` exists and is configured
- Verify `PASSWORD_SALT` and `ADMIN_PASSWORD` are set
- Clear browser cookies
- Check PHP session directory has write permissions:
  ```bash
  # Check session path
  php -i | grep session.save_path
  
  # Ensure it's writable
  sudo chmod 777 /var/lib/php/sessions
  ```

**Problem**: "Session expires too quickly"

**Solution**:
- Increase `SESSION_LIFETIME` in `env.php`
- Check server clock is accurate
- Ensure PHP session garbage collection isn't too aggressive

### Permission Issues

**Problem**: "Permission denied" errors

**Solution**:
```bash
# Set proper ownership (Apache example)
sudo chown -R www-data:www-data /var/www/score.zoomoot.ca

# Set directory permissions
find /var/www/score.zoomoot.ca -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/score.zoomoot.ca -type f -exec chmod 644 {} \;

# Database directory needs write access
chmod 755 /var/www/score.zoomoot.ca/score
```

### API Issues

**Problem**: "API endpoints return 404"

**Solution**:
- Ensure you're accessing URLs correctly:
  - `http://localhost:8000/api/team.php` ✓
  - `http://localhost:8000/../api/team.php` ✗
- Check web server configuration allows .php files
- Verify `api/` directory exists in `html/` folder

**Problem**: "CORS errors in browser console"

**Solution**: Not applicable - this is a same-origin application. If you see CORS errors, ensure you're accessing all pages from the same domain.

### Display Issues

**Problem**: "Styles not loading" or "Broken layout"

**Solution**:
- Check internet connection (Bootstrap/Alpine.js load from CDN)
- Open browser developer tools (F12) and check Console for errors
- Verify CDN URLs are accessible:
  - https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css
  - https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js

**Problem**: "Modals not appearing or can't be dismissed"

**Solution**: This was a known issue that has been fixed. Ensure you have the latest version with Alpine.js properly configured.

### Performance Issues

**Problem**: "Slow page loads"

**Solution**:
- Enable SQLite WAL mode (already enabled in init.php)
- Add database indices for frequently queried columns
- Enable PHP opcode caching (OPcache)
- Use a production web server instead of PHP built-in

## Security Checklist

Before deploying to production:

- [ ] Changed default `ADMIN_PASSWORD`
- [ ] Changed default `PASSWORD_SALT` to random string
- [ ] Set `DEBUG_MODE` to `false`
- [ ] Enabled HTTPS/SSL
- [ ] Configured proper file permissions (755 for directories, 644 for files)
- [ ] Denied public access to `env.php` via web server configuration
- [ ] Set up regular database backups
- [ ] Reviewed and understood session timeout settings
- [ ] Tested login/logout functionality
- [ ] Verified API authentication requirements

## Next Steps

- [API Documentation](API_DOCUMENTATION.md) - Learn about available endpoints
- [Configuration Guide](CONFIGURATION.md) - Detailed configuration options
- [Backup & Restore](BACKUP_RESTORE.md) - Database backup procedures
- [Deployment Guide](DEPLOYMENT.md) - Production deployment checklist

## Getting Help

If you encounter issues not covered here:

1. Check the test files for examples of proper usage
2. Review the source code comments
3. Check file permissions and ownership
4. Verify PHP and SQLite versions meet requirements
5. Check web server error logs

## License

[Your License Here]
