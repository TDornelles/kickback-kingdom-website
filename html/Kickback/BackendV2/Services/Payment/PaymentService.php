<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Services\Payment;

use Kickback\Backend\Views\vCart;

interface PaymentService
{
    /**
     * Creates a Stripe Checkout Session for the given cart
     *
     * @param vCart $cart the cart to create a checkout session for
     * @param int $usdTotalCents the total USD amount in cents
     *
     * @return array{sessionId: string, url: string} the session ID and redirect URL
     */
    public function createCheckoutSession(vCart $cart, int $usdTotalCents) : array;

    /**
     * Verifies a Stripe webhook signature and returns the parsed event
     *
     * @param string $payload the raw POST body from Stripe
     * @param string $sigHeader the Stripe-Signature header value
     *
     * @return object the verified Stripe event object
     */
    public function verifyWebhookSignature(string $payload, string $sigHeader) : object;

    /**
     * Gets the payment status for a Stripe checkout session
     *
     * @param string $sessionId the Stripe checkout session ID
     *
     * @return string the payment status (e.g. 'paid', 'unpaid', 'no_payment_required')
     */
    public function getSessionStatus(string $sessionId) : string;
}

?>
