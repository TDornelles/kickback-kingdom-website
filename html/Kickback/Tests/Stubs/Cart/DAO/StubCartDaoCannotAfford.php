<?php

declare(strict_types=1);

namespace Kickback\Tests\Stubs\Cart\DAO;

use Kickback\BackendV2\DAO\Cart\CartDAO;
use Kickback\Backend\Models\Cart;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vCartItem;
use Kickback\Backend\Views\vRecordId;

final class StubCartDAOCannotAfford implements CartDAO
{
    public function getOrCreateCartIdWithStoreId(vRecordId $accountId, vRecordId $storeId) : ?Cart
    {
        return new Cart($accountId->ctime, $accountId->crand, $storeId->ctime, $storeId->crand, null, null);
    }

    public function getCartProductsViews(vRecordId $cartId) : ?array
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

    public function removeProductFromCart(vCartItem $cartProduct) : ?bool
    {
        return true;
    }

    public function checkoutCart(vCart $cart) : ?bool
    {
        return false;
    }

    public function setStripeSessionId(vRecordId $cartId, string $sessionId) : bool
    {
        return true;
    }

    public function createStripeTransaction(vRecordId $cartId, string $stripeTransactionId) : bool
    {
        return true;
    }

    public function getCartByStripeSessionId(string $sessionId) : ?vCart
    {
        return null;
    }
}

?>
