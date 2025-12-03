<?php

declare(strict_types = 1);

namespace Kickback\Backend\Models;

use Kickback\Backend\Models\Enums\CurrencyCode;
use Kickback\Backend\Models\Enums\TransactionType;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vLoot;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vTransaction;

class TransactionComponent extends RecordId
{
    public Transaction $transaction;
    public vAccount $fromAccount;
    public vAccount $toAccount;
    public int $amount;
    public ?vLoot $loot;
    public ?CurrencyCode $currencyCode;
}

?>