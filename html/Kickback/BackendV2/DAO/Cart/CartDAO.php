<?php

declare(strict_types=1);

namespace Kickback\BackendV2\DAO\Cart;

use Kickback\Backend\Models\Cart;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vRecordId;

interface CartDAO
{
    public function getOrCreateCartWithStoreId(vRecordId $accountId, vRecordId $storeId) : ?Cart;

    public function getCartProductsViews(vRecordId $cartId) : ?array;

    public function getCartView(vRecordId $cartId) : ?vCart;

    public function addProductToCart(vRecordId $cartId, vRecordId $productId) : ?bool;
}

?>