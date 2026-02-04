<?php

declare(strict_types=1);

namespace Kickback\Tests\Cart\Stubs\DAO;

use Exception;
use Kickback\BackendV2\DAO\Cart\CartDAO;
use Kickback\Backend\Models\Cart;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vRecordId;

class StubCartDAOFail implements CartDAO
{
    public function getOrCreateCartWithStoreId(vRecordId $accountId, vRecordId $storeId) : ?Cart
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
}

?>
