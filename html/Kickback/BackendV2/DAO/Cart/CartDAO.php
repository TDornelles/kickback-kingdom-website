<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Repositories\Cart;

use Kickback\Backend\Models\Cart;
use Kickback\Backend\Views\vRecordId;

interface CartRepository
{
    public function getOrCreate(vRecordId $accountId, vRecordId $storeId) : Cart;

    public function getCartItems(vRecordId $cartId) : array;
}

?>