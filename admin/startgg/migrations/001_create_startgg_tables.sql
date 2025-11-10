-- Start.gg Integration Database Schema
-- Version: 1.0
-- Created: 2025-10-08

-- =====================================================
-- Table: startgg_config
-- Purpose: Store API configuration and settings
-- =====================================================
CREATE TABLE IF NOT EXISTS `startgg_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `api_token` VARCHAR(512) NOT NULL COMMENT 'Encrypted API token',
    `owner_id` VARCHAR(100) NOT NULL COMMENT 'Start.gg owner/user ID',
    `sync_interval` INT UNSIGNED DEFAULT 30 COMMENT 'Sync interval in minutes',
    `last_sync_at` DATETIME DEFAULT NULL COMMENT 'Last successful sync timestamp',
    `sync_enabled` TINYINT(1) DEFAULT 1 COMMENT 'Enable/disable auto-sync',
    `oauth_client_id` VARCHAR(255) DEFAULT NULL COMMENT 'OAuth application client ID',
    `oauth_client_secret` VARCHAR(512) DEFAULT NULL COMMENT 'Encrypted OAuth client secret',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_owner_id` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Start.gg API configuration';

-- =====================================================
-- Table: startgg_tournaments
-- Purpose: Store tournament data from start.gg
-- =====================================================
CREATE TABLE IF NOT EXISTS `startgg_tournaments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `startgg_id` BIGINT UNSIGNED NOT NULL UNIQUE COMMENT 'Start.gg tournament ID',
    `name` VARCHAR(255) NOT NULL COMMENT 'Tournament name',
    `slug` VARCHAR(255) NOT NULL UNIQUE COMMENT 'URL-friendly slug',
    `start_at` DATETIME NOT NULL COMMENT 'Tournament start date/time',
    `end_at` DATETIME DEFAULT NULL COMMENT 'Tournament end date/time',
    `timezone` VARCHAR(50) DEFAULT 'America/Los_Angeles' COMMENT 'Tournament timezone',
    `registration_closes_at` DATETIME DEFAULT NULL COMMENT 'Registration deadline',
    `is_registration_open` TINYINT(1) DEFAULT 0 COMMENT 'Registration open status',
    `is_online` TINYINT(1) DEFAULT 0 COMMENT 'Online vs in-person',
    `num_attendees` INT UNSIGNED DEFAULT 0 COMMENT 'Current number of attendees',
    `capacity` INT UNSIGNED DEFAULT NULL COMMENT 'Maximum capacity (if applicable)',

    -- Venue information
    `venue_name` VARCHAR(255) DEFAULT NULL COMMENT 'Venue name',
    `venue_address` TEXT DEFAULT NULL COMMENT 'Full venue address',
    `city` VARCHAR(100) DEFAULT NULL COMMENT 'City',
    `state` VARCHAR(50) DEFAULT NULL COMMENT 'State/province',
    `country` VARCHAR(50) DEFAULT 'US' COMMENT 'Country code',
    `latitude` DECIMAL(10, 8) DEFAULT NULL COMMENT 'GPS latitude',
    `longitude` DECIMAL(11, 8) DEFAULT NULL COMMENT 'GPS longitude',

    -- Tournament details
    `entry_fee` DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Entry fee amount',
    `currency` VARCHAR(3) DEFAULT 'USD' COMMENT 'Currency code',
    `rules` TEXT DEFAULT NULL COMMENT 'Tournament rules (markdown)',
    `description` TEXT DEFAULT NULL COMMENT 'Tournament description',

    -- Media
    `image_url` VARCHAR(512) DEFAULT NULL COMMENT 'Main tournament image',
    `banner_url` VARCHAR(512) DEFAULT NULL COMMENT 'Banner image',

    -- Links
    `startgg_url` VARCHAR(512) NOT NULL COMMENT 'Start.gg tournament page URL',
    `registration_url` VARCHAR(512) DEFAULT NULL COMMENT 'Direct registration URL',
    `discord_url` VARCHAR(512) DEFAULT NULL COMMENT 'Discord link',
    `facebook_url` VARCHAR(512) DEFAULT NULL COMMENT 'Facebook event link',

    -- Contact
    `contact_email` VARCHAR(255) DEFAULT NULL COMMENT 'Contact email',
    `contact_phone` VARCHAR(50) DEFAULT NULL COMMENT 'Contact phone',
    `contact_info` TEXT DEFAULT NULL COMMENT 'Additional contact information',

    -- Status and metadata
    `status` ENUM('draft', 'upcoming', 'in_progress', 'completed', 'cancelled') DEFAULT 'upcoming' COMMENT 'Tournament status',
    `is_public` TINYINT(1) DEFAULT 1 COMMENT 'Show on public site',
    `is_featured` TINYINT(1) DEFAULT 0 COMMENT 'Featured tournament',
    `last_synced_at` DATETIME DEFAULT NULL COMMENT 'Last sync from start.gg',
    `sync_error` TEXT DEFAULT NULL COMMENT 'Last sync error if any',

    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes for performance
    INDEX `idx_startgg_id` (`startgg_id`),
    INDEX `idx_slug` (`slug`),
    INDEX `idx_start_at` (`start_at`),
    INDEX `idx_status` (`status`),
    INDEX `idx_is_public` (`is_public`),
    INDEX `idx_is_featured` (`is_featured`),
    INDEX `idx_registration_open` (`is_registration_open`),
    INDEX `idx_city_state` (`city`, `state`),
    FULLTEXT INDEX `idx_fulltext_search` (`name`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Start.gg tournaments';

-- =====================================================
-- Table: startgg_events
-- Purpose: Store events within tournaments
-- =====================================================
CREATE TABLE IF NOT EXISTS `startgg_events` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tournament_id` INT UNSIGNED NOT NULL COMMENT 'FK to startgg_tournaments',
    `startgg_id` BIGINT UNSIGNED NOT NULL UNIQUE COMMENT 'Start.gg event ID',
    `name` VARCHAR(255) NOT NULL COMMENT 'Event name',
    `slug` VARCHAR(255) NOT NULL COMMENT 'URL-friendly slug',

    -- Game information
    `videogame_id` INT UNSIGNED DEFAULT NULL COMMENT 'Start.gg videogame ID',
    `videogame_name` VARCHAR(255) DEFAULT NULL COMMENT 'Game name',
    `videogame_display_name` VARCHAR(255) DEFAULT NULL COMMENT 'Display name for game',

    -- Event details
    `type` ENUM('singles', 'doubles', 'teams', 'other') DEFAULT 'singles' COMMENT 'Competition type',
    `num_entrants` INT UNSIGNED DEFAULT 0 COMMENT 'Current entrant count',
    `entrant_cap` INT UNSIGNED DEFAULT NULL COMMENT 'Maximum entrants',
    `start_at` DATETIME DEFAULT NULL COMMENT 'Event start time',
    `check_in_enabled` TINYINT(1) DEFAULT 0 COMMENT 'Check-in required',
    `check_in_buffer` INT UNSIGNED DEFAULT 0 COMMENT 'Check-in buffer minutes',

    -- Bracket information
    `bracket_type` VARCHAR(50) DEFAULT NULL COMMENT 'Single elimination, double elimination, etc',
    `has_bracket` TINYINT(1) DEFAULT 0 COMMENT 'Bracket generated',

    -- Status
    `state` ENUM('created', 'active', 'completed', 'cancelled') DEFAULT 'created' COMMENT 'Event state',
    `is_online` TINYINT(1) DEFAULT 0 COMMENT 'Online event',

    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    FOREIGN KEY (`tournament_id`) REFERENCES `startgg_tournaments`(`id`) ON DELETE CASCADE,
    INDEX `idx_tournament_id` (`tournament_id`),
    INDEX `idx_startgg_id` (`startgg_id`),
    INDEX `idx_slug` (`slug`),
    INDEX `idx_videogame_name` (`videogame_name`),
    INDEX `idx_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Events within tournaments';

-- =====================================================
-- Table: startgg_entrants
-- Purpose: Store entrant information for events
-- =====================================================
CREATE TABLE IF NOT EXISTS `startgg_entrants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT UNSIGNED NOT NULL COMMENT 'FK to startgg_events',
    `startgg_id` BIGINT UNSIGNED NOT NULL COMMENT 'Start.gg entrant ID',
    `participant_name` VARCHAR(255) NOT NULL COMMENT 'Participant/team name',
    `gamer_tag` VARCHAR(255) DEFAULT NULL COMMENT 'Player gamer tag',
    `prefix` VARCHAR(100) DEFAULT NULL COMMENT 'Team/sponsor prefix',
    `seed` INT UNSIGNED DEFAULT NULL COMMENT 'Tournament seed',
    `placement` INT UNSIGNED DEFAULT NULL COMMENT 'Final placement',
    `is_disqualified` TINYINT(1) DEFAULT 0 COMMENT 'DQ status',
    `checked_in` TINYINT(1) DEFAULT 0 COMMENT 'Check-in status',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`event_id`) REFERENCES `startgg_events`(`id`) ON DELETE CASCADE,
    INDEX `idx_event_id` (`event_id`),
    INDEX `idx_startgg_id` (`startgg_id`),
    UNIQUE KEY `unique_entrant_event` (`event_id`, `startgg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tournament entrants';

-- =====================================================
-- Table: startgg_sync_log
-- Purpose: Track sync operations and errors
-- =====================================================
CREATE TABLE IF NOT EXISTS `startgg_sync_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sync_type` ENUM('full', 'tournament', 'event', 'entrants') DEFAULT 'full' COMMENT 'Type of sync',
    `status` ENUM('started', 'success', 'failed', 'partial') DEFAULT 'started' COMMENT 'Sync status',
    `tournaments_synced` INT UNSIGNED DEFAULT 0 COMMENT 'Number of tournaments synced',
    `events_synced` INT UNSIGNED DEFAULT 0 COMMENT 'Number of events synced',
    `entrants_synced` INT UNSIGNED DEFAULT 0 COMMENT 'Number of entrants synced',
    `error_message` TEXT DEFAULT NULL COMMENT 'Error details if failed',
    `api_calls_made` INT UNSIGNED DEFAULT 0 COMMENT 'Number of API calls',
    `duration_seconds` DECIMAL(10, 2) DEFAULT NULL COMMENT 'Sync duration',
    `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Sync start time',
    `completed_at` DATETIME DEFAULT NULL COMMENT 'Sync completion time',

    INDEX `idx_status` (`status`),
    INDEX `idx_sync_type` (`sync_type`),
    INDEX `idx_started_at` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sync operation logs';

-- =====================================================
-- Insert default configuration
-- =====================================================
-- Note: API token will be inserted encrypted via PHP
INSERT INTO `startgg_config` (`owner_id`, `sync_interval`, `sync_enabled`)
VALUES ('6e4bd725', 30, 1)
ON DUPLICATE KEY UPDATE `owner_id` = '6e4bd725';

-- =====================================================
-- Done!
-- =====================================================
