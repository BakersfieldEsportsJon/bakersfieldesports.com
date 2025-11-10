-- Database Prerequisites Check Script
-- Run this before deploying security dashboard updates

-- Store results in temporary table
DROP TEMPORARY TABLE IF EXISTS prereq_results;
CREATE TEMPORARY TABLE prereq_results (
    check_name VARCHAR(100),
    status VARCHAR(50),
    details TEXT
);

-- Check Event Scheduler Status
INSERT INTO prereq_results
SELECT 
    'Event Scheduler Status',
    CASE 
        WHEN @@event_scheduler = 'ON' THEN 'OK'
        ELSE 'WARNING'
    END,
    CONCAT('Current status: ', @@event_scheduler, '. Should be ON');

-- Check for existing views that might conflict
INSERT INTO prereq_results
SELECT 
    'View Name Conflicts',
    CASE 
        WHEN COUNT(*) > 0 THEN 'WARNING'
        ELSE 'OK'
    END,
    CASE 
        WHEN COUNT(*) > 0 THEN CONCAT('Found ', COUNT(*), ' conflicting views: ', GROUP_CONCAT(table_name))
        ELSE 'No conflicts found'
    END
FROM information_schema.views
WHERE table_schema = DATABASE()
AND table_name IN ('v_session_stats', 'v_location_stats');

-- Check for existing events
INSERT INTO prereq_results
SELECT 
    'Event Name Conflicts',
    CASE 
        WHEN COUNT(*) > 0 THEN 'WARNING'
        ELSE 'OK'
    END,
    CASE 
        WHEN COUNT(*) > 0 THEN CONCAT('Found ', COUNT(*), ' existing events: ', GROUP_CONCAT(event_name))
        ELSE 'No conflicts found'
    END
FROM information_schema.events
WHERE event_schema = DATABASE()
AND event_name IN ('e_session_analysis', 'e_cleanup_events');

-- Check for existing procedures
INSERT INTO prereq_results
SELECT 
    'Stored Procedure Conflicts',
    CASE 
        WHEN COUNT(*) > 0 THEN 'WARNING'
        ELSE 'OK'
    END,
    CASE 
        WHEN COUNT(*) > 0 THEN CONCAT('Found ', COUNT(*), ' existing procedures: ', GROUP_CONCAT(routine_name))
        ELSE 'No conflicts found'
    END
FROM information_schema.routines
WHERE routine_schema = DATABASE()
AND routine_type = 'PROCEDURE'
AND routine_name IN ('sp_cleanup_old_events', 'sp_analyze_sessions');

-- Check active_sessions table structure
INSERT INTO prereq_results
SELECT 
    'Table Structure Check',
    CASE 
        WHEN COUNT(*) = 0 THEN 'ERROR'
        ELSE 'OK'
    END,
    CASE 
        WHEN COUNT(*) = 0 THEN 'active_sessions table not found'
        ELSE 'Table exists'
    END
FROM information_schema.tables
WHERE table_schema = DATABASE()
AND table_name = 'active_sessions';

-- Check database version
INSERT INTO prereq_results
SELECT 
    'MySQL Version Check',
    CASE 
        WHEN VERSION() >= '5.7' THEN 'OK'
        ELSE 'ERROR'
    END,
    CONCAT('Current version: ', VERSION(), '. Minimum required: 5.7');

-- Check JSON support
INSERT INTO prereq_results
SELECT 
    'JSON Support',
    CASE 
        WHEN COUNT(*) > 0 THEN 'OK'
        ELSE 'ERROR'
    END,
    'JSON data type support required for location tracking'
FROM information_schema.columns
WHERE table_schema = 'information_schema'
AND table_name = 'COLUMNS'
AND column_name = 'COLUMN_TYPE'
AND 'json' IN (
    SELECT DISTINCT column_type 
    FROM information_schema.columns 
    WHERE table_schema = 'information_schema'
);

-- Check available disk space (if possible)
INSERT INTO prereq_results
SELECT 
    'Database Size',
    'INFO',
    CONCAT(
        'Current database size: ',
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2),
        ' MB'
    )
FROM information_schema.tables
WHERE table_schema = DATABASE();

-- Display results
SELECT 
    check_name AS 'Check',
    status AS 'Status',
    details AS 'Details'
FROM prereq_results
ORDER BY 
    CASE status
        WHEN 'ERROR' THEN 1
        WHEN 'WARNING' THEN 2
        WHEN 'INFO' THEN 3
        WHEN 'OK' THEN 4
    END,
    check_name;

-- Summary
SELECT 
    CONCAT(
        'Summary: ',
        COUNT(CASE WHEN status = 'ERROR' THEN 1 END), ' errors, ',
        COUNT(CASE WHEN status = 'WARNING' THEN 1 END), ' warnings, ',
        COUNT(CASE WHEN status = 'OK' THEN 1 END), ' passed'
    ) AS 'Deployment Readiness'
FROM prereq_results;

-- Cleanup
DROP TEMPORARY TABLE prereq_results;
