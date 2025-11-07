# Production Deployment Guide - Zoomoot Score Tracker

This guide provides a comprehensive checklist and guidelines for deploying the Zoomoot Score Tracker to a production environment.

## Table of Contents

- [Pre-Deployment Checklist](#pre-deployment-checklist)
- [Server Requirements](#server-requirements)
- [Deployment Steps](#deployment-steps)
- [Web Server Configuration](#web-server-configuration)
- [SSL/HTTPS Setup](#sslhttps-setup)
- [Performance Optimization](#performance-optimization)
- [Security Hardening](#security-hardening)
- [Monitoring and Maintenance](#monitoring-and-maintenance)
- [Rollback Procedures](#rollback-procedures)

## Pre-Deployment Checklist

### Code Preparation

- [ ] All code committed to version control (git)
- [ ] Development/test files excluded from deployment
- [ ] `.gitignore` properly configured
- [ ] All tests passing (`test_database_integrity.php`, etc.)
- [ ] No debug output in code
- [ ] Error handling implemented for all endpoints

### Configuration Review

- [ ] `env.php` configured with production settings
- [ ] `ADMIN_PASSWORD` changed from default (strong password)
- [ ] `PASSWORD_SALT` changed from default (32+ random characters)
- [ ] `DEBUG_MODE` set to `false`
- [ ] `SESSION_LIFETIME` set appropriately (1-2 hours recommended)
- [ ] Database path configured correctly
- [ ] Session `secure` flag will be enabled after HTTPS setup

### Database Preparation

- [ ] Database schema initialized (`php html/api/init.php`)
- [ ] Sample/test data removed or replaced with real data
- [ ] Database file permissions set correctly (644)
- [ ] Database directory writable by web server (755)
- [ ] Database integrity verified (`PRAGMA integrity_check`)

### Security Verification

- [ ] Sensitive files excluded from public access
- [ ] Web server denies access to `env.php`
- [ ] Web server denies access to test files (`test_*.php`)
- [ ] Web server denies access to backup directory
- [ ] `.git` directory not accessible via web
- [ ] Directory listing disabled

### Backup Setup

- [ ] Backup script created and tested
- [ ] Backup schedule configured (cron/Task Scheduler)
- [ ] Backup verification script working
- [ ] Off-site backup location configured
- [ ] Restore procedure tested

## Server Requirements

### Minimum Requirements

- **OS**: Linux (Ubuntu 20.04+, Debian 11+, CentOS 8+) or Windows Server
- **PHP**: 8.0 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **SQLite**: 3.31.0+
- **Disk Space**: 100 MB minimum (more for backups)
- **RAM**: 512 MB minimum
- **SSL Certificate**: Required for production

### Required PHP Extensions

```bash
# Check installed extensions
php -m

# Required extensions:
# - sqlite3
# - json
# - session
# - pdo_sqlite (optional, for future compatibility)
```

**Install missing extensions** (Ubuntu/Debian):
```bash
sudo apt update
sudo apt install php8.1-sqlite3 php8.1-json
```

### Recommended Server Specifications

For optimal performance:

- **CPU**: 2+ cores
- **RAM**: 2+ GB
- **Disk**: SSD with 1+ GB free space
- **Bandwidth**: 100 Mbps+

## Deployment Steps

### Method 1: Git Deployment (Recommended)

**Step 1: Set up git repository**

On your local machine:
```bash
cd /path/to/score.zoomoot.ca

# Initialize git if not already done
git init

# Add all project files (excluding env.php, database, etc.)
git add .
git commit -m "Initial production deployment"

# Push to remote repository (GitHub, GitLab, etc.)
git remote add origin https://github.com/yourusername/score.zoomoot.ca.git
git push -u origin main
```

**Step 2: Clone on production server**

On production server:
```bash
# Navigate to web root
cd /var/www

# Clone repository
sudo git clone https://github.com/yourusername/score.zoomoot.ca.git

# Set ownership
sudo chown -R www-data:www-data score.zoomoot.ca
```

**Step 3: Configure environment**

```bash
cd score.zoomoot.ca

# Copy and edit configuration
sudo cp env_sample.php env.php
sudo nano env.php
# (Set strong password, random salt, DEBUG_MODE=false)

# Create database directory
sudo mkdir -p score
sudo chown www-data:www-data score
sudo chmod 755 score
```

**Step 4: Initialize database**

```bash
sudo -u www-data php html/api/init.php
```

**Step 5: Set file permissions**

```bash
# Set directory permissions
sudo find /var/www/score.zoomoot.ca -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/score.zoomoot.ca -type f -exec chmod 644 {} \;

# Make scripts executable (if any)
sudo chmod +x scripts/*.sh

# Secure sensitive files
sudo chmod 600 env.php
```

### Method 2: Manual Upload (FTP/SCP)

**Step 1: Prepare deployment package**

On local machine:
```bash
# Create clean copy without sensitive files
mkdir deploy_package
rsync -av --exclude='env.php' \
          --exclude='score/*.db' \
          --exclude='.git' \
          --exclude='backups' \
          --exclude='test_*.php' \
          score.zoomoot.ca/ deploy_package/

# Create archive
tar -czf zoomoot_deploy.tar.gz deploy_package/
```

**Step 2: Upload to server**

```bash
# Using SCP
scp zoomoot_deploy.tar.gz user@yourserver.com:/var/www/

# Or use FTP client (FileZilla, WinSCP, etc.)
```

**Step 3: Extract and configure**

On server:
```bash
cd /var/www
tar -xzf zoomoot_deploy.tar.gz
mv deploy_package score.zoomoot.ca
chown -R www-data:www-data score.zoomoot.ca

# Configure environment (as in Method 1, Step 3)
```

### Method 3: Automated Deployment Script

**deploy.sh**:
```bash
#!/bin/bash

set -e  # Exit on error

# Configuration
REPO_URL="https://github.com/yourusername/score.zoomoot.ca.git"
DEPLOY_DIR="/var/www/score.zoomoot.ca"
WEB_USER="www-data"

echo "Starting deployment..."

# Backup current installation
if [ -d "$DEPLOY_DIR" ]; then
    echo "Backing up current installation..."
    sudo cp -r "$DEPLOY_DIR" "${DEPLOY_DIR}_backup_$(date +%Y%m%d_%H%M%S)"
fi

# Clone or pull latest code
if [ -d "$DEPLOY_DIR/.git" ]; then
    echo "Pulling latest changes..."
    cd "$DEPLOY_DIR"
    sudo -u $WEB_USER git pull
else
    echo "Cloning repository..."
    sudo git clone "$REPO_URL" "$DEPLOY_DIR"
fi

# Set permissions
echo "Setting permissions..."
sudo chown -R $WEB_USER:$WEB_USER "$DEPLOY_DIR"
sudo find "$DEPLOY_DIR" -type d -exec chmod 755 {} \;
sudo find "$DEPLOY_DIR" -type f -exec chmod 644 {} \;

# Verify configuration exists
if [ ! -f "$DEPLOY_DIR/env.php" ]; then
    echo "WARNING: env.php not found. Copy env_sample.php and configure it."
fi

echo "Deployment complete!"
echo "Remember to:"
echo "1. Configure env.php if this is first deployment"
echo "2. Initialize database: php html/api/init.php"
echo "3. Configure web server virtual host"
echo "4. Set up SSL certificate"
```

## Web Server Configuration

### Apache Configuration

**Create virtual host** (`/etc/apache2/sites-available/zoomoot.conf`):

```apache
<VirtualHost *:80>
    ServerName scores.yourdomain.com
    ServerAdmin admin@yourdomain.com
    
    DocumentRoot /var/www/score.zoomoot.ca/html
    
    <Directory /var/www/score.zoomoot.ca/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Optional: Rewrite rules for clean URLs
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^api/(.*)$ api/$1.php [L]
        </IfModule>
    </Directory>
    
    # Deny access to sensitive directories and files
    <DirectoryMatch "^/var/www/score.zoomoot.ca/(\.git|backups|scripts|score)">
        Require all denied
    </DirectoryMatch>
    
    <FilesMatch "^(env\.php|env_sample\.php|test_.*\.php|\.gitignore|README\.md)$">
        Require all denied
    </FilesMatch>
    
    # Security headers
    <IfModule mod_headers.c>
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Referrer-Policy "strict-origin-when-cross-origin"
    </IfModule>
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/zoomoot_error.log
    CustomLog ${APACHE_LOG_DIR}/zoomoot_access.log combined
</VirtualHost>
```

**Enable site and required modules**:

```bash
# Enable required modules
sudo a2enmod rewrite headers

# Enable site
sudo a2ensite zoomoot.conf

# Test configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

### Nginx Configuration

**Create server block** (`/etc/nginx/sites-available/zoomoot`):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name scores.yourdomain.com;
    
    root /var/www/score.zoomoot.ca/html;
    index index.php index.html;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Logging
    access_log /var/log/nginx/zoomoot_access.log;
    error_log /var/log/nginx/zoomoot_error.log;
    
    # Deny access to sensitive files and directories
    location ~ ^/(\.git|backups|scripts|score) {
        deny all;
        return 404;
    }
    
    location ~ ^/(env\.php|env_sample\.php|test_.*\.php|\.gitignore|README\.md|.*\.md) {
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
    
    # Optional: Clean URLs for API
    location /api/ {
        try_files $uri $uri.php $uri/ =404;
    }
}
```

**Enable site and restart**:

```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/zoomoot /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

## SSL/HTTPS Setup

### Using Let's Encrypt (Free SSL)

**Step 1: Install Certbot**

**Ubuntu/Debian**:
```bash
sudo apt update
sudo apt install certbot python3-certbot-apache  # For Apache
# or
sudo apt install certbot python3-certbot-nginx   # For Nginx
```

**Step 2: Obtain certificate**

**Apache**:
```bash
sudo certbot --apache -d scores.yourdomain.com
```

**Nginx**:
```bash
sudo certbot --nginx -d scores.yourdomain.com
```

**Step 3: Verify auto-renewal**

```bash
# Test renewal process
sudo certbot renew --dry-run

# Check renewal timer
sudo systemctl status certbot.timer
```

**Step 4: Update session configuration**

Edit `html/api/auth.php` and enable secure cookies:

```php
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/',
    'domain' => '',
    'secure' => true,       // ← Change to true
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

**Step 5: Force HTTPS redirect**

**Apache** - Add to virtual host:
```apache
# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName scores.yourdomain.com
    Redirect permanent / https://scores.yourdomain.com/
</VirtualHost>
```

**Nginx** - Update server block:
```nginx
# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name scores.yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

### Using Commercial SSL Certificate

If using a commercial certificate provider:

**Step 1: Generate CSR**
```bash
openssl req -new -newkey rsa:2048 -nodes \
    -keyout /etc/ssl/private/zoomoot.key \
    -out /etc/ssl/certs/zoomoot.csr
```

**Step 2: Submit CSR to certificate authority**

**Step 3: Install certificate files**

Place received files:
- Certificate: `/etc/ssl/certs/zoomoot.crt`
- Private key: `/etc/ssl/private/zoomoot.key`
- CA bundle: `/etc/ssl/certs/zoomoot_ca_bundle.crt`

**Step 4: Configure web server**

**Apache**:
```apache
<VirtualHost *:443>
    ServerName scores.yourdomain.com
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/zoomoot.crt
    SSLCertificateKeyFile /etc/ssl/private/zoomoot.key
    SSLCertificateChainFile /etc/ssl/certs/zoomoot_ca_bundle.crt
    
    # ... rest of configuration ...
</VirtualHost>
```

**Nginx**:
```nginx
server {
    listen 443 ssl http2;
    server_name scores.yourdomain.com;
    
    ssl_certificate /etc/ssl/certs/zoomoot.crt;
    ssl_certificate_key /etc/ssl/private/zoomoot.key;
    ssl_trusted_certificate /etc/ssl/certs/zoomoot_ca_bundle.crt;
    
    # ... rest of configuration ...
}
```

## Performance Optimization

### PHP Configuration

Edit `php.ini` or create `/etc/php/8.1/fpm/conf.d/99-zoomoot.ini`:

```ini
[Zoomoot Optimization]

; OPcache settings
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  ; Disable for production
opcache.save_comments=1
opcache.fast_shutdown=1

; Session settings
session.save_handler=files
session.save_path=/var/lib/php/sessions
session.gc_maxlifetime=3600
session.gc_probability=1
session.gc_divisor=100

; Error handling (production)
display_errors=Off
display_startup_errors=Off
log_errors=On
error_log=/var/log/php/error.log

; Upload limits (if needed)
upload_max_filesize=2M
post_max_size=8M

; Execution limits
max_execution_time=30
max_input_time=60
memory_limit=128M
```

**Restart PHP-FPM**:
```bash
sudo systemctl restart php8.1-fpm
```

### Database Optimization

Already configured in `html/api/db.php`:
- WAL mode enabled (better concurrency)
- Foreign keys enforced
- Generated columns for performance

**Additional optimizations** (add to `html/api/db.php` if needed):

```php
// Increase cache size for better performance
$this->exec('PRAGMA cache_size=10000;');

// Store temporary tables in memory
$this->exec('PRAGMA temp_store=MEMORY;');

// Optimize query planner
$this->exec('PRAGMA optimize;');
```

### Web Server Performance

**Apache** - Enable compression:

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css application/json application/javascript
    DeflateCompressionLevel 6
</IfModule>

<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
</IfModule>
```

**Nginx** - Enable compression:

```nginx
# Gzip compression
gzip on;
gzip_vary on;
gzip_min_length 1000;
gzip_types text/plain text/css application/json application/javascript;
gzip_comp_level 6;

# Browser caching
location ~* \.(css|js)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

## Security Hardening

### Firewall Configuration

**UFW (Ubuntu)**:
```bash
# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

**FirewallD (CentOS/RHEL)**:
```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### Fail2ban (Brute Force Protection)

**Install and configure**:

```bash
# Install
sudo apt install fail2ban

# Create custom filter for login attempts
sudo nano /etc/fail2ban/filter.d/zoomoot.conf
```

**zoomoot.conf**:
```ini
[Definition]
failregex = ^.*Failed login attempt from <HOST>.*$
ignoreregex =
```

**Configure jail** (`/etc/fail2ban/jail.local`):
```ini
[zoomoot]
enabled = true
port = http,https
filter = zoomoot
logpath = /var/log/apache2/zoomoot_access.log  # or nginx path
maxretry = 5
bantime = 3600
findtime = 600
```

**Restart fail2ban**:
```bash
sudo systemctl restart fail2ban
sudo fail2ban-client status zoomoot
```

### File Permissions Hardening

```bash
# Application directory
sudo chown -R www-data:www-data /var/www/score.zoomoot.ca

# Directories: 755
sudo find /var/www/score.zoomoot.ca -type d -exec chmod 755 {} \;

# Files: 644
sudo find /var/www/score.zoomoot.ca -type f -exec chmod 644 {} \;

# Sensitive config: 600
sudo chmod 600 /var/www/score.zoomoot.ca/env.php

# Database directory: 755 (web server needs write)
sudo chmod 755 /var/www/score.zoomoot.ca/score

# Database file: 644
sudo chmod 644 /var/www/score.zoomoot.ca/score/*.db
```

### Security Headers

Already configured in web server, verify with:

```bash
curl -I https://scores.yourdomain.com | grep -E "(X-Frame|X-Content|X-XSS|Referrer)"
```

Should show:
```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
```

## Monitoring and Maintenance

### Log Monitoring

**Apache logs**:
```bash
# Real-time monitoring
sudo tail -f /var/log/apache2/zoomoot_access.log
sudo tail -f /var/log/apache2/zoomoot_error.log

# Search for errors
sudo grep -i error /var/log/apache2/zoomoot_error.log
```

**Nginx logs**:
```bash
sudo tail -f /var/log/nginx/zoomoot_access.log
sudo tail -f /var/log/nginx/zoomoot_error.log
```

**PHP error logs**:
```bash
sudo tail -f /var/log/php/error.log
```

### Health Check Script

**scripts/health_check.sh**:

```bash
#!/bin/bash

DOMAIN="https://scores.yourdomain.com"
ALERT_EMAIL="admin@yourdomain.com"

# Check if site is responding
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$DOMAIN")

if [ "$HTTP_CODE" != "200" ]; then
    echo "ERROR: Site returned HTTP $HTTP_CODE" | mail -s "Zoomoot Health Check Failed" "$ALERT_EMAIL"
    exit 1
fi

# Check database integrity
DB_CHECK=$(php -r "require 'html/api/db.php'; \$db = Database::getInstance(); echo \$db->query('PRAGMA integrity_check')->fetchColumn();")

if [ "$DB_CHECK" != "ok" ]; then
    echo "ERROR: Database integrity check failed" | mail -s "Zoomoot Database Error" "$ALERT_EMAIL"
    exit 1
fi

echo "Health check passed"
exit 0
```

**Schedule with cron**:
```bash
# Check every hour
0 * * * * /var/www/score.zoomoot.ca/scripts/health_check.sh
```

### Update Procedures

**Update application code**:

```bash
cd /var/www/score.zoomoot.ca

# Backup database first
sudo -u www-data php -r "copy('score/zoomoot_scores.db', 'score/backup_before_update.db');"

# Pull latest changes
sudo -u www-data git pull

# Restart web server
sudo systemctl restart apache2  # or nginx
```

**Update PHP**:

```bash
# Ubuntu - Update to latest PHP
sudo apt update
sudo apt upgrade php8.1

# Restart services
sudo systemctl restart apache2  # or php8.1-fpm
```

## Rollback Procedures

### Quick Rollback

If deployment fails:

**Git-based deployment**:
```bash
cd /var/www/score.zoomoot.ca

# Revert to previous commit
sudo -u www-data git reset --hard HEAD~1

# Or specific commit
sudo -u www-data git reset --hard abc123

# Restart web server
sudo systemctl restart apache2
```

**Manual deployment**:
```bash
# Restore from backup
sudo rm -rf /var/www/score.zoomoot.ca
sudo mv /var/www/score.zoomoot.ca_backup_TIMESTAMP /var/www/score.zoomoot.ca

# Restart web server
sudo systemctl restart apache2
```

### Database Rollback

```bash
# Restore database from backup
sudo cp /var/www/score.zoomoot.ca/backups/zoomoot_scores_TIMESTAMP.db \
        /var/www/score.zoomoot.ca/score/zoomoot_scores.db

# Set permissions
sudo chown www-data:www-data /var/www/score.zoomoot.ca/score/zoomoot_scores.db
sudo chmod 644 /var/www/score.zoomoot.ca/score/zoomoot_scores.db
```

## Post-Deployment Verification

### Functional Testing

- [ ] **Public page loads**: https://scores.yourdomain.com/
- [ ] **Login works**: Navigate to login, enter password, verify redirect
- [ ] **Protected pages require auth**: Try accessing `/activity.php` without login
- [ ] **Scores display correctly**: Check standings table
- [ ] **CRUD operations work**:
  - Create a test team
  - Create a test activity
  - Enter a test score
  - Update the score
  - Delete the score
- [ ] **Logout works**: Session cleared, redirected to public page

### Security Testing

- [ ] **HTTPS enforced**: http:// redirects to https://
- [ ] **Sensitive files not accessible**:
  - https://scores.yourdomain.com/env.php (should 403/404)
  - https://scores.yourdomain.com/score/zoomoot_scores.db (should 403/404)
  - https://scores.yourdomain.com/.git/ (should 403/404)
- [ ] **Session cookies secure**:
  ```bash
  curl -v https://scores.yourdomain.com/login.php 2>&1 | grep -i cookie
  # Should show: HttpOnly; Secure; SameSite=Strict
  ```
- [ ] **Security headers present**:
  ```bash
  curl -I https://scores.yourdomain.com | grep -E "(X-Frame|X-Content)"
  ```

### Performance Testing

```bash
# Test response time
ab -n 100 -c 10 https://scores.yourdomain.com/

# Or using curl
time curl https://scores.yourdomain.com/
```

Expected: < 500ms response time

## Maintenance Schedule

### Daily
- Review error logs for issues
- Monitor disk space
- Verify backups completed successfully

### Weekly
- Review access logs for suspicious activity
- Test backup restoration (sample file)
- Check SSL certificate expiration

### Monthly
- Update PHP and system packages
- Review and rotate logs
- Test disaster recovery procedure
- Audit user activity

### Quarterly
- Security audit (penetration testing)
- Performance review and optimization
- Review and update documentation

## Related Documentation

- [Installation Guide](INSTALLATION.md) - Setup instructions
- [Configuration Guide](CONFIGURATION.md) - Environment configuration
- [API Documentation](API_DOCUMENTATION.md) - API reference
- [Backup & Restore](BACKUP_RESTORE.md) - Database backup procedures

## Support

For deployment issues:
1. Check web server error logs
2. Check PHP error logs
3. Verify file permissions
4. Ensure all prerequisites are met
5. Test in staging environment first
6. Review configuration settings
