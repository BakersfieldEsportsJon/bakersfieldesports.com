# On-Site Registration Implementation Plan

## Date: October 27, 2025

## Research Findings

### Start.gg API Registration Capabilities

✅ **Good News:** Start.gg GraphQL API **DOES** support registration via:
- `registerForTournament` mutation - Register for tournament
- `generateRegistrationToken` mutation - Generate tournament registration token on behalf of user

### Current Setup
- **API Token**: Found in `api/update-startgg-events.php` (line 19)
- **API Endpoint**: `https://api.start.gg/gql/alpha`
- **Existing Client**: `includes/startgg/StartGGClient.php`

### Important Considerations

⚠️ **Authentication Requirements:**
The `registerForTournament` mutation likely requires:
1. **User-specific authentication** (OAuth 2.0 flow) - Players must authorize your site to register on their behalf
2. **OR** Admin API token with elevated permissions
3. Registration token generated via `generateRegistrationToken`

## Proposed Registration Flow

### Option 1: User OAuth Flow (Recommended for Start.gg Integration)
```
1. User clicks "Register & Pay" on your site
2. User logs in with Start.gg account (OAuth)
3. User completes payment via Stripe on your site
4. Your backend calls registerForTournament with user's OAuth token
5. User is registered on Start.gg
6. Confirmation email sent
```

**Pros:**
- Official Start.gg registration
- Shows up in their Start.gg profile
- Integrated with Start.gg bracket system

**Cons:**
- Requires users to have/create Start.gg account
- More complex OAuth implementation
- Users must authorize your application

### Option 2: Hybrid Approach (Simpler, Less Integrated)
```
1. User fills out registration form on your site
2. User pays via Stripe
3. Registration stored in YOUR database
4. Admin manually adds players to Start.gg bracket
   OR use API with admin token if permissions allow
5. Confirmation email sent with payment receipt
```

**Pros:**
- Simpler to implement
- No OAuth complexity
- Works without Start.gg account
- You collect all payment fees

**Cons:**
- Manual step to add to Start.gg
- Players don't see registration in their Start.gg profile
- Requires admin work before tournament

### Option 3: Payment-Only Widget (Quickest)
```
1. Keep current Start.gg registration link
2. Add separate "Pay Entry Fee" button
3. Stripe Payment Link or custom form
4. Store payment records locally
5. Manually verify payment before tournament day
```

**Pros:**
- Fastest to implement (~4-6 hours)
- No OAuth complexity
- Start.gg handles registration

**Cons:**
- Two-step process for users
- Manual payment verification
- No automatic sync

## Recommended Approach

**Start with Option 2 (Hybrid)** because:
1. Faster implementation than full OAuth
2. Full control over payment processing
3. Can upgrade to OAuth later if needed
4. Works immediately without Start.gg API registration permissions

## Technical Implementation Plan

### 1. Database Schema

```sql
CREATE TABLE tournament_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_slug VARCHAR(255) NOT NULL,
    event_id INT,

    -- Player Info
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    gamer_tag VARCHAR(100) NOT NULL,
    phone VARCHAR(20),

    -- Payment Info
    payment_status ENUM('pending', 'completed', 'refunded', 'failed') DEFAULT 'pending',
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_id VARCHAR(255),
    stripe_payment_intent_id VARCHAR(255),
    stripe_charge_id VARCHAR(255),

    -- Start.gg Sync
    startgg_synced BOOLEAN DEFAULT FALSE,
    startgg_participant_id INT NULL,
    startgg_sync_error TEXT NULL,

    -- Timestamps
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_completed_at TIMESTAMP NULL,
    synced_at TIMESTAMP NULL,

    -- Additional
    ip_address VARCHAR(45),
    user_agent TEXT,
    notes TEXT,

    INDEX idx_tournament (tournament_slug),
    INDEX idx_email (email),
    INDEX idx_payment_status (payment_status),
    INDEX idx_startgg_synced (startgg_synced)
);
```

### 2. Stripe Integration

**Setup Steps:**
1. Create Stripe account at stripe.com
2. Get API keys (test & live)
3. Install Stripe PHP library: `composer require stripe/stripe-php`
4. Add to `.env`:
```
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
```

**Files to Create:**
- `api/create-payment-intent.php` - Initialize payment
- `api/confirm-payment.php` - Handle payment webhook
- `js/stripe-checkout.js` - Frontend Stripe integration

### 3. Registration Form UI

**Add to Modal** (`events/index.php` / `js/tournament-modal.js`):
```html
<form id="registration-form">
    <input type="text" name="full_name" required placeholder="Full Name">
    <input type="email" name="email" required placeholder="Email">
    <input type="text" name="gamer_tag" required placeholder="Gamer Tag">
    <input type="tel" name="phone" placeholder="Phone (optional)">

    <!-- Stripe Card Element -->
    <div id="card-element"></div>
    <div id="card-errors"></div>

    <div class="fee-summary">
        <strong>Entry Fee:</strong> $20.00
    </div>

    <button type="submit">Register & Pay $20</button>
</form>
```

### 4. Backend API Endpoints

**api/register-tournament.php:**
```php
POST /api/register-tournament.php
{
    "tournament_slug": "fortnite-battle-royale-pc-tournament-1",
    "event_id": 1428270,
    "full_name": "John Doe",
    "email": "john@example.com",
    "gamer_tag": "JohnD",
    "phone": "661-555-1234",
    "payment_method_id": "pm_xxx" // from Stripe
}

Response:
{
    "success": true,
    "registration_id": 123,
    "payment_intent": {...},
    "message": "Registration successful! Check your email for confirmation."
}
```

**api/sync-to-startgg.php:**
- Admin endpoint to manually sync registrations to Start.gg
- Uses `generateRegistrationToken` + `registerForTournament` if API allows
- OR provides CSV export for manual import

### 5. Email Confirmations

**Templates Needed:**
1. **Registration Confirmation** - Sent immediately after payment
2. **Payment Receipt** - With transaction details
3. **Tournament Reminder** - 24 hours before event
4. **Check-in Instructions** - Day of tournament

**Use PHPMailer or existing mail system**

## Implementation Timeline

### Phase 1: Core Registration (8-10 hours)
- [ ] Create database table
- [ ] Build registration form in modal
- [ ] Create backend API endpoint
- [ ] Store registrations in database
- [ ] Basic email confirmation

### Phase 2: Stripe Integration (6-8 hours)
- [ ] Set up Stripe account
- [ ] Install Stripe PHP library
- [ ] Implement payment intent creation
- [ ] Add Stripe checkout to form
- [ ] Handle payment webhooks
- [ ] Update payment status

### Phase 3: Start.gg Sync (4-6 hours)
- [ ] Test `registerForTournament` mutation with API token
- [ ] Build sync function (if API allows)
- [ ] OR build CSV export for manual import
- [ ] Add admin dashboard for managing registrations

### Phase 4: Polish & Testing (4-6 hours)
- [ ] Email templates
- [ ] Error handling
- [ ] Payment refund handling
- [ ] End-to-end testing
- [ ] Security audit

**Total Estimated Time: 22-30 hours**

## Next Steps - What You Need

### Stripe Setup (Required)
1. Go to stripe.com and create account
2. Complete business verification
3. Get Test API keys (start with test mode)
4. Provide me the keys to add to `.env`

### Database Access (Required)
- Confirm database credentials in `.env` file
- I'll create the registration table

### Start.gg API Permissions (Optional)
- Check if your API token has permission to register users
- May need to contact Start.gg support for elevated permissions
- Otherwise we'll use hybrid approach with manual sync

### Email Configuration (Required for confirmations)
- Verify SMTP settings in `.env` work
- Test email sending

## Questions to Answer

1. **Do you want to require users to have a Start.gg account?**
   - Yes = Implement OAuth flow
   - No = Use hybrid approach with manual sync

2. **Do you have a Stripe account already?**
   - Yes = Provide API keys
   - No = I'll guide you through setup

3. **Should refunds be allowed? If so, under what conditions?**
   - Before tournament starts?
   - With admin approval only?

4. **Do you want an admin dashboard to manage registrations?**
   - View all registrations
   - Process refunds
   - Sync to Start.gg
   - Export player lists

## Security Considerations

- ✅ SSL/HTTPS required (Stripe requirement)
- ✅ Stripe handles all card data (PCI compliant)
- ✅ Store minimal payment info (only IDs, not card numbers)
- ✅ Validate all form inputs
- ✅ Rate limiting on registration endpoint
- ✅ CSRF protection
- ✅ Email verification (optional but recommended)

Ready to proceed? Let me know which option you prefer and I'll start building!
