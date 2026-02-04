<?php

declare(strict_types=1);

namespace Kickback\Tests\Cart\Stubs\Services;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vRecordId;
use Kickback\BackendV2\Services\Cart\CartService;

final class StubCartServiceFail implements CartService
{
    public function getCartForAccountWithStoreId(vRecordId $accountId, vRecordId $storeId) : Response
    {
        return new Response(false, "Stub fail", null);
    }

    public function getCartForAccountWithStoreLocator(vRecordId $accountId, string $storeLocator) : Response
    {
        return new Response(false, "Stub fail", null);
    }

    public function addProductToCart(vRecordId $cartId, vRecordId $productId) : Response
    {
        return new Response(false, "Stub fail", null);
    }
}

?>
