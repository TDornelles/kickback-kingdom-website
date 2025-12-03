<?php

declare(strict_types = 1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Models\Enums\CurrencyCode;

class vTransactionComponent extends vRecordId
{
    public vTransaction $transaction;
    public vAccount $fromAccount;
    public vAccount $toAccount;
    public int $amount;
    public ?vLoot $loot;
    public ?CurrencyCode $currencyCode;
}

?>