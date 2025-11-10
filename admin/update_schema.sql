-- Remove 2FA columns from users table
ALTER TABLE users
DROP COLUMN two_factor_secret,
DROP COLUMN two_factor_recovery_codes,
DROP COLUMN two_factor_enabled;

-- Update users table indexes
ALTER TABLE users
DROP INDEX idx_users_failed_logins,
ADD INDEX idx_users_failed_logins (failed_login_attempts);

-- Add security-related columns to users table
ALTER TABLE users
ADD COLUMN role ENUM('user', 'moderator', 'admin') DEFAULT 'user' AFTER status,
ADD COLUMN failed_login_attempts INT DEFAULT 0 AFTER role,
ADD COLUMN last_failed_login DATETIME AFTER failed_login_attempts,
ADD COLUMN password_changed_at DATETIME AFTER last_failed_login;

-- Create roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- Create permissions table
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

-- Create role_permissions join table
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- Create user_roles join table
CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- Add indexes for security-related queries
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_failed_logins ON users(failed_login_attempts);
