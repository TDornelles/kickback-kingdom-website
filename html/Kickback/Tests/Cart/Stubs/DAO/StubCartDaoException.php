<?php

declare(strict_types=1);

namespace Kickback\Tests\Cart\Stubs\DAO;

use Exception;
use Kickback\BackendV2\DAO\Cart\CartDAO;
use Kickback\Backend\Models\Cart;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vRecordId;

class StubCartDAOException implements CartDAO
{
    public function getOrCreateCartWithStoreId(vRecordId $accountId, vRecordId $storeId) : ?Cart
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
}

?>
