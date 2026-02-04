<?php

declare(strict_types=1);

namespace Kickback\Tests\Cart\Stubs\DAO;

use Kickback\BackendV2\DAO\Cart\CartDAO;
use Kickback\Backend\Models\Cart;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vRecordId;

class StubCartDAOSuccess implements CartDAO
{
    public function getOrCreateCartWithStoreId(vRecordId $accountId, vRecordId $storeId) : ?Cart
    {
        return new Cart($accountId->ctime, $accountId->crand, $storeId->ctime, $storeId->crand, null, null);
    }

    public function getCartProductsViews(vRecordId $cartId) : array
    {
        return [];
    }

    public function getCartView(vRecordId $cartId) : ?vCart
    {
        return new vCart($cartId->ctime, $cartId->crand);
    }

    public function addProductToCart(vRecordId $cartId, vRecordId $productId) : ?bool
    {
        return true;
    }
}

?>
