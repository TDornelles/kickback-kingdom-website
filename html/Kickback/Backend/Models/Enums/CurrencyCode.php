<?php

declare(strict_types=1);

namespace Kickback\Backend\Models\Enums;

/**
 * Currency Code Enum
 *
 * Represents supported currency codes for the store system
 */
enum CurrencyCode: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case ADA = 'ADA'; // Cardano, mentioned in STRIPE_INTEGRATION.md
    // Add more currencies as needed
}

?>
