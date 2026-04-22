<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Services\Payment;

use Exception;
use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Controllers\StripeController;
use Kickback\Backend\Views\vCart;

use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Checkout\Session;

class StripePaymentService implements PaymentService
{
    private StripeClient $stripe;
    private string $webhookSecret;

    public function __construct(?string $secretKey = null, ?string $webhookSecret = null)
    {
        $secretKey = $secretKey ?? StripeController::privateKey();
        $this->webhookSecret = $webhookSecret ?? (string)ServiceCredentials::get("stripe_webhook_secret");
        $this->stripe = new StripeClient($secretKey);
    }

    public function createCheckoutSession(vCart $cart, int $usdTotalCents) : array
    {
        if ($usdTotalCents <= 0)
        {
            throw new Exception("USD total must be greater than zero");
        }

        $lineItems = $this->buildLineItemsFromCart($cart, $usdTotalCents);

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        $checkoutSession = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'metadata' => [
                'cart_ctime' => $cart->ctime,
                'cart_crand' => (string)$cart->crand,
            ],
            'success_url' => "{$protocol}://{$host}/checkout-success.php?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => "{$protocol}://{$host}/checkout.php",
        ]);

        return [
            'sessionId' => $checkoutSession->id,
            'url' => $checkoutSession->url,
        ];
    }

    public function verifyWebhookSignature(string $payload, string $sigHeader) : object
    {
        if (empty($this->webhookSecret))
        {
            throw new Exception("Stripe webhook secret is not configured");
        }

        return Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
    }

    public function getSessionStatus(string $sessionId) : string
    {
        $session = $this->stripe->checkout->sessions->retrieve($sessionId);
        return $session->payment_status;
    }

    private function buildLineItemsFromCart(vCart $cart, int $usdTotalCents) : array
    {
        $productNames = [];
        if (!empty($cart->cartProducts))
        {
            foreach ($cart->cartProducts as $cartItem)
            {
                $name = $cartItem->product->name ?? 'Product';
                $productNames[] = $name;
            }
        }

        $description = !empty($productNames)
            ? implode(', ', array_unique($productNames))
            : 'Store Purchase';

        return [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Kickback Kingdom Store Purchase',
                    'description' => $description,
                ],
                'unit_amount' => $usdTotalCents,
            ],
            'quantity' => 1,
        ]];
    }
}

?>
