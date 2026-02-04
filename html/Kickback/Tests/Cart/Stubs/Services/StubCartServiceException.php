<?php

declare(strict_types=1);

namespace Kickback\Tests\Cart\Stubs\Services;

use Exception;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vRecordId;
use Kickback\BackendV2\Services\Cart\CartService;

final class StubCartServiceException implements CartService
{
    public function getCartForAccountWithStoreId(vRecordId $accountId, vRecordId $storeId) : Response
    {
        throw new Exception("Stub Exception: getCartForAccountWithStoreId");
    }

    public function getCartForAccountWithStoreLocator(vRecordId $accountId, string $storeLocator) : Response
    {
        throw new Exception("Stub Exception: getCartForAccountWithStoreLocator");
    }

    public function addProductToCart(vRecordId $cartId, vRecordId $productId) : Response
    {
        throw new Exception("Stub Exception: addProductToCart");
    }
}

?>
