<?php
declare(strict_types=1);

namespace Kickback\Services;

use Stripe\Stripe;
use Stripe\StripeClient;
use Kickback\Backend\Config\ServiceCredentials;

class StripeService
{
    private static ?StripeClient $client = null;
    private static bool $initialized = false;
    private const DEFAULT_CURRENCY = 'USD';
    /** @var array<string> */
    private const SUPPORTED_CURRENCIES = ['USD'];

    /**
     * Initialize Stripe with API key
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        $secretKey = ServiceCredentials::get("stripe_secret_key");
        if (empty($secretKey)) {
            throw new \Exception("Stripe secret key not configured");
        }

        Stripe::setApiKey($secretKey);
        self::$client = new StripeClient($secretKey);
        self::$initialized = true;
    }

    /**
     * Get the Stripe client instance
     */
    public static function getClient(): StripeClient
    {
        if (!self::$initialized) {
            self::initialize();
        }
        return self::$client;
    }

    /**
     * Get the publishable key for frontend
     */
    public static function getPublishableKey(): string
    {
        $publishableKey = ServiceCredentials::get("stripe_publishable_key");
        if (empty($publishableKey)) {
            throw new \Exception("Stripe publishable key not configured");
        }
        return $publishableKey;
    }

    /**
     * Check if Stripe is configured
     */
    public static function isConfigured(): bool
    {
        $secretKey = ServiceCredentials::get("stripe_secret_key");
        $publishableKey = ServiceCredentials::get("stripe_publishable_key");
        return !empty($secretKey) && !empty($publishableKey);
    }

    /**
     * Get the default currency used by the site.
     */
    public static function getDefaultCurrency(): string
    {
        return self::DEFAULT_CURRENCY;
    }

    /**
     * Get the list of supported currencies.
     *
     * @return array<string>
     */
    public static function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    /**
     * Check if a given currency is supported (case-insensitive).
     */
    public static function isCurrencySupported(string $currency): bool
    {
        $upper = strtoupper($currency);
        foreach (self::SUPPORTED_CURRENCIES as $supported) {
            if ($upper === strtoupper($supported)) {
                return true;
            }
        }
        return false;
    }
}