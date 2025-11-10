<?php
/**
 * Migration: Create party_bookings table
 * Date: 2025-10-28
 *
 * This migration creates the party_bookings table to store all birthday party bookings
 */

require_once dirname(dirname(__DIR__)) . '/admin/includes/db.php';

try {
    $pdo->beginTransaction();

    if (DB_ENGINE === 'sqlite') {
        $sql = "CREATE TABLE IF NOT EXISTS party_bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            booking_reference VARCHAR(50) UNIQUE NOT NULL,
            stripe_session_id VARCHAR(255) UNIQUE,
            stripe_payment_intent_id VARCHAR(255),

            -- Customer Information
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            customer_phone VARCHAR(50) NOT NULL,

            -- Party Details
            party_for VARCHAR(255) NOT NULL,
            party_age INTEGER NOT NULL,
            party_date DATE NOT NULL,
            party_time TIME NOT NULL,
            guest_count INTEGER DEFAULT 0,

            -- Pizza Configuration
            base_pizza_choice VARCHAR(50) NOT NULL,
            cheese_pizzas INTEGER DEFAULT 0,
            pepperoni_pizzas INTEGER DEFAULT 0,
            pizza_ready_time TIME,

            -- Pricing
            deposit_amount DECIMAL(10,2) NOT NULL DEFAULT 100.00,
            processing_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_amount DECIMAL(10,2) NOT NULL,
            balance_due DECIMAL(10,2) DEFAULT 0.00,

            -- Status Tracking
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
            confirmation_sent_at DATETIME,
            confirmed_at DATETIME,
            reminder_sent_at DATETIME,
            completed_at DATETIME,
            cancelled_at DATETIME,
            cancellation_reason TEXT,

            -- Special Requests
            special_requests TEXT,
            admin_notes TEXT,

            -- Timestamps
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
    } else {
        // MySQL
        $sql = "CREATE TABLE IF NOT EXISTS party_bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_reference VARCHAR(50) UNIQUE NOT NULL,
            stripe_session_id VARCHAR(255) UNIQUE,
            stripe_payment_intent_id VARCHAR(255),

            -- Customer Information
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            customer_phone VARCHAR(50) NOT NULL,

            -- Party Details
            party_for VARCHAR(255) NOT NULL,
            party_age INT NOT NULL,
            party_date DATE NOT NULL,
            party_time TIME NOT NULL,
            guest_count INT DEFAULT 0,

            -- Pizza Configuration
            base_pizza_choice VARCHAR(50) NOT NULL,
            cheese_pizzas INT DEFAULT 0,
            pepperoni_pizzas INT DEFAULT 0,
            pizza_ready_time TIME,

            -- Pricing
            deposit_amount DECIMAL(10,2) NOT NULL DEFAULT 100.00,
            processing_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_amount DECIMAL(10,2) NOT NULL,
            balance_due DECIMAL(10,2) DEFAULT 0.00,

            -- Status Tracking
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
            confirmation_sent_at DATETIME,
            confirmed_at DATETIME,
            reminder_sent_at DATETIME,
            completed_at DATETIME,
            cancelled_at DATETIME,
            cancellation_reason TEXT,

            -- Special Requests
            special_requests TEXT,
            admin_notes TEXT,

            -- Timestamps
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_party_date (party_date),
            INDEX idx_status (status),
            INDEX idx_customer_email (customer_email),
            INDEX idx_booking_reference (booking_reference)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }

    $pdo->exec($sql);

    $pdo->commit();

    echo "✓ Migration successful: party_bookings table created\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
