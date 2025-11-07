# Backup & Restore Guide - Zoomoot Score Tracker

This guide covers database backup and restore procedures for the Zoomoot Score Tracker application.

## Table of Contents

- [Overview](#overview)
- [Manual Backup](#manual-backup)
- [Automated Backup](#automated-backup)
- [Restore Procedures](#restore-procedures)
- [Backup Verification](#backup-verification)
- [Disaster Recovery](#disaster-recovery)
- [Best Practices](#best-practices)

## Overview

### What to Backup

The Zoomoot Score Tracker stores all data in a single SQLite database file. You need to backup:

**Required**:
- Database file: `score/zoomoot_scores.db`

**Optional but recommended**:
- Configuration: `env.php`
- WAL files (if present): `score/zoomoot_scores.db-wal`, `score/zoomoot_scores.db-shm`

**Not needed**:
- PHP source files (can be restored from repository)
- Bootstrap/Alpine.js files (loaded from CDN)

### Backup Frequency

Recommended backup schedules based on usage:

- **High activity** (daily events): Every 6-12 hours
- **Regular activity** (weekly events): Daily
- **Low activity** (monthly events): Weekly
- **Before major changes**: Always (manual backup)

### Storage Considerations

**Backup size**: The database is typically very small (< 1 MB for hundreds of scores)

**Storage options**:
- Local disk (different drive/partition)
- Network storage (NAS)
- Cloud storage (Dropbox, Google Drive, AWS S3)
- Version control (private Git repository)

## Manual Backup

### Simple File Copy

The easiest backup method is to copy the database file:

**Linux/macOS/WSL**:
```bash
# Create backup directory
mkdir -p backups

# Copy database with timestamp
cp score/zoomoot_scores.db backups/zoomoot_scores_$(date +%Y%m%d_%H%M%S).db

# Verify the copy
ls -lh backups/
```

**Windows PowerShell**:
```powershell
# Create backup directory
New-Item -ItemType Directory -Force -Path backups

# Copy database with timestamp
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
Copy-Item score/zoomoot_scores.db -Destination "backups/zoomoot_scores_$timestamp.db"

# Verify the copy
Get-ChildItem backups/
```

### SQLite Backup Command

Using SQLite's built-in backup command (more reliable for active databases):

```bash
# Backup while database is in use
sqlite3 score/zoomoot_scores.db ".backup backups/zoomoot_scores_$(date +%Y%m%d_%H%M%S).db"

# Or using vacuum into (creates optimized copy)
sqlite3 score/zoomoot_scores.db "VACUUM INTO 'backups/zoomoot_scores_$(date +%Y%m%d_%H%M%S).db'"
```

**Advantages**:
- Works while database is in use
- Creates consistent snapshot
- VACUUM INTO optimizes and compacts the database

### Backup with Compression

Save space with compressed backups:

**Linux/macOS/WSL**:
```bash
# Gzip compression
gzip -c score/zoomoot_scores.db > backups/zoomoot_scores_$(date +%Y%m%d_%H%M%S).db.gz

# Or use tar with compression
tar -czf backups/zoomoot_scores_$(date +%Y%m%d_%H%M%S).tar.gz score/zoomoot_scores.db

# Verify compression ratio
ls -lh backups/
```

**Typical compression**: 80-90% size reduction

### Full Backup (Database + Configuration)

Backup both database and configuration:

```bash
# Create timestamped backup directory
BACKUP_DIR="backups/full_backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Copy database
cp score/zoomoot_scores.db "$BACKUP_DIR/"

# Copy configuration (contains sensitive data)
cp env.php "$BACKUP_DIR/"

# Create compressed archive
tar -czf "$BACKUP_DIR.tar.gz" -C backups "$(basename $BACKUP_DIR)"

# Remove uncompressed directory
rm -rf "$BACKUP_DIR"

# Result: backups/full_backup_20240120_143022.tar.gz
```

### Export to SQL

Create portable SQL dump (useful for migrations):

```bash
# Export schema and data to SQL
sqlite3 score/zoomoot_scores.db .dump > backups/zoomoot_scores_$(date +%Y%m%d_%H%M%S).sql

# Compress the SQL dump
gzip backups/zoomoot_scores_*.sql

# Verify
ls -lh backups/*.sql.gz
```

**Advantages**:
- Human-readable format
- Can be used with other databases
- Easy to review changes

**Disadvantages**:
- Larger file size (before compression)
- Slower to restore

## Automated Backup

### Cron Job (Linux/macOS)

Set up automatic daily backups:

**1. Create backup script** (`scripts/backup.sh`):

```bash
#!/bin/bash

# Configuration
PROJECT_DIR="/var/www/score.zoomoot.ca"
BACKUP_DIR="$PROJECT_DIR/backups"
DB_PATH="$PROJECT_DIR/score/zoomoot_scores.db"
RETENTION_DAYS=30

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Generate timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/zoomoot_scores_$TIMESTAMP.db.gz"

# Perform backup
if sqlite3 "$DB_PATH" ".backup /tmp/backup_tmp.db"; then
    gzip -c /tmp/backup_tmp.db > "$BACKUP_FILE"
    rm /tmp/backup_tmp.db
    echo "[$(date)] Backup successful: $BACKUP_FILE"
    
    # Delete old backups
    find "$BACKUP_DIR" -name "zoomoot_scores_*.db.gz" -mtime +$RETENTION_DAYS -delete
    echo "[$(date)] Deleted backups older than $RETENTION_DAYS days"
else
    echo "[$(date)] Backup failed!" >&2
    exit 1
fi
```

**2. Make script executable**:

```bash
chmod +x scripts/backup.sh
```

**3. Test the script**:

```bash
./scripts/backup.sh
```

**4. Add to crontab**:

```bash
# Edit crontab
crontab -e

# Add this line for daily backup at 2 AM
0 2 * * * /var/www/score.zoomoot.ca/scripts/backup.sh >> /var/log/zoomoot_backup.log 2>&1

# Or for backup every 6 hours
0 */6 * * * /var/www/score.zoomoot.ca/scripts/backup.sh >> /var/log/zoomoot_backup.log 2>&1
```

**Cron schedule examples**:
- `0 2 * * *` - Daily at 2 AM
- `0 */6 * * *` - Every 6 hours
- `0 2 * * 0` - Weekly on Sunday at 2 AM
- `0 2 1 * *` - Monthly on the 1st at 2 AM

### Windows Task Scheduler

**1. Create backup script** (`scripts\backup.ps1`):

```powershell
# Configuration
$ProjectDir = "C:\Users\dalem\OneDrive\Documents\GitHub\score.zoomoot.ca"
$BackupDir = "$ProjectDir\backups"
$DbPath = "$ProjectDir\score\zoomoot_scores.db"
$RetentionDays = 30

# Create backup directory
New-Item -ItemType Directory -Force -Path $BackupDir | Out-Null

# Generate timestamp
$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$BackupFile = "$BackupDir\zoomoot_scores_$Timestamp.db"

# Perform backup
try {
    Copy-Item $DbPath -Destination $BackupFile
    Write-Host "[$(Get-Date)] Backup successful: $BackupFile"
    
    # Delete old backups
    Get-ChildItem $BackupDir -Filter "zoomoot_scores_*.db" | 
        Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$RetentionDays) } | 
        Remove-Item
    Write-Host "[$(Get-Date)] Deleted backups older than $RetentionDays days"
}
catch {
    Write-Error "[$(Get-Date)] Backup failed: $_"
    exit 1
}
```

**2. Create scheduled task**:

```powershell
# Run as Administrator
$Action = New-ScheduledTaskAction -Execute "PowerShell.exe" `
    -Argument "-File C:\Users\dalem\OneDrive\Documents\GitHub\score.zoomoot.ca\scripts\backup.ps1"

$Trigger = New-ScheduledTaskTrigger -Daily -At 2am

$Settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopOnIdleEnd

Register-ScheduledTask -TaskName "Zoomoot Backup" `
    -Action $Action `
    -Trigger $Trigger `
    -Settings $Settings `
    -Description "Daily backup of Zoomoot Score Tracker database"
```

### Cloud Backup with Rclone

Sync backups to cloud storage:

**1. Install rclone**:

```bash
# Linux
curl https://rclone.org/install.sh | sudo bash

# Configure cloud provider (e.g., Google Drive, Dropbox)
rclone config
```

**2. Add to backup script**:

```bash
# After creating backup, sync to cloud
rclone sync "$BACKUP_DIR" remote:zoomoot_backups \
    --include "zoomoot_scores_*.db.gz" \
    --max-age 90d \
    --verbose
```

**3. Schedule regular syncs**:

```bash
# Sync backups to cloud every hour
0 * * * * rclone sync /var/www/score.zoomoot.ca/backups remote:zoomoot_backups --include "*.db.gz" >> /var/log/rclone_backup.log 2>&1
```

## Restore Procedures

### Restore from Backup

**⚠️ Warning**: Restoring will **overwrite** current data. Always backup current state first.

**Step 1: Stop the web server** (if possible):

```bash
# Apache
sudo systemctl stop apache2

# Nginx
sudo systemctl stop nginx

# Or just stop the PHP server
# (Ctrl+C if running php -S)
```

**Step 2: Backup current database** (just in case):

```bash
cp score/zoomoot_scores.db score/zoomoot_scores.db.before_restore
```

**Step 3: Restore from backup**:

**From uncompressed backup**:
```bash
cp backups/zoomoot_scores_20240120_143022.db score/zoomoot_scores.db
```

**From compressed backup**:
```bash
gunzip -c backups/zoomoot_scores_20240120_143022.db.gz > score/zoomoot_scores.db
```

**From tar archive**:
```bash
tar -xzf backups/full_backup_20240120_143022.tar.gz -C /tmp
cp /tmp/full_backup_20240120_143022/zoomoot_scores.db score/
```

**From SQL dump**:
```bash
# Remove existing database
rm score/zoomoot_scores.db

# Restore from SQL
gunzip -c backups/zoomoot_scores_20240120_143022.sql.gz | sqlite3 score/zoomoot_scores.db
```

**Step 4: Set correct permissions**:

```bash
chmod 644 score/zoomoot_scores.db
chown www-data:www-data score/zoomoot_scores.db
```

**Step 5: Restart web server**:

```bash
sudo systemctl start apache2
# or
sudo systemctl start nginx
```

**Step 6: Verify restore**:

```bash
# Check database integrity
sqlite3 score/zoomoot_scores.db "PRAGMA integrity_check;"

# Should output: ok

# Check record counts
sqlite3 score/zoomoot_scores.db "SELECT 'Teams:', COUNT(*) FROM team; SELECT 'Activities:', COUNT(*) FROM activity; SELECT 'Scores:', COUNT(*) FROM score;"
```

### Restore Specific Tables

Restore only certain tables from backup:

```bash
# Export specific table from backup
sqlite3 backups/zoomoot_scores_20240120.db ".dump score" > /tmp/score_table.sql

# Import into current database
sqlite3 score/zoomoot_scores.db < /tmp/score_table.sql
```

### Point-in-Time Recovery

If you have multiple backups, you can restore to a specific point in time:

```bash
# List available backups
ls -lh backups/

# Choose backup closest to desired time
# Example: Restore to yesterday at 2 PM
cp backups/zoomoot_scores_20240119_140000.db score/zoomoot_scores.db
```

## Backup Verification

### Test Backup Integrity

Always verify backups can be restored:

**Quick check**:
```bash
# Verify backup file exists and is not empty
ls -lh backups/zoomoot_scores_*.db

# Check SQLite database integrity
sqlite3 backups/zoomoot_scores_20240120.db "PRAGMA integrity_check;"
```

**Full verification**:
```bash
# Create test database from backup
cp backups/zoomoot_scores_20240120.db /tmp/test_restore.db

# Run integrity check
sqlite3 /tmp/test_restore.db "PRAGMA integrity_check;"

# Verify table counts
sqlite3 /tmp/test_restore.db "
  SELECT 'Teams: ' || COUNT(*) FROM team;
  SELECT 'Activities: ' || COUNT(*) FROM activity;
  SELECT 'Scores: ' || COUNT(*) FROM score;
"

# Clean up
rm /tmp/test_restore.db
```

### Automated Verification Script

**scripts/verify_backup.sh**:

```bash
#!/bin/bash

BACKUP_FILE="$1"

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 <backup_file>"
    exit 1
fi

# Check file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo "Error: Backup file not found: $BACKUP_FILE"
    exit 1
fi

# Uncompress if needed
TEMP_DB="/tmp/verify_backup_$$.db"
if [[ "$BACKUP_FILE" == *.gz ]]; then
    gunzip -c "$BACKUP_FILE" > "$TEMP_DB"
else
    cp "$BACKUP_FILE" "$TEMP_DB"
fi

# Run integrity check
RESULT=$(sqlite3 "$TEMP_DB" "PRAGMA integrity_check;")

if [ "$RESULT" = "ok" ]; then
    echo "✓ Backup integrity: OK"
    
    # Show statistics
    echo "Database statistics:"
    sqlite3 "$TEMP_DB" "
      SELECT '  Teams: ' || COUNT(*) FROM team;
      SELECT '  Activities: ' || COUNT(*) FROM activity;
      SELECT '  Scores: ' || COUNT(*) FROM score;
    "
    
    rm "$TEMP_DB"
    exit 0
else
    echo "✗ Backup integrity: FAILED"
    echo "$RESULT"
    rm "$TEMP_DB"
    exit 1
fi
```

**Usage**:
```bash
chmod +x scripts/verify_backup.sh
./scripts/verify_backup.sh backups/zoomoot_scores_20240120.db.gz
```

## Disaster Recovery

### Complete Data Loss Scenario

If the database is completely lost or corrupted:

**1. Stop the application**:
```bash
sudo systemctl stop apache2  # or nginx
```

**2. Find latest valid backup**:
```bash
# List all backups by date
ls -lt backups/

# Verify the latest backup
./scripts/verify_backup.sh backups/zoomoot_scores_YYYYMMDD_HHMMSS.db.gz
```

**3. Restore from backup**:
```bash
# Remove corrupted database
rm -f score/zoomoot_scores.db*

# Restore from backup
gunzip -c backups/zoomoot_scores_20240120_140000.db.gz > score/zoomoot_scores.db

# Set permissions
chmod 644 score/zoomoot_scores.db
chown www-data:www-data score/zoomoot_scores.db
```

**4. Verify restoration**:
```bash
sqlite3 score/zoomoot_scores.db "PRAGMA integrity_check;"
php test_database_integrity.php
```

**5. Restart application**:
```bash
sudo systemctl start apache2
```

**6. Test functionality**:
- Access the public page: http://yoursite.com/
- Login to admin interface
- Verify data is present
- Try creating a test score

### Corruption Recovery

If database is corrupted but not completely lost:

**Attempt 1: SQLite recovery**:
```bash
# Try to recover using dump/restore
sqlite3 score/zoomoot_scores.db ".recover" | sqlite3 score/zoomoot_scores_recovered.db

# Verify recovered database
sqlite3 score/zoomoot_scores_recovered.db "PRAGMA integrity_check;"

# If successful, replace
mv score/zoomoot_scores.db score/zoomoot_scores.db.corrupted
mv score/zoomoot_scores_recovered.db score/zoomoot_scores.db
```

**Attempt 2: Partial recovery**:
```bash
# Export what's salvageable
sqlite3 score/zoomoot_scores.db .dump > /tmp/partial_recovery.sql

# Create new database
rm score/zoomoot_scores.db
php html/api/init.php  # Initialize fresh schema

# Import salvaged data
sqlite3 score/zoomoot_scores.db < /tmp/partial_recovery.sql
```

**Attempt 3: Restore from backup**:
```bash
# If recovery fails, restore from most recent backup
cp backups/zoomoot_scores_latest.db score/zoomoot_scores.db
```

## Best Practices

### Backup Checklist

- [ ] **Regular schedule**: Automated backups run daily (or more frequently)
- [ ] **Multiple locations**: Backups stored in at least 2 different locations
- [ ] **Retention policy**: Keep backups for at least 30 days
- [ ] **Verification**: Test restore process monthly
- [ ] **Monitoring**: Backup script logs are reviewed regularly
- [ ] **Documentation**: Recovery procedures are documented and accessible
- [ ] **Testing**: Disaster recovery tested at least annually
- [ ] **Off-site**: Critical backups stored off-site or in cloud

### 3-2-1 Backup Rule

Follow the 3-2-1 rule:
- **3** copies of data (original + 2 backups)
- **2** different media types (local disk + cloud storage)
- **1** copy off-site (cloud or remote location)

### Backup Rotation

Implement a backup rotation strategy:

**Daily backups**: Keep for 7 days
**Weekly backups**: Keep for 4 weeks
**Monthly backups**: Keep for 12 months

**Example script**:
```bash
# Daily backup
cp score/zoomoot_scores.db backups/daily/zoomoot_$(date +%A).db

# Weekly backup (Sundays)
if [ $(date +%u) -eq 7 ]; then
    cp score/zoomoot_scores.db backups/weekly/zoomoot_week$(date +%V).db
fi

# Monthly backup (1st of month)
if [ $(date +%d) -eq 01 ]; then
    cp score/zoomoot_scores.db backups/monthly/zoomoot_$(date +%Y-%m).db
fi
```

### Monitoring Backup Health

**Create alert script** (scripts/check_backup_age.sh):

```bash
#!/bin/bash

BACKUP_DIR="/var/www/score.zoomoot.ca/backups"
MAX_AGE_HOURS=24

# Find newest backup
NEWEST=$(find "$BACKUP_DIR" -name "zoomoot_scores_*.db*" -type f -printf '%T@ %p\n' | sort -n | tail -1 | cut -d' ' -f2-)

if [ -z "$NEWEST" ]; then
    echo "ERROR: No backups found!"
    exit 1
fi

# Check age
AGE_SECONDS=$(( $(date +%s) - $(stat -c %Y "$NEWEST") ))
AGE_HOURS=$(( AGE_SECONDS / 3600 ))

if [ $AGE_HOURS -gt $MAX_AGE_HOURS ]; then
    echo "WARNING: Latest backup is $AGE_HOURS hours old (threshold: $MAX_AGE_HOURS hours)"
    echo "Latest backup: $NEWEST"
    exit 1
else
    echo "OK: Latest backup is $AGE_HOURS hours old"
    echo "Latest backup: $NEWEST"
    exit 0
fi
```

**Add to crontab for monitoring**:
```bash
# Check backup freshness every 6 hours
0 */6 * * * /var/www/score.zoomoot.ca/scripts/check_backup_age.sh || mail -s "Zoomoot Backup Alert" admin@yourdomain.com
```

## Related Documentation

- [Installation Guide](INSTALLATION.md) - Setup instructions
- [Configuration Guide](CONFIGURATION.md) - Environment configuration
- [Deployment Guide](DEPLOYMENT.md) - Production deployment
- [API Documentation](API_DOCUMENTATION.md) - API reference

## Support

For backup/restore issues:
1. Verify backup file integrity with `PRAGMA integrity_check`
2. Check file permissions after restore
3. Review backup script logs
4. Test restore in a safe environment first
5. Ensure sufficient disk space for backups
