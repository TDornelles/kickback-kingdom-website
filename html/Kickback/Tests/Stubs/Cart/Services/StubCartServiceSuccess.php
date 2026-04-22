<?php

declare(strict_types=1);

namespace Kickback\Tests\Stubs\Cart\Services;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vCartItem;
use Kickback\Backend\Views\vProduct;
use Kickback\Backend\Views\vRecordId;
use Kickback\BackendV2\Services\Cart\CartService;

final class StubCartServiceSuccess implements CartService
{
    public function getCartForAccountWithStoreId(vRecordId $accountId, vRecordId $storeId) : Response
    {
        $cartView = new vCart($accountId->ctime, $accountId->crand);
        $cartView->cartProducts = [static::makeCartItem()];
        return new Response(true, "Stub success", $cartView);
    }

    public function getCartForAccountWithStoreLocator(vRecordId $accountId, string $storeLocator) : Response
    {
        $cartView = new vCart($accountId->ctime, $accountId->crand);
        $cartView->cartProducts = [static::makeCartItem()];
        return new Response(true, "Stub success", $cartView);
    }

    public function addProductToCart(vRecordId $cartId, vRecordId $productId) : Response
    {
        return new Response(true, "Stub success", true);
    }

    public function removeProductFromCart(vCartItem $cartProduct) : Response
    {
        return new Response(true, "Stub success", true);
    }

    public function checkoutCart(vRecordId $accountId, string $storeLocator) : Response
    {
        return new Response(true, "Stub success", true);
    }

    private static function makeCartItem() : vCartItem
    {
        $cartItem = new vCartItem('cartProductCtime', -1);
        $cartItem->product = new vProduct('testProductCtime', -1);
        return $cartItem;
    }
}

?>
