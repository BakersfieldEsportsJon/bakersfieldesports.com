# Security Dashboard Deployment Checklist

## Pre-Deployment Verification

1. Run Prerequisites Check:
```sql
mysql -u your_user -p your_database < admin/security/check_prerequisites.sql
```
- Verify no errors in results
- Address any warnings
- Note database size for capacity planning

2. Create Backup:
```bash
chmod +x admin/security/backup.sh
./admin/security/backup.sh
```
- Verify backup archive created
- Test backup restoration in development environment

3. Verify File List:
- [ ] admin/security_dashboard.php
- [ ] admin/includes/session_stats.php
- [ ] admin/security/maintenance.html
- [ ] admin/security/.htaccess
- [ ] admin/security/assets/js/chart.min.js
- [ ] database/migrations/session_activity_columns.sql
- [ ] database/migrations/security_events.sql

## Deployment Steps

1. Enable Maintenance Mode:
```bash
# Uncomment maintenance mode in .htaccess
sed -i 's/#ErrorDocument/ErrorDocument/' admin/security/.htaccess
sed -i 's/#RewriteEngine/RewriteEngine/' admin/security/.htaccess
sed -i 's/#RewriteCond/RewriteCond/' admin/security/.htaccess
sed -i 's/#RewriteRule/RewriteRule/' admin/security/.htaccess
```

2. Execute Database Updates:
```sql
-- Run migrations in order
mysql -u your_user -p your_database < database/migrations/session_activity_columns.sql
mysql -u your_user -p your_database < database/migrations/security_events.sql

-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- Generate test data
mysql -u your_user -p your_database < admin/security/generate_test_data.sql
```

3. Set File Permissions:
```bash
# Create and secure log directory
mkdir -p includes/logs
touch includes/logs/security.log
chmod 660 includes/logs/security.log
chown www-data:www-data includes/logs/security.log

# Secure admin directory
chmod 750 admin/security
chown www-data:www-data admin/security
```

## Post-Deployment Verification

1. Database Checks:
- [ ] Verify active_sessions table structure
- [ ] Check security_events table
- [ ] Confirm views created
- [ ] Test stored procedures
- [ ] Verify event scheduler running

2. Security Checks:
- [ ] Test admin authentication
- [ ] Verify .htaccess restrictions
- [ ] Check file permissions
- [ ] Validate SSL/TLS

3. Functionality Tests:
- [ ] Access security dashboard
- [ ] Verify charts display
- [ ] Check real-time updates
- [ ] Test alert system
- [ ] Validate session tracking

4. Performance Checks:
- [ ] Monitor query execution times
- [ ] Check event scheduler impact
- [ ] Verify log file growth
- [ ] Test dashboard refresh rate

## Rollback Procedure

If deployment fails:

1. Restore Database:
```sql
mysql -u your_user -p your_database < backup/active_sessions_backup.sql
```

2. Restore Files:
```bash
# Extract backup
tar -xzf security_backup_*.tar.gz

# Restore files
cp -r security_backup_*/* /path/to/site/
```

3. Disable Maintenance Mode:
```bash
# Comment out maintenance mode in .htaccess
sed -i 's/^ErrorDocument/#ErrorDocument/' admin/security/.htaccess
sed -i 's/^RewriteEngine/#RewriteEngine/' admin/security/.htaccess
sed -i 's/^RewriteCond/#RewriteCond/' admin/security/.htaccess
sed -i 's/^RewriteRule/#RewriteRule/' admin/security/.htaccess
```

## Final Steps

1. Remove Test Data:
```sql
-- Clean up test data after verification
DELETE FROM security_events WHERE event_type LIKE 'TEST_%';
DELETE FROM active_sessions WHERE username LIKE 'test_user%';
```

2. Update Documentation:
- [ ] Record deployment date/time
- [ ] Note any issues encountered
- [ ] Document configuration changes
- [ ] Update maintenance procedures

3. Security Review:
- [ ] Review error logs
- [ ] Check access logs
- [ ] Verify backup integrity
- [ ] Test monitoring alerts
