# Minimal Stripe Setup Intent Implementation

The absolute bare minimum Stripe integration using Setup Intents. No database operations, no webhooks, no complexity.

## 📁 Files (Only 4!)

1. **`html/Kickback/Services/StripeService.php`** - Basic Stripe configuration
2. **`html/api/v2/payments/config.php`** - Get Stripe publishable key
3. **`html/api/v2/payments/create-setup-intent.php`** - Create Setup Intent for saving payment methods
4. **`html/api/v2/payments/process-payment.php`** - Process immediate payments

## ⚙️ Configuration

Add to your `credentials.ini`:

```ini
stripe_publishable_key = "pk_test_your_key_here"
stripe_secret_key      = "sk_test_your_key_here"
```

## 🚀 Usage

### 1. Setup Payment Method (One-time)

```javascript
// Get Stripe config
const config = await fetch('/api/v2/payments/config.php').then(r => r.json());
const stripe = Stripe(config.data.stripe_publishable_key);

// Create Setup Intent
const setupResponse = await fetch('/api/v2/payments/create-setup-intent.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        description: "Save payment method"
    })
});

const {client_secret} = setupResponse.data;

// Confirm Setup Intent with Stripe Elements
const {error, setupIntent} = await stripe.confirmSetup({
    elements, // Your Stripe Elements
    clientSecret: client_secret,
    confirmParams: {
        return_url: 'https://yoursite.com/success'
    }
});

// Save the payment method ID for future use
const paymentMethodId = setupIntent.payment_method;
```

### 2. Process Payment (Using saved payment method)

```javascript
// Process payment immediately
const paymentResponse = await fetch('/api/v2/payments/process-payment.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        payment_method_id: 'pm_1234567890', // From Setup Intent
        amount: 29.99,
        description: "Guild membership fee"
    })
});

if (paymentResponse.success) {
    console.log('Payment successful!', paymentResponse.data);
} else {
    console.log('Payment failed:', paymentResponse.message);
}
```

## 🎯 What This Does

- ✅ **Setup Intent**: Securely saves payment methods for future use
- ✅ **Immediate Payment**: Processes payments instantly without storing data
- ✅ **No Database**: Zero database operations - pure Stripe processing
- ✅ **No Webhooks**: Direct payment confirmation
- ✅ **Minimal Code**: Just 4 files, ~200 lines total

## 🔒 Security

- User authentication required
- Stripe handles all payment data
- No sensitive data stored locally
- Payment methods saved in Stripe Vault

## 💡 Perfect For

- Donations
- Membership fees
- One-time purchases
- Subscription setup
- Any payment without complex order management

That's it! The simplest possible Stripe integration with Setup Intents.