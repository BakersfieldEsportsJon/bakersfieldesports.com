<?php
/**
 * Simple Migration Runner
 * Creates party_bookings table without Composer dependencies
 */

// Database configuration - adjust if needed
$host = 'localhost';
$dbname = getenv('DB_NAME') ?: 'bakerwgx_newdb';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Connected to database successfully.\n\n";

    // Check if table already exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'party_bookings'");
    if ($checkTable->rowCount() > 0) {
        echo "⚠ Table 'party_bookings' already exists. Skipping creation.\n";
        exit(0);
    }

    $pdo->beginTransaction();

    // MySQL table creation
    $sql = "CREATE TABLE party_bookings (
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

    $pdo->exec($sql);
    $pdo->commit();

    echo "✓ Migration successful: party_bookings table created\n\n";
    echo "Table structure:\n";
    echo "- Customer info: name, email, phone\n";
    echo "- Party details: date, time, age, guest count\n";
    echo "- Pizza options: base choice + additional pizzas\n";
    echo "- Status tracking: pending, confirmed, completed, cancelled\n";
    echo "- Automated reminders: reminder_sent_at timestamp\n";
    echo "- Admin management: notes, status updates\n\n";
    echo "Next steps:\n";
    echo "1. Configure Textla API credentials in .env file\n";
    echo "2. Set up cron job for automated reminders (see /cron/README.md)\n";
    echo "3. Access admin dashboard at /admin/party-bookings.php\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
