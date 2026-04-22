<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\DAO\Product;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vProduct;

interface ProductDAO
{
    /**
     * Returns all the products for a store with the matching Id
     * 
     * @param vRecordId $storeId the id of the store for which to get all products
     * 
     * @return ?array the array of vProduct objects for the matching
     */
    public function getProductsForStoreByStoreId(vRecordId $storeId) : ?array;

    /**
     * Returns a product by locator with its base price populated
     *
     * @param string $productLocator the locator to lookup
     *
     * @return ?vProduct the product view or null on failure/not found
     */
    public function getProductByLocator(string $productLocator) : ?vProduct;

    /**
     * Closes the reservations for the given reservation ids
     * 
     * @param array $reservations the reservation ids to close
     * 
     * @return void
     */
    public function closeProductReservations(array $reservations) : void;

    /**
     * Returns the active product reservations for a given cart id
     * 
     * @param vRecordId $cartId the cart id for which to get the active reservations
     * 
     * @return array the active product reservations for the given cart id
     */
    public function getActiveProductReservationsForCart(vRecordId $cartId) : array;

    /**
     * Returns the amount of a product available for purchase
     * 
     * @param vRecordId $productId the id of the product for which to get the amount available
     * 
     * @return ?int the amount of the product available for purchase or null on failure/not found
     */
    public function getProductAmountAvailable(vRecordId $productId) : ?int;

    /**
     * Returns the base price component ids for a given product id
     * 
     * @param vRecordId $productId the id of the product for which to get the base price component ids
     * 
     * @return ?array the base price component ids for the given product id or null on failure/not found
     */
    public function getBasePriceComponentIdsForProduct(vRecordId $productId) : ?array;

    /**
     * Reserves stock for the given product entry quantities and cart id
     * 
     * @param array $productEntryQuantities the product entry quantities to reserve stock for
     * @param vRecordId $cartId the cart id for which to reserve stock
     * 
     * @return ?bool true if the stock was successfully reserved, false if the stock could not be reserved, or null on failure
     */
    public function reserveStockForProducts(array $productEntryQuantities, vRecordId $cartId) : ?bool;
    
    /**
     * Returns whether the given products are available in the store with the given id
     * 
     * @param vRecordId $storeId the id of the store for which to check product availability
     * @param vRecordId[] $productIds the ids of the products for which to check availability
     * 
     * @return ?array an array of the available product ids for the given store id and product ids, null on failure
     *  eg:
     *      ["productId" => vRecordId, "quantityAvailable" => int]
     */
    public function areProductsAvailableInStore(vRecordId $storeId, array $productIds) : ?array;
}

?>
