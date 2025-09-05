# Stripe Purchase Integration

A runtime-only payment tracking system using Stripe Payment Intents and webhooks.

## ⚠️ **Important Limitations**

This implementation uses **runtime-only tracking** without database persistence. This approach has significant limitations:

- **Data loss on server restart** - All payment tracking is lost
- **No multi-server support** - Won't work with load balancers
- **Webhook timing issues** - Webhooks may arrive after runtime data is lost
- **No payment history** - Cannot track past transactions

## Files Created

### Frontend
- `html/stripe-purchase.php` - Purchase page with product selection and Stripe Elements

### API Endpoints
- `html/api/v2/payments/create-payment-intent.php` - Creates Payment Intent and stores in runtime
- `html/api/v2/payments/check-status.php` - Checks payment status from runtime tracking
- `html/api/v2/payments/webhook.php` - Handles Stripe webhooks and updates runtime tracking

## Setup Instructions

### 1. Configure Stripe Credentials
Add to your `credentials.ini`:
```ini
stripe_publishable_key=pk_test_...
stripe_secret_key=sk_test_...
stripe_webhook_secret=whsec_...
```

### 2. Configure Webhook Endpoint
In your Stripe Dashboard:
- **Endpoint URL**: `https://yourdomain.com/api/v2/payments/webhook.php`
- **Events to send**:
  - `payment_intent.succeeded`
  - `payment_intent.payment_failed`
  - `payment_intent.canceled`

### 3. Test the Flow
1. Navigate to `/stripe-purchase.php`
2. Select a product
3. Enter test card: `4242 4242 4242 4242`
4. Complete payment
5. Watch for webhook confirmation

## Test Cards
- **Success**: `4242 4242 4242 4242`
- **Decline**: `4000 0000 0000 0002`
- **3D Secure**: `4000 0025 0000 3155`

## Better Approach Recommendation

For production use, consider:
1. **Redis cache** with TTL for temporary tracking
2. **Minimal database table** for webhook persistence
3. **Queue system** for reliable webhook processing

This would provide reliability while maintaining simplicity.