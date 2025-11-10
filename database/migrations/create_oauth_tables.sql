-- OAuth and Tournament Registration Tables
-- Created: 2025-10-27
-- Purpose: Support Start.gg OAuth login and tournament registration with payment

-- ============================================
-- Table: oauth_users
-- Stores Start.gg user OAuth tokens and info
-- ============================================

CREATE TABLE IF NOT EXISTS oauth_users (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Start.gg User Info
    startgg_user_id INT UNIQUE NOT NULL,
    startgg_slug VARCHAR(255),
    startgg_name VARCHAR(255),
    email VARCHAR(255),
    gamer_tag VARCHAR(100),

    -- OAuth Tokens
    access_token TEXT NOT NULL,
    refresh_token TEXT,
    token_expires_at TIMESTAMP NULL,
    token_type VARCHAR(50) DEFAULT 'Bearer',

    -- OAuth App Source (local or production)
    oauth_environment ENUM('local', 'production') DEFAULT 'local',

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL,

    -- Indexes
    INDEX idx_user_id (startgg_user_id),
    INDEX idx_email (email),
    INDEX idx_slug (startgg_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: tournament_registrations
-- Stores tournament registrations with payment info
-- ============================================

CREATE TABLE IF NOT EXISTS tournament_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- OAuth User Reference
    oauth_user_id INT NOT NULL,
    startgg_user_id INT NOT NULL,

    -- Tournament Info
    tournament_slug VARCHAR(255) NOT NULL,
    tournament_name VARCHAR(255),
    event_id INT NOT NULL,
    event_name VARCHAR(255),
    event_slug VARCHAR(500),

    -- Player Info (cached from Start.gg OAuth)
    gamer_tag VARCHAR(100),
    full_name VARCHAR(255),
    email VARCHAR(255) NOT NULL,

    -- Payment Info
    payment_status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_currency VARCHAR(3) DEFAULT 'USD',

    -- Stripe Payment Details
    stripe_payment_intent_id VARCHAR(255) UNIQUE,
    stripe_charge_id VARCHAR(255),
    stripe_customer_id VARCHAR(255),
    payment_method_last4 VARCHAR(4),
    payment_method_brand VARCHAR(50),
    payment_completed_at TIMESTAMP NULL,

    -- Refund Info
    refund_amount DECIMAL(10,2) NULL,
    refund_reason TEXT NULL,
    refunded_at TIMESTAMP NULL,
    refunded_by VARCHAR(255) NULL,

    -- Start.gg Registration Status
    startgg_registered BOOLEAN DEFAULT FALSE,
    startgg_participant_id INT NULL,
    startgg_registration_error TEXT NULL,
    startgg_registered_at TIMESTAMP NULL,
    startgg_registration_attempts INT DEFAULT 0,

    -- Metadata
    ip_address VARCHAR(45),
    user_agent TEXT,
    registration_source VARCHAR(50) DEFAULT 'website',
    notes TEXT,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (oauth_user_id) REFERENCES oauth_users(id) ON DELETE CASCADE,

    -- Indexes
    INDEX idx_tournament (tournament_slug),
    INDEX idx_event (event_id),
    INDEX idx_user (startgg_user_id),
    INDEX idx_oauth_user (oauth_user_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_stripe_intent (stripe_payment_intent_id),
    INDEX idx_startgg_registered (startgg_registered),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: oauth_sessions
-- Stores OAuth state tokens for security
-- ============================================

CREATE TABLE IF NOT EXISTS oauth_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Session Info
    state_token VARCHAR(255) UNIQUE NOT NULL,
    tournament_slug VARCHAR(255),
    event_id INT,

    -- Security
    ip_address VARCHAR(45),
    user_agent TEXT,

    -- Expiry
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    used_at TIMESTAMP NULL,

    -- Indexes
    INDEX idx_state (state_token),
    INDEX idx_expires (expires_at),
    INDEX idx_used (used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: registration_emails
-- Log all emails sent for registrations
-- ============================================

CREATE TABLE IF NOT EXISTS registration_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,

    registration_id INT NOT NULL,
    email_type ENUM('confirmation', 'receipt', 'reminder', 'refund') NOT NULL,

    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500),

    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_successfully BOOLEAN DEFAULT TRUE,
    error_message TEXT NULL,

    FOREIGN KEY (registration_id) REFERENCES tournament_registrations(id) ON DELETE CASCADE,
    INDEX idx_registration (registration_id),
    INDEX idx_type (email_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Initial Data / Comments
-- ============================================

-- Add comments to tables
ALTER TABLE oauth_users COMMENT = 'Stores Start.gg user OAuth tokens and profile information';
ALTER TABLE tournament_registrations COMMENT = 'Tournament registrations with payment and Start.gg sync status';
ALTER TABLE oauth_sessions COMMENT = 'OAuth state tokens for CSRF protection';
ALTER TABLE registration_emails COMMENT = 'Email sending log for registration confirmations';
