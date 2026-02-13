<?php

declare(strict_types=1);

namespace Kickback\BackendV2\DAO\Cart;

use Kickback\Backend\Models\Cart;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vCartItem;
use Kickback\Backend\Views\vRecordId;

interface CartDAO
{
    /**
     * Gets the cart for the provided account and store, or creates one if it does not exist
     * 
     * @param vRecordId $accountId the account id for which to get or create the cart
     * @param vRecordId $storeId the store id for which to get or create the cart
     * 
     * @return vRecordId|null the id cart for the provided account and store, or null on failure
     */
    public function getOrCreateCartIdWithStoreId(vRecordId $accountId, vRecordId $storeId) : ?vRecordId;

    /**
     * Gets the cart products for a given cart id
      * 
      * @param vRecordId $cartId the cart id for which to get the cart products
      * 
      * @return ?array the array of vCartItem objects for the given cart id, or null on failure
     */
    public function getCartProductsViews(vRecordId $cartId) : ?array;

    /**
     * Returns the view of the cart for a given cart id
     * 
     * @param vRecordId $cartId the cart id for which to get the cart view
     * 
     * @return ?vCart the cart view for the given cart id, or null on failure
     */
    public function getCartView(vRecordId $cartId) : ?vCart;

    /**
     * Adds a product to the cart
     * 
     * @param vRecordId $cartId the cart id to which to add the product
     * @param vRecordId $productId the product id of the product to add to the cart
     * 
     * @return ?bool true on success, false on not enough stock available, or null if the cart or product was not found or another failure occurred
     */
    public function addProductToCart(vRecordId $cartId, vRecordId $productId) : ?bool;

    /**
     * Removes a product from the cart
     * 
     * @param vCartItem $cartProduct the cart product to remove from the cart
     * 
     * @return ?bool true on success, false on failure, or null if the cart product was not found
     */
    public function removeProductFromCart(vCartItem $cartProduct) : ?bool;

    /**
     * Checks out the cart, creating an order and clearing the cart
     * 
     * @param vCart $cart the cart to checkout
     * 
     * @return Response the response of the checkout operation
     */
    public function checkoutCart(vCart $cart) : ?bool;
}

?>
