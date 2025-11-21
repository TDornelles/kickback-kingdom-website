<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use \Kickback\Backend\Config\ServiceCredentials;

/**
 * StripeController - Helper methods for Stripe integration
 */
class StripeController
{
    /**
     * Get the Stripe publishable key
     *
     * @return string The Stripe public/publishable key
     */
    public static function publicKey(): string
    {
        return ServiceCredentials::get("stripe_public_key");
    }

    /**
     * Get the Stripe secret key
     *
     * @return string The Stripe secret key
     */
    public static function privateKey(): string
    {
        return ServiceCredentials::get("stripe_private_key");
    }
}

?>
