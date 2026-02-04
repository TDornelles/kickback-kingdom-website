<?php

declare(strict_types=1);

namespace Kickback\Tests\Cart\Stubs\Services;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vRecordId;
use Kickback\BackendV2\Services\Cart\CartService;

final class StubCartServiceSuccess implements CartService
{
    public function getCartForAccountWithStoreId(vRecordId $accountId, vRecordId $storeId) : Response
    {
        $cartView = new vCart($accountId->ctime, $accountId->crand);
        return new Response(true, "Stub success", $cartView);
    }

    public function getCartForAccountWithStoreLocator(vRecordId $accountId, string $storeLocator) : Response
    {
        $cartView = new vCart($accountId->ctime, $accountId->crand);
        return new Response(true, "Stub success", $cartView);
    }

    public function addProductToCart(vRecordId $cartId, vRecordId $productId) : Response
    {
        return new Response(true, "Stub success", true);
    }
}

?>
