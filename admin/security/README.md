# Security Dashboard Setup Instructions

## Database Setup

The security monitoring system requires several database changes. Execute these SQL scripts in order:

1. Add session activity columns:
```sql
-- File: database/migrations/session_activity_columns.sql
-- This adds tracking capabilities to the active_sessions table
mysql -u your_user -p your_database < session_activity_columns.sql
```

2. Create security events system:
```sql
-- File: database/migrations/security_events.sql
-- This creates the events table, views, and stored procedures
mysql -u your_user -p your_database < security_events.sql
```

## Post-Installation Steps

1. Verify Database Changes:
```sql
-- Check if columns were added correctly
DESCRIBE active_sessions;

-- Verify views exist
SHOW FULL TABLES WHERE Table_type = 'VIEW';

-- Check if stored procedures were created
SHOW PROCEDURE STATUS WHERE Db = 'your_database';

-- Verify events are running
SHOW EVENTS;
```

2. Enable Event Scheduler (if not already enabled):
```sql
SET GLOBAL event_scheduler = ON;
```

3. Verify File Permissions:
- Ensure the logs directory is writable:
```bash
chmod 660 includes/logs/security.log
chown www-data:www-data includes/logs/security.log
```

## Testing the Installation

1. Access the security dashboard at:
```
/admin/security_dashboard.php
```

2. Verify that:
- Session statistics are being displayed
- Charts are updating with real data
- Security alerts are being logged
- Location tracking is functional

## Troubleshooting

If data isn't appearing:
1. Check the security log file:
```bash
tail -f includes/logs/security.log
```

2. Verify database connectivity:
```sql
SELECT COUNT(*) FROM active_sessions;
SELECT COUNT(*) FROM security_events;
```

3. Check stored procedure execution:
```sql
CALL sp_analyze_sessions();
SELECT * FROM security_events ORDER BY created_at DESC LIMIT 5;
```

## Maintenance

The system automatically:
- Cleans up old security events after 30 days
- Analyzes sessions every 5 minutes
- Updates dashboard data every minute

To manually clean up old data:
```sql
CALL sp_cleanup_old_events(30);
```

## Security Considerations

1. The system tracks:
- Session activities
- User locations
- Security events
- Admin actions

2. Sensitive data is stored:
- Session IDs are hashed
- Location data is JSON encoded
- Events are logged with severity levels

3. Regular maintenance:
- Monitor the security_events table growth
- Review and acknowledge security alerts
- Check session analysis results regularly
