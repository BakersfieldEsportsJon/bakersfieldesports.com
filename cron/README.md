# Party Booking Automated Reminders

## Overview
This directory contains cron scripts for automated party booking reminders.

## Scripts

### send-party-reminders.php
Sends automated reminders 48 hours before party date via SMS (Textla) and Email.

**Features:**
- Finds all confirmed/pending bookings 2 days away
- Sends SMS reminder via Textla
- Sends detailed email reminder
- Marks booking as reminder sent
- Logs all activity

## Setup Instructions

### 1. Configure Textla API
Set the following environment variables in your `.env` file or server environment:

```
TEXTLA_API_KEY=your_textla_api_key
TEXTLA_PHONE_NUMBER=your_textla_phone_number
```

### 2. Set Up Cron Job

#### Linux/Unix (cPanel, SSH)
Edit your crontab:
```bash
crontab -e
```

Add the following line to run daily at 9:00 AM:
```
0 9 * * * /usr/bin/php /path/to/public_html/cron/send-party-reminders.php >> /path/to/logs/party-reminders.log 2>&1
```

#### Windows (Task Scheduler)
1. Open Task Scheduler
2. Create Basic Task
3. Set Trigger: Daily at 9:00 AM
4. Action: Start a Program
   - Program: `C:\xampp\php\php.exe` (or your PHP path)
   - Arguments: `C:\path\to\public_html\cron\send-party-reminders.php`

#### cPanel Cron Jobs
1. Log into cPanel
2. Navigate to "Cron Jobs"
3. Set schedule: `0 9 * * *`
4. Command: `/usr/bin/php /home/username/public_html/cron/send-party-reminders.php`

### 3. Test the Script
Run manually to test:
```bash
php /path/to/public_html/cron/send-party-reminders.php
```

## Monitoring

### Check Logs
Monitor the script execution:
```bash
tail -f /path/to/logs/party-reminders.log
```

### Verify Reminders Sent
Check the database:
```sql
SELECT booking_reference, customer_name, party_date, reminder_sent_at
FROM party_bookings
WHERE reminder_sent_at IS NOT NULL
ORDER BY reminder_sent_at DESC;
```

## Troubleshooting

### No Reminders Being Sent
- Check Textla API credentials in environment variables
- Verify cron job is running: `grep CRON /var/log/syslog`
- Check PHP error logs
- Verify database connection

### SMS Not Sending
- Verify Textla API key and phone number
- Check Textla account balance/status
- Review error logs for API responses

### Email Not Sending
- Verify server mail configuration
- Check PHP mail() function is enabled
- Review spam folders

## Configuration

### Change Reminder Timing
To send reminders at a different time before the party, edit the SQL query in `send-party-reminders.php`:

```php
// For 24 hours before:
AND DATE(party_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)

// For 72 hours before:
AND DATE(party_date) = DATE_ADD(CURDATE(), INTERVAL 3 DAY)
```

### Add Multiple Reminder Times
Create multiple cron jobs with different scripts for different reminder times (e.g., 7 days before, 48 hours before, 24 hours before).
