<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Services\Cart;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vRecordId;

interface CartService
{
    /**
    * Gets a cart for an account
    * Either selects an already existing cart or creates a new one
    * 
    * Additionally gets all items in the cart
    * @param vRecordId $accountId the account id to get the cart for
    * @param vRecordId $storeId the store to get the cart for
    * @return Response $resp the response containg the found vCart object in the data field
    */
    public function getCartForAccountWithStoreId(vRecordId $accountId, vRecordId $storeId) : Response;

    /**
    * Gets a cart for an account
    * Either selects an already existing cart or creates a new one
    * 
    * Additionally gets all items in the cart
    * @param vRecordId $accountId the account id to get the cart for
    * @param string $storeLocator the locator of the store to get the cart for
    * @return Response $resp the response containg the found vCart object in the data field
    */
    public function getCartForAccountWithStoreLocator(vRecordId $accountId, string $storeLocator) : Response;

    /**
     * Adds a product to a cart
     * 
     * This is a reference to the product itself and not a stock of the product;
     * indicating the owner of the cart wants to purchase some amount of stock of this product.
     * The stock of the product needs to be materialzied during checkout
     * 
     * @param vRecordId $cartId the cart to add the product to
     * @param vRecordId $productId the product to add to the cart
     * 
     * @return Response the response indicating success or failure
     */
    public function addProductToCart(vRecordId $cartId, vRecordId $productId) : Response;
}

?>