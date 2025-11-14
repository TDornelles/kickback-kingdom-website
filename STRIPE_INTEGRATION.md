# Simple Stripe Integration

This is the simplest possible Stripe implementation for the Kickback Kingdom store.

## Overview

This implementation uses **Stripe Checkout Sessions** (not Payment Intents) to provide a hosted checkout experience. This is the simplest approach because:

1. Stripe hosts the entire checkout UI
2. No need to build custom payment forms
3. PCI compliance is handled by Stripe
4. Minimal code required

## Files Created/Modified

### New Files
- `/html/php-components/Stripe/create-checkout-session.php` - Creates Stripe Checkout Session
- `/html/checkout-success.php` - Success page after payment

### Modified Files
- `/html/checkout.php` - Updated to integrate with Stripe Checkout

## How It Works

1. **User adds items to cart** on `/market.php`
2. **User goes to checkout** at `/checkout.php`
   - Cart items and totals are loaded
   - "Proceed to Payment" button is enabled
3. **User clicks "Proceed to Payment"**
   - JavaScript calls `/php-components/Stripe/create-checkout-session.php`
   - Backend creates a Stripe Checkout Session with cart total
   - Returns session ID to frontend
4. **Stripe.js redirects to hosted checkout**
   - User enters payment details on Stripe's secure page
   - Stripe processes the payment
5. **User is redirected back**
   - Success: `/checkout-success.php?session_id={CHECKOUT_SESSION_ID}`
   - Cancel: `/checkout.php`

## Setup Requirements

### 1. Stripe API Keys
Make sure your Stripe API keys are configured in ServiceCredentials:
- `stripe_private_key` - Your Stripe secret key
- `stripe_public_key` - Your Stripe publishable key

You can find these in your [Stripe Dashboard](https://dashboard.stripe.com/apikeys)

### 2. Test Mode
For testing, use Stripe's test mode keys (starting with `pk_test_` and `sk_test_`)

Test card number: `4242 4242 4242 4242`
- Any future expiry date
- Any 3-digit CVC
- Any ZIP code

## Current Limitations (By Design for Simplicity)

1. **USD Only** - Only processes cart items in USD
2. **Single Line Item** - All cart items are combined into one Stripe line item called "Kickback Kingdom Store Purchase"
3. **No Webhooks** - Payment confirmation is not yet hooked back into the cart system to mark orders as complete
4. **No Item Details** - Stripe checkout shows a generic purchase, not individual cart items

## Next Steps (Future Enhancements)

If you want to expand this basic implementation:

1. **Add Webhook Handler** - Create `/php-components/Stripe/webhook.php` to:
   - Verify webhook signature
   - Mark cart as checked out when payment succeeds
   - Update transaction records

2. **Multiple Line Items** - Pass individual cart items to Stripe:
   ```php
   'line_items' => [
       [
           'price_data' => [
               'currency' => 'usd',
               'product_data' => ['name' => 'Product 1'],
               'unit_amount' => 1000,
           ],
           'quantity' => 2,
       ],
       // ... more items
   ]
   ```

3. **Support Multiple Currencies** - Handle ADA and other currencies with conversion

4. **Order Confirmation Email** - Send receipt after successful payment

5. **Store Metadata** - Pass cart ID and user ID to Stripe:
   ```php
   'metadata' => [
       'cart_id' => $cartId->ctime . '_' . $cartId->crand,
       'user_id' => $userId,
   ]
   ```

## Testing

To test the integration:

1. Make sure you're using Stripe test mode keys
2. Add items to your cart on `/market.php`
3. Go to `/checkout.php`
4. Click "Proceed to Payment"
5. Use test card: `4242 4242 4242 4242`
6. Complete the checkout
7. Verify you're redirected to success page

## Troubleshooting

### "Cart is empty or has no USD items"
- Make sure your cart has items with USD prices
- Check that `CartController::getItemTotals()` is returning USD totals

### "No cart found"
- Cart ID is stored in sessionStorage
- Make sure you're adding items to cart properly from `/market.php`

### Stripe API Errors
- Check that your API keys are correct
- Verify you're using test mode keys for testing
- Check PHP error logs for detailed error messages

## Security Notes

- Never commit API keys to version control
- Use environment variables or secure credential storage
- Always use HTTPS in production
- Validate amounts on the server side (already done)
- Use Stripe webhooks for production payment confirmation
