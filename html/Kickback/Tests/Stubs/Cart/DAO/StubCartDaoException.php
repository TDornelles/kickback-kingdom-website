<?php

declare(strict_types=1);

namespace Kickback\Tests\Stubs\Cart\DAO;

use Exception;
use Kickback\BackendV2\DAO\Cart\CartDAO;
use Kickback\Backend\Models\Cart;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vCartItem;
use Kickback\Backend\Views\vRecordId;

class StubCartDAOException implements CartDAO
{
    public function getOrCreateCartIdWithStoreId(vRecordId $accountId, vRecordId $storeId) : ?Cart
    {
        throw new Exception("Stub Exception : Failed to get or create cart");
    }

    public function getCartProductsViews(vRecordId $cartId) : array
    {
        throw new Exception("Stub Exception : Failed to get cart items");
    }

    public function getCartView(vRecordId $cartId) : ?vCart
    {
        throw new Exception("Stub Exception : Failed to get cart view");
    }

    public function addProductToCart(vRecordId $cartId, vRecordId $productId) : ?bool
    {
        throw new Exception("Stub Exception : Failed to add product to cart");
    }

    public function removeProductFromCart(vCartItem $cartProduct) : ?bool
    {
        throw new Exception("Stub Exception : Failed to remove product from cart");
    }

    public function checkoutCart(vCart $cart) : ?bool
    {
        throw new Exception("Stub Exception : Failed to checkout cart");
    }

    public function setStripeSessionId(vRecordId $cartId, string $sessionId) : bool
    {
        throw new Exception("Stub Exception : Failed to set stripe session id");
    }

    public function createStripeTransaction(vRecordId $cartId, string $stripeTransactionId) : bool
    {
        throw new Exception("Stub Exception : Failed to create stripe transaction");
    }

    public function getCartByStripeSessionId(string $sessionId) : ?vCart
    {
        throw new Exception("Stub Exception : Failed to get cart by stripe session id");
    }
}

?>
