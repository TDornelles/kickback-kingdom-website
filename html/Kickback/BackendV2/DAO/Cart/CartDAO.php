<?php

declare(strict_types=1);

namespace Kickback\BackendV2\DAO;

use Kickback\Backend\Models\Cart;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vRecordId;

interface CartDAO
{
    public function getOrCreateCartWithStoreId(vRecordId $accountId, vRecordId $storeId) : ?Cart;

    public function getCartItemViews(vRecordId $cartId) : ?array;

    public function getCartView(vRecordId $cartId) : ?vCart;
}

?>