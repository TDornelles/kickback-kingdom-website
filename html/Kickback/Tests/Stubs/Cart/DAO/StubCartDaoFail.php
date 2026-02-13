<?php

declare(strict_types=1);

namespace Kickback\Tests\Stubs\Cart\DAO;

use Exception;
use Kickback\BackendV2\DAO\Cart\CartDAO;
use Kickback\Backend\Models\Cart;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vCartItem;
use Kickback\Backend\Views\vRecordId;

class StubCartDAOFail implements CartDAO
{
    public function getOrCreateCartIdWithStoreId(vRecordId $accountId, vRecordId $storeId) : ?Cart
    {
        return null;
    }

    public function getCartProductsViews(vRecordId $cartId) : ?array
    {
        return null;
    }

    public function getCartView(vRecordId $cartId) : ?vCart
    {
       return null;
    }

    public function addProductToCart(vRecordId $cartId, vRecordId $productId) : ?bool
    {
        return false;
    }

    public function removeProductFromCart(vCartItem $cartProduct) : ?bool
    {
        return false;
    }

    public function checkoutCart(vCart $cart) : ?bool
    {
        return null;
    }
}

?>
