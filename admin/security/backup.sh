#!/bin/bash

# Security Dashboard Backup Script
# This script creates backups of all security-related files and database tables

# Set variables
BACKUP_DIR="security_backup_$(date +%Y%m%d_%H%M%S)"
DB_USER="your_user"
DB_NAME="your_database"
LOG_FILE="backup.log"

# Create backup directory
mkdir -p "$BACKUP_DIR"
echo "Created backup directory: $BACKUP_DIR" | tee -a "$LOG_FILE"

# Backup files
echo "Backing up files..." | tee -a "$LOG_FILE"
FILES=(
    "admin/security_dashboard.php"
    "admin/includes/session_stats.php"
    "includes/security/monitoring.php"
    "admin/dashboard.php"
    "admin/security/README.md"
    "admin/security/DEPLOYMENT.md"
    "admin/security/.htaccess"
    "admin/security/maintenance.html"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        dir=$(dirname "$BACKUP_DIR/$file")
        mkdir -p "$dir"
        cp "$file" "$BACKUP_DIR/$file"
        echo "Backed up: $file" | tee -a "$LOG_FILE"
    else
        echo "Warning: File not found: $file" | tee -a "$LOG_FILE"
    fi
done

# Backup database tables
echo "Backing up database..." | tee -a "$LOG_FILE"
TABLES=(
    "active_sessions"
    "security_events"
)

for table in "${TABLES[@]}"; do
    echo "Backing up table: $table" | tee -a "$LOG_FILE"
    mysqldump -u "$DB_USER" -p "$DB_NAME" "$table" > "$BACKUP_DIR/${table}_backup.sql" 2>> "$LOG_FILE"
done

# Create views backup
echo "Backing up database views..." | tee -a "$LOG_FILE"
mysqldump -u "$DB_USER" -p "$DB_NAME" --no-data --routines --triggers --events > "$BACKUP_DIR/schema_backup.sql" 2>> "$LOG_FILE"

# Backup security log if exists
if [ -f "includes/logs/security.log" ]; then
    mkdir -p "$BACKUP_DIR/includes/logs"
    cp "includes/logs/security.log" "$BACKUP_DIR/includes/logs/"
    echo "Backed up security log" | tee -a "$LOG_FILE"
fi

# Create archive
tar -czf "${BACKUP_DIR}.tar.gz" "$BACKUP_DIR"
echo "Created backup archive: ${BACKUP_DIR}.tar.gz" | tee -a "$LOG_FILE"

# Cleanup
rm -rf "$BACKUP_DIR"
echo "Backup completed successfully" | tee -a "$LOG_FILE"

# Instructions
echo "
Backup completed!
Archive created: ${BACKUP_DIR}.tar.gz

To restore:
1. Extract the archive:
   tar -xzf ${BACKUP_DIR}.tar.gz

2. Restore files:
   cp -r ${BACKUP_DIR}/* /path/to/your/site/

3. Restore database:
   mysql -u [user] -p [database] < ${BACKUP_DIR}/[table]_backup.sql

4. Verify restoration:
   - Check file permissions
   - Test database connectivity
   - Verify security dashboard access
" | tee -a "$LOG_FILE"
