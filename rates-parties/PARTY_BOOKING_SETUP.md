# Party Booking System - Setup Guide

## Overview
The enhanced party booking system includes:
- **Party Flow Selection**: Customers choose whether they want pizza at the start or after 2 hours of gaming
- **Automatic Pizza Time Calculation**: System auto-calculates pizza ready time based on party flow
- **Zapier/Textla SMS Integration**: Automatic SMS notifications via Zapier webhook
- **Discord Integration**: Automatic booking notifications posted to Discord channel

## Database Migration

Run the database migration to add the `party_flow` field:

```bash
php public_html/database/migrations/20251029_add_party_flow_field.php
```

## Environment Variables

Add the following environment variables to your `.env` file:

### Required for Zapier/Textla SMS Integration
```env
ZAPIER_WEBHOOK_URL=https://hooks.zapier.com/hooks/catch/YOUR_WEBHOOK_ID/
```

### Required for Discord Integration
```env
DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/YOUR_WEBHOOK_ID/YOUR_WEBHOOK_TOKEN
```

## Setting Up Zapier for Textla SMS

1. **Create a Zapier Account** at https://zapier.com
2. **Create a New Zap**:
   - Trigger: **Webhooks by Zapier** → **Catch Hook**
   - Copy the webhook URL and add it to your `.env` as `ZAPIER_WEBHOOK_URL`
3. **Add Textla Action**:
   - Action: **Textla** → **Send SMS**
   - Configure the action to send SMS using the booking data
   - Map the fields:
     - Phone Number: `customer_phone`
     - Message: Customize with booking details (booking_reference, party_for, party_datetime, etc.)

### Example Textla SMS Message Template
```
Hi {{customer_name}}! Your party booking at Bakersfield eSports is confirmed!

📋 Booking Ref: {{booking_reference}}
🎂 For: {{party_for}} (Age {{party_age}})
📅 Date: {{party_datetime}}
🍕 Pizza Time: {{pizza_ready_time}}

We'll contact you within 24 hours to confirm details. See you soon!
```

4. **Test the Zap** and activate it

## Setting Up Discord Webhook

1. **Open your Discord server**
2. **Go to Server Settings** → **Integrations** → **Webhooks**
3. **Create a New Webhook**:
   - Name: "Party Bookings"
   - Select the channel where you want bookings posted
   - Copy the webhook URL
4. **Add to .env**:
   ```env
   DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/YOUR_WEBHOOK_ID/YOUR_WEBHOOK_TOKEN
   ```

## Party Flow Options

The system provides two party flow options:

### 1. Party Area First (`party_first`)
- Pizza is served at the party start time
- Then 2 hours of game time begins
- Good for: Younger kids, parents who want food first

### 2. Game Time First (`games_first`)
- 2 hours of game time starts immediately
- Pizza is served 2 hours after start time
- Good for: Older kids, maximum gaming time

## Booking Data Sent to Webhooks

Both Zapier and Discord webhooks receive the following data:

```json
{
  "booking_reference": "BK-10292025-ABC123",
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "customer_phone": "+15551234567",
  "party_for": "Sarah",
  "party_age": "12",
  "party_date": "2025-11-15",
  "party_time": "14:00",
  "party_datetime": "Friday, November 15, 2025 at 2:00 PM",
  "party_flow": "party_first",
  "pizza_ready_time": "14:00",
  "pizza_details": "2x Cheese\n+ 1x Pepperoni",
  "deposit_amount": 100.00,
  "total_amount": 123.50,
  "stripe_session_id": "cs_test_...",
  "created_at": "2025-10-29 12:34:56"
}
```

## Testing

### Test the Booking Flow
1. Go to https://bakersfieldesports.com/rates-parties/
2. Click "Book Now"
3. Fill out the form with test data
4. Select a party flow option
5. Verify that the pizza ready time auto-updates
6. Complete the booking (use Stripe test mode)

### Verify Webhooks
1. Check your Discord channel for the booking notification
2. Check Zapier task history to verify the webhook was received
3. Verify SMS was sent via Textla (if configured)

## Troubleshooting

### Webhooks Not Working
- Check error logs: `public_html/rates-parties/error_log`
- Verify webhook URLs are correct in `.env`
- Test webhooks directly using curl:

```bash
# Test Discord
curl -X POST "YOUR_DISCORD_WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -d '{"content": "Test message"}'

# Test Zapier
curl -X POST "YOUR_ZAPIER_WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```

### Pizza Time Not Calculating
- Verify both party time and party flow are selected
- Check browser console for JavaScript errors
- Ensure party-booking.js is loaded

## Files Modified/Created

### New Files
- `database/migrations/20251029_add_party_flow_field.php` - Database migration
- `PARTY_BOOKING_SETUP.md` - This setup guide

### Modified Files
- `rates-parties/index.php` - Added party flow selector and important notice
- `rates-parties/process_booking.php` - Added party_flow to required fields and metadata
- `rates-parties/webhook.php` - Added party_flow to database insert, added Zapier/Discord webhooks
- `js/party-booking.js` - Added auto-calculation of pizza ready time
- `css/styles.css` - Added styling for important notice and form help text

## Support

For issues or questions, check the error logs or contact the development team.
