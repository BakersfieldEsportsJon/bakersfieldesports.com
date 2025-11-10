# On-Site Registration & Payment Guide

## Current Setup ✅
Your tournament modal now shows:
- ✅ **Correct entry fees** ($20 for Fortnite, $15 for others)
- ✅ **Correct bracket types** (Double Elimination, Swiss, etc.)
- ✅ **Custom descriptions** per tournament
- ✅ **Hidden player count** (privacy)
- ✅ Links to Start.gg for registration

## What You Need for On-Site Registration 🚧

To allow players to register and pay directly on your website (instead of Start.gg), you'll need:

### 1. Payment Processing
**Choose a payment gateway:**
- **Stripe** (recommended)
  - 2.9% + 30¢ per transaction
  - Easy integration
  - PCI compliant
- **PayPal**
  - Similar fees
  - Some users prefer it
- **Square**
  - Good for in-person + online

### 2. Database Tables
Create tables to store:
```sql
-- registrations table
CREATE TABLE tournament_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_slug VARCHAR(255),
    player_name VARCHAR(255),
    email VARCHAR(255),
    gamer_tag VARCHAR(255),
    phone VARCHAR(20),
    payment_status ENUM('pending', 'paid', 'refunded'),
    payment_amount DECIMAL(10,2),
    payment_id VARCHAR(255),
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    start_gg_synced BOOLEAN DEFAULT FALSE
);
```

### 3. Registration Form
Create a form in the modal with:
- Player name
- Email
- Gamer tag
- Phone number
- Payment info (Stripe/PayPal widget)

### 4. Backend Processing
**PHP scripts needed:**
- `register-player.php` - Handle registration submission
- `process-payment.php` - Process payment via Stripe/PayPal
- `sync-to-startgg.php` - Push registration to Start.gg API

### 5. Start.gg API Integration
You'll need to:
1. Get an API token with **write** permissions
2. Use the Start.gg GraphQL mutation to add entrants:
```graphql
mutation AddParticipant($eventId: ID!, $contactInfo: ContactInfoCreateInput!) {
  createParticipant(
    eventId: $eventId
    contactInfo: $contactInfo
  ) {
    id
    gamerTag
  }
}
```

### 6. Email Confirmations
Send automated emails:
- Registration confirmation
- Payment receipt
- Event reminders
- Check-in instructions

### 7. Security Requirements
- **SSL certificate** (HTTPS) - required for payment processing
- **PCI compliance** - let Stripe/PayPal handle this
- **Data encryption** for stored payment info
- **GDPR/Privacy policy** for collecting personal data

### 8. Admin Dashboard
Build an admin panel to:
- View all registrations
- Check payment status
- Refund players if needed
- Export registration list
- Sync with Start.gg

## Estimated Development Time
- **Basic setup**: 20-30 hours
- **With Stripe integration**: +10 hours
- **Admin dashboard**: +15 hours
- **Testing & debugging**: +10 hours
- **Total**: 55-65 hours of development

## Estimated Cost
If hiring a developer at $50-100/hour:
- **Budget**: $2,750 - $6,500

## Alternative: Hybrid Approach (Recommended)
**Keep Start.gg for registration but add a payment option:**
1. Players register on Start.gg (free)
2. Modal shows "Pay entry fee" button
3. Payment processed on your site via Stripe
4. You manually mark them as paid in Start.gg

**Benefits:**
- Less development time (~15 hours)
- Lower cost (~$750-1,500)
- Start.gg handles bracket management
- You collect entry fees directly

## Quick Win: Payment-Only Integration
Easiest approach:
1. Keep current modal linking to Start.gg
2. Add a **"Pay Entry Fee"** button below it
3. Use Stripe Payment Links (no coding needed!)
4. Manually verify payments before tournament

**Stripe Payment Links**: Create in 5 minutes
- No coding required
- Share link directly
- Track payments in Stripe dashboard
- Cost: 2.9% + 30¢ per transaction

## Next Steps
1. **Decide**: Full on-site registration vs. hybrid vs. payment-only
2. **Choose payment processor**: Stripe recommended
3. **Set up Stripe account**: stripe.com
4. **Test with small tournament**: Get feedback
5. **Scale up**: Add more features based on player feedback

## Current Config File
Edit tournament details in: `/tournament-config.php`

```php
'fortnite-battle-royale-pc-tournament-1' => [
    'entry_fee' => 20.00,
    'bracket_type' => 'Double Elimination',
    'description' => 'Your custom description here',
    'rules' => 'Your rules here',
],
```

## Questions?
Consider what matters most:
- **Control**: Full on-site = most control
- **Ease**: Hybrid = best balance
- **Quick**: Payment links = fastest to implement
