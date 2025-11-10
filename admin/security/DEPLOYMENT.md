# Security Dashboard Deployment Checklist

## Pre-Deployment Backup
1. Database Backup:
```sql
mysqldump -u your_user -p your_database active_sessions > active_sessions_backup.sql
```

2. Files to Backup:
- admin/security_dashboard.php
- admin/includes/session_stats.php
- includes/security/monitoring.php
- includes/logs/security.log (if exists)

## Files to Transfer
1. New Files:
- admin/security_dashboard.php
- admin/includes/session_stats.php
- admin/security/README.md
- database/migrations/session_activity_columns.sql
- database/migrations/security_events.sql

2. Modified Files:
- admin/dashboard.php (added Security Monitoring section)
- includes/security/monitoring.php (enhanced monitoring capabilities)
- includes/security/session_manager.php (added monitoring integration)

## Database Updates
Execute SQL scripts in this order:

1. Add Session Activity Columns:
```sql
mysql -u your_user -p your_database < database/migrations/session_activity_columns.sql
```

2. Create Security Events System:
```sql
mysql -u your_user -p your_database < database/migrations/security_events.sql
```

3. Enable Event Scheduler:
```sql
SET GLOBAL event_scheduler = ON;
```

4. Verify Installation:
```sql
-- Check new columns
DESCRIBE active_sessions;

-- Verify views
SHOW FULL TABLES WHERE Table_type = 'VIEW';

-- Check stored procedures
SHOW PROCEDURE STATUS WHERE Db = 'your_database';

-- Verify events
SHOW EVENTS;
```

## File Permissions
1. Create and Set Log Directory:
```bash
mkdir -p includes/logs
touch includes/logs/security.log
chmod 660 includes/logs/security.log
chown www-data:www-data includes/logs/security.log
```

2. Secure Admin Directory:
```bash
chmod 750 admin/security
chown www-data:www-data admin/security
```

## Post-Deployment Verification

1. Access Tests:
- [ ] Verify admin dashboard shows Security Monitoring section
- [ ] Access security dashboard at /admin/security_dashboard.php
- [ ] Confirm proper authentication required

2. Functionality Tests:
- [ ] Check session statistics are displaying
- [ ] Verify charts are updating with real data
- [ ] Test security alerts are being logged
- [ ] Confirm location tracking works

3. Database Tests:
- [ ] Test session activity tracking
- [ ] Verify security events are being logged
- [ ] Check stored procedures execution
- [ ] Confirm event scheduler is running

4. Security Tests:
- [ ] Verify unauthorized access is blocked
- [ ] Test session validation
- [ ] Check location-based restrictions
- [ ] Confirm alert thresholds work

## Rollback Plan

If deployment fails:

1. Restore Database:
```sql
mysql -u your_user -p your_database < active_sessions_backup.sql
```

2. Restore Original Files:
- Revert admin/dashboard.php changes
- Remove new security dashboard files
- Restore original monitoring.php
- Remove security log file

3. Verify System State:
- Check session handling works
- Verify admin dashboard accessible
- Confirm no security alerts pending

## Maintenance Notes

1. Regular Tasks:
- Monitor security_events table growth
- Review and acknowledge alerts
- Check session analysis results
- Verify log file size

2. Performance Monitoring:
- Watch database query performance
- Monitor event scheduler impact
- Check log file I/O
- Review session cleanup efficiency
