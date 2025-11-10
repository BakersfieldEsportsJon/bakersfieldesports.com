-- Drop existing columns first to avoid conflicts
ALTER TABLE active_sessions
DROP COLUMN IF EXISTS session_id,
DROP COLUMN IF EXISTS activity_type,
DROP COLUMN IF EXISTS page_url,
DROP COLUMN IF EXISTS is_admin_session,
DROP COLUMN IF EXISTS location_data;

-- Add all required columns
ALTER TABLE active_sessions
ADD COLUMN session_id VARCHAR(255) AFTER id,
ADD COLUMN ip_address VARCHAR(45) NOT NULL AFTER username,
ADD COLUMN user_agent TEXT AFTER ip_address,
ADD COLUMN activity_type ENUM('login', 'logout', 'page_access', 'admin_action') AFTER user_agent,
ADD COLUMN page_url VARCHAR(255) AFTER activity_type,
ADD COLUMN is_admin_session BOOLEAN DEFAULT FALSE AFTER page_url,
ADD COLUMN location_data JSON AFTER is_admin_session;

-- Add indexes for performance
ALTER TABLE active_sessions
ADD UNIQUE INDEX idx_session_id (session_id),
ADD INDEX idx_session_activity (session_id, activity_type),
ADD INDEX idx_admin_session (is_admin_session);
